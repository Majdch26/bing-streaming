<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/catalogue.php';

$profil = exiger_profil();
$idSerie = (int) ($_GET['serie'] ?? 0);

$serie = videos($profil, "v.id = :id AND v.type = 'serie'", ['id' => $idSerie])[0] ?? null;
if (!$serie) {
    flash('error', 'This series is not available for this profile.');
    rediriger('series.php');
}

$episodes = requete(
    'SELECT e.*, vi.position_secondes, vi.duree_secondes
     FROM episode e
     LEFT JOIN visionnage vi ON vi.id_episode = e.id AND vi.id_profil = ?
     WHERE e.id_serie = ?
     ORDER BY e.numero_saison, e.numero_episode',
    [$profil['id'], $idSerie]
)->fetchAll();

$saisons = [];
foreach ($episodes as $episode) {
    $saisons[(int) $episode['numero_saison']][] = $episode;
}

/* Bouton principal : reprendre l'épisode en cours, passer au suivant, ou commencer au début */
$reprise = null;
$dernier = requete('SELECT vi.*, e.numero_saison, e.numero_episode FROM visionnage vi
                    JOIN episode e ON e.id = vi.id_episode
                    WHERE vi.id_profil = ? AND e.id_serie = ?
                    ORDER BY vi.date_visionnage DESC LIMIT 1', [$profil['id'], $idSerie])->fetch();
if ($dernier) {
    $fini = $dernier['duree_secondes'] > 0 && $dernier['position_secondes'] >= 0.95 * $dernier['duree_secondes'];
    if (!$fini) {
        $reprise = ['id' => $dernier['id_episode'], 'libelle' => 'Resume S' . $dernier['numero_saison'] . ' E' . $dernier['numero_episode']];
    } else {
        foreach ($episodes as $episode) {
            if ([$episode['numero_saison'], $episode['numero_episode']] > [$dernier['numero_saison'], $dernier['numero_episode']]) {
                $reprise = ['id' => $episode['id'], 'libelle' => 'Play S' . $episode['numero_saison'] . ' E' . $episode['numero_episode']];
                break;
            }
        }
    }
}
if ($reprise === null && $episodes) {
    $reprise = ['id' => $episodes[0]['id'], 'libelle' => 'Play S' . $episodes[0]['numero_saison'] . ' E' . $episodes[0]['numero_episode']];
}
$saisonActive = $dernier ? (int) $dernier['numero_saison'] : (int) array_key_first($saisons ?: [1 => []]);

$similaires = videos_similaires($profil, $idSerie, 6);
$favori = in_array($idSerie, ids_favoris((int) $profil['id']), true);

$page = 'series';
entete($serie['titre'], ['navbar.css', 'serie.css']);
include __DIR__ . '/includes/navbar.php';
?>
    <section class="hero-section">
        <div class="hero-backdrop">
            <img src="<?= e(url($serie['image_fond'] ?: $serie['image'])) ?>" class="hero-bg-image<?= $serie['image_fond'] ? '' : ' is-poster' ?>" alt="">
            <div class="hero-gradient"></div>
        </div>
        <div class="hero-content">
            <h1 class="hero-title"><?= e($serie['titre']) ?></h1>
            <div class="hero-meta">
                <span class="age-badge"><?= age_libelle((int) $serie['age_minimum']) ?></span>
                <span class="year"><?= annee($serie['date_publication']) ?></span>
                <span><?= (int) $serie['nb_saisons'] ?> Season<?= $serie['nb_saisons'] > 1 ? 's' : '' ?></span>
                <span><?= (int) $serie['nb_episodes'] ?> Episodes</span>
                <span class="quality"><?= e($serie['langue_originale']) ?></span>
            </div>
            <p class="hero-genres"><?= e($serie['categories']) ?></p>
            <p class="hero-description"><?= e($serie['description']) ?></p>
            <div class="hero-actions">
                <?php if ($reprise): ?>
                    <a href="<?= url('watch.php?episode=' . (int) $reprise['id']) ?>" class="action-button play-button">
                        <i class="fas fa-play"></i> <?= e($reprise['libelle']) ?>
                    </a>
                <?php endif; ?>
                <form method="post" action="<?= url('api/favorite.php') ?>" class="favorite-form">
                    <?= champ_csrf() ?>
                    <input type="hidden" name="video_id" value="<?= $idSerie ?>">
                    <input type="hidden" name="action" value="toggle">
                    <button type="submit" class="action-button secondary-button<?= $favori ? ' added' : '' ?>">
                        <i class="fas <?= $favori ? 'fa-check' : 'fa-plus' ?>"></i> <span class="label"><?= $favori ? 'In My List' : 'My List' ?></span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <div class="series-container">
        <div class="seasons-container">
            <h2 class="section-title">Episodes</h2>

            <?php if (!$saisons): ?>
                <div class="empty-state"><i class="fas fa-clock"></i>Episodes are coming soon.</div>
            <?php endif; ?>

            <div class="season-selector" role="tablist">
                <?php foreach (array_keys($saisons) as $numero): ?>
                    <button class="season-tab<?= $numero === $saisonActive ? ' active' : '' ?>" role="tab" data-season="<?= $numero ?>">
                        Season <?= $numero ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($saisons as $numero => $liste): ?>
                <div class="season-content" id="season-<?= $numero ?>"<?= $numero !== $saisonActive ? ' hidden' : '' ?>>
                    <div class="episodes-grid">
                        <?php foreach ($liste as $episode):
                            $progression = $episode['duree_secondes'] > 0
                                ? min(100, 100 * $episode['position_secondes'] / $episode['duree_secondes']) : 0; ?>
                            <a href="<?= url('watch.php?episode=' . (int) $episode['id']) ?>" class="episode-card">
                                <div class="episode-thumb">
                                    <img src="<?= e(url($episode['image_episode'] ?: ($serie['image_fond'] ?: $serie['image']))) ?>" alt="" class="episode-image<?= $episode['image_episode'] ? '' : ' is-fallback' ?>" loading="lazy">
                                    <span class="episode-play"><i class="fas fa-play"></i></span>
                                    <?php if ($progression > 0): ?>
                                        <span class="progress-bar"><span style="width: <?= (int) $progression ?>%"></span></span>
                                    <?php endif; ?>
                                </div>
                                <div class="episode-info">
                                    <div class="episode-number">S<?= (int) $episode['numero_saison'] ?> E<?= (int) $episode['numero_episode'] ?></div>
                                    <h3 class="episode-title"><?= e($episode['titre']) ?></h3>
                                    <p class="episode-description"><?= e($episode['description']) ?></p>
                                    <div class="episode-meta">
                                        <span><i class="far fa-clock"></i> <?= duree((int) $episode['duree']) ?></span>
                                        <?php if ($progression >= 95): ?><span class="watched"><i class="fas fa-check"></i> Watched</span><?php endif; ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($similaires): ?>
            <div class="similar-section">
                <h2 class="section-title">More Like This</h2>
                <div class="similar-grid">
                    <?php foreach ($similaires as $video): ?>
                        <a href="<?= url($video['type'] === 'film' ? 'watch.php?movie=' . $video['id'] : 'serie.php?serie=' . $video['id']) ?>" class="similar-card">
                            <img src="<?= e(url($video['image'])) ?>" alt="" class="similar-image" loading="lazy">
                            <div class="similar-title"><?= e($video['titre']) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php pied(['serie.js']); ?>
