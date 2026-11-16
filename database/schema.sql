-- =====================================================================
-- BING : schéma SQLite
-- Même modèle que le projet de base de données (streaming-sql),
-- adapté à SQLite : catalogue, offres, abonnements datés, profils,
-- favoris, historique de visionnage et règles de gestion en déclencheurs.
-- =====================================================================

PRAGMA foreign_keys = ON;

-- ---------- Catalogue ----------

CREATE TABLE categorie (
    id      INTEGER PRIMARY KEY,
    nom     TEXT NOT NULL UNIQUE COLLATE NOCASE,
    slug    TEXT NOT NULL UNIQUE
);

CREATE TABLE video (
    id                  INTEGER PRIMARY KEY,
    titre               TEXT NOT NULL,
    type                TEXT NOT NULL CHECK (type IN ('film', 'serie')),
    description         TEXT NOT NULL,
    date_publication    TEXT NOT NULL,
    langue_originale    TEXT NOT NULL,
    image               TEXT NOT NULL,
    image_fond          TEXT,
    age_minimum         INTEGER NOT NULL DEFAULT 0 CHECK (age_minimum IN (0, 10, 12, 16, 18)),
    date_ajout          TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (titre, langue_originale)
);
CREATE INDEX idx_video_type ON video (type);
CREATE INDEX idx_video_date_publication ON video (date_publication);

CREATE TABLE video_categorie (
    id_video        INTEGER NOT NULL REFERENCES video (id) ON DELETE CASCADE,
    id_categorie    INTEGER NOT NULL REFERENCES categorie (id) ON DELETE CASCADE,
    PRIMARY KEY (id_video, id_categorie)
);
CREATE INDEX idx_video_categorie_categorie ON video_categorie (id_categorie);

CREATE TABLE film (
    id_video    INTEGER PRIMARY KEY REFERENCES video (id) ON DELETE CASCADE,
    duree       INTEGER NOT NULL CHECK (duree > 0),          -- en minutes
    url_video   TEXT NOT NULL
);

CREATE TABLE serie (
    id_video    INTEGER PRIMARY KEY REFERENCES video (id) ON DELETE CASCADE,
    terminee    INTEGER NOT NULL DEFAULT 0 CHECK (terminee IN (0, 1))
);

CREATE TABLE episode (
    id              INTEGER PRIMARY KEY,
    id_serie        INTEGER NOT NULL REFERENCES serie (id_video) ON DELETE CASCADE,
    numero_saison   INTEGER NOT NULL CHECK (numero_saison > 0),
    numero_episode  INTEGER NOT NULL CHECK (numero_episode > 0),
    titre           TEXT NOT NULL,
    description     TEXT NOT NULL,
    duree           INTEGER NOT NULL CHECK (duree > 0),      -- en minutes
    url_video       TEXT NOT NULL,
    image_episode   TEXT,
    UNIQUE (id_serie, numero_saison, numero_episode)
);

CREATE TABLE soustitre (
    id              INTEGER PRIMARY KEY,
    id_film         INTEGER REFERENCES film (id_video) ON DELETE CASCADE,
    id_episode      INTEGER REFERENCES episode (id) ON DELETE CASCADE,
    langue          TEXT NOT NULL,                           -- code : fr, en...
    libelle         TEXT NOT NULL,                           -- Français, English...
    url_fichier     TEXT NOT NULL,
    CHECK ((id_film IS NULL) <> (id_episode IS NULL))
);
CREATE UNIQUE INDEX unq_soustitre_film_langue ON soustitre (id_film, langue) WHERE id_film IS NOT NULL;
CREATE UNIQUE INDEX unq_soustitre_episode_langue ON soustitre (id_episode, langue) WHERE id_episode IS NOT NULL;

-- ---------- Comptes et abonnements ----------

CREATE TABLE offre (
    id              INTEGER PRIMARY KEY,
    nom             TEXT NOT NULL UNIQUE,
    prix_mensuel    REAL NOT NULL CHECK (prix_mensuel >= 0),
    nb_profils_max  INTEGER NOT NULL DEFAULT 1 CHECK (nb_profils_max BETWEEN 1 AND 10),
    qualite_max     TEXT NOT NULL DEFAULT 'HD' CHECK (qualite_max IN ('SD', 'HD', '4K')),
    avantages       TEXT NOT NULL DEFAULT '',                -- une ligne par avantage
    mise_en_avant   INTEGER NOT NULL DEFAULT 0 CHECK (mise_en_avant IN (0, 1)),
    disponible      INTEGER NOT NULL DEFAULT 1 CHECK (disponible IN (0, 1))
);

CREATE TABLE utilisateur (
    id                  INTEGER PRIMARY KEY,
    nom                 TEXT NOT NULL,
    email               TEXT NOT NULL UNIQUE COLLATE NOCASE CHECK (email LIKE '%_@_%._%'),
    mot_de_passe_hash   TEXT NOT NULL,                       -- password_hash() (bcrypt)
    est_admin           INTEGER NOT NULL DEFAULT 0 CHECK (est_admin IN (0, 1)),
    date_creation       TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Chaque souscription a ses propres dates et garde le prix payé
CREATE TABLE abonnement (
    id              INTEGER PRIMARY KEY,
    id_utilisateur  INTEGER NOT NULL REFERENCES utilisateur (id) ON DELETE CASCADE,
    id_offre        INTEGER NOT NULL REFERENCES offre (id),
    date_debut      TEXT NOT NULL,
    date_fin        TEXT NOT NULL,
    prix_mensuel    REAL NOT NULL CHECK (prix_mensuel >= 0),
    CHECK (date_fin >= date_debut)
);
CREATE INDEX idx_abonnement_utilisateur ON abonnement (id_utilisateur, date_debut, date_fin);

CREATE TABLE profils (
    id              INTEGER PRIMARY KEY,
    id_utilisateur  INTEGER NOT NULL REFERENCES utilisateur (id) ON DELETE CASCADE,
    nom             TEXT NOT NULL CHECK (length(trim(nom)) BETWEEN 1 AND 30),
    age             INTEGER CHECK (age BETWEEN 0 AND 120),
    avatar          TEXT NOT NULL DEFAULT 'avatar1.png',
    UNIQUE (id_utilisateur, nom)
);

CREATE TABLE favoris (
    id_profil   INTEGER NOT NULL REFERENCES profils (id) ON DELETE CASCADE,
    id_video    INTEGER NOT NULL REFERENCES video (id) ON DELETE CASCADE,
    date_ajout  TEXT NOT NULL DEFAULT (datetime('now')),
    PRIMARY KEY (id_profil, id_video)
);

-- Une ligne par profil et par film ou épisode : position de lecture pour reprendre
CREATE TABLE visionnage (
    id                  INTEGER PRIMARY KEY,
    id_profil           INTEGER NOT NULL REFERENCES profils (id) ON DELETE CASCADE,
    id_film             INTEGER REFERENCES film (id_video) ON DELETE CASCADE,
    id_episode          INTEGER REFERENCES episode (id) ON DELETE CASCADE,
    position_secondes   INTEGER NOT NULL DEFAULT 0 CHECK (position_secondes >= 0),
    duree_secondes      INTEGER NOT NULL DEFAULT 0 CHECK (duree_secondes >= 0),
    date_visionnage     TEXT NOT NULL DEFAULT (datetime('now')),
    CHECK ((id_film IS NULL) <> (id_episode IS NULL))
);
CREATE UNIQUE INDEX unq_visionnage_film ON visionnage (id_profil, id_film) WHERE id_film IS NOT NULL;
CREATE UNIQUE INDEX unq_visionnage_episode ON visionnage (id_profil, id_episode) WHERE id_episode IS NOT NULL;
CREATE INDEX idx_visionnage_date ON visionnage (id_profil, date_visionnage);

-- ---------- Vues ----------

-- Abonnement en cours de chaque utilisateur (aucune ligne si pas d'abonnement actif)
CREATE VIEW v_abonnement_courant AS
SELECT a.id, a.id_utilisateur, a.id_offre, a.date_debut, a.date_fin, a.prix_mensuel,
       o.nom AS offre, o.nb_profils_max, o.qualite_max
FROM abonnement a
JOIN offre o ON o.id = a.id_offre
WHERE date('now') BETWEEN a.date_debut AND a.date_fin;

-- Catalogue : une ligne par film ou série avec catégories, durée et nombre d'épisodes
CREATE VIEW v_catalogue AS
SELECT v.*,
       (SELECT group_concat(nom, ', ') FROM (
            SELECT c.nom FROM video_categorie vc JOIN categorie c ON c.id = vc.id_categorie
            WHERE vc.id_video = v.id ORDER BY c.nom)) AS categories,
       (SELECT group_concat(slug, ' ') FROM (
            SELECT c.slug FROM video_categorie vc JOIN categorie c ON c.id = vc.id_categorie
            WHERE vc.id_video = v.id ORDER BY c.slug)) AS slugs,
       CASE v.type WHEN 'film' THEN (SELECT duree FROM film WHERE id_video = v.id)
                   ELSE (SELECT COALESCE(SUM(duree), 0) FROM episode WHERE id_serie = v.id) END AS duree_minutes,
       (SELECT COUNT(DISTINCT numero_saison) FROM episode WHERE id_serie = v.id) AS nb_saisons,
       (SELECT COUNT(*) FROM episode WHERE id_serie = v.id) AS nb_episodes,
       (SELECT COUNT(DISTINCT vi.id_profil) FROM visionnage vi
         LEFT JOIN episode e ON e.id = vi.id_episode
         WHERE vi.id_film = v.id OR e.id_serie = v.id) AS nb_spectateurs
FROM video v;

-- ---------- Règles de gestion ----------

-- Le nombre de profils est limité par l'offre en cours (1 sans abonnement)
CREATE TRIGGER t_profils_nombre_max
BEFORE INSERT ON profils
BEGIN
    SELECT RAISE(ABORT, 'Le nombre maximum de profils pour cet abonnement est atteint.')
    WHERE (SELECT COUNT(*) FROM profils WHERE id_utilisateur = NEW.id_utilisateur)
          >= COALESCE((SELECT MAX(nb_profils_max) FROM v_abonnement_courant
                       WHERE id_utilisateur = NEW.id_utilisateur), 1);
END;

-- Deux abonnements d'un même utilisateur ne se chevauchent pas
CREATE TRIGGER t_abonnement_sans_chevauchement
BEFORE INSERT ON abonnement
BEGIN
    SELECT RAISE(ABORT, 'Cet abonnement chevauche un abonnement existant.')
    WHERE EXISTS (SELECT 1 FROM abonnement
                  WHERE id_utilisateur = NEW.id_utilisateur
                    AND date_debut <= NEW.date_fin AND date_fin >= NEW.date_debut);
END;

CREATE TRIGGER t_film_type
BEFORE INSERT ON film
BEGIN
    SELECT RAISE(ABORT, 'Cette vidéo n''est pas un film.')
    WHERE (SELECT type FROM video WHERE id = NEW.id_video) <> 'film';
END;

CREATE TRIGGER t_serie_type
BEFORE INSERT ON serie
BEGIN
    SELECT RAISE(ABORT, 'Cette vidéo n''est pas une série.')
    WHERE (SELECT type FROM video WHERE id = NEW.id_video) <> 'serie';
END;

CREATE TRIGGER t_video_type_fixe
BEFORE UPDATE OF type ON video
WHEN NEW.type <> OLD.type
BEGIN
    SELECT RAISE(ABORT, 'Le type d''une vidéo ne peut pas être modifié.');
END;

-- Un profil ne met pas en favori une vidéo interdite à son âge
CREATE TRIGGER t_favoris_age
BEFORE INSERT ON favoris
BEGIN
    SELECT RAISE(ABORT, 'Cette vidéo n''est pas autorisée pour l''âge de ce profil.')
    WHERE (SELECT age FROM profils WHERE id = NEW.id_profil)
          < (SELECT age_minimum FROM video WHERE id = NEW.id_video);
END;

-- Un profil ne regarde pas une vidéo interdite à son âge
CREATE TRIGGER t_visionnage_age
BEFORE INSERT ON visionnage
BEGIN
    SELECT RAISE(ABORT, 'Cette vidéo n''est pas autorisée pour l''âge de ce profil.')
    WHERE (SELECT age FROM profils WHERE id = NEW.id_profil)
          < (SELECT v.age_minimum FROM video v
             WHERE v.id = COALESCE(NEW.id_film, (SELECT id_serie FROM episode WHERE id = NEW.id_episode)));
END;
