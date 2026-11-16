<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$utilisateur = exiger_connexion();
$idUtilisateur = (int) $utilisateur['id'];
$abonnement = abonnement_courant($idUtilisateur);
$offres = requete('SELECT * FROM offre WHERE disponible = 1 ORDER BY prix_mensuel')->fetchAll();

/* Profils qui seront supprimés si l'offre autorise moins de profils :
   on garde le profil en cours, puis les plus anciens */
function profils_en_trop(int $idUtilisateur, int $max): array
{
    $profils = requete('SELECT id, nom FROM profils WHERE id_utilisateur = ?
                        ORDER BY (id = ?) DESC, id', [$idUtilisateur, (int) ($_SESSION['profile_id'] ?? 0)])->fetchAll();
    return array_slice($profils, $max);
}

/* Montant à payer tout de suite pour passer à une offre plus chère :
   la différence de prix, au prorata des jours restants */
function montant_changement(array $abonnement, array $offre): float
{
    $debut = new DateTimeImmutable($abonnement['date_debut']);
    $fin = new DateTimeImmutable($abonnement['date_fin']);
    $joursPeriode = max(1, $debut->diff($fin)->days + 1);
    $joursRestants = max(1, (new DateTimeImmutable('today'))->diff($fin)->days + 1);
    $difference = max(0, $offre['prix_mensuel'] - $abonnement['prix_mensuel']);
    // Prorata sur un mois : la différence s'applique aux jours restants du mois en cours
    $joursMois = min($joursPeriode, 31);
    return max(0.01, round($difference * min($joursRestants, $joursMois) / $joursMois, 2));
}

if (est_post()) {
    verifier_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'password') {
        $actuel = (string) ($_POST['current_password'] ?? '');
        $nouveau = (string) ($_POST['new_password'] ?? '');
        $hash = requete('SELECT mot_de_passe_hash FROM utilisateur WHERE id = ?', [$idUtilisateur])->fetchColumn();
        if (!password_verify($actuel, $hash)) {
            flash('error', 'Your current password is incorrect.');
        } elseif (strlen($nouveau) < 8) {
            flash('error', 'Your new password must contain at least 8 characters.');
        } elseif ($nouveau !== ($_POST['confirm_password'] ?? '')) {
            flash('error', "The new passwords don't match.");
        } elseif ($nouveau === $actuel) {
            flash('error', 'The new password must be different from the current one.');
        } else {
            requete('UPDATE utilisateur SET mot_de_passe_hash = ? WHERE id = ?', [password_hash($nouveau, PASSWORD_DEFAULT), $idUtilisateur]);
            session_regenerate_id(true);
            flash('success', 'Password changed successfully.');
        }
        rediriger('account.php#change-password');
    }

    if ($action === 'change_plan' || $action === 'confirm_downgrade') {
        $offre = requete('SELECT * FROM offre WHERE id = ? AND disponible = 1', [(int) ($_POST['new_plan_id'] ?? 0)])->fetch();
        if (!$offre) {
            flash('error', 'Please choose an available plan.');
            rediriger('account.php#plan');
        }

        if (!$abonnement) {
            // Pas d'abonnement en cours : on paie le premier mois de la nouvelle offre
            $_SESSION['paiement'] = ['type' => 'renew', 'offre' => (int) $offre['id'], 'montant' => (float) $offre['prix_mensuel']];
            rediriger('payment.php');
        }
        if ((int) $offre['id'] === (int) $abonnement['id_offre']) {
            flash('success', 'You are already on this plan.');
            rediriger('account.php');
        }
        if ($offre['prix_mensuel'] > $abonnement['prix_mensuel']) {
            $_SESSION['paiement'] = ['type' => 'change', 'offre' => (int) $offre['id'],
                                     'montant' => montant_changement($abonnement, $offre)];
            rediriger('payment.php');
        }

        // Offre moins chère : pas de paiement, mais il faut peut-être supprimer des profils
        $enTrop = profils_en_trop($idUtilisateur, (int) $offre['nb_profils_max']);
        if ($enTrop && $action !== 'confirm_downgrade') {
            $_SESSION['changement_en_attente'] = (int) $offre['id'];
            rediriger('account.php?confirm=1#plan');
        }
        try {
            db()->beginTransaction();
            foreach ($enTrop as $profil) {
                requete('DELETE FROM profils WHERE id = ? AND id_utilisateur = ?', [$profil['id'], $idUtilisateur]);
            }
            souscrire($idUtilisateur, (int) $offre['id']);
            db()->commit();
            unset($_SESSION['changement_en_attente']);
            flash('success', 'Your plan has been changed to ' . $offre['nom'] . '.');
        } catch (Throwable $e) {
            db()->rollBack();
            flash('error', message_erreur($e));
        }
        rediriger('account.php');
    }

    if ($action === 'delete_account') {
        $hash = requete('SELECT mot_de_passe_hash FROM utilisateur WHERE id = ?', [$idUtilisateur])->fetchColumn();
        if ($utilisateur['est_admin']) {
            flash('error', 'Administrator accounts cannot be deleted from this page.');
        } elseif (!password_verify((string) ($_POST['password'] ?? ''), $hash)) {
            flash('error', 'Incorrect password: your account was not deleted.');
        } else {
            requete('DELETE FROM utilisateur WHERE id = ?', [$idUtilisateur]);
            $_SESSION = [];
            session_regenerate_id(true);
            flash('success', 'Your account and all its data have been deleted.');
            rediriger('login.php');
        }
        rediriger('account.php#danger');
    }
    rediriger('account.php');
}

$compte = requete('SELECT * FROM utilisateur WHERE id = ?', [$idUtilisateur])->fetch();
$nbProfils = (int) requete('SELECT COUNT(*) FROM profils WHERE id_utilisateur = ?', [$idUtilisateur])->fetchColumn();
$historiqueAbonnements = requete('SELECT a.*, o.nom FROM abonnement a JOIN offre o ON o.id = a.id_offre
                                  WHERE a.id_utilisateur = ? ORDER BY a.date_debut DESC LIMIT 6', [$idUtilisateur])->fetchAll();
$activite = isset($_SESSION['profile_id']) ? requete(
    "SELECT vi.*, COALESCE(vf.titre, vs.titre) AS titre, e.numero_saison, e.numero_episode,
            COALESCE(vi.id_film, e.id_serie) AS id_video
     FROM visionnage vi
     LEFT JOIN video vf ON vf.id = vi.id_film
     LEFT JOIN episode e ON e.id = vi.id_episode
     LEFT JOIN video vs ON vs.id = e.id_serie
     JOIN profils p ON p.id = vi.id_profil
     WHERE vi.id_profil = ? AND p.id_utilisateur = ?
     ORDER BY vi.date_visionnage DESC LIMIT 8",
    [$_SESSION['profile_id'], $idUtilisateur])->fetchAll() : [];

$enAttente = null;
if (isset($_GET['confirm'], $_SESSION['changement_en_attente'])) {
    $enAttente = requete('SELECT * FROM offre WHERE id = ?', [$_SESSION['changement_en_attente']])->fetch();
}

$profil = isset($_SESSION['profile_id'])
    ? requete('SELECT * FROM profils WHERE id = ? AND id_utilisateur = ?', [$_SESSION['profile_id'], $idUtilisateur])->fetch()
    : null;
$profil = $profil ?: ['nom' => $utilisateur['nom'], 'avatar' => 'avatar1.png'];

entete('Account', ['navbar.css', 'account.css']);
include __DIR__ . '/includes/navbar.php';
?>
    <div class="account-container">
        <div class="account-header">
            <h1 class="account-title">Account</h1>
        </div>

        <div class="account-section" id="plan">
            <h2 class="section-title"><i class="fas fa-id-card"></i> Membership &amp; Billing</h2>
            <?php if (!$abonnement): ?>
                <div class="error-message">You have no active subscription. Choose a plan to start watching again.</div>
            <?php endif; ?>
            <div class="account-details">
                <div class="detail-card">
                    <div class="detail-label"><i class="fas fa-envelope"></i> Email</div>
                    <div class="detail-value"><?= e($compte['email']) ?></div>
                    <div class="detail-sub">Member since <?= e(date('F Y', strtotime($compte['date_creation']))) ?></div>
                </div>

                <div class="detail-card">
                    <div class="detail-label"><i class="fas fa-lock"></i> Password</div>
                    <div class="detail-value">••••••••</div>
                    <a href="#change-password" class="action-link"><i class="fas fa-pencil-alt"></i> Change password</a>
                </div>

                <div class="detail-card">
                    <div class="detail-label"><i class="fas fa-crown"></i> Plan</div>
                    <?php if ($abonnement): ?>
                        <div class="detail-value"><?= e($abonnement['offre']) ?> ($<?= number_format((float) $abonnement['prix_mensuel'], 2) ?>/month)</div>
                        <div class="detail-sub">Paid until <?= e(date('F j, Y', strtotime($abonnement['date_fin']))) ?></div>
                    <?php else: ?>
                        <div class="detail-value">No active plan</div>
                    <?php endif; ?>
                    <button type="button" id="changePlanBtn" class="action-link"><i class="fas fa-exchange-alt"></i> <?= $abonnement ? 'Change plan' : 'Choose a plan' ?></button>
                </div>

                <div class="detail-card">
                    <div class="detail-label"><i class="fas fa-user-friends"></i> Profiles</div>
                    <div class="detail-value"><?= $nbProfils ?> of <?= $abonnement ? (int) $abonnement['nb_profils_max'] : 1 ?> used</div>
                    <a href="<?= url('profiles.php') ?>" class="action-link"><i class="fas fa-cog"></i> Manage profiles</a>
                </div>
            </div>

            <?php if ($historiqueAbonnements): ?>
            <h3 class="sub-title">Billing history</h3>
            <table class="history-table">
                <thead><tr><th>Plan</th><th>From</th><th>To</th><th>Price</th></tr></thead>
                <tbody>
                <?php foreach ($historiqueAbonnements as $ligne): ?>
                    <tr>
                        <td><?= e($ligne['nom']) ?></td>
                        <td><?= e($ligne['date_debut']) ?></td>
                        <td><?= e($ligne['date_fin']) ?></td>
                        <td>$<?= number_format((float) $ligne['prix_mensuel'], 2) ?>/month</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <?php if ($activite): ?>
        <div class="account-section">
            <h2 class="section-title"><i class="fas fa-history"></i> Recently watched by <?= e($profil['nom']) ?></h2>
            <table class="history-table">
                <thead><tr><th>Title</th><th>Date</th><th>Progress</th></tr></thead>
                <tbody>
                <?php foreach ($activite as $ligne):
                    $pourcent = $ligne['duree_secondes'] > 0 ? min(100, (int) round(100 * $ligne['position_secondes'] / $ligne['duree_secondes'])) : 0; ?>
                    <tr>
                        <td>
                            <a href="<?= url($ligne['id_film'] ? 'watch.php?movie=' . $ligne['id_film'] : 'watch.php?episode=' . $ligne['id_episode']) ?>"><?= e($ligne['titre']) ?></a>
                            <?php if ($ligne['id_episode']): ?><span class="muted">S<?= (int) $ligne['numero_saison'] ?> E<?= (int) $ligne['numero_episode'] ?></span><?php endif; ?>
                        </td>
                        <td><?= e(date('M j, Y', strtotime($ligne['date_visionnage']))) ?></td>
                        <td><span class="mini-progress"><span style="width: <?= $pourcent ?>%"></span></span> <?= $pourcent ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="account-section" id="change-password">
            <h2 class="section-title"><i class="fas fa-key"></i> Change Password</h2>
            <form method="POST">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="password">
                <div class="form-group password-container">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    <i class="fas fa-eye toggle-password" data-target="current_password"></i>
                </div>
                <div class="form-group password-container">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                    <i class="fas fa-eye toggle-password" data-target="new_password"></i>
                </div>
                <div class="form-group password-container">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                    <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                </div>
                <button type="submit" class="submit-button"><i class="fas fa-save"></i> Update Password</button>
            </form>
        </div>

        <?php if (!$utilisateur['est_admin']): ?>
        <div class="account-section danger-zone" id="danger">
            <h2 class="section-title"><i class="fas fa-exclamation-triangle"></i> Delete Account</h2>
            <p class="muted">Your profiles, lists, viewing history and subscriptions will be permanently deleted.</p>
            <form method="POST" class="delete-account-form">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="delete_account">
                <div class="form-group">
                    <label for="delete_password">Confirm with your password</label>
                    <input type="password" id="delete_password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="submit-button danger-button"><i class="fas fa-trash"></i> Delete my account</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Choix d'une offre -->
    <div class="modal-overlay<?= !$abonnement && !$enAttente ? ' active' : '' ?>" id="planModal">
        <div class="plan-modal" role="dialog" aria-labelledby="planModalTitle">
            <div class="modal-header">
                <h2 class="modal-title" id="planModalTitle"><?= $abonnement ? 'Change Your Plan' : 'Choose a Plan' ?></h2>
                <button class="close-modal" type="button" aria-label="Close">&times;</button>
            </div>
            <form method="POST">
                <?= champ_csrf() ?>
                <input type="hidden" name="action" value="change_plan">
                <div class="plans-grid">
                    <?php foreach ($offres as $offre):
                        $actuelle = $abonnement && (int) $offre['id'] === (int) $abonnement['id_offre']; ?>
                        <label class="plan-option<?= $actuelle ? ' selected' : '' ?>">
                            <input type="radio" name="new_plan_id" value="<?= (int) $offre['id'] ?>"<?= $actuelle ? ' checked' : '' ?> class="visually-hidden" required>
                            <div class="plan-name">
                                <?= e($offre['nom']) ?>
                                <span class="plan-price">$<?= number_format((float) $offre['prix_mensuel'], 2) ?>/month</span>
                            </div>
                            <?php if ($actuelle): ?><span class="current-plan-marker">Current Plan</span><?php endif; ?>
                            <ul class="plan-features">
                                <?php foreach (array_filter(explode("\n", $offre['avantages'])) as $avantage): ?>
                                    <li><?= e(trim($avantage)) ?></li>
                                <?php endforeach; ?>
                                <?php if ($abonnement && !$actuelle): ?>
                                    <?php if ($offre['prix_mensuel'] > $abonnement['prix_mensuel']): ?>
                                        <li class="plan-note">Pay $<?= number_format(montant_changement($abonnement, $offre), 2) ?> today (prorated)</li>
                                    <?php else: ?>
                                        <li class="plan-note">No payment today</li>
                                    <?php endif; ?>
                                <?php elseif (!$abonnement): ?>
                                    <li class="plan-note">Pay $<?= number_format((float) $offre['prix_mensuel'], 2) ?> today</li>
                                <?php endif; ?>
                            </ul>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="submit-button btn-full"><i class="fas fa-check-circle"></i> Continue</button>
            </form>
        </div>
    </div>

    <?php if ($enAttente):
        $enTrop = profils_en_trop($idUtilisateur, (int) $enAttente['nb_profils_max']); ?>
        <div class="confirmation-modal">
            <div class="confirmation-box" role="alertdialog">
                <h3 class="confirmation-title">Confirm Plan Change</h3>
                <p class="confirmation-message">
                    The <?= e($enAttente['nom']) ?> plan allows <?= (int) $enAttente['nb_profils_max'] ?> profile<?= $enAttente['nb_profils_max'] > 1 ? 's' : '' ?>.
                    These profiles will be <strong>deleted</strong> with their lists and history:
                </p>
                <ul class="profiles-to-delete">
                    <?php foreach ($enTrop as $p): ?><li><?= e($p['nom']) ?></li><?php endforeach; ?>
                </ul>
                <div class="confirmation-buttons">
                    <form method="POST">
                        <?= champ_csrf() ?>
                        <input type="hidden" name="action" value="confirm_downgrade">
                        <input type="hidden" name="new_plan_id" value="<?= (int) $enAttente['id'] ?>">
                        <button type="submit" class="confirmation-button confirm-btn">Confirm Change</button>
                    </form>
                    <a href="<?= url('account.php') ?>" class="confirmation-button cancel-btn">Cancel</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php pied(['forms.js', 'account.js']); ?>
