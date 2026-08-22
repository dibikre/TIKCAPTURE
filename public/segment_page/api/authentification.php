<?php
/**
 * TikCapture — Authentification API Entry Point
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Global dependencies
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/email_config.php';
require_once __DIR__ . '/../../helpers/jwt_helper.php';
require_once __DIR__ . '/../../helpers/api_response.php';
require_once __DIR__ . '/../../helpers/validator.php';
require_once __DIR__ . '/../../helpers/email_helper.php';
require_once __DIR__ . '/../../helpers/invitation_helper.php';

// Auth specific modular files
require_once __DIR__ . '/auth_helpers.php';
require_once __DIR__ . '/auth-handlers.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

$input = [];
if ($method === 'POST') {
    $json = file_get_contents('php://input');
    $input = json_decode($json, true) ?: $_POST;
}

// ============================================
// SWITCH PRINCIPAL - TOUTES LES ACTIONS ICI
// ============================================
switch ($action) {
    // ÉTAPE 1: Inscription initiale (envoi du code)
    case 'register-init':
        if ($method !== 'POST') APIResponse::error('Méthode non autorisée', 405);
        handleRegisterInit($pdo, $input);
        break;
    
    // ÉTAPE 2: Vérification du code et finalisation
    case 'register-verify':
        if ($method !== 'POST') APIResponse::error('Méthode non autorisée', 405);
        handleRegisterVerify($pdo, $input);
        break;
    
    // Renvoyer le code (si non reçu)
    case 'resend-code':
        if ($method !== 'POST') APIResponse::error('Méthode non autorisée', 405);
        handleResendCode($pdo, $input);
        break;
    
    case 'login':
        if ($method !== 'POST') APIResponse::error('Méthode non autorisée', 405);
        handleLogin($pdo, $input);
        break;
        
    case 'logout':
        handleLogout($pdo);
        break;
        
    case 'verify':
        handleVerifyToken($pdo);
        break;
        
    case 'refresh':
        handleRefreshToken($pdo);
        break;
        
    case 'profile':
        if ($method === 'GET') {
            handleGetProfile($pdo);
        } elseif ($method === 'PUT' || $method === 'POST') {
            handleUpdateProfile($pdo, $input);
        } else {
            APIResponse::error('Méthode non autorisée', 405);
        }
        break;
        
    case 'change-password':
        if ($method !== 'POST') APIResponse::error('Méthode non autorisée', 405);
        handleChangePassword($pdo, $input);
        break;
    
    case 'forgot-password':
        if ($method !== 'POST') APIResponse::error('Méthode non autorisée', 405);
        handleForgotPassword($pdo, $input);
        break;

    case 'reset-password':
        if ($method !== 'POST') APIResponse::error('Méthode non autorisée', 405);
        handleResetPassword($pdo, $input);
        break;
        
    default:
        APIResponse::error('Action non reconnue.', 400);
}
?>
