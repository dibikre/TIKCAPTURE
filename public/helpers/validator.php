<?php
class Validator {
    
    private $errors = [];
    
    /**
     * Valide un email
     */
    public function email($email, $field = 'email') {
        if (empty($email)) {
            $this->errors[$field] = 'L\'email est requis';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Format d\'email invalide';
        } elseif (strlen($email) > 100) {
            $this->errors[$field] = 'Email trop long (max 100 caractères)';
        }
        return $this;
    }
    
    /**
     * Valide un mot de passe
     */
    public function password($password, $field = 'password', $confirm = null) {
        if (empty($password)) {
            $this->errors[$field] = 'Le mot de passe est requis';
        } elseif (strlen($password) < 8) {
            $this->errors[$field] = 'Le mot de passe doit contenir au moins 8 caractères';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $this->errors[$field] = 'Le mot de passe doit contenir au moins une majuscule';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $this->errors[$field] = 'Le mot de passe doit contenir au moins une minuscule';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $this->errors[$field] = 'Le mot de passe doit contenir au moins un chiffre';
        } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $this->errors[$field] = 'Le mot de passe doit contenir au moins un caractère spécial';
        }
        
        if ($confirm !== null && $password !== $confirm) {
            $this->errors[$field . '_confirm'] = 'Les mots de passe ne correspondent pas';
        }
        
        return $this;
    }
    
    /**
     * Valide un nom d'utilisateur
     */
    public function username($username, $field = 'username') {
        if (empty($username)) {
            $this->errors[$field] = 'Le nom d\'utilisateur est requis';
        } elseif (strlen($username) < 3) {
            $this->errors[$field] = 'Le nom d\'utilisateur doit contenir au moins 3 caractères';
        } elseif (strlen($username) > 50) {
            $this->errors[$field] = 'Le nom d\'utilisateur ne doit pas dépasser 50 caractères';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $this->errors[$field] = 'Caractères autorisés: lettres, chiffres, tirets et underscores';
        }
        return $this;
    }
    
    /**
     * Valide un nom complet
     */
    public function fullName($name, $field = 'full_name') {
        if (empty($name)) {
            $this->errors[$field] = 'Le nom complet est requis';
        } elseif (strlen($name) < 2) {
            $this->errors[$field] = 'Le nom complet est trop court';
        } elseif (strlen($name) > 100) {
            $this->errors[$field] = 'Le nom complet ne doit pas dépasser 100 caractères';
        } elseif (!preg_match('/^[\p{L}\s\'-]+$/u', $name)) {
            $this->errors[$field] = 'Le nom contient des caractères invalides';
        }
        return $this;
    }
    
    /**
     * Vérifie s'il y a des erreurs
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Retourne les erreurs
     */
    public function errors() {
        return $this->errors;
    }
}
?>