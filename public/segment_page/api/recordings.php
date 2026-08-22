<?php
/**
 * API Recent Recordings — TikCapture
 * Chemin : /segment_page/api/recordings.php
 *
 * GET  → retourne les 8 derniers enregistrements
 * POST → ajoute un enregistrement à l'historique
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
define('MAX_RECORDINGS', 14);
define('STORAGE_FILE',   __DIR__ . '/../data/recordings.json');
define('STORAGE_DIR',    __DIR__ . '/../data');
define('AVATAR_DIR',     __DIR__ . '/../data/avatars');
define('AVATAR_URL_PATH','segment_page/data/avatars');

// ─── Helpers ──────────────────────────────────────────────────────────────────
function respond(mixed $data, int $code = 200): never
{
    http_response_code($code);
    exit(json_encode($data, JSON_UNESCAPED_UNICODE));
}

function saveLocalAvatar(string $url, string $uniqueId): string
{
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) return $url;
    
    if (!is_dir(AVATAR_DIR)) {
        mkdir(AVATAR_DIR, 0755, true);
    }

    $filename  = 'avatar_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $uniqueId) . '_' . time() . '.webp';
    $filepath  = AVATAR_DIR . '/' . $filename;

    // Download image
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
        ]
    ]);
    
    $content = file_get_contents($url, false, $ctx);
    if ($content !== false) {
        $img = @imagecreatefromstring($content);
        if ($img) {
            // Force resize to a standard size (e.g., 200x200) to save bytes
            $resized = imagecreatetruecolor(160, 160);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, 160, 160, imagesx($img), imagesy($img));
            
            // Convert to WebP with good compression (80)
            imagewebp($resized, $filepath, 80);
            
            imagedestroy($img);
            imagedestroy($resized);
            
            return '/' . AVATAR_URL_PATH . '/' . $filename;
        } else {
            // Fallback: save as is if GD fails
            file_put_contents($filepath, $content);
            return '/' . AVATAR_URL_PATH . '/' . $filename;
        }
    }

    return $url; 
}

function readRecordings(): array
{
    if (!file_exists(STORAGE_FILE)) return [];
    $content = file_get_contents(STORAGE_FILE);
    $data    = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function writeRecordings(array $data): void
{
    if (!is_dir(STORAGE_DIR)) {
        mkdir(STORAGE_DIR, 0755, true);
    }
    file_put_contents(
        STORAGE_FILE,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

// ─── GET — lire les enregistrements récents ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    respond(['recordings' => readRecordings()]);
}

// ─── POST — ajouter un enregistrement ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (!$body || empty($body['uniqueId'])) {
        respond(['success' => false, 'message' => 'Données invalides.'], 400);
    }

    $newEntry = [
        'uniqueId'    => (string)($body['uniqueId']    ?? 'unknown'),
        'nickname'    => (string)($body['nickname']    ?? ''),
        'avatar'      => saveLocalAvatar((string)($body['avatar'] ?? ''), (string)($body['uniqueId'] ?? 'unknown')),
        'title'       => (string)($body['title']       ?? ''),
        'viewers'     => (int)   ($body['viewers']     ?? 0),
        'startTime'   => (string)($body['startTime']   ?? ''),
        'quality'     => (string)($body['quality']     ?? ''),
        'recordedAt'  => date('Y-m-d H:i:s'),
        'timestamp'   => time()
    ];

    $recordings = readRecordings();

    // Supprimer si déjà présent (doublon récent)
    $recordings = array_values(array_filter(
        $recordings,
        fn($r) => !($r['uniqueId'] === $newEntry['uniqueId'])
    ));

    // Ajouter en haut
    array_unshift($recordings, $newEntry);

    // Garder uniquement les 8 derniers
    $recordings = array_slice($recordings, 0, MAX_RECORDINGS);

    writeRecordings($recordings);

    respond(['success' => true, 'recordings' => $recordings]);
}

respond(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
