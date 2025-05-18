<?php
session_start();
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM utilisateur WHERE email = :email";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['nom'];
        $_SESSION['subscription_id'] = $user['id_abonnement'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        // Redirect admin to admin dashboard, others to profiles
        if ($user['is_admin']) {
            header("Location: /admin/admin.php");
        } else {
            header("Location: profiles.php");
        }
        exit();
    } else {
        $error_message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - BING</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #00A8E1;
      --primary-dark: #0088b9;
      --secondary: #f4c430;
      --secondary-dark: #e6b82e;
      --dark: #0f0f0f;
      --darker: #0a0a0a;
      --light: #ffffff;
      --gray: #a2a2a2;
      --dark-gray: #2a2a2a;
      --light-gray: #e0e0e0;
      --success: #46d369;
      --error: #ff4d4d;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
    }
    
    body {
      background-color: var(--darker);
      color: var(--light);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px;
      background-image: linear-gradient(rgba(0, 0, 0, 0.85), rgba(0, 0, 0, 0.85)),
                      url('https://m.media-amazon.com/images/M/MV5BZjBlMjIxYzgtOGYyOC00NzY3LTg1ZWMtM2U0NzE0OTQ2YjRlXkEyXkFqcGdeQXVyMTUzMTg2ODkz._V1_.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }
    
    .logo {
      width: 150px;
      margin-bottom: 30px;
    }
    
    .login-container {
      text-align: center;
      width: 100%;
      max-width: 500px;
      animation: fadeIn 0.5s ease;
    }
    
    .login-box {
      background-color: rgba(26, 26, 26, 0.9);
      padding: 50px 40px;
      border-radius: 12px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
      border-top: 4px solid var(--secondary);
      position: relative;
      overflow: hidden;
    }
    
    .login-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--secondary), var(--primary));
    }
    
    .login-title {
      font-size: 2.2rem;
      margin-bottom: 30px;
      color: var(--light);
      font-weight: 600;
      position: relative;
      display: inline-block;
    }
    
    .login-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 3px;
      background: var(--secondary);
    }
    
    .form-group {
      margin-bottom: 25px;
      text-align: left;
      position: relative;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--light-gray);
      font-size: 0.9rem;
    }
    
    .input-icon {
      position: absolute;
      top: 38px;
      left: 15px;
      color: var(--gray);
    }
    
    .form-group input {
      width: 100%;
      padding: 14px 14px 14px 45px;
      background-color: rgba(44, 44, 44, 0.8);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      color: white;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .form-group input:focus {
      outline: none;
      border-color: var(--primary);
      background-color: rgba(44, 44, 44, 1);
      box-shadow: 0 0 0 3px rgba(0, 168, 225, 0.2);
    }
    
    .form-group input::placeholder {
      color: var(--gray);
    }
    
    .forgot-password {
      text-align: right;
      margin-top: -15px;
      margin-bottom: 20px;
    }
    
    .forgot-password a {
      color: var(--gray);
      font-size: 0.85rem;
      transition: all 0.3s ease;
    }
    
    .forgot-password a:hover {
      color: var(--secondary);
      text-decoration: none;
    }
    
    .submit-button {
      width: 100%;
      padding: 15px;
      background-color: var(--secondary);
      color: var(--dark);
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      font-size: 1rem;
      margin-top: 10px;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    
    .submit-button:hover {
      background-color: var(--secondary-dark);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(244, 196, 48, 0.3);
    }
    
    .signup-prompt {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      color: var(--gray);
      font-size: 0.95rem;
    }
    
    .signup-prompt a {
      color: var(--secondary);
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .signup-prompt a:hover {
      color: var(--secondary-dark);
      text-decoration: underline;
    }
    
    .error-message {
      color: var(--error);
      margin: 15px 0;
      text-align: center;
      padding: 10px;
      background-color: rgba(255, 77, 77, 0.1);
      border-radius: 8px;
      animation: shake 0.5s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-5px); }
      40%, 80% { transform: translateX(5px); }
    }
    
    /* Mobile styles */
    @media (max-width: 768px) {
      .login-box {
        padding: 40px 30px;
      }
      
      .login-title {
        font-size: 1.8rem;
      }
    }
    
    @media (max-width: 480px) {
      .login-box {
        padding: 30px 20px;
      }
      
      .login-title {
        font-size: 1.6rem;
      }
      
      .form-group input {
        padding: 12px 12px 12px 40px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <h1 class="login-title">Sign In</h1>
      
      <?php if (isset($error_message)): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
      <?php endif; ?>
      
      <form method="POST" action="login.php">
        <div class="form-group">
          <label for="email">Email Address</label>
          <i class="fas fa-envelope input-icon"></i>
          <input type="email" id="email" name="email" placeholder="Enter your email" required>
        </div>
        
        <div class="form-group">
          <label for="password">Password</label>
          <i class="fas fa-lock input-icon"></i>
          <input type="password" id="password" name="password" placeholder="Enter your password" required>
        </div>
        
        <div class="forgot-password">
          <a href="forgot-password.php">Forgot your password?</a>
        </div>
        
        <button type="submit" class="submit-button">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
        
        <div class="signup-prompt">
          New to BING? <a href="pricing.html">Start your subscription now</a>
        </div>
      </form>
    </div>
  </div>
  
  <script>
    /* Add animation when page loads */
    document.addEventListener('DOMContentLoaded', function() {
      const elements = document.querySelectorAll('.login-title, .form-group, .submit-button, .signup-prompt');
      elements.forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
      });
    });
    
    /* Focus effects for inputs */
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.querySelector('.input-icon').style.color = 'var(--primary)';
      });
      
      input.addEventListener('blur', function() {
        this.parentElement.querySelector('.input-icon').style.color = 'var(--gray)';
      });
    });
  </script>
</body>
</html>