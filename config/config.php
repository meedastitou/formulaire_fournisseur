<?php
/**
 * ════════════════════════════════════════════════════════════
 * CONFIGURATION GÉNÉRALE
 * ════════════════════════════════════════════════════════════
 */

// Configuration site
define('SITE_NAME', 'Flux Achat Portal');
define('SITE_URL', 'https://votre-domaine.com'); // ⚠️ À MODIFIER

// Configuration n8n webhook
define('N8N_WEBHOOK_URL', 'https://votre-n8n.com/webhook/reponse-fournisseur'); // ⚠️ À MODIFIER

// Configuration email
define('EMAIL_RESPONSABLE', 'achat@votresociete.com'); // ⚠️ À MODIFIER

// Timezone
date_default_timezone_set('Africa/Casablanca');

// Démarrer session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', 1); // Mettre à 0 en production
?>