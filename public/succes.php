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
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .header .rfq-number {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Content */
        .content {
            padding: 50px 40px;
            text-align: center;
        }

        /* Checkmark Icon */
        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s ease-out 0.2s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-icon svg {
            width: 40px;
            height: 40px;
            fill: none;
            stroke: white;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .checkmark {
            stroke-dasharray: 50;
            stroke-dashoffset: 50;
            animation: drawCheck 0.5s ease-out 0.5s forwards;
        }

        @keyframes drawCheck {
            to {
                stroke-dashoffset: 0;
            }
        }

        /* Title */
        .content h2 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
        }

        .content .subtitle {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        /* Recap Box */
        .recap-box {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left: 4px solid #28a745;
            border-radius: 8px;
            padding: 25px;
            margin: 0 auto 30px;
            max-width: 450px;
            text-align: center;
        }

        .recap-box h3 {
            font-size: 16px;
            font-weight: 700;
            color: #155724;
            margin-bottom: 15px;
        }

        .recap-box p {
            font-size: 14px;
            color: #155724;
            margin: 8px 0;
            line-height: 1.5;
        }

        .recap-box .label {
            font-weight: 600;
        }

        /* Note */
        .note {
            font-size: 14px;
            color: #888;
            line-height: 1.6;
        }

        .note .email-note {
            color: #28a745;
            margin-top: 5px;
        }

        /* Footer */
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #eee;
        }

        .footer p {
            font-size: 12px;
            color: #999;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .content {
                padding: 30px 20px;
            }

            .content h2 {
                font-size: 22px;
            }

            .recap-box {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Header -->
    <div class="header">
        <h1>🏢 Portail Fournisseur</h1>
        <?php if ($rfq): ?>
        <p class="rfq-number">Référence: <?= htmlspecialchars($rfq['numero_rfq']) ?></p>
        <?php endif; ?>
    </div>

    <!-- Content -->
    <div class="content">
        
        <!-- Animated Checkmark -->
        <div class="success-icon">
            <svg viewBox="0 0 24 24">
                <polyline class="checkmark" points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h2>Merci pour votre réponse !</h2>
        
        <p class="subtitle">
            Votre cotation a été enregistrée avec succès.<br>
            Notre équipe achats va analyser votre offre et reviendra vers vous si nécessaire.
        </p>

        <?php if ($rfq): ?>
        <!-- Recap Box -->
        <div class="recap-box">
            <h3>📋 Récapitulatif</h3>
            <p><span class="label">Demande:</span> <?= htmlspecialchars($rfq['numero_rfq']) ?></p>
            <p><span class="label">Fournisseur:</span> <?= htmlspecialchars($rfq['nom_fournisseur']) ?></p>
            <p><span class="label">Date de réponse:</span> <?= date('d/m/Y à H:i') ?></p>
        </div>
        <?php endif; ?>

        <!-- Note -->
        <p class="note">
            Vous pouvez fermer cette page.<br>
            <span class="email-note">📧 Un email de confirmation vous sera envoyé prochainement.</span>
        </p>

    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?= date('Y') ?> - Service Achats - Tous droits réservés</p>
    </div>

</div>

</body>
</html>
