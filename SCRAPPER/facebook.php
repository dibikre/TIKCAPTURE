#!/usr/bin/env php
<?php

/**
 * Facebook Live Page Fetcher
 * --------------------------
 * Récupère le code source d'une page Facebook Live (avec gestion des cookies)
 * et extrait le bloc JSON <script type="application/json" data-sjs> contenant
 * le terme exact : VideoPlayerScrubberPreviewImageOnCanvas
 *
 * Usage : php facebook_live_fetch.php
 */

define('COOKIE_FILE',   __DIR__ . '/fb_cookies.txt');
define('USER_AGENT',    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                      . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                      . 'Chrome/123.0.0.0 Safari/537.36');
// Termes cibles : chaque terme donnera un fichier .json distinct
define('JSON_TARGETS', [
    'VideoPlayerScrubberPreviewImageOnCanvas',  // bloc lecteur vidéo
    'is_video_broadcast',                       // bloc broadcast live
]);

// ── Couleurs ANSI ──────────────────────────────────────────────────────────────
function colored(string $text, string $color): string {
    $colors = [
        'red'    => "\033[31m",
        'green'  => "\033[32m",
        'yellow' => "\033[33m",
        'cyan'   => "\033[36m",
        'bold'   => "\033[1m",
        'reset'  => "\033[0m",
    ];
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

function info(string $msg): void  { echo colored("[INFO] ", 'cyan')   . $msg . PHP_EOL; }
function ok(string $msg): void    { echo colored("[OK]   ", 'green')  . $msg . PHP_EOL; }
function warn(string $msg): void  { echo colored("[WARN] ", 'yellow') . $msg . PHP_EOL; }
function error(string $msg): void { echo colored("[ERR]  ", 'red')    . $msg . PHP_EOL; }

// ── Validation de l'URL ────────────────────────────────────────────────────────
function validateFacebookUrl(string $url): bool {
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
    $host = parse_url($url, PHP_URL_HOST);
    return in_array($host, ['www.facebook.com', 'facebook.com', 'm.facebook.com', 'fb.watch']);
}

// ── Initialisation cURL commune ────────────────────────────────────────────────
function buildCurl(string $url): \CurlHandle {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => USER_AGENT,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_ENCODING       => '',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
        ],
        CURLOPT_COOKIEFILE     => COOKIE_FILE,
        CURLOPT_COOKIEJAR      => COOKIE_FILE,
    ]);
    return $ch;
}

// ── Étape 1 : première requête pour obtenir les cookies ────────────────────────
function fetchWithCookies(string $url): array {
    info("Première requête vers : $url");
    info("Fichier cookies : " . COOKIE_FILE);

    $ch = buildCurl($url);

    $responseHeaders = [];
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) use (&$responseHeaders) {
        $responseHeaders[] = trim($header);
        return strlen($header);
    });

    $html     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        error("Erreur cURL : $err");
        return ['success' => false, 'html' => null, 'http_code' => 0];
    }

    info("Code HTTP : $httpCode  |  URL finale : $finalUrl");

    $setCookies = array_filter($responseHeaders, fn($h) => stripos($h, 'set-cookie:') === 0);
    if ($setCookies) {
        ok("Cookies reçus (" . count($setCookies) . ") :");
        foreach ($setCookies as $c) {
            $name = explode('=', ltrim(substr($c, strlen('set-cookie:'))))[0];
            echo "       \xe2\x80\xa2 " . colored(trim($name), 'yellow') . PHP_EOL;
        }
    } else {
        warn("Aucun nouveau cookie reçu lors de cette requête.");
    }

    if ($httpCode >= 200 && $httpCode < 400 && $html !== false && strlen($html) > 0) {
        return ['success' => true, 'html' => $html, 'http_code' => $httpCode];
    }

    error("Reponse invalide (HTTP $httpCode, longueur : " . strlen((string)$html) . ")");
    return ['success' => false, 'html' => null, 'http_code' => $httpCode];
}

// ── Étape 2 : deuxième requête avec les cookies déjà stockés ──────────────────
function refetchWithStoredCookies(string $url): array {
    info("Deuxieme requete (avec cookies sauvegardes)...");
    $ch   = buildCurl($url);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err)  { error("Erreur cURL : $err"); return ['success' => false, 'html' => null]; }
    if ($code >= 200 && $code < 400 && $html) {
        ok("Reponse OK (HTTP $code, " . strlen($html) . " octets)");
        return ['success' => true, 'html' => $html];
    }
    error("Echec de la deuxieme requete (HTTP $code)");
    return ['success' => false, 'html' => null];
}

// ── Sauvegarde du fichier HTML ─────────────────────────────────────────────────
function saveHtml(string $html, string $url): string {
    $slug     = preg_replace('/[^a-zA-Z0-9_\-]/', '_', parse_url($url, PHP_URL_PATH) ?? 'live');
    $slug     = trim($slug, '_') ?: 'live';
    $filename = __DIR__ . '/facebook_live_' . $slug . '_' . date('Ymd_His') . '.html';
    file_put_contents($filename, $html);
    return $filename;
}

// ── Extraction & sauvegarde des JSON ciblés ───────────────────────────────────
/**
 * Extrait tous les blocs <script type="application/json" data-sjs> du HTML,
 * puis pour chaque terme dans JSON_TARGETS, sauvegarde le bloc qui contient
 * ce terme en MOT ENTIER (lookaround négatif sur [a-zA-Z0-9_]).
 *
 * Un fichier .json distinct est créé par terme trouvé.
 * Le suffixe du nom de fichier reprend les 30 premiers caractères du terme.
 *
 * Retourne : ['total' => int, 'results' => [ terme => fichier|null, ... ]]
 */
function extractAllDataSjsBlocks(string $html): array {
    $pattern = '/<script[^>]*type=["\'"]application\/json["\'"][^>]*data-sjs[^>]*>(.*?)<\/script>/si';
    if (!preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
        $pattern = '/<script[^>]*data-sjs[^>]*type=["\'"]application\/json["\'"][^>]*>(.*?)<\/script>/si';
        if (!preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            return [];
        }
    }
    return $matches;
}

function saveJsonBlock(string $rawJson, string $filename): void {
    $decoded = json_decode($rawJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        warn("JSON invalide (" . json_last_error_msg() . ") - sauvegarde brute.");
        $output = $rawJson;
    } else {
        $output = json_encode(
            $decoded,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
    file_put_contents($filename, $output);
}

function extractAndSaveJson(string $html, string $baseSlug): array {

    $blocks = extractAllDataSjsBlocks($html);
    $total  = count($blocks);

    if ($total === 0) {
        return ['total' => 0, 'results' => []];
    }

    info("Blocs <script data-sjs> detectes : " . colored((string)$total, 'yellow'));

    $timestamp = date('Ymd_His');
    $results   = [];   // [ terme => chemin_fichier | null ]

    foreach (JSON_TARGETS as $term) {
        info("Recherche du terme exact : " . colored($term, 'bold') . " ...");

        // Mot entier : pas de [a-zA-Z0-9_] immédiatement avant ou après
        $termRegex = '/(?<![a-zA-Z0-9_])' . preg_quote($term, '/') . '(?![a-zA-Z0-9_])/';

        $found = null;
        foreach ($blocks as $match) {
            if (preg_match($termRegex, $match[1])) {
                $found = trim($match[1]);
                break;   // on prend le premier bloc qui contient le terme
            }
        }

        if ($found === null || $found === '') {
            $results[$term] = null;
            continue;
        }

        // Suffixe lisible basé sur le terme (30 premiers caractères, sans espaces)
        $termSlug = substr(preg_replace('/[^a-zA-Z0-9]/', '_', $term), 0, 30);
        $filename = __DIR__ . '/facebook_live_' . $baseSlug . '_' . $termSlug . '_' . $timestamp . '.json';
        saveJsonBlock($found, $filename);
        $results[$term] = $filename;
    }

    return ['total' => $total, 'results' => $results];
}

// ── Point d'entrée ─────────────────────────────────────────────────────────────
function main(): void {
    echo PHP_EOL;
    echo colored("╔══════════════════════════════════════════╗", 'cyan') . PHP_EOL;
    echo colored("║   Facebook Live — Recuperation source    ║", 'cyan') . PHP_EOL;
    echo colored("╚══════════════════════════════════════════╝", 'cyan') . PHP_EOL;
    echo PHP_EOL;

    if (!function_exists('curl_init')) {
        error("L'extension PHP cURL est requise. Installez-la et relancez le script.");
        exit(1);
    }

    // ── Saisie de l'URL ──────────────────────────────────────────────────────
    do {
        echo colored("Entrez l'URL du live Facebook : ", 'bold');
        $url = trim((string) fgets(STDIN));

        if ($url === '') {
            warn("URL vide. Veuillez saisir une URL valide.");
            continue;
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        if (!validateFacebookUrl($url)) {
            warn("L'URL ne semble pas etre une URL Facebook valide. Reessayez.");
            $url = '';
        }
    } while (empty($url));

    echo PHP_EOL;

    // ── Requête 1 ─────────────────────────────────────────────────────────────
    $result1 = fetchWithCookies($url);

    if (!$result1['success']) {
        error("Impossible de recuperer la page lors de la premiere requete.");
        if (!file_exists(COOKIE_FILE) || filesize(COOKIE_FILE) === 0) {
            error("Aucun cookie disponible. Abandon.");
            exit(1);
        }
        warn("Tentative avec les cookies existants malgre l'echec...");
    }

    echo PHP_EOL;

    // ── Requête 2 ─────────────────────────────────────────────────────────────
    $result2   = refetchWithStoredCookies($url);
    $finalHtml = $result2['html'] ?? $result1['html'];

    if (empty($finalHtml)) {
        error("Impossible de recuperer le code source. Verifiez l'URL ou votre connexion.");
        exit(1);
    }

    echo PHP_EOL;

    // ── Sauvegarde HTML ───────────────────────────────────────────────────────
    $slug     = preg_replace('/[^a-zA-Z0-9_\-]/', '_', parse_url($url, PHP_URL_PATH) ?? 'live');
    $slug     = trim($slug, '_') ?: 'live';
    $htmlPath = saveHtml($finalHtml, $url);
    ok("Code source HTML sauvegarde dans :");
    echo "       " . colored($htmlPath, 'green') . PHP_EOL;

    // ── Extraction JSON ciblée (tous les termes) ─────────────────────────────
    echo PHP_EOL;
    info("Extraction des blocs JSON cibles (" . count(JSON_TARGETS) . " termes)...");

    $result = extractAndSaveJson($finalHtml, $slug);

    if ($result['total'] === 0) {
        warn("Aucun bloc <script data-sjs> trouve dans le HTML.");
        warn("La page retournee est peut-etre une page de connexion Facebook.");
    } else {
        $found   = array_filter($result['results']);
        $missing = array_keys(array_filter($result['results'], fn($v) => $v === null));

        if (!empty($found)) {
            ok(count($found) . "/" . count(JSON_TARGETS) . " bloc(s) JSON trouves et sauvegardes :");
            foreach ($found as $term => $filepath) {
                echo "       " . colored("[" . $term . "]", 'cyan') . PHP_EOL;
                echo "       -> " . colored($filepath, 'green') . PHP_EOL;
            }
        }
        if (!empty($missing)) {
            echo PHP_EOL;
            warn(count($missing) . " terme(s) non trouves dans les blocs data-sjs :");
            foreach ($missing as $term) {
                echo "       " . colored("[-] " . $term, 'yellow') . PHP_EOL;
            }
            warn("Verifiez vos cookies ou que l'URL est bien un live video actif.");
        }
    }

    // ── Résumé ────────────────────────────────────────────────────────────────
    echo PHP_EOL;
    $cookieCount = 0;
    if (file_exists(COOKIE_FILE)) {
        $lines       = file(COOKIE_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $cookieCount = count(array_filter($lines, fn($l) => $l[0] !== '#'));
    }
    info("Cookies totaux stockes  : $cookieCount  (fichier : " . COOKIE_FILE . ")");
    info("Taille du HTML          : " . number_format(strlen($finalHtml)) . " octets");
    echo PHP_EOL;

    echo colored("Note : ", 'yellow')
       . "Si aucun JSON cible n'est trouve, exportez vos cookies de session\n"
       . "   depuis le navigateur (EditThisCookie / Cookie-Editor) au format\n"
       . "   Netscape et placez-les dans : " . colored(COOKIE_FILE, 'cyan') . "\n"
       . "   puis relancez le script.\n";
    echo PHP_EOL;
}

main();