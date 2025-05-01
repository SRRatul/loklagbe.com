<?php
require_once 'config.php';
require_once 'contact_process.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - LokLagbe.com</title>
    <link rel="stylesheet" href="contact.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: left;
        }

        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .nav-menu {
            text-align: center;
            padding: 10px 0;
            background-color: #f4f4f4;
        }

        .nav-menu ul {
            display: flex;
            justify-content: center;
            gap: 30px;
        }

        .nav-menu li {
            display: inline-block;
        }

        .nav-menu a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-menu a:hover {
            color: #d21f50;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header class="logo-container">
    
        <nav class="nav-menu">
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php">Sign Up</a></li>
            </ul>
        </nav>
    </header>

    <!-- Contact Section -->
    <section class="contact-section">
        <h2 class="section-title">Get in Touch</h2>
        <p class="section-text">Have any questions or concerns? Reach out to us by filling out the form below.</p>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <form class="contact-form" action="contact.php" method="POST">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" required>

            <label for="message">Your Message</label>
            <textarea id="message" name="message" rows="5" required></textarea>

            <!-- Google reCAPTCHA -->
            <div class="g-recaptcha" data-sitekey="6Le6-wgrAAAAAMaRzICaKQCzNAqAb6bmWUTPnRbI"></div>

            <button type="submit" class="primary-btn">Submit</button>
        </form>
    </section>

    <!-- Footer Section -->
    <footer class="footer">
        <h3>Our Office Address</h3>
        <p>688 Beribadh Road, Mohammadpur, Dhaka, Bangladesh</p>
    </footer>

</body>

</html>