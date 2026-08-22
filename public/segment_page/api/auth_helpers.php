<?php
/**
 * Helper functions for Authentication
 */

/**
 * Logger les actions d'authentification
 */
function logAuth($pdo, $userId, $action, $status, $message = '', $email = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO auth_logs (user_id, email, action, ip_address, user_agent, status, message) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $email, $action, $ip, $userAgent, $status, $message]);
    } catch (Exception $e) {
        // Silencieux - ne pas bloquer l'auth si le log échoue
        error_log("Erreur log auth: " . $e->getMessage());
    }
}

/**
 * Créer une session utilisateur
 */
function createUserSession($pdo, $userId) {
    $token = bin2hex(random_bytes(32));
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, device_info, ip_address, token) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $userAgent, $ip, $token]);
    
    return $token;
}
?>
