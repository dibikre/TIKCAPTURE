<?php
/**
 * indexnow-submit.php — Soumission en masse via IndexNow
 * Site : https://tikcapture.live
 *
 * php indexnow-submit.php static     # 13 pages statiques
 *  php indexnow-submit.php creators   # tous les créateurs
 * php indexnow-submit.php videos     # toutes les vidéos (par batches de 500)
 * php indexnow-submit.php blog       # tous les articles
 *
 * CONFIGURATION :
 *   1. Remplacez INDEXNOW_KEY par votre vraie clé
 *   2. Vérifiez que le fichier <clé>.txt est bien à la racine de votre site
 *   3. Exécutez depuis la racine du projet (pour que config/db.php soit accessible)
 */

// ═══════════════════════════════════════════════════════════════════════════════
// CONFIGURATION — À MODIFIER
// ═══════════════════════════════════════════════════════════════════════════════

define('SITE_URL',     'https://tikcapture.live');
define('INDEXNOW_KEY', '67817a2b9bca4170beadba9f0d2d924b');              // ← Remplacer
define('KEY_LOCATION', SITE_URL . '/' . INDEXNOW_KEY . '.txt');
define('INDEXNOW_API', 'https://api.indexnow.org/IndexNow');

define('URLS_PER_BATCH', 500);   // Max recommandé par requête
define('SLEEP_BETWEEN',  2);     // Secondes entre chaque batch (évite le 429)

// ═══════════════════════════════════════════════════════════════════════════════
// UTILITAIRES
// ═══════════════════════════════════════════════════════════════════════════════

function makeSlug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'video';
}

function log_msg(string $msg, string $level = 'INFO'): void {
    $ts = date('Y-m-d H:i:s');
    $line = "[{$ts}] [{$level}] {$msg}";
    echo $line . PHP_EOL;

    // Log aussi dans un fichier
    file_put_contents(
        __DIR__ . '/indexnow-submit.log',
        $line . PHP_EOL,
        FILE_APPEND
    );
}

/**
 * Envoie un batch d'URLs à IndexNow
 * Retourne le code HTTP de réponse
 */
function submit_batch(array $urls): int {
    if (empty($urls)) return 0;

    $payload = json_encode([
        'host'        => parse_url(SITE_URL, PHP_URL_HOST),
        'key'         => INDEXNOW_KEY,
        'keyLocation' => KEY_LOCATION,
        'urlList'     => array_values($urls),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $ch = curl_init(INDEXNOW_API);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        log_msg("cURL error: {$error}", 'ERROR');
        return 0;
    }

    return $httpCode;
}

/**
 * Soumet un tableau d'URLs par batches de URLS_PER_BATCH
 * et affiche un rapport pour chaque batch
 */
function submit_all(array $urls, string $label): void {
    $total   = count($urls);
    $batches = array_chunk($urls, URLS_PER_BATCH);
    $batchNb = count($batches);

    log_msg("── {$label} : {$total} URLs → {$batchNb} batch(es)");

    foreach ($batches as $i => $batch) {
        $n    = $i + 1;
        $size = count($batch);
        log_msg("   Batch {$n}/{$batchNb} ({$size} URLs) en cours…");

        $code = submit_batch($batch);
        $status = match($code) {
            200 => '✅ 200 OK — soumises avec succès',
            400 => '❌ 400 Bad Request — format invalide',
            403 => '❌ 403 Forbidden — clé invalide ou fichier clé introuvable',
            422 => '❌ 422 Unprocessable — URLs hors domaine ou clé incorrecte',
            429 => '⚠️  429 Too Many Requests — attendez avant de relancer',
            0   => '❌ Erreur réseau / cURL',
            default => "⚠️  HTTP {$code} — réponse inattendue",
        };

        log_msg("   → {$status}");

        // Pause entre les batches pour éviter le rate-limit
        if ($n < $batchNb) {
            log_msg("   Pause " . SLEEP_BETWEEN . "s…");
            sleep(SLEEP_BETWEEN);
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// COLLECTE DES URLs PAR TYPE
// ═══════════════════════════════════════════════════════════════════════════════

function get_static_urls(): array {
    $pages = [
        '/',
        '/tiktok-video',
        '/createurs',
        '/comment-enregistrer',
        '/comment-telecharger',
        '/blog',
        '/faq',
        '/tutoriels-video',
        '/contact',
        '/suggestion',
        '/cgu',
        '/cgv',
        '/mentions-legales',
    ];

    return array_map(fn($p) => SITE_URL . $p, $pages);
}

function get_creator_urls(PDO $pdo): array {
    try {
        $rows = $pdo->query("SELECT id FROM creators ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_COLUMN);
        return array_map(fn($id) => SITE_URL . '/createurs/' . rawurlencode($id), $rows);
    } catch (Throwable $e) {
        log_msg("Erreur creators: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

function get_video_urls(PDO $pdo): array {
    $urls = [];
    try {
        $stmt = $pdo->query("
            SELECT v.title, v.creator_id
            FROM videos v
            ORDER BY v.created_at DESC
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slug  = makeSlug($row['title']);
            $urls[] = SITE_URL . '/createurs/' . rawurlencode($row['creator_id']) . '/videos/' . $slug;
        }
    } catch (Throwable $e) {
        log_msg("Erreur videos: " . $e->getMessage(), 'ERROR');
    }
    return $urls;
}

function get_blog_urls(PDO $pdo): array {
    try {
        $rows = $pdo->query("SELECT slug FROM articles ORDER BY updated_at DESC")->fetchAll(PDO::FETCH_COLUMN);
        return array_map(fn($slug) => SITE_URL . '/blog/' . rawurlencode($slug), $rows);
    } catch (Throwable $e) {
        log_msg("Erreur blog: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// MAIN
// ═══════════════════════════════════════════════════════════════════════════════

// Vérification de la clé
if (INDEXNOW_KEY === 'VOTRE_CLE_ICI') {
    log_msg("⛔ INDEXNOW_KEY non configurée ! Modifiez la constante en haut du fichier.", 'ERROR');
    exit(1);
}

// Argument CLI pour choisir le type
$mode = $argv[1] ?? 'all';

log_msg("════════════════════════════════════════");
log_msg("IndexNow Bulk Submit — " . SITE_URL);
log_msg("Mode : {$mode}");
log_msg("Clé  : " . INDEXNOW_KEY);
log_msg("════════════════════════════════════════");

// Connexion DB (uniquement si nécessaire)
$needsDB = in_array($mode, ['all', 'creators', 'videos', 'blog']);

$pdo = null;
if ($needsDB) {
    try {
        require_once __DIR__ . '/config/db.php';
        // $pdo est supposé défini par config/db.php
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            throw new RuntimeException("Variable \$pdo non définie dans config/db.php");
        }
        log_msg("Connexion DB OK");
    } catch (Throwable $e) {
        log_msg("Connexion DB échouée: " . $e->getMessage(), 'ERROR');
        if ($mode !== 'static') exit(1);
    }
}

$startTime = microtime(true);
$totalSent = 0;

// ─── Exécution selon le mode ──────────────────────────────────────────────────

if (in_array($mode, ['all', 'static'])) {
    $urls = get_static_urls();
    submit_all($urls, 'Pages statiques');
    $totalSent += count($urls);
}

if (in_array($mode, ['all', 'creators']) && $pdo) {
    $urls = get_creator_urls($pdo);
    submit_all($urls, 'Créateurs');
    $totalSent += count($urls);
    if ($mode === 'all') sleep(SLEEP_BETWEEN);
}

if (in_array($mode, ['all', 'videos']) && $pdo) {
    $urls = get_video_urls($pdo);
    submit_all($urls, 'Vidéos');
    $totalSent += count($urls);
    if ($mode === 'all') sleep(SLEEP_BETWEEN);
}

if (in_array($mode, ['all', 'blog']) && $pdo) {
    $urls = get_blog_urls($pdo);
    submit_all($urls, 'Blog');
    $totalSent += count($urls);
}

// ─── Résumé final ─────────────────────────────────────────────────────────────

$elapsed = round(microtime(true) - $startTime, 2);
log_msg("════════════════════════════════════════");
log_msg("✅ Terminé en {$elapsed}s — {$totalSent} URLs soumises au total");
log_msg("   Vérifiez dans Bing Webmaster Tools :");
log_msg("   https://www.bing.com/webmasters");
log_msg("════════════════════════════════════════");