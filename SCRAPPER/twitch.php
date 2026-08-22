#!/usr/bin/env php
<?php

/**
 * Script CLI pour récupérer le JSON d'un live Twitch via ytdlp.online
 * Usage: php twitch_json.php <URL_twitch.tv>
 *    ou: php twitch_json.php  (saisie interactive)
 */

// ─── Saisie de l'URL ──────────────────────────────────────────────────────────
if ($argc >= 2) {
    $url = trim($argv[1]);
} else {
    echo "┌─────────────────────────────────────┐\n";
    echo "│      Twitch JSON Fetcher (yt-dlp)   │\n";
    echo "└─────────────────────────────────────┘\n\n";
    echo "🔗 Entre l'URL Twitch (ex: https://twitch.tv/xqc) :\n> ";
    $url = trim(fgets(STDIN));
}

if (empty($url)) {
    echo "❌ URL vide. Abandon.\n";
    exit(1);
}

// ─── Validation ───────────────────────────────────────────────────────────────
if (!preg_match('#^https?://(www\.)?twitch\.tv/#i', $url)) {
    echo "❌ L'URL doit commencer par https://twitch.tv/\n";
    echo "   Reçu : {$url}\n";
    exit(1);
}

// ─── Nom du fichier de sortie ─────────────────────────────────────────────────
$channel = trim(parse_url($url, PHP_URL_PATH), '/');
$channel = explode('/', $channel)[0];
$channel = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $channel);
$outputFile = __DIR__ . DIRECTORY_SEPARATOR . "twitch_{$channel}.json";

echo "\n🎯 Canal   : {$channel}\n";
echo "🔗 URL     : {$url}\n";
echo "📁 Fichier : {$outputFile}\n\n";

// ─── Appel SSE ytdlp.online avec retry infini ────────────────────────────────
$command    = $url . ' -J';
$encodedCmd = urlencode($command);
$streamUrl  = "https://ytdlp.online/stream?command=" . $encodedCmd;

$rawJsonLine = null;
$attempt     = 0;

while ($rawJsonLine === null) {
    $attempt++;
    echo "⏳ Tentative #{$attempt} — Connexion à ytdlp.online...\n\n";

    $buffer   = '';
    $hasError = false;

    $ch = curl_init($streamUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_HTTPHEADER     => [
            'Accept: text/event-stream',
            'Cache-Control: no-cache',
        ],
    ]);

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) use (&$buffer, &$rawJsonLine, &$hasError) {
        $buffer .= $data;

        while (($pos = strpos($buffer, "\n")) !== false) {
            $line   = substr($buffer, 0, $pos);
            $buffer = substr($buffer, $pos + 1);

            if (trim($line) === '') continue;

            if (strpos($line, 'data: ') === 0) {
                $content      = substr($line, 6);
                $cleanContent = strip_tags($content);
                $cleanContent = html_entity_decode($cleanContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $cleanContent = trim($cleanContent);

                if (!empty($cleanContent) && $cleanContent !== '{}') {
                    echo $cleanContent . "\n";

                    // Détecter une erreur de connexion pour déclencher un retry
                    if (stripos($cleanContent, 'Connection refused') !== false
                        || stripos($cleanContent, 'Max retries reached') !== false
                        || stripos($cleanContent, 'Failed to establish') !== false
                    ) {
                        $hasError = true;
                    }

                    // Détecter le JSON retourné par -J
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

    echo "\n";

    if ($rawJsonLine !== null) {
        break; // Succès !
    }

    if ($httpCode !== 200) {
        echo "⚠️  Erreur HTTP {$httpCode} — Nouvelle tentative dans 5 secondes...\n\n";
    } elseif ($hasError) {
        echo "⚠️  Proxy indisponible — Nouvelle tentative dans 5 secondes...\n\n";
    } else {
        echo "⚠️  Aucun JSON reçu — Nouvelle tentative dans 5 secondes...\n\n";
    }

    sleep(5);
}

// ─── Extraction des données utiles ───────────────────────────────────────────
$raw = json_decode($rawJsonLine, true);

// Détecter si un flux HLS est présent + construire les qualités
$hasHls    = false;
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

// URL du format d'origine (premier format)
$origineQuality = !empty($raw['formats'][0]['url']) ? $raw['formats'][0]['url'] : null;

// ─── Structure finale ─────────────────────────────────────────────────────────
$output = [
    'name'             => $raw['uploader']              ?? null,
    'creator_pic'      => $raw['thumbnail']             ?? null,
    'status_live'      => $hasHls ? 1 : 0,
    'room_id'          => $raw['uploader_id']           ?? null,
    'live_title'       => $raw['title']                 ?? null,
    'live_description' => $raw['fulltitle']             ?? null,
    'follower'         => null,
    'followed'         => null,
    'spectator'        => $raw['concurrent_view_count'] ?? null,
    'live_since'       => $raw['release_timestamp']     ?? null,
    'origine_quality'  => $origineQuality,
    'qualities'        => $qualities,
];

$prettyJson = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if (file_put_contents($outputFile, $prettyJson) === false) {
    echo "❌ Impossible d'écrire le fichier : {$outputFile}\n";
    exit(1);
}

// ─── Résumé ───────────────────────────────────────────────────────────────────
echo "✅ JSON sauvegardé : {$outputFile}\n";
echo "📦 Taille          : " . number_format(strlen($prettyJson)) . " octets\n";

echo "👤 Streamer  : {$output['name']}\n";
echo "🔴 Live      : " . ($output['status_live'] ? 'Oui' : 'Non') . "\n";
echo "🎬 Titre     : {$output['live_title']}\n";
echo "👁️  Viewers   : {$output['spectator']}\n";
echo "🎞️  Qualités  : " . count($qualities) . " format(s) disponible(s)\n";

echo "\n✨ Terminé !\n";