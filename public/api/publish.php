<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/api_secret.php';

// Headers CORS et JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Helper to get headers if getallheaders() is missing (nginx/fpm sometimes)
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Vérification de la clé API (Bearer Token)
$headers = getallheaders();
$authHeader = '';

// Case insensitive header search
foreach ($headers as $key => $value) {
    if (strtolower($key) === 'authorization') {
        $authHeader = $value;
        break;
    }
}

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Authorization header missing or invalid']);
    exit;
}

$token = $matches[1];

if ($token !== $API_SECRET) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid API Key']);
    exit;
}

// Récupération des données
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Validation basique
$title = $input['title'] ?? null;
$content = $input['content'] ?? null;
$excerpt = $input['excerpt'] ?? '';
$imageUrl = $input['image_url'] ?? '';

if (!$title || !$content) {
    http_response_code(400);
    echo json_encode(['error' => 'Title and content are required']);
    exit;
}

// Génération du slug
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}

$slug = slugify($title);

try {
    // Vérifier si le slug existe
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
        $slug .= '-' . time();
    }

    // Insertion
    $stmt = $pdo->prepare("INSERT INTO articles (title, slug, excerpt, content, image_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $slug, $excerpt, $content, $imageUrl]);

    $id = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'message' => 'Article published successfully',
        'id' => $id,
        'slug' => $slug,
        'url' => "/blog/$slug"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
