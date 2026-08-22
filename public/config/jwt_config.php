<?php
// Configuration JWT pour l'authentification par token
define('JWT_SECRET_KEY', 'votre_cle_secrete_tres_longue_et_complexe_123456789'); // CHANGEZ CETTE CLÉ !
define('JWT_ISSUER', 'tikcapture.live');
define('JWT_AUDIENCE', 'tikcapture_mobile_app');
define('JWT_EXPIRATION_HOURS', 24); // Token valide 24h
define('JWT_REFRESH_DAYS', 30); // Refresh token valide 30 jours
?>