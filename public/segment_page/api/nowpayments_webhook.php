<?php
// tikcapture.live/segment_page/api/nowpayments_webhook.php
// Ce fichier reçoit les callbacks IPN de NowPayments

require_once __DIR__ . '/../../config/db.php';

define('NOWPAYMENTS_IPN_SECRET', 'CHANGE_THIS_SECRET_IN_DASHBOARD');

// ── Lire le payload ───────────────────────────────────────────
$rawBody = file_get_contents('php://input');
$data    = json_decode($rawBody, true);

if (empty($data)) {
    http_response_code(400);
    exit('Invalid payload');
}

// ── Vérifier la signature NowPayments ─────────────────────────
$receivedSig = $_SERVER['HTTP_X_NOWPAYMENTS_SIG'] ?? '';

if (!empty(NOWPAYMENTS_IPN_SECRET) && NOWPAYMENTS_IPN_SECRET !== 'CHANGE_THIS_SECRET_IN_DASHBOARD') {
    ksort($data);
    $expectedSig = hash_hmac('sha512', json_encode($data), NOWPAYMENTS_IPN_SECRET);
    if (!hash_equals($expectedSig, $receivedSig)) {
        http_response_code(401);
        error_log('NowPayments webhook: invalid signature');
        exit('Invalid signature');
    }
}

// ── Extraire les données ──────────────────────────────────────
$paymentId     = $data['payment_id']     ?? '';
$orderId       = $data['order_id']       ?? '';
$status        = $data['payment_status'] ?? '';
$actuallyPaid  = $data['actually_paid']  ?? 0;
$payCurrency   = $data['pay_currency']   ?? '';
$payAmount     = $data['pay_amount']     ?? 0;

error_log("NowPayments webhook: payment_id=$paymentId order_id=$orderId status=$status");

try {
    // Trouver le paiement en base
    $stmt = $pdo->prepare("
        SELECT * FROM payments WHERE order_id = ? OR payment_id = ? LIMIT 1
    ");
    $stmt->execute([$orderId, $paymentId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        http_response_code(404);
        error_log("NowPayments webhook: payment not found order=$orderId");
        exit('Payment not found');
    }

    // Mettre à jour le statut
    $stmt = $pdo->prepare("
        UPDATE payments
        SET status = ?, pay_currency = ?, pay_amount = ?, actually_paid = ?,
            payment_id = COALESCE(NULLIF(payment_id, ''), ?), updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$status, $payCurrency, $payAmount, $actuallyPaid, $paymentId, $payment['id']]);

    // ── Activer le premium si paiement confirmé ────────────────
    if (in_array($status, ['finished', 'confirmed', 'partially_paid'])) {
        $userId = $payment['user_id'];
        $plan   = $payment['plan'];

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
        $current      = $stmt->fetch();
        $currentExpiry = $current['subscription_expires_at'];

        if ($currentExpiry && strtotime($currentExpiry) > time()) {
            $newExpiry = date('Y-m-d H:i:s', strtotime($currentExpiry . ' +' . $d . ' days'));
        } else {
            $newExpiry = date('Y-m-d H:i:s', strtotime('+' . $d . ' days'));
        }

        $stmt = $pdo->prepare("
            UPDATE users
            SET is_premium = 1, subscription_plan = ?, subscription_expires_at = ?
            WHERE id = ?
        ");
        $stmt->execute([$plan, $newExpiry, $userId]);

        error_log("NowPayments webhook: premium activated user=$userId plan=$plan expires=$newExpiry");
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    http_response_code(500);
    error_log('NowPayments webhook error: ' . $e->getMessage());
    exit('Server error');
}