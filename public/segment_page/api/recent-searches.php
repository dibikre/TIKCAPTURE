<?php
/**
 * API Recent Searches — TikCapture
 * Chemin : /segment_page/api/recent-searches.php
 *
 * GET  → retourne les 5 dernières recherches { username, platform }
 * POST → ajoute { username, platform }, conserve uniquement les 5 dernières
 */
declare(strict_types=1);

// ─── CORS ─────────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Config ───────────────────────────────────────────────────────────────────
define('MAX_RECENT',   10);
define('STORAGE_FILE', __DIR__ . '/../data/recent-searches.json');
define('STORAGE_DIR',  __DIR__ . '/../data');

// ─── Plateformes autorisées ───────────────────────────────────────────────────
const ALLOWED_PLATFORMS = ['tiktok', 'twitch', 'kick', 'bigo', 'dlive'];

// ─── Helpers ──────────────────────────────────────────────────────────────────
function respond(mixed $data, int $code = 200): never
{
    http_response_code($code);
    exit(json_encode($data, JSON_UNESCAPED_UNICODE));
}

function readSearches(): array
{
    if (!file_exists(STORAGE_FILE)) return [];
    $content = file_get_contents(STORAGE_FILE);
    $data    = json_decode($content, true);
    if (!is_array($data)) return [];

    // Migration : ancien format string[] → nouveau format {username, platform}[]
    return array_values(array_map(function ($item) {
        if (is_string($item)) {
            return ['username' => $item, 'platform' => 'tiktok'];
        }
        return $item;
    }, $data));
}

function writeSearches(array $searches): void
{
    if (!is_dir(STORAGE_DIR)) {
        mkdir(STORAGE_DIR, 0755, true);
    }
    file_put_contents(
        STORAGE_FILE,
        json_encode($searches, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

function sanitizeUsername(string $input): string
{
    $clean = trim($input);
    $clean = ltrim($clean, '@');
    // Pour les URLs (autres plateformes) on autorise aussi : / : . ? = & % -
    $clean = preg_replace('/[^a-zA-Z0-9._\-\/:?=&%]/', '', $clean);
    return substr($clean, 0, 200);
}

function sanitizePlatform(string $input): string
{
    $clean = strtolower(trim($input));
    return in_array($clean, ALLOWED_PLATFORMS, true) ? $clean : 'tiktok';
}

// ─── GET — lire les recherches récentes ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    respond(['searches' => readSearches()]);
}

// ─── POST — ajouter une recherche ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    $username = sanitizeUsername($body['username'] ?? '');
    $platform = sanitizePlatform($body['platform'] ?? 'tiktok');

    if (empty($username)) {
        respond(['success' => false, 'message' => 'Username invalide.'], 400);
    }

    $searches = readSearches();

    // Supprime l'entrée existante avec le même username+platform (évite les doublons)
    $searches = array_values(array_filter(
        $searches,
        fn($entry) => !(
            (is_array($entry) ? $entry['username'] : $entry) === $username &&
            (is_array($entry) ? ($entry['platform'] ?? 'tiktok') : 'tiktok') === $platform
        )
    ));

    // Ajoute en tête de liste
    array_unshift($searches, ['username' => $username, 'platform' => $platform]);

    // Garde uniquement les MAX_RECENT derniers
    $searches = array_slice($searches, 0, MAX_RECENT);

    writeSearches($searches);

    respond(['success' => true, 'searches' => $searches]);
}

respond(['success' => false, 'message' => 'Méthode non autorisée.'], 405);