<?php
/* Ajoute ou retire une vidéo de « Ma liste » du profil en cours.
   Répond en JSON pour JavaScript, ou redirige si le formulaire est envoyé sans JavaScript. */
require __DIR__ . '/../includes/bootstrap.php';

$enJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
if (!est_post()) {
    json(['error' => 'POST required'], 405);
}
verifier_csrf();

$utilisateur = utilisateur_courant();
$profil = $utilisateur && isset($_SESSION['profile_id'])
    ? requete('SELECT * FROM profils WHERE id = ? AND id_utilisateur = ?', [$_SESSION['profile_id'], $utilisateur['id']])->fetch()
    : null;
if (!$profil) {
    $enJson ? json(['error' => 'Choose a profile first'], 401) : rediriger('profiles.php');
}

$action = $_POST['action'] ?? 'toggle';
$idVideo = (int) ($_POST['video_id'] ?? 0);
$favori = false;

try {
    if ($action === 'clear') {
        requete('DELETE FROM favoris WHERE id_profil = ?', [$profil['id']]);
    } else {
        if (!requete('SELECT 1 FROM video WHERE id = ?', [$idVideo])->fetchColumn()) {
            throw new RuntimeException('This title does not exist.');
        }
        $existe = (bool) requete('SELECT 1 FROM favoris WHERE id_profil = ? AND id_video = ?', [$profil['id'], $idVideo])->fetchColumn();
        if ($existe && $action !== 'add') {
            requete('DELETE FROM favoris WHERE id_profil = ? AND id_video = ?', [$profil['id'], $idVideo]);
        } elseif (!$existe && $action !== 'remove') {
            requete('INSERT INTO favoris (id_profil, id_video) VALUES (?, ?)', [$profil['id'], $idVideo]);
            $favori = true;
        } else {
            $favori = $existe;
        }
    }
} catch (Throwable $e) {
    $message = message_erreur($e);
    if ($enJson) {
        json(['error' => $message], 422);
    }
    flash('error', $message);
}

$total = (int) requete('SELECT COUNT(*) FROM favoris WHERE id_profil = ?', [$profil['id']])->fetchColumn();
if ($enJson) {
    json(['favorite' => $favori, 'total' => $total]);
}

// Retour à la page d'origine si elle fait partie du site
$retour = $_SERVER['HTTP_REFERER'] ?? '';
$hote = $_SERVER['HTTP_HOST'] ?? '';
if ($retour !== '' && parse_url($retour, PHP_URL_HOST) === parse_url('http://' . $hote, PHP_URL_HOST)) {
    header('Location: ' . $retour);
    exit;
}
rediriger('index.php');
