<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Récupérer le nom de chaîne depuis l'URL
$nom_chaine = $_GET['channel'] ?? '';

if (empty($nom_chaine)) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètre "channel" requis']);
    exit;
}

// Nettoyer l'input si c'est une URL
if (preg_match('#https?://dlive\.tv/([^/?\s]+)#i', $nom_chaine, $matches)) {
    $nom_chaine = $matches[1];
}

// === APPEL API GRAPHQL ===
$payload = [
    "operationName" => "LivestreamPage",
    "variables" => [
        "displayname" => $nom_chaine,
        "add" => false,
        "isLoggedIn" => false,
        "isMe" => false,
        "showUnpicked" => false,
        "order" => "PickTime"
    ],
    "extensions" => [
        "persistedQuery" => [
            "version" => 1,
            "sha256Hash" => "b8c7cd860dbe43512fb7574eefdc60cefd9eb30d35b982cc7b9a23dc4093524b"
        ]
    ]
];

$json_payload = json_encode($payload);

$ch = curl_init("https://graphigo.prd.dlive.tv/");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $json_payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_payload)
    ],
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $http_code !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'Erreur HTTP DLive: ' . $http_code]);
    exit;
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur JSON: ' . json_last_error_msg()]);
    exit;
}

$user = $data['data']['userByDisplayName'] ?? null;
$livestream = $user['livestream'] ?? null;
$username = $user['username'] ?? $nom_chaine;

// === RÉCUPÉRATION URL HLS SIGNÉE ===
$hls_url = null;
if ($livestream) {
    $url_principale = "https://live.prd.dlive.tv/hls/live/{$username}.m3u8?web";

    $ch = curl_init($url_principale);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: */*',
            'Origin: https://dlive.tv',
            'Referer: https://dlive.tv/',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'
        ]
    ]);
    $playlist_content = curl_exec($ch);
    $playlist_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($playlist_content && $playlist_code === 200) {
        $url_reelle = null;
        foreach (explode("\n", $playlist_content) as $line) {
            $line = trim($line);
            if (strpos($line, 'https://livestreams.prdv3.dlivecdn.com/') === 0) {
                $url_reelle = $line;
                break;
            }
        }

        if ($url_reelle) {
            $sign_payload = json_encode(['playlisturi' => $url_reelle]);
            $ch = curl_init("https://live.prd.dlive.tv/hls/sign/url");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $sign_payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($sign_payload),
                    'Origin: https://dlive.tv',
                    'Referer: https://dlive.tv/',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0'
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            $sign_response = curl_exec($ch);
            $sign_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($sign_response && $sign_code === 200) {
                $hls_url = trim($sign_response);
            }
        }
    }
}

$tags = $livestream['tags'] ?? [];
$tags_str = is_array($tags) ? implode(', ', $tags) : null;

$output = [
    'platform'         => 'dlive',
    'logo'             => 'dlive.png',
    'name'             => $user['displayname'] ?? null,
    'creator_pic'      => $livestream['thumbnailUrl'] ?? null,
    'status_live'      => $livestream ? true : false,
    'room_id'          => $livestream['permlink'] ?? null,
    'live_title'       => $livestream['title'] ?? null,
    'live_description' => $tags_str,
    'follower'         => $user['followers']['totalCount'] ?? null,
    'followed'         => $user['following']['totalCount'] ?? null,
    'spectator'        => $livestream['watchingCount'] ?? null,
    'live_since'       => $user['lastStreamedAt'] ?? null,
    'origine_quality'  => $hls_url,
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);