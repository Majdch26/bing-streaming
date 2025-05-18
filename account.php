<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* Get user details */
$stmt = $conn->prepare("SELECT u.*, a.nom as abonnement_nom, a.prix, a.nb_profile_max FROM utilisateur u JOIN abonnement a ON u.id_abonnement = a.id WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

/* Get all available plans */
$plans_stmt = $conn->query("SELECT * FROM abonnement ORDER BY prix ASC");
$plans = $plans_stmt->fetchAll();

/* Handle password change */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['mot_de_passe'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE utilisateur SET mot_de_passe = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);
            $password_success = "Password changed successfully!";
        } else {
            $password_error = "New passwords don't match.";
        }
    } else {
        $password_error = "Current password is incorrect.";
    }
}

/* Handle plan change confirmation */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_plan_change'])) {
    $new_plan_id = (int)$_POST['new_plan_id'];
    
    /* Get new plan details */
    $new_plan = null;
    foreach ($plans as $plan) {
        if ($plan['id'] == $new_plan_id) {
            $new_plan = $plan;
            break;
        }
    }
    
    if ($new_plan) {
        try {
            /* Check if downgrading (fewer profiles allowed) */
            if ($new_plan['nb_profile_max'] < $user['nb_profile_max']) {
                /* Get current profiles ordered by creation date (oldest first) */
                $profile_stmt = $conn->prepare("SELECT id FROM profils WHERE id_utilisateur = ? ORDER BY id ASC");
                $profile_stmt->execute([$_SESSION['user_id']]);
                $all_profiles = $profile_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $profiles_to_keep = $new_plan['nb_profile_max'];
                $profiles_to_delete = array_slice($all_profiles, $profiles_to_keep);
                
                if (!empty($profiles_to_delete)) {
                    /* Delete excess profiles (keeping newest ones) */
                    $placeholders = rtrim(str_repeat('?,', count($profiles_to_delete)), ',');
                    $delete_stmt = $conn->prepare("DELETE FROM profils WHERE id IN ($placeholders)");
                    $delete_stmt->execute($profiles_to_delete);
                }
            }
            
            /* Update the plan */
            $stmt = $conn->prepare("UPDATE utilisateur SET id_abonnement = ? WHERE id = ?");
            $stmt->execute([$new_plan_id, $_SESSION['user_id']]);
            
            /* Update session */
            $_SESSION['subscription_id'] = $new_plan_id;
            header("Location: account.php?success=1");
            exit();
        } catch (PDOException $e) {
            $error = "Plan change failed. Please try again.";
        }
    }
}

/* Handle plan change initiation */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_plan'])) {
    $new_plan_id = (int)$_POST['new_plan_id'];
    $current_plan_id = $user['id_abonnement'];
    
    /* Get both plans */
    $current_plan = null;
    $new_plan = null;
    foreach ($plans as $plan) {
        if ($plan['id'] == $current_plan_id) $current_plan = $plan;
        if ($plan['id'] == $new_plan_id) $new_plan = $plan;
        if ($current_plan && $new_plan) break;
    }
    
    if ($new_plan) {
        /* Check if upgrade (needs payment) */
        if ($new_plan['prix'] > $current_plan['prix']) {
            $difference = $new_plan['prix'] - $current_plan['prix'];
            $_SESSION['plan_change'] = [
                'current_plan_id' => $current_plan_id,
                'current_plan_price' => $current_plan['prix'],
                'current_plan_name' => $current_plan['nom'],
                'new_plan_id' => $new_plan_id,
                'new_plan_price' => $new_plan['prix'],
                'new_plan_name' => $new_plan['nom'],
                'amount_due' => $difference,
                'user_id' => $_SESSION['user_id']
            ];
            header("Location: payment.php?action=plan_upgrade");
            exit();
        } else {
            /* Downgrade or same price - check profile count */
            try {
                $profile_stmt = $conn->prepare("SELECT COUNT(*) as profile_count FROM profils WHERE id_utilisateur = ?");
                $profile_stmt->execute([$_SESSION['user_id']]);
                $profile_count = $profile_stmt->fetch()['profile_count'];
                
                if ($profile_count > $new_plan['nb_profile_max']) {
                    $_SESSION['pending_plan_change'] = [
                        'new_plan_id' => $new_plan_id,
                        'profiles_to_delete' => $profile_count - $new_plan['nb_profile_max']
                    ];
                    header("Location: account.php?confirm=1");
                    exit();
                } else {
                    /* No profile deletion needed, process immediately */
                    $stmt = $conn->prepare("UPDATE utilisateur SET id_abonnement = ? WHERE id = ?");
                    $stmt->execute([$new_plan_id, $_SESSION['user_id']]);
                    
                    /* Update session */
                    $_SESSION['subscription_id'] = $new_plan_id;
                    header("Location: account.php?success=1");
                    exit();
                }
            } catch (PDOException $e) {
                /* If profile table doesn't exist, just change the plan */
                $stmt = $conn->prepare("UPDATE utilisateur SET id_abonnement = ? WHERE id = ?");
                $stmt->execute([$new_plan_id, $_SESSION['user_id']]);
                $_SESSION['subscription_id'] = $new_plan_id;
                header("Location: account.php?success=1");
                exit();
            }
        }
    }
}

/* Get current profile count */
$profile_count = 0;
try {
    $profile_stmt = $conn->prepare("SELECT COUNT(*) as count FROM profils WHERE id_utilisateur = ?");
    $profile_stmt->execute([$_SESSION['user_id']]);
    $profile_count = $profile_stmt->fetch()['count'];
} catch (PDOException $e) {
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - BING</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/account.css">
</head>
<body>
    <?php include('navbar.php'); ?>
    
    <div class="account-container">
        <div class="account-header">
            <h1 class="account-title">Account Settings</h1>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">Your plan has been updated successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($password_error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($password_error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($password_success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($password_success); ?></div>
        <?php endif; ?>
        
        <div class="account-section">
            <h2 class="section-title">
                <i class="fas fa-id-card"></i> Membership & Billing
            </h2>
            <div class="account-details">
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-envelope"></i> Email
                    </div>
                    <div class="detail-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-lock"></i> Password
                    </div>
                    <div class="detail-value">••••••••</div>
                    <a href="#change-password" class="action-link">
                        <i class="fas fa-pencil-alt"></i> Change password
                    </a>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-crown"></i> Plan
                    </div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($user['abonnement_nom']); ?> ($<?php echo $user['prix']; ?>/month)
                    </div>
                    <a id="changePlanBtn" class="action-link">
                        <i class="fas fa-exchange-alt"></i> Change plan
                    </a>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-user-friends"></i> Profiles
                    </div>
                    <div class="detail-value">
                        <?php echo $profile_count . " of " . $user['nb_profile_max'] . " profile(s) used"; ?>
                    </div>
                    <a href="profiles.php" class="action-link">
                        <i class="fas fa-cog"></i> Manage profiles
                    </a>
                </div>
            </div>
        </div>
        
        <div class="account-section" id="change-password">
            <h2 class="section-title">
                <i class="fas fa-key"></i> Change Password
            </h2>
            <form method="POST">
                <div class="form-group password-container">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('current_password')"></i>
                </div>
                
                <div class="form-group password-container">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('new_password')"></i>
                </div>
                
                <div class="form-group password-container">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                </div>
                
                <button type="submit" name="change_password" class="submit-button">
                    <i class="fas fa-save"></i> Update Password
                </button>
            </form>
        </div>
    </div>
    
    <!-- Plan Change Modal -->
    <div class="modal-overlay" id="planModal">
        <div class="plan-modal">
            <div class="modal-header">
                <h2 class="modal-title">Change Your Subscription Plan</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST">
                <div class="plans-grid">
                    <?php foreach ($plans as $plan): ?>
                        <label class="plan-option <?php echo $plan['id'] == $user['id_abonnement'] ? 'selected' : ''; ?>">
                            <input type="radio" name="new_plan_id" value="<?php echo $plan['id']; ?>" 
                                   <?php echo $plan['id'] == $user['id_abonnement'] ? 'checked' : ''; ?> 
                                   style="display: none;">
                            <div class="plan-name">
                                <?php echo htmlspecialchars($plan['nom']); ?>
                                <span class="plan-price">$<?php echo $plan['prix']; ?>/month</span>
                            </div>
                            <?php if ($plan['id'] == $user['id_abonnement']): ?>
                                <span class="current-plan-marker">Current Plan</span>
                            <?php endif; ?>
                            <ul class="plan-features">
                                <li>Max <?php echo $plan['nb_profile_max']; ?> profiles</li>
                                <li>HD streaming available</li>
                                <li>Cancel anytime</li>
                                <?php if ($plan['prix'] > $user['prix']): ?>
                                    <li style="color: var(--secondary); font-weight: 500;">
                                        +$<?php echo $plan['prix'] - $user['prix']; ?> upgrade fee
                                    </li>
                                <?php elseif ($plan['prix'] < $user['prix']): ?>
                                    <li style="color: var(--primary);">
                                        No refunds for downgrades
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" name="change_plan" class="submit-button" style="width: 100%;">
                    <i class="fas fa-check-circle"></i> Continue with Plan Change
                </button>
            </form>
        </div>
    </div>
    
    <!-- Confirmation Modal (shown when URL has ?confirm=1) -->
    <?php if (isset($_GET['confirm']) && isset($_SESSION['pending_plan_change'])): 
        $new_plan_id = $_SESSION['pending_plan_change']['new_plan_id'];
        $profiles_to_delete = $_SESSION['pending_plan_change']['profiles_to_delete'];
        
        $new_plan = null;
        foreach ($plans as $plan) {
            if ($plan['id'] == $new_plan_id) {
                $new_plan = $plan;
                break;
            }
        }
        
        if ($new_plan): ?>
            <div class="confirmation-modal">
                <div class="confirmation-box">
                    <h3 class="confirmation-title">Confirm Plan Change</h3>
                    <p class="confirmation-message">
                        You're about to change to the <?php echo htmlspecialchars($new_plan['nom']); ?> plan.<br><br>
                        <strong>Warning:</strong> This will delete <?php echo $profiles_to_delete; ?> of your profiles 
                        (<?php echo $new_plan['nb_profile_max']; ?> profiles allowed in this plan).<br><br>
                        Are you sure you want to proceed?
                    </p>
                    <div class="confirmation-buttons">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="new_plan_id" value="<?php echo $new_plan_id; ?>">
                            <button type="submit" name="confirm_plan_change" class="confirmation-button confirm-btn">
                                Confirm Change
                            </button>
                        </form>
                        <a href="account.php" class="confirmation-button cancel-btn">Cancel</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <script src="/js/account.js"></script>
</body>
</html>