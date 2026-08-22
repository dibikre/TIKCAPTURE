<?php
/**
 * sitemap.php — Sitemap dynamique avec index
 * Remplace sitemap.xml — à placer dans public/
 *
 * Génère automatiquement :
 *   ?type=index        → sitemap index (liste tous les sitemaps)
 *   ?type=static       → pages statiques du site
 *   ?type=creators     → profils créateurs
 *   ?type=videos&p=1   → vidéos paginées (500 par fichier max)
 *   ?type=blog         → articles de blog
 *
 * Limite Google : 50 000 URLs / fichier et 50 Mo non compressé
 * On utilise 500 URLs/fichier pour rester léger et rapide
 */

define('SITE_URL',    'https://tikcapture.live');
define('URLS_PER_PAGE', 500);

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

require_once __DIR__ . '/config/db.php';

function makeSlug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'video';
}

$type = $_GET['type'] ?? 'index';
$page = max(1, (int)($_GET['p'] ?? 1));

// ─── Helpers XML ──────────────────────────────────────────────────────────────

function xml_url(string $loc, string $lastmod = '', string $changefreq = 'weekly', string $priority = '0.5'): string
{
    $out  = "  <url>\n";
    $out .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    if ($lastmod) {
        $out .= "    <lastmod>" . htmlspecialchars($lastmod) . "</lastmod>\n";
    }
    $out .= "    <changefreq>{$changefreq}</changefreq>\n";
    $out .= "    <priority>{$priority}</priority>\n";
    $out .= "  </url>\n";
    return $out;
}

function xml_sitemap_entry(string $loc, string $lastmod = ''): string
{
    $out  = "  <sitemap>\n";
    $out .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    if ($lastmod) {
        $out .= "    <lastmod>" . htmlspecialchars($lastmod) . "</lastmod>\n";
    }
    $out .= "  </sitemap>\n";
    return $out;
}

function today(): string
{
    return date('Y-m-d');
}

// ═══════════════════════════════════════════════════════════════════════════════
// INDEX
// ═══════════════════════════════════════════════════════════════════════════════

if ($type === 'index') {

    // Compter les vidéos pour calculer le nombre de pages
    try {
        $totalVideos = (int)$pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();
    } catch (Throwable $e) {
        $totalVideos = 0;
    }

    $videoPages = max(1, (int)ceil($totalVideos / URLS_PER_PAGE));

    // Dernière modif créateurs
    try {
        $lastCreator = $pdo->query("SELECT MAX(updated_at) FROM creators")->fetchColumn();
        $lastCreator = $lastCreator ? date('Y-m-d', strtotime($lastCreator)) : today();
    } catch (Throwable $e) {
        $lastCreator = today();
    }

    // Dernière modif vidéos
    try {
        $lastVideo = $pdo->query("SELECT MAX(created_at) FROM videos")->fetchColumn();
        $lastVideo = $lastVideo ? date('Y-m-d', strtotime($lastVideo)) : today();
    } catch (Throwable $e) {
        $lastVideo = today();
    }

    // Dernière modif blog
    try {
        $lastBlog = $pdo->query("SELECT MAX(updated_at) FROM articles")->fetchColumn();
        $lastBlog = $lastBlog ? date('Y-m-d', strtotime($lastBlog)) : today();
    } catch (Throwable $e) {
        $lastBlog = today();
    }

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    echo xml_sitemap_entry(SITE_URL . '/sitemap.php?type=static',   today());
    echo xml_sitemap_entry(SITE_URL . '/sitemap.php?type=blog',     $lastBlog);
    echo xml_sitemap_entry(SITE_URL . '/sitemap.php?type=creators', $lastCreator);

    for ($p = 1; $p <= $videoPages; $p++) {
        echo xml_sitemap_entry(SITE_URL . '/sitemap.php?type=videos&p=' . $p, $lastVideo);
    }

    echo '</sitemapindex>';
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// PAGES STATIQUES
// ═══════════════════════════════════════════════════════════════════════════════

if ($type === 'static') {
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    $staticPages = [
        ['/',                    '1.0',  'weekly',  today()],
        ['/tiktok-video',        '0.9',  'weekly',  today()],
        ['/createurs',             '0.9',  'daily',   today()],
        ['/comment-enregistrer', '0.8',  'monthly', ''],
        ['/comment-telecharger', '0.8',  'monthly', ''],
        ['/blog',                '0.8',  'weekly',  today()],
        ['/faq',                 '0.7',  'monthly', ''],
        ['/tutoriels-video',     '0.7',  'monthly', ''],
        ['/contact',             '0.5',  'yearly',  ''],
        ['/suggestion',          '0.4',  'yearly',  ''],
        ['/cgu',                 '0.3',  'yearly',  ''],
        ['/cgv',                 '0.3',  'yearly',  ''],
        ['/mentions-legales',    '0.3',  'yearly',  ''],
    ];

    foreach ($staticPages as [$path, $priority, $changefreq, $lastmod]) {
        echo xml_url(SITE_URL . $path, $lastmod, $changefreq, $priority);
    }

    echo '</urlset>';
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// CRÉATEURS
// ═══════════════════════════════════════════════════════════════════════════════

function xml_image_url(string $loc, string $image_loc, string $title = '', string $lastmod = ''): string
{
    $out  = "  <url>\n";
    $out .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    if ($lastmod) {
        $out .= "    <lastmod>" . htmlspecialchars($lastmod) . "</lastmod>\n";
    }
    $out .= "    <changefreq>weekly</changefreq>\n";
    $out .= "    <priority>0.8</priority>\n";
    $out .= "    <image:image>\n";
    $out .= "      <image:loc>" . htmlspecialchars($image_loc) . "</image:loc>\n";
    if ($title) $out .= "      <image:title>" . htmlspecialchars($title) . "</image:title>\n";
    $out .= "    </image:image>\n";
    $out .= "  </url>\n";
    return $out;
}

if ($type === 'creators') {
    try {
        $rows = $pdo->query("
            SELECT id, name, avatar_url, updated_at
            FROM creators
            ORDER BY updated_at DESC
        ")->fetchAll();
    } catch (Throwable $e) {
        $rows = [];
    }

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

    foreach ($rows as $row) {
        $lastmod = $row['updated_at']
            ? date('Y-m-d', strtotime($row['updated_at']))
            : today();
        $avatar = 'https://tikcapture.live/files/' . $row['avatar_url'];
        echo xml_image_url(
            SITE_URL . '/createurs/' . rawurlencode($row['id']),
            $avatar,
            $row['name'],
            $lastmod
        );
    }

    echo '</urlset>';
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// VIDÉOS — paginées
// ═══════════════════════════════════════════════════════════════════════════════

function xml_video_url(string $loc, array $video, string $lastmod = ''): string
{
    $out  = "  <url>\n";
    $out .= "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    if ($lastmod) {
        $out .= "    <lastmod>" . htmlspecialchars($lastmod) . "</lastmod>\n";
    }
    $out .= "    <changefreq>monthly</changefreq>\n";
    $out .= "    <priority>0.7</priority>\n";
    $out .= "    <video:video>\n";
    $out .= "      <video:thumbnail_loc>" . htmlspecialchars($video['thumbnail_url']) . "</video:thumbnail_loc>\n";
    $out .= "      <video:title>" . htmlspecialchars($video['title']) . "</video:title>\n";
    $out .= "      <video:description>" . htmlspecialchars($video['description'] ?: $video['title']) . "</video:description>\n";
    if (!empty($video['duration'])) {
        $parts = array_reverse(explode(':', $video['duration']));
        $sec = 0;
        if (isset($parts[0])) $sec += (int)$parts[0];
        if (isset($parts[1])) $sec += (int)$parts[1] * 60;
        if (isset($parts[2])) $sec += (int)$parts[2] * 3600;
        if ($sec > 0) {
            $out .= "      <video:duration>{$sec}</video:duration>\n";
        }
    }
    $out .= "      <video:publication_date>" . date('c', strtotime($video['created_at'])) . "</video:publication_date>\n";
    $out .= "      <video:family_friendly>yes</video:family_friendly>\n";
    $out .= "    </video:video>\n";
    $out .= "  </url>\n";
    return $out;
}

if ($type === 'videos') {
    $offset = ($page - 1) * URLS_PER_PAGE;

    try {
        $stmt = $pdo->prepare("
            SELECT v.id, v.title, v.description, v.thumbnail_url, v.duration, v.creator_id, v.created_at
            FROM videos v
            ORDER BY v.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit',  URLS_PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,        PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        $rows = [];
    }

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

    foreach ($rows as $row) {
        $lastmod = $row['created_at']
            ? date('Y-m-d', strtotime($row['created_at']))
            : today();
        $slug = makeSlug($row['title']);
        $videoData = [
            'title' => $row['title'],
            'description' => $row['description'],
            'thumbnail_url' => 'https://tikcapture.live/files/' . $row['thumbnail_url'],
            'duration' => $row['duration'],
            'created_at' => $row['created_at']
        ];
        echo xml_video_url(
            SITE_URL . '/createurs/' . rawurlencode($row['creator_id']) . '/videos/' . $slug,
            $videoData,
            $lastmod
        );
    }

    echo '</urlset>';
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
// BLOG
// ═══════════════════════════════════════════════════════════════════════════════

if ($type === 'blog') {
    try {
        $rows = $pdo->query("
            SELECT slug, updated_at
            FROM articles
            ORDER BY updated_at DESC
        ")->fetchAll();
    } catch (Throwable $e) {
        $rows = [];
    }

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($rows as $row) {
        $lastmod = $row['updated_at']
            ? date('Y-m-d', strtotime($row['updated_at']))
            : today();
        echo xml_url(
            SITE_URL . '/blog/' . rawurlencode($row['slug']),
            $lastmod,
            'monthly',
            '0.6'
        );
    }

    echo '</urlset>';
    exit;
}

// ─── Fallback ─────────────────────────────────────────────────────────────────
http_response_code(404);
echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>';