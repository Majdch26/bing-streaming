<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/catalogue.php';

$profil = exiger_profil();
$idFilm = isset($_GET['movie']) ? (int) $_GET['movie'] : null;
$idEpisode = isset($_GET['episode']) ? (int) $_GET['episode'] : null;

if ($idFilm !== null) {
    $video = videos($profil, "v.id = :id AND v.type = 'film'", ['id' => $idFilm])[0] ?? null;
    if ($video) {
        $video += requete('SELECT url_video, duree FROM film WHERE id_video = ?', [$idFilm])->fetch();
        $idVideo = $idFilm;
    }
} elseif ($idEpisode !== null) {
    $episode = requete('SELECT * FROM episode WHERE id = ?', [$idEpisode])->fetch();
    $video = $episode ? (videos($profil, 'v.id = :id', ['id' => $episode['id_serie']])[0] ?? null) : null;
    if ($video) {
        $idVideo = (int) $video['id'];
        $suivant = requete('SELECT id, numero_saison, numero_episode, titre FROM episode
                            WHERE id_serie = :s AND (numero_saison > :n OR (numero_saison = :n AND numero_episode > :e))
                            ORDER BY numero_saison, numero_episode LIMIT 1',
                           ['s' => $idVideo, 'n' => $episode['numero_saison'], 'e' => $episode['numero_episode']])->fetch();
    }
}

if (empty($video)) {
    flash('error', 'This title is not available for this profile.');
    rediriger('index.php');
}

$source = $idFilm !== null ? $video['url_video'] : $episode['url_video'];
$titre = $idFilm !== null ? $video['titre'] : $episode['titre'];
$description = $idFilm !== null ? $video['description'] : $episode['description'];
$dureeMinutes = (int) ($idFilm !== null ? $video['duree'] : $episode['duree']);
$affiche = $idFilm !== null ? ($video['image_fond'] ?: $video['image'])
                            : ($episode['image_episode'] ?: ($video['image_fond'] ?: $video['image']));

$soustitres = requete('SELECT langue, libelle, url_fichier FROM soustitre WHERE id_film IS ? AND id_episode IS ? ORDER BY libelle',
                      [$idFilm, $idEpisode])->fetchAll();
$position = position_lecture((int) $profil['id'], $idFilm, $idEpisode);
$reprendreA = ($position['duree_secondes'] > 0 && $position['position_secondes'] < 0.95 * $position['duree_secondes'])
    ? (int) $position['position_secondes'] : 0;

$similaires = videos_similaires($profil, $idVideo, 8);
$favori = in_array($idVideo, ids_favoris((int) $profil['id']), true);

$page = $idFilm !== null ? 'movies' : 'series';
entete($idFilm !== null ? $titre : $titre . ' | ' . $video['titre'], ['navbar.css', 'watch.css'], ['plyr' => true]);
include __DIR__ . '/includes/navbar.php';
?>
    <div class="video-container">
        <video id="player" playsinline controls data-poster="<?= e(url($affiche)) ?>"
               data-movie="<?= $idFilm ?? '' ?>" data-episode="<?= $idEpisode ?? '' ?>"
               data-resume="<?= $reprendreA ?>"
               data-next="<?= !empty($suivant) ? e(url('watch.php?episode=' . $suivant['id'])) : '' ?>">
            <source src="<?= e($source) ?>" type="video/mp4">
            <?php foreach ($soustitres as $i => $piste): ?>
                <track kind="captions" label="<?= e($piste['libelle']) ?>" srclang="<?= e($piste['langue']) ?>" src="<?= e(url($piste['url_fichier'])) ?>"<?= $i === 0 ? ' default' : '' ?>>
            <?php endforeach; ?>
        </video>
        <div class="next-overlay" id="nextOverlay" hidden>
            <?php if (!empty($suivant)): ?>
                <p>Next episode in <strong id="nextCountdown">8</strong> s</p>
                <p class="next-title">S<?= (int) $suivant['numero_saison'] ?> E<?= (int) $suivant['numero_episode'] ?> · <?= e($suivant['titre']) ?></p>
                <div class="next-buttons">
                    <a class="action-button play-button" href="<?= url('watch.php?episode=' . (int) $suivant['id']) ?>">Play now</a>
                    <button type="button" class="action-button" id="nextCancel">Cancel</button>
                </div>
            <?php endif; ?>
        </div>
        <p class="video-error" id="videoError" hidden>The video could not be loaded. Check your connection and try again.</p>
    </div>

    <div class="video-info">
        <h1 class="video-title"><?= e($titre) ?></h1>
        <?php if ($idEpisode !== null): ?>
            <div class="series-title">
                <i class="fas fa-tv"></i>
                <a href="<?= url('serie.php?serie=' . $idVideo) ?>"><?= e($video['titre']) ?></a> • S<?= (int) $episode['numero_saison'] ?> E<?= (int) $episode['numero_episode'] ?>
            </div>
        <?php endif; ?>

        <div class="video-meta">
            <span class="meta-item"><span class="age-badge"><?= age_libelle((int) $video['age_minimum']) ?></span></span>
            <span class="meta-item"><i class="far fa-calendar"></i> <?= annee($video['date_publication']) ?></span>
            <span class="meta-item"><i class="far fa-clock"></i> <?= duree($dureeMinutes) ?></span>
            <span class="meta-item"><i class="fas fa-language"></i> <?= e($video['langue_originale']) ?></span>
            <?php if ($soustitres): ?>
                <span class="meta-item"><i class="fas fa-closed-captioning"></i> <?= e(implode(', ', array_column($soustitres, 'libelle'))) ?></span>
            <?php endif; ?>
            <?php if ($reprendreA > 0): ?>
                <span class="meta-item resume-info"><i class="fas fa-history"></i> Resuming at <?= gmdate($reprendreA >= 3600 ? 'G:i:s' : 'i:s', $reprendreA) ?></span>
            <?php endif; ?>
        </div>

        <p class="video-description"><?= e($description) ?></p>
        <p class="video-genres"><?= e($video['categories']) ?></p>

        <div class="action-buttons">
            <form method="post" action="<?= url('api/favorite.php') ?>" class="favorite-form">
                <?= champ_csrf() ?>
                <input type="hidden" name="video_id" value="<?= $idVideo ?>">
                <input type="hidden" name="action" value="toggle">
                <button type="submit" class="action-button<?= $favori ? ' added' : '' ?>">
                    <i class="fas <?= $favori ? 'fa-check' : 'fa-plus' ?>"></i> <span class="label"><?= $favori ? 'In My List' : 'My List' ?></span>
                </button>
            </form>
            <?php if ($idEpisode !== null): ?>
                <a href="<?= url('serie.php?serie=' . $idVideo) ?>" class="action-button">
                    <i class="fas fa-list"></i> <span>All Episodes</span>
                </a>
                <?php if (!empty($suivant)): ?>
                    <a href="<?= url('watch.php?episode=' . (int) $suivant['id']) ?>" class="action-button play-button">
                        <i class="fas fa-forward"></i> <span>Next Episode</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($similaires): ?>
        <div class="similar-section">
            <h2 class="section-title">More Like This</h2>
            <div class="similar-row">
                <?php foreach ($similaires as $autre): ?>
                    <a class="similar-card" href="<?= url($autre['type'] === 'film' ? 'watch.php?movie=' . $autre['id'] : 'serie.php?serie=' . $autre['id']) ?>">
                        <img src="<?= e(url($autre['image'])) ?>" alt="" class="similar-thumbnail" loading="lazy">
                        <div class="similar-info">
                            <div class="similar-title"><?= e($autre['titre']) ?></div>
                            <div class="similar-meta">
                                <span><?= annee($autre['date_publication']) ?></span>
                                <span class="quality-badge"><?= $autre['type'] === 'film' ? 'Movie' : 'Series' ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php pied(['vendor/plyr/plyr.min.js', 'watch.js']); ?>
