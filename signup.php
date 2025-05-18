<?php
if (!isset($_GET['plan'])) {
    header("Location: pricing.html");
    exit();
}

$selectedPlan = (int)$_GET['plan'];
$planNames = [
    1 => 'Basic',
    2 => 'Standard', 
    3 => 'Premium'
];
$planName = $planNames[$selectedPlan] ?? 'Selected';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - <?= htmlspecialchars($planName) ?> Plan</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/signup.css">
</head>
<body>
  <div class="signup-container">
    <div class="signup-header">
      <h1 class="signup-title">Create Your Account</h1>
      <p class="signup-subtitle">Join millions enjoying unlimited entertainment</p>
      <div class="plan-badge"><?= htmlspecialchars($planName) ?> Plan</div>
    </div>
    
    <div class="progress-steps">
      <div class="progress-step">
        <div class="step-number active">1</div>
        <div class="step-label active">Account</div>
      </div>
      <div class="progress-step">
        <div class="step-number">2</div>
        <div class="step-label">Payment</div>
      </div>
      <div class="progress-step">
        <div class="step-number">3</div>
        <div class="step-label">Complete</div>
      </div>
    </div>
    
    <form class="signup-form" action="payment.php" method="POST">
      <input type="hidden" name="id_abonnement" value="<?= htmlspecialchars($selectedPlan) ?>">

      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="nom" required placeholder="Enter your full name">
      </div>
      
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" required placeholder="Enter your email">
      </div>
      
      <div class="form-group password-container">
        <label for="password">Password</label>
        <input type="password" id="password" name="mot_de_passe" required placeholder="Create a password">
        <i class="toggle-password fas fa-eye" onclick="togglePasswordVisibility()"></i>
      </div>
      
      <button type="submit" class="submit-button">
        Continue to Payment <i class="fas fa-arrow-right"></i>
      </button>
      
      <div class="login-link">
        Already have an account? <a href="login.php">Sign in</a>
      </div>
    </form>
  </div>
  
  <script>
    function togglePasswordVisibility() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.querySelector('.toggle-password');
      
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }
    
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