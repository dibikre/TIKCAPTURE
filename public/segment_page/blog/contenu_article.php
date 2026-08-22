<?php
require_once __DIR__ . '/../../config/db.php';

$slug = $_GET['slug'] ?? '';
$article = null;

if ($slug) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE slug = ?");
        $stmt->execute([$slug]);
        $article = $stmt->fetch();
    } catch (PDOException $e) {
        // En cas d'erreur DB
    }
}
?>

<section class="container" style="padding-top: 4rem; padding-bottom: 4rem;">
    <?php if ($article): ?>
        <article class="blog-post" style="max-width: 800px; margin: 0 auto;">
            <a href="/blog" style="display: inline-block; margin-bottom: 2rem; color: var(--accent-primary); text-decoration: none; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Retour au blog
            </a>
            
            <header style="margin-bottom: 2rem;">
                <h1 style="font-size: clamp(2rem, 5vw, 3rem); margin-bottom: 1rem; color: var(--text-primary); line-height: 1.2;"><?= htmlspecialchars($article['title']) ?></h1>
                
                <div style="display: flex; align-items: center; gap: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                    <span><i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($article['created_at'])) ?></span>
                    <?php if (!empty($article['updated_at']) && $article['updated_at'] > $article['created_at']): ?>
                        <span>(Mis à jour le <?= date('d/m/Y', strtotime($article['updated_at'])) ?>)</span>
                    <?php endif; ?>
                </div>
            </header>

            <?php if (!empty($article['image_url'])): ?>
                <div style="margin-bottom: 2rem; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow);">
                    <img src="<?= htmlspecialchars($article['image_url']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" style="width: 100%; height: auto; display: block;">
                </div>
            <?php endif; ?>

            <div class="article-content" style="font-size: 1.1rem; line-height: 1.8; color: var(--text-secondary);">
                <?php 
                // ATTENTION : On suppose ici que le contenu venant de l'API (via l'app Python authentifiée) est de confiance (HTML).
                // Si le contenu est du Markdown, il faudrait le parser ici.
                echo $article['content']; 
                ?>
            </div>
        </article>
    <?php else: ?>
        <div style="text-align: center; padding: 4rem; background: var(--bg-card); border-radius: 12px;">
            <h2 style="margin-bottom: 1rem;">Article introuvable</h2>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">L'article que vous recherchez n'existe pas ou a été supprimé.</p>
            <a href="/blog" class="btn-accent" style="text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 8px;">Retour au blog</a>
        </div>
    <?php endif; ?>
</section>

<style>
    /* Styles spécifiques pour le contenu de l'article */
    .article-content h2 { color: var(--text-primary); margin-top: 2rem; margin-bottom: 1rem; }
    .article-content h3 { color: var(--text-primary); margin-top: 1.5rem; margin-bottom: 1rem; }
    .article-content p { margin-bottom: 1.5rem; }
    .article-content ul, .article-content ol { margin-bottom: 1.5rem; padding-left: 2rem; }
    .article-content li { margin-bottom: 0.5rem; }
    .article-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 1rem 0; }
    .article-content a { color: var(--accent-primary); text-decoration: underline; }
    .article-content blockquote { border-left: 4px solid var(--accent-primary); padding-left: 1rem; font-style: italic; color: var(--text-muted); margin: 1.5rem 0; }
</style>
