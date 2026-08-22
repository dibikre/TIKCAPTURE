<?php
$pageTitle = "Télécharger Vidéos TikTok sans filigrane - TikCapture";
$pageDesc = "Téléchargez des vidéos TikTok sans filigrane gratuitement et en haute qualité. Compatible mobile et PC, illimité et rapide.";
$pageImage = "https://tikcapture.live/images/og-image-video.jpg";
$pageUrl = "https://tikcapture.live/tiktok-video";

if (isset($videoData) && $videoData) {
    $authorName = htmlspecialchars($videoData['author_nickname'] ?: 'Utilisateur TikTok');
    $desc = isset($videoData['desc']) ? htmlspecialchars($videoData['desc']) : 'Vidéo TikTok';
    if (strlen($desc) > 150) $desc = substr($desc, 0, 147) . '...';
    
    $pageTitle = "Vidéo de $authorName - Télécharger sans filigrane | TikCapture";
    $pageDesc = "Regarder et télécharger la vidéo de $authorName : \"$desc\". Gratuit, HD et sans filigrane sur TikCapture.";
    if (!empty($videoData['cover'])) {
        $pageImage = $videoData['cover'];
    }
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?php echo $pageTitle; ?></title>
<meta name="description" content="<?php echo $pageDesc; ?>">
<meta name="keywords" content="télécharger tiktok, tiktok sans filigrane, tiktok downloader, video tiktok, mp4 tiktok, tiktok no watermark, télécharger video tiktok">
<meta name="author" content="TikCapture">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="googlebot" content="index, follow">
<link rel="canonical" href="<?php echo $pageUrl; ?>">
<meta http-equiv="content-language" content="fr">
<link rel="alternate" hreflang="fr" href="<?php echo $pageUrl; ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?php echo $pageUrl; ?>">
<meta property="og:site_name" content="TikCapture">
<meta property="og:title" content="<?php echo $pageTitle; ?>">
<meta property="og:description" content="<?php echo $pageDesc; ?>">
<meta property="og:image" content="<?php echo $pageImage; ?>">
<meta property="og:locale" content="fr_FR">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?php echo $pageUrl; ?>">
<meta name="twitter:title" content="<?php echo $pageTitle; ?>">
<meta name="twitter:description" content="<?php echo $pageDesc; ?>">
<meta name="twitter:image" content="<?php echo $pageImage; ?>">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon/favicon-16x16.png">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="/assets/favicon/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="192x192" href="/assets/favicon/android-icon-192x192.png">
<link rel="shortcut icon" href="/assets/favicon/favicon.ico">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-icon-180x180.png">
<meta name="apple-mobile-web-app-title" content="TikCapture">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="manifest" href="/assets/favicon/site.webmanifest">
<meta name="mobile-web-app-capable" content="yes">
<meta name="theme-color" content="#fe2c55">
<meta name="msapplication-TileColor" content="#fe2c55">
<meta name="msapplication-TileImage" content="/assets/favicon/ms-icon-144x144.png">

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "TikCapture - Téléchargeur TikTok",
  "url": "<?php echo $pageUrl; ?>",
  "applicationCategory": "MultimediaApplication",
  "operatingSystem": "Any",
  "description": "Téléchargez des vidéos TikTok sans filigrane gratuitement et en haute qualité.",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "EUR"
  },
  "featureList": [
    "Téléchargement sans filigrane",
    "Qualité HD",
    "Rapide et gratuit",
    "Compatible Mobile et PC"
  ]
}
</script>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>