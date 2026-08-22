<?php
echo "=== Script de vérification Bigo Live ===\n\n";
echo "Entrez l'URL du live Bigo (ex: https://www.bigo.tv/fr/Princeejay) : ";
$input_url = trim(fgets(STDIN));

if (preg_match('#https?://www\.bigo\.tv/[^/]+/([^/?]+)#', $input_url, $matches)) {
    $room_id = $matches[1];
    echo "✓ BigoId extrait: $room_id\n\n";
} else {
    echo "✗ Erreur: Format d'URL invalide!\n";
    echo "Format attendu: https://www.bigo.tv/fr/{bigoId}\n";
    exit(1);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://ta.bigo.tv/official_website/studio/getInternalStudioInfo');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['siteId' => $room_id]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
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
    echo "Erreur cURL: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

curl_close($ch);

$json_data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Erreur JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

$data = $json_data['data'] ?? [];

$output = [
    'name'             => $data['nick_name']  ?? null,
    'creator_pic'      => $data['snapshot']   ?? null,
    'status_live'      => $data['alive']      ?? null,
    'room_id'          => $data['roomId']     ?? null,
    'live_title'       => $data['roomTopic']  ?? null,
    'live_description' => $data['roomTopic']  ?? null,
    'follower'         => null,
    'followed'         => null,
    'spectator'        => null,
    'live_since'       => null,
    'origine_quality'  => $data['hls_src']    ?? null,
];

$timestamp = date('Ymd_His');
$filename  = "bigo_user_{$room_id}_{$timestamp}.json";
$filepath  = __DIR__ . '/' . $filename;

file_put_contents($filepath, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo "Nom         : " . ($output['name'] ?? '—') . "\n";
echo "Status live : " . ($output['status_live'] ? 'En direct' : 'Hors ligne') . "\n";
echo "Titre       : " . ($output['live_title'] ?? '—') . "\n";
echo "HLS URL     : " . ($output['origine_quality'] ?? '—') . "\n";
echo "\n✓ JSON enregistré: $filename\n";