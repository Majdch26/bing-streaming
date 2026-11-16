<?php
/* Requêtes du catalogue, toujours filtrées selon l'âge du profil */

function videos(array $profil, string $condition = '1 = 1', array $parametres = [],
                string $ordre = 'v.date_publication DESC', int $limite = 0): array
{
    $sql = 'SELECT v.* FROM v_catalogue v WHERE ' . filtre_age($profil) . " AND ($condition) ORDER BY $ordre";
    if ($limite > 0) {
        $sql .= ' LIMIT ' . $limite;
    }
    return requete($sql, $parametres)->fetchAll();
}

/* Vidéos qui partagent au moins une catégorie avec la vidéo donnée */
function videos_similaires(array $profil, int $idVideo, int $limite = 6, ?string $type = null): array
{
    return videos(
        $profil,
        'v.id <> :id AND (:type IS NULL OR v.type = :type) AND v.id IN (
            SELECT vc2.id_video FROM video_categorie vc1
            JOIN video_categorie vc2 ON vc2.id_categorie = vc1.id_categorie
            WHERE vc1.id_video = :id)',
        ['id' => $idVideo, 'type' => $type],
        '(SELECT COUNT(*) FROM video_categorie a JOIN video_categorie b ON a.id_categorie = b.id_categorie
           WHERE a.id_video = v.id AND b.id_video = :id) DESC, v.nb_spectateurs DESC',
        $limite
    );
}

/* Rangée « Continue watching » : films commencés et séries en cours,
   avec le lien pour reprendre au bon endroit */
function continuer_regarder(array $profil): array
{
    $lignes = requete(
        "SELECT vi.*, e.id_serie, e.numero_saison, e.numero_episode, e.titre AS titre_episode,
                COALESCE(vi.id_film, e.id_serie) AS id_video
         FROM visionnage vi
         LEFT JOIN episode e ON e.id = vi.id_episode
         WHERE vi.id_profil = ?
         ORDER BY vi.date_visionnage DESC",
        [$profil['id']]
    )->fetchAll();

    $resultat = [];
    foreach ($lignes as $ligne) {
        $idVideo = (int) $ligne['id_video'];
        if (isset($resultat[$idVideo])) {
            continue;   // seule la lecture la plus récente de chaque série compte
        }
        $fini = $ligne['duree_secondes'] > 0 && $ligne['position_secondes'] >= 0.95 * $ligne['duree_secondes'];
        $progression = $ligne['duree_secondes'] > 0 ? 100 * $ligne['position_secondes'] / $ligne['duree_secondes'] : 0;

        if ($ligne['id_film'] !== null) {
            if ($fini) {
                $resultat[$idVideo] = null;
                continue;
            }
            $lien = 'watch.php?movie=' . $idVideo;
            $sousTitre = max(1, (int) round(($ligne['duree_secondes'] - $ligne['position_secondes']) / 60)) . ' min left';
        } else {
            $episode = $ligne;
            if ($fini) {
                // Épisode terminé : on propose le suivant, s'il existe
                $episode = requete('SELECT id AS id_episode, numero_saison, numero_episode, titre AS titre_episode FROM episode
                                    WHERE id_serie = ? AND (numero_saison > ? OR (numero_saison = ? AND numero_episode > ?))
                                    ORDER BY numero_saison, numero_episode LIMIT 1',
                                   [$idVideo, $ligne['numero_saison'], $ligne['numero_saison'], $ligne['numero_episode']])->fetch();
                if (!$episode) {
                    $resultat[$idVideo] = null;
                    continue;
                }
                $progression = 0;
            }
            $lien = 'watch.php?episode=' . $episode['id_episode'];
            $sousTitre = 'S' . $episode['numero_saison'] . ':E' . $episode['numero_episode'] . ' · ' . $episode['titre_episode'];
        }
        $resultat[$idVideo] = ['lien_reprise' => $lien, 'progression' => $progression, 'sous_titre' => $sousTitre];
    }

    $resultat = array_filter($resultat);
    if (!$resultat) {
        return [];
    }
    $videos = [];
    foreach (videos($profil, 'v.id IN (' . implode(',', array_keys($resultat)) . ')') as $video) {
        $videos[(int) $video['id']] = $video + $resultat[(int) $video['id']];
    }
    // Garder l'ordre « vu le plus récemment »
    return array_values(array_filter(array_map(fn ($id) => $videos[$id] ?? null, array_keys($resultat))));
}

/* Position de lecture enregistrée (en secondes) pour un film ou un épisode */
function position_lecture(int $idProfil, ?int $idFilm, ?int $idEpisode): array
{
    $ligne = requete('SELECT position_secondes, duree_secondes FROM visionnage
                      WHERE id_profil = ? AND id_film IS ? AND id_episode IS ?',
                     [$idProfil, $idFilm, $idEpisode])->fetch();
    return $ligne ?: ['position_secondes' => 0, 'duree_secondes' => 0];
}
