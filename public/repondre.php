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
        <!--form id="formReponse" action="traitement.php" method="POST" enctype="multipart/form-data"-->
        <form id="formReponse" method="POST" action="https://bjaai.jbel-annour.site/webhook/proxy-formulaire-post/formulairee/<?= htmlspecialchars($uuid) ?>" enctype="multipart/form-data">
            <input type="hidden" name="uuid" value="<?= htmlspecialchars($uuid) ?>">

            <!-- ═══════════════════════════════════════════════════════════ -->
            <!-- SECTION ENTÊTE - Informations globales de la réponse -->
            <!-- ══════════════════════════════════════════════════════════════════ -->

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

                <!-- Méthodes de paiement -->
                <div class="form-group">
                    <label for="methodes_paiement">
                        Méthodes de paiement acceptées
                    </label>
                    <input
                        type="text"
                        id="methodes_paiement"
                        name="methodes_paiement"
                        placeholder="Ex: Chèque, Virement, Crédit client"
                        maxlength="255"
                    >
                    <small>Optionnel - Indiquez les modes de paiement que vous acceptez</small>
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
                
                <!-- Quantité demandée (caché pour remplissage auto JS) -->
                <input type="hidden" id="quantite_demandee_<?= $index ?>" value="<?= $ligne['quantite_demandee'] ?>" />
                
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
<script>
/**
 * ════════════════════════════════════════════════════════════
 * VALIDATION FORMULAIRE FOURNISSEUR
 * ════════════════════════════════════════════════════════════
 */

document.addEventListener('DOMContentLoaded', function() {

    const form = document.getElementById('formReponse');
    const loading = document.getElementById('loading');

    // ──────────────────────────────────────────────────────────
    // AUTO-REMPLISSAGE QUANTITÉ DISPONIBLE AU CHARGEMENT
    // ──────────────────────────────────────────────────────────
    
    // Remplir les champs si "oui" est sélectionné par défaut
    const radios = document.querySelectorAll('input[name^="lignes["][name$="[disponibilite]"]');
    radios.forEach(radio => {
        if (radio.value === 'oui' && radio.checked) {
            const match = radio.name.match(/lignes\[(\d+)\]/);
            if (match) {
                const index = match[1];
                toggleQuantitePartielle(index, 'oui');
            }
        }
    });

    // ──────────────────────────────────────────────────────────
    // AUTO-AFFICHAGE/MASQUAGE MARQUE PROPOSÉE AU CHARGEMENT
    // ──────────────────────────────────────────────────────────

    const marqueRadios = document.querySelectorAll('input[name^="lignes["][name$="[marque_conforme]"]');
    marqueRadios.forEach(radio => {
        const match = radio.name.match(/lignes\[(\d+)\]/);
        if (match) {
            const index = match[1];
            const checkedRadio = document.querySelector('input[name="lignes[' + index + '][marque_conforme]"]:checked');
            if (checkedRadio) {
                const isConforme = checkedRadio.value === '1';
                toggleMarqueAlternative(index, isConforme);
            }
        }
    });

    // ──────────────────────────────────────────────────────────
    // SOUMISSION DU FORMULAIRE
    // ──────────────────────────────────────────────────────────

    if (form) {
        form.addEventListener('submit', function(e) {
            // Valider avant envoi
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }

            // Afficher le loader
            if (loading) {
                loading.classList.add('show');
            }

            // Désactiver le bouton submit
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Envoi en cours...';
            }
        });
    }

    // ──────────────────────────────────────────────────────────
    // VALIDATION DU FORMULAIRE
    // ──────────────────────────────────────────────────────────

    function validateForm() {
        let isValid = true;
        clearErrors();

        // Valider la devise
        const devise = document.getElementById('devise');
        if (devise && !devise.value) {
            showError(devise, 'Veuillez sélectionner une devise');
            isValid = false;
        }

        // Valider le fichier si présent
        const fichierDevis = document.getElementById('fichier_devis');
        if (fichierDevis && fichierDevis.files.length > 0) {
            const file = fichierDevis.files[0];
            const maxSize = 10 * 1024 * 1024; // 10 MB
            const allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
            const extension = file.name.split('.').pop().toLowerCase();

            if (file.size > maxSize) {
                showError(fichierDevis, 'Le fichier ne doit pas dépasser 10 MB');
                isValid = false;
            }

            if (!allowedTypes.includes(extension)) {
                showError(fichierDevis, 'Format de fichier non autorisé');
                isValid = false;
            }
        }

        // Valider chaque ligne d'article
        const lignes = document.querySelectorAll('.form-section:not(.entete-section)');
        lignes.forEach(function(ligne, index) {
            // Ignorer la section titre
            if (ligne.classList.contains('section-title')) return;

            const disponibilite = ligne.querySelector('input[name*="[disponibilite]"]:checked');
            if (!disponibilite) return;

            const dispoValue = disponibilite.value;

            // Si disponible ou partiel, valider prix et délai
            if (dispoValue !== 'non') {
                const prixInput = ligne.querySelector('input[name*="[prix_unitaire_ht]"]');
                if (prixInput && (!prixInput.value || parseFloat(prixInput.value) <= 0)) {
                    showError(prixInput, 'Prix unitaire obligatoire et doit être positif');
                    isValid = false;
                }

                const delaiInput = ligne.querySelector('input[name*="[delai_livraison_jours]"]');
                if (delaiInput && (!delaiInput.value || parseInt(delaiInput.value) < 0)) {
                    showError(delaiInput, 'Délai de livraison obligatoire');
                    isValid = false;
                }
            }

            // Si partiel, valider quantité disponible
            if (dispoValue === 'partielle') {
                const qtyInput = ligne.querySelector('input[name*="[quantite_disponible]"]');
                if (qtyInput && (!qtyInput.value || parseFloat(qtyInput.value) <= 0)) {
                    showError(qtyInput, 'Quantité disponible obligatoire');
                    isValid = false;
                }
            }

            // Si marque non conforme, valider marque proposée
            const marqueConforme = ligne.querySelector('input[name*="[marque_conforme]"]:checked');
            if (marqueConforme && marqueConforme.value === '0') {
                const marqueInput = ligne.querySelector('input[name*="[marque_proposee]"]');
                if (marqueInput && !marqueInput.value.trim()) {
                    showError(marqueInput, 'Veuillez indiquer la marque proposée');
                    isValid = false;
                }
            }
        });

        if (!isValid) {
            // Scroll vers la première erreur
            const firstError = document.querySelector('.form-group.has-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return isValid;
    }

    // ──────────────────────────────────────────────────────────
    // AFFICHER/MASQUER ERREURS
    // ──────────────────────────────────────────────────────────

    function showError(input, message) {
        const formGroup = input.closest('.form-group');
        if (formGroup) {
            formGroup.classList.add('has-error');

            // Créer message d'erreur s'il n'existe pas
            let errorMsg = formGroup.querySelector('.error-message');
            if (!errorMsg) {
                errorMsg = document.createElement('span');
                errorMsg.className = 'error-message show';
                formGroup.appendChild(errorMsg);
            }
            errorMsg.textContent = message;
            errorMsg.classList.add('show');
        }
    }

    function clearErrors() {
        document.querySelectorAll('.form-group.has-error').forEach(function(group) {
            group.classList.remove('has-error');
        });
        document.querySelectorAll('.error-message').forEach(function(msg) {
            msg.classList.remove('show');
        });
    }

});

// ════════════════════════════════════════════════════════════
// FONCTIONS GLOBALES (appelées depuis le HTML)
// ════════════════════════════════════════════════════════════

/**
 * Afficher/masquer le champ quantité partielle et remplissage auto si "oui"
 */
function toggleQuantitePartielle(index, value) {
    const qtyGroup = document.getElementById('qty_group_' + index);
    const qtyInput = document.getElementById('qty_' + index);
    const quantiteDemandeeInput = document.getElementById('quantite_demandee_' + index);
    console.log('Toggle quantité partielle pour index', index, 'avec valeur', value);
    console.log('Quantité demandée input:', quantiteDemandeeInput.value);
    if (qtyGroup) {
        if (value === 'oui') {
            // Remplissage automatique avec quantité demandée
            qtyGroup.style.display = 'none';
            if (qtyInput && quantiteDemandeeInput) {
                let demandedValue = quantiteDemandeeInput.value;
                console.log('Quantité demandée:', demandedValue);
                // Parser le nombre (gérer les formats français avec virgule)
                if (demandedValue) {
                    demandedValue = String(demandedValue).replace(',', '.');
                    demandedValue = parseFloat(demandedValue) || '';
                }
                qtyInput.value = demandedValue;
                console.log('Quantité remplie automatiquement:', qtyInput.value);
                qtyInput.required = false;
            }
        } else if (value === 'partielle') {
            // Afficher le champ pour saisie manuelle
            qtyGroup.style.display = 'block';
            if (qtyInput) {
                qtyInput.required = true;
                qtyInput.value = '';
            }
        } else {
            // "non" disponible
            qtyGroup.style.display = 'none';
            if (qtyInput) {
                qtyInput.required = false;
                qtyInput.value = '';
            }
        }
    }

    // Si non disponible, désactiver prix et délai
    const prixInput = document.querySelector('input[name="lignes[' + index + '][prix_unitaire_ht]"]');
    const delaiInput = document.querySelector('input[name="lignes[' + index + '][delai_livraison_jours]"]');

    if (value === 'non') {
        if (prixInput) {
            prixInput.disabled = true;
            prixInput.value = '';
            prixInput.required = false;
        }
        if (delaiInput) {
            delaiInput.disabled = true;
            delaiInput.value = '';
            delaiInput.required = false;
        }
    } else {
        if (prixInput) {
            prixInput.disabled = false;
            prixInput.required = true;
        }
        if (delaiInput) {
            delaiInput.disabled = false;
            delaiInput.required = true;
        }
    }
}

/**
 * Afficher/masquer le champ marque alternative
 */
function toggleMarqueAlternative(index, conforme) {
    const marqueGroup = document.getElementById('marque_group_' + index);
    const marqueInput = document.getElementById('marque_' + index);

    if (marqueGroup) {
        if (conforme) {
            marqueGroup.style.display = 'none';
            if (marqueInput) {
                marqueInput.required = false;
                marqueInput.value = '';
            }
        } else {
            marqueGroup.style.display = 'block';
            if (marqueInput) marqueInput.required = true;
        }
    }
}
</script>
<!-- <script src="../assets/js/validation.js"></script> -->

</body>
</html>