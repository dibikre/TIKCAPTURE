<?php
/**
 * Auth handlers for tikcapture
 */

/**
 * ÉTAPE 1: Initialisation de l'inscription - Envoie le code de vérification
 */
function handleRegisterInit($pdo, $data) {
    $validator = new Validator();
    $validator->username($data['username'] ?? '')
              ->email($data['email'] ?? '')
              ->password($data['password'] ?? '', 'password', $data['password_confirm'] ?? '')
              ->fullName($data['full_name'] ?? '');
    
    if ($validator->fails()) {
        APIResponse::error('Données invalides', 422, $validator->errors());
    }
    
    // Vérifier code d'invitation (facultatif)
    $invitationCode = $data['invitation_code'] ?? null;
    $invitationCheck = InvitationHelper::validateCode($pdo, $invitationCode);
    
    if (!$invitationCheck['valid']) {
        APIResponse::error($invitationCheck['error'], 400, ['invitation_code' => $invitationCheck['error']]);
    }
    
    try {
        // Vérifier si email ou username existe déjà (même non vérifié)
        $stmt = $pdo->prepare("
            SELECT id, is_verified FROM users 
            WHERE email = ? OR username = ?
        ");
        $stmt->execute([$data['email'], $data['username']]);
        $existing = $stmt->fetch();
        
        if ($existing && $existing['is_verified']) {
            APIResponse::error('Cet email ou ce nom d\'utilisateur est déjà utilisé', 409);
        }
        
        // Si existe mais non vérifié, on supprime l'ancien pour recréer
        if ($existing && !$existing['is_verified']) {
            // Supprimer d'abord les vérifications liées
            $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
            $stmt->execute([$existing['id']]);
            
            // Puis supprimer l'utilisateur
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$existing['id']]);
        }
        
        // Hasher le mot de passe temporairement
        $passwordHash = password_hash($data['password'], PASSWORD_ARGON2ID);
        
        // Insérer l'utilisateur (non vérifié)
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, email, password_hash, full_name, phone, 
                is_active, is_verified, invitation_code_used
            ) VALUES (?, ?, ?, ?, ?, FALSE, FALSE, ?)
        ");
        
        $stmt->execute([
            $data['username'],
            $data['email'],
            $passwordHash,
            $data['full_name'],
            $data['phone'] ?? null,
            $invitationCode
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Générer code de vérification
        $code = EmailHelper::generateVerificationCode();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . VERIFICATION_EXPIRY_MINUTES . ' minutes'));
        
        // Sauvegarder le code
        $stmt = $pdo->prepare("
            INSERT INTO email_verifications 
            (user_id, email, verification_code, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $data['email'], $code, $expiresAt]);
        
        // Envoyer l'email
        $emailSent = EmailHelper::sendVerificationEmail(
            $data['email'], 
            $data['full_name'], 
            $code
        );
        
        if (!$emailSent) {
            // Rollback si email échoue
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            APIResponse::error('Erreur lors de l\'envoi de l\'email. Veuillez réessayer.', 500);
        }
        
        logAuth($pdo, $userId, 'register', 'success', 'Code de vérification envoyé');
        
        APIResponse::success([
            'user_id' => $userId,
            'email' => $data['email'],
            'message' => 'Code de vérification envoyé par email',
            'expires_in_minutes' => VERIFICATION_EXPIRY_MINUTES,
            'max_attempts' => MAX_VERIFICATION_ATTEMPTS
        ], 'Vérifiez votre email pour le code à 6 chiffres');
        
    } catch (PDOException $e) {
        logAuth($pdo, null, 'register', 'failed', 'Erreur BD: ' . $e->getMessage());
        APIResponse::error('Erreur lors de l\'inscription', 500);
    }
}

/**
 * ÉTAPE 2: Vérification du code et finalisation
 */
function handleRegisterVerify($pdo, $data) {
    $userId = $data['user_id'] ?? null;
    $code = $data['verification_code'] ?? '';
    
    if (!$userId || empty($code)) {
        APIResponse::error('User ID et code de vérification requis', 422);
    }
    
    // Nettoyer le code (enlever espaces)
    $code = preg_replace('/\s+/', '', $code);
    
    try {
        // Récupérer la vérification
        $stmt = $pdo->prepare("
            SELECT v.*, u.username, u.full_name, u.invitation_code_used 
            FROM email_verifications v
            JOIN users u ON v.user_id = u.id
            WHERE v.user_id = ? AND v.email = u.email 
            AND v.is_verified = FALSE
            ORDER BY v.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $verification = $stmt->fetch();
        
        if (!$verification) {
            APIResponse::error('Aucune vérification en attente trouvée', 404);
        }
        
        // Vérifier expiration
        if (strtotime($verification['expires_at']) < time()) {
            APIResponse::error('Ce code a expiré. Veuillez demander un nouveau code.', 410);
        }
        
        // Vérifier nombre de tentatives
        if ($verification['attempts'] >= $verification['max_attempts']) {
            APIResponse::error('Trop de tentatives. Veuillez demander un nouveau code.', 429);
        }
        
        // Incrémenter les tentatives
        $stmt = $pdo->prepare("
            UPDATE email_verifications 
            SET attempts = attempts + 1 
            WHERE id = ?
        ");
        $stmt->execute([$verification['id']]);
        
        // Vérifier le code
        if ($verification['verification_code'] !== $code) {
            $remaining = $verification['max_attempts'] - ($verification['attempts'] + 1);
            APIResponse::error(
                'Code incorrect. ' . ($remaining > 0 ? "$remaining tentatives restantes." : ""), 
                400,
                ['remaining_attempts' => max(0, $remaining)]
            );
        }
        
        // Code correct ! Finaliser l'inscription
        $pdo->beginTransaction();
        
        try {
            // Marquer email comme vérifié
            $stmt = $pdo->prepare("
                UPDATE email_verifications 
                SET is_verified = TRUE, verified_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$verification['id']]);
            
            // Activer l'utilisateur
            $stmt = $pdo->prepare("
                UPDATE users 
                SET is_verified = TRUE, is_active = TRUE, email_verified = TRUE 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            // Incrémenter l'usage du code d'invitation si utilisé
            if ($verification['invitation_code_used']) {
                InvitationHelper::incrementUsage($pdo, $verification['invitation_code_used']);
            }
            
            $pdo->commit();
            
            // Envoyer email de bienvenue (asynchrone, pas bloquant)
            EmailHelper::sendWelcomeEmail(
                $verification['email'],
                $verification['full_name'],
                $verification['username']
            );
            
            logAuth($pdo, $userId, 'register', 'success', 'Inscription finalisée avec succès');
            
            // Générer token JWT pour connexion automatique
            $token = JWTHelper::generateToken([
                'sub' => $userId,
                'username' => $verification['username'],
                'email' => $verification['email']
            ]);
            
            $user = [
                'id'         => $userId,
                'username'   => $verification['username'],
                'email'      => $verification['email'],
                'full_name'  => $verification['full_name'],
                'is_verified'=> true,
                'is_premium' => false,
            ];
            
            APIResponse::auth($token, $user, 'Inscription finalisée avec succès !');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        logAuth($pdo, $userId, 'register', 'failed', 'Erreur vérification: ' . $e->getMessage());
        APIResponse::error('Erreur lors de la vérification', 500);
    }
}

/**
 * Renvoyer le code de vérification
 */
function handleResendCode($pdo, $data) {
    $userId = $data['user_id'] ?? null;
    
    if (!$userId) {
        APIResponse::error('User ID requis', 422);
    }
    
    try {
        // Vérifier que l'utilisateur existe et n'est pas vérifié
        $stmt = $pdo->prepare("
            SELECT id, email, full_name, is_verified 
            FROM users WHERE id = ? AND is_verified = FALSE
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            APIResponse::error('Utilisateur non trouvé ou déjà vérifié', 404);
        }
        
        // Vérifier délai entre renvois (anti-spam: 60 secondes)
        $stmt = $pdo->prepare("
            SELECT created_at FROM email_verifications 
            WHERE user_id = ? ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$userId]);
        $lastCode = $stmt->fetch();
        
        if ($lastCode) {
            $lastTime = strtotime($lastCode['created_at']);
            if (time() - $lastTime < 60) {
                $wait = 60 - (time() - $lastTime);
                APIResponse::error("Veuillez attendre $wait secondes avant de demander un nouveau code", 429);
            }
        }
        
        // Générer nouveau code
        $newCode = EmailHelper::generateVerificationCode();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . VERIFICATION_EXPIRY_MINUTES . ' minutes'));
        
        // Invalider anciens codes
        $stmt = $pdo->prepare("
            UPDATE email_verifications 
            SET is_verified = TRUE 
            WHERE user_id = ? AND is_verified = FALSE
        ");
        $stmt->execute([$userId]);
        
        // Créer nouveau code
        $stmt = $pdo->prepare("
            INSERT INTO email_verifications 
            (user_id, email, verification_code, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $user['email'], $newCode, $expiresAt]);
        
        // Envoyer email
        $sent = EmailHelper::sendVerificationEmail($user['email'], $user['full_name'], $newCode);
        
        if (!$sent) {
            APIResponse::error('Erreur lors de l\'envoi de l\'email', 500);
        }
        
        APIResponse::success([
            'expires_in_minutes' => VERIFICATION_EXPIRY_MINUTES,
            'max_attempts' => MAX_VERIFICATION_ATTEMPTS
        ], 'Nouveau code envoyé');
        
    } catch (PDOException $e) {
        APIResponse::error('Erreur lors du renvoi', 500);
    }
}

/**
 * Connexion (modifiée pour vérifier is_verified)
 */
function handleLogin($pdo, $data) {
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        APIResponse::error('Email et mot de passe requis', 422);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, email, password_hash, full_name, phone,
                   avatar_url, is_active, is_verified, email_verified, is_premium
            FROM users
            WHERE email = ? OR username = ?
        ");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            logAuth($pdo, null, 'login', 'failed', 'Utilisateur non trouvé');
            APIResponse::error('Email ou mot de passe incorrect', 401);
        }
        
        if (!$user['is_verified']) {
            // Rediriger vers la vérification
            APIResponse::error('Compte non vérifié. Vérifiez votre email ou demandez un nouveau code.', 403, [
                'needs_verification' => true,
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);
        }
        
        if (!$user['is_active']) {
            logAuth($pdo, $user['id'], 'login', 'failed', 'Compte désactivé');
            APIResponse::error('Ce compte a été désactivé', 403);
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            logAuth($pdo, $user['id'], 'login', 'failed', 'Mot de passe incorrect');
            APIResponse::error('Email ou mot de passe incorrect', 401);
        }
        
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        logAuth($pdo, $user['id'], 'login', 'success', 'Connexion réussie');
        
        $token = JWTHelper::generateToken([
            'sub' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email']
        ]);
        
        unset($user['password_hash']);

        // S'assurer que is_premium est bien un booléen
        $user['is_premium'] = ($user['is_premium'] == '1' || $user['is_premium'] === true);
        
        APIResponse::auth($token, $user, 'Connexion réussie');
        
    } catch (PDOException $e) {
        logAuth($pdo, null, 'login', 'failed', 'Erreur BD: ' . $e->getMessage());
        APIResponse::error('Erreur lors de la connexion', 500);
    }
}

/**
 * Déconnexion
 */
function handleLogout($pdo) {
    $token = JWTHelper::getBearerToken();
    
    if ($token) {
        $payload = JWTHelper::verifyToken($token);
        if ($payload && isset($payload['sub'])) {
            logAuth($pdo, $payload['sub'], 'logout', 'success', 'Déconnexion réussie');
            
            // Invalider la session en base si existe
            $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE token = ?");
            $stmt->execute([$token]);
        }
    }
    
    APIResponse::success(null, 'Déconnexion réussie');
}

/**
 * Vérifier la validité du token
 */
function handleVerifyToken($pdo) {
    $token = JWTHelper::getBearerToken();
    
    if (!$token) {
        APIResponse::error('Token manquant', 401);
    }
    
    $payload = JWTHelper::verifyToken($token);
    
    if (!$payload) {
        APIResponse::error('Token invalide ou expiré', 401);
    }
    
    // Récupérer les infos utilisateur fraîches
    $stmt = $pdo->prepare("
        SELECT id, username, email, full_name, phone, avatar_url, is_active, is_premium 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$payload['sub']]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['is_active']) {
        APIResponse::error('Utilisateur non trouvé ou inactif', 401);
    }
    
    APIResponse::success([
        'valid' => true,
        'user' => $user,
        'expires_at' => date('c', $payload['exp'])
    ], 'Token valide');
}

/**
 * Rafraîchir le token
 */
function handleRefreshToken($pdo) {
    $token = JWTHelper::getBearerToken();
    
    if (!$token) {
        APIResponse::error('Token manquant', 401);
    }
    
    $payload = JWTHelper::verifyToken($token);
    
    if (!$payload) {
        APIResponse::error('Token invalide', 401);
    }
    
    // Vérifier que l'utilisateur existe toujours
    $stmt = $pdo->prepare("SELECT id, username, email, is_active FROM users WHERE id = ?");
    $stmt->execute([$payload['sub']]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['is_active']) {
        APIResponse::error('Utilisateur invalide', 401);
    }
    
    // Générer nouveau token
    $newToken = JWTHelper::generateToken([
        'sub' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email']
    ]);
    
    logAuth($pdo, $user['id'], 'token_refresh', 'success', 'Token rafraîchi');
    
    APIResponse::auth($newToken, $user, 'Token rafraîchi avec succès');
}

/**
 * Obtenir le profil utilisateur
 */
function handleGetProfile($pdo) {
    $token = JWTHelper::getBearerToken();
    
    if (!$token) {
        APIResponse::error('Non authentifié', 401);
    }
    
    $payload = JWTHelper::verifyToken($token);
    if (!$payload) {
        APIResponse::error('Token invalide', 401);
    }
    
    $stmt = $pdo->prepare("
        SELECT id, username, email, full_name, phone, avatar_url, 
               created_at, email_verified, last_login 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$payload['sub']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        APIResponse::error('Utilisateur non trouvé', 404);
    }
    
    APIResponse::success($user, 'Profil récupéré');
}

/**
 * Mettre à jour le profil
 */
function handleUpdateProfile($pdo, $data) {
    $token = JWTHelper::getBearerToken();
    
    if (!$token) {
        APIResponse::error('Non authentifié', 401);
    }
    
    $payload = JWTHelper::verifyToken($token);
    if (!$payload) {
        APIResponse::error('Token invalide', 401);
    }
    
    $allowedFields = ['full_name', 'phone', 'avatar_url'];
    $updates = [];
    $values = [];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $values[] = $data[$field];
        }
    }
    
    if (empty($updates)) {
        APIResponse::error('Aucune donnée à mettre à jour', 422);
    }
    
    $values[] = $payload['sub'];
    
    try {
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        // Récupérer les données mises à jour
        $stmt = $pdo->prepare("
            SELECT id, username, email, full_name, phone, avatar_url 
            FROM users WHERE id = ?
        ");
        $stmt->execute([$payload['sub']]);
        $user = $stmt->fetch();
        
        APIResponse::success($user, 'Profil mis à jour avec succès');
        
    } catch (PDOException $e) {
        APIResponse::error('Erreur lors de la mise à jour', 500);
    }
}

/**
 * Changer le mot de passe
 */
function handleChangePassword($pdo, $data) {
    $token = JWTHelper::getBearerToken();
    
    if (!$token) {
        APIResponse::error('Non authentifié', 401);
    }
    
    $payload = JWTHelper::verifyToken($token);
    if (!$payload) {
        APIResponse::error('Token invalide', 401);
    }
    
    $currentPassword = $data['current_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    $confirmPassword = $data['new_password_confirm'] ?? '';
    
    // Validation
    $validator = new Validator();
    $validator->password($newPassword, 'new_password', $confirmPassword);
    
    if ($validator->fails()) {
        APIResponse::error('Validation échouée', 422, $validator->errors());
    }
    
    try {
        // Vérifier l'ancien mot de passe
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$payload['sub']]);
        $user = $stmt->fetch();
        
        if (!password_verify($currentPassword, $user['password_hash'])) {
            APIResponse::error('Mot de passe actuel incorrect', 403);
        }
        
        // Hasher et mettre à jour
        $newHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newHash, $payload['sub']]);
        
        APIResponse::success(null, 'Mot de passe changé avec succès');
        
    } catch (PDOException $e) {
        APIResponse::error('Erreur lors du changement de mot de passe', 500);
    }
}

/**
 * MOT DE PASSE OUBLIÉ - Demande de réinitialisation
 */
function handleForgotPassword($pdo, $data) {
    $email = $data['email'] ?? '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        APIResponse::error('Email invalide', 422);
    }
    
    try {
        // Vérifier que l'utilisateur existe et est vérifié
        $stmt = $pdo->prepare("
            SELECT id, username, full_name, email, is_verified 
            FROM users WHERE email = ? AND is_active = TRUE
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Ne pas révéler si l'email existe ou pas (sécurité)
        if (!$user || !$user['is_verified']) {
            // Simuler un délai pour éviter la détection par timing
            usleep(random_int(100000, 300000)); // 0.1-0.3s
            APIResponse::success(null, 'Si cet email existe, un lien a été envoyé');
        }
        
        // Générer un token unique
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // 1 heure valide
        
        // Supprimer anciens tokens pour cet email
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
        
        // Sauvegarder le nouveau token
        $stmt = $pdo->prepare("
            INSERT INTO password_resets (email, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$email, $token, $expiresAt]);
        
        // Envoyer l'email avec PHPMailer
        $resetUrl = "https://tikcapture.live/reset-password?token=$token";
        $emailSent = EmailHelper::sendPasswordResetEmail(
            $user['email'],
            $user['full_name'],
            $resetUrl,
            $token
        );
        
        if (!$emailSent) {
            logAuth($pdo, $user['id'], 'password_reset', 'failed', 'Erreur envoi email');
            APIResponse::error('Erreur lors de l\'envoi de l\'email', 500);
        }
        
        logAuth($pdo, $user['id'], 'password_reset', 'success', 'Token envoyé', $email);
        APIResponse::success(null, 'Si cet email existe, un lien de réinitialisation a été envoyé');
        
    } catch (PDOException $e) {
        APIResponse::error('Erreur serveur', 500);
    }
}

/**
 * RÉINITIALISATION MOT DE PASSE - Avec token
 */
function handleResetPassword($pdo, $data) {
    $token = $data['token'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    $confirmPassword = $data['new_password_confirm'] ?? '';
    
    // Validation
    $validator = new Validator();
    $validator->password($newPassword, 'new_password', $confirmPassword);
    
    if ($validator->fails()) {
        APIResponse::error('Données invalides', 422, $validator->errors());
    }
    
    try {
        // Vérifier le token
        $stmt = $pdo->prepare("
            SELECT email, expires_at FROM password_resets 
            WHERE token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            APIResponse::error('Token invalide ou expiré', 400);
        }
        
        // Hasher le nouveau mot de passe
        $passwordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        
        // Mettre à jour le mot de passe
        $stmt = $pdo->prepare("
            UPDATE users SET password_hash = ? WHERE email = ?
        ");
        $stmt->execute([$passwordHash, $reset['email']]);
        
        // Supprimer le token utilisé
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        
        // Invalider toutes les sessions actives de l'utilisateur
        $stmt = $pdo->prepare("
            UPDATE user_sessions SET is_active = FALSE 
            WHERE user_id = (SELECT id FROM users WHERE email = ?)
        ");
        $stmt->execute([$reset['email']]);
        
        APIResponse::success(null, 'Mot de passe réinitialisé avec succès');
        
    } catch (PDOException $e) {
        APIResponse::error('Erreur lors de la réinitialisation', 500);
    }
}
?>
