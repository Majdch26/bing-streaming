<?php
/* Enregistre la position de lecture du profil en cours (appelé par le lecteur toutes les 10 secondes) */
require __DIR__ . '/../includes/bootstrap.php';

if (!est_post()) {
    json(['error' => 'POST required'], 405);
}
verifier_csrf();

$utilisateur = utilisateur_courant();
$profil = $utilisateur && isset($_SESSION['profile_id'])
    ? requete('SELECT * FROM profils WHERE id = ? AND id_utilisateur = ?', [$_SESSION['profile_id'], $utilisateur['id']])->fetch()
    : null;
if (!$profil) {
    json(['error' => 'Choose a profile first'], 401);
}

$idFilm = isset($_POST['movie']) ? (int) $_POST['movie'] : null;
$idEpisode = isset($_POST['episode']) ? (int) $_POST['episode'] : null;
$position = max(0, (int) round((float) ($_POST['position'] ?? 0)));
$duree = max(0, (int) round((float) ($_POST['duration'] ?? 0)));

if (($idFilm === null) === ($idEpisode === null)) {
    json(['error' => 'Send either a movie or an episode'], 422);
}
if ($duree > 0) {
    $position = min($position, $duree);
}

try {
    if ($idFilm !== null) {
        requete("INSERT INTO visionnage (id_profil, id_film, position_secondes, duree_secondes)
                 VALUES (:p, :c, :pos, :d)
                 ON CONFLICT (id_profil, id_film) WHERE id_film IS NOT NULL
                 DO UPDATE SET position_secondes = :pos, duree_secondes = MAX(duree_secondes, :d),
                               date_visionnage = datetime('now')",
                ['p' => $profil['id'], 'c' => $idFilm, 'pos' => $position, 'd' => $duree]);
    } else {
        requete("INSERT INTO visionnage (id_profil, id_episode, position_secondes, duree_secondes)
                 VALUES (:p, :c, :pos, :d)
                 ON CONFLICT (id_profil, id_episode) WHERE id_episode IS NOT NULL
                 DO UPDATE SET position_secondes = :pos, duree_secondes = MAX(duree_secondes, :d),
                               date_visionnage = datetime('now')",
                ['p' => $profil['id'], 'c' => $idEpisode, 'pos' => $position, 'd' => $duree]);
    }
} catch (Throwable $e) {
    json(['error' => message_erreur($e)], 422);
}

json(['saved' => true, 'position' => $position]);
