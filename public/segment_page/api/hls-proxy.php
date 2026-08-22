<?php
/**
 * HLS Proxy — TikCapture
 * Chemin : /segment_page/api/hls-proxy.php
 *
 * Relaie les requêtes vers des flux HLS externes (Bigo, DLive, etc.)
 * pour contourner les restrictions CORS côté navigateur.
 *
 * Usage :
 *   GET /segment_page/api/hls-proxy.php?url=https://...
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Range');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Validation de l'URL ──────────────────────────────────────────────────────

$url = $_GET['url'] ?? '';

if (empty($url)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Paramètre "url" requis']);
    exit;
}

$url = urldecode($url);

// Sécurité anti-SSRF : bloquer les IPs privées et localhost
// On autorise tous les domaines publics (Bigo change de CDN régulièrement)
$parsedUrl = parse_url($url);
$host      = strtolower($parsedUrl['host'] ?? '');
$scheme    = strtolower($parsedUrl['scheme'] ?? '');

// Protocole : uniquement HTTP/HTTPS
if (!in_array($scheme, ['http', 'https'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Protocole non autorisé']);
    exit;
}

// Bloquer localhost et domaines internes
$blockedPatterns = [
    'localhost', '127.', '0.0.0.0', '::1',
    '192.168.', '10.', '172.16.', '172.17.', '172.18.', '172.19.',
    '172.20.', '172.21.', '172.22.', '172.23.', '172.24.', '172.25.',
    '172.26.', '172.27.', '172.28.', '172.29.', '172.30.', '172.31.',
    'tikcapture.live', // Bloquer les boucles vers soi-même
];

foreach ($blockedPatterns as $pattern) {
    if (str_contains($host, $pattern)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Hôte non autorisé']);
        exit;
    }
}

// Bloquer les IPs privées résolues (protection SSRF avancée)
if (filter_var($host, FILTER_VALIDATE_IP)) {
    $ipLong = ip2long($host);
    $privateRanges = [
        [ip2long('10.0.0.0'),     ip2long('10.255.255.255')],
        [ip2long('172.16.0.0'),   ip2long('172.31.255.255')],
        [ip2long('192.168.0.0'),  ip2long('192.168.255.255')],
        [ip2long('127.0.0.0'),    ip2long('127.255.255.255')],
    ];
    foreach ($privateRanges as [$start, $end]) {
        if ($ipLong >= $start && $ipLong <= $end) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'IP privée non autorisée']);
            exit;
        }
    }
}

// ─── Requête vers la source ───────────────────────────────────────────────────

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_HTTPHEADER     => [
        'Accept: */*',
        'Accept-Encoding: identity',
        'Origin: https://tikcapture.live',
    ],
    CURLOPT_HEADER         => true, // Pour récupérer le Content-Type source
]);

// Transmettre le header Range si présent (pour les segments partiels)
if (!empty($_SERVER['HTTP_RANGE'])) {
    curl_setopt($ch, CURLOPT_RANGE, str_replace('bytes=', '', $_SERVER['HTTP_RANGE']));
}

$response   = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error      = curl_error($ch);
curl_close($ch);

$body = substr($response, $headerSize);

if ($error || $httpCode < 200 || $httpCode >= 400) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => "Erreur proxy: HTTP $httpCode — $error"]);
    exit;
}

// ─── Détection du type de contenu ────────────────────────────────────────────

$isM3U8 = str_contains($contentType, 'mpegurl')
       || str_contains($contentType, 'x-mpegURL')
       || str_ends_with(strtok($url, '?'), '.m3u8');

$isTs = str_contains($contentType, 'mp2t')
     || str_contains($contentType, 'mpeg')
     || str_ends_with(strtok($url, '?'), '.ts');

// ─── Si c'est un manifeste M3U8 : réécrire les URLs des segments ──────────────
// Les segments relatifs sont réécrits pour passer par ce proxy.

if ($isM3U8) {
    header('Content-Type: application/vnd.apple.mpegurl');
    header('Cache-Control: no-cache');

    // Base URL du manifeste (sans le nom de fichier)
    $baseUrl = substr($url, 0, strrpos($url, '/') + 1);

    $lines  = explode("\n", $body);
    $output = [];

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if (empty($trimmed) || str_starts_with($trimmed, '#')) {
            // Lignes de métadonnées — les conserver telles quelles
            // Sauf URI= dans les tags comme #EXT-X-KEY ou #EXT-X-MAP
            $line = preg_replace_callback(
                '/URI="([^"]+)"/',
                function ($m) use ($baseUrl) {
                    $uri = $m[1];
                    if (!str_starts_with($uri, 'http')) {
                        $uri = $baseUrl . $uri;
                    }
                    return 'URI="' . 'hls-proxy.php?url=' . urlencode($uri) . '"';
                },
                $line
            );
            $output[] = $line;
            continue;
        }

        // Ligne de segment ou de sous-manifeste
        $absoluteUrl = str_starts_with($trimmed, 'http') ? $trimmed : $baseUrl . $trimmed;
        $output[] = 'hls-proxy.php?url=' . urlencode($absoluteUrl);
    }

    echo implode("\n", $output);
    exit;
}

// ─── Segment vidéo .ts ou autre binaire ──────────────────────────────────────

if ($isTs) {
    header('Content-Type: video/mp2t');
} else {
    header('Content-Type: ' . ($contentType ?: 'application/octet-stream'));
}

header('Content-Length: ' . strlen($body));
header('Cache-Control: no-cache');

echo $body;
exit;