<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$url = $_GET['url'] ?? '';

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètre "url" requis']);
    exit;
}

if (!preg_match('#https?://www\.bigo\.tv/[^/]+/([^/?]+)#', $url, $matches)) {
    http_response_code(400);
    echo json_encode(['error' => 'Format d\'URL Bigo invalide. Format attendu: https://www.bigo.tv/fr/{bigoId}']);
    exit;
}

$room_id = $matches[1];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://ta.bigo.tv/official_website/studio/getInternalStudioInfo');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['siteId' => $room_id]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_ENCODING, '');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: */*',
    'Accept-Language: zh,zh-TW;q=0.9,en-US;q=0.8,en;q=0.7,zh-CN;q=0.6,ru;q=0.5',
    'Origin: https://www.bigo.tv',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.106 Safari/537.36'
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    http_response_code(502);
    echo json_encode(['error' => 'Erreur cURL: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

$json_data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur JSON: ' . json_last_error_msg()]);
    exit;
}

$data = $json_data['data'] ?? [];

$output = [
    'platform'         => 'bigo',
    'logo'             => 'bigo.png',
    'name'             => $data['nick_name'] ?? null,
    'creator_pic'      => $data['snapshot'] ?? null,
    'status_live'      => $data['alive'] ?? false,
    'room_id'          => $data['roomId'] ?? null,
    'live_title'       => $data['roomTopic'] ?? null,
    'live_description' => $data['roomTopic'] ?? null,
    'follower'         => null,
    'followed'         => null,
    'spectator'        => null,
    'live_since'       => null,
    'origine_quality'  => $data['hls_src'] ?? null,
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);