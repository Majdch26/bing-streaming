<?php
/*
 * Chargé en premier par chaque page : base de données, session,
 * protection CSRF, contrôle d'accès et petites fonctions utiles.
 */

declare(strict_types=1);

const RACINE = __DIR__ . '/..';
const AVATARS = ['avatar1.png', 'avatar2.png', 'avatar3.png', 'avatar4.png',
                 'avatar5.png', 'avatar6.png', 'avatar7.png', 'avatar8.png'];

/* ---------- Session ---------- */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

/* ---------- Base de données ---------- */

/* Connexion SQLite. La base est créée automatiquement au premier lancement
   à partir de database/schema.sql et database/demo.sql. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $chemin = getenv('BING_DB') ?: RACINE . '/data/bing.sqlite';
    $nouvelle = !file_exists($chemin);
    if ($nouvelle && !is_dir(dirname($chemin))) {
        mkdir(dirname($chemin), 0775, true);
    }

    $pdo = new PDO('sqlite:' . $chemin, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 3000');

    if ($nouvelle) {
        try {
            $pdo->exec(file_get_contents(RACINE . '/database/schema.sql'));
            $pdo->exec(file_get_contents(RACINE . '/database/demo.sql'));
        } catch (Throwable $e) {
            $pdo = null;
            @unlink($chemin);
            throw $e;
        }
    }
    return $pdo;
}

function requete(string $sql, array $parametres = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($parametres);
    return $stmt;
}

/* Message lisible pour une erreur levée par un déclencheur (RAISE) ou une contrainte */
function message_erreur(Throwable $e): string
{
    $message = $e->getMessage();
    if (preg_match('/SQLSTATE\[\w+\]: [^:]+: \d+ (.+)$/', $message, $m)) {
        $message = $m[1];
    }
    if (str_contains($message, 'UNIQUE constraint failed')) {
        return 'This entry already exists.';
    }
    if (str_contains($message, 'CHECK constraint failed') || str_contains($message, 'FOREIGN KEY')) {
        return 'Invalid data: ' . $message;
    }
    return $message;
}

/* ---------- Affichage ---------- */

function e(?string $texte): string
{
    return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8');
}

/* Chemin de base du site, pour qu'il fonctionne aussi dans un sous-dossier
   (par exemple http://localhost/bing/ avec XAMPP) */
function base(): string
{
    static $base = null;
    if ($base === null) {
        $racine = realpath(RACINE);
        $docroot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
        $base = ($docroot !== '' && str_starts_with($racine, $docroot))
            ? str_replace('\\', '/', substr($racine, strlen($docroot)))
            : '';
    }
    return rtrim($base, '/');
}

function url(string $chemin = ''): string
{
    if (preg_match('#^https?://#', $chemin)) {
        return $chemin;
    }
    return base() . '/' . ltrim($chemin, '/');
}

function rediriger(string $chemin): never
{
    header('Location: ' . url($chemin));
    exit;
}

function duree(int $minutes): string
{
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $h > 0 ? "{$h}h " . str_pad((string) $m, 2, '0', STR_PAD_LEFT) . 'm' : "{$m}m";
}

function annee(?string $date): string
{
    return $date ? substr($date, 0, 4) : '';
}

function age_libelle(int $age): string
{
    return $age === 0 ? 'All' : $age . '+';
}

/* Messages affichés une fois après une redirection */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function messages_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- Protection CSRF ---------- */

function jeton_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function champ_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(jeton_csrf()) . '">';
}

function verifier_csrf(): void
{
    $jeton = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($jeton) || !hash_equals(jeton_csrf(), $jeton)) {
        http_response_code(403);
        exit('Invalid security token. Please reload the page and try again.');
    }
}

function est_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/* ---------- Utilisateur, abonnement, profil ---------- */

function utilisateur_courant(): ?array
{
    static $utilisateur = false;
    if ($utilisateur === false) {
        $utilisateur = null;
        if (isset($_SESSION['user_id'])) {
            $utilisateur = requete('SELECT id, nom, email, est_admin FROM utilisateur WHERE id = ?',
                                   [$_SESSION['user_id']])->fetch() ?: null;
            if ($utilisateur === null) {
                unset($_SESSION['user_id'], $_SESSION['profile_id']);
            }
        }
    }
    return $utilisateur;
}

function abonnement_courant(int $idUtilisateur): ?array
{
    return requete('SELECT * FROM v_abonnement_courant WHERE id_utilisateur = ?', [$idUtilisateur])->fetch() ?: null;
}

function exiger_connexion(): array
{
    $utilisateur = utilisateur_courant();
    if ($utilisateur === null) {
        rediriger('login.php');
    }
    return $utilisateur;
}

function exiger_admin(): array
{
    $utilisateur = exiger_connexion();
    if (!$utilisateur['est_admin']) {
        http_response_code(403);
        exit('Access denied.');
    }
    return $utilisateur;
}

/* Pages du catalogue : il faut être connecté, avoir un abonnement en cours
   et avoir choisi un de ses profils */
function exiger_profil(): array
{
    $utilisateur = exiger_connexion();
    if (abonnement_courant((int) $utilisateur['id']) === null) {
        flash('error', 'Your subscription has ended. Choose a plan to keep watching.');
        rediriger('account.php#plan');
    }

    $profil = null;
    if (isset($_SESSION['profile_id'])) {
        $profil = requete('SELECT * FROM profils WHERE id = ? AND id_utilisateur = ?',
                          [$_SESSION['profile_id'], $utilisateur['id']])->fetch() ?: null;
    }
    if ($profil === null) {
        unset($_SESSION['profile_id']);
        rediriger('profiles.php');
    }
    return $profil;
}

/* Condition SQL qui garde seulement les vidéos autorisées à l'âge du profil */
function filtre_age(array $profil, string $alias = 'v'): string
{
    return $profil['age'] === null ? '1 = 1' : "{$alias}.age_minimum <= " . (int) $profil['age'];
}

function ids_favoris(int $idProfil): array
{
    return array_map('intval', requete('SELECT id_video FROM favoris WHERE id_profil = ?', [$idProfil])
        ->fetchAll(PDO::FETCH_COLUMN));
}

/* Remplace l'abonnement en cours par une nouvelle offre à partir d'aujourd'hui */
function souscrire(int $idUtilisateur, int $idOffre, int $nbMois = 1): void
{
    $offre = requete('SELECT * FROM offre WHERE id = ? AND disponible = 1', [$idOffre])->fetch();
    if (!$offre) {
        throw new RuntimeException('This plan is not available.');
    }
    $pdo = db();
    $demarre = !$pdo->inTransaction();
    if ($demarre) {
        $pdo->beginTransaction();
    }
    try {
        // L'abonnement en cours (ou futur) s'arrête hier ; s'il commençait aujourd'hui, il est remplacé
        requete("DELETE FROM abonnement WHERE id_utilisateur = ? AND date_debut >= date('now')", [$idUtilisateur]);
        requete("UPDATE abonnement SET date_fin = date('now', '-1 day')
                 WHERE id_utilisateur = ? AND date_fin >= date('now')", [$idUtilisateur]);
        requete("INSERT INTO abonnement (id_utilisateur, id_offre, date_debut, date_fin, prix_mensuel)
                 VALUES (?, ?, date('now'), date('now', ?, '-1 day'), ?)",
                [$idUtilisateur, $idOffre, "+{$nbMois} months", $offre['prix_mensuel']]);
        if ($demarre) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($demarre) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/* Réponse JSON pour les appels en JavaScript */
function json(array $donnees, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}
