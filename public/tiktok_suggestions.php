<?php
/**
 * Proxy pour récupérer les suggestions de lives TikTok
 * Utilise api_proxy.php pour cacher la clé API
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ============================================================
// CONFIGURATION
// ============================================================
$API_KEY = 'tk_d139de23e6a29ef1c7ed10ab5acb7da88fd74057ef0adec56eb61da4cac742';
$API_URL = 'https://tikcapture.live/temp/tiktok_live.php';

// URL de l'API TikTok pour les suggestions
$suggestionsApiUrl = "https://webcast.tiktok.com/webcast/feed/?aid=1988&app_language=fr&app_name=tiktok_web&browser_language=fr&browser_name=Mozilla&browser_online=true&browser_platform=Win32&browser_version=5.0%20%28Windows%29&channel=tiktok_web&channel_id=87&cookie_enabled=true&cpu_number=8&data_collection_enabled=true&device_id=7595864447907137031&device_platform=web_pc&device_type=web_h264&focus_state=true&from_page=&history_len=8&is_fullscreen=false&is_non_personalized=0&is_page_visible=true&os=windows&priority_region=MA&referer=https%3A%2F%2Fwww.tiktok.com%2F&region=MA&req_from=pc_web_inner_recommend_room_loadmore&root_referer=https%3A%2F%2Fwww.tiktok.com%2F&screen_height=1080&screen_width=1920&tz_name=Africa%2FCasablanca&user_is_login=true&verifyFp=verify_mkgrt5xh_WG8CVMqy_0N5l_4fwB_AVvK_UOISXbIzbfs6&webcast_language=fr";

// ============================================================
// RÉCUPÉRATION DES LIVES EN COURS
// ============================================================
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $suggestionsApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate, br');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language: fr,fr-FR;q=0.9,en-US;q=0.8,en;q=0.7',
    'Accept-Encoding: gzip, deflate, br, zstd',
    'Cache-Control: no-cache',
    'Connection: keep-alive',
    'Pragma: no-cache',
    'Sec-Fetch-Dest: document',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Site: none',
    'Sec-Fetch-User: ?1',
    'Upgrade-Insecure-Requests: 1'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch) || $httpCode !== 200) {
    curl_close($ch);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des suggestions'
    ]);
    exit;
}
curl_close($ch);

$jsonData = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($jsonData['data'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Données invalides'
    ]);
    exit;
}

// ============================================================
// EXTRACTION DES SUGGESTIONS ET ENRICHISSEMENT
// ============================================================
$suggestions = [];
$usernamesToEnrich = [];

// D'abord, extraire les usernames
foreach ($jsonData['data'] as $item) {
    if (!isset($item['data'])) continue;
    
    $streamData = $item['data'];
    $username = $streamData['owner']['display_id'] ?? null;
    
    if ($username) {
        $suggestions[] = [
            'username' => $username,
            'nickname' => $streamData['owner']['nickname'] ?? null,
            'avatar' => $streamData['owner']['avatar_large']['url_list'][0] ?? null,
            'cover' => $streamData['cover']['url_list'][0] ?? null,
            'viewers' => $streamData['user_count'] ?? 0,
            'likes' => $streamData['like_count'] ?? 0,
            'title' => $streamData['title'] ?? '',
            'enriched' => false
        ];
        
        // Limiter à 10 suggestions maximum
        if (count($suggestions) >= 10) {
            break;
        }
    }
}

// ============================================================
// ENRICHISSEMENT AVEC L'API SÉCURISÉE (AVEC SCREENSHOTS)
// ============================================================
// Enrichir les 3 premiers lives avec des screenshots via l'API sécurisée
$enrichLimit = min(3, count($suggestions));

for ($i = 0; $i < $enrichLimit; $i++) {
    $username = $suggestions[$i]['username'];
    
    // Appel à l'API sécurisée via le proxy interne
    $enrichCh = curl_init($API_URL);
    curl_setopt($enrichCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($enrichCh, CURLOPT_POST, true);
    curl_setopt($enrichCh, CURLOPT_POSTFIELDS, json_encode([
        'action' => 'search',
        'username' => $username
    ]));
    curl_setopt($enrichCh, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $API_KEY
    ]);
    curl_setopt($enrichCh, CURLOPT_TIMEOUT, 15); // Timeout de 15 secondes
    
    $enrichResponse = curl_exec($enrichCh);
    $enrichHttpCode = curl_getinfo($enrichCh, CURLINFO_HTTP_CODE);
    curl_close($enrichCh);
    
    if ($enrichHttpCode === 200 && $enrichResponse) {
        $enrichData = json_decode($enrichResponse, true);
        
        if (isset($enrichData['success']) && $enrichData['success']) {
            // Si on a un screenshot du stream, l'utiliser
            if (!empty($enrichData['live']['streamThumbnail'])) {
                $suggestions[$i]['cover'] = 'donnees/' . $enrichData['live']['streamThumbnail'];
                $suggestions[$i]['enriched'] = true;
            }
            
            // Mettre à jour les viewers si disponibles
            if (isset($enrichData['live']['viewers'])) {
                $suggestions[$i]['viewers'] = $enrichData['live']['viewers'];
            }
        }
    }
    
    // Pause de 500ms entre chaque appel pour éviter la surcharge
    usleep(500000);
}

// ============================================================
// RÉPONSE FINALE
// ============================================================
echo json_encode([
    'success' => true,
    'count' => count($suggestions),
    'suggestions' => $suggestions,
    'enriched_count' => array_reduce($suggestions, function($count, $item) {
        return $count + ($item['enriched'] ? 1 : 0);
    }, 0)
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>