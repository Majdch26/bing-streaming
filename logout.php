<?php
require __DIR__ . '/includes/bootstrap.php';

// La déconnexion se fait par un formulaire POST, pour qu'un simple lien ne puisse pas déconnecter
if (est_post()) {
    verifier_csrf();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
rediriger('login.php');
