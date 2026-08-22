<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$url = $_GET['url'] ?? '';

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètre "url" requis']);
    exit;
}

if (!preg_match('#^https?://(www\.)?twitch\.tv/#i', $url)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL Twitch invalide']);
    exit;
}

$command = $url . ' -J';
$encodedCmd = urlencode($command);
$streamUrl = "https://ytdlp.online/stream?command=" . $encodedCmd;

$rawJsonLine = null;
$attempt = 0;
$maxAttempts = 5;

while ($rawJsonLine === null && $attempt < $maxAttempts) {
    $attempt++;
    $buffer = '';
    $hasError = false;

    $ch = curl_init($streamUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Accept: text/event-stream',
            'Cache-Control: no-cache',
        ],
    ]);

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) use (&$buffer, &$rawJsonLine, &$hasError) {
        $buffer .= $data;

        while (($pos = strpos($buffer, "\n")) !== false) {
            $line = substr($buffer, 0, $pos);
            $buffer = substr($buffer, $pos + 1);

            if (trim($line) === '') continue;

            if (strpos($line, 'data: ') === 0) {
                $content = substr($line, 6);
                $cleanContent = strip_tags($content);
                $cleanContent = html_entity_decode($cleanContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cleanContent = trim($cleanContent);

                if (!empty($cleanContent) && $cleanContent !== '{}') {
                    if (stripos($cleanContent, 'Connection refused') !== false
                        || stripos($cleanContent, 'Max retries reached') !== false
                        || stripos($cleanContent, 'Failed to establish') !== false
                    ) {
                        $hasError = true;
                    }

                    if ($rawJsonLine === null) {
                        $firstChar = substr($cleanContent, 0, 1);
                        if ($firstChar === '{' || $firstChar === '[') {
                            $testDecode = json_decode($cleanContent, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $rawJsonLine = $cleanContent;
                            }
                        }
                    }
                }
            }

            if (strpos($line, 'event: close') === 0) {
                return 0;
            }
        }

        return strlen($data);
    });

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($rawJsonLine !== null) {
        break;
    }

    sleep(2);
}

if ($rawJsonLine === null) {
    http_response_code(504);
    echo json_encode(['error' => 'Impossible de récupérer les données Twitch']);
    exit;
}

$raw = json_decode($rawJsonLine, true);

$hasHls = false;
$qualities = [];

if (!empty($raw['formats'])) {
    foreach ($raw['formats'] as $fmt) {
        $fmtUrl = $fmt['url'] ?? '';
        if (!empty($fmtUrl)) {
            if (str_contains($fmtUrl, '.m3u8') || ($fmt['protocol'] ?? '') === 'm3u8_native') {
                $hasHls = true;
            }
            $qualities[] = [
                ($fmt['format_id'] ?? 'unknown') => $fmtUrl
            ];
        }
    }
}

$origineQuality = !empty($raw['formats'][0]['url']) ? $raw['formats'][0]['url'] : null;

$output = [
    'platform'         => 'twitch',
    'logo'             => 'Twitch.jpg',
    'name'             => $raw['uploader'] ?? null,
    'creator_pic'      => $raw['thumbnail'] ?? null,
    'status_live'      => $hasHls ? true : false,
    'room_id'          => $raw['uploader_id'] ?? null,
    'live_title'       => $raw['title'] ?? null,
    'live_description' => $raw['fulltitle'] ?? null,
    'follower'         => null,
    'followed'         => null,
    'spectator'        => $raw['concurrent_view_count'] ?? null,
    'live_since'       => $raw['release_timestamp'] ?? null,
    'origine_quality'  => $origineQuality,
    'qualities'        => $qualities,
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);