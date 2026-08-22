<?php
require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/../vendor/autoload.php'; // PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    
    private static function getMailer() {
        $mail = new PHPMailer(true);
        
        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        
        // 🔍 DEBUG TEMPORAIRE - à retirer après diagnostic
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer [$level]: $str");
        };
        
        // Encodage UTF-8
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        
        // Expéditeur
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        return $mail;
    }
    
    /**
     * Génère un code de vérification à 6 chiffres
     */
    public static function generateVerificationCode() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Envoie l'email de vérification avec le code
     */
    public static function sendVerificationEmail($toEmail, $toName, $code, $isRegistration = true) {
        try {
            $mail = self::getMailer();
            $mail->addAddress($toEmail, $toName);
            
            if ($isRegistration) {
                $mail->Subject = 'Vérifiez votre compte TikCapture - Code: ' . $code;
                $mail->Body = self::getVerificationTemplate($toName, $code);
            } else {
                $mail->Subject = 'Code de vérification TikCapture - ' . $code;
                $mail->Body = self::getSimpleCodeTemplate($toName, $code);
            }
            
            $mail->isHTML(true);
            $mail->AltBody = 'Votre code de vérification est: ' . $code;
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur envoi email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Template HTML pour l'inscription
     */
    private static function getVerificationTemplate($name, $code) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #1A237E 0%, #3949AB 100%); padding: 40px 20px; text-align: center; }
                .header h1 { color: white; margin: 0; font-size: 28px; }
                .content { padding: 40px 30px; }
                .code-box { background: #f8f9fa; border: 2px dashed #1A237E; border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0; }
                .code { font-size: 48px; font-weight: bold; color: #1A237E; letter-spacing: 10px; font-family: 'Courier New', monospace; }
                .warning { color: #e74c3c; font-size: 14px; margin-top: 20px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Vérification TikCapture</h1>
                </div>
                <div class='content'>
                    <h2>Bonjour " . htmlspecialchars($name) . ",</h2>
                    <p>Merci de vous inscrire sur <strong>TikCapture</strong> ! Pour finaliser votre inscription, veuillez saisir le code de vérification suivant dans l'application :</p>
                    
                    <div class='code-box'>
                        <div class='code'>" . $code . "</div>
                    </div>
                    
                    <p class='warning'>⏰ Ce code expire dans " . VERIFICATION_EXPIRY_MINUTES . " minutes.<br>
                    🔒 Ne partagez ce code avec personne.</p>
                    
                    <p>Si vous n'avez pas demandé cette inscription, ignorez simplement cet email.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 TikCapture - Sécurité renforcée</p>
                    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Template simple pour renvoi de code
     */
    private static function getSimpleCodeTemplate($name, $code) {
        return "
        <!DOCTYPE html>
        <html>
        <body style='font-family: Arial, sans-serif; text-align: center; padding: 40px;'>
            <h2 style='color: #1A237E;'>Bonjour " . htmlspecialchars($name) . "</h2>
            <p>Votre nouveau code de vérification :</p>
            <h1 style='font-size: 48px; color: #1A237E; letter-spacing: 10px;'>" . $code . "</h1>
            <p style='color: #666;'>Valable " . VERIFICATION_EXPIRY_MINUTES . " minutes</p>
        </body>
        </html>";
    }
    
    /**
     * Envoie un email de bienvenue après vérification
     */
    public static function sendWelcomeEmail($toEmail, $toName, $username) {
        try {
            $mail = self::getMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = 'Bienvenue sur TikCapture ! 🎉';
            $mail->isHTML(true);
            
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h1 style='color: #1A237E;'>Bienvenue " . htmlspecialchars($toName) . " !</h1>
                <p>Votre compte <strong>@" . htmlspecialchars($username) . "</strong> est maintenant actif.</p>
                <p>Commencez à capturer vos moments préférés dès maintenant !</p>
                <a href='https://tikcapture.live/login' style='display: inline-block; background: #1A237E; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px;'>Accéder à l'application</a>
            </div>";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erreur email bienvenue: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envoie l'email de réinitialisation de mot de passe
     */
    public static function sendPasswordResetEmail($toEmail, $toName, $resetUrl, $token) {
        try {
            $mail = self::getMailer();
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = 'Réinitialisation de votre mot de passe TikCapture';
            $mail->isHTML(true);
            
            $mail->Body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #1A237E 0%, #3949AB 100%); padding: 40px 20px; text-align: center; }
                    .header h1 { color: white; margin: 0; font-size: 24px; }
                    .content { padding: 40px 30px; }
                    .button { display: inline-block; background: #1A237E; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                    .token-box { background: #f8f9fa; border: 2px dashed #1A237E; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; font-family: monospace; font-size: 18px; word-break: break-all; }
                    .warning { color: #e74c3c; font-size: 14px; margin-top: 20px; }
                    .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🔐 Réinitialisation du mot de passe</h1>
                    </div>
                    <div class='content'>
                        <h2>Bonjour " . htmlspecialchars($toName) . ",</h2>
                        <p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :</p>
                        
                        <center><a href='" . $resetUrl . "' class='button'>Réinitialiser mon mot de passe</a></center>
                        
                        <p>Ou copiez ce lien dans votre navigateur :</p>
                        <div class='token-box'>" . $resetUrl . "</div>
                        
                        <p class='warning'>⏰ Ce lien expire dans 1 heure.<br>
                        🔒 Si vous n'avez pas fait cette demande, ignorez cet email.</p>
                    </div>
                    <div class='footer'>
                        <p>© 2024 TikCapture - Sécurité renforcée</p>
                    </div>
                </div>
            </body>
            </html>";
            
            $mail->AltBody = "Réinitialisation mot de passe TikCapture\n\nCopiez ce lien: $resetUrl\n\nCe lien expire dans 1 heure.";
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur envoi email reset: " . $e->getMessage());
            return false;
        }
    }
}
?>