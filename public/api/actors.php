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

try {
    $page   = max(1, (int)($_GET['page']   ?? 1));
    $limit  = min(48, max(1, (int)($_GET['limit'] ?? 24)));
    $search = trim($_GET['search'] ?? '');
    $offset = ($page - 1) * $limit;

    $where  = '';
    $params = [];
    if ($search !== '') {
        $where = "WHERE (c.name LIKE :search OR c.platform LIKE :search2)";
        $params[':search']  = "%$search%";
        $params[':search2'] = "%$search%";
    }

    $countSql  = "SELECT COUNT(*) FROM creators c $where";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total      = (int)$countStmt->fetchColumn();
    $totalPages = (int)ceil($total / $limit);

    $sql = "
        SELECT
            c.id,
            c.name,
            c.profile_name AS profileName,
            c.platform,
            c.avatar_url   AS avatar,
            c.cover_url    AS coverImage,
            c.description,
            COUNT(v.id)    AS totalVideos
        FROM creators c
        LEFT JOIN videos v ON v.creator_id = c.id
        $where
        GROUP BY c.id, c.name, c.profile_name, c.platform, c.avatar_url, c.cover_url, c.description
        ORDER BY c.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $rows = array_map(function ($row) {
        $row['avatar']     = to_public_asset_path($row['avatar']);
        $row['coverImage'] = to_public_asset_path($row['coverImage']);
        return $row;
    }, $rows);

    echo json_encode([
        'status'     => 'success',
        'data'       => $rows,
        'total'      => $total,
        'page'       => $page,
        'totalPages' => $totalPages,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
