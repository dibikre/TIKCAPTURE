<?php
header('Content-Type: application/json');

function formatBytes($bytes) {
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return round($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

if (isset($_POST['filename']) && !empty($_POST['filename'])) {
    // Nettoyer le chemin mais garder le dossier vids/
    $filename = str_replace(['../', './'], '', $_POST['filename']);
    $filepath = __DIR__ . '/' . $filename;
    
    // Log pour debug (à retirer après)
    error_log("Checking file: " . $filepath);
    
    if (file_exists($filepath)) {
        $size = filesize($filepath);
        echo json_encode([
            'size' => $size,
            'formatted_size' => formatBytes($size),
            'path' => $filepath // Pour debug
        ]);
    } else {
        echo json_encode([
            'error' => 'File not found',
            'path' => $filepath, // Pour debug
            'received' => $_POST['filename']
        ]);
    }
} else {
    echo json_encode(['error' => 'No filename provided']);
}
?>