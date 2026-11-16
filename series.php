<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/catalogue.php';

$profil = exiger_profil();
$favoris = ids_favoris((int) $profil['id']);
$videos = videos($profil, "v.type = 'serie'", [], 'v.date_publication DESC');
$titrePage = 'TV Series';
$filtre = true;
$classe = 'series';

$page = 'series';
entete('TV Series', ['navbar.css', 'series.css', 'grid.css']);
include __DIR__ . '/includes/navbar.php';
include __DIR__ . '/includes/grille.php';
pied(['grid.js']);
