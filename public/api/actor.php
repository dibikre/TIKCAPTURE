<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/file_path_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$id = $_GET['id'] ?? '';
if ($id === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing id']);
    exit;
}

try {
    // Fonction pour générer un slug depuis un titre
    function makeSlug(string $text): string {
        $text = mb_strtolower($text, 'UTF-8');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'video';
    }

    $creatorStmt = $pdo->prepare("
        SELECT
            c.id,
            c.name,
            c.profile_name AS profileName,
            c.platform,
            c.avatar_url AS avatar,
            c.cover_url AS coverImage,
            c.description
        FROM creators c
        WHERE c.id = ?
        LIMIT 1
    ");
    $creatorStmt->execute([$id]);
    $creator = $creatorStmt->fetch();

    if (!$creator) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Actor not found']);
        exit;
    }

    $videosStmt = $pdo->prepare("
        SELECT
            v.id,
            v.title,
            v.thumbnail_url AS thumbnail,
            v.sprite_url,
            v.duration,
            v.views
        FROM videos v
        WHERE v.creator_id = ?
        ORDER BY v.created_at DESC
    ");
    $videosStmt->execute([$id]);
    $videos = $videosStmt->fetchAll();
    $videos = array_map(function ($video) {
        $video['thumbnail'] = to_public_asset_path($video['thumbnail']);
        $video['sprite_url'] = to_public_asset_path($video['sprite_url'] ?? '');
        $video['slug'] = makeSlug($video['title']);
        return $video;
    }, $videos);

    $creator['avatar'] = to_public_asset_path($creator['avatar']);
    $creator['coverImage'] = to_public_asset_path($creator['coverImage']);
    $creator['videos'] = $videos;
    $creator['totalVideos'] = count($videos);

    echo json_encode(['status' => 'success', 'data' => $creator], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>