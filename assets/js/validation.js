/**
 * ════════════════════════════════════════════════════════════
 * VALIDATION FORMULAIRE FOURNISSEUR
 * ════════════════════════════════════════════════════════════
 */

document.addEventListener('DOMContentLoaded', function() {

    const form = document.getElementById('formReponse');
    const loading = document.getElementById('loading');

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
 * Afficher/masquer le champ quantité partielle
 */
function toggleQuantitePartielle(index, value) {
    const qtyGroup = document.getElementById('qty_group_' + index);
    const qtyInput = document.getElementById('qty_' + index);

    if (qtyGroup) {
        if (value === 'partielle') {
            qtyGroup.style.display = 'block';
            if (qtyInput) qtyInput.required = true;
        } else {
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
