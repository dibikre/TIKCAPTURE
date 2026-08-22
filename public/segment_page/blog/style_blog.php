<?php include __DIR__ . '/../index/style_index.php'; ?>

.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    padding: 2rem 0;
}
.blog-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.blog-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-glow);
    border-color: var(--accent-primary);
}
.blog-thumbnail {
    width: 100%;
    height: 200px;
    object-fit: cover;
}
.blog-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    height: calc(100% - 200px);
}
.blog-title {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
    font-weight: 700;
}
.blog-excerpt {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 1rem;
    flex-grow: 1;
}
.blog-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    font-size: 0.8rem;
    color: var(--text-muted);
}
.read-more {
    display: inline-block;
    color: var(--accent-primary);
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s;
}
.read-more:hover {
    color: var(--text-primary);
}
