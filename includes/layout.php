<?php
/* Début et fin communs à toutes les pages */

function entete(string $titre, array $css = [], array $options = []): void
{
    $plyr = $options['plyr'] ?? false;
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(jeton_csrf()) ?>">
    <title><?= e($titre) ?> - BING</title>
    <link rel="icon" href="<?= url('images/logo.png') ?>">
    <link rel="stylesheet" href="<?= url('vendor/poppins/poppins.css') ?>">
    <link rel="stylesheet" href="<?= url('vendor/fontawesome/css/all.min.css') ?>">
    <?php if ($plyr): ?>
    <link rel="stylesheet" href="<?= url('vendor/plyr/plyr.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= url('css/common.css') ?>">
    <?php foreach ($css as $fichier): ?>
    <link rel="stylesheet" href="<?= url('css/' . $fichier) ?>">
    <?php endforeach; ?>
</head>
<body>
    <?php
}

function pied(array $js = []): void
{
    foreach (messages_flash() as $flash): ?>
    <div class="toast toast-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
    <script>window.BING = { base: <?= json_encode(base()) ?> };</script>
    <script src="<?= url('js/common.js') ?>"></script>
    <?php foreach ($js as $fichier): ?>
    <script src="<?= url(str_starts_with($fichier, 'vendor/') ? $fichier : 'js/' . $fichier) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
    <?php
}

/* Carte d'un film ou d'une série, utilisée dans les rangées de l'accueil */
function carte_video(array $video, array $favoris): string
{
    $lien = $video['type'] === 'film' ? 'watch.php?movie=' . $video['id'] : 'serie.php?serie=' . $video['id'];
    $favori = in_array((int) $video['id'], $favoris, true);
    $meta = $video['type'] === 'film'
        ? duree((int) $video['duree_minutes'])
        : $video['nb_saisons'] . ' season' . ($video['nb_saisons'] > 1 ? 's' : '');
    ob_start(); ?>
    <div class="movie-card" data-id="<?= (int) $video['id'] ?>">
        <a href="<?= url($lien) ?>" class="movie-poster" style="background-image: url('<?= e(url($video['image'])) ?>')" aria-label="<?= e($video['titre']) ?>">
            <?php if (isset($video['progression'])): ?>
            <span class="progress-bar"><span style="width: <?= (int) $video['progression'] ?>%"></span></span>
            <?php endif; ?>
        </a>
        <div class="movie-info">
            <h3 class="movie-title"><?= e($video['titre']) ?></h3>
            <div class="movie-meta"><?= e($video['sous_titre'] ?? $video['categories']) ?></div>
        </div>
        <div class="expanded-info">
            <h3 class="expanded-title"><?= e($video['titre']) ?></h3>
            <div class="expanded-meta">
                <span class="age-badge"><?= age_libelle((int) $video['age_minimum']) ?></span>
                <?= annee($video['date_publication']) ?> • <?= e($meta) ?>
            </div>
            <div class="expanded-meta"><?= e($video['categories']) ?></div>
            <div class="expanded-description"><?= e($video['description']) ?></div>
            <div class="expanded-actions">
                <a class="expanded-watch-button" href="<?= url($video['lien_reprise'] ?? $lien) ?>">
                    <i class="fas fa-play"></i> <?= isset($video['lien_reprise']) ? 'Resume' : ($video['type'] === 'film' ? 'Watch' : 'Episodes') ?>
                </a>
                <?= bouton_favori((int) $video['id'], $favori, 'expanded-add-button') ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/* Bouton « Ma liste » : fonctionne en JavaScript (fetch) et sans (formulaire) */
function bouton_favori(int $idVideo, bool $favori, string $classe): string
{
    $libelle = $favori ? 'Remove from My List' : 'Add to My List';
    return '<form method="post" action="' . url('api/favorite.php') . '" class="favorite-form">'
        . champ_csrf()
        . '<input type="hidden" name="video_id" value="' . $idVideo . '">'
        . '<input type="hidden" name="action" value="toggle">'
        . '<button type="submit" class="' . e($classe) . ($favori ? ' added' : '') . '" title="' . $libelle . '" aria-label="' . $libelle . '">'
        . '<i class="fas ' . ($favori ? 'fa-check' : 'fa-plus') . '"></i></button></form>';
}
