<?php
/**
 * ════════════════════════════════════════════════════════════
 * PAGE : Confirmation de succès
 * ════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../includes/functions.php';

$uuid = isset($_GET['uuid']) ? cleanInput($_GET['uuid']) : '';
$rfq = null;

if (!empty($uuid) && isValidUUID($uuid)) {
    $rfq = getRFQByUUID($uuid);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réponse envoyée avec succès</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">

    <!-- Header -->
    <div class="header">
        <h1>Portail Fournisseur</h1>
        <?php if ($rfq): ?>
        <p class="rfq-number">Référence: <?= htmlspecialchars($rfq['numero_rfq']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Message de succès -->
    <div class="message-container">
        <div class="message-icon" style="color: #28a745;">
            &#10004;
        </div>

        <h1>Merci pour votre réponse !</h1>

        <p>
            Votre cotation a été enregistrée avec succès.<br>
            Notre équipe achats va analyser votre offre et reviendra vers vous si nécessaire.
        </p>

        <?php if ($rfq): ?>
        <div class="alert alert-success" style="max-width: 500px; margin: 0 auto 30px;">
            <strong>Récapitulatif:</strong><br>
            Demande: <?= htmlspecialchars($rfq['numero_rfq']) ?><br>
            Fournisseur: <?= htmlspecialchars($rfq['nom_fournisseur']) ?><br>
            Date de réponse: <?= date('d/m/Y à H:i') ?>
        </div>
        <?php endif; ?>

        <p style="color: #666; font-size: 14px;">
            Vous pouvez fermer cette page.<br>
            Un email de confirmation vous sera envoyé prochainement.
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?= date('Y') ?> - Service Achats - Tous droits réservés</p>
    </div>

</div>

</body>
</html>
