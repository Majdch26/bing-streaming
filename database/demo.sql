-- =====================================================================
-- BING : données de démonstration
-- Comptes : demo@bing.test / Demo1234!   et   admin@bing.test / Admin1234!
-- Les dates sont relatives à aujourd'hui : la démo reste valide dans le temps.
-- Vidéos : films libres de la fondation Blender et extraits d'exemple
-- hébergés par Google (gtv-videos-bucket).
-- =====================================================================

-- ---------- Offres ----------
INSERT INTO offre (id, nom, prix_mensuel, nb_profils_max, qualite_max, mise_en_avant, avantages) VALUES
(1, 'Basic', 7.99, 1, 'SD', 0, 'SD quality (480p)
Watch on 1 screen at a time
1 profile
Cancel anytime'),
(2, 'Standard', 12.99, 2, 'HD', 1, 'HD quality (1080p)
Watch on 2 screens at once
Up to 2 profiles
Cancel anytime'),
(3, 'Premium', 17.99, 4, '4K', 0, 'Ultra HD quality (4K)
Watch on 4 screens at once
Up to 4 profiles
Cancel anytime');

-- ---------- Catégories ----------
INSERT INTO categorie (id, nom, slug) VALUES
(1, 'Action', 'action'), (2, 'Adventure', 'adventure'), (3, 'Comedy', 'comedy'),
(4, 'Crime', 'crime'), (5, 'Drama', 'drama'), (6, 'Fantasy', 'fantasy'),
(7, 'Horror', 'horror'), (8, 'Mystery', 'mystery'), (9, 'Sci-Fi', 'sci-fi'),
(10, 'Thriller', 'thriller');

-- ---------- Vidéos ----------
INSERT INTO video (id, titre, type, description, date_publication, langue_originale, image, image_fond, age_minimum) VALUES
(1, 'Inception', 'film', 'A thief who steals corporate secrets through dreams is given the inverse task of planting an idea into the mind of a CEO.', '2010-07-16', 'English', 'images/posters/inception.jpg', NULL, 12),
(2, 'Interstellar', 'film', 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity''s survival.', '2014-11-07', 'English', 'images/posters/interstellar.jpg', NULL, 10),
(3, 'Parasite', 'film', 'A poor family schemes to become employed by a wealthy family by infiltrating their household.', '2019-05-30', 'Korean', 'images/posters/parasite.jpg', NULL, 16),
(4, 'Stranger Things', 'serie', 'When a young boy vanishes, a small town uncovers a mystery involving secret experiments and a strange little girl.', '2016-07-15', 'English', 'images/posters/strangerthings.jpg', 'images/posters/strangerthings_fond.jpg', 12),
(5, 'The Last of Us', 'serie', 'After a fungal outbreak destroys civilization, a hardened survivor takes charge of a 14-year-old girl who may be humanity''s last hope.', '2023-01-15', 'English', 'images/posters/thelastofus.jpg', NULL, 16),
(6, 'Expend4bles', 'film', 'Armed with every weapon they can get their hands on, the Expendables are the world''s last line of defense.', '2023-09-22', 'English', 'images/posters/expend4bles.jpg', NULL, 16),
(7, 'Renfield', 'film', 'Renfield is forced to procure his master''s prey and do his every bidding, until he decides to see if there is a life outside the shadow of the Prince of Darkness.', '2023-04-07', 'English', 'images/posters/renfield.jpg', NULL, 16),
(8, 'Crawl', 'film', 'A woman fights for survival against alligators in a flooded house during a category 5 hurricane.', '2019-07-11', 'English', 'images/posters/crawl.jpg', NULL, 16),
(9, 'Poker Face', 'serie', 'A mystery-of-the-week series following Charlie Cale, who can tell when someone is lying, as she drives across the country.', '2023-01-26', 'English', 'images/posters/pokerface.jpg', 'images/posters/pokerface_fond.jpg', 12),
(10, 'Squid Game', 'serie', 'Hundreds of cash-strapped players accept a strange invitation to compete in children''s games for a tempting prize.', '2021-09-17', 'Korean', 'images/posters/squidgame.jpg', NULL, 16);

INSERT INTO video_categorie (id_video, id_categorie) VALUES
(1, 9), (1, 1), (1, 10),
(2, 9), (2, 2), (2, 5),
(3, 10), (3, 5),
(4, 6), (4, 9), (4, 7),
(5, 5), (5, 2), (5, 7),
(6, 1), (6, 2),
(7, 3), (7, 7),
(8, 10), (8, 7),
(9, 8), (9, 4), (9, 3),
(10, 10), (10, 5), (10, 8);

INSERT INTO film (id_video, duree, url_video) VALUES
(1, 148, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4'),
(2, 169, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4'),
(3, 132, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4'),
(6, 103, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4'),
(7, 93, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4'),
(8, 87, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4');

INSERT INTO serie (id_video, terminee) VALUES (4, 1), (5, 0), (9, 0), (10, 0);

INSERT INTO episode (id, id_serie, numero_saison, numero_episode, titre, description, duree, url_video, image_episode) VALUES
(1, 4, 1, 1, 'Chapter One: The Vanishing of Will Byers', 'On his way home from a friend''s house, young Will sees something terrifying. Nearby, a sinister secret lurks in the depths of a government lab.', 47, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 'images/posters/strangerthings_s1e1.jpg'),
(2, 4, 1, 2, 'Chapter Two: The Weirdo on Maple Street', 'Lucas, Mike and Dustin try to talk to the girl they found in the woods. Hopper questions an anxious Joyce about an unsettling phone call.', 55, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4', 'images/posters/strangerthings_s1e2.jpg'),
(3, 4, 1, 3, 'Chapter Three: Holly, Jolly', 'An increasingly concerned Nancy looks for Barb. Joyce is convinced that Will is trying to talk to her.', 51, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4', 'images/posters/strangerthings_s1e3.jpg'),
(4, 4, 1, 4, 'Chapter Four: The Body', 'Refusing to believe Will is dead, Joyce tries to connect with her son. The boys give Eleven a makeover.', 50, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/SubaruOutbackOnStreetAndDirt.mp4', 'images/posters/strangerthings_s1e4.jpg'),
(5, 4, 1, 5, 'Chapter Five: The Flea and the Acrobat', 'Hopper breaks into the lab while Nancy and Jonathan confront the force that took Will.', 52, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/VolkswagenGTIReview.mp4', 'images/posters/strangerthings_s1e5.jpg'),
(6, 4, 2, 1, 'Chapter One: MADMAX', 'As the town prepares for Halloween, a high-scoring rival shakes things up at the arcade, and a skeptical Hopper inspects a field of rotting pumpkins.', 48, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/WeAreGoingOnBullrun.mp4', NULL),
(7, 4, 2, 2, 'Chapter Two: Trick or Treat, Freak', 'After Will sees something terrible on trick-or-treat night, Mike wonders whether Eleven is still out there.', 56, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/WhatCarCanYouGetForAGrand.mp4', NULL),
(8, 4, 2, 3, 'Chapter Three: The Pollywog', 'Dustin adopts a strange new pet, and Eleven grows increasingly impatient.', 51, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', NULL),
(9, 4, 2, 4, 'Chapter Four: Will the Wise', 'An ailing Will opens up to Joyce, with disturbing results. While Hopper digs for the truth, Eleven unearths a surprising discovery.', 46, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4', NULL),
(10, 4, 2, 5, 'Chapter Five: Dig Dug', 'Nancy and Jonathan swap conspiracy theories with a new ally as Eleven searches for someone from her past.', 58, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4', NULL),
(11, 5, 1, 1, 'When You''re Lost in the Darkness', 'Twenty years after a fungal outbreak ravages the planet, survivors Joel and Tess are tasked with a mission that could change everything.', 81, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4', NULL),
(12, 5, 1, 2, 'Infected', 'Joel, Tess and Ellie cross the ruins of an abandoned city, where the infected are never far away.', 53, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4', NULL),
(13, 5, 1, 3, 'Long, Long Time', 'A survivalist living alone in a fortified town receives an unexpected visitor.', 76, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4', NULL),
(14, 9, 1, 1, 'Dead Man''s Hand', 'When a close friend of hers meets an untimely end, Charlie Cale, a casino waitress with a gift for spotting lies, sets out to find the truth.', 56, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4', NULL),
(15, 9, 1, 2, 'The Night Shift', 'On the run, Charlie takes a job at a roadside diner where a murder seems to have an obvious culprit.', 46, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4', NULL),
(16, 10, 1, 1, 'Red Light, Green Light', 'Hoping to win easy money, a broke father joins hundreds of players in a mysterious game with deadly stakes.', 60, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4', NULL),
(17, 10, 1, 2, 'Hell', 'Back in the outside world, the players realize that their everyday lives may be no better than the game.', 62, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', NULL),
(18, 10, 1, 3, 'The Man with the Umbrella', 'The players prepare for the next game, but some of them already know what is coming.', 54, 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4', NULL);

INSERT INTO soustitre (id_film, id_episode, langue, libelle, url_fichier) VALUES
(1, NULL, 'en', 'English', 'subtitles/demo-en.vtt'),
(1, NULL, 'fr', 'Français', 'subtitles/demo-fr.vtt'),
(2, NULL, 'fr', 'Français', 'subtitles/demo-fr.vtt'),
(NULL, 1, 'en', 'English', 'subtitles/demo-en.vtt'),
(NULL, 1, 'fr', 'Français', 'subtitles/demo-fr.vtt'),
(NULL, 11, 'fr', 'Français', 'subtitles/demo-fr.vtt');

-- ---------- Comptes ----------
-- Mot de passe de tous les comptes de démonstration : Demo1234!  (admin : Admin1234!)
INSERT INTO utilisateur (id, nom, email, mot_de_passe_hash, est_admin, date_creation) VALUES
(1, 'Admin', 'admin@bing.test', '$2y$10$6nhieghcyAPSz0u.VTyn1.ZoPIgVCjnBFnI13XCDFXTn37GYZBj8i', 1, datetime('now', '-13 months')),
(2, 'Majd', 'demo@bing.test', '$2y$10$WLmEog108ici..LK1J8/9uxedIcIrf7IkDoY4XG/hlsqi3CI8ED26', 0, datetime('now', '-11 months'));

-- Abonnés fictifs pour les statistiques de l'administration (inscrits sur 12 mois)
WITH RECURSIVE n(i) AS (SELECT 3 UNION ALL SELECT i + 1 FROM n WHERE i < 26)
INSERT INTO utilisateur (id, nom, email, mot_de_passe_hash, date_creation)
SELECT i,
       'User ' || i,
       'user' || i || '@bing.test',
       '$2y$10$WLmEog108ici..LK1J8/9uxedIcIrf7IkDoY4XG/hlsqi3CI8ED26',
       datetime('now', '-' || ((i * 37) % 360) || ' days', '-' || ((i * 13) % 20) || ' hours')
FROM n;

-- Abonnements : le compte demo est en Standard, l'admin en Premium
INSERT INTO abonnement (id_utilisateur, id_offre, date_debut, date_fin, prix_mensuel) VALUES
(1, 3, date('now', '-20 days'), date('now', '+10 days'), 17.99),
(2, 1, date('now', '-11 months'), date('now', '-5 months', '-1 day'), 7.99),
(2, 2, date('now', '-5 months'), date('now', '+1 month'), 12.99);

-- Pour les autres : un abonnement passé, puis un abonnement en cours pour 3 sur 4
INSERT INTO abonnement (id_utilisateur, id_offre, date_debut, date_fin, prix_mensuel)
SELECT u.id, 1 + (u.id % 3),
       date(u.date_creation),
       date(u.date_creation, '+' || (1 + u.id % 4) || ' months', '-1 day'),
       CASE 1 + (u.id % 3) WHEN 1 THEN 7.99 WHEN 2 THEN 12.99 ELSE 17.99 END
FROM utilisateur u
WHERE u.id > 2;

INSERT INTO abonnement (id_utilisateur, id_offre, date_debut, date_fin, prix_mensuel)
SELECT u.id, 1 + ((u.id + 1) % 3),
       a.date_fin_suivante,
       date('now', '+' || (5 + u.id % 25) || ' days'),
       CASE 1 + ((u.id + 1) % 3) WHEN 1 THEN 7.99 WHEN 2 THEN 12.99 ELSE 17.99 END
FROM utilisateur u
JOIN (SELECT id_utilisateur, date(MAX(date_fin), '+1 day') AS date_fin_suivante
      FROM abonnement GROUP BY id_utilisateur) a ON a.id_utilisateur = u.id
WHERE u.id > 2 AND u.id % 4 <> 0 AND a.date_fin_suivante <= date('now');

-- ---------- Profils ----------
INSERT INTO profils (id, id_utilisateur, nom, age, avatar) VALUES
(1, 1, 'Admin', 30, 'avatar1.png'),
(2, 2, 'Majd', 22, 'avatar5.png'),
(3, 2, 'Kids', 12, 'avatar3.png');

INSERT INTO profils (id_utilisateur, nom, age, avatar)
SELECT id, 'Profile', 14 + (id * 7) % 40, 'avatar' || (1 + id % 8) || '.png'
FROM utilisateur WHERE id > 2;

-- ---------- Favoris ----------
INSERT INTO favoris (id_profil, id_video, date_ajout) VALUES
(2, 4, datetime('now', '-30 days')), (2, 1, datetime('now', '-12 days')), (2, 10, datetime('now', '-3 days'));

INSERT INTO favoris (id_profil, id_video, date_ajout)
SELECT p.id, v.id, datetime('now', '-' || ((p.id * v.id) % 90) || ' days')
FROM profils p
JOIN video v ON v.age_minimum <= p.age
WHERE p.id > 3 AND (p.id * 3 + v.id * 7) % 5 = 0;

-- ---------- Historique de visionnage ----------
-- Le profil Majd a commencé quelques contenus (rangée « Continue watching »)
INSERT INTO visionnage (id_profil, id_film, id_episode, position_secondes, duree_secondes, date_visionnage) VALUES
(2, NULL, 1, 2820, 2820, datetime('now', '-6 days')),
(2, NULL, 2, 1260, 3300, datetime('now', '-1 days')),
(2, 1, NULL, 3900, 8880, datetime('now', '-2 days'));

WITH contenu AS (
    SELECT f.id_video AS id_film, NULL AS id_episode, f.duree, v.age_minimum, f.id_video AS cle
    FROM film f JOIN video v ON v.id = f.id_video
    UNION ALL
    SELECT NULL, e.id, e.duree, v.age_minimum, 100 + e.id
    FROM episode e JOIN video v ON v.id = e.id_serie
)
INSERT INTO visionnage (id_profil, id_film, id_episode, position_secondes, duree_secondes, date_visionnage)
SELECT p.id, c.id_film, c.id_episode,
       c.duree * 60 * (40 + (p.id * c.cle) % 61) / 100,
       c.duree * 60,
       datetime('now', '-' || ((p.id * 11 + c.cle * 5) % 120) || ' days', '-' || ((p.id + c.cle) % 23) || ' hours')
FROM profils p
JOIN contenu c ON c.age_minimum <= p.age
WHERE p.id > 3 AND (p.id * 7 + c.cle * 3) % 4 = 0;
