<?php
// ─── Configuration ────────────────────────────────────────────────────────────
define('UI_USER',     'dibykre');
define('UI_PASSWORD', '4696');
define('IMPORT_API_KEY', 'mediaforge_import_2026');

session_start();

// ─── Connexion DB ─────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/file_path_helper.php';

// ─── Authentification ─────────────────────────────────────────────────────────
$authError = '';
if (isset($_POST['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
if (isset($_POST['login'])) {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');
    if ($u === UI_USER && $p === UI_PASSWORD) {
        $_SESSION['logged_in'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $authError = 'Identifiants incorrects.';
    }
}
$loggedIn = !empty($_SESSION['logged_in']);

// ─── Traitement import (AJAX JSON) ───────────────────────────────────────────
if ($loggedIn && isset($_POST['action']) && $_POST['action'] === 'import') {
    header('Content-Type: application/json; charset=utf-8');

    if (empty($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 400, 'msg' => 'Fichier ZIP manquant ou erreur upload']);
        exit;
    }

    $uploadedZip = $_FILES['zip_file']['tmp_name'];
    $tmpRoot     = sys_get_temp_dir() . '/live_import_' . uniqid();
    mkdir($tmpRoot, 0755, true);
    $tmpDirs = [$tmpRoot];

    try {
        $zip = new ZipArchive();
        if ($zip->open($uploadedZip) !== true) {
            throw new RuntimeException('Impossible d\'ouvrir le fichier ZIP');
        }
        $zip->extractTo($tmpRoot);
        $zip->close();

        $creatorDirs = detect_creator_dirs($tmpRoot, $tmpDirs);

        if (empty($creatorDirs)) {
            throw new RuntimeException('Aucun dossier créateur valide trouvé (published.json manquant)');
        }

        $results = [];
        foreach ($creatorDirs as $dir) {
            $results[] = import_creator($dir, $pdo);
        }

        $ok = array_filter($results, fn($r) => $r['status'] === 'ok');
        echo json_encode([
            'status'   => 200,
            'msg'      => sprintf('%d/%d créateur(s) importé(s) avec succès', count($ok), count($results)),
            'total'    => count($results),
            'imported' => $results,
        ], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        echo json_encode(['status' => 500, 'msg' => $e->getMessage()]);
    } finally {
        foreach (array_reverse($tmpDirs) as $d) {
            if (is_dir($d)) rrmdir($d);
        }
    }
    exit;
}

// ─── Chargement stats (pour le dashboard) ────────────────────────────────────
$stats = ['creators' => 0, 'videos' => 0, 'tasks' => 0];
$recentVideos = [];
if ($loggedIn) {
    try {
        $stats['creators'] = (int)$pdo->query("SELECT COUNT(*) FROM creators")->fetchColumn();
        $stats['videos']   = (int)$pdo->query("SELECT COUNT(*) FROM videos")->fetchColumn();
        $stats['tasks']    = (int)$pdo->query("SELECT COUNT(*) FROM video_playback_tasks")->fetchColumn();
        $recentVideos = $pdo->query("
            SELECT v.id, v.title, v.duration, v.day_folder, v.published_at,
                   c.id AS creator_id, c.name AS creator_name
            FROM videos v
            LEFT JOIN creators c ON c.id = v.creator_id
            ORDER BY v.created_at DESC
            LIMIT 8
        ")->fetchAll();
    } catch (Throwable $e) {}
}

// ═══════════════════════════════════════════════════════════════════════════════
// FONCTIONS IMPORT
// ═══════════════════════════════════════════════════════════════════════════════

function detect_creator_dirs(string $tmpRoot, array &$tmpDirs): array
{
    if (file_exists($tmpRoot . '/published.json')) return [$tmpRoot];

    $innerZips = glob($tmpRoot . '/*.zip');
    if (!empty($innerZips)) {
        $innerTmp  = sys_get_temp_dir() . '/live_inner_' . uniqid();
        mkdir($innerTmp, 0755, true);
        $tmpDirs[] = $innerTmp;
        $zip = new ZipArchive();
        if ($zip->open($innerZips[0]) === true) { $zip->extractTo($innerTmp); $zip->close(); }
        return find_subdirs_with_json($innerTmp);
    }

    return find_subdirs_with_json($tmpRoot);
}

function find_subdirs_with_json(string $dir): array
{
    $found = [];
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $dir . '/' . $item;
        if (is_dir($full) && file_exists($full . '/published.json')) $found[] = $full;
    }
    return $found;
}

function import_creator(string $dir, PDO $pdo): array
{
    $jsonPath = $dir . '/published.json';
    if (!file_exists($jsonPath)) return ['creator_id'=>null,'video_id'=>null,'tasks_inserted'=>0,'status'=>'error','msg'=>'published.json manquant'];

    try {
        $data = json_decode(file_get_contents($jsonPath), true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        return ['creator_id'=>null,'video_id'=>null,'tasks_inserted'=>0,'status'=>'error','msg'=>'JSON invalide : '.$e->getMessage()];
    }

    $username      = trim($data['username']         ?? '');
    $displayName   = trim($data['display_name']     ?? $username);
    $signature     = trim($data['signature']        ?? '');
    $title         = trim($data['title']            ?? '');
    $description   = trim($data['description']      ?? '');
    $doodCode      = trim($data['doodstream_code']  ?? '');
    $doodEmbed     = trim($data['doodstream_embed'] ?? '');
    $durationSec   = (int)($data['duration']        ?? 0);
    $recordedAt    = trim($data['recorded_at']      ?? '');
    $publishedAt   = trim($data['published_at']     ?? '');
    $dayFolder     = trim($data['day_folder']       ?? date('d_m_Y'));
    $sourceTs      = trim($data['source_ts']        ?? '');
    $ytInfo        = $data['yt_dlp_info']           ?? [];
    $uploaderId    = trim($ytInfo['uploader_id']    ?? '');
    $uploaderUrl   = trim($ytInfo['uploader_url']   ?? '');
    $webpageUrl    = trim($ytInfo['webpage_url']    ?? '');
    $viewCount     = (int)($ytInfo['view_count']    ?? 0);
    $likeCount     = (int)($ytInfo['like_count']    ?? 0);

    if ($username === '' || $doodCode === '') {
        return ['creator_id'=>$username?:null,'video_id'=>null,'tasks_inserted'=>0,'status'=>'error','msg'=>'username ou doodstream_code manquant'];
    }

    $durationStr      = sprintf('%02d:%02d', intdiv($durationSec, 60), $durationSec % 60);
    $recordedAtFinal  = $recordedAt  !== '' ? $recordedAt  : $publishedAt;
    $publishedAtFinal = $publishedAt !== '' ? $publishedAt : date('Y-m-d H:i:s');

    $siteRoot   = realpath(__DIR__ . '/../../');
    $imgDestDir = $siteRoot . '/files/images/' . $dayFolder . '/' . $username . '/';
    $vidDestDir = $siteRoot . '/files/videos/'  . $dayFolder . '/' . $username . '/';
    foreach ([$imgDestDir, $vidDestDir] as $d) { if (!is_dir($d)) mkdir($d, 0755, true); }

    $avatarPath = move_if_exists($dir, $data['profil_pic'] ?? null, $imgDestDir, 'images/'.$dayFolder.'/'.$username.'/');
    $coverPath  = move_if_exists($dir, $data['cover']      ?? null, $imgDestDir, 'images/'.$dayFolder.'/'.$username.'/');
    $thumbPath  = move_if_exists($dir, $data['thumbnail']  ?? null, $imgDestDir, 'images/'.$dayFolder.'/'.$username.'/');
    $spritePath = move_if_exists($dir, $data['sprite']     ?? null, $imgDestDir, 'images/'.$dayFolder.'/'.$username.'/');
    $demoPath   = move_if_exists($dir, $data['demo']       ?? null, $vidDestDir, 'videos/' .$dayFolder.'/'.$username.'/');

    $transcriptContent = null;
    $transcriptFile    = $dir . '/transcript.txt';
    if (file_exists($transcriptFile)) $transcriptContent = file_get_contents($transcriptFile);

    try {
        $pdo->prepare("
            INSERT INTO creators (id,name,profile_name,platform,description,avatar_url,cover_url,uploader_id,uploader_url,webpage_url,created_at,updated_at)
            VALUES (:id,:name,:profile_name,'tiktok',:description,:avatar_url,:cover_url,:uploader_id,:uploader_url,:webpage_url,NOW(),NOW())
            ON DUPLICATE KEY UPDATE
                name=VALUES(name),profile_name=VALUES(profile_name),
                description=IF(VALUES(description)<>'',VALUES(description),description),
                avatar_url=IF(VALUES(avatar_url) IS NOT NULL,VALUES(avatar_url),avatar_url),
                cover_url=IF(VALUES(cover_url) IS NOT NULL,VALUES(cover_url),cover_url),
                uploader_id=IF(VALUES(uploader_id)<>'',VALUES(uploader_id),uploader_id),
                uploader_url=IF(VALUES(uploader_url)<>'',VALUES(uploader_url),uploader_url),
                webpage_url=IF(VALUES(webpage_url)<>'',VALUES(webpage_url),webpage_url),
                updated_at=NOW()
        ")->execute([':id'=>$username,':name'=>$displayName,':profile_name'=>$username,':description'=>$signature,':avatar_url'=>$avatarPath,':cover_url'=>$coverPath,':uploader_id'=>$uploaderId,':uploader_url'=>$uploaderUrl,':webpage_url'=>$webpageUrl]);
    } catch (PDOException $e) {
        return ['creator_id'=>$username,'video_id'=>null,'tasks_inserted'=>0,'status'=>'error','msg'=>'DB creators : '.$e->getMessage()];
    }

    try {
        $pdo->prepare("
            INSERT INTO videos (id,creator_id,title,description,thumbnail_url,video_url,sprite_url,demo_url,doodstream_code,doodstream_embed,duration,views,view_count,like_count,transcript,source_filename,recorded_at,published_at,day_folder,created_at)
            VALUES (:id,:creator_id,:title,:description,:thumbnail_url,:video_url,:sprite_url,:demo_url,:doodstream_code,:doodstream_embed,:duration,'0',:view_count,:like_count,:transcript,:source_filename,:recorded_at,:published_at,:day_folder,NOW())
            ON DUPLICATE KEY UPDATE
                title=VALUES(title),description=VALUES(description),
                thumbnail_url=IF(VALUES(thumbnail_url) IS NOT NULL,VALUES(thumbnail_url),thumbnail_url),
                sprite_url=IF(VALUES(sprite_url) IS NOT NULL,VALUES(sprite_url),sprite_url),
                demo_url=IF(VALUES(demo_url) IS NOT NULL,VALUES(demo_url),demo_url),
                doodstream_embed=VALUES(doodstream_embed),duration=VALUES(duration),
                view_count=VALUES(view_count),like_count=VALUES(like_count),
                transcript=IF(VALUES(transcript) IS NOT NULL,VALUES(transcript),transcript),
                source_filename=VALUES(source_filename),published_at=VALUES(published_at),day_folder=VALUES(day_folder)
        ")->execute([':id'=>$doodCode,':creator_id'=>$username,':title'=>$title,':description'=>$description,':thumbnail_url'=>$thumbPath,':video_url'=>$doodEmbed,':sprite_url'=>$spritePath,':demo_url'=>$demoPath,':doodstream_code'=>$doodCode,':doodstream_embed'=>$doodEmbed,':duration'=>$durationStr,':view_count'=>$viewCount,':like_count'=>$likeCount,':transcript'=>$transcriptContent,':source_filename'=>$sourceTs,':recorded_at'=>($recordedAtFinal!==''?$recordedAtFinal:null),':published_at'=>$publishedAtFinal,':day_folder'=>$dayFolder]);
    } catch (PDOException $e) {
        return ['creator_id'=>$username,'video_id'=>null,'tasks_inserted'=>0,'status'=>'error','msg'=>'DB videos : '.$e->getMessage()];
    }

    $tasksInserted = 0;
    if ($transcriptContent !== null && trim($transcriptContent) !== '') {
        $pdo->prepare("DELETE FROM video_playback_tasks WHERE video_id = ?")->execute([$doodCode]);
        $lines   = explode("\n", $transcriptContent);
        $pattern = '/^\[(\d{2}:\d{2}:\d{2})\.\d+\s*→\s*(\d{2}:\d{2}:\d{2})\.\d+\]\s*(.+)$/u';
        $stmt    = $pdo->prepare("INSERT INTO video_playback_tasks (video_id,timecode,label,position) VALUES (:video_id,:timecode,:label,:position)");
        foreach ($lines as $pos => $line) {
            $line = trim($line);
            if ($line === '' || !preg_match($pattern, $line, $m)) continue;
            $label = trim($m[3]);
            if ($label === '') continue;
            $stmt->execute([':video_id'=>$doodCode,':timecode'=>$m[1],':label'=>$label,':position'=>$pos]);
            $tasksInserted++;
        }
    }

    return ['creator_id'=>$username,'video_id'=>$doodCode,'tasks_inserted'=>$tasksInserted,'status'=>'ok'];
}

function move_if_exists(string $srcDir, ?string $filename, string $destDir, string $shortPrefix): ?string
{
    if ($filename === null || $filename === '') return null;
    $src = $srcDir . '/' . $filename;
    if (!file_exists($src)) return null;
    $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($filename));
    $dest     = $destDir . $safeName;
    if (!file_exists($dest)) copy($src, $dest);
    return $shortPrefix . $safeName;
}

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        is_dir($path) ? rrmdir($path) : unlink($path);
    }
    rmdir($dir);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaForge — Import Live</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: '#FF0050',
                        dark:  { DEFAULT: '#0D0F14', card: '#161921', border: '#2A2F42' }
                    }
                }
            }
        }
    </script>
    <style>
        body { background: #0D0F14; }
        .glass { background: rgba(255,255,255,0.04); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); }
        .drop-zone { border: 2px dashed #2A2F42; transition: all .2s; }
        .drop-zone.dragover { border-color: #FF0050; background: rgba(255,0,80,0.06); }
        .log-line { font-family: 'Consolas', monospace; font-size: 13px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner { animation: spin 1s linear infinite; }
        .fade-in { animation: fadeIn .3s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body class="dark text-white min-h-screen">

<?php if (!$loggedIn): ?>
<!-- ════════════════════════════════════════════════════════════════════════════
     PAGE LOGIN
════════════════════════════════════════════════════════════════════════════ -->
<div class="min-h-screen flex items-center justify-center px-4">
    <div class="glass rounded-2xl p-8 w-full max-w-sm space-y-6 fade-in">
        <div class="text-center">
            <div class="text-4xl mb-3">⚡</div>
            <h1 class="text-2xl font-bold">MediaForge</h1>
            <p class="text-gray-400 text-sm mt-1">Panneau d'import Live</p>
        </div>

        <?php if ($authError): ?>
        <div class="bg-red-500/10 border border-red-500/30 rounded-xl px-4 py-3 text-red-400 text-sm text-center">
            <?= htmlspecialchars($authError) ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1">Utilisateur</label>
                <input type="text" name="username" required autofocus
                       class="w-full bg-dark-border/40 border border-dark-border rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-brand"
                       placeholder="dibykre">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Mot de passe</label>
                <input type="password" name="password" required
                       class="w-full bg-dark-border/40 border border-dark-border rounded-xl px-4 py-3 text-white placeholder-gray-600 focus:outline-none focus:border-brand"
                       placeholder="••••">
            </div>
            <button type="submit" name="login"
                    class="w-full bg-brand hover:bg-brand/80 text-white font-bold py-3 rounded-xl transition">
                Connexion
            </button>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ════════════════════════════════════════════════════════════════════════════
     DASHBOARD
════════════════════════════════════════════════════════════════════════════ -->

<!-- Navbar -->
<nav class="glass border-b border-dark-border sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-2xl">⚡</span>
            <div>
                <span class="font-bold text-white">MediaForge</span>
                <span class="text-gray-500 text-sm ml-2">Import Live</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-400">👤 <?= htmlspecialchars(UI_USER) ?></span>
            <form method="POST">
                <button type="submit" name="logout"
                        class="text-sm text-gray-400 hover:text-brand transition px-3 py-1 rounded-lg border border-dark-border hover:border-brand">
                    Déconnexion
                </button>
            </form>
        </div>
    </div>
</nav>

<main class="max-w-6xl mx-auto px-4 py-8 space-y-8">

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
        <?php foreach ([
            ['👤', 'Créateurs',  $stats['creators'], '#4F8EF7'],
            ['🎬', 'Vidéos',     $stats['videos'],   '#00D4AA'],
            ['📋', 'Tâches',     $stats['tasks'],    '#A259FF'],
        ] as [$icon, $label, $val, $color]): ?>
        <div class="glass rounded-2xl p-5 text-center">
            <div class="text-3xl mb-1"><?= $icon ?></div>
            <div class="text-3xl font-bold" style="color:<?= $color ?>"><?= number_format($val) ?></div>
            <div class="text-sm text-gray-400 mt-1"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Zone d'import -->
    <div class="glass rounded-2xl p-6 space-y-5">
        <div class="flex items-center gap-3">
            <span class="text-2xl">📦</span>
            <div>
                <h2 class="text-xl font-bold">Importer un ZIP</h2>
                <p class="text-sm text-gray-400">Créateur unique • Lot de créateurs • ZIP imbriqué</p>
            </div>
        </div>

        <!-- Drop zone -->
        <div id="dropZone" class="drop-zone rounded-2xl p-10 text-center cursor-pointer"
             onclick="document.getElementById('zipInput').click()">
            <div class="text-5xl mb-3">🗜️</div>
            <p class="text-gray-300 font-semibold">Glissez votre ZIP ici</p>
            <p class="text-gray-500 text-sm mt-1">ou cliquez pour sélectionner</p>
            <p id="selectedFile" class="text-brand text-sm mt-3 font-mono hidden"></p>
        </div>
        <input type="file" id="zipInput" accept=".zip" class="hidden">

        <!-- Bouton import -->
        <button id="importBtn"
                class="w-full bg-brand hover:bg-brand/80 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold py-4 rounded-xl transition flex items-center justify-center gap-3"
                disabled>
            <span id="importBtnIcon">🚀</span>
            <span id="importBtnText">Lancer l'import</span>
        </button>

        <!-- Barre de progression -->
        <div id="progressBar" class="hidden">
            <div class="flex items-center justify-between text-sm text-gray-400 mb-2">
                <span id="progressLabel">Traitement en cours…</span>
                <svg class="spinner w-4 h-4 text-brand" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
            </div>
            <div class="h-2 bg-dark-border rounded-full overflow-hidden">
                <div class="h-full bg-brand rounded-full animate-pulse w-full"></div>
            </div>
        </div>
    </div>

    <!-- Résultats -->
    <div id="results" class="hidden space-y-4 fade-in">
        <h2 class="text-xl font-bold flex items-center gap-2">
            <span>📊</span> Résultat de l'import
        </h2>
        <div id="resultSummary" class="glass rounded-2xl p-5"></div>
        <div id="resultDetails" class="space-y-3"></div>
    </div>

    <!-- Vidéos récentes -->
    <?php if (!empty($recentVideos)): ?>
    <div class="space-y-4">
        <h2 class="text-xl font-bold flex items-center gap-2">
            <span>🎬</span> Dernières vidéos importées
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($recentVideos as $v): ?>
            <div class="glass rounded-xl p-4 space-y-2 hover:border-brand/40 transition">
                <p class="text-sm font-semibold text-white line-clamp-2"><?= htmlspecialchars($v['title']) ?></p>
                <p class="text-xs text-gray-400">👤 <?= htmlspecialchars($v['creator_name'] ?? '—') ?></p>
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span>⏱ <?= htmlspecialchars($v['duration'] ?? '—') ?></span>
                    <span>📁 <?= htmlspecialchars($v['day_folder'] ?? '—') ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<script>
const dropZone   = document.getElementById('dropZone');
const zipInput   = document.getElementById('zipInput');
const importBtn  = document.getElementById('importBtn');
const selectedFile = document.getElementById('selectedFile');
const progressBar  = document.getElementById('progressBar');
const progressLabel = document.getElementById('progressLabel');
const resultsDiv   = document.getElementById('results');
const resultSummary = document.getElementById('resultSummary');
const resultDetails = document.getElementById('resultDetails');

let selectedZip = null;

// ── Drag & Drop ───────────────────────────────────────────────────────────────
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.name.endsWith('.zip')) setFile(file);
});

zipInput.addEventListener('change', () => {
    if (zipInput.files[0]) setFile(zipInput.files[0]);
});

function setFile(file) {
    selectedZip = file;
    const sizeMb = (file.size / 1024 / 1024).toFixed(1);
    selectedFile.textContent = `📎 ${file.name}  (${sizeMb} Mo)`;
    selectedFile.classList.remove('hidden');
    importBtn.disabled = false;
}

// ── Import ────────────────────────────────────────────────────────────────────
importBtn.addEventListener('click', async () => {
    if (!selectedZip) return;

    importBtn.disabled = true;
    document.getElementById('importBtnText').textContent = 'Import en cours…';
    document.getElementById('importBtnIcon').textContent = '⏳';
    progressBar.classList.remove('hidden');
    progressLabel.textContent = `Envoi de ${selectedZip.name}…`;
    resultsDiv.classList.add('hidden');

    const formData = new FormData();
    formData.append('action',   'import');
    formData.append('zip_file', selectedZip);

    try {
        progressLabel.textContent = 'Extraction et traitement du ZIP…';
        const res  = await fetch(window.location.href, { method: 'POST', body: formData });
        const data = await res.json();
        showResults(data);
    } catch (e) {
        showResults({ status: 500, msg: 'Erreur réseau : ' + e.message });
    } finally {
        progressBar.classList.add('hidden');
        importBtn.disabled = false;
        document.getElementById('importBtnText').textContent = 'Lancer l\'import';
        document.getElementById('importBtnIcon').textContent = '🚀';
    }
});

// ── Affichage résultats ────────────────────────────────────────────────────────
function showResults(data) {
    resultsDiv.classList.remove('hidden');
    resultsDiv.classList.add('fade-in');

    if (data.status !== 200) {
        resultSummary.innerHTML = `
            <div class="flex items-center gap-3 text-red-400">
                <span class="text-2xl">❌</span>
                <div>
                    <p class="font-bold">Erreur</p>
                    <p class="text-sm">${escHtml(data.msg)}</p>
                </div>
            </div>`;
        resultDetails.innerHTML = '';
        return;
    }

    const ok  = (data.imported || []).filter(r => r.status === 'ok').length;
    const err = (data.total || 0) - ok;

    resultSummary.innerHTML = `
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <span class="text-3xl">${ok > 0 ? '✅' : '⚠️'}</span>
                <div>
                    <p class="font-bold text-lg">${escHtml(data.msg)}</p>
                    <p class="text-sm text-gray-400">Total : ${data.total} créateur(s)</p>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-green-500/15 text-green-400">${ok} succès</span>
                ${err > 0 ? `<span class="px-3 py-1 rounded-full text-sm font-semibold bg-red-500/15 text-red-400">${err} erreur(s)</span>` : ''}
            </div>
        </div>`;

    resultDetails.innerHTML = (data.imported || []).map(r => {
        const isOk = r.status === 'ok';
        return `
        <div class="glass rounded-xl p-4 flex items-start gap-4 fade-in">
            <span class="text-2xl mt-0.5">${isOk ? '✅' : '❌'}</span>
            <div class="flex-1 min-w-0">
                <p class="font-semibold ${isOk ? 'text-white' : 'text-red-400'}">
                    ${escHtml(r.creator_id || 'Inconnu')}
                </p>
                ${isOk ? `
                <div class="flex flex-wrap gap-3 mt-2">
                    <span class="text-xs px-2 py-1 rounded-lg bg-white/5 text-gray-300">🎬 ${escHtml(r.video_id)}</span>
                    <span class="text-xs px-2 py-1 rounded-lg bg-white/5 text-gray-300">📋 ${r.tasks_inserted} tâches</span>
                </div>` : `
                <p class="text-sm text-red-400/80 mt-1">${escHtml(r.msg || '')}</p>`}
            </div>
        </div>`;
    }).join('');

    // Rafraîchir les stats après import réussi
    if (ok > 0) setTimeout(() => window.location.reload(), 3000);
}

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php endif; ?>
</body>
</html>