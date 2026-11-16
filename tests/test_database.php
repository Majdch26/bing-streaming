<?php
/*
 * Tests des règles de la base (déclencheurs, contraintes, vues) et des fonctions PHP partagées.
 * Lancer : php tests/test_database.php
 */

$temporaire = tempnam(sys_get_temp_dir(), 'bing');
unlink($temporaire);
$base = $temporaire . '.sqlite';
putenv('BING_DB=' . $base);
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require __DIR__ . '/../includes/bootstrap.php';

$reussis = 0;
$echecs = [];

function verifier(string $nom, bool $condition): void
{
    global $reussis, $echecs;
    if ($condition) {
        $reussis++;
    } else {
        $echecs[] = $nom;
    }
}

/* Vérifie que la requête échoue avec un message qui contient $attendu */
function verifier_erreur(string $nom, callable $action, string $attendu): void
{
    try {
        $action();
        verifier($nom . ' (aucune erreur)', false);
    } catch (Throwable $e) {
        verifier($nom . ' → ' . $e->getMessage(), str_contains($e->getMessage(), $attendu));
    }
}

/* ---------- Création de la base ---------- */
verifier('les clés étrangères sont activées', (int) db()->query('PRAGMA foreign_keys')->fetchColumn() === 1);
verifier('la base est créée au premier appel', file_exists($base) && filesize($base) > 0);
verifier('données de démo : 10 titres', (int) requete('SELECT COUNT(*) FROM video')->fetchColumn() === 10);
verifier('le compte démo a un abonnement Standard en cours', abonnement_courant(2)['offre'] === 'Standard');
verifier('mot de passe démo haché avec bcrypt', password_verify('Demo1234!', requete('SELECT mot_de_passe_hash FROM utilisateur WHERE id = 2')->fetchColumn()));

/* ---------- Catalogue ---------- */
$catalogue = requete('SELECT * FROM v_catalogue WHERE id = 4')->fetch();
verifier('v_catalogue : saisons et épisodes de Stranger Things', $catalogue['nb_saisons'] == 2 && $catalogue['nb_episodes'] == 10);
verifier('v_catalogue : catégories triées', $catalogue['categories'] === 'Fantasy, Horror, Sci-Fi');
verifier_erreur('un film ne devient pas une série', fn () => requete('INSERT INTO serie (id_video) VALUES (1)'), "pas une série");
verifier_erreur('une série ne devient pas un film', fn () => requete("INSERT INTO film (id_video, duree, url_video) VALUES (4, 10, 'x')"), "pas un film");
verifier_erreur('le type d\'une vidéo est figé', fn () => requete("UPDATE video SET type = 'serie' WHERE id = 1"), 'ne peut pas être modifié');
verifier_erreur('âge minimum invalide refusé', fn () => requete('UPDATE video SET age_minimum = 7 WHERE id = 1'), 'CHECK');
verifier_erreur('deux épisodes à la même place refusés',
    fn () => requete("INSERT INTO episode (id_serie, numero_saison, numero_episode, titre, description, duree, url_video) VALUES (4, 1, 1, 'x', 'x', 10, 'x')"),
    'UNIQUE');
verifier_erreur('sous-titre sans film ni épisode refusé',
    fn () => requete("INSERT INTO soustitre (langue, libelle, url_fichier) VALUES ('fr', 'Français', 'x.vtt')"), 'CHECK');

/* ---------- Comptes, abonnements, profils ---------- */
verifier_erreur('email mal formé refusé', fn () => requete("INSERT INTO utilisateur (nom, email, mot_de_passe_hash) VALUES ('x', 'pas-un-email', 'x')"), 'CHECK');
verifier_erreur('email en double refusé (majuscules comprises)',
    fn () => requete("INSERT INTO utilisateur (nom, email, mot_de_passe_hash) VALUES ('x', 'DEMO@bing.test', 'x')"), 'UNIQUE');

requete("INSERT INTO utilisateur (id, nom, email, mot_de_passe_hash) VALUES (100, 'Test', 'test@bing.test', 'x')");
verifier('sans abonnement : aucun abonnement courant', abonnement_courant(100) === null);
requete("INSERT INTO profils (id_utilisateur, nom) VALUES (100, 'Un')");
verifier_erreur('sans abonnement : un seul profil', fn () => requete("INSERT INTO profils (id_utilisateur, nom) VALUES (100, 'Deux')"), 'nombre maximum');

souscrire(100, 2);
$abonnement = abonnement_courant(100);
verifier('souscrire : abonnement Standard en cours', $abonnement['offre'] === 'Standard');
verifier('souscrire : dure un mois', $abonnement['date_fin'] === date('Y-m-d', strtotime('+1 month -1 day')));
requete("INSERT INTO profils (id_utilisateur, nom) VALUES (100, 'Deux')");
verifier_erreur('Standard : pas de 3e profil', fn () => requete("INSERT INTO profils (id_utilisateur, nom) VALUES (100, 'Trois')"), 'nombre maximum');

souscrire(100, 3);
verifier_erreur('deux profils du même nom refusés', fn () => requete("INSERT INTO profils (id_utilisateur, nom) VALUES (100, 'Un')"), 'UNIQUE');
verifier('changer d\'offre le même jour remplace l\'abonnement', abonnement_courant(100)['offre'] === 'Premium'
    && (int) requete('SELECT COUNT(*) FROM abonnement WHERE id_utilisateur = 100')->fetchColumn() === 1);
verifier('le prix payé est celui de l\'offre', (float) abonnement_courant(100)['prix_mensuel'] === 17.99);

requete("UPDATE abonnement SET date_debut = date('now', '-10 days') WHERE id_utilisateur = 100");
souscrire(100, 1);
$historique = requete('SELECT * FROM abonnement WHERE id_utilisateur = 100 ORDER BY date_debut')->fetchAll();
verifier('changer d\'offre en cours de mois : l\'ancien abonnement s\'arrête hier',
    count($historique) === 2 && $historique[0]['date_fin'] === date('Y-m-d', strtotime('-1 day')));
verifier('l\'historique garde l\'ancien prix', (float) $historique[0]['prix_mensuel'] === 17.99);

verifier_erreur('abonnements qui se chevauchent refusés',
    fn () => requete("INSERT INTO abonnement (id_utilisateur, id_offre, date_debut, date_fin, prix_mensuel) VALUES (100, 1, date('now'), date('now', '+5 days'), 1)"),
    'chevauche');
verifier_erreur('une offre utilisée ne peut pas être supprimée', fn () => requete('DELETE FROM offre WHERE id = 1'), 'FOREIGN KEY');

$prixAvant = (float) abonnement_courant(100)['prix_mensuel'];
requete('UPDATE offre SET prix_mensuel = 99 WHERE id = 1');
verifier('changer le prix d\'une offre ne change pas les abonnements en cours', (float) abonnement_courant(100)['prix_mensuel'] === $prixAvant);
requete('UPDATE offre SET prix_mensuel = 7.99 WHERE id = 1');

/* ---------- Âge, favoris, visionnage ---------- */
$enfant = (int) requete("SELECT id FROM profils WHERE nom = 'Kids' AND id_utilisateur = 2")->fetchColumn();
verifier_erreur('un enfant ne met pas en favori une série 16+', fn () => requete('INSERT INTO favoris (id_profil, id_video) VALUES (?, 10)', [$enfant]), 'âge');
verifier_erreur('un enfant ne regarde pas un épisode 16+',
    fn () => requete('INSERT INTO visionnage (id_profil, id_episode) VALUES (?, 16)', [$enfant]), 'âge');
requete('INSERT INTO visionnage (id_profil, id_episode, position_secondes, duree_secondes) VALUES (?, 1, 60, 2820)', [$enfant]);
verifier('un profil de 12 ans peut regarder un épisode 12+', (int) requete('SELECT COUNT(*) FROM visionnage WHERE id_profil = ?', [$enfant])->fetchColumn() === 1);
verifier('filtre_age : un profil de 12 ans voit seulement les titres 12+ et moins',
    array_column(requete('SELECT v.titre FROM video v WHERE ' . filtre_age(['age' => 12]) . ' ORDER BY v.titre')->fetchAll(), 'titre')
    === ['Inception', 'Interstellar', 'Poker Face', 'Stranger Things']);
verifier('filtre_age : âge non renseigné = aucune restriction', filtre_age(['age' => null]) === '1 = 1');

verifier_erreur('une seule ligne de visionnage par profil et par épisode',
    fn () => requete('INSERT INTO visionnage (id_profil, id_episode) VALUES (2, 1)'), 'UNIQUE');

/* ---------- Suppressions en cascade ---------- */
requete('DELETE FROM utilisateur WHERE id = 2');
verifier('supprimer un compte supprime profils, favoris, visionnages et abonnements',
    (int) requete('SELECT (SELECT COUNT(*) FROM profils WHERE id_utilisateur = 2) + (SELECT COUNT(*) FROM abonnement WHERE id_utilisateur = 2)
                   + (SELECT COUNT(*) FROM favoris WHERE id_profil IN (2, 3)) + (SELECT COUNT(*) FROM visionnage WHERE id_profil IN (2, 3))')->fetchColumn() === 0);
requete('DELETE FROM video WHERE id = 4');
verifier('supprimer une série supprime ses épisodes et sous-titres',
    (int) requete('SELECT (SELECT COUNT(*) FROM episode WHERE id_serie = 4) + (SELECT COUNT(*) FROM soustitre WHERE id_episode IN (1, 2, 3))')->fetchColumn() === 0);

/* ---------- Fonctions d'affichage ---------- */
verifier('duree() : heures et minutes', duree(148) === '2h 28m' && duree(45) === '45m');
verifier('e() échappe le HTML', e('<script>"\'') === '&lt;script&gt;&quot;&#039;');
verifier('message_erreur() extrait le message du déclencheur',
    message_erreur(new PDOException('SQLSTATE[HY000]: General error: 19 Le nombre maximum de profils pour cet abonnement est atteint.'))
    === 'Le nombre maximum de profils pour cet abonnement est atteint.');

/* ---------- Bilan ---------- */
@unlink($base);
foreach ($echecs as $echec) {
    echo "ÉCHEC : $echec\n";
}
echo "$reussis / " . ($reussis + count($echecs)) . " tests réussis\n";
exit($echecs ? 1 : 0);
