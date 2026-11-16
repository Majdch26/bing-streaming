<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$utilisateur = exiger_connexion();
$recap = $_SESSION['paiement_termine'] ?? null;
if (!$recap) {
    rediriger('profiles.php');
}
unset($_SESSION['paiement_termine']);
$abonnement = abonnement_courant((int) $utilisateur['id']);
$inscription = $recap['type'] === 'signup';

entete($inscription ? 'Welcome' : 'Plan updated', ['success.css']);
?>
    <div class="success-container">
        <div class="progress-steps">
            <?php foreach ([$inscription ? 'Account' : 'Plan', 'Payment', 'Complete'] as $etape): ?>
            <div class="progress-step">
                <div class="step-number completed"><i class="fas fa-check"></i></div>
                <div class="step-label completed"><?= $etape ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="success-card">
            <div class="success-icon"><i class="fas fa-check"></i></div>

            <h1 class="success-title"><?= $inscription ? 'Account Created Successfully!' : 'Your plan is updated!' ?></h1>

            <p class="success-message">
                <?php if ($inscription): ?>
                    Welcome to BING, <?= e($utilisateur['nom']) ?>! Your <?= e($recap['offre']) ?> plan is now active.
                <?php else: ?>
                    You're now subscribed to the <?= e($recap['offre']) ?> plan. The change takes effect immediately.
                <?php endif; ?>
            </p>

            <div class="user-info">
                <div class="info-row">
                    <span class="info-label">Account:</span>
                    <span class="info-value"><?= e($utilisateur['email']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Plan:</span>
                    <span class="info-value"><span class="plan-badge"><?= e($recap['offre']) ?></span></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Paid today:</span>
                    <span class="info-value">$<?= number_format($recap['montant'], 2) ?> (card ending in <?= e($recap['carte']) ?>)</span>
                </div>
                <?php if ($abonnement): ?>
                <div class="info-row">
                    <span class="info-label">Renews on:</span>
                    <span class="info-value"><?= e(date('F j, Y', strtotime($abonnement['date_fin'] . ' +1 day'))) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($inscription): ?>
            <div class="next-steps">
                <h3>Next steps:</h3>
                <ul>
                    <li>Create your viewing profiles</li>
                    <li>Start watching</li>
                </ul>
            </div>
            <?php endif; ?>

            <a href="<?= url($inscription ? 'profiles.php' : 'account.php') ?>" class="continue-button">
                <?= $inscription ? 'Create Profiles' : 'Back to Account' ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
<?php pied(); ?>
