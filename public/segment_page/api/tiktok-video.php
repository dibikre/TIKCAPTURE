<?php
/**
 * TikCapture — API Téléchargement Vidéo TikTok
 * URL  : https://tikcapture.live/segment_page/api/tiktok-video.php
 * Body : POST JSON { "url": "https://www.tiktok.com/@user/video/xxx" }
 */

// ─── CORS ─────────────────────────────────────────────────────────────────────

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

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
}

// ─── Lecture body JSON ────────────────────────────────────────────────────────

$body = json_decode(file_get_contents('php://input'), true);
$url  = isset($body['url']) ? trim($body['url']) : '';

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'URL manquante']);
    exit();
}

// ─── Nettoyage URL (supprimer query params parasites) ─────────────────────────

// URLs courtes : suivre la redirection
if (preg_match('/^https?:\/\/(vm|vt|t)\.tiktok\.com/i', $url)) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_NOBODY         => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;
    curl_close($ch);
}

// Supprimer les query params parasites
$parsed = parse_url($url);
if (!empty($parsed['host']) && stripos($parsed['host'], 'tiktok.com') !== false) {
    $url = ($parsed['scheme'] ?? 'https') . '://' . $parsed['host'] . ($parsed['path'] ?? '');
}

// Valider que c'est bien une URL de vidéo
if (!preg_match('/\/video\/\d+/', $url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'URL TikTok invalide. Format attendu : https://www.tiktok.com/@username/video/ID']);
    exit();
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

$cookie_file = __DIR__ . '/cookie.txt';

function formatBytes($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function escape_sequence_decode($str) {
    $regex = '/\\\u([dD][89abAB][\da-fA-F]{2})\\\u([dD][c-fC-F][\da-fA-F]{2})|\\\u([\da-fA-F]{4})/sx';
    return preg_replace_callback($regex, function ($m) {
        if (isset($m[3])) {
            $cp = hexdec($m[3]);
        } else {
            $cp = (hexdec($m[1]) << 10) + hexdec($m[2]) + 0x10000 - (0xD800 << 10) - 0xDC00;
        }
        if ($cp > 0xD7FF && 0xE000 > $cp) $cp = 0xFFFD;
        if ($cp < 0x80)  return chr($cp);
        if ($cp < 0xA0)  return chr(0xC0 | $cp >> 6) . chr(0x80 | $cp & 0x3F);
        return html_entity_decode('&#' . $cp . ';');
    }, $str);
}

// Identique à getContent() de l'ancien projet
function httpGet($url) {
    global $cookie_file;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        CURLOPT_ENCODING       => 'utf-8',
        CURLOPT_AUTOREFERER    => false,
        CURLOPT_COOKIEJAR      => $cookie_file,
        CURLOPT_COOKIEFILE     => $cookie_file,
        CURLOPT_REFERER        => 'https://www.tiktok.com/',
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_MAXREDIRS      => 10,
    ]);
    if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }
    $data = curl_exec($ch);
    curl_close($ch);
    return strval($data);
}

function extractBetween($str, $start, $end) {
    $parts = explode($start, $str);
    if (count($parts) < 2) return '';
    return explode($end, $parts[1])[0];
}

// ─── Fetch page TikTok (avec retry WAF) ──────────────────────────────────────

$resp = '';
for ($attempt = 1; $attempt <= 3; $attempt++) {
    $resp = httpGet($url);

    // WAF challenge détecté → attendre 3s et réessayer
    if (strpos($resp, 'wafchallengeid') !== false || strpos($resp, 'Please wait') !== false) {
        if ($attempt < 3) { sleep(3); continue; }
    }

    // Données trouvées → ok
    if (strpos($resp, '"playAddr":"') !== false || strpos($resp, '"downloadAddr":"') !== false) {
        break;
    }

    if ($attempt < 3) { sleep(2); }
}

if (empty($resp)) {
    echo json_encode(['success' => false, 'error' => 'Impossible de récupérer la page TikTok']);
    exit();
}

$hasSans = strpos($resp, '"playAddr":"') !== false;
$hasAvec = strpos($resp, '"downloadAddr":"') !== false;

if (!$hasSans && !$hasAvec) {
    echo json_encode([
        'success' => false,
        'error'   => 'Vidéo introuvable. Vérifiez que l\'URL est correcte et que la vidéo est publique.',
    ]);
    exit();
}

// ─── Extraction vidéo ─────────────────────────────────────────────────────────

$urlNoWatermark = $hasSans ? escape_sequence_decode(extractBetween($resp, '"playAddr":"',    '"')) : '';
$urlWatermark   = $hasAvec ? escape_sequence_decode(extractBetween($resp, '"downloadAddr":"', '"')) : '';

$dynamicCover = escape_sequence_decode(extractBetween($resp, '"dynamicCover":"', '"'));
$cover        = escape_sequence_decode(extractBetween($resp, '"cover":"',        '"'));
$videoDesc    = escape_sequence_decode(extractBetween($resp, '"desc":"',         '"'));
$videoWidth   = (int) extractBetween($resp, '"width":',        ',');
$videoHeight  = (int) extractBetween($resp, '"height":',       ',');
$playCount    = (int) extractBetween($resp, '"playCount":',    ',');
$commentCount = (int) extractBetween($resp, '"commentCount":', ',');
$shareCount   = (int) extractBetween($resp, '"shareCount":',   ',');

$createTime = '';
$rawTime = extractBetween($resp, '"createTime":"', '"');
if ($rawTime) {
    $dt = new DateTime("@$rawTime");
    $createTime = $dt->format('d M Y H:i:s');
}

preg_match('/"itemStruct":{"id":"(.+?)"/', $resp, $vidMatch);
$videoId = $vidMatch[1] ?? '';

$hashtags = [];
preg_match_all('/"hashtagName":"([^"]+)"/', $resp, $hm);
if (!empty($hm[1])) {
    $hashtags = array_values(array_unique($hm[1]));
}

// ─── Extraction auteur ────────────────────────────────────────────────────────

$username       = extractBetween($resp, 'uniqueId":"',      '"');
$authorNickname = extractBetween($resp, '"nickname":"',     '"');
$authorSig      = escape_sequence_decode(extractBetween($resp, '"signature":"',   '"'));
$authorAvatar   = escape_sequence_decode(extractBetween($resp, '"avatarLarger":"','"'));
$videoCount     = (int) extractBetween($resp, '"videoCount":',   ',');
$followingCount = (int) extractBetween($resp, '"followingCount":',',');
$followerCount  = (int) extractBetween($resp, '"followerCount":', ',');

// ─── API related — identique à getDownloadableLink() de l'ancien projet ───────

$videoSize          = 0;
$videoSizeFormatted = 'N/A';
$relatedVideos      = [];

if (!empty($videoId)) {
    $apiUrl = "https://www.tiktok.com/api/related/item_list/?WebIdLastTime=0&aid=1988&app_language=en&app_name=tiktok_web&browser_language=en-US&browser_name=Mozilla&browser_online=true&browser_platform=Win32&channel=tiktok_web&count=8&cursor=0&data_collection_enabled=true&device_platform=web_pc&focus_state=false&from_page=video&history_len=4&isNonPersonalized=false&is_fullscreen=false&is_page_visible=true&itemID={$videoId}&language=en&os=windows";
    $apiResp = httpGet($apiUrl);
    $apiData = json_decode($apiResp, true);

    if (isset($apiData['itemList'][0]['video']['PlayAddrStruct']['DataSize'])) {
        $videoSize          = (int) $apiData['itemList'][0]['video']['PlayAddrStruct']['DataSize'];
        $videoSizeFormatted = formatBytes($videoSize);
    }

    if (isset($apiData['itemList']) && count($apiData['itemList']) > 1) {
        for ($i = 1; $i < count($apiData['itemList']); $i++) {
            $item = $apiData['itemList'][$i];
            $relatedVideos[] = [
                'id'              => $item['id'] ?? '',
                'desc'            => $item['desc'] ?? '',
                'cover'           => $item['video']['cover'] ?? '',
                'dynamic_cover'   => $item['video']['dynamicCover'] ?? '',
                'author_nickname' => $item['author']['nickname'] ?? '',
                'author_avatar'   => $item['author']['avatarThumb'] ?? '',
                'author_uniqueid' => $item['author']['uniqueId'] ?? '',
                'play_count'      => $item['stats']['playCount'] ?? 0,
                'digg_count'      => $item['stats']['diggCount'] ?? 0,
                'comment_count'   => $item['stats']['commentCount'] ?? 0,
                'duration'        => $item['video']['duration'] ?? 0,
                'video_url'       => 'https://www.tiktok.com/@' . ($item['author']['uniqueId'] ?? '') . '/video/' . ($item['id'] ?? ''),
            ];
        }
    }
}

// ─── Réponse JSON ─────────────────────────────────────────────────────────────

echo json_encode([
    'success' => true,
    'video'   => [
        'id'             => $videoId,
        'desc'           => $videoDesc,
        'cover'          => $cover,
        'dynamicCover'   => $dynamicCover,
        'createTime'     => $createTime,
        'width'          => $videoWidth,
        'height'         => $videoHeight,
        'size'           => $videoSize,
        'sizeFormatted'  => $videoSizeFormatted,
        'urlNoWatermark' => $urlNoWatermark,
        'urlWatermark'   => $urlWatermark,
        'hashtags'       => $hashtags,
        'stats'          => [
            'plays'    => $playCount,
            'comments' => $commentCount,
            'shares'   => $shareCount,
        ],
    ],
    'author'  => [
        'username'   => $username,
        'nickname'   => $authorNickname,
        'signature'  => $authorSig,
        'avatar'     => $authorAvatar,
        'videoCount' => $videoCount,
        'following'  => $followingCount,
        'followers'  => $followerCount,
    ],
    'related' => $relatedVideos,
], JSON_UNESCAPED_UNICODE);