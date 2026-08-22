<?php
if (php_sapi_name() !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

echo "=== Récupération des données DLive ===\n\n";
echo "Entrez le nom de la chaîne ou l'URL (https://dlive.tv/{nom_chaine}): ";
$input = trim(fgets(STDIN));

if (empty($input)) {
    die("Erreur: Aucune entrée fournie.\n");
}

$nom_chaine = $input;
if (preg_match('#https?://dlive\.tv/([^/?\s]+)#i', $input, $matches)) {
    $nom_chaine = $matches[1];
}
echo "Chaîne: $nom_chaine\n";

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
    die("Erreur HTTP: $http_code\n");
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Erreur JSON: " . json_last_error_msg() . "\n");
}

$user      = $data['data']['userByDisplayName'] ?? null;
$livestream = $user['livestream'] ?? null;
$username  = $user['username'] ?? $nom_chaine;

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
    $playlist_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($playlist_content && $playlist_code === 200) {
        // Extraire l'URL réelle depuis la playlist
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
            $sign_code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($sign_response && $sign_code === 200) {
                $hls_url = trim($sign_response);
            }
        }
    }
}

// === CONSTRUCTION DU JSON ===
$tags = $livestream['tags'] ?? [];
$tags_str = is_array($tags) ? implode(', ', $tags) : null;

$output = [
    'name'             => $user['displayname']                     ?? null,
    'creator_pic'      => $livestream['thumbnailUrl']              ?? null,
    'status_live'      => $livestream ? true : false,
    'room_id'          => $livestream['permlink']                  ?? null,
    'live_title'       => $livestream['title']                     ?? null,
    'live_description' => $tags_str,
    'follower'         => $user['followers']['totalCount']         ?? null,
    'followed'         => $user['following']['totalCount']         ?? null,
    'spectator'        => $livestream['watchingCount']             ?? null,
    'live_since'       => $user['lastStreamedAt']                  ?? null,
    'origine_quality'  => $hls_url,
];

// === ENREGISTREMENT JSON ===
$dossier = "donnees_dlive";
if (!is_dir($dossier)) {
    mkdir($dossier, 0755, true);
}

$timestamp = date('Y-m-d_H-i-s');
$fichier   = "$dossier/{$nom_chaine}_{$timestamp}.json";

file_put_contents($fichier, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo "\n✓ JSON enregistré: $fichier\n";
echo "Nom        : " . ($output['name'] ?? '—') . "\n";
echo "Status live: " . ($output['status_live'] ? 'En direct' : 'Hors ligne') . "\n";
echo "Titre      : " . ($output['live_title'] ?? '—') . "\n";
echo "Spectateurs: " . ($output['spectator'] ?? '—') . "\n";
echo "HLS URL    : " . ($output['origine_quality'] ?? '—') . "\n";
echo "\n=== Terminé ===\n";