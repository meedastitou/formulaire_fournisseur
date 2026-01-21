<?php
/**
 * ════════════════════════════════════════════════════════════
 * FONCTIONS UTILITAIRES
 * ════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Récupérer RFQ par UUID
 */
function getRFQByUUID($uuid) {
    $pdo = getDBConnection();
    
    $sql = "SELECT dc.*, f.nom_fournisseur, f.email
            FROM demandes_cotation dc
            JOIN fournisseurs f ON dc.code_fournisseur = f.code_fournisseur
            WHERE dc.uuid = :uuid
            LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uuid' => $uuid]);
    
    return $stmt->fetch();
}

/**
 * Récupérer lignes de cotation
 */
function getLignesCotation($rfq_uuid) {
    $pdo = getDBConnection();
    
    $sql = "SELECT * FROM lignes_cotation 
            WHERE rfq_uuid = :uuid 
            ORDER BY id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uuid' => $rfq_uuid]);
    
    return $stmt->fetchAll();
}

/**
 * Vérifier si déjà répondu (nouvelle structure entête/détail)
 */
function isAlreadyResponded($rfq_uuid) {
    $pdo = getDBConnection();

    $sql = "SELECT COUNT(*) as count
            FROM reponses_fournisseurs_entete
            WHERE rfq_uuid = :uuid";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uuid' => $rfq_uuid]);

    $result = $stmt->fetch();
    return $result['count'] > 0;
}

/**
 * Enregistrer l'entête de la réponse fournisseur
 */
function saveReponseEntete($rfq_uuid, $reference_fournisseur, $fichier_devis_url, $devise, $commentaire, $methodes_paiement = null) {
    $pdo = getDBConnection();

    $sql = "INSERT INTO reponses_fournisseurs_entete
            (rfq_uuid, reference_fournisseur, fichier_devis_url, devise, methodes_paiement, date_reponse, commentaire)
            VALUES (:rfq_uuid, :ref, :fichier, :devise, :methodes_paiement, NOW(), :commentaire)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'rfq_uuid' => $rfq_uuid,
        'ref' => $reference_fournisseur,
        'fichier' => $fichier_devis_url,
        'devise' => $devise,
        'methodes_paiement' => $methodes_paiement,
        'commentaire' => $commentaire
    ]);

    return $pdo->lastInsertId();
}

/**
 * Enregistrer le détail d'une ligne de réponse
 */
function saveReponseDetail($entete_id, $rfq_uuid, $ligne_cotation_id, $code_article, $data) {
    $pdo = getDBConnection();

    // Calculer la date de livraison à partir du délai en jours
    $date_livraison = null;
    if (!empty($data['delai_livraison_jours'])) {
        $date_livraison = date('Y-m-d H:i:s', strtotime('+' . intval($data['delai_livraison_jours']) . ' days'));
    }

    // Gérer le commentaire selon la disponibilité
    $commentaire = $data['commentaire'] ?? null;
    $disponibilite = $data['disponibilite'] ?? 'oui';

    // Si article non disponible, ajouter l'info au commentaire
    if ($disponibilite === 'non') {
        $prefixe = '[ARTICLE NON DISPONIBLE]';
        if (!empty($commentaire)) {
            $commentaire = $prefixe . ' ' . $commentaire;
        } else {
            $commentaire = $prefixe;
        }
    } elseif ($disponibilite === 'partielle') {
        $prefixe = '[DISPONIBILITÉ PARTIELLE]';
        if (!empty($commentaire)) {
            $commentaire = $prefixe . ' ' . $commentaire;
        } else {
            $commentaire = $prefixe;
        }
    }

    $sql = "INSERT INTO reponses_fournisseurs_detail
            (reponse_entete_id, rfq_uuid, ligne_cotation_id, code_article,
             prix_unitaire_ht, date_livraison, quantite_disponible,
             marque_conforme, marque_proposee, fichier_joint_url, commentaire_article)
            VALUES (:entete_id, :rfq_uuid, :ligne_id, :code_article,
                    :prix, :date_livraison, :qty_dispo,
                    :marque_conforme, :marque_proposee, :fichier, :commentaire)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'entete_id' => $entete_id,
        'rfq_uuid' => $rfq_uuid,
        'ligne_id' => $ligne_cotation_id,
        'code_article' => $code_article,
        'prix' => $data['prix_unitaire_ht'] ?? null,
        'date_livraison' => $date_livraison,
        'qty_dispo' => $data['quantite_disponible'] ?? null,
        'marque_conforme' => isset($data['marque_conforme']) ? (int)$data['marque_conforme'] : 1,
        'marque_proposee' => $data['marque_proposee'] ?? null,
        'fichier' => $data['fichier_joint_url'] ?? null,
        'commentaire' => $commentaire
    ]);

    return $pdo->lastInsertId();
}

/**
 * Mettre à jour le statut de la RFQ
 */
function updateRFQStatut($rfq_uuid, $statut) {
    $pdo = getDBConnection();

    $sql = "UPDATE demandes_cotation
            SET statut = :statut,
                date_reponse = NOW(),
                ip_reponse = :ip
            WHERE uuid = :uuid";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'statut' => $statut,
        'ip' => getClientIP(),
        'uuid' => $rfq_uuid
    ]);

    return $stmt->rowCount() > 0;
}

/**
 * Enregistrer l'historique des prix
 */
function saveHistoriquePrix($code_article, $code_fournisseur, $prix_ht, $quantite, $numero_rfq) {
    $pdo = getDBConnection();

    // Récupérer infos fournisseur et article
    $sql = "SELECT f.nom_fournisseur, lc.designation_article, lc.unite
            FROM fournisseurs f
            JOIN demandes_cotation dc ON f.code_fournisseur = dc.code_fournisseur
            JOIN lignes_cotation lc ON dc.uuid = lc.rfq_uuid
            WHERE f.code_fournisseur = :code_fournisseur
            AND lc.code_article = :code_article
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['code_fournisseur' => $code_fournisseur, 'code_article' => $code_article]);
    $info = $stmt->fetch();

    $sql = "INSERT INTO historique_prix
            (code_article, designation_article, code_fournisseur, nom_fournisseur,
             prix_unitaire_ht, quantite, unite, date_prix, source, numero_reference)
            VALUES (:code_article, :designation, :code_fournisseur, :nom_fournisseur,
                    :prix, :quantite, :unite, NOW(), 'cotation', :numero_ref)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'code_article' => $code_article,
        'designation' => $info['designation_article'] ?? null,
        'code_fournisseur' => $code_fournisseur,
        'nom_fournisseur' => $info['nom_fournisseur'] ?? null,
        'prix' => $prix_ht,
        'quantite' => $quantite,
        'unite' => $info['unite'] ?? null,
        'numero_ref' => $numero_rfq
    ]);
}

/**
 * Vérifier si RFQ est rejetée
 */
function isRejected($rfq_uuid) {
    $pdo = getDBConnection();
    
    $sql = "SELECT statut FROM demandes_cotation WHERE uuid = :uuid";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uuid' => $rfq_uuid]);
    
    $result = $stmt->fetch();
    return $result && $result['statut'] === 'rejete';
}

/**
 * Nettoyer input
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Valider UUID format
 */
function isValidUUID($uuid) {
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    return preg_match($pattern, $uuid) === 1;
}

/**
 * Obtenir IP client
 */
function getClientIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * Envoyer webhook à n8n
 */
function sendWebhookToN8n($data) {
    $ch = curl_init(N8N_WEBHOOK_URL);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'response' => $response,
        'http_code' => $httpCode
    ];
}

/**
 * Logger dans logs_systeme
 */
function logSystem($niveau, $module, $action, $message, $donnees_json = null) {
    try {
        $pdo = getDBConnection();
        
        $sql = "INSERT INTO logs_systeme 
                (niveau, module, action, message, donnees_json, ip_address, date_log)
                VALUES (:niveau, :module, :action, :message, :donnees_json, :ip, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'niveau' => $niveau,
            'module' => $module,
            'action' => $action,
            'message' => $message,
            'donnees_json' => $donnees_json ? json_encode($donnees_json) : null,
            'ip' => getClientIP()
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur log système: " . $e->getMessage());
        return false;
    }
}

/**
 * Formater date FR
 */
function formatDateFR($date) {
    if (!$date) return '-';
    
    $timestamp = strtotime($date);
    return date('d/m/Y à H:i', $timestamp);
}
?>