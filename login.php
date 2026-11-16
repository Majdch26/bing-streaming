<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

if (utilisateur_courant()) {
    rediriger(utilisateur_courant()['est_admin'] ? 'admin/' : 'profiles.php');
}

$email = '';
$erreur = null;

if (est_post()) {
    verifier_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $motDePasse = (string) ($_POST['password'] ?? '');

    // Petit frein contre les essais de mots de passe en série
    $_SESSION['essais'] = ($_SESSION['essais'] ?? 0) + 1;
    if ($_SESSION['essais'] > 5) {
        sleep(min(5, $_SESSION['essais'] - 5));
    }

    $utilisateur = requete('SELECT * FROM utilisateur WHERE email = ?', [$email])->fetch();
    if ($utilisateur && password_verify($motDePasse, $utilisateur['mot_de_passe_hash'])) {
        if (password_needs_rehash($utilisateur['mot_de_passe_hash'], PASSWORD_DEFAULT)) {
            requete('UPDATE utilisateur SET mot_de_passe_hash = ? WHERE id = ?',
                    [password_hash($motDePasse, PASSWORD_DEFAULT), $utilisateur['id']]);
        }
        session_regenerate_id(true);
        unset($_SESSION['essais'], $_SESSION['profile_id']);
        $_SESSION['user_id'] = (int) $utilisateur['id'];
        rediriger($utilisateur['est_admin'] ? 'admin/' : 'profiles.php');
    }
    $erreur = 'Invalid email or password.';
}

entete('Sign in', ['login.css']);
?>
  <div class="login-container">
    <img class="logo" src="<?= url('images/logo.png') ?>" alt="BING">
    <div class="login-box">
      <h1 class="login-title">Sign In</h1>

      <?php if ($erreur): ?>
        <p class="error-message"><?= e($erreur) ?></p>
      <?php endif; ?>

      <form method="POST" action="<?= url('login.php') ?>">
        <?= champ_csrf() ?>
        <div class="form-group">
          <label for="email">Email Address</label>
          <i class="fas fa-envelope input-icon"></i>
          <input type="email" id="email" name="email" value="<?= e($email) ?>" placeholder="Enter your email" required autofocus autocomplete="email">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <i class="fas fa-lock input-icon"></i>
          <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
        </div>

        <button type="submit" class="submit-button">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>

        <p class="demo-hint">Demo account: <strong>demo@bing.test</strong> / <strong>Demo1234!</strong></p>

        <div class="signup-prompt">
          New to BING? <a href="<?= url('pricing.php') ?>">Start your subscription now</a>
        </div>
      </form>
    </div>
  </div>
<?php pied(['login.js']); ?>
