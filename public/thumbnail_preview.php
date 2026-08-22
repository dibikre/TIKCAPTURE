<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$streamUrl = $input['stream_url'] ?? '';

if (!$streamUrl) {
    echo json_encode(['success' => false, 'error' => 'No stream URL']);
    exit;
}

$filename = 'thumb_' . uniqid() . '.jpg';
$outputPath = __DIR__ . '/donnees/' . $filename;
$ffmpeg = __DIR__ . '/binaires/ffmpeg';

if (!file_exists($ffmpeg)) {
    echo json_encode(['success' => false, 'error' => 'ffmpeg not found']);
    exit;
}

$cmd = 'timeout 15 ' . escapeshellarg($ffmpeg) . ' -y -ss 00:00:03 -i ' . escapeshellarg($streamUrl) .
    ' -frames:v 1 -q:v 2 ' . escapeshellarg($outputPath) .
    ' 2>/dev/null';

exec($cmd, $output, $returnCode);

if ($returnCode === 0 && file_exists($outputPath)) {
    echo json_encode(['success' => true, 'filename' => $filename]);
} else {
    if (file_exists($outputPath)) unlink($outputPath);
    echo json_encode(['success' => false, 'error' => 'ffmpeg failed', 'code' => $returnCode]);
}