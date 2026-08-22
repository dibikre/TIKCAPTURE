<?php
require_once __DIR__ . '/../../config/db.php';

try {
    // Récupérer les articles publiés
    // On vérifie si la table existe d'abord pour éviter une erreur fatale si l'installation a échoué
    $stmt = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC");
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    $articles = [];
    // On pourrait logger l'erreur ici
}
?>

<section class="container" style="padding-top: 4rem; padding-bottom: 4rem;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; margin-bottom: 1rem; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Blog TikCapture</h1>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">Retrouvez nos derniers guides, tutoriels et actualités pour maîtriser l'enregistrement de lives TikTok.</p>
    </div>

    <div class="blog-grid">
        <?php if (empty($articles)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 4rem; background: var(--bg-card); border-radius: 12px;">
                <i class="fas fa-inbox fa-3x" style="color: var(--text-muted); margin-bottom: 1rem;"></i>
                <p>Aucun article n'a été publié pour le moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($articles as $article): ?>
                <article class="blog-card">
                    <a href="/blog/<?= htmlspecialchars($article['slug']) ?>" style="text-decoration: none; color: inherit;">
                        <?php if (!empty($article['image_url'])): ?>
                            <img src="<?= htmlspecialchars($article['image_url']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="blog-thumbnail">
                        <?php else: ?>
                            <div class="blog-thumbnail" style="background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-newspaper fa-3x" style="color: var(--text-muted);"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="blog-content">
                            <h2 class="blog-title"><?= htmlspecialchars($article['title']) ?></h2>
                            <p class="blog-excerpt">
                                <?php 
                                    $excerpt = !empty($article['excerpt']) ? $article['excerpt'] : strip_tags($article['content']);
                                    echo htmlspecialchars(substr($excerpt, 0, 120)) . (strlen($excerpt) > 120 ? '...' : ''); 
                                ?>
                            </p>
                            <div class="blog-meta">
                                <span class="blog-date"><i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($article['created_at'])) ?></span>
                                <span class="read-more">Lire la suite →</span>
                            </div>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
