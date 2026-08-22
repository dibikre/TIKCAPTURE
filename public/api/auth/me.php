<?php
/**
 * GET /api/auth/me.php
 * Header : Authorization: Bearer <token>
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/jwt_helper.php';

$token   = JWTHelper::getBearerToken();
$payload = $token ? JWTHelper::verifyToken($token) : false;

if (!$payload) {
    http_response_code(401);
    echo json_encode(['status'=>401,'msg'=>'Token invalide ou expiré']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$payload['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['status'=>404,'msg'=>'Utilisateur introuvable']);
        exit;
    }

    echo json_encode([
        'status' => 200,
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