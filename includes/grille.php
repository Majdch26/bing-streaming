<?php
/* Grille de cartes utilisée par les pages Films, Séries et Genres.
   Variables attendues : $videos, $favoris, $titrePage, $filtre (bool), $classe ('movie' ou 'series') */

$genresPresents = [];
foreach ($videos as $video) {
    foreach (array_filter(explode(', ', (string) $video['categories'])) as $nom) {
        $genresPresents[$nom] = true;
    }
}
ksort($genresPresents);
?>
    <div class="filter-section">
        <h1 class="page-heading"><?= e($titrePage) ?></h1>
        <?php if ($filtre && $genresPresents): ?>
        <label for="genreFilter" class="visually-hidden">Filter by genre</label>
        <select class="genre-filter" id="genreFilter">
            <option value="all">All Genres</option>
            <?php foreach (array_keys($genresPresents) as $nom): ?>
                <option value="<?= e($nom) ?>"><?= e($nom) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>

    <div class="content-section">
        <?php if (!$videos): ?>
            <div class="empty-state"><i class="fas fa-film"></i>Nothing to show here yet.</div>
        <?php endif; ?>
        <div class="<?= $classe === 'series' ? 'series-grid' : 'movies-grid' ?>">
            <?php foreach ($videos as $video):
                $film = $video['type'] === 'film';
                $lien = $film ? 'watch.php?movie=' . $video['id'] : 'serie.php?serie=' . $video['id']; ?>
            <div class="<?= $classe ?>-card" data-genres="<?= e($video['categories']) ?>">
                <a class="<?= $classe ?>-poster-container" href="<?= url($lien) ?>">
                    <img src="<?= e(url($video['image'])) ?>" class="<?= $classe ?>-poster" alt="<?= e($video['titre']) ?>" loading="lazy">
                    <div class="<?= $classe ?>-badge"><?= age_libelle((int) $video['age_minimum']) ?></div>
                </a>
                <div class="<?= $classe ?>-info">
                    <h3 class="<?= $classe ?>-title"><?= e($video['titre']) ?></h3>
                    <div class="<?= $classe ?>-meta movie-meta">
                        <span><?= annee($video['date_publication']) ?></span>
                        <?php if ($film): ?>
                            <span class="movie-duration"><?= duree((int) $video['duree_minutes']) ?></span>
                        <?php else: ?>
                            <span class="movie-duration"><?= (int) $video['nb_saisons'] ?> Season<?= $video['nb_saisons'] > 1 ? 's' : '' ?> · <?= (int) $video['nb_episodes'] ?> Episodes</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-genres"><?= e($video['categories']) ?></div>
                    <p class="<?= $classe ?>-description"><?= e($video['description']) ?></p>
                    <div class="card-actions">
                        <a href="<?= url($lien) ?>" class="watch-button"><?= $film ? 'Watch Now' : 'View Series' ?></a>
                        <?= bouton_favori((int) $video['id'], in_array((int) $video['id'], $favoris, true), 'card-add-button') ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
