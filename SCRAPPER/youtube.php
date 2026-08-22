<?php

// ══════════════════════════════════════════════════════════════════
// Détection du mode : CLI ou HTTP
// ══════════════════════════════════════════════════════════════════

$isCli = (php_sapi_name() === 'cli');

if ($isCli) {
    // ── Mode CLI ──────────────────────────────────────────────────
    if (!empty($argv[1])) {
        $inputUrl = trim($argv[1]);
    } else {
        fwrite(STDOUT, "\n\e[31m●\e[0m \e[1mYouTube Live API Tester\e[0m\n");
        fwrite(STDOUT, "\e[90m─────────────────────────────────────────\e[0m\n");
        fwrite(STDOUT, "URL YouTube : ");
        $inputUrl = trim(fgets(STDIN));
    }
} else {
    // ── Mode HTTP ─────────────────────────────────────────────────
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    $inputUrl = $_GET['url'] ?? '';
}

// ══════════════════════════════════════════════════════════════════
// Validation URL
// ══════════════════════════════════════════════════════════════════

if (empty($inputUrl)) {
    if ($isCli) { fwrite(STDERR, "\e[31m❌ URL vide.\e[0m\n"); exit(1); }
    http_response_code(400);
    echo json_encode(['error' => 'Paramètre "url" requis']);
    exit;
}

if (!preg_match('#^https?://(www\.)?(youtube\.com|youtu\.be)/#i', $inputUrl)) {
    if ($isCli) { fwrite(STDERR, "\e[31m❌ URL YouTube invalide.\e[0m\n"); exit(1); }
    http_response_code(400);
    echo json_encode(['error' => 'URL YouTube invalide']);
    exit;
}

if ($isCli) fwrite(STDOUT, "\n\e[90m⏳ Récupération en cours...\e[0m\n\n");

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

    if ($isCli) {
        fwrite(STDOUT, "\e[90m   Tentative {$attempt}/{$maxAttempts}...\e[0m\n");
    }

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

    if ($rawJsonLine !== null) {
        if ($isCli) {
            fwrite(STDOUT, "\e[32m   ✓ Données reçues !\e[0m\n\n");
        }
        break;
    }

    if ($isCli && $attempt < $maxAttempts) {
        fwrite(STDOUT, "\e[33m   ⚠ Échec, nouvelle tentative dans 7s...\e[0m\n");
        sleep(7);
    }
}

if ($rawJsonLine === null) {
    if ($isCli) { 
        fwrite(STDERR, "\e[31m❌ Impossible de récupérer les données après {$maxAttempts} tentatives.\e[0m\n"); 
        exit(1); 
    }
    http_response_code(504);
    echo json_encode(['error' => 'Impossible de récupérer les données YouTube']);
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

        // SIMPLIFIÉ : uniquement resolution et url
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
    'spectator'          => $raw['view_count']             ?? null,
    'live_since'         => $raw['release_timestamp']      ?? null,
    'origine_quality'    => $origineQuality,
    'origine_resolution' => $origineResolution,
    'qualities'          => $qualities,
];

// ══════════════════════════════════════════════════════════════════
// SAUVEGARDE AUTOMATIQUE DU JSON
// ══════════════════════════════════════════════════════════════════

$scriptDir = __DIR__;
$timestamp = date('Y-m-d_H-i-s');
$videoId = $roomId ?? 'unknown';
$filename = "youtube_live_{$videoId}_{$timestamp}.json";
$filepath = $scriptDir . DIRECTORY_SEPARATOR . $filename;

$jsonContent = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (file_put_contents($filepath, $jsonContent) !== false) {
    if ($isCli) {
        fwrite(STDOUT, "\e[32m✅ JSON sauvegardé :\e[0m \e[97m{$filename}\e[0m\n");
        fwrite(STDOUT, "\e[90m   Chemin : {$filepath}\e[0m\n\n");
    }
} else {
    if ($isCli) {
        fwrite(STDERR, "\e[31m⚠️ Impossible de sauvegarder le fichier JSON.\e[0m\n\n");
    }
}

// ══════════════════════════════════════════════════════════════════
// Sortie : JSON (HTTP) ou affichage terminal (CLI)
// ══════════════════════════════════════════════════════════════════

if (!$isCli) {
    echo $jsonContent;
    exit;
}

// ── Affichage CLI ─────────────────────────────────────────────────

function cli_row($label, $value, $w = 22) {
    $val = ($value !== null && $value !== '') ? $value : '—';
    echo "  \e[90m" . str_pad($label, $w) . "\e[0m $val\n";
}

function cli_trunc($s, $max = 80) {
    return mb_strlen($s) > $max ? mb_strimwidth($s, 0, $max, '…') : $s;
}

$badge = $isLive ? "\e[41m\e[97m LIVE \e[0m" : "\e[100m\e[97m OFFLINE \e[0m";

echo "\e[31m══════════════════════════════════════════\e[0m\n";
echo "  \e[1m\e[97m" . ($output['name'] ?? 'Inconnu') . "\e[0m  $badge\n";
echo "\e[31m══════════════════════════════════════════\e[0m\n\n";

cli_row("Titre",          $output['live_title']);
cli_row("Room ID",        $output['room_id']);
cli_row("Spectateurs",    $output['spectator'] !== null ? number_format($output['spectator'], 0, ',', ' ') : null);
cli_row("Abonnés",        $output['follower']  !== null ? number_format($output['follower'],  0, ',', ' ') : null);
cli_row("En live depuis", $output['live_since'] ? date('d/m/Y H:i:s', $output['live_since']) : null);

echo "\n\e[90m── Origine quality ──────────────────────────────────────────\e[0m\n";
cli_row("Résolution", $output['origine_resolution'] ?? '—');
cli_row("URL",        $output['origine_quality'] ? cli_trunc($output['origine_quality'], 72) : null);

$qualities = $output['qualities'] ?? [];
echo "\n\e[90m── Qualités disponibles (" . count($qualities) . ") ──────────────────────────────\e[0m\n\n";

if (empty($qualities)) {
    echo "  \e[90m—\e[0m\n";
} else {
    printf("  \e[90m%-14s  %s\e[0m\n", 'résolution', 'url');
    echo "  \e[90m" . str_repeat('─', 90) . "\e[0m\n";
    foreach ($qualities as $q) {
        printf(
            "  \e[97m%-14s\e[0m  \e[90m%s\e[0m\n",
            $q['resolution'] ?? '—',
            cli_trunc($q['url'] ?? '—', 70)
        );
    }
}

if (!empty($output['live_description'])) {
    $desc = mb_strimwidth(str_replace("\n", ' ', $output['live_description']), 0, 120, '…');
    echo "\n\e[90m── Description ──────────────────────────────────────────────\e[0m\n";
    echo "  \e[37m$desc\e[0m\n";
}

echo "\n\e[90mAfficher le JSON complet ? (o/N) : \e[0m";
$showJson = trim(fgets(STDIN));
if (strtolower($showJson) === 'o') {
    echo "\n" . $jsonContent . "\n";
}

echo "\n";
