<?php
require_once __DIR__ . '/config/db.php';

$actorId = $_GET['actor'] ?? '';
$videoId = $_GET['video'] ?? '';

function makeSlug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'video';
}

$title = $desc = $image = $url = $tags = '';

try {
    // Si video_id ressemble à un slug (contient des tirets), chercher par titre
    $resolvedVideoId = $videoId;
    if (str_contains($videoId, '-')) {
        $slugStmt = $pdo->prepare("SELECT id, title FROM videos WHERE creator_id = ?");
        $slugStmt->execute([$actorId]);
        foreach ($slugStmt->fetchAll() as $v) {
            if (makeSlug($v['title']) === $videoId) {
                $resolvedVideoId = $v['id'];
                break;
            }
        }
    }

    $stmt = $pdo->prepare("
        SELECT v.title, v.description, v.thumbnail_url, v.duration,
               COALESCE(v.tags, '') AS tags,
               c.name AS cname, c.id AS cid
        FROM videos v JOIN creators c ON c.id = v.creator_id
        WHERE v.id = ? AND c.id = ? LIMIT 1
    ");
    $stmt->execute([$resolvedVideoId, $actorId]);
    $r = $stmt->fetch();
    if ($r) {
        $title = htmlspecialchars($r['title']);
        $desc  = htmlspecialchars($r['description']) ?: 'Live de ' . htmlspecialchars($r['cname']) . ' — Durée : ' . htmlspecialchars($r['duration']) . '. Replay sur TikCapture.';
        $image = 'https://tikcapture.live/files/' . $r['thumbnail_url'];
        $slug  = makeSlug($r['title']);
        $url   = 'https://tikcapture.live/createurs/' . urlencode($actorId) . '/videos/' . $slug;
        $tags  = !empty($r['tags']) ? htmlspecialchars($r['tags']) : '';
    }
} catch (Throwable $e) {}

$title = $title ?: 'Vidéo Live – TikCapture';
$desc  = $desc  ?: 'Regardez ce live en replay sur TikCapture.';
$image = $image ?: 'https://tikcapture.live/images/og-image.jpg';
$url   = $url   ?: 'https://tikcapture.live/createurs';

// Tentative de lecture du template (index.html ou index.php en fallback)
$templatePath = __DIR__ . '/index.html';
if (!file_exists($templatePath)) {
    $templatePath = __DIR__ . '/index.php';
}

if (!file_exists($templatePath)) {
    die("Erreur : Template de base introuvable. Veuillez lancer un build.");
}

$html = file_get_contents($templatePath);

// Si on utilise l'index.php comme template, on DOIT enlever le bloc PHP SSR du haut
$html = preg_replace('/<\?php.*?\?>/ms', '', $html);

// Injecter les bonnes meta
$metas = '
<title>' . $title . '</title>
<meta name="description" content="' . htmlspecialchars($desc) . '">
<link rel="canonical" href="' . $url . '">
<meta property="og:title" content="' . htmlspecialchars($title) . '">
<meta property="og:description" content="' . htmlspecialchars($desc) . '">
<meta property="og:image" content="' . $image . '">
<meta property="og:url" content="' . $url . '">
<meta property="og:type" content="video.other">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="' . htmlspecialchars($title) . '">
<meta name="twitter:description" content="' . htmlspecialchars($desc) . '">
<meta name="twitter:image" content="' . $image . '">
' . ($tags ? '<meta name="keywords" content="' . $tags . '">' : '') . '
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": "' . $title . '",
  "description": "' . $desc . '",
  "thumbnailUrl": "' . $image . '",
  "uploadDate": "' . ($r ? date("c", strtotime($r["created_at"])) : date("c")) . '",
  "author": {
    "@type": "Person",
    "name": "' . ($r ? htmlspecialchars($r["cname"]) : "Créateur") . '"
  }
}
</script>
';

// 1. Supprimer les meta existantes de index.html D'ABORD
$html = preg_replace('/<title>[^<]*<\/title>/i', '', $html);
$html = preg_replace('/<meta name="description"[^>]*>/i', '', $html);
$html = preg_replace('/<meta name="keywords"[^>]*>/i', '', $html);
$html = preg_replace('/<meta property="og:[^"]*"[^>]*>/i', '', $html);
$html = preg_replace('/<meta name="twitter:[^"]*"[^>]*>/i', '', $html);
$html = preg_replace('/<link rel="canonical"[^>]*>/i', '', $html);

// 2. Injecter les nouvelles meta ENSUITE
$html = str_replace('</head>', $metas . '</head>', $html);

echo $html;