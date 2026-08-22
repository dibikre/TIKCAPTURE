<?php
/**
 * POST /api/auth/login.php
 * Body JSON : { email, password }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['status'=>405,'msg'=>'POST requis']); exit; }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/jwt_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email']    ?? '');
$password = trim($input['password'] ?? '');

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['status'=>400,'msg'=>'Email et mot de passe requis']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['status'=>401,'msg'=>'Email ou mot de passe incorrect']);
        exit;
    }

    if (!$user['is_active']) {
        http_response_code(403);
        echo json_encode(['status'=>403,'msg'=>'Compte désactivé']);
        exit;
    }

    // Mettre à jour last_login
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
        ->execute([$user['id']]);

    $token = JWTHelper::generateToken([
        'user_id'  => $user['id'],
        'email'    => $user['email'],
        'username' => $user['username'],
    ]);

    echo json_encode([
        'status' => 200,
        'msg'    => 'Connexion réussie',
        'token'  => $token,
        'user'   => [
            'id'                     => $user['id'],
            'username'               => $user['username'],
            'email'                  => $user['email'],
            'full_name'              => $user['full_name'],
            'avatar_url'             => $user['avatar_url'],
            'is_premium'             => (int)$user['is_premium'],
            'subscription_plan'      => $user['subscription_plan'],
            'subscription_expires_at'=> $user['subscription_expires_at'],
        ],
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status'=>500,'msg'=>'Erreur serveur']);
}