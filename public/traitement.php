<?php
/**
 * ════════════════════════════════════════════════════════════
 * TRAITEMENT : Réception et enregistrement de la réponse fournisseur
 * ════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../includes/functions.php';

// ──────────────────────────────────────────────────────────
// CONFIGURATION UPLOAD
// ──────────────────────────────────────────────────────────

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);

// ──────────────────────────────────────────────────────────
// VÉRIFICATION MÉTHODE POST
// ──────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: erreur.php?code=invalid_method');
    exit;
}

// ──────────────────────────────────────────────────────────
// RÉCUPÉRATION ET VALIDATION UUID
// ──────────────────────────────────────────────────────────

$uuid = isset($_POST['uuid']) ? cleanInput($_POST['uuid']) : '';

if (empty($uuid) || !isValidUUID($uuid)) {
    logSystem('warning', 'formulaire_php', 'traitement_reponse', 'UUID invalide', ['uuid' => $uuid]);
    header('Location: erreur.php?code=invalid_uuid');
    exit;
}

// ──────────────────────────────────────────────────────────
// VÉRIFICATION RFQ EXISTE
// ──────────────────────────────────────────────────────────

$rfq = getRFQByUUID($uuid);

if (!$rfq) {
    logSystem('warning', 'formulaire_php', 'traitement_reponse', 'RFQ non trouvée', ['uuid' => $uuid]);
    header('Location: erreur.php?code=rfq_not_found');
    exit;
}

// ──────────────────────────────────────────────────────────
// VÉRIFICATION PAS DÉJÀ RÉPONDU
// ──────────────────────────────────────────────────────────

if (isAlreadyResponded($uuid)) {
    logSystem('info', 'formulaire_php', 'traitement_reponse', 'Tentative de double réponse', ['uuid' => $uuid]);
    header('Location: erreur.php?code=already_responded');
    exit;
}

// ──────────────────────────────────────────────────────────
// RÉCUPÉRATION DONNÉES ENTÊTE
// ──────────────────────────────────────────────────────────

$reference_fournisseur = isset($_POST['reference_fournisseur']) ? cleanInput($_POST['reference_fournisseur']) : null;
$devise = isset($_POST['devise']) ? cleanInput($_POST['devise']) : 'MAD';
$commentaire_global = isset($_POST['commentaire_global']) ? cleanInput($_POST['commentaire_global']) : null;

// Validation devise
if (!in_array($devise, ['MAD', 'EUR', 'USD'])) {
    $devise = 'MAD';
}

// ──────────────────────────────────────────────────────────
// TRAITEMENT UPLOAD FICHIER DEVIS
// ──────────────────────────────────────────────────────────

$fichier_devis_url = null;

if (isset($_FILES['fichier_devis']) && $_FILES['fichier_devis']['error'] === UPLOAD_ERR_OK) {
    $fichier_devis_url = handleFileUpload($_FILES['fichier_devis'], $uuid, 'devis');

    if ($fichier_devis_url === false) {
        logSystem('error', 'formulaire_php', 'upload_fichier', 'Échec upload fichier devis', [
            'uuid' => $uuid,
            'file' => $_FILES['fichier_devis']['name']
        ]);
        // On continue même si l'upload échoue (fichier optionnel)
        $fichier_devis_url = null;
    }
}

// ──────────────────────────────────────────────────────────
// RÉCUPÉRATION DONNÉES LIGNES
// ──────────────────────────────────────────────────────────

$lignes = isset($_POST['lignes']) ? $_POST['lignes'] : [];

if (empty($lignes)) {
    logSystem('error', 'formulaire_php', 'traitement_reponse', 'Aucune ligne reçue', ['uuid' => $uuid]);
    header('Location: erreur.php?code=no_data');
    exit;
}

// ──────────────────────────────────────────────────────────
// VALIDATION DES LIGNES
// ──────────────────────────────────────────────────────────

$lignes_validees = [];
$erreurs = [];

foreach ($lignes as $index => $ligne) {
    $ligne_id = isset($ligne['ligne_id']) ? intval($ligne['ligne_id']) : 0;
    $code_article = isset($ligne['code_article']) ? cleanInput($ligne['code_article']) : '';
    $prix_ht = isset($ligne['prix_unitaire_ht']) ? floatval($ligne['prix_unitaire_ht']) : null;
    $delai = isset($ligne['delai_livraison_jours']) ? intval($ligne['delai_livraison_jours']) : null;
    $disponibilite = isset($ligne['disponibilite']) ? cleanInput($ligne['disponibilite']) : 'oui';

    // Validation prix (obligatoire si disponible)
    if ($disponibilite !== 'non' && ($prix_ht === null || $prix_ht <= 0)) {
        $erreurs[] = "Article " . ($index + 1) . ": Prix unitaire HT invalide";
        continue;
    }

    // Validation délai (obligatoire si disponible)
    if ($disponibilite !== 'non' && ($delai === null || $delai < 0)) {
        $erreurs[] = "Article " . ($index + 1) . ": Délai de livraison invalide";
        continue;
    }

    // Quantité disponible si partielle
    $quantite_disponible = null;
    if ($disponibilite === 'partielle') {
        $quantite_disponible = isset($ligne['quantite_disponible']) ? floatval($ligne['quantite_disponible']) : null;
        if ($quantite_disponible === null || $quantite_disponible <= 0) {
            $erreurs[] = "Article " . ($index + 1) . ": Quantité disponible invalide";
            continue;
        }
    } elseif ($disponibilite === 'non') {
        // Si non disponible, on met les valeurs à null
        $prix_ht = null;
        $delai = null;
    }

    // Marque conforme
    $marque_conforme = isset($ligne['marque_conforme']) ? intval($ligne['marque_conforme']) : 1;
    $marque_proposee = null;
    if ($marque_conforme === 0 && isset($ligne['marque_proposee'])) {
        $marque_proposee = cleanInput($ligne['marque_proposee']);
    }

    // Référence et commentaire
    $reference = isset($ligne['reference_fournisseur']) ? cleanInput($ligne['reference_fournisseur']) : null;
    $commentaire = isset($ligne['commentaire']) ? cleanInput($ligne['commentaire']) : null;

    $lignes_validees[] = [
        'ligne_id' => $ligne_id,
        'code_article' => $code_article,
        'prix_unitaire_ht' => $prix_ht,
        'delai_livraison_jours' => $delai,
        'quantite_disponible' => $quantite_disponible,
        'marque_conforme' => $marque_conforme,
        'marque_proposee' => $marque_proposee,
        'reference_fournisseur' => $reference,
        'commentaire' => $commentaire,
        'disponibilite' => $disponibilite
    ];
}

// Si erreurs critiques, rediriger
if (!empty($erreurs) && count($lignes_validees) === 0) {
    $_SESSION['erreurs_validation'] = $erreurs;
    logSystem('warning', 'formulaire_php', 'validation_lignes', 'Toutes les lignes invalides', [
        'uuid' => $uuid,
        'erreurs' => $erreurs
    ]);
    header('Location: erreur.php?code=validation_failed');
    exit;
}

// ──────────────────────────────────────────────────────────
// ENREGISTREMENT EN BASE DE DONNÉES
// ──────────────────────────────────────────────────────────

try {
    $pdo = getDBConnection();
    $pdo->beginTransaction();

    // 1. Enregistrer l'entête
    $entete_id = saveReponseEntete(
        $uuid,
        $reference_fournisseur,
        $fichier_devis_url,
        $devise,
        $commentaire_global
    );

    if (!$entete_id) {
        throw new Exception("Échec enregistrement entête");
    }

    // 2. Enregistrer chaque ligne de détail
    foreach ($lignes_validees as $ligne) {
        // Ne pas enregistrer les articles non disponibles sans info
        if ($ligne['disponibilite'] === 'non' && empty($ligne['commentaire'])) {
            continue;
        }

        $detail_id = saveReponseDetail(
            $entete_id,
            $uuid,
            $ligne['ligne_id'],
            $ligne['code_article'],
            $ligne
        );

        // Enregistrer dans l'historique des prix si prix valide
        if ($ligne['prix_unitaire_ht'] !== null && $ligne['prix_unitaire_ht'] > 0) {
            saveHistoriquePrix(
                $ligne['code_article'],
                $rfq['code_fournisseur'],
                $ligne['prix_unitaire_ht'],
                $ligne['quantite_disponible'],
                $rfq['numero_rfq']
            );
        }
    }

    // 3. Mettre à jour le statut de la RFQ
    updateRFQStatut($uuid, 'repondu');

    // 4. Mettre à jour les stats du fournisseur
    updateFournisseurStats($rfq['code_fournisseur']);

    $pdo->commit();

    // 5. Log succès
    logSystem('info', 'formulaire_php', 'traitement_reponse', 'Réponse enregistrée avec succès', [
        'uuid' => $uuid,
        'numero_rfq' => $rfq['numero_rfq'],
        'fournisseur' => $rfq['code_fournisseur'],
        'nb_lignes' => count($lignes_validees),
        'entete_id' => $entete_id
    ]);

    // 6. Envoyer webhook à n8n
    $webhookData = [
        'event' => 'reponse_fournisseur',
        'uuid' => $uuid,
        'numero_rfq' => $rfq['numero_rfq'],
        'code_fournisseur' => $rfq['code_fournisseur'],
        'nom_fournisseur' => $rfq['nom_fournisseur'],
        'entete_id' => $entete_id,
        'devise' => $devise,
        'nb_lignes' => count($lignes_validees),
        'date_reponse' => date('Y-m-d H:i:s'),
        'ip' => getClientIP()
    ];

    $webhookResult = sendWebhookToN8n($webhookData);

    if (!$webhookResult['success']) {
        logSystem('warning', 'formulaire_php', 'webhook_n8n', 'Échec envoi webhook', [
            'uuid' => $uuid,
            'http_code' => $webhookResult['http_code']
        ]);
    }

    // 7. Redirection vers succès
    header('Location: succes.php?uuid=' . urlencode($uuid));
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    logSystem('error', 'formulaire_php', 'traitement_reponse', 'Erreur enregistrement: ' . $e->getMessage(), [
        'uuid' => $uuid,
        'trace' => $e->getTraceAsString()
    ]);

    header('Location: erreur.php?code=database_error');
    exit;
}

// ════════════════════════════════════════════════════════════
// FONCTIONS UTILITAIRES LOCALES
// ════════════════════════════════════════════════════════════

/**
 * Gérer l'upload d'un fichier
 */
function handleFileUpload($file, $uuid, $type) {
    // Vérifier taille
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }

    // Vérifier extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return false;
    }

    // Créer dossier si nécessaire
    $uploadDir = UPLOAD_DIR . date('Y/m/');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer nom unique
    $filename = $type . '_' . $uuid . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Déplacer fichier
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Retourner chemin relatif pour stockage en BDD
        return 'uploads/' . date('Y/m/') . $filename;
    }

    return false;
}

/**
 * Mettre à jour les statistiques du fournisseur
 */
function updateFournisseurStats($code_fournisseur) {
    $pdo = getDBConnection();

    $sql = "UPDATE fournisseurs SET
            nb_reponses = nb_reponses + 1,
            taux_reponse = ROUND((nb_reponses + 1) / GREATEST(nb_total_rfq, 1) * 100, 2),
            updated_at = NOW()
            WHERE code_fournisseur = :code";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['code' => $code_fournisseur]);
}
?>
