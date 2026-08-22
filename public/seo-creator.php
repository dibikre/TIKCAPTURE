<?php
require_once __DIR__ . '/config/db.php';

$id = $_GET['id'] ?? '';
$title = $desc = $image = $url = '';

try {
    $stmt = $pdo->prepare("
        SELECT c.name, c.description, c.avatar_url, c.platform,
               COUNT(v.id) AS total
        FROM creators c LEFT JOIN videos v ON v.creator_id = c.id
        WHERE c.id = ? GROUP BY c.id LIMIT 1
    ");
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    if ($r) {
        $title = htmlspecialchars($r['name']);
        $desc  = htmlspecialchars($r['description']) ?: 'Regardez les lives enregistrés de ' . htmlspecialchars($r['name']) . ' sur ' . htmlspecialchars($r['platform']) . '. ' . $r['total'] . ' vidéo(s) disponible(s).';
        $image = 'https://tikcapture.live/files/' . $r['avatar_url'];
        $url   = 'https://tikcapture.live/createurs/' . urlencode($id);
    }
} catch (Throwable $e) {}

$title = $title ?: 'Créateur – TikCapture';
$desc  = $desc  ?: 'Profil créateur sur TikCapture.';
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
// sinon il sera affiché en texte brut dans le navigateur
$html = preg_replace('/<\?php.*?\?>/ms', '', $html);

$metas = '
<title>' . $title . ' – TikCapture</title>
<meta name="robots" content="index, follow">
<meta name="description" content="' . htmlspecialchars($desc) . '">
<link rel="canonical" href="' . $url . '">
<meta property="og:title" content="' . htmlspecialchars($title) . '">
<meta property="og:description" content="' . htmlspecialchars($desc) . '">
<meta property="og:image" content="' . $image . '">
<meta property="og:url" content="' . $url . '">
<meta property="og:type" content="profile">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="' . htmlspecialchars($title) . '">
<meta name="twitter:description" content="' . htmlspecialchars($desc) . '">
<meta name="twitter:image" content="' . $image . '">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Person",
  "name": "' . $title . '",
  "description": "' . $desc . '",
  "image": "' . $image . '",
  "url": "' . $url . '"
}
</script>
';
$html = preg_replace('/<title>[^<]*<\/title>/i', '', $html);
$html = preg_replace('/<meta name="description"[^>]*>/i', '', $html);
$html = preg_replace('/<meta property="og:[^"]*"[^>]*>/i', '', $html);
$html = preg_replace('/<meta name="twitter:[^"]*"[^>]*>/i', '', $html);
$html = preg_replace('/<link rel="canonical"[^>]*>/i', '', $html);
$html = preg_replace('/<meta name="robots"[^>]*>/i', '', $html);
$html = str_replace('</head>', $metas . '</head>', $html);
echo $html;