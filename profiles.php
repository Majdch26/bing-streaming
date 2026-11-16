<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/layout.php';

$utilisateur = exiger_connexion();
$idUtilisateur = (int) $utilisateur['id'];
$abonnement = abonnement_courant($idUtilisateur);
$maxProfils = $abonnement ? (int) $abonnement['nb_profils_max'] : 1;

/* Lit et vérifie les champs d'un profil envoyés par un formulaire */
function lire_profil(): array
{
    $nom = trim((string) ($_POST['profile_name'] ?? ''));
    $age = trim((string) ($_POST['profile_age'] ?? ''));
    $avatar = (string) ($_POST['avatar'] ?? '');
    if ($nom === '' || mb_strlen($nom) > 30) {
        throw new InvalidArgumentException('The profile name must have between 1 and 30 characters.');
    }
    if ($age !== '' && (!ctype_digit($age) || (int) $age > 120)) {
        throw new InvalidArgumentException('Please enter an age between 0 and 120, or leave it empty.');
    }
    if (!in_array($avatar, AVATARS, true)) {
        $avatar = AVATARS[0];
    }
    return [$nom, $age === '' ? null : (int) $age, $avatar];
}

if (est_post()) {
    verifier_csrf();
    $idProfil = (int) ($_POST['profile_id'] ?? 0);
    try {
        if (isset($_POST['select_profile'])) {
            $existe = requete('SELECT 1 FROM profils WHERE id = ? AND id_utilisateur = ?', [$idProfil, $idUtilisateur])->fetchColumn();
            if ($existe) {
                $_SESSION['profile_id'] = $idProfil;
                rediriger('index.php');
            }
        } elseif (isset($_POST['create_profile'])) {
            [$nom, $age, $avatar] = lire_profil();
            requete('INSERT INTO profils (id_utilisateur, nom, age, avatar) VALUES (?, ?, ?, ?)',
                    [$idUtilisateur, $nom, $age, $avatar]);
            flash('success', "Profile \"$nom\" created.");
        } elseif (isset($_POST['update_profile'])) {
            [$nom, $age, $avatar] = lire_profil();
            requete('UPDATE profils SET nom = ?, age = ?, avatar = ? WHERE id = ? AND id_utilisateur = ?',
                    [$nom, $age, $avatar, $idProfil, $idUtilisateur]);
            flash('success', 'Profile updated.');
        } elseif (isset($_POST['delete_profile'])) {
            requete('DELETE FROM profils WHERE id = ? AND id_utilisateur = ?', [$idProfil, $idUtilisateur]);
            if ((int) ($_SESSION['profile_id'] ?? 0) === $idProfil) {
                unset($_SESSION['profile_id']);
            }
            flash('success', 'Profile deleted.');
        }
    } catch (Throwable $e) {
        flash('error', str_contains($e->getMessage(), 'UNIQUE')
            ? 'You already have a profile with this name.'
            : message_erreur($e));
    }
    rediriger('profiles.php');
}

$profils = requete('SELECT * FROM profils WHERE id_utilisateur = ? ORDER BY id', [$idUtilisateur])->fetchAll();
$peutAjouter = count($profils) < $maxProfils;

entete("Who's watching?", ['profiles.css']);
?>
    <div class="profiles-container">
        <img class="profiles-logo" src="<?= url('images/logo.png') ?>" alt="BING">
        <h1 class="profiles-title"><?= $profils ? "Who's watching?" : 'Create your first profile' ?></h1>

        <?php if (!$abonnement): ?>
            <p class="error-message">Your subscription has ended. <a href="<?= url('account.php#plan') ?>">Choose a plan</a> to keep watching.</p>
        <?php endif; ?>

        <div class="profiles-list">
            <?php foreach ($profils as $profil): ?>
                <form method="POST" class="profile-item">
                    <?= champ_csrf() ?>
                    <input type="hidden" name="profile_id" value="<?= (int) $profil['id'] ?>">
                    <button type="submit" name="select_profile" class="profile-select">
                        <div class="profile-avatar-container">
                            <img src="<?= url('images/avatars/' . $profil['avatar']) ?>" alt="" class="profile-avatar">
                        </div>
                        <div class="profile-name"><?= e($profil['nom']) ?></div>
                        <?php if ($profil['age'] !== null && $profil['age'] < 13): ?>
                            <div class="profile-kids">Kids</div>
                        <?php endif; ?>
                    </button>
                </form>
            <?php endforeach; ?>

            <?php if ($peutAjouter): ?>
                <button type="button" class="profile-item add-profile" onclick="showAddForm()">
                    <div class="add-icon"><i class="fas fa-plus"></i></div>
                    <div class="profile-name">Add Profile</div>
                </button>
            <?php endif; ?>
        </div>

        <p class="profiles-quota"><?= count($profils) ?> of <?= $maxProfils ?> profile<?= $maxProfils > 1 ? 's' : '' ?> used<?= $abonnement ? ' with the ' . e($abonnement['offre']) . ' plan' : '' ?></p>

        <?php if ($profils): ?>
            <button class="manage-button" onclick="showManageProfiles()">Manage Profiles</button>
        <?php endif; ?>
        <a class="profiles-account-link" href="<?= url('account.php') ?>"><i class="fas fa-user-cog"></i> Account</a>
    </div>

    <!-- Ajout d'un profil -->
    <div class="profile-form-container<?= !$profils && $peutAjouter ? ' active' : '' ?>" id="addProfileForm">
        <div class="profile-form">
            <button class="close-btn" onclick="hideAddForm()" aria-label="Close">&times;</button>
            <h3>Add Profile</h3>
            <form method="POST">
                <?= champ_csrf() ?>
                <div class="avatar-selection">
                    <?php foreach (AVATARS as $i => $avatar): ?>
                        <label class="avatar-option<?= $i === 0 ? ' selected' : '' ?>">
                            <input type="radio" name="avatar" value="<?= $avatar ?>"<?= $i === 0 ? ' checked' : '' ?> class="visually-hidden">
                            <img src="<?= url('images/avatars/' . $avatar) ?>" alt="Avatar <?= $i + 1 ?>">
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-group">
                    <label for="profile_name">Profile Name</label>
                    <input type="text" id="profile_name" name="profile_name" placeholder="Enter profile name" required maxlength="30">
                </div>

                <div class="form-group">
                    <label for="profile_age">Age (optional)</label>
                    <input type="number" id="profile_age" name="profile_age" placeholder="Enter age" min="0" max="120">
                    <span class="field-hint">Titles above this age rating are hidden from the profile.</span>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideAddForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="create_profile">Create Profile</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modification d'un profil -->
    <div class="profile-form-container" id="editProfileForm">
        <div class="profile-form">
            <button class="close-btn" onclick="hideEditForm()" aria-label="Close">&times;</button>
            <h3>Edit Profile</h3>
            <form method="POST" id="editForm">
                <?= champ_csrf() ?>
                <input type="hidden" name="profile_id" id="editProfileId">
                <div class="avatar-selection">
                    <?php foreach (AVATARS as $avatar): ?>
                        <label class="avatar-option">
                            <input type="radio" name="avatar" value="<?= $avatar ?>" class="visually-hidden">
                            <img src="<?= url('images/avatars/' . $avatar) ?>" alt="">
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-group">
                    <label for="edit_profile_name">Profile Name</label>
                    <input type="text" id="edit_profile_name" name="profile_name" required maxlength="30">
                </div>

                <div class="form-group">
                    <label for="edit_profile_age">Age (optional)</label>
                    <input type="number" id="edit_profile_age" name="profile_age" min="0" max="120">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Profile</button>
                    <button type="submit" class="btn btn-primary" name="update_profile">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Gestion des profils -->
    <div class="manage-profiles-container" id="manageProfiles">
        <div class="manage-profiles-content">
            <button class="close-btn" onclick="hideManageProfiles()" aria-label="Close">&times;</button>
            <h3 class="manage-profiles-title">Manage Profiles</h3>

            <div class="manage-profiles-list">
                <?php foreach ($profils as $profil): ?>
                    <div class="manage-profile-item">
                        <img src="<?= url('images/avatars/' . $profil['avatar']) ?>" alt="" class="manage-profile-avatar">
                        <div class="manage-profile-name"><?= e($profil['nom']) ?><?= $profil['age'] !== null ? ' <small>(' . (int) $profil['age'] . ')</small>' : '' ?></div>
                        <div class="profile-actions">
                            <button class="action-btn edit-btn"
                                    data-id="<?= (int) $profil['id'] ?>"
                                    data-name="<?= e($profil['nom']) ?>"
                                    data-age="<?= e((string) ($profil['age'] ?? '')) ?>"
                                    data-avatar="<?= e($profil['avatar']) ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn delete-btn" data-id="<?= (int) $profil['id'] ?>">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($peutAjouter): ?>
                <button class="btn btn-primary btn-block" onclick="hideManageProfiles(); showAddForm();">
                    <i class="fas fa-plus"></i> Add New Profile
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Confirmation de suppression -->
    <div class="delete-confirm-overlay" id="deleteConfirm">
        <div class="delete-confirm-box">
            <h3 class="delete-confirm-title">Delete Profile</h3>
            <p class="delete-confirm-message">Are you sure you want to delete this profile? Its list and viewing history will be deleted too.</p>
            <form method="POST" id="deleteForm">
                <?= champ_csrf() ?>
                <input type="hidden" name="profile_id" id="deleteProfileId">
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideDeleteConfirm()">Cancel</button>
                    <button type="submit" class="btn btn-danger" name="delete_profile">Delete</button>
                </div>
            </form>
        </div>
    </div>
<?php pied(['profiles.js']); ?>
