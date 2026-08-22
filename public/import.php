<?php
session_start();

// ─── Auth ─────────────────────────────────────────────────────────────────────
define('USERNAME', 'dibykre');
define('PASSWORD', '4696');
define('API_KEY',  '116859tc0vf080ijf3jk5x');
define('API_BASE', 'https://doodapi.co/api');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === USERNAME && $_POST['password'] === PASSWORD) {
        $_SESSION['auth'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = 'Identifiants incorrects.';
    }
}
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ─── API helpers ──────────────────────────────────────────────────────────────
function api_get(string $endpoint, array $params = []): array {
    $params['key'] = API_KEY;
    $url = API_BASE . $endpoint . '?' . http_build_query($params);
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return ['status' => 0, 'msg' => 'Request failed'];
    return json_decode($raw, true) ?? ['status' => 0, 'msg' => 'Invalid JSON'];
}

// ─── AJAX endpoints ───────────────────────────────────────────────────────────
if (!empty($_GET['ajax']) && !empty($_SESSION['auth'])) {
    header('Content-Type: application/json');

    // List folders
    if ($_GET['ajax'] === 'folders') {
        echo json_encode(api_get('/folder/list', ['fld_id' => 0]));
        exit;
    }

    // List files in folder
    if ($_GET['ajax'] === 'files' && isset($_GET['fld_id'])) {
        echo json_encode(api_get('/file/list', ['fld_id' => (string)$_GET['fld_id'], 'per_page' => 200]));
        exit;
    }

    // ── Publish endpoint ──────────────────────────────────────────────────────
    if ($_GET['ajax'] === 'publish' && $_SERVER['REQUEST_METHOD'] === 'POST') {

        $payload = json_decode(file_get_contents('php://input'), true);
        if (!$payload || empty($payload['zip_b64']) || empty($payload['selected_codes'])) {
            echo json_encode(['ok' => false, 'error' => 'Données manquantes']);
            exit;
        }

        // Decode + save ZIP
        $zipData = base64_decode($payload['zip_b64']);
        $tmpZip  = sys_get_temp_dir() . '/dood_pub_' . uniqid() . '.zip';
        file_put_contents($tmpZip, $zipData);

        $zip = new ZipArchive();
        if ($zip->open($tmpZip) !== true) {
            unlink($tmpZip);
            echo json_encode(['ok' => false, 'error' => "Impossible d'ouvrir le ZIP"]);
            exit;
        }

        $tmpDir = sys_get_temp_dir() . '/dood_pub_' . uniqid();
        mkdir($tmpDir, 0755, true);
        $zip->extractTo($tmpDir);
        $zip->close();
        unlink($tmpZip);

        // Scan all metadata.json in the ZIP
        $metas = [];
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($rii as $file) {
            if ($file->getFilename() === 'metadata.json') {
                $meta = json_decode(file_get_contents($file->getPathname()), true);
                if ($meta) {
                    $creatorFolder = basename(dirname($file->getPathname()));
                    $metas[$creatorFolder] = [
                        'meta' => $meta,
                        'dir'  => dirname($file->getPathname()),
                    ];
                }
            }
        }

        // DB connection
        require_once __DIR__ . '/config/db.php';

        $results = [];
        $errors  = [];

        foreach ($payload['selected_codes'] as $item) {
            $doodCode  = $item['code']  ?? '';
            $doodTitle = $item['title'] ?? '';

            // Match: find metadata whose live.room_id or files.video basename appears in dood title
            $matchedMeta    = null;
            $matchedCreator = null;

            foreach ($metas as $cFolder => $mdata) {
                $meta      = $mdata['meta'];
                $roomId    = $meta['live']['room_id']  ?? '';
                $videoFile = $meta['files']['video']   ?? '';
                $videoBase = pathinfo($videoFile, PATHINFO_FILENAME);

                if (
                    ($roomId    && (str_contains($doodTitle, $roomId)    || $doodCode === $roomId))    ||
                    ($videoBase && (str_contains($doodTitle, $videoBase) || $doodCode === $videoBase))
                ) {
                    $matchedMeta    = $meta;
                    $matchedCreator = $cFolder;
                    break;
                }
            }

            if (!$matchedMeta) {
                $errors[] = "Aucune métadonnée pour : $doodTitle ($doodCode)";
                continue;
            }

            $meta     = $matchedMeta;
            $dir      = $metas[$matchedCreator]['dir'];
            $creator  = $meta['creator'];
            $live     = $meta['live'];
            $rec      = $meta['recording'];
            $files    = $meta['files'];
            $platform = $meta['plateforme'] ?? 'tiktok';

            // Build day folder slug  e.g. 24_03_2026
            $recordedTs = strtotime($meta['recorded_at'] ?? 'now');
            $dayFolder  = date('d_m_Y', $recordedTs);
            $slug       = preg_replace('/[^a-z0-9_\-]/', '', strtolower($creator['username'] ?? 'unknown'));

            // Copy images to server
            $avatarDb   = "images/{$dayFolder}/{$slug}/avatar.jpg";
            $spriteDb   = "images/{$dayFolder}/{$slug}/sprite_6x6.jpg";
            $thumbDb    = "images/{$dayFolder}/{$slug}/thumbnail.jpg";
            $avatarDest = "files/{$avatarDb}";
            $spriteDest = "files/{$spriteDb}";
            $thumbDest  = "files/{$thumbDb}";
            @mkdir(dirname($avatarDest), 0755, true);

            $avatarSrc = $dir . '/' . ($files['avatar']    ?? 'avatar.jpg');
            $spriteSrc = $dir . '/' . ($files['sprite']    ?? 'sprite.jpg');
            $thumbSrc  = $dir . '/' . ($files['thumbnail'] ?? 'thumbnail.jpg');
            if (file_exists($avatarSrc)) @copy($avatarSrc, $avatarDest);
            if (file_exists($spriteSrc)) @copy($spriteSrc, $spriteDest);
            if (file_exists($thumbSrc))  @copy($thumbSrc,  $thumbDest);

            $demoDest = "files/videos/{$dayFolder}/{$slug}/demo_15s.mp4";
            @mkdir(dirname($demoDest), 0755, true);
            $demoSrc  = $dir . '/' . ($files['demo'] ?? 'demo_15s.mp4');
            if (file_exists($demoSrc)) @copy($demoSrc, $demoDest);

            // ── Upsert creator ────────────────────────────────────────────────
            $creatorId = $creator['username'] ?? $matchedCreator;
            $pdo->prepare("
                INSERT INTO creators
                  (id, name, profile_name, platform, avatar_url, cover_url, description, uploader_id, uploader_url, webpage_url)
                VALUES
                  (:id,:name,:pn,:pl,:av,:cv,:desc,:uid,:uurl,:wurl)
                ON DUPLICATE KEY UPDATE
                  name=VALUES(name), profile_name=VALUES(profile_name),
                  avatar_url=VALUES(avatar_url), description=VALUES(description),
                  updated_at=CURRENT_TIMESTAMP
            ")->execute([
                ':id'   => $creatorId,
                ':name' => $creator['nickname'] ?? $creatorId,
                ':pn'   => $creator['username'] ?? $creatorId,
                ':pl'   => $platform,
                ':av'   => $avatarDb,
                ':cv'   => '',
                ':desc' => $creator['bio'] ?? '',
                ':uid'  => $creator['username'] ?? $creatorId,
                ':uurl' => 'https://www.tiktok.com/@' . ($creator['username'] ?? ''),
                ':wurl' => $live['url'] ?? '',
            ]);

            // ── Insert video ──────────────────────────────────────────────────
            $videoId = $doodCode;
            $embedUrl   = 'https://dood.li/e/' . $doodCode;
            $durationF  = $rec['duration_formatted'] ?? '00:00:00';
            $dParts     = explode(':', $durationF);
            $dDisplay   = count($dParts) === 3
                ? ($dParts[0] > 0 ? ((int)$dParts[0]*60+(int)$dParts[1]).':'.$dParts[2] : $dParts[1].':'.$dParts[2])
                : $durationF;

            $startTime   = $live['start_time'] ?? $meta['recorded_at'] ?? 'now';
            $recordedAt  = date('Y-m-d H:i:s', strtotime($startTime));
            $publishedAt = date('Y-m-d H:i:s');
            $description = 'Remix du live '.$platform.' de '.$creatorId.' diffusé le '.date('Y-m-d à H:i', strtotime($startTime));
            $tags        = "Live de $creatorId, enregistrement VOD, remix live, revisualiser direct";

            $pdo->prepare("
                INSERT INTO videos
                  (id, creator_id, title, thumbnail_url, video_url, duration, views, description,
                   tags, doodstream_code, doodstream_embed, sprite_url, demo_url,
                   recorded_at, published_at, day_folder, source_filename)
                VALUES
                  (:id,:cid,:title,:thumb,:vurl,:dur,:views,:desc,
                   :tags,:dcode,:dembed,:sprite,:demo,
                   :rat,:pat,:day,:src)
                ON DUPLICATE KEY UPDATE
                  doodstream_code=VALUES(doodstream_code),
                  doodstream_embed=VALUES(doodstream_embed),
                  thumbnail_url=VALUES(thumbnail_url),
                  sprite_url=VALUES(sprite_url),
                  published_at=VALUES(published_at)
            ")->execute([
                ':id'     => $videoId,
                ':cid'    => $creatorId,
                ':title'  => (function() use ($live, $creatorId) {
                    $t = $live['title'] ?? '';
                    if (!$t) return 'Live ' . $creatorId;
                    return stripos($t, 'Live Tiktok') !== false
                        ? $t
                        : $creatorId . ' - ' . $t;
                })(),
                ':thumb'  => $thumbDb,
                ':vurl'   => $embedUrl,
                ':dur'    => $dDisplay,
                ':views'  => '0',
                ':desc'   => $description,
                ':tags'   => $tags,
                ':dcode'  => $doodCode,
                ':dembed' => $embedUrl,
                ':sprite' => $spriteDb,
                ':demo'   => "videos/{$dayFolder}/{$slug}/demo_15s.mp4",
                ':rat'    => $recordedAt,
                ':pat'    => $publishedAt,
                ':day'    => $dayFolder,
                ':src'    => $files['video'] ?? '',
            ]);

            api_get('/file/set_folder', [
                'file_code' => $doodCode,
                'fld_id'    => '1754075',
            ]);

            $results[] = [
                'video'   => $videoId,
                'creator' => $creatorId,
                'title'   => $live['title'] ?? '',
                'dood'    => $doodCode,
            ];
        }

        // Cleanup temp dir
        $di = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($di as $f) { $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath()); }
        @rmdir($tmpDir);

        echo json_encode(['ok' => true, 'published' => $results, 'errors' => $errors]);
        exit;
    }

    echo json_encode(['status' => 400, 'msg' => 'Unknown action']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DoodStream — Médiathèque</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
  :root {
    --ink:     #0a0a0f;
    --surface: #0f0f1a;
    --card:    #15151f;
    --border:  #1e1e2e;
    --accent:  #6c63ff;
    --accent2: #ff6584;
    --green:   #22c55e;
    --muted:   #4a4a6a;
    --text:    #e0dff5;
    --sub:     #8888aa;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html { background: var(--ink); color: var(--text); }
  body { font-family: 'DM Sans', sans-serif; min-height: 100vh; background: var(--ink); }
  body::before {
    content: ''; position: fixed; inset: 0; z-index: 0; pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    background-size: 200px; opacity: .35;
  }
  .page { position: relative; z-index: 1; }

  /* ── Login ── */
  .login-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; background:radial-gradient(ellipse 80% 60% at 50% 0%, #1a1a3a 0%, var(--ink) 70%); }
  .login-card { width:100%; max-width:400px; padding:2.5rem; background:var(--card); border:1px solid var(--border); border-radius:1.25rem; box-shadow:0 0 60px rgba(108,99,255,.15),0 24px 60px rgba(0,0,0,.5); }
  .login-card h1 { font-family:'Syne',sans-serif; font-size:2rem; font-weight:800; letter-spacing:-.03em; }
  .login-card h1 span { color:var(--accent); }
  .field { display:flex; flex-direction:column; gap:.35rem; margin-top:1.25rem; }
  .field label { font-size:.75rem; font-weight:500; color:var(--sub); text-transform:uppercase; letter-spacing:.08em; }
  .field input { background:var(--surface); border:1px solid var(--border); color:var(--text); padding:.65rem 1rem; border-radius:.625rem; font-family:inherit; font-size:.95rem; outline:none; transition:border .2s; }
  .field input:focus { border-color:var(--accent); }
  .btn-primary { margin-top:1.75rem; width:100%; padding:.75rem; background:var(--accent); color:#fff; font-family:'Syne',sans-serif; font-size:.95rem; font-weight:700; letter-spacing:.03em; border:none; border-radius:.625rem; cursor:pointer; transition:opacity .2s,transform .15s; }
  .btn-primary:hover { opacity:.88; transform:translateY(-1px); }
  .err { margin-top:1rem; padding:.65rem 1rem; background:rgba(255,101,132,.1); border:1px solid rgba(255,101,132,.3); border-radius:.5rem; color:var(--accent2); font-size:.875rem; }

  /* ── Header ── */
  .header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 2rem; border-bottom:1px solid var(--border); background:rgba(15,15,26,.85); backdrop-filter:blur(12px); position:sticky; top:0; z-index:10; }
  .logo { font-family:'Syne',sans-serif; font-size:1.35rem; font-weight:800; letter-spacing:-.03em; }
  .logo span { color:var(--accent); }
  .btn-logout { font-size:.8rem; font-weight:600; letter-spacing:.05em; text-transform:uppercase; padding:.45rem 1.1rem; border-radius:.5rem; border:1px solid var(--border); background:transparent; color:var(--sub); cursor:pointer; font-family:'DM Sans',sans-serif; transition:all .2s; }
  .btn-logout:hover { border-color:var(--accent2); color:var(--accent2); }
  .main { padding:2.5rem 2rem; max-width:1400px; margin:0 auto; }

  /* ── Filter ── */
  .filter-zone { background:var(--card); border:1px solid var(--border); border-radius:1rem; padding:1.5rem 2rem; display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; box-shadow:0 4px 24px rgba(0,0,0,.3); }
  .filter-label { font-family:'Syne',sans-serif; font-size:.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--sub); white-space:nowrap; }
  .folder-select { flex:1; min-width:240px; appearance:none; background:var(--surface) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236c63ff' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") no-repeat right 1rem center; border:1px solid var(--border); color:var(--text); padding:.7rem 2.5rem .7rem 1rem; border-radius:.625rem; font-family:'DM Sans',sans-serif; font-size:.95rem; cursor:pointer; outline:none; transition:border .2s; }
  .folder-select:focus { border-color:var(--accent); }
  .folder-select option { background:var(--card); }
  .badge { font-size:.75rem; font-weight:600; padding:.3rem .75rem; border-radius:999px; background:rgba(108,99,255,.15); color:var(--accent); border:1px solid rgba(108,99,255,.3); white-space:nowrap; }

  /* ── Selection toolbar ── */
  .sel-toolbar { display:none; align-items:center; gap:.75rem; flex-wrap:wrap; margin-top:1.25rem; padding:.9rem 1.25rem; background:rgba(108,99,255,.08); border:1px solid rgba(108,99,255,.25); border-radius:.875rem; }
  .sel-toolbar.visible { display:flex; }
  .sel-count { font-family:'Syne',sans-serif; font-size:.85rem; font-weight:700; color:var(--accent); min-width:110px; }
  .btn-sel { font-size:.75rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; padding:.4rem .9rem; border-radius:.5rem; cursor:pointer; font-family:'DM Sans',sans-serif; transition:all .18s; border:1px solid; white-space:nowrap; }
  .btn-sel-all  { border-color:var(--accent);  color:var(--accent);  background:transparent; }
  .btn-sel-all:hover  { background:var(--accent);  color:#fff; }
  .btn-sel-none { border-color:var(--muted);   color:var(--sub);    background:transparent; }
  .btn-sel-none:hover { border-color:var(--accent2); color:var(--accent2); }
  .btn-sel-del  { border-color:var(--accent2); color:var(--accent2); background:transparent; }
  .btn-sel-del:hover  { background:var(--accent2); color:#fff; }
  .btn-sel-pub  { border-color:var(--green); color:#000; background:var(--green); margin-left:auto; display:none; align-items:center; gap:.4rem; font-weight:700; }
  .btn-sel-pub.show { display:inline-flex; }
  .btn-sel-pub:hover { opacity:.88; transform:translateY(-1px); box-shadow:0 4px 16px rgba(34,197,94,.3); }

  /* ── Grid ── */
  .grid-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; color:var(--sub); text-transform:uppercase; letter-spacing:.08em; margin-top:2.5rem; margin-bottom:1.25rem; display:flex; align-items:center; gap:.75rem; }
  .grid-title::after { content:''; flex:1; height:1px; background:var(--border); }
  .video-grid { display:grid; gap:1.25rem; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); }
  .video-card { background:var(--card); border:1px solid var(--border); border-radius:.875rem; overflow:hidden; transition:transform .25s,box-shadow .25s,border-color .25s; cursor:pointer; text-decoration:none; display:block; position:relative; }
  .video-card:hover { transform:translateY(-4px) scale(1.01); border-color:var(--accent); box-shadow:0 8px 32px rgba(108,99,255,.2); }
  .thumb-wrap { position:relative; aspect-ratio:16/9; background:var(--surface); overflow:hidden; }
  .thumb-wrap img { width:100%; height:100%; object-fit:cover; transition:opacity .3s; }
  .thumb-wrap img.loading { opacity:0; }
  .thumb-wrap img.loaded  { opacity:1; }
  .play-overlay { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0); transition:background .25s; }
  .video-card:hover .play-overlay { background:rgba(0,0,0,.35); }
  .play-btn { width:44px; height:44px; border-radius:50%; background:var(--accent); display:flex; align-items:center; justify-content:center; opacity:0; transform:scale(.7); transition:opacity .25s,transform .25s; box-shadow:0 0 20px rgba(108,99,255,.5); }
  .video-card:hover .play-btn { opacity:1; transform:scale(1); }
  .card-body { padding:.875rem 1rem 1rem; }
  .card-title { font-size:.875rem; font-weight:500; color:var(--text); line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  .card-meta { display:flex; gap:.75rem; margin-top:.5rem; }
  .meta-tag { font-size:.7rem; color:var(--sub); }

  /* Checkbox */
  .card-checkbox { position:absolute; top:.55rem; left:.55rem; z-index:5; width:20px; height:20px; border-radius:6px; border:2px solid rgba(255,255,255,.35); background:rgba(10,10,15,.6); backdrop-filter:blur(4px); cursor:pointer; display:flex; align-items:center; justify-content:center; transition:border-color .2s,background .2s,opacity .2s,transform .15s; opacity:0; }
  .video-card:hover .card-checkbox, .video-card.selected .card-checkbox { opacity:1; }
  .card-checkbox.checked { border-color:var(--accent); background:var(--accent); }
  .card-checkbox svg { display:none; }
  .card-checkbox.checked svg { display:block; }
  .card-checkbox:hover { transform:scale(1.1); }
  .video-card.selected { border-color:var(--accent); box-shadow:0 0 0 2px rgba(108,99,255,.35),0 8px 24px rgba(108,99,255,.15); }
  .video-card.selected .thumb-wrap::after { content:''; position:absolute; inset:0; background:rgba(108,99,255,.12); pointer-events:none; }

  /* States */
  .state-box { min-height:260px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.75rem; color:var(--muted); font-size:.95rem; }
  .state-box svg { opacity:.35; }
  .spinner { width:36px; height:36px; border-radius:50%; border:3px solid var(--border); border-top-color:var(--accent); animation:spin .8s linear infinite; }
  @keyframes spin { to { transform:rotate(360deg); } }

  /* ── Video modal ── */
  .modal-bg { display:none; position:fixed; inset:0; z-index:50; background:rgba(0,0,0,.8); backdrop-filter:blur(8px); align-items:center; justify-content:center; padding:1rem; }
  .modal-bg.open { display:flex; }
  .modal-box { background:var(--card); border:1px solid var(--border); border-radius:1.25rem; width:100%; max-width:860px; overflow:hidden; position:relative; box-shadow:0 32px 80px rgba(0,0,0,.7); }
  .modal-close { position:absolute; top:.75rem; right:.75rem; z-index:5; background:rgba(255,255,255,.08); border:none; color:var(--text); width:36px; height:36px; border-radius:50%; cursor:pointer; font-size:1.1rem; display:flex; align-items:center; justify-content:center; transition:background .2s; }
  .modal-close:hover { background:rgba(255,101,132,.2); color:var(--accent2); }
  .modal-embed { aspect-ratio:16/9; width:100%; }
  .modal-embed iframe { width:100%; height:100%; border:none; display:block; }
  .modal-info { padding:1rem 1.25rem; }
  .modal-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; }

  /* ── Publish modal ── */
  .pub-modal-bg { display:none; position:fixed; inset:0; z-index:60; background:rgba(0,0,0,.88); backdrop-filter:blur(14px); align-items:center; justify-content:center; padding:1rem; }
  .pub-modal-bg.open { display:flex; }
  .pub-modal { background:var(--card); border:1px solid var(--border); border-radius:1.5rem; width:100%; max-width:580px; padding:2rem; position:relative; box-shadow:0 0 80px rgba(34,197,94,.1),0 32px 80px rgba(0,0,0,.7); }
  .pub-modal h2 { font-family:'Syne',sans-serif; font-size:1.4rem; font-weight:800; letter-spacing:-.02em; }
  .pub-modal h2 span { color:var(--green); }
  .pub-close { position:absolute; top:.875rem; right:.875rem; background:rgba(255,255,255,.06); border:none; color:var(--sub); width:32px; height:32px; border-radius:50%; cursor:pointer; font-size:1rem; display:flex; align-items:center; justify-content:center; transition:all .2s; }
  .pub-close:hover { background:rgba(255,101,132,.15); color:var(--accent2); }

  .pub-summary { margin-top:1.25rem; padding:.75rem 1rem; background:rgba(108,99,255,.07); border:1px solid rgba(108,99,255,.2); border-radius:.75rem; font-size:.85rem; color:var(--sub); line-height:1.7; }
  .pub-summary strong { color:var(--text); font-weight:600; }

  /* Drop zone */
  .drop-zone { margin-top:1.5rem; border:2px dashed var(--border); border-radius:1rem; padding:2.5rem 1.5rem; text-align:center; cursor:pointer; transition:border-color .25s,background .25s; position:relative; }
  .drop-zone.drag-over { border-color:var(--green); background:rgba(34,197,94,.05); }
  .drop-zone input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
  .drop-zone-icon { font-size:2.5rem; }
  .drop-zone p { margin-top:.5rem; color:var(--sub); font-size:.875rem; }
  .drop-zone p strong { color:var(--text); }
  .file-chosen { margin-top:.75rem; font-size:.8rem; color:var(--green); font-weight:600; display:none; padding:.4rem .8rem; background:rgba(34,197,94,.1); border-radius:.5rem; border:1px solid rgba(34,197,94,.25); }
  .file-chosen.show { display:inline-block; }

  /* Pub log */
  .pub-log { margin-top:1.25rem; max-height:200px; overflow-y:auto; background:var(--surface); border:1px solid var(--border); border-radius:.75rem; padding:.75rem 1rem; display:none; font-size:.8rem; font-family:monospace; line-height:1.9; }
  .pub-log.show { display:block; }
  .log-ok   { color:var(--green); }
  .log-err  { color:var(--accent2); }
  .log-info { color:var(--sub); }

  .btn-pub-go { margin-top:1.5rem; width:100%; padding:.85rem; background:var(--green); color:#000; font-family:'Syne',sans-serif; font-size:.95rem; font-weight:800; letter-spacing:.03em; border:none; border-radius:.75rem; cursor:pointer; transition:opacity .2s,transform .15s; display:flex; align-items:center; justify-content:center; gap:.5rem; }
  .btn-pub-go:hover:not(:disabled) { opacity:.9; transform:translateY(-1px); box-shadow:0 6px 20px rgba(34,197,94,.3); }
  .btn-pub-go:disabled { opacity:.35; cursor:not-allowed; transform:none; }
</style>
</head>
<body class="page">

<?php if (empty($_SESSION['auth'])): ?>
<!-- ═══ LOGIN ═══ -->
<div class="login-wrap">
  <div class="login-card">
    <h1>Dood<span>Stream</span></h1>
    <p style="color:var(--sub);font-size:.9rem;margin-top:.5rem;">Connectez-vous pour accéder à votre médiathèque.</p>
    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" autocomplete="off">
      <div class="field"><label>Nom d'utilisateur</label><input type="text" name="username" placeholder="dibykre" required></div>
      <div class="field"><label>Mot de passe</label><input type="password" name="password" placeholder="••••••" required></div>
      <button type="submit" name="login" class="btn-primary">Connexion →</button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ═══ DASHBOARD ═══ -->
<header class="header">
  <div class="logo">Dood<span>Stream</span></div>
  <form method="POST"><button type="submit" name="logout" class="btn-logout">Déconnexion</button></form>
</header>

<main class="main">

  <!-- Filter -->
  <div class="filter-zone">
    <div class="filter-label">📁 Dossier</div>
    <select class="folder-select" id="folderSelect" disabled>
      <option value="">— Chargement des dossiers… —</option>
    </select>
    <div class="badge" id="countBadge" style="display:none"></div>
  </div>

  <!-- Selection toolbar -->
  <div class="sel-toolbar" id="selToolbar">
    <span class="sel-count" id="selCount">0 sélectionné(s)</span>
    <button class="btn-sel btn-sel-all"  onclick="selectAll()">✓ Tout sélectionner</button>
    <button class="btn-sel btn-sel-none" onclick="deselectAll()">✕ Tout désélectionner</button>
    <button class="btn-sel btn-sel-del"  onclick="deleteSelected()">🗑 Supprimer</button>
    <button class="btn-sel btn-sel-pub"  id="btnPublish" onclick="openPublish()">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V6M5 12l7-7 7 7"/></svg>
      Publier la sélection
    </button>
  </div>

  <div id="gridTitle" class="grid-title" style="display:none">Vidéos</div>
  <div id="videoGrid" class="video-grid"></div>
  <div id="stateBox" class="state-box">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7c0-1.1.9-2 2-2h3l2 3h9a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    <span>Sélectionnez un dossier pour afficher les vidéos</span>
  </div>
</main>

<!-- ═══ Video player modal ═══ -->
<div class="modal-bg" id="modal">
  <div class="modal-box">
    <button class="modal-close" id="modalClose">✕</button>
    <div class="modal-embed"><iframe id="modalFrame" src="" allowfullscreen allow="autoplay"></iframe></div>
    <div class="modal-info"><div class="modal-title" id="modalTitle"></div></div>
  </div>
</div>

<!-- ═══ Publish modal ═══ -->
<div class="pub-modal-bg" id="pubModal">
  <div class="pub-modal">
    <button class="pub-close" id="pubClose">✕</button>
    <h2>📤 Publier <span>les vidéos</span></h2>
    <p style="color:var(--sub);font-size:.875rem;margin-top:.5rem;line-height:1.6;">
      Déposez le fichier <strong style="color:var(--text)">ZIP</strong> exporté contenant les dossiers créateurs
      avec <code style="color:var(--accent);font-size:.8rem">metadata.json</code>, avatars et miniatures.
      Les vidéos sélectionnées seront associées par leur <code style="color:var(--accent);font-size:.8rem">room_id</code> et publiées en base.
    </p>

    <div class="pub-summary" id="pubSummary"></div>

    <div class="drop-zone" id="dropZone">
      <input type="file" id="zipInput" accept=".zip">
      <div class="drop-zone-icon">📦</div>
      <p><strong>Glissez-déposez</strong> votre ZIP ici</p>
      <p style="margin-top:.3rem;font-size:.78rem;">ou cliquez pour parcourir</p>
      <div class="file-chosen" id="fileChosen"></div>
    </div>

    <div class="pub-log" id="pubLog"></div>

    <button class="btn-pub-go" id="btnPubGo" disabled onclick="doPublish()">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 19V6M5 12l7-7 7 7"/></svg>
      Publier maintenant
    </button>
  </div>
</div>

<script>
const API = p => `${location.pathname}?ajax=${p}`;
let zipFile = null;

// ── Folders ───────────────────────────────────────────────────────────────────
async function loadFolders() {
  const sel = document.getElementById('folderSelect');
  try {
    const data = await fetch(API('folders')).then(r => r.json());
    sel.innerHTML = '<option value="">— Choisir un dossier —</option>';
    if (data.status === 200 && data.result) {
      const folders = data.result.folders || [];
      if (!folders.length) { sel.innerHTML += '<option disabled>Aucun dossier trouvé</option>'; }
      else folders.forEach(f => { const o=document.createElement('option'); o.value=f.fld_id; o.textContent=f.name; sel.appendChild(o); });
      sel.disabled = false;
    } else { sel.innerHTML = '<option disabled>Erreur de chargement</option>'; }
  } catch { sel.innerHTML = '<option disabled>Erreur réseau</option>'; }
}

// ── Selection ─────────────────────────────────────────────────────────────────
function updateSelectionUI() {
  const all = document.querySelectorAll('.video-card');
  const sel = document.querySelectorAll('.video-card.selected');
  const n   = sel.length;
  document.getElementById('selCount').textContent = n + ' sélectionné' + (n > 1 ? 's' : '');
  document.getElementById('selToolbar').classList.toggle('visible', all.length > 0);
  document.getElementById('btnPublish').classList.toggle('show', n > 0);
}
function toggleCard(card) {
  card.classList.toggle('selected');
  card.querySelector('.card-checkbox').classList.toggle('checked', card.classList.contains('selected'));
  updateSelectionUI();
}
function selectAll()   { document.querySelectorAll('.video-card').forEach(c => { c.classList.add('selected');    c.querySelector('.card-checkbox').classList.add('checked');    }); updateSelectionUI(); }
function deselectAll() { document.querySelectorAll('.video-card').forEach(c => { c.classList.remove('selected'); c.querySelector('.card-checkbox').classList.remove('checked'); }); updateSelectionUI(); }
function deleteSelected() {
  const sel = document.querySelectorAll('.video-card.selected');
  if (!sel.length) return;
  if (!confirm(`Supprimer ${sel.length} vidéo(s) ? Action irréversible.`)) return;
  sel.forEach(c => { c.style.transition='opacity .3s,transform .3s'; c.style.opacity='0'; c.style.transform='scale(.9)'; setTimeout(()=>c.remove(),300); });
  setTimeout(() => {
    updateSelectionUI();
    if (!document.querySelector('.video-card')) {
      document.getElementById('gridTitle').style.display='none';
      document.getElementById('countBadge').style.display='none';
      showState(`<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg><span>Aucune vidéo dans ce dossier</span>`);
    }
  }, 320);
}

// ── Files ─────────────────────────────────────────────────────────────────────
async function loadFiles(fldId) {
  const grid  = document.getElementById('videoGrid');
  const title = document.getElementById('gridTitle');
  const badge = document.getElementById('countBadge');
  grid.innerHTML=''; title.style.display='none'; badge.style.display='none';
  document.getElementById('selToolbar').classList.remove('visible');
  showState('<div class="spinner"></div><span>Chargement…</span>');
  try {
    const data = await fetch(API('files')+'&fld_id='+encodeURIComponent(fldId)).then(r=>r.json());
    hideState();
    if (data.status===200 && data.result?.files?.length) {
      const files = data.result.files;
      title.style.display='flex'; title.textContent='Vidéos';
      badge.style.display='inline-flex';
      badge.textContent = files.length+' vidéo'+(files.length>1?'s':'');
      files.forEach(f => {
        const thumb = f.single_img||f.splash_img||'';
        const embed = 'https://dood.so/e/'+f.file_code;
        const mins  = f.length ? Math.floor(f.length/60)+'min' : '';
        const views = f.views  ? f.views+' vues' : '';
        const card  = document.createElement('a');
        card.className='video-card'; card.href='#';
        card.dataset.embed=embed; card.dataset.code=f.file_code; card.dataset.title=f.title||'Sans titre';
        card.innerHTML=`
          <div class="card-checkbox" title="Sélectionner">
            <svg width="11" height="11" viewBox="0 0 12 12" fill="none">
              <polyline points="1.5,6 5,9.5 10.5,2.5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="thumb-wrap">
            ${thumb?`<img src="${thumb}" loading="lazy" class="loading" onload="this.classList.remove('loading');this.classList.add('loaded');" onerror="this.style.display='none'">`:''}
            <div class="play-overlay"><div class="play-btn"><svg width="16" height="16" viewBox="0 0 24 24" fill="white"><polygon points="5,3 19,12 5,21"/></svg></div></div>
          </div>
          <div class="card-body">
            <div class="card-title">${escHtml(f.title||'Sans titre')}</div>
            <div class="card-meta">
              ${mins  ? `<span class="meta-tag">⏱ ${mins}</span>` : ''}
              ${views ? `<span class="meta-tag">👁 ${views}</span>` : ''}
            </div>
          </div>`;
        card.querySelector('.card-checkbox').addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); toggleCard(card); });
        card.addEventListener('click', e => { e.preventDefault(); openModal(embed, f.title||'Sans titre'); });
        grid.appendChild(card);
      });
      updateSelectionUI();
    } else {
      showState(`<svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg><span>Aucune vidéo dans ce dossier</span>`);
    }
  } catch { showState(`<span style="color:var(--accent2)">Erreur lors du chargement</span>`); }
}

function showState(html) { const s=document.getElementById('stateBox'); s.innerHTML=html; s.style.display='flex'; }
function hideState()     { document.getElementById('stateBox').style.display='none'; }

// ── Video player modal ────────────────────────────────────────────────────────
function openModal(embedUrl, title) { document.getElementById('modalFrame').src=embedUrl; document.getElementById('modalTitle').textContent=title; document.getElementById('modal').classList.add('open'); }
function closeModal() { document.getElementById('modalFrame').src=''; document.getElementById('modal').classList.remove('open'); }
document.getElementById('modalClose').addEventListener('click', closeModal);
document.getElementById('modal').addEventListener('click', e => { if(e.target===e.currentTarget) closeModal(); });

// ── Publish modal ─────────────────────────────────────────────────────────────
function openPublish() {
  const sel = [...document.querySelectorAll('.video-card.selected')];
  if (!sel.length) return;
  const names = sel.slice(0,5).map(c => '· '+escHtml(c.dataset.title||c.dataset.code));
  const more  = sel.length > 5 ? `<em style="color:var(--muted)"> + ${sel.length-5} autres…</em>` : '';
  document.getElementById('pubSummary').innerHTML =
    `<strong>${sel.length} vidéo(s) à publier :</strong><br>${names.join('<br>')}${more}`;
  document.getElementById('pubLog').innerHTML='';
  document.getElementById('pubLog').classList.remove('show');
  document.getElementById('fileChosen').textContent='';
  document.getElementById('fileChosen').classList.remove('show');
  document.getElementById('btnPubGo').disabled=true;
  document.getElementById('btnPubGo').textContent='Publier maintenant';
  document.getElementById('btnPubGo').style.background='var(--green)';
  zipFile=null;
  document.getElementById('pubModal').classList.add('open');
}
function closePublish() { document.getElementById('pubModal').classList.remove('open'); }
document.getElementById('pubClose').addEventListener('click', closePublish);
document.getElementById('pubModal').addEventListener('click', e => { if(e.target===e.currentTarget) closePublish(); });

// Drop zone
const dropZone = document.getElementById('dropZone');
const zipInput = document.getElementById('zipInput');
dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('drag-over'); handleZip(e.dataTransfer.files[0]); });
zipInput.addEventListener('change', () => handleZip(zipInput.files[0]));

function handleZip(file) {
  if (!file || !file.name.toLowerCase().endsWith('.zip')) { alert('Veuillez sélectionner un fichier .zip valide'); return; }
  zipFile = file;
  const fc = document.getElementById('fileChosen');
  fc.textContent = '📦 '+file.name+' — '+(file.size/1024/1024).toFixed(2)+' Mo';
  fc.classList.add('show');
  document.getElementById('btnPubGo').disabled = false;
}

// ── Do publish ────────────────────────────────────────────────────────────────
async function doPublish() {
  if (!zipFile) return;
  const btn = document.getElementById('btnPubGo');
  const log = document.getElementById('pubLog');
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner" style="width:17px;height:17px;border-width:2px"></div>&nbsp; Publication en cours…';
  log.innerHTML = '<span class="log-info">⏳ Lecture du fichier ZIP…</span>';
  log.classList.add('show');

  const selected = [...document.querySelectorAll('.video-card.selected')].map(c => ({
    code:  c.dataset.code,
    title: c.dataset.title,
  }));

  let b64;
  try {
    b64 = await new Promise((res, rej) => {
      const reader = new FileReader();
      reader.onload  = () => res(reader.result.split(',')[1]);
      reader.onerror = () => rej('Lecture échouée');
      reader.readAsDataURL(zipFile);
    });
  } catch(e) {
    log.innerHTML += `<div class="log-err">❌ ${e}</div>`;
    btn.disabled=false; btn.innerHTML='Réessayer'; return;
  }

  log.innerHTML += '<div class="log-info">📡 Envoi au serveur…</div>';

  try {
    const data = await fetch(API('publish'), {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ zip_b64: b64, selected_codes: selected }),
    }).then(r=>r.json());

    if (data.ok) {
      (data.published||[]).forEach(p => {
        log.innerHTML += `<div class="log-ok">✅ <strong>${escHtml(p.title||p.video)}</strong> → <code>${p.dood}</code> — créateur : ${escHtml(p.creator)} — 📁 déplacé vers <em>publiee</em></div>`;
      });
      (data.errors||[]).forEach(e => {
        log.innerHTML += `<div class="log-err">⚠️ ${escHtml(e)}</div>`;
      });
      const n = (data.published||[]).length;
      log.innerHTML += `<div class="log-ok" style="margin-top:.5rem;font-size:.85rem;font-weight:700;border-top:1px solid rgba(34,197,94,.2);padding-top:.5rem">🎉 Terminé — ${n} vidéo(s) publiée(s) en base de données</div>`;
      btn.innerHTML='✅ Publication réussie !';
      btn.style.background='#166534'; btn.style.color='#bbf7d0';
    } else {
      log.innerHTML += `<div class="log-err">❌ Erreur : ${escHtml(data.error||'inconnue')}</div>`;
      btn.disabled=false; btn.innerHTML='Réessayer';
    }
  } catch(e) {
    log.innerHTML += `<div class="log-err">❌ Erreur réseau : ${escHtml(String(e))}</div>`;
    btn.disabled=false; btn.innerHTML='Réessayer';
  }
}

// ── Folder select ─────────────────────────────────────────────────────────────
document.getElementById('folderSelect').addEventListener('change', function() {
  if (this.value) { loadFiles(this.value); }
  else {
    document.getElementById('videoGrid').innerHTML='';
    document.getElementById('gridTitle').style.display='none';
    document.getElementById('countBadge').style.display='none';
    document.getElementById('selToolbar').classList.remove('visible');
    showState(`<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7c0-1.1.9-2 2-2h3l2 3h9a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg><span>Sélectionnez un dossier pour afficher les vidéos</span>`);
  }
});

function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

loadFolders();
</script>

<?php endif; ?>
</body>
</html>