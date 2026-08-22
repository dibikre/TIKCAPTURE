<?php

// ══════════════════════════════════════════════════════════════════
// API YOUTUBE LIVE - Pour TikCapture
// ══════════════════════════════════════════════════════════════════

header('Content-Type: application/json; charset=utf-8');

// ── Récupération URL ──────────────────────────────────────────────
$inputUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $inputUrl = $json['url'] ?? '';
} else {
    $inputUrl = $_GET['url'] ?? '';
}

$inputUrl = trim($inputUrl);

// ── Validation URL ────────────────────────────────────────────────
if (empty($inputUrl)) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètre "url" requis']);
    exit;
}

if (!preg_match('#^https?://(www\.)?(youtube\.com|youtu\.be)/#i', $inputUrl)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL YouTube invalide']);
    exit;
}

// ══════════════════════════════════════════════════════════════════
// Appel ytdlp.online AVEC RETRY
// ══════════════════════════════════════════════════════════════════

$streamUrl   = "https://ytdlp.online/stream?command=" . urlencode($inputUrl . ' -J');
$rawJsonLine = null;
$attempt     = 0;
$maxAttempts = 5;

while ($rawJsonLine === null && $attempt < $maxAttempts) {
    $attempt++;
    $buffer = '';

    $ch = curl_init($streamUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_HTTPHEADER     => ['Accept: text/event-stream', 'Cache-Control: no-cache'],
    ]);

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) use (&$buffer, &$rawJsonLine) {
        $buffer .= $data;
        while (($pos = strpos($buffer, "\n")) !== false) {
            $line   = substr($buffer, 0, $pos);
            $buffer = substr($buffer, $pos + 1);
            if (trim($line) === '') continue;
            if (strpos($line, 'data: ') === 0) {
                $content = trim(html_entity_decode(strip_tags(substr($line, 6)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if (!empty($content) && $content !== '{}' && $rawJsonLine === null) {
                    $fc = substr($content, 0, 1);
                    if ($fc === '{' || $fc === '[') {
                        json_decode($content);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $rawJsonLine = $content;
                            return 0;
                        }
                    }
                }
            }
            if (strpos($line, 'event: close') === 0) return 0;
        }
        return strlen($data);
    });

    curl_exec($ch);
    curl_close($ch);

    if ($rawJsonLine !== null) break;
    sleep(7);
}

if ($rawJsonLine === null) {
    http_response_code(504);
    echo json_encode(['error' => 'Impossible de récupérer les données YouTube après ' . $maxAttempts . ' tentatives']);
    exit;
}

// ══════════════════════════════════════════════════════════════════
// Traitement du JSON brut
// ══════════════════════════════════════════════════════════════════

$raw = json_decode($rawJsonLine, true);

// status_live
$isLive = (($raw['live_status'] ?? null) === 'is_live');

// room_id
$roomId = $raw['subtitles']['live_chat'][0]['video_id'] ?? null;

// creator_pic : thumbnails[n].url, premier disponible
$creatorPic = null;
foreach (($raw['thumbnails'] ?? []) as $thumb) {
    if (!empty($thumb['url'])) { $creatorPic = $thumb['url']; break; }
}

// live_title : fulltitle décodé
$liveTitle = null;
if (!empty($raw['fulltitle'])) {
    $liveTitle = html_entity_decode(mb_convert_encoding($raw['fulltitle'], 'UTF-8', 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// live_description : description décodée
$liveDescription = null;
if (!empty($raw['description'])) {
    $liveDescription = html_entity_decode(mb_convert_encoding($raw['description'], 'UTF-8', 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// qualities SIMPLIFIÉ - uniquement resolution et url
$origineQuality    = null;
$origineResolution = null;
$qualities         = [];

if (!empty($raw['formats']) && is_array($raw['formats'])) {
    $first             = $raw['formats'][0];
    $origineQuality    = $first['url']        ?? null;
    $origineResolution = $first['resolution'] ?? null;

    foreach ($raw['formats'] as $fmt) {
        $fmtUrl = $fmt['url'] ?? '';
        if (empty($fmtUrl)) continue;
        $isHls = str_contains($fmtUrl, '.m3u8')
               || in_array($fmt['protocol'] ?? '', ['m3u8_native', 'm3u8']);
        if (!$isHls) continue;

        $qualities[] = [
            'resolution' => $fmt['resolution'] ?? null,
            'url'        => $fmtUrl,
        ];
    }

    // Fallback si aucun HLS
    if (empty($qualities)) {
        foreach ($raw['formats'] as $fmt) {
            $fmtUrl = $fmt['url'] ?? '';
            if (empty($fmtUrl)) continue;
            $qualities[] = [
                'resolution' => $fmt['resolution'] ?? null,
                'url'        => $fmtUrl,
            ];
        }
    }
}

// ══════════════════════════════════════════════════════════════════
// Réponse JSON formatée pour le frontend
// ══════════════════════════════════════════════════════════════════

$output = [
    'platform'           => 'youtube',
    'logo'               => 'YouTube.jpg',
    'name'               => $raw['uploader']               ?? null,
    'creator_pic'        => $creatorPic,
    'status_live'        => $isLive,
    'room_id'            => $roomId,
    'live_title'         => $liveTitle,
    'live_description'   => $liveDescription,
    'follower'           => $raw['channel_follower_count'] ?? null,
    'followed'           => null,
    'spectator'          => $raw['concurrent_viewers']     ?? $raw['view_count'] ?? null,
    'live_since'         => $raw['release_timestamp']      ?? null,
    'origine_quality'    => $origineQuality,
    'origine_resolution' => $origineResolution,
    'qualities'          => $qualities,
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
