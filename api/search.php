<?php
/* Recherche instantanée de la barre de navigation (JSON) */
require __DIR__ . '/../includes/bootstrap.php';

$utilisateur = utilisateur_courant();
if ($utilisateur === null) {
    json(['error' => 'Not signed in'], 401);
}
$profil = isset($_SESSION['profile_id'])
    ? requete('SELECT * FROM profils WHERE id = ? AND id_utilisateur = ?', [$_SESSION['profile_id'], $utilisateur['id']])->fetch()
    : null;

$texte = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($texte) < 2) {
    json([]);
}

// Les caractères spéciaux de LIKE sont échappés : « 100% » cherche bien « 100% »
$motif = '%' . strtr($texte, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']) . '%';
$resultats = requete(
    "SELECT v.id, v.titre, v.type, v.date_publication, v.image
     FROM video v
     WHERE (v.titre LIKE :motif ESCAPE '\\' OR v.id IN (
                SELECT vc.id_video FROM video_categorie vc JOIN categorie c ON c.id = vc.id_categorie
                WHERE c.nom LIKE :motif ESCAPE '\\'))
       AND " . ($profil ? filtre_age($profil) : '1 = 1') . "
     ORDER BY (v.titre LIKE :debut ESCAPE '\\') DESC, v.date_publication DESC
     LIMIT 8",
    ['motif' => $motif, 'debut' => substr($motif, 1)]
)->fetchAll();

json($resultats);
