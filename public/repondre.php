<?php
/**
 * ════════════════════════════════════════════════════════════
 * PAGE : FORMULAIRE RÉPONSE FOURNISSEUR
 * ════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../includes/functions.php';

// ──────────────────────────────────────────────────────────
// RÉCUPÉRER ET VALIDER UUID
// ──────────────────────────────────────────────────────────

$uuid = isset($_GET['uuid']) ? cleanInput($_GET['uuid']) : '';

if (empty($uuid) || !isValidUUID($uuid)) {
    header('Location: erreur.php?code=invalid_uuid');
    exit;
}

// ──────────────────────────────────────────────────────────
// RÉCUPÉRER DONNÉES RFQ
// ──────────────────────────────────────────────────────────

$rfq = getRFQByUUID($uuid);

if (!$rfq) {
    header('Location: erreur.php?code=rfq_not_found');
    exit;
}

// ──────────────────────────────────────────────────────────
// VÉRIFIER SI DÉJÀ RÉPONDU
// ──────────────────────────────────────────────────────────

if (isAlreadyResponded($uuid)) {
    header('Location: erreur.php?code=already_responded');
    exit;
}

// ──────────────────────────────────────────────────────────
// VÉRIFIER SI REJETÉE
// ──────────────────────────────────────────────────────────

if (isRejected($uuid)) {
    header('Location: erreur.php?code=already_rejected');
    exit;
}

// ──────────────────────────────────────────────────────────
// RÉCUPÉRER ARTICLES
// ──────────────────────────────────────────────────────────

$lignes = getLignesCotation($uuid);

if (empty($lignes)) {
    header('Location: erreur.php?code=no_articles');
    exit;
}

// ──────────────────────────────────────────────────────────
// TRACKING OUVERTURE FORMULAIRE
// ──────────────────────────────────────────────────────────

try {
    $pdo = getDBConnection();
    $pdo->prepare("UPDATE demandes_cotation SET date_clic_formulaire = NOW() WHERE uuid = :uuid")
        ->execute(['uuid' => $uuid]);
} catch (Exception $e) {
    // Silent fail
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Répondre à la demande de cotation - <?= htmlspecialchars($rfq['numero_rfq']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">
    
    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- HEADER -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    
    <div class="header">
        <h1>📋 Demande de Cotation</h1>
        <p class="rfq-number">Référence: <?= htmlspecialchars($rfq['numero_rfq']) ?></p>
    </div>
    
    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- CONTENT -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    
    <div class="content">
        
        <!-- Info Fournisseur -->
        <div class="info-box">
            <h2>Informations</h2>
            <p><strong>Fournisseur:</strong> <?= htmlspecialchars($rfq['nom_fournisseur']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($rfq['email']) ?></p>
            <p><strong>Date d'envoi:</strong> <?= formatDateFR($rfq['date_envoi']) ?></p>
        </div>
        
        <!-- Alert Instructions -->
        <div class="alert alert-info">
            <strong>ℹ️ Instructions:</strong> Veuillez remplir les informations demandées pour chaque article ci-dessous. 
            Les champs marqués d'un astérisque (<span style="color: #dc3545;">*</span>) sont obligatoires.
        </div>
        
        <!-- Formulaire -->
        <form id="formReponse" action="traitement.php" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="uuid" value="<?= htmlspecialchars($uuid) ?>">

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- SECTION ENTÊTE - Informations globales de la réponse -->
            <!-- ═══════════════════════════════════════════════════════════ -->

            <div class="form-section entete-section">
                <h3>Informations générales de votre offre</h3>

                <!-- Référence fournisseur -->
                <div class="form-group">
                    <label for="reference_fournisseur">
                        Votre référence de devis
                    </label>
                    <input
                        type="text"
                        id="reference_fournisseur"
                        name="reference_fournisseur"
                        placeholder="Ex: DEV-2026-001"
                        maxlength="50"
                    >
                    <small>Optionnel - Votre numéro de référence interne</small>
                </div>

                <!-- Devise -->
                <div class="form-group">
                    <label for="devise">
                        Devise <span class="required">*</span>
                    </label>
                    <select id="devise" name="devise" required>
                        <option value="MAD" selected>MAD - Dirham Marocain</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="USD">USD - Dollar Américain</option>
                    </select>
                </div>

                <!-- Fichier devis -->
                <div class="form-group">
                    <label for="fichier_devis">
                        Joindre votre devis (PDF, Word, Excel)
                    </label>
                    <input
                        type="file"
                        id="fichier_devis"
                        name="fichier_devis"
                        accept=".pdf,.doc,.docx,.xls,.xlsx"
                        class="file-input"
                    >
                    <small>Optionnel - Max 10 MB - Formats: PDF, DOC, DOCX, XLS, XLSX</small>
                </div>

                <!-- Commentaire global -->
                <div class="form-group">
                    <label for="commentaire_global">
                        Commentaire général
                    </label>
                    <textarea
                        id="commentaire_global"
                        name="commentaire_global"
                        rows="3"
                        placeholder="Conditions particulières, remarques générales..."
                    ></textarea>
                    <small>Optionnel - Informations complémentaires sur votre offre</small>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- SECTION DÉTAILS - Prix par article -->
            <!-- ═══════════════════════════════════════════════════════════ -->

            <div class="section-title">
                <h3>Détail des prix par article</h3>
                <p>Veuillez renseigner les informations pour chaque article demandé</p>
            </div>

            <?php foreach ($lignes as $index => $ligne): ?>
            
            <div class="form-section">
                <h3>Article <?= ($index + 1) ?> - <?= htmlspecialchars($ligne['code_article']) ?></h3>
                
                <input type="hidden" name="lignes[<?= $index ?>][ligne_id]" value="<?= $ligne['id'] ?>">
                <input type="hidden" name="lignes[<?= $index ?>][code_article]" value="<?= htmlspecialchars($ligne['code_article']) ?>">
                
                <!-- Infos article -->
                <div class="alert alert-warning">
                    <strong>Désignation:</strong> <?= htmlspecialchars($ligne['designation_article']) ?><br>
                    <strong>Quantité demandée:</strong> <?= number_format($ligne['quantite_demandee'], 2, ',', ' ') ?> <?= htmlspecialchars($ligne['unite']) ?><br>
                    <?php if ($ligne['marque_souhaitee']): ?>
                    <strong>Marque souhaitée:</strong> <?= htmlspecialchars($ligne['marque_souhaitee']) ?>
                    <?php endif; ?>
                </div>
                
                <!-- Prix unitaire HT -->
                <div class="form-group">
                    <label for="prix_<?= $index ?>">
                        Prix unitaire HT (MAD) <span class="required">*</span>
                    </label>
                    <input 
                        type="number" 
                        step="0.0001" 
                        id="prix_<?= $index ?>" 
                        name="lignes[<?= $index ?>][prix_unitaire_ht]"
                        placeholder="Ex: 15.50"
                        required
                    >
                    <small>Prix hors taxe par unité</small>
                </div>
                
                <!-- Délai livraison -->
                <div class="form-group">
                    <label for="delai_<?= $index ?>">
                        Délai de livraison (jours) <span class="required">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="delai_<?= $index ?>" 
                        name="lignes[<?= $index ?>][delai_livraison_jours]"
                        placeholder="Ex: 7"
                        min="0"
                        required
                    >
                    <small>Nombre de jours ouvrés</small>
                </div>
                
                <!-- Disponibilité -->
                <div class="form-group">
                    <label>
                        Disponibilité <span class="required">*</span>
                    </label>
                    <div class="radio-group">
                        <label>
                            <input 
                                type="radio" 
                                name="lignes[<?= $index ?>][disponibilite]" 
                                value="oui"
                                checked
                                onchange="toggleQuantitePartielle(<?= $index ?>, 'oui')"
                            >
                            Oui (totale)
                        </label>
                        <label>
                            <input 
                                type="radio" 
                                name="lignes[<?= $index ?>][disponibilite]" 
                                value="partielle"
                                onchange="toggleQuantitePartielle(<?= $index ?>, 'partielle')"
                            >
                            Partielle
                        </label>
                        <label>
                            <input 
                                type="radio" 
                                name="lignes[<?= $index ?>][disponibilite]" 
                                value="non"
                                onchange="toggleQuantitePartielle(<?= $index ?>, 'non')"
                            >
                            Non disponible
                        </label>
                    </div>
                </div>
                
                <!-- Quantité disponible (si partielle) -->
                <div class="form-group" id="qty_group_<?= $index ?>" style="display:none;">
                    <label for="qty_<?= $index ?>">
                        Quantité disponible <span class="required">*</span>
                    </label>
                    <input 
                        type="number" 
                        step="0.01" 
                        id="qty_<?= $index ?>" 
                        name="lignes[<?= $index ?>][quantite_disponible]"
                        placeholder="Quantité que vous pouvez fournir"
                    >
                </div>
                
                <!-- Marque conforme -->
                <?php if ($ligne['marque_souhaitee']): ?>
                <div class="form-group">
                    <label>
                        Marque demandée disponible? <span class="required">*</span>
                    </label>
                    <div class="radio-group">
                        <label>
                            <input 
                                type="radio" 
                                name="lignes[<?= $index ?>][marque_conforme]" 
                                value="1"
                                onchange="toggleMarqueAlternative(<?= $index ?>, true)"
                                checked
                            >
                            Oui
                        </label>
                        <label>
                            <input 
                                type="radio" 
                                name="lignes[<?= $index ?>][marque_conforme]" 
                                value="0"
                                onchange="toggleMarqueAlternative(<?= $index ?>, false)"
                            >
                            Non (autre marque disponible)
                        </label>
                    </div>
                </div>
                
                <!-- Marque proposée (si différente) -->
                <div class="form-group" id="marque_group_<?= $index ?>" style="display:none;">
                    <label for="marque_<?= $index ?>">
                        Marque proposée <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="marque_<?= $index ?>" 
                        name="lignes[<?= $index ?>][marque_proposee]"
                        placeholder="Nom de la marque disponible"
                    >
                </div>
                <?php else: ?>
                <input type="hidden" name="lignes[<?= $index ?>][marque_conforme]" value="1">
                <?php endif; ?>
                
                <!-- Référence fournisseur -->
                <div class="form-group">
                    <label for="ref_<?= $index ?>">
                    Votre référence interne
                    </label>
                    <input 
                        type="text" 
                        id="ref_<?= $index ?>" 
                        name="lignes[<?= $index ?>][reference_fournisseur]"
                        placeholder="Ex: REF-12345"
                    >
                    <small>Optionnel</small>
                </div>
                <!-- Commentaire -->
                <div class="form-group">
                    <label for="comment_<?= $index ?>">
                        Commentaire
                    </label>
                    <textarea 
                        id="comment_<?= $index ?>" 
                        name="lignes[<?= $index ?>][commentaire]"
                        placeholder="Informations complémentaires..."
                    ></textarea>
                    <small>Optionnel</small>
                </div>
                
            </div>
            
            <?php endforeach; ?>
            
            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block">
                    ✅ Envoyer ma réponse
                </button>
            </div>
            
        </form>
    
    <!-- Loading -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Envoi en cours...</p>
    </div>
    
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- FOOTER -->
<!-- ═══════════════════════════════════════════════════════════ -->

<div class="footer">
    <p>© <?= date('Y') ?> - Service Achats - Tous droits réservés</p>
</div>
</div>
<script src="../assets/js/validation.js"></script>
</body>
</html>