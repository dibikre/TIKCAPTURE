<?php
/**
 * TikTok Live Recorder - API Backend SÉCURISÉE
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ============================================================
// CONFIGURATION SÉCURITÉ
// ============================================================
$SECURITY_CONFIG = [
    'apiKeysFile' => __DIR__ . '/donnees/.api_keys.json',
    'keyExpirationDays' => 30,
    'rateLimitPerHour' => 100,
    'securityLogFile' => __DIR__ . '/donnees/security.log',
    'ipWhitelist' => [],
    'enableRateLimit' => true
];

// ============================================================
// CONFIGURATION PRINCIPALE
// ============================================================
$CONFIG = [
    'recordingMode' => 'client',
    'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    'timeout' => 30,
    'outputDir' => __DIR__ . '/donnees',
    'logFile' => __DIR__ . '/donnees/api.log',
    'debug' => true,
];

// Créer les dossiers nécessaires
if (!file_exists($CONFIG['outputDir'])) {
    mkdir($CONFIG['outputDir'], 0777, true);
}

if (!file_exists($SECURITY_CONFIG['apiKeysFile'])) {
    logMessage("Création du fichier de clés: " . $SECURITY_CONFIG['apiKeysFile'], 'INFO');
    file_put_contents($SECURITY_CONFIG['apiKeysFile'], json_encode([], JSON_PRETTY_PRINT));
    chmod($SECURITY_CONFIG['apiKeysFile'], 0600);
    logMessage("✓ Fichier de clés créé avec succès", 'INFO');
} else {
    logMessage("✓ Fichier de clés existant trouvé", 'DEBUG');
}

/**
 * Logger les événements de sécurité
 */
function securityLog($message, $level = 'INFO', $data = []) {
    global $SECURITY_CONFIG;
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $logEntry = [
        'timestamp' => $timestamp,
        'level' => $level,
        'ip' => $ip,
        'user_agent' => $userAgent,
        'message' => $message,
        'data' => $data
    ];
    
    $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($SECURITY_CONFIG['securityLogFile'], $logLine, FILE_APPEND);
}

/**
 * Logger les événements normaux
 */
function logMessage($message, $level = 'INFO') {
    global $CONFIG;
    if (!$CONFIG['debug']) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
    file_put_contents($CONFIG['logFile'], $logEntry, FILE_APPEND);
}

/**
 * Réponse JSON standardisée
 */
function jsonResponse($data, $success = true, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Réponse d'erreur
 */
function jsonError($message, $statusCode = 400, $details = null) {
    global $CONFIG;
    
    $response = [
        'error' => $message,
        'success' => false
    ];
    
    if ($details && $CONFIG['debug']) {
        $response['details'] = $details;
    }
    
    logMessage("Error: {$message}", 'ERROR');
    securityLog($message, 'ERROR', $details ?? []);
    
    jsonResponse($response, false, $statusCode);
}

/**
 * Générer une nouvelle clé API
 */
function generateApiKey($name = '', $expirationDays = null) {
    global $SECURITY_CONFIG;
    
    if ($expirationDays === null) {
        $expirationDays = $SECURITY_CONFIG['keyExpirationDays'];
    }
    
    $key = 'tk_' . bin2hex(random_bytes(31));
    
    $keyData = [
        'key' => $key,
        'name' => $name ?: 'Clé générée le ' . date('Y-m-d H:i:s'),
        'created_at' => time(),
        'expires_at' => time() + ($expirationDays * 86400),
        'requests_count' => 0,
        'last_used' => null,
        'active' => true
    ];
    
    $keys = json_decode(file_get_contents($SECURITY_CONFIG['apiKeysFile']), true) ?: [];
    $keys[$key] = $keyData;
    
    $saveResult = file_put_contents($SECURITY_CONFIG['apiKeysFile'], json_encode($keys, JSON_PRETTY_PRINT));
    
    if ($saveResult === false) {
        logMessage("✗ ÉCHEC sauvegarde clé dans: " . $SECURITY_CONFIG['apiKeysFile'], 'CRITICAL');
        throw new Exception("Impossible de sauvegarder la clé API");
    } else {
        logMessage("✓ Clé sauvegardée avec succès (" . count($keys) . " clés au total)", 'INFO');
    }
    
    securityLog("Nouvelle clé API générée", 'INFO', ['name' => $name, 'key' => substr($key, 0, 10) . '...', 'total_keys' => count($keys)]);
    
    return $keyData;
}

/**
 * Vérifier la validité d'une clé API
 */
function validateApiKey($apiKey) {
    global $SECURITY_CONFIG;
    
    if (empty($apiKey)) {
        securityLog("Tentative d'accès sans clé API", 'WARNING');
        return ['valid' => false, 'error' => 'Clé API manquante'];
    }
    
    $keys = json_decode(file_get_contents($SECURITY_CONFIG['apiKeysFile']), true) ?: [];
    
    if (!isset($keys[$apiKey])) {
        securityLog("Tentative d'accès avec clé invalide", 'WARNING', ['key' => substr($apiKey, 0, 10) . '...']);
        return ['valid' => false, 'error' => 'Clé API invalide'];
    }
    
    $keyData = $keys[$apiKey];
    
    if (!$keyData['active']) {
        securityLog("Tentative d'accès avec clé désactivée", 'WARNING', ['key' => substr($apiKey, 0, 10) . '...']);
        return ['valid' => false, 'error' => 'Clé API désactivée'];
    }
    
    if ($keyData['expires_at'] < time()) {
        securityLog("Tentative d'accès avec clé expirée", 'WARNING', ['key' => substr($apiKey, 0, 10) . '...']);
        return ['valid' => false, 'error' => 'Clé API expirée'];
    }
    
    if ($SECURITY_CONFIG['enableRateLimit']) {
        $hourAgo = time() - 3600;
        if (isset($keyData['last_request_time']) && $keyData['last_request_time'] > $hourAgo) {
            if (isset($keyData['requests_this_hour']) && $keyData['requests_this_hour'] >= $SECURITY_CONFIG['rateLimitPerHour']) {
                securityLog("Rate limit dépassé", 'WARNING', ['key' => substr($apiKey, 0, 10) . '...']);
                return ['valid' => false, 'error' => 'Limite de requêtes dépassée (max ' . $SECURITY_CONFIG['rateLimitPerHour'] . '/heure)'];
            }
        }
    }
    
    return ['valid' => true, 'keyData' => $keyData];
}

/**
 * Mettre à jour les statistiques d'utilisation d'une clé
 */
function updateKeyUsage($apiKey) {
    global $SECURITY_CONFIG;
    
    $keys = json_decode(file_get_contents($SECURITY_CONFIG['apiKeysFile']), true) ?: [];
    
    if (!isset($keys[$apiKey])) {
        return;
    }
    
    $currentTime = time();
    $hourAgo = $currentTime - 3600;
    
    if (!isset($keys[$apiKey]['last_request_time']) || $keys[$apiKey]['last_request_time'] < $hourAgo) {
        $keys[$apiKey]['requests_this_hour'] = 0;
    }
    
    $keys[$apiKey]['requests_count']++;
    $keys[$apiKey]['requests_this_hour'] = ($keys[$apiKey]['requests_this_hour'] ?? 0) + 1;
    $keys[$apiKey]['last_used'] = $currentTime;
    $keys[$apiKey]['last_request_time'] = $currentTime;
    
    file_put_contents($SECURITY_CONFIG['apiKeysFile'], json_encode($keys, JSON_PRETTY_PRINT));
}

/**
 * Vérifier l'IP whitelist
 */
function checkIpWhitelist() {
    global $SECURITY_CONFIG;
    
    if (empty($SECURITY_CONFIG['ipWhitelist'])) {
        return true;
    }
    
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (!in_array($clientIp, $SECURITY_CONFIG['ipWhitelist'])) {
        securityLog("Accès refusé - IP non autorisée", 'WARNING', ['ip' => $clientIp]);
        return false;
    }
    
    return true;
}

/**
 * Middleware d'authentification
 */
function authenticateRequest() {
    if (!checkIpWhitelist()) {
        jsonError("Accès refusé - IP non autorisée", 403);
    }
    
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
    
    if (!$apiKey) {
        $input = json_decode(file_get_contents('php://input'), true);
        $apiKey = $input['api_key'] ?? null;
    }
    
    $validation = validateApiKey($apiKey);
    
    if (!$validation['valid']) {
        jsonError($validation['error'], 401);
    }
    
    updateKeyUsage($apiKey);
    
    securityLog("Requête authentifiée avec succès", 'INFO', [
        'key' => substr($apiKey, 0, 10) . '...',
        'name' => $validation['keyData']['name']
    ]);
    
    return $validation['keyData'];
}

/**
 * Résoudre une URL raccourcie TikTok
 */
function resolveShortUrl($url) {
    global $CONFIG;
    
    logMessage("Résolution de l'URL raccourcie: {$url}");
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_NOBODY => true,
        CURLOPT_HEADER => true,
        CURLOPT_USERAGENT => $CONFIG['userAgent'],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 400 && !empty($finalUrl)) {
        logMessage("URL résolue: {$finalUrl}");
        return $finalUrl;
    }
    
    logMessage("Échec de résolution (HTTP {$httpCode})", 'WARNING');
    return $url;
}

/**
 * Extraire le nom d'utilisateur
 */
function extractUsername($input) {
    global $CONFIG;
    
    $input = trim($input);
    
    // Détecter et résoudre les URL raccourcies vt.tiktok.com
    if (preg_match('/vt\.tiktok\.com/i', $input)) {
        logMessage("URL raccourcie détectée: {$input}");
        $input = resolveShortUrl($input);
    }
    
    // Extraire depuis URL complète
    if (preg_match('/tiktok\.com\/@([^\/?]+)/', $input, $matches)) {
        return $matches[1];
    }
    
    // Enlever @ au début
    if (strpos($input, '@') === 0) {
        return substr($input, 1);
    }
    
    return $input;
}

/**
 * Construire l'URL de la page live TikTok
 */
function buildLivePageUrl($username) {
    return "https://www.tiktok.com/@{$username}/live";
}

/**
 * Récupérer les données du live TikTok via le code source de la page
 */
function fetchTikTokLiveData($username) {
    global $CONFIG;

    logMessage("Fetching data via page source for username: {$username}");
    $url = buildLivePageUrl($username);
    logMessage("URL page live: {$url}");
    sleep(2);
    
    // Nettoyer les préfixes #HttpOnly_ incompatibles avec cURL
    $cookiePath = __DIR__ . '/donnees/tiktok_cookies.txt';
    if (file_exists($cookiePath)) {
        $content = file_get_contents($cookiePath);
        $content = preg_replace('/^#HttpOnly_/m', '', $content);
        file_put_contents($cookiePath, $content);
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_USERAGENT => $CONFIG['userAgent'],
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: fr-FR,fr;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Sec-Fetch-Dest: document',
            'Sec-Fetch-Mode: navigate',
            'Sec-Fetch-Site: none',
            'Sec-Fetch-User: ?1',
            'Upgrade-Insecure-Requests: 1',
            'Cache-Control: max-age=0'
        ],
        CURLOPT_ENCODING => 'gzip, deflate',
        CURLOPT_CONNECTTIMEOUT => $CONFIG['timeout'],
        CURLOPT_TIMEOUT => $CONFIG['timeout'],
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR  => __DIR__ . '/donnees/tiktok_cookies.txt',
        CURLOPT_COOKIEFILE => __DIR__ . '/donnees/tiktok_cookies.txt',
    ]);

    $response   = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers    = substr($response, 0, $headerSize);
    $html       = substr($response, $headerSize);
    $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error      = curl_error($ch);
    curl_close($ch);

    // Mettre à jour les cookies depuis les Set-Cookie reçus
    $cookiePath = __DIR__ . '/donnees/tiktok_cookies.txt';
    preg_match_all('/Set-Cookie:\s*([^=]+)=([^;\r\n]*)/i', $headers, $matches);
    if (!empty($matches[1])) {
        $existingCookies = [];
        if (file_exists($cookiePath)) {
            foreach (file($cookiePath) as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) continue;
                $parts = explode("\t", $line);
                if (count($parts) >= 7) {
                    $existingCookies[$parts[5]] = $parts;
                }
            }
        }

        foreach ($matches[1] as $i => $name) {
            $name  = trim($name);
            $value = trim($matches[2][$i]);
            if (isset($existingCookies[$name])) {
                $existingCookies[$name][6] = $value;
                logMessage("🔄 Cookie mis à jour: {$name}");
            }
        }

        $lines = ["# Netscape HTTP Cookie File", "# Updated by TikTok API", ""];
        foreach ($existingCookies as $parts) {
            $lines[] = implode("\t", $parts);
        }
        file_put_contents($cookiePath, implode("\n", $lines) . "\n");
        logMessage("✓ Cookies mis à jour (" . count($matches[1]) . " nouveaux)");
    }

    if ($html === false || empty($html)) {
        throw new Exception("Erreur de requête: $error");
    }
    if ($httpCode !== 200) {
        throw new Exception("Erreur HTTP: $httpCode");
    }

    logMessage("Page HTML récupérée (" . strlen($html) . " bytes)");

    // =====================================================
    // EXTRACTION DU JSON DEPUIS SIGI_STATE
    // =====================================================
    $sigiData = null;

    if (preg_match('/<script\s+id="SIGI_STATE"\s+type="application\/json">\s*(\{.+?\})\s*<\/script>/s', $html, $matches)) {
        logMessage("✓ SIGI_STATE trouvé (Méthode 1)");
        $sigiData = json_decode($matches[1], true);
    }

    if (!$sigiData) {
        if (preg_match('/<script[^>]*id=["\']SIGI_STATE["\'][^>]*>\s*(\{.+?\})\s*<\/script>/s', $html, $matches)) {
            logMessage("✓ SIGI_STATE trouvé (Méthode 2)");
            $sigiData = json_decode($matches[1], true);
        }
    }

    if (!$sigiData) {
        if (preg_match('/<script\s+id="__NEXT_DATA__"\s+type="application\/json">\s*(\{.+?\})\s*<\/script>/s', $html, $matches)) {
            logMessage("✓ __NEXT_DATA__ trouvé (Méthode 3)");
            $nextData = json_decode($matches[1], true);
            if ($nextData && isset($nextData['props']['pageProps'])) {
                $sigiData = $nextData['props']['pageProps'];
            }
        }
    }

    if (!$sigiData || json_last_error() !== JSON_ERROR_NONE) {
        logMessage("✗ Impossible d'extraire SIGI_STATE du code source", 'ERROR');
        logMessage("Début HTML (500 chars): " . substr($html, 0, 500), 'DEBUG');
        throw new Exception("Impossible d'extraire les données de la page TikTok. La page peut avoir changé de structure.");
    }

    logMessage("✓ JSON SIGI_STATE extrait avec succès");

    $jsonData = convertSigiStateToApiFormat($sigiData, $username);

    // Sauvegarde du JSON brut
    $jsonDir = $CONFIG['outputDir'];
    if (!is_dir($jsonDir)) {
        mkdir($jsonDir, 0755, true);
    }
    if (is_writable($jsonDir)) {
        $timestamp = time();
        $safeUsername = preg_replace('/[^a-zA-Z0-9_-]/', '_', $username);
        $jsonFile = "{$jsonDir}/live_{$safeUsername}_{$timestamp}.json";
        file_put_contents($jsonFile, json_encode($sigiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        logMessage("✓ JSON SIGI_STATE brut sauvegardé: {$jsonFile}");
    } else {
        logMessage("⚠ Impossible d'écrire dans le dossier JSON: {$jsonDir}", 'WARNING');
    }

    logMessage("Data fetched successfully for {$username}");
    return $jsonData;
}

/**
 * Convertir les données SIGI_STATE au format attendu par formatDataForFrontend()
 */
function convertSigiStateToApiFormat($sigiData, $username) {
    logMessage("Conversion SIGI_STATE → format API...");

    $liveRoomUserInfo = $sigiData['LiveRoom']['liveRoomUserInfo'] ?? [];
    $user = $liveRoomUserInfo['user'] ?? [];
    $stats = $liveRoomUserInfo['stats'] ?? [];
    $liveRoom = $liveRoomUserInfo['liveRoom'] ?? [];

    $liveStatus = $liveRoom['status'] ?? 0;
    $isLive = ($liveStatus === 2);
    
    logMessage("Statut live: " . ($isLive ? "EN DIRECT" : "HORS LIGNE") . " (status=$liveStatus)");

    $apiFormat = [
        'data' => [
            'user' => [
                'uniqueId'     => $user['uniqueId'] ?? '',
                'nickname'     => $user['nickname'] ?? '',
                'avatarLarger' => $user['avatarLarger'] ?? '',
                'avatarMedium' => $user['avatarMedium'] ?? '',
                'avatarThumb'  => $user['avatarThumb'] ?? '',
                'verified'     => $user['verified'] ?? false,
                'signature'    => $user['signature'] ?? '',
                'id'           => $user['id'] ?? '',
            ],
            'stats' => [
                'followerCount'  => $stats['followerCount'] ?? 0,
                'followingCount' => $stats['followingCount'] ?? 0,
            ],
            'liveRoom' => [
                'status'    => $liveStatus,
                'title'     => $liveRoom['title'] ?? '',
                'startTime' => $liveRoom['startTime'] ?? 0,
                'coverUrl'  => $liveRoom['coverUrl'] ?? '',
                'liveRoomStats' => [
                    'userCount'  => $liveRoom['liveRoomStats']['userCount'] ?? 0,
                    'enterCount' => $liveRoom['liveRoomStats']['enterCount'] ?? 0,
                ],
                'streamData' => [],
            ],
        ],
    ];

    if ($isLive && isset($liveRoom['streamData'])) {
        $streamData = $liveRoom['streamData'];

        if (isset($streamData['pull_data'])) {
            $apiFormat['data']['liveRoom']['streamData']['pull_data'] = $streamData['pull_data'];
            logMessage("✓ pull_data H264 extrait");
        }

        if (isset($liveRoom['hevcStreamData']['pull_data'])) {
            $apiFormat['data']['liveRoom']['hevcStreamData'] = [
                'pull_data' => $liveRoom['hevcStreamData']['pull_data']
            ];
            logMessage("✓ pull_data H265 (HEVC) extrait");
        }

        if (isset($streamData['streamId'])) {
            $apiFormat['data']['liveRoom']['streamData']['streamId'] = $streamData['streamId'];
        }
    }

    $streamCount = 0;
    if (isset($apiFormat['data']['liveRoom']['streamData']['pull_data']['stream_data'])) {
        $sd = $apiFormat['data']['liveRoom']['streamData']['pull_data']['stream_data'];
        if (is_string($sd)) {
            $sdDecoded = json_decode($sd, true);
            if (isset($sdDecoded['data'])) {
                $streamCount = count($sdDecoded['data']);
            }
        }
    }
    logMessage("✓ Conversion terminée: {$streamCount} qualités de stream trouvées");

    return $apiFormat;
}

/**
 * Extraire les URLs de stream avec détection du type
 */
function extractStreamUrls($data)
{
    if (!isset($data['data']['liveRoom']['streamData']['pull_data'])) {
        return [];
    }
    
    $pullData   = $data['data']['liveRoom']['streamData']['pull_data'];
    $streamData = $pullData['stream_data'] ?? null;
    
    if (is_string($streamData)) {
        $streamData = json_decode($streamData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
    }
    
    if (!isset($streamData['data'])) {
        return [];
    }
    
    $qualitiesMap = [];
    if (isset($pullData['options']['qualities'])) {
        foreach ($pullData['options']['qualities'] as $quality) {
            if (isset($quality['sdk_key'], $quality['name'])) {
                $qualitiesMap[$quality['sdk_key']] = $quality['name'];
            }
        }
    }
    
    $urls = [];
    foreach ($streamData['data'] as $qualityKey => $streams) {
        if ($qualityKey === 'ao') continue;
        if (!isset($streams['main'])) continue;
        
        $qualityName = $qualitiesMap[$qualityKey] ?? $qualityKey;
        $resolution = '';
        $bitrate    = '';
        
        if (!empty($streams['main']['sdk_params'])) {
            $sdkParams = json_decode($streams['main']['sdk_params'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $resolution = $sdkParams['resolution'] ?? '';
                $bitrate    = isset($sdkParams['vbitrate'])
                    ? round($sdkParams['vbitrate'] / 1000) . 'kbps'
                    : '';
            }
        }
        
        $hls  = $streams['main']['hls']  ?? '';
        $flv  = $streams['main']['flv']  ?? '';
        $cmaf = $streams['main']['cmaf'] ?? '';
        $dash = $streams['main']['dash'] ?? '';
        
        $type = '';
        $url  = '';
        
        if ($hls) {
            $type = 'HLS';
            $url  = $hls;
        } elseif ($flv) {
            $type = 'FLV';
            $url  = $flv;
        } elseif ($cmaf) {
            $type = 'CMAF';
            $url  = $cmaf;
        } elseif ($dash) {
            $type = 'DASH';
            $url  = $dash;
        } else {
            continue;
        }
        
        $urls[$qualityKey] = [
            'qualityName' => $qualityName,
            'resolution'  => $resolution,
            'bitrate'     => $bitrate,
            'type'        => $type,
            'url'         => $url,
            'hls'         => $hls,
            'flv'         => $flv,
            'cmaf'        => $cmaf,
            'dash'        => $dash
        ];
    }
    
    return $urls;
}

/**
 * Formater les données pour le frontend
 */
function formatDataForFrontend($data, $username) {
    if (!isset($data['data'])) {
        throw new Exception("Utilisateur non trouvé ou données invalides");
    }

    $user     = $data['data']['user'] ?? [];
    $stats    = $data['data']['stats'] ?? [];
    $liveRoom = $data['data']['liveRoom'] ?? [];
    $isLive   = ($liveRoom['status'] ?? 0) === 2;
    $streams  = extractStreamUrls($data);

    return [
        'success' => true,
        'user' => [
            'uniqueId' => $user['uniqueId'] ?? '',
            'nickname' => $user['nickname'] ?? '',
            'avatar'   => $user['avatarLarger'] ?? '',
            'verified' => $user['verified'] ?? false,
            'bio'      => $user['signature'] ?? ''
        ],
        'stats' => [
            'followers' => $stats['followerCount'] ?? 0,
            'following' => $stats['followingCount'] ?? 0
        ],
        'live' => [
            'status'    => $isLive ? 'EN DIRECT' : 'HORS LIGNE',
            'isLive'    => $isLive,
            'title'     => $liveRoom['title'] ?? '',
            'startTime' => isset($liveRoom['startTime']) ? date('d/m/Y H:i:s', $liveRoom['startTime']) : '',
            'viewers'   => $liveRoom['liveRoomStats']['userCount'] ?? 0,
            'thumbnail' => $liveRoom['coverUrl'] ?? ''
        ],
        'streams' => $streams
    ];
}

// ============================================================
// ROUTING
// ============================================================

try {
    $method = $_SERVER['REQUEST_METHOD'];
    logMessage("Request: {$method} " . ($_SERVER['REQUEST_URI'] ?? ''));

    if ($method !== 'POST') {
        jsonError("Méthode non autorisée. Utilisez POST uniquement.", 405);
    }

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonError("JSON invalide: " . json_last_error_msg(), 400);
    }
    
    $action = $input['action'] ?? '';
    
    if ($action === 'generate_key') {
        $masterSecret = $input['master_secret'] ?? '';
        $expectedSecret = 'MonSecret2026Ultra$ecuris#789XYZ';
        
        if ($masterSecret !== $expectedSecret) {
            securityLog("Tentative de génération de clé avec secret invalide", 'CRITICAL');
            jsonError("Non autorisé", 403);
        }
        
        $name = $input['name'] ?? '';
        $expirationDays = $input['expiration_days'] ?? null;
        
        $newKey = generateApiKey($name, $expirationDays);
        
        jsonResponse([
            'success'    => true,
            'message'    => 'Clé API générée avec succès',
            'key'        => $newKey['key'],
            'name'       => $newKey['name'],
            'created_at' => date('Y-m-d H:i:s', $newKey['created_at']),
            'expires_at' => date('Y-m-d H:i:s', $newKey['expires_at'])
        ]);
    }
    
    $keyData = authenticateRequest();
    
    switch ($action) {
        case 'search':
            $username = $input['username'] ?? '';
            if (empty($username)) {
                jsonError("Nom d'utilisateur requis");
            }

            $username = extractUsername($username);
            $rawData = fetchTikTokLiveData($username);
            $formattedData = formatDataForFrontend($rawData, $username);
            
            jsonResponse($formattedData);
            break;

        case 'status':
            $statusData = [
                'status'          => 'ok',
                'server'          => 'TikTok Live API (Secured)',
                'version'         => '2.0-secure',
                'recording_mode'  => $CONFIG['recordingMode'],
                'authenticated'   => true,
                'key_name'        => $keyData['name'],
                'requests_count'  => $keyData['requests_count'],
                'timestamp'       => date('c')
            ];

            jsonResponse($statusData);
            break;

        default:
            jsonError("Action non reconnue: {$action}", 400, [
                'available_actions' => ['search', 'status']
            ]);
    }

} catch (Exception $e) {
    jsonError($e->getMessage(), 500, [
        'trace' => $CONFIG['debug'] ? $e->getTraceAsString() : null
    ]);
}