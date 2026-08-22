<?php
/**
 * TikCapture — API Blog
 * GET /segment_page/api/blog-api.php          → liste des articles
 * GET /segment_page/api/blog-api.php?slug=xxx → article par slug
 */

// ─── CORS ─────────────────────────────────────────────────────────────────────

$allowed_origins = [
    'https://tikcapture.live',
    'https://www.tikcapture.live',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://localhost:4000',
    'http://localhost:45678',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
header('Access-Control-Allow-Origin: ' . (in_array($origin, $allowed_origins) ? $origin : 'https://tikcapture.live'));
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

// ─── DB ───────────────────────────────────────────────────────────────────────

$host     = 'localhost';
$dbname   = 'rmwylxiw_cl';
$username = 'rmwylxiw_cl';
$password = '46966222Aa@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données']);
    exit();
}

// ─── Route : article par slug ─────────────────────────────────────────────────

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if ($slug) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $article = $stmt->fetch();

        if (!$article) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Article introuvable']);
            exit();
        }

        echo json_encode(['success' => true, 'article' => $article], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération de l\'article']);
    }
    exit();
}

// ─── Route : liste des articles ───────────────────────────────────────────────

try {
    $stmt = $pdo->query("SELECT id, title, slug, excerpt, content, image_url, created_at, updated_at FROM articles ORDER BY created_at DESC");
    $articles = $stmt->fetchAll();

    // Générer un extrait si absent
    foreach ($articles as &$a) {
        if (empty($a['excerpt'])) {
            $a['excerpt'] = mb_substr(strip_tags($a['content']), 0, 150);
        }
    }

    echo json_encode(['success' => true, 'articles' => $articles], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération des articles']);
}