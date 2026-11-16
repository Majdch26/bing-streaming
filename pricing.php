<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if (utilisateur_courant()) {
    rediriger('account.php#plan');
}
$offres = requete('SELECT * FROM offre WHERE disponible = 1 ORDER BY prix_mensuel')->fetchAll();

entete('Choose your plan', ['pricing.css']);
?>
  <div class="plans-container">
    <a href="<?= url('login.php') ?>"><img class="plans-logo" src="<?= url('images/logo.png') ?>" alt="BING"></a>
    <h1 class="plans-title">Choose Your Plan</h1>
    <p class="plans-subtitle">Select the perfect plan for your entertainment needs. Upgrade, downgrade, or cancel anytime.</p>

    <div class="plans-grid">
      <?php foreach ($offres as $offre): ?>
      <a class="plan-card<?= $offre['mise_en_avant'] ? ' popular' : '' ?>" href="<?= url('signup.php?plan=' . (int) $offre['id']) ?>">
        <?php if ($offre['mise_en_avant']): ?><div class="popular-badge">Most Popular</div><?php endif; ?>
        <h3 class="plan-name"><?= e($offre['nom']) ?></h3>
        <div class="plan-price">$<?= number_format((float) $offre['prix_mensuel'], 2) ?><span>/month</span></div>
        <ul class="plan-features">
          <?php foreach (array_filter(explode("\n", $offre['avantages'])) as $avantage): ?>
          <li><?= e(trim($avantage)) ?></li>
          <?php endforeach; ?>
        </ul>
        <span class="select-button">Select Plan</span>
      </a>
      <?php endforeach; ?>
    </div>

    <p class="payment-info">This is a demo: payment is simulated and no card is ever charged.
      Already a member? <a href="<?= url('login.php') ?>">Sign in</a></p>
  </div>
<?php pied(); ?>
