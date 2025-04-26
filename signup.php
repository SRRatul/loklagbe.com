<?php
require_once 'config.php';

// Empty string initialize kortesi for error and success message
$error = '';
$success = '';

// Check if user is already logged in
if (is_logged_in()) {
    redirect('index.php');
}

// Process signup form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already exists";
        } else {
            // Check if phone already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Phone number already exists";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
                
                if ($stmt->execute()) {
                    $success = "Registration successful! You can now login.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
            }
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
    <title>Lok Lagbe - Sign Up</title>
    <link rel="stylesheet" href="signup.css">
    <style>
        .input-field {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .input-field:focus {
            border-color: #d21f50;
            outline: none;
            box-shadow: 0 0 5px rgba(210, 31, 80, 0.5);
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-box">
            <img src="loklagbe.jpeg" alt="Lok Lagbe" class="logo">
            <h2>Sign Up</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form action="signup.php" method="POST">
                <input type="text" name="name" placeholder="Full Name" required class="input-field">
                <input type="email" name="email" placeholder="Email" required class="input-field">
                <input type="tel" name="phone" placeholder="Phone Number" required class="input-field">
                <input type="password" name="password" placeholder="Password" required class="input-field">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required class="input-field">
                <button type="submit" class="primary-btn">Sign Up</button>
            </form>
            <p>Or sign up with</p>
            <div class="social-buttons">
                <button class="social-btn fb-btn" id="facebook-btn">
                    <img src="FACEBOOK.png" alt="Facebook" class="icon"> Facebook
                </button>
                <button class="social-btn google-btn" id="google-btn">
                    <img src="GOOGLE.png" alt="Google" class="icon"> Google
                </button>
            </div>
            <p>By signing up I agree to the <a href="#" class="terms-link">Terms & Condition</a></p>
            <p>Already have an account? <a href="login.php" class="login-link">Login Now</a></p>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>

