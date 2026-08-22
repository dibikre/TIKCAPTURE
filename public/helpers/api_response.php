<?php
require_once __DIR__ . '/../config/jwt_config.php';

class APIResponse {
    
    /**
     * Réponse succès
     */
    public static function success($data, $message = 'Success', $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    /**
     * Réponse erreur
     */
    public static function error($message, $code = 400, $errors = null) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        $response = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => date('c')
        ];
        if ($errors) {
            $response['errors'] = $errors;
        }
        echo json_encode($response);
        exit;
    }
    
    /**
     * Réponse authentification
     */
    public static function auth($token, $user, $message = 'Authentication successful') {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => JWT_EXPIRATION_HOURS * 3600,
                'user' => $user
            ],
            'timestamp' => date('c')
        ]);
        exit;
    }
}
?>