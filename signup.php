<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if (utilisateur_courant()) {
    rediriger('account.php#plan');
}

$offre = requete('SELECT * FROM offre WHERE id = ? AND disponible = 1', [(int) ($_GET['plan'] ?? $_POST['plan'] ?? 0)])->fetch();
if (!$offre) {
    rediriger('pricing.php');
}

$nom = $email = '';
$erreurs = [];

if (est_post()) {
    verifier_csrf();
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $motDePasse = (string) ($_POST['mot_de_passe'] ?? '');
    $confirmation = (string) ($_POST['confirmation'] ?? '');

    if ($nom === '' || mb_strlen($nom) > 60) {
        $erreurs[] = 'Please enter your name (60 characters max).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'Please enter a valid email address.';
    } elseif (requete('SELECT 1 FROM utilisateur WHERE email = ?', [$email])->fetchColumn()) {
        $erreurs[] = 'An account already exists with this email. Please sign in.';
    }
    if (strlen($motDePasse) < 8) {
        $erreurs[] = 'Your password must contain at least 8 characters.';
    } elseif ($motDePasse !== $confirmation) {
        $erreurs[] = 'The two passwords do not match.';
    }

    if (!$erreurs) {
        // Le mot de passe est haché tout de suite : il ne repasse jamais par la page de paiement
        $_SESSION['paiement'] = [
            'type' => 'signup',
            'nom' => $nom,
            'email' => $email,
            'hash' => password_hash($motDePasse, PASSWORD_DEFAULT),
            'offre' => (int) $offre['id'],
            'montant' => (float) $offre['prix_mensuel'],
        ];
        rediriger('payment.php');
    }
}

entete('Sign up', ['signup.css']);
?>
  <div class="signup-container">
    <div class="signup-header">
      <h1 class="signup-title">Create Your Account</h1>
      <p class="signup-subtitle">One step away from unlimited entertainment</p>
      <div class="plan-badge"><?= e($offre['nom']) ?> Plan · $<?= number_format((float) $offre['prix_mensuel'], 2) ?>/month</div>
    </div>

    <div class="progress-steps">
      <div class="progress-step">
        <div class="step-number active">1</div>
        <div class="step-label active">Account</div>
      </div>
      <div class="progress-step">
        <div class="step-number">2</div>
        <div class="step-label">Payment</div>
      </div>
      <div class="progress-step">
        <div class="step-number">3</div>
        <div class="step-label">Complete</div>
      </div>
    </div>

    <?php foreach ($erreurs as $erreur): ?>
      <p class="error-message"><?= e($erreur) ?></p>
    <?php endforeach; ?>

    <form class="signup-form" method="POST" action="<?= url('signup.php?plan=' . (int) $offre['id']) ?>" novalidate>
      <?= champ_csrf() ?>
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="nom" value="<?= e($nom) ?>" required maxlength="60" placeholder="Enter your full name" autocomplete="name">
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" required placeholder="Enter your email" autocomplete="email">
      </div>

      <div class="form-group password-container">
        <label for="password">Password</label>
        <input type="password" id="password" name="mot_de_passe" required minlength="8" placeholder="At least 8 characters" autocomplete="new-password">
        <i class="toggle-password fas fa-eye" data-target="password"></i>
      </div>

      <div class="form-group password-container">
        <label for="confirmation">Confirm Password</label>
        <input type="password" id="confirmation" name="confirmation" required minlength="8" placeholder="Type it again" autocomplete="new-password">
        <i class="toggle-password fas fa-eye" data-target="confirmation"></i>
      </div>

      <button type="submit" class="submit-button">
        Continue to Payment <i class="fas fa-arrow-right"></i>
      </button>

      <div class="login-link">
        Already have an account? <a href="<?= url('login.php') ?>">Sign in</a> ·
        <a href="<?= url('pricing.php') ?>">Change plan</a>
      </div>
    </form>
  </div>
<?php pied(['forms.js']); ?>
