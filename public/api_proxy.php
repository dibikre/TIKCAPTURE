<?php
/**
 * Proxy API avec auto-authentification et délégation directe
 * Évite tout deadlock de boucle cURL sur les serveurs PHP mono-thread
 */

// Désactiver l'affichage direct des erreurs PHP pour ne pas corrompre le JSON
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Gestionnaire d'arrêt d'urgence si erreur fatale
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'error' => 'Erreur critique interne du serveur PHP: ' . $error['message']
        ], JSON_UNESCAPED_UNICODE);
    }
});

try {
    // 1. Définition des emplacements de fichiers de sécurité
    $SECURITY_KEYS_FILE = __DIR__ . '/donnees/.api_keys.json';
    $KEY_FILE = __DIR__ . '/donnees/api_key.json';

    // 2. Fonction de récupération ou création de clé d'API
    if (!function_exists('obtenirOuCreerCleApi')) {
        function obtenirOuCreerCleApi($securityFile, $keyFile) {
            if (file_exists($keyFile)) {
                $k = json_decode(@file_get_contents($keyFile), true);
                if ($k && isset($k['key']) && ($k['expires_at'] ?? 0) > (time() + 86400)) {
                    return $k['key'];
                }
            }
            
            $dir = dirname($securityFile);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            
            $nouvelleCle = 'tk_' . bin2hex(random_bytes(31));
            $donneesCle = [
                'key' => $nouvelleCle,
                'name' => 'Clé proxy auto ' . date('Y-m-d H:i:s'),
                'created_at' => time(),
                'expires_at' => time() + (365 * 86400),
                'requests_count' => 0,
                'last_used' => null,
                'active' => true
            ];
            
            $clesExistantes = file_exists($securityFile) ? json_decode(@file_get_contents($securityFile), true) : [];
            if (!is_array($clesExistantes)) {
                $clesExistantes = [];
            }
            $clesExistantes[$nouvelleCle] = $donneesCle;
            @file_put_contents($securityFile, json_encode($clesExistantes, JSON_PRETTY_PRINT));
            @file_put_contents($keyFile, json_encode(['key' => $nouvelleCle, 'expires_at' => $donneesCle['expires_at']], JSON_PRETTY_PRINT));
            return $nouvelleCle;
        }
    }

    // 3. Obtenir et injecter la clé dans les variables de requête
    $cleActive = obtenirOuCreerCleApi($SECURITY_KEYS_FILE, $KEY_FILE);
    $_SERVER['HTTP_X_API_KEY'] = $cleActive;

    // 4. Exécution directe en processus de tiktok_live.php
    if (file_exists(__DIR__ . '/tiktok_live.php')) {
        require __DIR__ . '/tiktok_live.php';
        exit;
    }

    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'tiktok_live.php introuvable sur le serveur']);
} catch (Throwable $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'error' => 'Erreur proxy: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
