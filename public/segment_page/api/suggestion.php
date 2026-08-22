<?php
/**
 * API Suggestions — TikCapture
 * Chemin : /segment_page/api/suggestion.php
 *
 * Protections : Rate limiting (IP), Honeypot, Cloudflare Turnstile
 * Dépendance  : PHPMailer via Composer (vendor/autoload.php)
 */

declare(strict_types=1);

// ─── CORS ─────────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: https://tikcapture.live');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── Méthode ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Méthode non autorisée.']));
}

// ─── Autoload Composer ────────────────────────────────────────────────────────
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Dépendances manquantes (autoload).']));
}
require_once $autoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

// ─── Config email ─────────────────────────────────────────────────────────────
$configPath = __DIR__ . '/../../config/email_config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Configuration serveur manquante.']));
}
require_once $configPath;

// ─── Helpers ──────────────────────────────────────────────────────────────────
function respond(bool $success, string $message, int $code = 200): never
{
    http_response_code($code);
    exit(json_encode(['success' => $success, 'message' => $message]));
}

function getClientIp(): string
{
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return 'unknown';
}

// ─── 1. Rate limiting (IP) ────────────────────────────────────────────────────
$ip          = getClientIp();
$rateDir     = sys_get_temp_dir() . '/tikcapture_rl_suggestion';
$maxAttempts = 5;
$windowSec   = 3600; // 1 heure

if (!is_dir($rateDir)) mkdir($rateDir, 0700, true);

$rateFile = $rateDir . '/' . hash('sha256', $ip) . '.json';
$now      = time();
$attempts = [];

if (file_exists($rateFile)) {
    $data     = json_decode(file_get_contents($rateFile), true) ?? [];
    $attempts = array_filter($data['attempts'] ?? [], fn($t) => ($now - $t) < $windowSec);
}

if (count($attempts) >= $maxAttempts) {
    respond(false, 'Trop de tentatives. Réessayez dans une heure.', 429);
}

// ─── 2. Parse JSON body ───────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    respond(false, 'Corps de requête invalide.', 400);
}

// ─── 3. Honeypot ──────────────────────────────────────────────────────────────
if (!empty($body['website'])) {
    respond(true, 'Suggestion envoyée avec succès.');
}

// ─── 4. Cloudflare Turnstile ──────────────────────────────────────────────────
$turnstileSecret = '0x4AAAAAACnAeIejs0uqoYmB7PIzCwEfnKU'; // ← Colle ici ta SECRET KEY Turnstile
$turnstileToken  = trim($body['cf_turnstile_token'] ?? '');

if (empty($turnstileToken)) {
    respond(false, 'Vérification anti-bot manquante.', 400);
}

$tsResponse = file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'secret'   => $turnstileSecret,
            'response' => $turnstileToken,
            'remoteip' => $ip,
        ]),
        'timeout' => 5,
    ],
]));

$tsData = $tsResponse ? json_decode($tsResponse, true) : null;

if (!$tsData || !($tsData['success'] ?? false)) {
    respond(false, 'Vérification anti-bot échouée. Rafraîchissez la page.', 403);
}

// ─── 5. Validation des champs ─────────────────────────────────────────────────
$title    = trim(strip_tags($body['title']    ?? ''));
$detail   = trim(strip_tags($body['detail']   ?? ''));
$category = trim(strip_tags($body['category'] ?? 'Non précisée'));
$rating   = (int) ($body['rating'] ?? 0);

$allowedCategories = [
    'Nouvelle fonctionnalité',
    'Amélioration existante',
    'Interface / Design',
    'Performance',
    'Autre',
];

if (empty($title) || strlen($title) < 3 || strlen($title) > 150) {
    respond(false, 'Titre invalide (3–150 caractères requis).', 422);
}
if (empty($detail) || strlen($detail) < 10 || strlen($detail) > 3000) {
    respond(false, 'Détail invalide (10–3000 caractères requis).', 422);
}
if ($category && !in_array($category, $allowedCategories, true)) {
    $category = 'Autre';
}
$rating = max(0, min(5, $rating));
$stars  = $rating > 0 ? str_repeat('★', $rating) . str_repeat('☆', 5 - $rating) : 'Non noté';

// ─── 6. Enregistrement du rate limit ─────────────────────────────────────────
$attempts[] = $now;
file_put_contents($rateFile, json_encode(['attempts' => array_values($attempts)]), LOCK_EX);

// ─── 7. Envoi email ───────────────────────────────────────────────────────────
try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int) SMTP_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 10;

    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress('contact@tikcapture.live', 'TikCapture');

    $mail->isHTML(true);
    $mail->Subject = "[TikCapture Suggestion] {$title}";

    // ── Corps HTML ──
    $safeTitle    = htmlspecialchars($title,    ENT_QUOTES, 'UTF-8');
    $safeDetail   = nl2br(htmlspecialchars($detail,   ENT_QUOTES, 'UTF-8'));
    $safeCategory = htmlspecialchars($category, ENT_QUOTES, 'UTF-8');
    $safeStars    = htmlspecialchars($stars,    ENT_QUOTES, 'UTF-8');
    $date         = date('d/m/Y à H:i');

    $mail->Body = <<<HTML
    <!DOCTYPE html>
    <html lang="fr">
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#0a0a0a;font-family:system-ui,sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;padding:40px 20px;">
        <tr><td align="center">
          <table width="600" cellpadding="0" cellspacing="0" style="background:#111;border:1px solid #222;border-radius:16px;overflow:hidden;max-width:600px;width:100%;">
            <!-- Header -->
            <tr>
              <td style="background:linear-gradient(135deg,#FF0050,#ff6b9d);padding:32px 40px;">
                <h1 style="margin:0;color:#fff;font-size:22px;font-weight:800;letter-spacing:-0.5px;">
                  💡 Nouvelle suggestion — TikCapture
                </h1>
                <p style="margin:6px 0 0;color:rgba(255,255,255,0.8);font-size:13px;">{$date}</p>
              </td>
            </tr>
            <!-- Body -->
            <tr>
              <td style="padding:32px 40px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <!-- Méta -->
                  <tr>
                    <td style="padding:0 0 20px;">
                      <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:12px;overflow:hidden;">
                        <tr>
                          <td style="padding:16px 20px;border-bottom:1px solid #2a2a2a;">
                            <span style="color:#888;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;">Catégorie</span>
                            <p style="margin:4px 0 0;color:#fff;font-size:15px;font-weight:600;">{$safeCategory}</p>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding:16px 20px;border-bottom:1px solid #2a2a2a;">
                            <span style="color:#888;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;">Satisfaction actuelle</span>
                            <p style="margin:4px 0 0;color:#FF0050;font-size:18px;letter-spacing:2px;">{$safeStars}</p>
                          </td>
                        </tr>
                        <tr>
                          <td style="padding:16px 20px;">
                            <span style="color:#888;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;">Titre</span>
                            <p style="margin:4px 0 0;color:#fff;font-size:15px;font-weight:600;">{$safeTitle}</p>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <!-- Détail -->
                  <tr>
                    <td>
                      <p style="color:#888;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;margin:0 0 10px;">Détails</p>
                      <div style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:12px;padding:20px;color:#ddd;font-size:14px;line-height:1.7;">
                        {$safeDetail}
                      </div>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <!-- Footer -->
            <tr>
              <td style="padding:20px 40px;border-top:1px solid #1f1f1f;">
                <p style="margin:0;color:#444;font-size:12px;text-align:center;">
                  TikCapture · <a href="https://tikcapture.live" style="color:#FF0050;text-decoration:none;">tikcapture.live</a>
                </p>
              </td>
            </tr>
          </table>
        </td></tr>
      </table>
    </body>
    </html>
    HTML;

    $mail->AltBody = "Nouvelle suggestion : {$title}\nCatégorie : {$category}\nSatisfaction : {$stars}\n\n{$detail}";

    $mail->send();
    respond(true, 'Votre suggestion a bien été envoyée. Merci !');

} catch (MailException $e) {
    error_log('[TikCapture Suggestion] PHPMailer error: ' . $e->getMessage());
    respond(false, 'Erreur lors de l\'envoi. Veuillez réessayer.', 500);
}