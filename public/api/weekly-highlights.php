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

/**
 * Calcule le lundi de la semaine courante à 00:00:00 UTC.
 * La semaine ne se "recalcule" qu'au prochain lundi.
 */
function getWeekStart(): string {
    $now  = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $dow  = (int)$now->format('N'); // 1=Lundi … 7=Dimanche
    $monday = $now->modify('-' . ($dow - 1) . ' days')->setTime(0, 0, 0);
    return $monday->format('Y-m-d H:i:s');
}

try {
    $weekStart = getWeekStart();

    // ── Créateurs récents (8 premiers ajoutés depuis lundi) ──────────────────
    $creatorsStmt = $pdo->prepare("
        SELECT
            c.id,
            c.name,
            c.profile_name   AS profileName,
            c.platform,
            c.avatar_url     AS avatar,
            c.cover_url      AS coverImage,
            c.description,
            COUNT(v.id)      AS totalVideos
        FROM creators c
        LEFT JOIN videos v ON v.creator_id = c.id
        WHERE c.created_at >= :week_start
        GROUP BY c.id, c.name, c.profile_name, c.platform,
                 c.avatar_url, c.cover_url, c.description
        ORDER BY c.created_at ASC
        LIMIT 8
    ");
    $creatorsStmt->execute([':week_start' => $weekStart]);
    $creators = $creatorsStmt->fetchAll();

    $creators = array_map(function ($row) {
        $row['avatar']     = to_public_asset_path($row['avatar']);
        $row['coverImage'] = to_public_asset_path($row['coverImage']);
        $row['totalVideos'] = (int)$row['totalVideos'];
        return $row;
    }, $creators);

    // ── Vidéos à découvrir (6 premières ajoutées depuis lundi) ───────────────
    function makeSlug(string $text): string {
        $text = mb_strtolower($text, 'UTF-8');
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'video';
    }

    $videosStmt = $pdo->prepare("
        SELECT
            v.id,
            v.creator_id,
            v.title,
            v.thumbnail_url  AS thumbnail,
            v.sprite_url,
            v.duration,
            v.views,
            c.name           AS creatorName,
            c.platform,
            c.avatar_url     AS creatorAvatar
        FROM videos v
        INNER JOIN creators c ON c.id = v.creator_id
        WHERE v.created_at >= :week_start
        ORDER BY v.created_at ASC
        LIMIT 6
    ");
    $videosStmt->execute([':week_start' => $weekStart]);
    $videos = $videosStmt->fetchAll();

    $videos = array_map(function ($video) {
        $video['thumbnail']     = to_public_asset_path($video['thumbnail']);
        $video['sprite_url']    = to_public_asset_path($video['sprite_url'] ?? '');
        $video['creatorAvatar'] = to_public_asset_path($video['creatorAvatar']);
        $video['slug']          = makeSlug($video['title']);
        return $video;
    }, $videos);

    echo json_encode([
        'status'    => 'success',
        'weekStart' => $weekStart,
        'creators'  => $creators,
        'videos'    => $videos,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>