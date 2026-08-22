<?php
/**
 * TikCapture — Proxy de téléchargement vidéo
 * URL : https://tikcapture.live/segment_page/api/download-proxy.php?url=<encoded_video_url>
 */

$allowed_origins = [
    'https://tikcapture.live',
    'https://www.tikcapture.live',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://localhost:4000',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$url = isset($_GET['url']) ? trim($_GET['url']) : '';

if (empty($url)) {
    http_response_code(400);
    die('URL manquante');
}

// Valider que c'est bien une URL TikTok CDN
$allowed_hosts = ['tiktok.com', 'tiktokv.com', 'tiktokcdn.com', 'tiktokcdn-us.com', 'tiktokv.eu'];
$parsed = parse_url($url);
$host = strtolower($parsed['host'] ?? '');

$is_allowed = false;
foreach ($allowed_hosts as $allowed) {
    if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
        $is_allowed = true;
        break;
    }
}

if (!$is_allowed) {
    http_response_code(403);
    die('Domaine non autorisé');
}

// Cookie partagé avec tiktok-video.php
$cookie_file = __DIR__ . '/cookie.txt';

// Streamer la vidéo — même config que downloadVideo() de l'ancien projet
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 10,
    // Même User-Agent que l'ancien projet qui fonctionnait
    CURLOPT_USERAGENT      => 'okhttp',
    // Range header identique à l'ancien projet
    CURLOPT_HTTPHEADER     => ['Range: bytes=0-'],
    CURLOPT_REFERER        => 'https://www.tiktok.com/',
    CURLOPT_COOKIEJAR      => $cookie_file,
    CURLOPT_COOKIEFILE     => $cookie_file,
    CURLOPT_ENCODING       => 'utf-8',
    CURLOPT_AUTOREFERER    => true,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_TIMEOUT        => 120,
    CURLOPT_HEADERFUNCTION => function($ch, $header) {
        // Transmettre Content-Type et Content-Length du CDN
        $lower = strtolower($header);
        if (str_starts_with($lower, 'content-type:') || str_starts_with($lower, 'content-length:')) {
            header(rtrim($header));
        }
        return strlen($header);
    },
    CURLOPT_WRITEFUNCTION  => function($ch, $data) {
        echo $data;
        flush();
        return strlen($data);
    },
]);

// Forcer le téléchargement
header('Content-Type: video/mp4');
header('Content-Disposition: attachment; filename="tiktok-' . time() . '.mp4"');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // Désactive le buffering LiteSpeed/Nginx

curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 206) {
    http_response_code(502);
}