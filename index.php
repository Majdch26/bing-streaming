<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/catalogue.php';

$utilisateur = utilisateur_courant();
if ($utilisateur === null) {
    rediriger('login.php');
}
$profil = exiger_profil();
$favoris = ids_favoris((int) $profil['id']);

$aLaUne = videos($profil, '1 = 1', [], 'v.image_fond IS NULL, v.date_publication DESC', 4);
$rangees = [
    ['Continue Watching', 'continue', continuer_regarder($profil)],
    ['Popular on BING', 'popular', videos($profil, '1 = 1', [], 'v.nb_spectateurs DESC, v.titre', 10)],
    ['My List', 'my-list', videos($profil, 'v.id IN (SELECT id_video FROM favoris WHERE id_profil = :p)',
                                  ['p' => $profil['id']],
                                  '(SELECT date_ajout FROM favoris WHERE id_profil = :p AND id_video = v.id) DESC')],
    ['New Releases', 'new', videos($profil, '1 = 1', [], 'v.date_publication DESC', 10)],
    ['Movies', 'movies', videos($profil, "v.type = 'film'", [], 'v.titre')],
    ['TV Series', 'series', videos($profil, "v.type = 'serie'", [], 'v.titre')],
];
$maListe = $rangees[2][2];

$page = 'home';
entete('Home', ['navbar.css', 'index.css']);
include __DIR__ . '/includes/navbar.php';
?>
    <!-- Carrousel des nouveautés -->
    <section class="hero-carousel" aria-label="Featured">
        <?php foreach ($aLaUne as $i => $video):
            $lien = $video['type'] === 'film' ? 'watch.php?movie=' . $video['id'] : 'serie.php?serie=' . $video['id']; ?>
            <div class="hero-slide<?= $i === 0 ? ' active' : '' ?>">
                <?php if ($video['image_fond']): ?>
                    <img src="<?= e(url($video['image_fond'])) ?>" class="hero-image" alt="">
                <?php else: ?>
                    <div class="hero-image hero-image-blur" style="background-image: url('<?= e(url($video['image'])) ?>')"></div>
                    <img src="<?= e(url($video['image'])) ?>" class="hero-poster" alt="">
                <?php endif; ?>
                <div class="gradient-overlay"></div>
                <div class="hero-content">
                    <div class="content-wrapper">
                        <p class="hero-kicker"><?= $video['type'] === 'film' ? 'Movie' : 'Series' ?> · <?= annee($video['date_publication']) ?> · <?= e($video['categories']) ?></p>
                        <h1 class="hero-title"><?= e($video['titre']) ?></h1>
                        <p class="hero-description"><?= e($video['description']) ?></p>
                        <div class="hero-buttons">
                            <a class="watch-button" href="<?= url($lien) ?>"><?= $video['type'] === 'film' ? 'Watch Now' : 'Episodes' ?></a>
                            <?= bouton_favori((int) $video['id'], in_array((int) $video['id'], $favoris, true), 'expanded-add-button hero-add') ?>
                        </div>
                        <div class="age-rating"><?= age_libelle((int) $video['age_minimum']) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (count($aLaUne) > 1): ?>
        <button class="carousel-arrow arrow-left" aria-label="Previous">‹</button>
        <button class="carousel-arrow arrow-right" aria-label="Next">›</button>
        <div class="carousel-dots">
            <?php foreach ($aLaUne as $i => $video): ?>
                <button class="dot<?= $i === 0 ? ' active' : '' ?>" data-slide="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <div class="content-section">
        <?php foreach ($rangees as [$titre, $ancre, $liste]):
            if (!$liste && $ancre !== 'my-list') {
                continue;
            } ?>
        <section class="movie-section" id="<?= $ancre ?>">
            <h2 class="section-title"><?= e($titre) ?></h2>
            <?php if (!$liste): ?>
                <p class="row-empty">Use the <i class="fas fa-plus"></i> button on any title to add it to your list.</p>
            <?php else: ?>
            <button class="section-arrow left" aria-label="Scroll left">‹</button>
            <div class="movies-container">
                <?php foreach ($liste as $video): ?>
                    <?= carte_video($video, $favoris) ?>
                <?php endforeach; ?>
            </div>
            <button class="section-arrow right" aria-label="Scroll right">›</button>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    </div>

    <!-- Fenêtre « Ma liste » -->
    <div class="watchlist-popup" id="watchlistPopup">
        <div class="watchlist-header">
            My List
            <button class="watchlist-close" aria-label="Close">×</button>
        </div>
        <div class="watchlist-content">
            <?php if (!$maListe): ?>
                <div class="watchlist-empty">
                    <div class="watchlist-empty-icon"><i class="fas fa-film"></i></div>
                    <p>Your list is empty</p>
                </div>
            <?php else: ?>
                <?php foreach ($maListe as $video): ?>
                    <div class="watchlist-item">
                        <a class="watchlist-item-poster" href="<?= url($video['type'] === 'film' ? 'watch.php?movie=' . $video['id'] : 'serie.php?serie=' . $video['id']) ?>"
                           style="background-image: url('<?= e(url($video['image'])) ?>')"></a>
                        <div class="watchlist-item-details">
                            <div class="watchlist-item-title"><?= e($video['titre']) ?></div>
                            <form method="post" action="<?= url('api/favorite.php') ?>">
                                <?= champ_csrf() ?>
                                <input type="hidden" name="video_id" value="<?= (int) $video['id'] ?>">
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="watchlist-item-remove" aria-label="Remove">×</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($maListe): ?>
        <div class="watchlist-footer">
            <form method="post" action="<?= url('api/favorite.php') ?>">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="watchlist-clear">Clear All</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <button class="watchlist-button" aria-label="My List">
        <span class="watchlist-count"><?= count($maListe) ?></span>
        <i class="fas fa-bookmark"></i>
    </button>
<?php pied(['index.js']); ?>
