<?php
require_once __DIR__ . '/../config/jwt_config.php';

class JWTHelper {
    
    /**
     * Génère un token JWT
     */
    public static function generateToken($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $time = time();
        
        $tokenPayload = array_merge($payload, [
            'iss' => JWT_ISSUER,
            'aud' => JWT_AUDIENCE,
            'iat' => $time,
            'exp' => $time + (JWT_EXPIRATION_HOURS * 3600)
        ]);
        
        $payloadJson = json_encode($tokenPayload);
        
        // Encoder en Base64URL
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payloadJson));
        
        // Créer la signature
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", JWT_SECRET_KEY, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return "$base64Header.$base64Payload.$base64Signature";
    }
    
    /**
     * Vérifie et décode un token JWT
     */
    public static function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        // Recalculer la signature
        $signature = hash_hmac('sha256', "$base64Header.$base64Payload", JWT_SECRET_KEY, true);
        $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if (!hash_equals($base64Signature, $expectedSignature)) {
            return false;
        }
        
        // Décoder le payload
        $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload));
        $payload = json_decode($payloadJson, true);
        
        // Vérifier expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Extrait le token du header Authorization
     */
    public static function getBearerToken() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
?>