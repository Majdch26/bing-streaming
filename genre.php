<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/catalogue.php';

$profil = exiger_profil();
$favoris = ids_favoris((int) $profil['id']);
$slug = (string) ($_GET['g'] ?? '');

if ($slug === 'new') {
    $titrePage = 'New Releases';
    $videos = videos($profil, "v.date_publication >= date('now', '-4 years')", [], 'v.date_publication DESC');
} else {
    $categorie = requete('SELECT * FROM categorie WHERE slug = ?', [$slug])->fetch();
    if (!$categorie) {
        flash('error', 'This category does not exist.');
        rediriger('index.php');
    }
    $titrePage = $categorie['nom'];
    $videos = videos($profil, 'v.id IN (SELECT id_video FROM video_categorie WHERE id_categorie = :c)',
                     ['c' => $categorie['id']], 'v.nb_spectateurs DESC, v.date_publication DESC');
}
$filtre = false;
$classe = 'movie';

$page = 'genre';
entete($titrePage, ['navbar.css', 'movies.css', 'grid.css']);
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/grille.php';
pied(['grid.js']);
