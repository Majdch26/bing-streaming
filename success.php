<?php
session_start();
require 'db.php';

/* Verify payment was just completed */
if (!isset($_SESSION['just_completed_payment'])) {
    header("Location: signup.php");
    exit();
}

/* Clear the payment flag */
unset($_SESSION['just_completed_payment']);

/* Get user info */
$userName = $_SESSION['user_name'] ?? 'User';
$planId = $_SESSION['subscription_id'] ?? 0;
$isUpgrade = isset($_GET['upgrade']) && $_GET['upgrade'] == '1';

/* Get plan details */
$planNames = array(
    1 => 'Basic',
    2 => 'Standard', 
    3 => 'Premium'
);
$planName = $planNames[$planId] ?? 'Selected Plan';

/* Get plan price */
$stmt = $conn->prepare("SELECT prix FROM abonnement WHERE id = ?");
$stmt->execute([$planId]);
$planPrice = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isUpgrade ? 'Plan Upgraded' : 'Welcome'; ?> - BING Streaming</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/success.css">
</head>
<body>
    <div class="success-container">
        <div class="progress-steps">
            <div class="progress-step">
                <div class="step-number completed">
                    <i class="fas fa-check"></i>
                </div>
                <div class="step-label completed">Account</div>
            </div>
            <div class="progress-step">
                <div class="step-number completed">
                    <i class="fas fa-check"></i>
                </div>
                <div class="step-label completed">Payment</div>
            </div>
            <div class="progress-step">
                <div class="step-number completed">
                    <i class="fas fa-check"></i>
                </div>
                <div class="step-label completed">Complete</div>
            </div>
        </div>
        
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h1 class="success-title">
                <?php echo $isUpgrade ? 'Plan Upgrade Complete!' : 'Account Created Successfully!'; ?>
            </h1>
            
            <p class="success-message">
                <?php if ($isUpgrade): ?>
                    You're now subscribed to <?php echo htmlspecialchars($planName); ?> Plan ($<?php echo $planPrice; ?>/month).<br>
                    The changes will take effect immediately.
                <?php else: ?>
                    Welcome to BING Streaming, <?php echo htmlspecialchars($userName); ?>!<br>
                    Your <?php echo htmlspecialchars($planName); ?> Plan ($<?php echo $planPrice; ?>/month) is now active.
                <?php endif; ?>
            </p>
            
            <div class="user-info">
                <div class="info-row">
                    <span class="info-label">Account:</span>
                    <span class="info-value"><?php echo htmlspecialchars($userName); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Plan:</span>
                    <span class="info-value">
                        <span class="plan-badge"><?php echo htmlspecialchars($planName); ?></span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Billing:</span>
                    <span class="info-value">$<?php echo $planPrice; ?> per month</span>
                </div>
            </div>
            
            <div class="next-steps">
                <h3>Next Steps:</h3>
                <ul>
                    <li>Create your viewing profiles</li>
                    <li>Start watching</li>
                </ul>
            </div>
            
            <!--<div class="app-download">
                <a href="#"><img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store"></a>
                <a href="#"><img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play"></a>
            </div>-->
            
            <a href="profiles.php" class="continue-button">
                <?php echo $isUpgrade ? 'Back to Account' : 'Create Profiles'; ?>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <script>
        /* Add animation when page loads */
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll(
                '.success-icon, .success-title, .success-message, ' +
                '.user-info, .next-steps, .app-download, .continue-button'
            );
            elements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.15}s`;
            });
        });
    </script>
</body>
</html>