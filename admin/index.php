<?php
/*
 * Administration : tableau de bord (indicateurs) et gestion du catalogue,
 * des offres et des utilisateurs. Réservé aux comptes administrateurs.
 */
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/layout.php';

$admin = exiger_admin();

/* ---------- Lecture des champs envoyés ---------- */

function texte(string $cle, int $max = 500, bool $obligatoire = true): ?string
{
    $valeur = trim((string) ($_POST[$cle] ?? ''));
    if ($valeur === '') {
        if ($obligatoire) {
            throw new InvalidArgumentException("The field \"$cle\" is required.");
        }
        return null;
    }
    if (mb_strlen($valeur) > $max) {
        throw new InvalidArgumentException("The field \"$cle\" is too long ($max characters max).");
    }
    return $valeur;
}

function entier(string $cle, int $min, int $max): int
{
    $valeur = filter_var($_POST[$cle] ?? null, FILTER_VALIDATE_INT);
    if ($valeur === false || $valeur < $min || $valeur > $max) {
        throw new InvalidArgumentException("The field \"$cle\" must be a number between $min and $max.");
    }
    return $valeur;
}

function date_valide(string $cle): string
{
    $valeur = (string) ($_POST[$cle] ?? '');
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);
    if (!$date || $date->format('Y-m-d') !== $valeur) {
        throw new InvalidArgumentException("The field \"$cle\" must be a valid date.");
    }
    return $valeur;
}

/* Chemin local (images/..., subtitles/...) ou adresse http(s) */
function lien(string $cle, bool $obligatoire = true): ?string
{
    $valeur = texte($cle, 500, $obligatoire);
    if ($valeur !== null && !preg_match('#^(https?://|images/|subtitles/)#', $valeur)) {
        throw new InvalidArgumentException("The field \"$cle\" must be an http(s) address or a path starting with images/ or subtitles/.");
    }
    return $valeur;
}

function enregistrer_categories(int $idVideo): void
{
    requete('DELETE FROM video_categorie WHERE id_video = ?', [$idVideo]);
    foreach ((array) ($_POST['categories'] ?? []) as $idCategorie) {
        requete('INSERT OR IGNORE INTO video_categorie (id_video, id_categorie) SELECT ?, id FROM categorie WHERE id = ?',
                [$idVideo, (int) $idCategorie]);
    }
}

/* ---------- Actions ---------- */

if (est_post()) {
    verifier_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    $onglet = 'dashboard';

    try {
        db()->beginTransaction();
        switch ($action) {
            case 'save_video':
                $onglet = 'videos';
                $champs = [texte('titre', 200), texte('description', 1000), date_valide('date_publication'),
                           texte('langue_originale', 50), lien('image'), lien('image_fond', false),
                           entier('age_minimum', 0, 18)];
                if ($id === 0) {
                    $type = ($_POST['type'] ?? '') === 'serie' ? 'serie' : 'film';
                    requete('INSERT INTO video (titre, description, date_publication, langue_originale, image, image_fond, age_minimum, type)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)', [...$champs, $type]);
                    $id = (int) db()->lastInsertId();
                    if ($type === 'film') {
                        requete('INSERT INTO film (id_video, duree, url_video) VALUES (?, ?, ?)',
                                [$id, entier('duree', 1, 600), lien('url_video')]);
                    } else {
                        requete('INSERT INTO serie (id_video, terminee) VALUES (?, ?)', [$id, isset($_POST['terminee']) ? 1 : 0]);
                    }
                } else {
                    requete('UPDATE video SET titre = ?, description = ?, date_publication = ?, langue_originale = ?,
                             image = ?, image_fond = ?, age_minimum = ? WHERE id = ?', [...$champs, $id]);
                    if (requete("SELECT type FROM video WHERE id = ?", [$id])->fetchColumn() === 'film') {
                        requete('UPDATE film SET duree = ?, url_video = ? WHERE id_video = ?',
                                [entier('duree', 1, 600), lien('url_video'), $id]);
                    } else {
                        requete('UPDATE serie SET terminee = ? WHERE id_video = ?', [isset($_POST['terminee']) ? 1 : 0, $id]);
                    }
                }
                enregistrer_categories($id);
                flash('success', 'Title saved.');
                break;

            case 'delete_video':
                $onglet = 'videos';
                requete('DELETE FROM video WHERE id = ?', [$id]);
                flash('success', 'Title deleted with its episodes, subtitles and viewing history.');
                break;

            case 'save_episode':
                $onglet = 'episodes';
                $champs = [entier('id_serie', 1, PHP_INT_MAX), entier('numero_saison', 1, 99), entier('numero_episode', 1, 999),
                           texte('titre', 200), texte('description', 1000), entier('duree', 1, 600),
                           lien('url_video'), lien('image_episode', false)];
                if ($id === 0) {
                    requete('INSERT INTO episode (id_serie, numero_saison, numero_episode, titre, description, duree, url_video, image_episode)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)', $champs);
                } else {
                    requete('UPDATE episode SET id_serie = ?, numero_saison = ?, numero_episode = ?, titre = ?, description = ?,
                             duree = ?, url_video = ?, image_episode = ? WHERE id = ?', [...$champs, $id]);
                }
                flash('success', 'Episode saved.');
                break;

            case 'delete_episode':
                $onglet = 'episodes';
                requete('DELETE FROM episode WHERE id = ?', [$id]);
                flash('success', 'Episode deleted.');
                break;

            case 'save_subtitle':
                $onglet = 'subtitles';
                [$genre, $cible] = array_pad(explode(':', (string) ($_POST['cible'] ?? '')), 2, '0');
                requete('INSERT INTO soustitre (id_film, id_episode, langue, libelle, url_fichier) VALUES (?, ?, ?, ?, ?)', [
                    $genre === 'film' ? (int) $cible : null,
                    $genre === 'episode' ? (int) $cible : null,
                    mb_strtolower(texte('langue', 5)), texte('libelle', 40), lien('url_fichier'),
                ]);
                flash('success', 'Subtitle track added.');
                break;

            case 'delete_subtitle':
                $onglet = 'subtitles';
                requete('DELETE FROM soustitre WHERE id = ?', [$id]);
                flash('success', 'Subtitle track deleted.');
                break;

            case 'save_plan':
                $onglet = 'plans';
                $prix = filter_var($_POST['prix_mensuel'] ?? null, FILTER_VALIDATE_FLOAT);
                if ($prix === false || $prix < 0 || $prix > 999) {
                    throw new InvalidArgumentException('The price must be between 0 and 999.');
                }
                $qualite = in_array($_POST['qualite_max'] ?? '', ['SD', 'HD', '4K'], true) ? $_POST['qualite_max'] : 'HD';
                $champs = [texte('nom', 40), round($prix, 2), entier('nb_profils_max', 1, 10), $qualite,
                           (string) texte('avantages', 1000, false), isset($_POST['mise_en_avant']) ? 1 : 0,
                           isset($_POST['disponible']) ? 1 : 0];
                if ($id === 0) {
                    requete('INSERT INTO offre (nom, prix_mensuel, nb_profils_max, qualite_max, avantages, mise_en_avant, disponible)
                             VALUES (?, ?, ?, ?, ?, ?, ?)', $champs);
                } else {
                    // Les abonnements en cours gardent leur prix : seul le prix des prochaines souscriptions change
                    requete('UPDATE offre SET nom = ?, prix_mensuel = ?, nb_profils_max = ?, qualite_max = ?, avantages = ?,
                             mise_en_avant = ?, disponible = ? WHERE id = ?', [...$champs, $id]);
                }
                flash('success', 'Plan saved.');
                break;

            case 'delete_plan':
                $onglet = 'plans';
                if (requete('SELECT COUNT(*) FROM abonnement WHERE id_offre = ?', [$id])->fetchColumn() > 0) {
                    throw new RuntimeException('This plan has subscriptions: make it unavailable instead of deleting it.');
                }
                requete('DELETE FROM offre WHERE id = ?', [$id]);
                flash('success', 'Plan deleted.');
                break;

            case 'save_user':
                $onglet = 'users';
                $email = mb_strtolower(texte('email', 200));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException('Please enter a valid email address.');
                }
                $estAdmin = isset($_POST['est_admin']) ? 1 : 0;
                if ($id === (int) $admin['id'] && !$estAdmin) {
                    throw new RuntimeException('You cannot remove your own administrator rights.');
                }
                requete('UPDATE utilisateur SET nom = ?, email = ?, est_admin = ? WHERE id = ?', [texte('nom', 60), $email, $estAdmin, $id]);
                flash('success', 'User saved.');
                break;

            case 'delete_user':
                $onglet = 'users';
                if ($id === (int) $admin['id']) {
                    throw new RuntimeException('You cannot delete your own account.');
                }
                requete('DELETE FROM utilisateur WHERE id = ?', [$id]);
                flash('success', 'User deleted with all their data.');
                break;

            default:
                throw new RuntimeException('Unknown action.');
        }
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        flash('error', str_contains($e->getMessage(), 'UNIQUE') ? 'This entry already exists.' : message_erreur($e));
    }
    rediriger('admin/#' . $onglet);
}

/* ---------- Indicateurs du tableau de bord ---------- */

$kpi = requete("SELECT
        (SELECT COUNT(DISTINCT id_utilisateur) FROM v_abonnement_courant) AS abonnes,
        (SELECT COALESCE(SUM(prix_mensuel), 0) FROM v_abonnement_courant) AS revenu_mensuel,
        (SELECT COUNT(*) FROM utilisateur WHERE date_creation >= datetime('now', '-30 days')) AS inscriptions_30j,
        (SELECT COALESCE(SUM(position_secondes), 0) / 3600.0 FROM visionnage WHERE date_visionnage >= datetime('now', '-30 days')) AS heures_30j,
        (SELECT COUNT(*) FROM video) AS titres,
        (SELECT COUNT(*) FROM episode) AS episodes,
        (SELECT COUNT(*) FROM utilisateur u WHERE EXISTS (SELECT 1 FROM abonnement a WHERE a.id_utilisateur = u.id)
            AND NOT EXISTS (SELECT 1 FROM v_abonnement_courant c WHERE c.id_utilisateur = u.id)) AS anciens")->fetch();

$parOffre = requete("SELECT o.nom, o.prix_mensuel, COUNT(c.id) AS abonnes, COALESCE(SUM(c.prix_mensuel), 0) AS revenu
                     FROM offre o LEFT JOIN v_abonnement_courant c ON c.id_offre = o.id
                     GROUP BY o.id ORDER BY o.prix_mensuel")->fetchAll();
$maxRevenu = max(1, ...array_map(fn ($o) => (float) $o['revenu'], $parOffre ?: [['revenu' => 1]]));

$inscriptions = [];
for ($i = 11; $i >= 0; $i--) {
    $inscriptions[date('Y-m', strtotime("first day of -$i month"))] = 0;
}
foreach (requete("SELECT strftime('%Y-%m', date_creation) AS mois, COUNT(*) AS n FROM utilisateur
                  WHERE date_creation >= date('now', 'start of month', '-11 months') GROUP BY mois") as $ligne) {
    $inscriptions[$ligne['mois']] = (int) $ligne['n'];
}
$maxInscriptions = max(1, ...array_values($inscriptions));

$topTitres = requete("SELECT titre, type, nb_spectateurs,
                        (SELECT COUNT(*) FROM favoris f WHERE f.id_video = v.id) AS favoris
                      FROM v_catalogue v ORDER BY nb_spectateurs DESC, favoris DESC LIMIT 5")->fetchAll();

/* ---------- Données des onglets ---------- */

$categories = requete('SELECT * FROM categorie ORDER BY nom')->fetchAll();
$videos = requete("SELECT v.*, f.duree, f.url_video, s.terminee,
                     (SELECT group_concat(id_categorie) FROM video_categorie WHERE id_video = v.id) AS ids_categories
                   FROM v_catalogue v LEFT JOIN film f ON f.id_video = v.id LEFT JOIN serie s ON s.id_video = v.id
                   ORDER BY v.titre")->fetchAll();
$series = array_values(array_filter($videos, fn ($v) => $v['type'] === 'serie'));
$films = array_values(array_filter($videos, fn ($v) => $v['type'] === 'film'));
$episodes = requete('SELECT e.*, v.titre AS serie FROM episode e JOIN video v ON v.id = e.id_serie
                     ORDER BY v.titre, e.numero_saison, e.numero_episode')->fetchAll();
$soustitres = requete("SELECT s.*, COALESCE(vf.titre, vs.titre || ' · S' || e.numero_saison || 'E' || e.numero_episode) AS cible
                       FROM soustitre s
                       LEFT JOIN video vf ON vf.id = s.id_film
                       LEFT JOIN episode e ON e.id = s.id_episode
                       LEFT JOIN video vs ON vs.id = e.id_serie
                       ORDER BY cible, s.libelle")->fetchAll();
$offres = requete('SELECT o.*, (SELECT COUNT(*) FROM abonnement a WHERE a.id_offre = o.id) AS nb_abonnements
                   FROM offre o ORDER BY o.prix_mensuel')->fetchAll();
$utilisateurs = requete("SELECT u.id, u.nom, u.email, u.est_admin, u.date_creation, c.offre, c.date_fin,
                           (SELECT COUNT(*) FROM profils p WHERE p.id_utilisateur = u.id) AS nb_profils
                         FROM utilisateur u LEFT JOIN v_abonnement_courant c ON c.id_utilisateur = u.id
                         ORDER BY u.date_creation DESC")->fetchAll();

/* Les données d'une ligne, pour pré-remplir le formulaire de modification */
function donnees(array $ligne): string
{
    return e(json_encode($ligne, JSON_UNESCAPED_UNICODE));
}

function bouton_supprimer(string $action, int $id, string $question): string
{
    return '<form method="post" class="inline-form" data-confirm="' . e($question) . '">' . champ_csrf()
        . '<input type="hidden" name="action" value="' . $action . '"><input type="hidden" name="id" value="' . $id . '">'
        . '<button type="submit" class="action-button delete-button"><i class="fas fa-trash"></i> Delete</button></form>';
}

entete('Admin', ['admin.css']);
?>
    <nav class="admin-navbar">
        <a href="<?= url('admin/') ?>"><img class="admin-logo" src="<?= url('images/logo.png') ?>" alt="BING"></a>
        <div class="admin-nav-links">
            <a href="<?= url('profiles.php') ?>" class="admin-link"><i class="fas fa-tv"></i> Open the site</a>
            <form method="post" action="<?= url('logout.php') ?>"><?= champ_csrf() ?>
                <button type="submit" class="admin-logout">Log Out</button>
            </form>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Admin Dashboard</h1>
        </div>

        <div class="admin-section">
            <div class="tabs" role="tablist">
                <?php foreach (['dashboard' => 'Dashboard', 'videos' => 'Titles', 'episodes' => 'Episodes',
                                'subtitles' => 'Subtitles', 'plans' => 'Plans', 'users' => 'Users'] as $cle => $nom): ?>
                    <button class="tab" data-tab="<?= $cle ?>" role="tab"><?= $nom ?></button>
                <?php endforeach; ?>
            </div>

            <!-- Tableau de bord -->
            <div class="tab-content" id="dashboard-tab">
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-value"><?= (int) $kpi['abonnes'] ?></div><div class="stat-label">Active subscribers</div></div>
                    <div class="stat-card"><div class="stat-value">$<?= number_format((float) $kpi['revenu_mensuel'], 2) ?></div><div class="stat-label">Monthly revenue</div></div>
                    <div class="stat-card"><div class="stat-value"><?= (int) $kpi['inscriptions_30j'] ?></div><div class="stat-label">Sign-ups (30 days)</div></div>
                    <div class="stat-card"><div class="stat-value"><?= number_format((float) $kpi['heures_30j'], 0) ?> h</div><div class="stat-label">Watched (30 days)</div></div>
                    <div class="stat-card"><div class="stat-value"><?= (int) $kpi['titres'] ?></div><div class="stat-label">Titles · <?= (int) $kpi['episodes'] ?> episodes</div></div>
                    <div class="stat-card"><div class="stat-value"><?= (int) $kpi['anciens'] ?></div><div class="stat-label">Former subscribers</div></div>
                </div>

                <div class="dashboard-grid">
                    <div class="chart-card">
                        <h3>Revenue by plan</h3>
                        <?php foreach ($parOffre as $offre): ?>
                            <div class="bar-row">
                                <span class="bar-label"><?= e($offre['nom']) ?></span>
                                <span class="bar-track"><span class="bar-fill" style="width: <?= round(100 * $offre['revenu'] / $maxRevenu) ?>%"></span></span>
                                <span class="bar-value">$<?= number_format((float) $offre['revenu'], 2) ?> · <?= (int) $offre['abonnes'] ?> subs</span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="chart-card">
                        <h3>Sign-ups per month</h3>
                        <div class="column-chart">
                            <?php foreach ($inscriptions as $mois => $nombre): ?>
                                <div class="column" title="<?= e($mois) ?> : <?= $nombre ?>">
                                    <span class="column-value"><?= $nombre ?: '' ?></span>
                                    <span class="column-bar" style="height: <?= round(100 * $nombre / $maxInscriptions) ?>%"></span>
                                    <span class="column-label"><?= e(date('M', strtotime($mois . '-01'))) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Most watched titles</h3>
                        <table class="data-table compact">
                            <thead><tr><th>Title</th><th>Viewers</th><th>In lists</th></tr></thead>
                            <tbody>
                            <?php foreach ($topTitres as $titre): ?>
                                <tr><td><?= e($titre['titre']) ?> <span class="muted"><?= $titre['type'] === 'film' ? 'Movie' : 'Series' ?></span></td>
                                    <td><?= (int) $titre['nb_spectateurs'] ?></td><td><?= (int) $titre['favoris'] ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Titres -->
            <div class="tab-content" id="videos-tab">
                <div class="tab-toolbar">
                    <h3 class="section-title"><i class="fas fa-film"></i> Titles</h3>
                    <button class="submit-button" data-open="videoModal"><i class="fas fa-plus"></i> Add a title</button>
                </div>
                <table class="data-table">
                    <thead><tr><th>Title</th><th>Type</th><th>Genres</th><th>Age</th><th>Length</th><th>Viewers</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($videos as $video): ?>
                        <tr>
                            <td><img class="table-thumb" src="<?= e(url($video['image'])) ?>" alt=""> <?= e($video['titre']) ?></td>
                            <td><?= $video['type'] === 'film' ? 'Movie' : 'Series' ?></td>
                            <td><?= e($video['categories']) ?></td>
                            <td><?= age_libelle((int) $video['age_minimum']) ?></td>
                            <td><?= $video['type'] === 'film' ? duree((int) $video['duree']) : (int) $video['nb_episodes'] . ' ep.' ?></td>
                            <td><?= (int) $video['nb_spectateurs'] ?></td>
                            <td><div class="action-buttons">
                                <button class="action-button edit-button" data-edit="videoModal" data-json="<?= donnees($video) ?>"><i class="fas fa-edit"></i> Edit</button>
                                <?= bouton_supprimer('delete_video', (int) $video['id'], 'Delete "' . $video['titre'] . '" with its episodes and history?') ?>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Épisodes -->
            <div class="tab-content" id="episodes-tab">
                <div class="tab-toolbar">
                    <h3 class="section-title"><i class="fas fa-list-ol"></i> Episodes</h3>
                    <button class="submit-button" data-open="episodeModal"><i class="fas fa-plus"></i> Add an episode</button>
                </div>
                <table class="data-table">
                    <thead><tr><th>Series</th><th>#</th><th>Title</th><th>Length</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($episodes as $episode): ?>
                        <tr>
                            <td><?= e($episode['serie']) ?></td>
                            <td>S<?= (int) $episode['numero_saison'] ?> E<?= (int) $episode['numero_episode'] ?></td>
                            <td><?= e($episode['titre']) ?></td>
                            <td><?= duree((int) $episode['duree']) ?></td>
                            <td><div class="action-buttons">
                                <button class="action-button edit-button" data-edit="episodeModal" data-json="<?= donnees($episode) ?>"><i class="fas fa-edit"></i> Edit</button>
                                <?= bouton_supprimer('delete_episode', (int) $episode['id'], 'Delete this episode?') ?>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sous-titres -->
            <div class="tab-content" id="subtitles-tab">
                <div class="tab-toolbar">
                    <h3 class="section-title"><i class="fas fa-closed-captioning"></i> Subtitles</h3>
                    <button class="submit-button" data-open="subtitleModal"><i class="fas fa-plus"></i> Add a track</button>
                </div>
                <table class="data-table">
                    <thead><tr><th>For</th><th>Language</th><th>File</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($soustitres as $piste): ?>
                        <tr>
                            <td><?= e($piste['cible']) ?></td>
                            <td><?= e($piste['libelle']) ?> (<?= e($piste['langue']) ?>)</td>
                            <td class="muted"><?= e($piste['url_fichier']) ?></td>
                            <td><?= bouton_supprimer('delete_subtitle', (int) $piste['id'], 'Delete this subtitle track?') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Offres -->
            <div class="tab-content" id="plans-tab">
                <div class="tab-toolbar">
                    <h3 class="section-title"><i class="fas fa-crown"></i> Plans</h3>
                    <button class="submit-button" data-open="planModal"><i class="fas fa-plus"></i> Add a plan</button>
                </div>
                <table class="data-table">
                    <thead><tr><th>Plan</th><th>Price</th><th>Profiles</th><th>Quality</th><th>Subscriptions</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($offres as $offre): ?>
                        <tr>
                            <td><?= e($offre['nom']) ?><?= $offre['mise_en_avant'] ? ' <span class="pill">Featured</span>' : '' ?></td>
                            <td>$<?= number_format((float) $offre['prix_mensuel'], 2) ?></td>
                            <td><?= (int) $offre['nb_profils_max'] ?></td>
                            <td><?= e($offre['qualite_max']) ?></td>
                            <td><?= (int) $offre['nb_abonnements'] ?></td>
                            <td><?= $offre['disponible'] ? 'Available' : '<span class="muted">Hidden</span>' ?></td>
                            <td><div class="action-buttons">
                                <button class="action-button edit-button" data-edit="planModal" data-json="<?= donnees($offre) ?>"><i class="fas fa-edit"></i> Edit</button>
                                <?php if (!$offre['nb_abonnements']): ?>
                                    <?= bouton_supprimer('delete_plan', (int) $offre['id'], 'Delete this plan?') ?>
                                <?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Utilisateurs -->
            <div class="tab-content" id="users-tab">
                <div class="tab-toolbar">
                    <h3 class="section-title"><i class="fas fa-users"></i> Users (<?= count($utilisateurs) ?>)</h3>
                    <input type="search" class="table-filter" placeholder="Filter by name or email" data-filter="users-table">
                </div>
                <table class="data-table" id="users-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Plan</th><th>Until</th><th>Profiles</th><th>Joined</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($utilisateurs as $u): ?>
                        <tr>
                            <td><?= e($u['nom']) ?><?= $u['est_admin'] ? ' <span class="pill">Admin</span>' : '' ?></td>
                            <td><?= e($u['email']) ?></td>
                            <td><?= $u['offre'] ? e($u['offre']) : '<span class="muted">None</span>' ?></td>
                            <td><?= e($u['date_fin'] ?? '') ?></td>
                            <td><?= (int) $u['nb_profils'] ?></td>
                            <td><?= e(substr($u['date_creation'], 0, 10)) ?></td>
                            <td><div class="action-buttons">
                                <button class="action-button edit-button" data-edit="userModal" data-json="<?= donnees($u) ?>"><i class="fas fa-edit"></i> Edit</button>
                                <?php if ((int) $u['id'] !== (int) $admin['id']): ?>
                                    <?= bouton_supprimer('delete_user', (int) $u['id'], 'Delete ' . $u['email'] . ' and all their data?') ?>
                                <?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Formulaire : titre -->
    <div class="modal-overlay" id="videoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" data-title-new="Add a title" data-title-edit="Edit title">Add a title</h2>
                <button class="close-modal" type="button" aria-label="Close">&times;</button>
            </div>
            <form method="POST">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="save_video">
                <input type="hidden" name="id" value="0">
                <div class="form-grid">
                    <div class="form-group"><label>Title</label><input type="text" name="titre" required maxlength="200"></div>
                    <div class="form-group"><label>Type</label>
                        <select name="type" data-new-only><option value="film">Movie</option><option value="serie">Series</option></select></div>
                    <div class="form-group"><label>Release date</label><input type="date" name="date_publication" required></div>
                    <div class="form-group"><label>Original language</label><input type="text" name="langue_originale" required maxlength="50"></div>
                    <div class="form-group"><label>Age rating</label>
                        <select name="age_minimum"><?php foreach ([0, 10, 12, 16, 18] as $age): ?><option value="<?= $age ?>"><?= age_libelle($age) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Poster (images/... or https://)</label><input type="text" name="image" required placeholder="images/posters/..."></div>
                    <div class="form-group"><label>Backdrop (optional)</label><input type="text" name="image_fond" placeholder="images/posters/..."></div>
                    <div class="form-group" data-type="film"><label>Length (minutes)</label><input type="number" name="duree" min="1" max="600"></div>
                    <div class="form-group" data-type="film"><label>Video URL</label><input type="text" name="url_video" placeholder="https://..."></div>
                    <div class="form-group checkbox" data-type="serie"><label><input type="checkbox" name="terminee"> Series has ended</label></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="3" required maxlength="1000"></textarea></div>
                <div class="form-group"><label>Genres</label>
                    <div class="checkbox-grid">
                        <?php foreach ($categories as $categorie): ?>
                            <label><input type="checkbox" name="categories[]" value="<?= (int) $categorie['id'] ?>"> <?= e($categorie['nom']) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="submit-button"><i class="fas fa-save"></i> Save</button>
            </form>
        </div>
    </div>

    <!-- Formulaire : épisode -->
    <div class="modal-overlay" id="episodeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" data-title-new="Add an episode" data-title-edit="Edit episode">Add an episode</h2>
                <button class="close-modal" type="button" aria-label="Close">&times;</button>
            </div>
            <form method="POST">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="save_episode">
                <input type="hidden" name="id" value="0">
                <div class="form-grid">
                    <div class="form-group"><label>Series</label>
                        <select name="id_serie" required><?php foreach ($series as $serie): ?><option value="<?= (int) $serie['id'] ?>"><?= e($serie['titre']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Title</label><input type="text" name="titre" required maxlength="200"></div>
                    <div class="form-group"><label>Season</label><input type="number" name="numero_saison" min="1" max="99" required value="1"></div>
                    <div class="form-group"><label>Episode</label><input type="number" name="numero_episode" min="1" max="999" required></div>
                    <div class="form-group"><label>Length (minutes)</label><input type="number" name="duree" min="1" max="600" required></div>
                    <div class="form-group"><label>Video URL</label><input type="text" name="url_video" required placeholder="https://..."></div>
                    <div class="form-group"><label>Still image (optional)</label><input type="text" name="image_episode" placeholder="images/posters/..."></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="3" required maxlength="1000"></textarea></div>
                <button type="submit" class="submit-button"><i class="fas fa-save"></i> Save</button>
            </form>
        </div>
    </div>

    <!-- Formulaire : sous-titres -->
    <div class="modal-overlay" id="subtitleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add a subtitle track</h2>
                <button class="close-modal" type="button" aria-label="Close">&times;</button>
            </div>
            <form method="POST">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="save_subtitle">
                <div class="form-grid">
                    <div class="form-group"><label>For</label>
                        <select name="cible" required>
                            <optgroup label="Movies"><?php foreach ($films as $film): ?><option value="film:<?= (int) $film['id'] ?>"><?= e($film['titre']) ?></option><?php endforeach; ?></optgroup>
                            <optgroup label="Episodes"><?php foreach ($episodes as $episode): ?><option value="episode:<?= (int) $episode['id'] ?>"><?= e($episode['serie']) ?> · S<?= (int) $episode['numero_saison'] ?>E<?= (int) $episode['numero_episode'] ?></option><?php endforeach; ?></optgroup>
                        </select></div>
                    <div class="form-group"><label>Language code</label><input type="text" name="langue" required maxlength="5" placeholder="fr"></div>
                    <div class="form-group"><label>Label</label><input type="text" name="libelle" required maxlength="40" placeholder="Français"></div>
                    <div class="form-group"><label>WebVTT file</label><input type="text" name="url_fichier" required placeholder="subtitles/....vtt"></div>
                </div>
                <button type="submit" class="submit-button"><i class="fas fa-save"></i> Save</button>
            </form>
        </div>
    </div>

    <!-- Formulaire : offre -->
    <div class="modal-overlay" id="planModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" data-title-new="Add a plan" data-title-edit="Edit plan">Add a plan</h2>
                <button class="close-modal" type="button" aria-label="Close">&times;</button>
            </div>
            <form method="POST">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="save_plan">
                <input type="hidden" name="id" value="0">
                <div class="form-grid">
                    <div class="form-group"><label>Name</label><input type="text" name="nom" required maxlength="40"></div>
                    <div class="form-group"><label>Monthly price ($)</label><input type="number" name="prix_mensuel" min="0" max="999" step="0.01" required></div>
                    <div class="form-group"><label>Max profiles</label><input type="number" name="nb_profils_max" min="1" max="10" required value="1"></div>
                    <div class="form-group"><label>Max quality</label>
                        <select name="qualite_max"><option>SD</option><option selected>HD</option><option>4K</option></select></div>
                    <div class="form-group checkbox"><label><input type="checkbox" name="mise_en_avant"> Featured ("Most popular")</label></div>
                    <div class="form-group checkbox"><label><input type="checkbox" name="disponible" checked> Available for new subscriptions</label></div>
                </div>
                <div class="form-group"><label>Benefits (one per line)</label><textarea name="avantages" rows="4" maxlength="1000"></textarea></div>
                <p class="form-note">Changing the price only applies to new subscriptions: current subscribers keep the price they signed up with.</p>
                <button type="submit" class="submit-button"><i class="fas fa-save"></i> Save</button>
            </form>
        </div>
    </div>

    <!-- Formulaire : utilisateur -->
    <div class="modal-overlay" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit user</h2>
                <button class="close-modal" type="button" aria-label="Close">&times;</button>
            </div>
            <form method="POST">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="save_user">
                <input type="hidden" name="id" value="0">
                <div class="form-grid">
                    <div class="form-group"><label>Name</label><input type="text" name="nom" required maxlength="60"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                    <div class="form-group checkbox"><label><input type="checkbox" name="est_admin"> Administrator</label></div>
                </div>
                <button type="submit" class="submit-button"><i class="fas fa-save"></i> Save</button>
            </form>
        </div>
    </div>
<?php pied(['admin.js']); ?>
