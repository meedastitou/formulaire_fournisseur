<?php
/**
 * ════════════════════════════════════════════════════════════
 * CONFIGURATION BASE DE DONNÉES
 * ════════════════════════════════════════════════════════════
 */

// Configuration MySQL
define('DB_HOST', 'localhost');
define('DB_NAME', 'flux_achat_portal');
define('DB_USER', 'root'); // ⚠️ À MODIFIER
define('DB_PASS', '');     // ⚠️ À MODIFIER
define('DB_CHARSET', 'utf8mb4');

/**
 * Créer connexion PDO
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("Erreur connexion DB: " . $e->getMessage());
        die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
    }
}

/**
 * Tester connexion
 */
function testConnection() {
    try {
        $pdo = getDBConnection();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>