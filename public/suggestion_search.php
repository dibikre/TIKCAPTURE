<?php
/**
 * suggestion_search.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Retourne jusqu'à 10 suggestions d'utilisateurs TikTok.
 * Requête POST JSON : { "keyword": "maika" }
 *
 * Intègre le X-Gnarly encoder complet (port PHP de encode.js).
 * Format URL confirmé : keyword + count + cursor + msToken + X-Bogus + X-Gnarly
 * ─────────────────────────────────────────────────────────────────────────────
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  X-GNARLY ENCODER  –  Port PHP complet de encode.js                     ║
// ╚══════════════════════════════════════════════════════════════════════════╝

$AA = [
    4294967295,138,1498001188,211147047,253,0,203,288,9,1196819126,
    3212677781,135,263,193,58,18,244,2931180889,240,173,268,2157053261,
    261,175,14,5,171,270,156,258,13,32,3732962506,185,169,2,6,132,162,
    200,3,160,217618912,62,2517678443,44,164,4,96,183,2903579748,
    3863347763,119,181,10,190,8,2654435769,259,104,230,128,2633865432,
    225,1,257,143,179,16,600974999,185100057,32,188,53,2718276124,177,
    196,4294967296,147,117,17,49,7,28,12,266,216,11,0,45,166,247,1451689750,
];

define('TIKTOK_CHARSET', 'u09tbS3UvgDEe6r-ZVMXzLpsAohTn7mdINQlW412GqBjfYiyk8JORCF5/xKHwacP=');

// ── Utilitaires 32 bits ──────────────────────────────────────────────────────

function xg_rotl32(int $v, int $c): int {
    $v &= 0xFFFFFFFF;
    return (($v << $c) | ($v >> (32 - $c))) & 0xFFFFFFFF;
}

function xg_add32(int $a, int $b): int {
    return ($a + $b) & 0xFFFFFFFF;
}

// ── ChaCha ───────────────────────────────────────────────────────────────────

function xg_qr(array &$s, int $a, int $b, int $c, int $d): void {
    $s[$a]=xg_add32($s[$a],$s[$b]); $s[$d]=xg_rotl32($s[$d]^$s[$a],16);
    $s[$c]=xg_add32($s[$c],$s[$d]); $s[$b]=xg_rotl32($s[$b]^$s[$c],12);
    $s[$a]=xg_add32($s[$a],$s[$b]); $s[$d]=xg_rotl32($s[$d]^$s[$a], 8);
    $s[$c]=xg_add32($s[$c],$s[$d]); $s[$b]=xg_rotl32($s[$b]^$s[$c], 7);
}

function xg_chachaBlock(array $state, int $R): array {
    $x = $state;
    $r = 0;
    while ($r < $R) {
        xg_qr($x,0,4, 8,12); xg_qr($x,1,5, 9,13);
        xg_qr($x,2,6,10,14); xg_qr($x,3,7,11,15);
        if (++$r >= $R) break;
        xg_qr($x,0,5,10,15); xg_qr($x,1,6,11,12);
        xg_qr($x,2,7,12,13); xg_qr($x,3,4,13,14);
        ++$r;
    }
    for ($i = 0; $i < 16; $i++) $x[$i] = xg_add32($x[$i], $state[$i]);
    return $x;
}

function xg_incrCounter(array &$state): void {
    $state[12] = xg_add32($state[12], 1);
}

// ── PRNG ─────────────────────────────────────────────────────────────────────

function xg_initPrng(array $AA): array {
    $nowMs = (int)(microtime(true) * 1000);
    return [
        'state' => [
            (int)$AA[44]&0xFFFFFFFF, (int)$AA[74]&0xFFFFFFFF,
            (int)$AA[10]&0xFFFFFFFF, (int)$AA[62]&0xFFFFFFFF,
            (int)$AA[42]&0xFFFFFFFF, (int)$AA[17]&0xFFFFFFFF,
            (int)$AA[ 2]&0xFFFFFFFF, (int)$AA[21]&0xFFFFFFFF,
            (int)$AA[ 3]&0xFFFFFFFF, (int)$AA[70]&0xFFFFFFFF,
            (int)$AA[50]&0xFFFFFFFF, (int)$AA[32]&0xFFFFFFFF,
            ($AA[0] & $nowMs) & 0xFFFFFFFF,
            random_int(0,0xFFFFFFFF),
            random_int(0,0xFFFFFFFF),
            random_int(0,0xFFFFFFFF),
        ],
        'cursor' => (int)$AA[88],   // = 0
    ];
}

function xg_prngRand(array &$prng): float {
    $block  = xg_chachaBlock($prng['state'], 8);
    $cursor = $prng['cursor'];
    $t = $block[$cursor] & 0xFFFFFFFF;
    $r = (int)(((0xFFFFF800 & $block[$cursor + 8]) & 0xFFFFFFFF) >> 11);
    if ($cursor === 7) {
        xg_incrCounter($prng['state']);
        $prng['cursor'] = 0;
    } else {
        $prng['cursor']++;
    }
    return ($t + 4294967296.0 * $r) / pow(2, 53);
}

// ── ChaCha XOR ───────────────────────────────────────────────────────────────

function xg_chachaXor(array $stateWords, int $rounds, array &$data): void {
    $n  = count($data);
    $nw = (int)(($n + 3) / 4);
    $u  = array_fill(0, $nw, 0);
    $fw = (int)($n / 4);
    $rem = $n % 4;

    for ($a = 0; $a < $fw; $a++) {
        $s = 4 * $a;
        $u[$a] = ($data[$s] | ($data[$s+1]<<8) | ($data[$s+2]<<16) | ($data[$s+3]<<24)) & 0xFFFFFFFF;
    }
    if ($rem > 0) {
        $u[$fw] = 0;
        for ($c = 0; $c < $rem; $c++) $u[$fw] |= ($data[4*$fw+$c] << (8*$c));
        $u[$fw] &= 0xFFFFFFFF;
    }

    $st  = $stateWords;
    $off = 0;
    for (; $off + 16 < $nw; $off += 16) {
        $block = xg_chachaBlock($st, $rounds);
        xg_incrCounter($st);
        for ($i = 0; $i < 16; $i++) { $u[$off+$i] ^= $block[$i]; $u[$off+$i] &= 0xFFFFFFFF; }
    }
    $block = xg_chachaBlock($st, $rounds);
    for ($i = 0, $rem2 = $nw - $off; $i < $rem2; $i++) {
        $u[$off+$i] ^= $block[$i]; $u[$off+$i] &= 0xFFFFFFFF;
    }

    for ($a = 0; $a < $fw; $a++) {
        $f = 4*$a;
        $data[$f]   =  $u[$a]        & 0xFF;
        $data[$f+1] = ($u[$a] >>  8) & 0xFF;
        $data[$f+2] = ($u[$a] >> 16) & 0xFF;
        $data[$f+3] = ($u[$a] >> 24) & 0xFF;
    }
    if ($rem > 0) for ($d = 0; $d < $rem; $d++) $data[4*$fw+$d] = ($u[$fw] >> (8*$d)) & 0xFF;
}

// ── Base64 custom TikTok ─────────────────────────────────────────────────────

function xg_b64Encode(array $bytes): string {
    $res = '';
    $len = count($bytes);
    for ($i = 3; $i <= $len; $i += 3) {
        $val = (($bytes[$i-3] & 0xFF) << 16)
             | (($bytes[$i-2] & 0xFF) <<  8)
             |  ($bytes[$i-1] & 0xFF);
        $res .= TIKTOK_CHARSET[($val>>18)&0x3F]
              . TIKTOK_CHARSET[($val>>12)&0x3F]
              . TIKTOK_CHARSET[($val>> 6)&0x3F]
              . TIKTOK_CHARSET[ $val     &0x3F];
    }
    return $res;
}

// ── Sérialisation TLV ────────────────────────────────────────────────────────

function xg_numToBytes(int $v): array {
    if ($v < 0xFF * 0xFF) return [($v >> 8) & 0xFF, $v & 0xFF];
    return [($v>>24)&0xFF, ($v>>16)&0xFF, ($v>>8)&0xFF, $v&0xFF];
}

function xg_serialize(array $obj): array {
    $arr = [count($obj)];
    foreach ($obj as $key => $value) {
        $arr[] = (int)$key;
        $valBytes = (is_int($value) || is_float($value))
            ? xg_numToBytes((int)$value)
            : array_values(unpack('C*', $value));
        $lenBytes = xg_numToBytes(count($valBytes));
        array_push($arr, ...$lenBytes);
        array_push($arr, ...$valBytes);
    }
    return $arr;
}

// ── Fonction publique encode() ───────────────────────────────────────────────

function xg_encode(string $queryString, string $body, string $userAgent): string {
    global $AA;

    $prng   = xg_initPrng($AA);
    $nowMs  = (int)(microtime(true) * 1000);
    $ts     = (int)($nowMs / 1000);
    $tsMicro = ($nowMs * 1000) % 2147483648;

    // Construire l'objet interne (champs 0-9)
    $obj    = [];
    $obj[1] = 1;
    $obj[2] = 0;
    $obj[3] = md5($queryString);
    $obj[4] = md5($body);
    $obj[5] = md5($userAgent);
    $obj[6] = $ts;
    $obj[7] = 1245783967;
    $obj[8] = (int)$tsMicro;
    $obj[9] = '5.1.0';
    $obj[0] = ($obj[6] ^ $obj[7] ^ $obj[8] ^ $obj[1] ^ $obj[2]) & 0xFFFFFFFF;

    // Sérialiser → bytes → string
    $plainBytes = xg_serialize($obj);
    $str = '';
    foreach ($plainBytes as $b) $str .= chr($b & 0xFF);

    // Générer 12 mots-clés aléatoires via le PRNG ChaCha
    $keyWords   = [];
    $keyByteArr = [];
    $rounds     = 0;
    for ($i = 0; $i < 12; $i++) {
        $num = (int)floor(pow(2, 32) * xg_prngRand($prng)) & 0xFFFFFFFF;
        $keyWords[]   = $num;
        $rounds       = (($num & 15) + $rounds) & 15;
        $keyByteArr[] =  $num        & 0xFF;
        $keyByteArr[] = ($num >>  8) & 0xFF;
        $keyByteArr[] = ($num >> 16) & 0xFF;
        $keyByteArr[] = ($num >> 24) & 0xFF;
    }
    $rounds += 5;

    // État ChaCha : OT[4 mots] + keyWords[12 mots]
    $OT = [
        (int)$AA[ 9] & 0xFFFFFFFF,
        (int)$AA[69] & 0xFFFFFFFF,
        (int)$AA[51] & 0xFFFFFFFF,
        (int)$AA[92] & 0xFFFFFFFF,
    ];
    $stateWords = array_merge($OT, array_map(fn($w) => $w & 0xFFFFFFFF, $keyWords));

    // Chiffrer le plaintext
    $dataBytes = [];
    for ($i = 0; $i < strlen($str); $i++) $dataBytes[] = ord($str[$i]);
    xg_chachaXor($stateWords, $rounds, $dataBytes);

    $x = '';
    foreach ($dataBytes as $b) $x .= chr($b & 0xFF);

    // Calculer la position d'insertion de la clé (someVal)
    $xLen    = strlen($x);
    $someVal = 0;
    foreach ($keyByteArr as $el) { $someVal += $el; $someVal = $someVal % ($xLen + 1); }
    for ($i = 0; $i < $xLen; $i++) { $someVal += ord($x[$i]); $someVal = $someVal % ($xLen + 1); }

    // Sentinel 0x4B = chr(75) = ((64^8)^3)
    $sentinel  = chr(((( 1 << 6) ^ (1 << 3)) ^ 3) & 0xFF);
    $keyString = '';
    foreach ($keyByteArr as $b) $keyString .= chr($b);

    $raw = $sentinel . substr($x, 0, $someVal) . $keyString . substr($x, $someVal);

    // Encoder en Base64 TikTok custom
    $rawBytes = [];
    for ($i = 0; $i < strlen($raw); $i++) $rawBytes[] = ord($raw[$i]);

    return xg_b64Encode($rawBytes);
}

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  LECTURE DES COOKIES                                                     ║
// ╚══════════════════════════════════════════════════════════════════════════╝

function sug_loadCookieString(): string {
    $path = __DIR__ . '/donnees/tiktok_cookies.txt';
    if (!file_exists($path)) return '';
    $pairs = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = preg_replace('/^#HttpOnly_/', '', $line);
        if (str_starts_with(ltrim($line), '#')) continue;
        $parts = explode("\t", $line);
        if (count($parts) >= 7) $pairs[] = trim($parts[5]) . '=' . trim($parts[6]);
    }
    return implode('; ', $pairs);
}

function sug_getCookieValue(string $name): string {
    $path = __DIR__ . '/donnees/tiktok_cookies.txt';
    if (!file_exists($path)) return '';
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = preg_replace('/^#HttpOnly_/', '', $line);
        if (str_starts_with(ltrim($line), '#')) continue;
        $parts = explode("\t", $line);
        if (count($parts) >= 7 && trim($parts[5]) === $name) return trim($parts[6]);
    }
    return '';
}

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  APPEL API TIKTOK SEARCH                                                 ║
// ╚══════════════════════════════════════════════════════════════════════════╝

function searchTikTokLives(string $keyword): array {
    $UA      = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
             . '(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $msToken  = sug_getCookieValue('msToken');
    $deviceId = sug_getCookieValue('tt_chain_token') ?: '7608144287465227784';
    $odinId   = sug_getCookieValue('odin_tt') ?: '7368814249832088582';

    // ── Query string complète (ordre identique à l'URL réelle) ─────────────
    $baseParams = [
        'WebIdLastTime'            => (string)(time() - rand(100, 3600)),
        'aid'                      => '1988',
        'app_language'             => 'fr',
        'app_name'                 => 'tiktok_web',
        'browser_language'         => 'fr',
        'browser_name'             => 'Mozilla',
        'browser_online'           => 'true',
        'browser_platform'         => 'Win32',
        'browser_version'          => '5.0 (Windows)',
        'channel'                  => 'tiktok_web',
        'client_ab_versions'       => '70508271,72437276,73720540,74087153,74926161,75004380,75030792,75077941,75182839,75195481,75225343,75252003,75294820,75301225,75308229,75322036,75337541,75350406,75360573,75362516,75373598,75381398,75388125,75395001,75401813,75414975,75419443,75423621,75428596,75431413,75440609,75472954,75476288,75479423,75484880,75485114,75491423,75492091,75507855,75518247,75520328,75528341,70138197,70156809,70405643,71057832,71200802,71381811,71516509,71803300,71962127,72360691,72408100,72854054,72892778,73004916,73171280,73208420,73989921,74276218,74844724,75330961',
        'cookie_enabled'           => 'true',
        'count'                    => '12',
        'cursor'                   => '0',
        'data_collection_enabled'  => 'true',
        'device_id'                => $deviceId,
        'device_platform'          => 'web_pc',
        'device_type'              => 'web_h264',
        'focus_state'              => 'true',
        'from_page'                => 'search',
        'history_len'              => '6',
        'is_fullscreen'            => 'false',
        'is_non_personalized_search' => '0',
        'is_page_visible'          => 'true',
        'keyword'                  => $keyword,
        'odinId'                   => $odinId,
        'offset'                   => '0',
        'os'                       => 'windows',
        'priority_region'          => 'MA',
        'referer'                  => 'https://www.tiktok.com/live',
        'region'                   => 'MA',
        'root_referer'             => 'https://www.tiktok.com/',
        'screen_height'            => '1080',
        'screen_width'             => '1920',
        'tz_name'                  => 'Africa/Casablanca',
        'user_is_login'            => 'true',
        'web_search_code'          => '{"tiktok":{"client_params_x":{"search_engine":{"ies_mt_user_live_video_card_use_libra":1,"mt_search_general_user_live_card":1}},"search_server":{}}}',
        'webcast_language'         => 'fr',
        'msToken'                  => $msToken,
    ];
    $baseQuery = http_build_query($baseParams);

    // ── X-Gnarly signé sur la query string complète ─────────────────────────
    $xGnarly = xg_encode($baseQuery, '', $UA);
    $xBogus  = 'DFSzsIVYIfJANejvC7Pb/Sa6Tz53';

    // ── URL finale ──────────────────────────────────────────────────────────
    $url = 'https://www.tiktok.com/api/search/live/full/?'
         . $baseQuery
         . '&X-Bogus='  . urlencode($xBogus)
         . '&X-Gnarly=' . urlencode($xGnarly);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_USERAGENT      => $UA,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json, text/plain, */*',
            'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer: https://www.tiktok.com/live',
            'Origin: https://www.tiktok.com',
            'sec-fetch-site: same-origin',
            'sec-fetch-mode: cors',
            'sec-fetch-dest: empty',
            'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "Windows"',
        ],
        CURLOPT_COOKIE         => sug_loadCookieString(),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) return [];

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($data['data'])) return [];

    $suggestions = [];
    foreach (array_slice($data['data'], 0, 10) as $item) {
        // raw_data est un JSON string imbriqué — il faut le décoder
        $rawStr = $item['live_info']['raw_data'] ?? null;
        if (!$rawStr) continue;

        $room = json_decode($rawStr, true);
        if (!$room || json_last_error() !== JSON_ERROR_NONE) continue;

        // Nettoyer les URLs des entités unicode (\u0026 → &)
        array_walk_recursive($room, function(&$val) {
            if (is_string($val)) $val = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
        });

        $owner = $room['owner'] ?? null;
        if (!$owner || empty($owner['display_id'])) continue;

        $uniqueId = $owner['display_id'];
        $nickname = $owner['nickname'] ?? $uniqueId;

        $avatar = $owner['avatar_thumb']['url_list'][0]
               ?? $owner['avatar_medium']['url_list'][0]
               ?? '';

        // stream_snapshot = capture réelle du stream en cours
        $cover = $room['stream_snapshot']['urls'][0]
              ?? $owner['avatar_medium']['url_list'][0]
              ?? $avatar;

        $viewers   = (int)($room['user_count'] ?? 0);
        $title     = $room['title']            ?? '';
        $followers = (int)($owner['follow_info']['follower_count'] ?? 0);

        $suggestions[] = [
            'username'  => $uniqueId,
            'nickname'  => $nickname,
            'avatar'    => $avatar,
            'cover'     => $cover,
            'viewers'   => $viewers,
            'title'     => $title,
            'followers' => $followers,
            'verified'  => false,
        ];
    }

    return $suggestions;
}

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  POINT D'ENTRÉE                                                          ║
// ╚══════════════════════════════════════════════════════════════════════════╝

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$keyword = trim($input['keyword'] ?? '');

if ($keyword === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Paramètre keyword manquant.']);
    exit;
}

$keyword     = mb_substr($keyword, 0, 50);
$suggestions = searchTikTokLives($keyword);

echo json_encode(
    ['success' => true, 'suggestions' => $suggestions, 'count' => count($suggestions)],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);