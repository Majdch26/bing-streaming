<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* Available avatars (add more as needed) */
$availableAvatars = [
    'avatar1.png',
    'avatar2.png',
    'avatar3.png',
    'avatar4.png',
    'avatar5.png',
    'avatar6.png',
    'avatar7.png',
    'avatar8.png'
];

/* Get user's profiles */
$stmt = $conn->prepare("SELECT * FROM profils WHERE id_utilisateur = ?");
$stmt->execute([$_SESSION['user_id']]);
$profiles = $stmt->fetchAll();

/* Get subscription details */
$stmt = $conn->prepare("SELECT a.* FROM abonnement a JOIN utilisateur u ON a.id = u.id_abonnement WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$subscription = $stmt->fetch();

/* Handle profile creation */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_profile'])) {
    $profileName = trim($_POST['profile_name']);
    $profileAge = (int)$_POST['profile_age'];
    $avatar = $_POST['avatar'] ?? 'default1.png';
    
    /* Check if user can create more profiles */
    if (count($profiles) < $subscription['nb_profile_max']) {
        $stmt = $conn->prepare("INSERT INTO profils (id_utilisateur, nom, age, avatar) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $profileName, $profileAge, $avatar]);
        header("Location: profiles.php");
        exit();
    } else {
        $profile_error = "You've reached the maximum number of profiles for your subscription.";
    }
}

/* Handle profile selection */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_profile'])) {
    $_SESSION['profile_id'] = $_POST['profile_id'];
    $_SESSION['profile_name'] = $_POST['profile_name'];
    header("Location: home.php");
    exit();
}

/* Handle profile deletion */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_profile'])) {
    $profileId = $_POST['profile_id'];
    $stmt = $conn->prepare("DELETE FROM profils WHERE id = ? AND id_utilisateur = ?");
    $stmt->execute([$profileId, $_SESSION['user_id']]);
    header("Location: profiles.php");
    exit();
}

/* Handle profile update */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $profileId = $_POST['profile_id'];
    $profileName = trim($_POST['profile_name']);
    $avatar = $_POST['avatar'];
    
    $stmt = $conn->prepare("UPDATE profils SET nom = ?, avatar = ? WHERE id = ? AND id_utilisateur = ?");
    $stmt->execute([$profileName, $avatar, $profileId, $_SESSION['user_id']]);
    header("Location: profiles.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Who's Watching?</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/profiles.css">
</head>
<body>
    <div class="profiles-container">
        <h1 class="profiles-title">Who's watching?</h1>
        
        <div class="profiles-list">
            <?php foreach ($profiles as $profile): ?>
                <form method="POST" class="profile-item">
                    <input type="hidden" name="profile_id" value="<?= $profile['id'] ?>">
                    <input type="hidden" name="profile_name" value="<?= htmlspecialchars($profile['nom']) ?>">
                    <button type="submit" name="select_profile" style="background: none; border: none; cursor: pointer; padding: 0;">
                        <div class="profile-avatar-container">
                            <img src="images/avatars/<?= $profile['avatar'] ?>" alt="<?= htmlspecialchars($profile['nom']) ?>" class="profile-avatar">
                        </div>
                        <div class="profile-name"><?= htmlspecialchars($profile['nom']) ?></div>
                    </button>
                </form>
            <?php endforeach; ?>
            
            <?php if (count($profiles) < $subscription['nb_profile_max']): ?>
                <div class="profile-item add-profile" onclick="showAddForm()">
                    <div class="add-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="profile-name">Add Profile</div>
                </div>
            <?php endif; ?>
        </div>
        
        <button class="manage-button" onclick="showManageProfiles()">Manage Profiles</button>
    </div>
    
    <!-- Add Profile Form -->
    <div class="profile-form-container" id="addProfileForm">
        <div class="profile-form">
            <button class="close-btn" onclick="hideAddForm()">&times;</button>
            <h3>Add Profile</h3>
            <?php if (isset($profile_error)): ?>
                <p class="error-message"><?= $profile_error ?></p>
            <?php endif; ?>
            <form method="POST">
                <div class="avatar-selection">
                    <?php foreach ($availableAvatars as $index => $avatar): ?>
                        <label class="avatar-option <?= $index === 0 ? 'selected' : '' ?>">
                            <input type="radio" name="avatar" value="<?= $avatar ?>" <?= $index === 0 ? 'checked' : '' ?> style="display: none;">
                            <img src="images/avatars/<?= $avatar ?>" alt="Avatar <?= $index + 1 ?>">
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-group">
                    <label for="profile_name">Profile Name</label>
                    <input type="text" id="profile_name" name="profile_name" placeholder="Enter profile name" required>
                </div>
                
                <div class="form-group">
                    <label for="profile_age">Age (optional)</label>
                    <input type="number" id="profile_age" name="profile_age" placeholder="Enter age" min="0">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideAddForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="create_profile">Create Profile</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Profile Form -->
    <div class="profile-form-container" id="editProfileForm">
        <div class="profile-form">
            <button class="close-btn" onclick="hideEditForm()">&times;</button>
            <h3>Edit Profile</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="profile_id" id="editProfileId">
                
                <div class="avatar-selection">
                    <?php foreach ($availableAvatars as $avatar): ?>
                        <label class="avatar-option">
                            <input type="radio" name="avatar" value="<?= $avatar ?>" style="display: none;">
                            <img src="images/avatars/<?= $avatar ?>" alt="Avatar">
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-group">
                    <label for="edit_profile_name">Profile Name</label>
                    <input type="text" id="edit_profile_name" name="profile_name" placeholder="Enter profile name" required>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Profile</button>
                    <button type="submit" class="btn btn-primary" name="update_profile">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Manage Profiles -->
    <div class="manage-profiles-container" id="manageProfiles">
        <div class="manage-profiles-content">
            <button class="close-btn" onclick="hideManageProfiles()">&times;</button>
            <h3 class="manage-profiles-title">Manage Profiles</h3>
            
            <div class="manage-profiles-list">
                <?php foreach ($profiles as $profile): ?>
                    <div class="manage-profile-item">
                        <img src="images/avatars/<?= $profile['avatar'] ?>" alt="<?= htmlspecialchars($profile['nom']) ?>" class="manage-profile-avatar">
                        <div class="manage-profile-name"><?= htmlspecialchars($profile['nom']) ?></div>
                        <div class="profile-actions">
                            <button class="action-btn edit-btn" onclick="showEditForm(<?= $profile['id'] ?>, '<?= htmlspecialchars($profile['nom']) ?>', '<?= $profile['avatar'] ?>')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn delete-btn" onclick="showDeleteConfirm(<?= $profile['id'] ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($profiles) < $subscription['nb_profile_max']): ?>
                <button class="btn btn-primary" style="width: 100%;" onclick="hideManageProfiles(); showAddForm();">
                    <i class="fas fa-plus"></i> Add New Profile
                </button>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Delete Confirmation -->
    <div class="delete-confirm-overlay" id="deleteConfirm">
        <div class="delete-confirm-box">
            <h3 class="delete-confirm-title">Delete Profile</h3>
            <p class="delete-confirm-message">Are you sure you want to delete this profile? This action cannot be undone.</p>
            
            <form method="POST" id="deleteForm">
                <input type="hidden" name="profile_id" id="deleteProfileId">
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="hideDeleteConfirm()">Cancel</button>
                    <button type="submit" class="btn btn-danger" name="delete_profile">Delete</button>
                </div>
            </form>
        </div>
    </div>
    <script src="/js/profiles.js"></script>
</body>
</html>