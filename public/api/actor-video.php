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

$actorId = $_GET['actor_id'] ?? '';
$videoId = $_GET['video_id'] ?? '';
if ($actorId === '' || $videoId === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing actor_id or video_id']);
    exit;
}

try {
    function makeSlug(string $text): string {
        $text = mb_strtolower($text, 'UTF-8');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'video';
    }

    // Résoudre video_id depuis slug si nécessaire
    $resolvedVideoId = $videoId;
    if (!preg_match('/^[a-z0-9]{8,}$/', $videoId)) {
        // C'est un slug — chercher la vidéo correspondante
        $slugStmt = $pdo->prepare("SELECT id, title FROM videos WHERE creator_id = ?");
        $slugStmt->execute([$actorId]);
        $allVideos = $slugStmt->fetchAll();
        foreach ($allVideos as $v) {
            if (makeSlug($v['title']) === $videoId) {
                $resolvedVideoId = $v['id'];
                break;
            }
        }
    }

    $mainStmt = $pdo->prepare("
        SELECT
            c.id AS actor_id,
            c.name,
            c.profile_name AS profileName,
            c.platform,
            c.avatar_url AS avatar,
            c.cover_url AS coverImage,
            c.description AS actorDescription,
            v.id AS video_id,
            v.title,
            v.thumbnail_url AS thumbnail,
            v.video_url AS videoUrl,
            v.duration,
            v.views,
            v.description,
            v.transcript,
            v.sprite_url,
            v.demo_url,
            v.doodstream_embed
        FROM creators c
        INNER JOIN videos v ON v.creator_id = c.id
        WHERE c.id = ? AND v.id = ?
        LIMIT 1
    ");
    $mainStmt->execute([$actorId, $resolvedVideoId]);
    $row = $mainStmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Video not found']);
        exit;
    }

    $tasks = [];
    try {
        $tasksStmt = $pdo->prepare("
            SELECT timecode AS time, label
            FROM video_playback_tasks
            WHERE video_id = ?
            ORDER BY position ASC, id ASC
        ");
        $tasksStmt->execute([$videoId]);
        $tasks = $tasksStmt->fetchAll();
    } catch (Throwable $_) {}

    $actorTotalStmt = $pdo->prepare("SELECT COUNT(*) FROM videos WHERE creator_id = ?");
    $actorTotalStmt->execute([$actorId]);
    $actorTotalVideos = (int) $actorTotalStmt->fetchColumn();

    $relatedVideosStmt = $pdo->prepare("
        SELECT
            c.id AS actorId,
            c.name AS actorName,
            v.id,
            v.title,
            v.thumbnail_url AS thumbnail,
            v.duration,
            v.views
        FROM videos v
        INNER JOIN creators c ON c.id = v.creator_id
        WHERE v.id <> ?
        ORDER BY v.created_at DESC
        LIMIT 6
    ");
    $relatedVideosStmt->execute([$videoId]);
    $relatedVideosRows = $relatedVideosStmt->fetchAll();
    $relatedVideos = array_map(function ($item) {
        return [
            'actorId' => $item['actorId'],
            'actorName' => $item['actorName'],
            'video' => [
                'id' => $item['id'],
                'slug' => makeSlug($item['title']),
                'title' => $item['title'],
                'thumbnail' => to_public_asset_path($item['thumbnail']),
                'duration' => $item['duration'],
                'views' => $item['views']
            ]
        ];
    }, $relatedVideosRows);

    $relatedCreatorsStmt = $pdo->prepare("
        SELECT
            c.id,
            c.name,
            c.profile_name AS profileName,
            c.platform,
            c.avatar_url AS avatar,
            c.cover_url AS coverImage,
            c.description,
            COUNT(v.id) AS totalVideos
        FROM creators c
        LEFT JOIN videos v ON v.creator_id = c.id
        WHERE c.id <> ?
        GROUP BY c.id, c.name, c.profile_name, c.platform, c.avatar_url, c.cover_url, c.description
        ORDER BY c.created_at DESC
        LIMIT 3
    ");
    $relatedCreatorsStmt->execute([$actorId]);
    $relatedCreators = $relatedCreatorsStmt->fetchAll();
    $relatedCreators = array_map(function ($creator) {
        $creator['avatar'] = to_public_asset_path($creator['avatar']);
        $creator['coverImage'] = to_public_asset_path($creator['coverImage']);
        return $creator;
    }, $relatedCreators);

    $response = [
        'actor' => [
            'id' => $row['actor_id'],
            'name' => $row['name'],
            'profileName' => $row['profileName'],
            'platform' => $row['platform'],
            'avatar' => to_public_asset_path($row['avatar']),
            'coverImage' => to_public_asset_path($row['coverImage']),
            'description' => $row['actorDescription'],
            'totalVideos' => $actorTotalVideos,
            'videos' => []
        ],
        'video' => [
            'id' => $row['video_id'],
            'slug' => makeSlug($row['title']),
            'title' => $row['title'],
            'thumbnail' => to_public_asset_path($row['thumbnail']),
            'videoUrl' => to_public_asset_path($row['demo_url']),
            'duration' => $row['duration'],
            'views' => $row['views'],
            'description' => $row['description'],
            'transcript' => $row['transcript'],
            'doodstream_embed' => $row['doodstream_embed'] ?? null
        ],
        'playbackTasks' => $tasks,
        'sprite' => [
            'imageUrl' => to_public_asset_path($row['sprite_url'] ?: $row['thumbnail']),
            'columns'  => 6,
            'rows'     => 6
        ],
        'relatedVideos' => $relatedVideos,
        'relatedCreators' => $relatedCreators
    ];

    echo json_encode(['status' => 'success', 'data' => $response], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>