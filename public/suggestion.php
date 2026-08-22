<?php
$page_title = 'Suggestions - TikCapture';
$page_desc = 'Proposez vos idées et suggestions pour améliorer TikCapture. Aidez-nous à développer de nouvelles fonctionnalités pour l\'enregistrement de lives TikTok.';
$page_url = 'https://tikcapture.live/suggestion';
$page_og_title = 'Suggestions - TikCapture';
$page_og_desc = 'Vos idées comptent ! Proposez des améliorations pour TikCapture et participez à l\'évolution du service.';
$page_keywords = 'suggestions, idées, améliorations, fonctionnalités, propositions tikcapture, feedback';
$page_og_image = 'https://tikcapture.live/images/og-image.jpg';
$page_twitter_title = 'Suggestions - TikCapture';
$page_twitter_desc = 'Proposez vos idées pour améliorer TikCapture. Votre feedback est précieux.';
$page_twitter_image = 'https://tikcapture.live/images/twitter-card.jpg';
$page_schema_type = 'WebPage';
$page_schema_name = 'Suggestions - TikCapture';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__. '/segment_page/all/meta.php'; ?>
    <title>Suggestions - TikCapture</title>
    <style>
        <?php include __DIR__. '/segment_page/index/style_index.php'; ?>
        .page-container {
            min-height: 60vh;
            padding: 4rem 1rem;
            max-width: 1200px;
            margin: 0 auto;
            color: var(--text-primary);
        }
        .page-title {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-stars">
            <div id="stars"></div>
        </div>
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon"><img src="https://tikcapture.live/assets/images/favicon.png" alt="TikCapture" style="width:100%;height:100%;object-fit:contain;border-radius:inherit" /></div>
                <span class="logo-text">TikCapture</span>
            </div>
            
            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <nav class="header-nav">
                <button class="b-menu" onclick="window.location.href='/'">
                    <span>ACCUEIL</span>
                </button>
                <button class="b-menu" onclick="window.location.href='tiktok-video'">
                    <span>Télécharger Vidéos</span>
                </button>
                <button class="b-menu" onclick="window.location.href='comment-enregistrer'">
                    <span>Comment Enregistrer</span>
                </button>
                <button class="b-menu" onclick="window.location.href='comment-telecharger'">
                    <span>Comment Télécharger</span>
                </button>
                <button class="b-menu" onclick="window.location.href='blog'">
                    <span>Blog</span>
                </button>
            </nav>
            
            <div class="header-status">
                <span class="status-dot"></span>
                <span>fonctionnel</span>
            </div>
        </div>
        
        <div class="mobile-dropdown" id="mobileDropdown">
            <div class="mobile-dropdown-content">
                <button class="b-menu" onclick="window.location.href='/'">
                    <span>ACCUEIL</span>
                </button>
                <button class="b-menu" onclick="window.location.href='tiktok-video'">
                    <span>Télécharger Vidéos</span>
                </button>
                <button class="b-menu" onclick="window.location.href='comment-enregistrer'">
                    <span>Comment Enregistrer</span>
                </button>
                <button class="b-menu" onclick="window.location.href='comment-telecharger'">
                    <span>Comment Télécharger</span>
                </button>
                <button class="b-menu" onclick="window.location.href='blog'">
                    <span>Blog</span>
                </button>
            </div>
        </div>
    </header>

    <main class="page-container">
        <h1 class="page-title">Vos Suggestions</h1>
        <!-- Contenu vide comme demandé -->
    </main>

    <?php include __DIR__. '/segment_page/footer.php'; ?>

    <script>
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mobileDropdown = document.getElementById('mobileDropdown');
        if(mobileMenuToggle && mobileDropdown){
            mobileMenuToggle.addEventListener('click', () => {
                mobileDropdown.classList.toggle('active');
                mobileMenuToggle.classList.toggle('active');
            });
        }
    </script>
</body>
</html>
