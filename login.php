<?php
require_once 'config.php';

$error = '';
$success = '';


if (is_logged_in()) {
    redirect('index.php');
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {

        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                

                redirect('index.php');
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lok Lagbe - Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>


     
    

    <div class="signup-container">
        <div class="signup-box">
            <img src="loklagbe.jpeg" alt="Lok Lagbe" class="logo">
            <h2>Login</h2>
           
        
            <div style="text-align: center; margin-bottom: 20px;">
  <a href="index.php" style="margin: 0 10px; text-decoration: none; color: #333; font-weight: bold; transition: color 0.3s, transform 0.3s;"
     onmouseover="this.style.color='#e91e63'; this.style.transform='scale(1.1)';"
     onmouseout="this.style.color='#333'; this.style.transform='scale(1)';">Home</a>
  
  <a href="About-Us.html" style="margin: 0 10px; text-decoration: none; color: #333; font-weight: bold; transition: color 0.3s, transform 0.3s;"
     onmouseover="this.style.color='#e91e63'; this.style.transform='scale(1.1)';"
     onmouseout="this.style.color='#333'; this.style.transform='scale(1)';">About</a>

  <a href="contact.php" style="margin: 0 10px; text-decoration: none; color: #333; font-weight: bold; transition: color 0.3s, transform 0.3s;"
     onmouseover="this.style.color='#e91e63'; this.style.transform='scale(1.1)';"
     onmouseout="this.style.color='#333'; this.style.transform='scale(1)';">Contact</a>

  <a href="signup.php" style="margin: 0 10px; text-decoration: none; color: #333; font-weight: bold; transition: color 0.3s, transform 0.3s;"
     onmouseover="this.style.color='#e91e63'; this.style.transform='scale(1.1)';"
     onmouseout="this.style.color='#333'; this.style.transform='scale(1)';">Sign Up</a>
</div>

            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <input type="email" name="email" placeholder="Email" required class="input-field">
                <input type="password" name="password" placeholder="Password" required class="input-field">
                <button type="submit" class="primary-btn">Login</button>
            </form>
            <p>Or login with</p>
       
            <p>By logging in I agree to the <a href="terms.html" class="terms-link">Terms & Condition</a></p>
            <p>Don't have an account? <a href="signup.php" class="login-link">Sign Up Now</a></p>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>

