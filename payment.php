<?php
session_start();
require 'db.php';

/* Check if this is a plan upgrade request */
if (isset($_GET['action']) && $_GET['action'] == 'plan_upgrade') {
    /* Verify user is logged in and has plan change data */
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['plan_change'])) {
        header("Location: account.php");
        exit();
    }
} 
/* Otherwise check if this is a new signup */
elseif ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['nom'], $_POST['email'], $_POST['mot_de_passe'], $_POST['id_abonnement'])) {
    header("Location: signup.php");
    exit();
}

/* Process payment when form is submitted */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card'])) {
    /* Handle plan upgrade payment */
    if (isset($_GET['action']) && $_GET['action'] == 'plan_upgrade') {
        /* Basic card validation (in real app, use payment processor) */
        $card = str_replace(' ', '', $_POST['card']);
        $expiry = $_POST['expiry'];
        $cvv = $_POST['cvv'];

        if (strlen($card) < 15 || strlen($cvv) < 3) {
            $error = "Invalid card details. Please check your payment information.";
        } else {
            try {
                /* Update user's plan */
                $stmt = $conn->prepare("UPDATE utilisateur SET id_abonnement = ? WHERE id = ?");
                $stmt->execute([$_SESSION['plan_change']['new_plan_id'], $_SESSION['user_id']]);
                
                /* Update session */
                $_SESSION['subscription_id'] = $_SESSION['plan_change']['new_plan_id'];
                unset($_SESSION['plan_change']);
                
                /* Redirect to account page with success message */
                header("Location: account.php?success=1");
                exit();
            } catch (PDOException $e) {
                $error = "Payment processing failed. Please try again.";
            }
        }
    } 
    /* Handle new signup payment */
    else {
        $nom = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
        $id_abonnement = (int)$_POST['id_abonnement'];

        /* Basic card validation (in real app, use payment processor) */
        $card = str_replace(' ', '', $_POST['card']);
        $expiry = $_POST['expiry'];
        $cvv = $_POST['cvv'];

        if (strlen($card) < 15 || strlen($cvv) < 3) {
            $error = "Invalid card details. Please check your payment information.";
        } else {
            try {
                /* Begin transaction */
                $conn->beginTransaction();

                /* Create user */
                $stmt = $conn->prepare("INSERT INTO utilisateur (id_abonnement, nom, email, mot_de_passe, date_creation, abonnement_actif)
                                        VALUES (?, ?, ?, ?, DATE('now'), 1)");
                $stmt->execute([$id_abonnement, $nom, $email, $mot_de_passe]);

                /* Log the user in */
                $user_id = $conn->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $nom;
                $_SESSION['subscription_id'] = $id_abonnement;
                $_SESSION['just_completed_payment'] = true;

                /* Commit transaction */
                $conn->commit();

                /* Redirect to success page */
                header("Location: success.php");
                exit();
            } catch (PDOException $e) {
                $conn->rollBack();
                if ($e->getCode() == 23000) {
                    $error = "Email already exists. Please use a different email or login.";
                } else {
                    $error = "Payment processing failed. Please try again.";
                }
            }
        }
    }
}

/* Get plan details for display */
if (isset($_GET['action']) && $_GET['action'] == 'plan_upgrade') {
    if (!isset($_SESSION['plan_change'])) {
        header("Location: account.php");
        exit();
    }
    
    $planChange = $_SESSION['plan_change'];
    $planId = $planChange['new_plan_id'];
    
    $stmt = $conn->prepare("SELECT * FROM abonnement WHERE id = ?");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();
    $planName = $plan ? htmlspecialchars($plan['nom'] . ' ($' . $plan['prix'] . '/month)') : 'Selected Plan';
    
    $amountDue = $planChange['amount_due'];
} else {
    $planNames = array(
        1 => 'Basic ($7.99/month)',
        2 => 'Standard ($12.99/month)', 
        3 => 'Premium ($16.99/month)'
    );
    $planId = (int)$_POST['id_abonnement'];
    $planName = $planNames[$planId] ?? 'Selected Plan';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo htmlspecialchars($planName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/payment.css">
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1 class="payment-title">Payment Details</h1>
            <?php if (isset($_GET['action']) && $_GET['action'] == 'plan_upgrade'): ?>
                <p class="payment-subtitle">Complete your plan upgrade</p>
                <div class="plan-badge">Upgrade to <?php echo $planName; ?></div>
                <div class="plan-badge" style="background-color: var(--primary); margin-top: 5px;">
                    Amount Due: $<?php echo $amountDue; ?>
                </div>
            <?php else: ?>
                <p class="payment-subtitle">Complete your subscription to start watching</p>
                <div class="plan-badge"><?php echo htmlspecialchars($planName); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="progress-steps">
            <div class="progress-step">
                <div class="step-number <?php echo isset($_GET['action']) ? 'completed' : 'active'; ?>">
                    <?php echo isset($_GET['action']) ? '<i class="fas fa-check"></i>' : '1'; ?>
                </div>
                <div class="step-label <?php echo isset($_GET['action']) ? 'completed' : 'active'; ?>">
                    <?php echo isset($_GET['action']) ? 'Account' : 'Plan'; ?>
                </div>
            </div>
            <div class="progress-step">
                <div class="step-number active">2</div>
                <div class="step-label active">Payment</div>
            </div>
            <div class="progress-step">
                <div class="step-number">3</div>
                <div class="step-label">Complete</div>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <form class="payment-form" method="POST">
            <?php if (isset($_GET['action']) && $_GET['action'] == 'plan_upgrade'): ?>
                <!-- No hidden fields needed for plan upgrade -->
            <?php else: ?>
                <!-- Original signup hidden fields -->
                <input type="hidden" name="nom" value="<?php echo htmlspecialchars($_POST['nom']); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_POST['email']); ?>">
                <input type="hidden" name="mot_de_passe" value="<?php echo htmlspecialchars($_POST['mot_de_passe']); ?>">
                <input type="hidden" name="id_abonnement" value="<?php echo htmlspecialchars($_POST['id_abonnement']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Card Number</label>
                <input type="text" name="card" placeholder="1234 5678 9012 3456" required
                       inputmode="numeric" pattern="[0-9\s]{13,19}" maxlength="19">
                <div class="card-icons">
                    <div class="card-icon"><i class="fab fa-cc-visa"></i></div>
                    <div class="card-icon"><i class="fab fa-cc-mastercard"></i></div>
                    <div class="card-icon"><i class="fab fa-cc-amex"></i></div>
                    <div class="card-icon"><i class="fab fa-cc-discover"></i></div>
                </div>
            </div>
            
            <div class="card-details">
                <div class="form-group">
                    <label>Expiry Date</label>
                    <input type="text" name="expiry" placeholder="MM/YY" required
                           pattern="(0[1-9]|1[0-2])\/?([0-9]{2})" maxlength="5">
                </div>
                
                <div class="form-group">
                    <label>CVV</label>
                    <input type="text" name="cvv" placeholder="123" required
                           pattern="[0-9]{3,4}" maxlength="4" inputmode="numeric">
                </div>
            </div>
            
            <button type="submit" class="submit-button">
                <?php echo isset($_GET['action']) ? 'Complete Upgrade' : 'Complete Subscription'; ?> <i class="fas fa-lock"></i>
            </button>
            
            <div class="secure-payment">
                <i class="fas fa-lock"></i>
                <span>Secure 256-bit SSL encrypted payment</span>
            </div>
        </form>
    </div>
    
    <script>
        /* Format card number input */
        document.querySelector('input[name="card"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            e.target.value = value.trim();
        });

        /* Format expiry date input */
        document.querySelector('input[name="expiry"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        /* Add animation when page loads */
        document.addEventListener('DOMContentLoaded', function() {
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>