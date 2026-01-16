<?php
/**
 * ════════════════════════════════════════════════════════════
 * PAGE : Affichage des erreurs
 * ════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../includes/functions.php';

$code = isset($_GET['code']) ? cleanInput($_GET['code']) : 'unknown';

// Messages d'erreur selon le code
$erreurs = [
    'invalid_uuid' => [
        'titre' => 'Lien invalide',
        'message' => 'Le lien que vous avez utilisé est invalide ou mal formé. Veuillez vérifier le lien reçu par email.',
        'icon' => '&#128279;' // lien cassé
    ],
    'rfq_not_found' => [
        'titre' => 'Demande non trouvée',
        'message' => 'La demande de cotation correspondante n\'a pas été trouvée dans notre système. Elle a peut-être été supprimée ou le lien est incorrect.',
        'icon' => '&#128269;' // loupe
    ],
    'already_responded' => [
        'titre' => 'Réponse déjà envoyée',
        'message' => 'Vous avez déjà répondu à cette demande de cotation. Une seule réponse est autorisée par demande.',
        'icon' => '&#9989;' // check vert
    ],
    'already_rejected' => [
        'titre' => 'Demande déjà rejetée',
        'message' => 'Cette demande de cotation a déjà été rejetée. Il n\'est plus possible d\'y répondre.',
        'icon' => '&#10060;' // croix rouge
    ],
    'no_articles' => [
        'titre' => 'Aucun article',
        'message' => 'Cette demande de cotation ne contient aucun article. Veuillez contacter notre service achats.',
        'icon' => '&#128230;' // boîte vide
    ],
    'invalid_method' => [
        'titre' => 'Méthode non autorisée',
        'message' => 'La méthode d\'accès à cette page n\'est pas autorisée. Veuillez utiliser le formulaire.',
        'icon' => '&#9940;' // interdit
    ],
    'no_data' => [
        'titre' => 'Données manquantes',
        'message' => 'Les données du formulaire n\'ont pas été reçues correctement. Veuillez réessayer.',
        'icon' => '&#128196;' // document
    ],
    'validation_failed' => [
        'titre' => 'Erreur de validation',
        'message' => 'Certaines données saisies sont invalides. Veuillez vérifier votre saisie et réessayer.',
        'icon' => '&#9888;' // warning
    ],
    'database_error' => [
        'titre' => 'Erreur technique',
        'message' => 'Une erreur technique s\'est produite lors de l\'enregistrement. Veuillez réessayer dans quelques instants ou contacter notre service achats.',
        'icon' => '&#128187;' // ordinateur
    ],
    'expired' => [
        'titre' => 'Demande expirée',
        'message' => 'Cette demande de cotation a expiré. Le délai de réponse est dépassé.',
        'icon' => '&#9200;' // horloge
    ],
    'unknown' => [
        'titre' => 'Erreur',
        'message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer ou contacter notre service achats.',
        'icon' => '&#10067;' // point d'interrogation
    ]
];

// Récupérer les infos de l'erreur
$erreur = isset($erreurs[$code]) ? $erreurs[$code] : $erreurs['unknown'];

// Récupérer les erreurs de validation si présentes
$erreurs_validation = isset($_SESSION['erreurs_validation']) ? $_SESSION['erreurs_validation'] : [];
unset($_SESSION['erreurs_validation']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($erreur['titre']) ?> - Portail Fournisseur</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">

    <!-- Header -->
    <div class="header" style="background: linear-gradient(135deg, #dc3545 0%, #a71d2a 100%);">
        <h1>Portail Fournisseur</h1>
        <p class="rfq-number">Une erreur s'est produite</p>
    </div>

    <!-- Message d'erreur -->
    <div class="message-container">
        <div class="message-icon" style="color: #dc3545;">
            <?= $erreur['icon'] ?>
        </div>

        <h1><?= htmlspecialchars($erreur['titre']) ?></h1>

        <p><?= htmlspecialchars($erreur['message']) ?></p>

        <?php if (!empty($erreurs_validation)): ?>
        <div class="alert alert-error" style="max-width: 600px; margin: 0 auto 30px; text-align: left;">
            <strong>Détails des erreurs:</strong>
            <ul style="margin-top: 10px; padding-left: 20px;">
                <?php foreach ($erreurs_validation as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div style="margin-top: 30px;">
            <a href="javascript:history.back()" class="btn btn-secondary">
                &#8592; Retour
            </a>
        </div>

        <p style="margin-top: 30px; color: #666; font-size: 14px;">
            Si le problème persiste, contactez notre service achats:<br>
            <a href="mailto:<?= htmlspecialchars(EMAIL_RESPONSABLE) ?>"><?= htmlspecialchars(EMAIL_RESPONSABLE) ?></a>
        </p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?= date('Y') ?> - Service Achats - Tous droits réservés</p>
    </div>

</div>

</body>
</html>
