<?php
class InvitationHelper {
    
    /**
     * Vérifie si un code d'invitation est valide
     */
    public static function validateCode($pdo, $code) {
        if (empty($code)) {
            return ['valid' => true, 'optional' => true]; // Facultatif
        }
        
        $stmt = $pdo->prepare("
            SELECT id, max_uses, used_count, is_active, expires_at 
            FROM invitation_codes 
            WHERE code = ? AND is_active = TRUE
        ");
        $stmt->execute([$code]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            return ['valid' => false, 'error' => 'Code d\'invitation invalide'];
        }
        
        // Vérifier expiration
        if ($invitation['expires_at'] && strtotime($invitation['expires_at']) < time()) {
            return ['valid' => false, 'error' => 'Ce code d\'invitation a expiré'];
        }
        
        // Vérifier nombre d'utilisations
        if ($invitation['max_uses'] !== null && $invitation['used_count'] >= $invitation['max_uses']) {
            return ['valid' => false, 'error' => 'Ce code d\'invitation a atteint sa limite d\'utilisations'];
        }
        
        return [
            'valid' => true, 
            'id' => $invitation['id'],
            'max_uses' => $invitation['max_uses'],
            'used_count' => $invitation['used_count']
        ];
    }
    
    /**
     * Incrémente le compteur d'utilisation d'un code
     */
    public static function incrementUsage($pdo, $code) {
        if (empty($code)) return true;
        
        $stmt = $pdo->prepare("
            UPDATE invitation_codes 
            SET used_count = used_count + 1 
            WHERE code = ?
        ");
        return $stmt->execute([$code]);
    }
    
    /**
     * Génère un nouveau code d'invitation (pour admin)
     */
    public static function generateCode($pdo, $createdBy = null, $maxUses = null, $expiresDays = null) {
        $code = strtoupper(bin2hex(random_bytes(4))); // 8 caractères hex
        
        // S'assurer de l'unicité
        while (true) {
            $stmt = $pdo->prepare("SELECT id FROM invitation_codes WHERE code = ?");
            $stmt->execute([$code]);
            if (!$stmt->fetch()) break;
            $code = strtoupper(bin2hex(random_bytes(4)));
        }
        
        $expiresAt = $expiresDays ? date('Y-m-d H:i:s', strtotime("+$expiresDays days")) : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO invitation_codes (code, created_by, max_uses, expires_at) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$code, $createdBy, $maxUses, $expiresAt]);
        
        return $code;
    }
}
?>