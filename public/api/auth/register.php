<?php
/**
 * POST /api/auth/register.php
 * Body JSON : { username, email, password, full_name? }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); echo json_encode(['status'=>405,'msg'=>'POST requis']); exit; }

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../helpers/jwt_helper.php';

$input     = json_decode(file_get_contents('php://input'), true);
$username  = trim($input['username']  ?? '');
$email     = trim($input['email']     ?? '');
$password  = trim($input['password']  ?? '');
$full_name = trim($input['full_name'] ?? '');

// Validation
if (!$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['status'=>400,'msg'=>'Username, email et mot de passe requis']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status'=>400,'msg'=>'Email invalide']);
    exit;
}
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['status'=>400,'msg'=>'Mot de passe trop court (6 caractères minimum)']);
    exit;
}
if (strlen($username) < 3 || strlen($username) > 50) {
    http_response_code(400);
    echo json_encode(['status'=>400,'msg'=>'Username : 3 à 50 caractères']);
    exit;
}

try {
    // Vérifier doublon
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
    $stmt->execute([$email, $username]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['status'=>409,'msg'=>'Email ou username déjà utilisé']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_ARGON2ID);

    $pdo->prepare("
        INSERT INTO users (username, email, password_hash, full_name, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, 1, NOW(), NOW())
    ")->execute([$username, $email, $hash, $full_name ?: null]);

    $userId = (int)$pdo->lastInsertId();

    $token = JWTHelper::generateToken([
        'user_id'  => $userId,
        'email'    => $email,
        'username' => $username,
    ]);

    http_response_code(201);
    echo json_encode([
        'status' => 201,
        'msg'    => 'Compte créé avec succès',
        'token'  => $token,
        'user'   => [
            'id'                     => $userId,
            'username'               => $username,
            'email'                  => $email,
            'full_name'              => $full_name ?: null,
            'avatar_url'             => null,
            'is_premium'             => 0,
            'subscription_plan'      => null,
            'subscription_expires_at'=> null,
        ],
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status'=>500,'msg'=>'Erreur serveur']);
}