<?php
// tikcapture.live/segment_page/api/subscription.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/jwt_helper.php';
require_once __DIR__ . '/../../helpers/api_response.php';

// ── Config NowPayments ────────────────────────────────────────
define('NOWPAYMENTS_API_KEY',    'FMH8FJS-7T1M43R-KAQN6CC-NYZ7CQZ');
define('NOWPAYMENTS_IPN_SECRET', 'CHANGE_THIS_SECRET_IN_DASHBOARD'); // à définir dans NowPayments dashboard
define('NOWPAYMENTS_API_URL',    'https://api.nowpayments.io/v1');
define('WEBHOOK_URL',            'https://tikcapture.live/segment_page/api/nowpayments_webhook.php');
define('SUCCESS_URL',            'https://tikcapture.live/payment-success');
define('CANCEL_URL',             'https://tikcapture.live/payment-cancel');

// ── Plans d'abonnement ────────────────────────────────────────
$PLANS = [
    'weekly'    => ['label' => 'Semaine',    'days' => 7,   'price' => 1.50],
    'monthly'   => ['label' => 'Mensuel',    'days' => 30,  'price' => 3.50],
    'quarterly' => ['label' => 'Trimestriel','days' => 90,  'price' => 10.00],
    'biannual'  => ['label' => 'Semestriel', 'days' => 180, 'price' => 17.50],
    'annual'    => ['label' => 'Annuel',     'days' => 365, 'price' => 30.00],
];

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = [];
if ($method === 'POST') {
    $json  = file_get_contents('php://input');
    $input = json_decode($json, true) ?: [];
}

// ── Auth helper ───────────────────────────────────────────────
function requireAuth() {
    $token   = JWTHelper::getBearerToken();
    if (!$token) APIResponse::error('Non authentifié', 401);
    $payload = JWTHelper::verifyToken($token);
    if (!$payload) APIResponse::error('Token invalide', 401);
    return $payload;
}

switch ($action) {
    case 'plans':
        handleGetPlans($PLANS);
        break;
    case 'create-invoice':
        handleCreateInvoice($pdo, $input, $PLANS);
        break;
    case 'verify-payment':
        handleVerifyPayment($pdo, $input);
        break;
    case 'status':
        handleGetStatus($pdo);
        break;
    default:
        APIResponse::error('Action non reconnue', 400);
}

// ── GET /plans ────────────────────────────────────────────────
function handleGetPlans($plans) {
    APIResponse::success($plans, 'Plans récupérés');
}

// ── POST /create-invoice ──────────────────────────────────────
function handleCreateInvoice($pdo, $data, $plans) {
    $payload = requireAuth();
    $userId  = $payload['sub'];
    $plan    = $data['plan'] ?? '';

    if (!isset($plans[$plan])) {
        APIResponse::error('Plan invalide', 400);
    }

    $planData = $plans[$plan];
    $orderId  = 'TC_' . $userId . '_' . $plan . '_' . time();

    // Créer l'invoice NowPayments
    $invoiceData = [
        'price_amount'    => $planData['price'],
        'price_currency'  => 'usd',
        'order_id'        => $orderId,
        'order_description'=> 'TikCapture Premium — ' . $planData['label'],
        'ipn_callback_url'=> WEBHOOK_URL,
        'success_url'     => SUCCESS_URL . '?order=' . $orderId,
        'cancel_url'      => CANCEL_URL,
    ];

    $ch = curl_init(NOWPAYMENTS_API_URL . '/invoice');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($invoiceData),
        CURLOPT_HTTPHEADER     => [
            'x-api-key: ' . NOWPAYMENTS_API_KEY,
            'Content-Type: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 && $httpCode !== 201) {
        error_log('NowPayments error: ' . $response);
        APIResponse::error('Erreur création paiement: ' . $response, 500);
    }

    $invoice = json_decode($response, true);

    if (empty($invoice['id']) || empty($invoice['invoice_url'])) {
        APIResponse::error('Réponse NowPayments invalide', 500);
    }

    // Sauvegarder en base
    try {
        $stmt = $pdo->prepare("
            INSERT INTO payments (user_id, payment_id, order_id, plan, amount_usd, status, payment_url)
            VALUES (?, ?, ?, ?, ?, 'waiting', ?)
        ");
        $stmt->execute([
            $userId,
            $invoice['id'],
            $orderId,
            $plan,
            $planData['price'],
            $invoice['invoice_url'],
        ]);
    } catch (PDOException $e) {
        error_log('DB error: ' . $e->getMessage());
        APIResponse::error('Erreur base de données', 500);
    }

    APIResponse::success([
        'payment_id'  => $invoice['id'],
        'order_id'    => $orderId,
        'invoice_url' => $invoice['invoice_url'],
        'plan'        => $plan,
        'amount'      => $planData['price'],
        'label'       => $planData['label'],
    ], 'Invoice créée');
}

// ── POST /verify-payment ──────────────────────────────────────
function handleVerifyPayment($pdo, $data) {
    $payload   = requireAuth();
    $userId    = $payload['sub'];
    $orderId   = $data['order_id'] ?? '';
    $paymentId = $data['payment_id'] ?? '';

    if (empty($orderId) && empty($paymentId)) {
        APIResponse::error('order_id ou payment_id requis', 400);
    }

    // Chercher le paiement en base
    $stmt = $pdo->prepare("
        SELECT * FROM payments
        WHERE user_id = ? AND (order_id = ? OR payment_id = ?)
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$userId, $orderId, $paymentId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        APIResponse::error('Paiement non trouvé', 404);
    }

    // Vérifier le statut via NowPayments API
    $ch = curl_init(NOWPAYMENTS_API_URL . '/payment/' . $payment['payment_id']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['x-api-key: ' . NOWPAYMENTS_API_KEY],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $npData = json_decode($response, true);
    $status = $npData['payment_status'] ?? $payment['status'];

    // Mettre à jour le statut en base
    $stmt = $pdo->prepare("UPDATE payments SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $payment['id']]);

    // Si payé → activer le premium
    if (in_array($status, ['finished', 'confirmed', 'partially_paid'])) {
        _activatePremium($pdo, $userId, $payment['plan']);
        $stmt = $pdo->prepare("SELECT is_premium, subscription_plan, subscription_expires_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        APIResponse::success([
            'status'      => $status,
            'is_premium'  => true,
            'plan'        => $payment['plan'],
            'expires_at'  => $user['subscription_expires_at'],
        ], 'Paiement confirmé, premium activé');
    }

    APIResponse::success(['status' => $status], 'Statut récupéré');
}

// ── GET /status ───────────────────────────────────────────────
function handleGetStatus($pdo) {
    $payload = requireAuth();
    $userId  = $payload['sub'];

    $stmt = $pdo->prepare("
        SELECT is_premium, subscription_plan, subscription_expires_at, invitation_code
        FROM users WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) APIResponse::error('Utilisateur non trouvé', 404);

    // Vérifier si l'abonnement a expiré
    $isActive = false;
    if ($user['is_premium'] && $user['subscription_expires_at']) {
        $isActive = strtotime($user['subscription_expires_at']) > time();
        if (!$isActive) {
            // Désactiver le premium expiré
            $stmt = $pdo->prepare("UPDATE users SET is_premium = 0 WHERE id = ?");
            $stmt->execute([$userId]);
        }
    }

    APIResponse::success([
        'is_premium'   => $isActive,
        'plan'         => $user['subscription_plan'],
        'expires_at'   => $user['subscription_expires_at'],
        'invitation_code' => $user['invitation_code'],
    ], 'Statut récupéré');
}

// ── Helper : activer le premium ───────────────────────────────
function _activatePremium($pdo, $userId, $plan) {
    $days = [
        'weekly'    => 7,
        'monthly'   => 30,
        'quarterly' => 90,
        'biannual'  => 180,
        'annual'    => 365,
    ];
    $d = $days[$plan] ?? 30;

    // Vérifier si déjà premium → prolonger
    $stmt = $pdo->prepare("SELECT subscription_expires_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $current = $stmt->fetch();
    $currentExpiry = $current['subscription_expires_at'];

    if ($currentExpiry && strtotime($currentExpiry) > time()) {
        // Prolonger depuis la date actuelle d'expiration
        $newExpiry = date('Y-m-d H:i:s', strtotime($currentExpiry . ' +' . $d . ' days'));
    } else {
        // Nouvelle souscription
        $newExpiry = date('Y-m-d H:i:s', strtotime('+' . $d . ' days'));
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET is_premium = 1, subscription_plan = ?, subscription_expires_at = ?
        WHERE id = ?
    ");
    $stmt->execute([$plan, $newExpiry, $userId]);
}