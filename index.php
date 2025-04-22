<?php
require_once 'config.php';

// Check if user is logged in
$logged_in = is_logged_in();
$user_name = $logged_in ? $_SESSION['user_name'] : '';
$is_admin = $logged_in && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Get active hero image
$stmt = $conn->prepare("SELECT * FROM hero_images WHERE is_active = TRUE ORDER BY id DESC LIMIT 1");
$stmt->execute();
$hero_image_result = $stmt->get_result();
$hero_image = $hero_image_result->num_rows > 0 ? $hero_image_result->fetch_assoc() : null;
$hero_image_path = $hero_image ? $hero_image['image_path'] : '/placeholder.svg?height=400&width=500';
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LokLagbe - Find Local Services</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <img src="loklagbe.jpeg" alt="LokLagbe Logo" class="logo">
                </div>
                <nav class="nav-menu">
                    <ul>
                        <li><a href="index.php" class="active">Home</a></li>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="About-Us.html">About us</a></li>
                        <?php if ($logged_in): ?>
                            <li class="dropdown">
                                <a href="#" class="nav-btn">Welcome, <?php echo $user_name; ?> <i class="fas fa-chevron-down"></i></a>
                                <div class="dropdown-content">
                                    <a href="profile.php">My Profile</a>
                                    <?php if ($is_admin): ?>
                                        <a href="admin/index.php">Admin Dashboard</a>
                                    <?php endif; ?>
                                    <a href="logout.php">Logout</a>
                                </div>
                            </li>
                        <?php else: ?>
                            <li><a href="login.php" class="nav-btn">Login</a></li>
                            <li><a href="signup.php" class="nav-btn primary">Sign Up</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <div class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Find Local Services <span>On Demand</span></h1>
                    <p>Connect with trusted professionals for all your household and business needs in Bangladesh.</p>
                    <div class="hero-buttons">
                        <?php if ($logged_in): ?>
                            <a href="services.php" class="btn primary-btn">Browse Services</a>
                        <?php else: ?>
                            <a href="signup.php" class="btn primary-btn">Get Started</a>
                        <?php endif; ?>
                        <a href="#how-it-works" class="btn secondary-btn">Learn More</a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="<?php echo $hero_image_path; ?>" alt="LokLagbe Services">
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <div class="container">
            <div class="section-header">
                <h2>Our Services</h2>
                <p>Find the right professional for any job you need</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Home Repair</h3>
                    <p>Professional repair services for all your household needs</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-broom"></i>
                    </div>
                    <h3>Cleaning</h3>
                    <p>Expert cleaning services for homes and offices</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Electrical</h3>
                    <p>Certified electricians for all electrical work</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-tint"></i>
                    </div>
                    <h3>Plumbing</h3>
                    <p>Reliable plumbing services for any water-related issues</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-paint-roller"></i>
                    </div>
                    <h3>Painting</h3>
                    <p>Professional painting services for interior and exterior</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3>Transport</h3>
                    <p>Reliable transportation services for goods and people</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>How It Works</h2>
                <p>Get the service you need in just a few simple steps</p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Choose a Service</h3>
                        <p>Browse through our wide range of services and select what you need</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Book an Appointment</h3>
                        <p>Select your preferred date and time for the service</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Get the Service</h3>
                        <p>Our professional will arrive at your location and complete the job</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <div class="section-header">
                <h2>What Our Customers Say</h2>
                <p>Hear from people who have used our services</p>
            </div>
            <div class="testimonials-slider">
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"I needed an electrician urgently and LokLagbe connected me with a professional within an hour. Excellent service!"</p>
                        <div class="testimonial-author">
                            <h4>Rahima Ahmed</h4>
                            <p>Dhaka</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"The plumber was very professional and fixed my leaking pipe quickly. I'll definitely use LokLagbe again."</p>
                        <div class="testimonial-author">
                            <h4>Karim Hassan</h4>
                            <p>Chittagong</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"Finding reliable home cleaning services was always a challenge until I discovered LokLagbe. Now it's just a click away!"</p>
                        <div class="testimonial-author">
                            <h4>Fatima Rahman</h4>
                            <p>Sylhet</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of satisfied customers who trust LokLagbe for their service needs</p>
                <?php if ($logged_in): ?>
                    <a href="services.php" class="btn primary-btn">Browse Services</a>
                <?php else: ?>
                    <a href="signup.php" class="btn primary-btn">Sign Up Now</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="loklagbe.jpeg" alt="LokLagbe Logo" class="logo">
                    <p>Connecting you with trusted service professionals</p>
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h3>Company</h3>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="services.php">Services</a></li>
                         
                            <li><a href="contact.php">Contact Us</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h3>Legal</h3>
                        <ul>
                            <li><a href="terms.html">Terms & Conditions</a></li>
                          
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h3>Contact</h3>
                        <ul>
                            <li><i class="fas fa-map-marker-alt"></i> 688 Beribadh Road, Mohammadpur, Dhaka</li>
                            <li><i class="fas fa-phone"></i> +880 1234 567890</li>
                            <li><i class="fas fa-envelope"></i> info@loklagbe.com</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 LokLagbe. All rights reserved.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
    <style>
        /* Add dropdown styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            min-width: 180px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            right: 0;
        }

        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-align: left;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
            color: #d21f50;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown .nav-btn i {
            font-size: 0.8rem;
            margin-left: 5px;
        }
    </style>
</body>
</html>
