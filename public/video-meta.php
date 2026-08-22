<?php
require_once __DIR__ . '/config/db.php';

$actorId = $_GET['actor']  ?? '';
$videoId = $_GET['video']  ?? '';

$title       = 'Vidéo Live – TikCapture';
$description = 'Regardez ce live en replay sur TikCapture.';
$image       = 'https://tikcapture.live/images/og-image.jpg';
$url         = 'https://tikcapture.live';

if ($actorId && $videoId) {
    try {
        $stmt = $pdo->prepare("
            SELECT v.title, v.description, v.thumbnail_url, v.duration,
                   c.name AS creator_name, c.id AS creator_id
            FROM videos v
            JOIN creators c ON c.id = v.creator_id
            WHERE v.id = ? AND c.id = ?
            LIMIT 1
        ");
        $stmt->execute([$videoId, $actorId]);
        $row = $stmt->fetch();
        if ($row) {
            $title       = htmlspecialchars($row['title']) . ' — ' . htmlspecialchars($row['creator_name']) . ' | TikCapture';
            $description = 'Live de ' . htmlspecialchars($row['creator_name']) . ' — Durée : ' . htmlspecialchars($row['duration']) . '. Regardez ce live en replay sur TikCapture.';
            $image       = 'https://tikcapture.live/files/' . htmlspecialchars($row['thumbnail_url']);
            $url         = 'https://tikcapture.live/createurs/' . urlencode($actorId) . '/videos/' . urlencode($videoId);
        }
    } catch (Throwable $e) {}
}

header('Content-Type: application/json');
echo json_encode([
    'title'       => $title,
    'description' => $description,
    'image'       => $image,
    'url'         => $url,
]);