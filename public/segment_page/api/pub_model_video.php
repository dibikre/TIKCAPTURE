<?php
/**
 * pub_model_video.php — API de publication d'une vidéo live
 * URL : https://tikcapture.live/segment_page/api/pub_model_video.php
 *
 * Méthode : POST (multipart/form-data ou application/json)
 *
 * Champs attendus (POST) :
 *   username          string  — handle TikTok (@username)
 *   display_name      string  — nom affiché
 *   signature         string  — bio / description profil
 *   title             string  — titre du live
 *   description       string  — description du live
 *   doodstream_code   string  — filecode Doodstream de la vidéo principale
 *   doodstream_embed  string  — URL embed Doodstream
 *   duration          int     — durée en secondes
 *   recorded_at       string  — datetime ISO (ex: 2025-07-12 14:30:00)
 *   day_folder        string  — dossier jour ex: 12_07_2025
 *
 * Fichiers uploadés (multipart) :
 *   thumbnail         file    — vignette (miniature dernière vidéo = photo de profil)
 *   cover             file    — photo de couverture TikTok
 *   sprite            file    — sprite 6x6
 *   demo              file    — vidéo démo
 *
 * Réponse JSON :
 *   {"status":200,"msg":"OK","video_id":42,"model_id":7}
 *   {"status":4xx,"msg":"Erreur..."}
 */

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => 405, "msg" => "Méthode non autorisée (POST requis)"]);
    exit;
}

// ─── Chargement config DB ────────────────────────────────────
$config_path = __DIR__ . '/../../config/db.php';
if (!file_exists($config_path)) {
    http_response_code(500);
    echo json_encode(["status" => 500, "msg" => "config/db.php introuvable"]);
    exit;
}
require_once $config_path;  // injecte $pdo

// ─── Helpers ─────────────────────────────────────────────────

function post(string $key, string $default = ''): string {
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function err(int $code, string $msg): void {
    http_response_code($code);
    echo json_encode(["status" => $code, "msg" => $msg]);
    exit;
}

/**
 * Déplace un fichier uploadé vers $dest_path (créé les dossiers si besoin).
 * Retourne le chemin relatif depuis la racine du site, ou null si pas de fichier.
 */
function save_upload(string $field, string $day_folder, string $sub_dir, string $new_name = ''): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Racine absolue du site (deux niveaux au-dessus de segment_page/api/)
    $site_root = realpath(__DIR__ . '/../../');
    $dir_abs   = $site_root . '/media/' . $day_folder . '/' . $sub_dir . '/';

    if (!is_dir($dir_abs)) {
        if (!mkdir($dir_abs, 0755, true)) {
            return null;
        }
    }

    $ext       = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $filename  = ($new_name ?: pathinfo($_FILES[$field]['name'], PATHINFO_FILENAME))
                 . '.' . $ext;
    $filename  = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    $dest_abs  = $dir_abs . $filename;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest_abs)) {
        return null;
    }

    // Retourner le chemin relatif utilisable côté web
    return 'media/' . $day_folder . '/' . $sub_dir . '/' . $filename;
}

// ─── Lecture des champs POST ──────────────────────────────────

$username         = post('username');
$display_name     = post('display_name');
$signature        = post('signature');
$title            = post('title');
$description      = post('description');
$doodstream_code  = post('doodstream_code');
$doodstream_embed = post('doodstream_embed');
$duration         = (int) post('duration', '0');
$recorded_at      = post('recorded_at');
$day_folder       = post('day_folder');  // ex: 12_07_2025

// Validation minimale
if ($username === '') {
    err(400, "Champ 'username' obligatoire");
}
if ($day_folder === '') {
    // Fallback sur la date du jour
    $day_folder = date('d_m_Y');
}

// Normaliser username (sans @)
$username = ltrim($username, '@');

// ─── Sauvegarde des fichiers ──────────────────────────────────

$thumbnail_path = save_upload('thumbnail', $day_folder, 'images',
    $username . '_thumbnail');
$cover_path     = save_upload('cover',     $day_folder, 'images',
    $username . '_cover');
$sprite_path    = save_upload('sprite',    $day_folder, 'images',
    $username . '_sprite');
$demo_path      = save_upload('demo',      $day_folder, 'videos',
    $username . '_demo');

// ─── Construire l'embed si absent ────────────────────────────
if ($doodstream_embed === '' && $doodstream_code !== '') {
    $doodstream_embed = 'https://dood.li/e/' . $doodstream_code;
}

// ─── Upsert modèle ───────────────────────────────────────────
try {
    // Chercher si le modèle existe déjà
    $stmt = $pdo->prepare(
        "SELECT id, profile_pic, cover_pic FROM models WHERE username = :username LIMIT 1"
    );
    $stmt->execute([':username' => $username]);
    $model = $stmt->fetch();

    if ($model) {
        $model_id = (int) $model['id'];
        // Mettre à jour uniquement les champs renseignés
        $updates  = ["updated_at = NOW()"];
        $params   = [':id' => $model_id];

        if ($display_name !== '') {
            $updates[] = "display_name = :display_name";
            $params[':display_name'] = $display_name;
        }
        if ($signature !== '') {
            $updates[] = "signature = :signature";
            $params[':signature'] = $signature;
        }
        // La miniature de la dernière vidéo devient la photo de profil
        if ($thumbnail_path !== null) {
            $updates[] = "profile_pic = :profile_pic";
            $params[':profile_pic'] = $thumbnail_path;
        }
        if ($cover_path !== null) {
            $updates[] = "cover_pic = :cover_pic";
            $params[':cover_pic'] = $cover_path;
        }

        $pdo->prepare("UPDATE models SET " . implode(', ', $updates) . " WHERE id = :id")
            ->execute($params);

    } else {
        // Créer le modèle
        $pdo->prepare(
            "INSERT INTO models (username, display_name, signature, profile_pic, cover_pic, created_at, updated_at)
             VALUES (:username, :display_name, :signature, :profile_pic, :cover_pic, NOW(), NOW())"
        )->execute([
            ':username'     => $username,
            ':display_name' => $display_name,
            ':signature'    => $signature,
            ':profile_pic'  => $thumbnail_path,
            ':cover_pic'    => $cover_path,
        ]);
        $model_id = (int) $pdo->lastInsertId();
    }

} catch (PDOException $e) {
    err(500, "Erreur DB (model) : " . $e->getMessage());
}

// ─── Insertion vidéo ─────────────────────────────────────────
try {
    $pdo->prepare(
        "INSERT INTO videos
            (model_id, title, description, doodstream_code, doodstream_embed,
             demo_path, thumbnail_path, sprite_path, duration, recorded_at, published_at, status)
         VALUES
            (:model_id, :title, :description, :doodstream_code, :doodstream_embed,
             :demo_path, :thumbnail_path, :sprite_path, :duration, :recorded_at, NOW(), 'published')"
    )->execute([
        ':model_id'          => $model_id,
        ':title'             => $title,
        ':description'       => $description,
        ':doodstream_code'   => $doodstream_code ?: null,
        ':doodstream_embed'  => $doodstream_embed ?: null,
        ':demo_path'         => $demo_path,
        ':thumbnail_path'    => $thumbnail_path,
        ':sprite_path'       => $sprite_path,
        ':duration'          => $duration ?: null,
        ':recorded_at'       => $recorded_at ?: null,
    ]);
    $video_id = (int) $pdo->lastInsertId();

} catch (PDOException $e) {
    err(500, "Erreur DB (video) : " . $e->getMessage());
}

// ─── Réponse succès ───────────────────────────────────────────
http_response_code(200);
echo json_encode([
    "status"       => 200,
    "msg"          => "Publication réussie",
    "video_id"     => $video_id,
    "model_id"     => $model_id,
    "thumbnail"    => $thumbnail_path,
    "cover"        => $cover_path,
    "sprite"       => $sprite_path,
    "demo"         => $demo_path,
    "embed"        => $doodstream_embed,
]);