<?php
/**
 * Proxy API avec auto-renouvellement de clé ROBUSTE
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ============================================================
// CONFIGURATION
// ============================================================
$MASTER_SECRET = 'MonSecret2026Ultra$ecuris#789XYZ';
$API_URL = 'https://tikcapture.live/tiktok_live.php';
$KEY_FILE = __DIR__ . '/donnees/api_key.json';  // ← Changé vers donnees/

// ============================================================
// FONCTION: Générer une nouvelle clé API
// ============================================================
function generateNewKey($masterSecret, $apiUrl, $keyFile) {
    error_log("[KEY-GEN] Début génération nouvelle clé...");
    
    $data = [
        'action' => 'generate_key',
        'master_secret' => $masterSecret,
        'name' => 'Auto-generated key - ' . date('Y-m-d H:i:s'),
        'expiration_days' => 365
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("[KEY-GEN] HTTP Code: $httpCode");
    error_log("[KEY-GEN] Response: " . substr($response, 0, 200));
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['key'])) {
            $keyData = [
                'key' => $result['key'],
                'created_at' => time(),
                'expires_at' => time() + (365 * 24 * 60 * 60),
                'generation_count' => file_exists($keyFile) ? 
                    (json_decode(file_get_contents($keyFile), true)['generation_count'] ?? 0) + 1 : 1
            ];
            
            file_put_contents($keyFile, json_encode($keyData, JSON_PRETTY_PRINT));
            error_log("[KEY-GEN] ✓ Clé générée et sauvegardée dans: $keyFile");
            error_log("[KEY-GEN] ✓ Clé: " . substr($result['key'], 0, 20) . "...");
            
            // IMPORTANT: Attendre 2 secondes pour que l'API enregistre la clé
            sleep(2);
            
            return $result['key'];
        } else {
            error_log("[KEY-GEN] ✗ Pas de clé dans la réponse: " . print_r($result, true));
        }
    } else {
        error_log("[KEY-GEN] ✗ Échec HTTP $httpCode: $response");
    }
    
    return null;
}

// ============================================================
// FONCTION: Récupérer la clé API valide
// ============================================================
function getValidApiKey($masterSecret, $apiUrl, $keyFile) {
    // Vérifier si le fichier de clé existe
    if (file_exists($keyFile)) {
        $keyData = json_decode(file_get_contents($keyFile), true);
        
        if ($keyData && isset($keyData['key'])) {
            // Vérifier expiration (marge de 7 jours)
            $expiresIn = $keyData['expires_at'] - time();
            $daysLeft = round($expiresIn / 86400);
            
            if ($expiresIn > (7 * 24 * 60 * 60)) {
                error_log("[KEY-CHECK] ✓ Clé valide (expire dans $daysLeft jours)");
                return $keyData['key'];
            }
            
            error_log("[KEY-CHECK] ⚠ Clé expire bientôt ($daysLeft jours), régénération...");
        }
    } else {
        error_log("[KEY-CHECK] ⚠ Aucun fichier de clé trouvé, génération...");
    }
    
    return generateNewKey($masterSecret, $apiUrl, $keyFile);
}

// ============================================================
// FONCTION: Tester une clé API
// ============================================================
function testApiKey($apiKey, $apiUrl) {
    $testData = [
        'action' => 'test', // Action de test (à adapter selon votre API)
        'api_key' => $apiKey
    ];
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Codes 401/403 = clé invalide
    return !in_array($httpCode, [401, 403]);
}

// ============================================================
// TRAITEMENT DE LA REQUÊTE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON invalide']);
    exit;
}

// Obtenir une clé API valide
$API_KEY = getValidApiKey($MASTER_SECRET, $API_URL, $KEY_FILE);

if (!$API_KEY) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossible de générer une clé API valide']);
    exit;
}

// Ajouter la clé API
$input['api_key'] = $API_KEY;

// Faire la requête vers l'API réelle
$ch = curl_init($API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-API-Key: ' . $API_KEY
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// ============================================================
// GESTION DES ERREURS D'AUTHENTIFICATION
// ============================================================
if ($httpCode === 401 || $httpCode === 403) {
    error_log("[AUTH-ERROR] Clé rejetée (HTTP $httpCode), tentative de régénération...");
    
    // Supprimer l'ancienne clé
    if (file_exists($KEY_FILE)) {
        unlink($KEY_FILE);
    }
    
    // Générer nouvelle clé
    $API_KEY = generateNewKey($MASTER_SECRET, $API_URL, $KEY_FILE);
    
    if ($API_KEY) {
        error_log("[RETRY] Nouvelle tentative avec clé fraîche...");
        
        // RETRY avec nouvelle clé
        $input['api_key'] = $API_KEY;
        
        $ch = curl_init($API_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . $API_KEY
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("[RETRY] Résultat: HTTP $httpCode");
    } else {
        error_log("[RETRY] ✗ Impossible de générer nouvelle clé");
    }
}

if ($error) {
    error_log("[CURL-ERROR] $error");
}

// Retourner la réponse
http_response_code($httpCode);
echo $response;
?>