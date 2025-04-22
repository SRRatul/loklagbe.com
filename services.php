<?php
require_once 'config.php';

// Check if user is logged in
$logged_in = is_logged_in();
$user_id = $logged_in ? $_SESSION['user_id'] : null;

// Get all service categories
$stmt = $conn->prepare("SELECT * FROM service_categories ORDER BY name");
$stmt->execute();
$categories_result = $stmt->get_result();
$categories = [];
while ($category = $categories_result->fetch_assoc()) {
    $categories[] = $category;
}
$stmt->close();

// Get all services
$stmt = $conn->prepare("
    SELECT s.*, c.name as category_name, c.icon as category_icon
    FROM services s
    JOIN service_categories c ON s.category_id = c.id
    ORDER BY c.name, s.name
");
$stmt->execute();
$services_result = $stmt->get_result();
$services = [];
while ($service = $services_result->fetch_assoc()) {
    $services[] = $service;
}
$stmt->close();

// Group services by category
$services_by_category = [];
foreach ($services as $service) {
    $category_id = $service['category_id'];
    if (!isset($services_by_category[$category_id])) {
        $services_by_category[$category_id] = [
            'name' => $service['category_name'],
            'icon' => $service['category_icon'],
            'services' => []
        ];
    }
    $services_by_category[$category_id]['services'][] = $service;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - LokLagbe</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .services-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .services-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .services-header h1 {
            font-size: 2.5rem;
            color: #1b5e20;
            margin-bottom: 10px;
        }
        
        .services-header p {
            color: #555;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .category-section {
            margin-bottom: 50px;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .category-icon {
            width: 50px;
            height: 50px;
            background-color: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #1b5e20;
            font-size: 1.5rem;
        }
        
        .category-title {
            font-size: 1.8rem;
            color: #1b5e20;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .service-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .service-image {
            height: 200px;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1b5e20;
            font-size: 3rem;
        }
        
        .service-content {
            padding: 20px;
        }
        
        .service-title {
            font-size: 1.3rem;
            font-weight: 500;
            color: #1b5e20;
            margin-bottom: 10px;
        }
        
        .service-description {
            color: #555;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .service-price {
            font-weight: 500;
            color: #d21f50;
            margin-bottom: 15px;
        }
        
        .service-rating {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .rating-stars {
            color: #ffb400;
            margin-right: 5px;
        }
        
        .rating-count {
            color: #666;
            font-size: 0.9rem;
        }
        
        .service-action {
            display: flex;
            justify-content: space-between;
        }
        
        .btn-book {
            background-color: #d21f50;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        
        .btn-book:hover {
            background-color: #c4184a;
        }
        
        .btn-details {
            background-color: transparent;
            color: #1b5e20;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            border: 1px solid #1b5e20;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .btn-details:hover {
            background-color: #1b5e20;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo-container">
                    <a href="index.php"><img src="loklagbe.jpeg" alt="LokLagbe Logo" class="logo"></a>
                </div>
                <nav class="nav-menu">
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="services.php" class="active">Services</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <?php if ($logged_in): ?>
                            <li><a href="profile.php" class="nav-btn">My Profile</a></li>
                            <li><a href="logout.php" class="nav-btn primary">Logout</a></li>
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

    <!-- Services Section -->
    <section class="services-container">
        <div class="services-header">
            <h1>Our Services</h1>
            <p>Browse through our wide range of professional services to find exactly what you need</p>
        </div>
        
        <?php foreach ($services_by_category as $category_id => $category): ?>
            <div class="category-section" id="category-<?php echo $category_id; ?>">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="fas <?php echo $category['icon']; ?>"></i>
                    </div>
                    <h2 class="category-title"><?php echo $category['name']; ?></h2>
                </div>
                
                <div class="services-grid">
                    <?php foreach ($category['services'] as $service): ?>
                        <div class="service-card">
                            <div class="service-image">
                                <i class="fas <?php echo $category['icon']; ?>"></i>
                            </div>
                            <div class="service-content">
                                <h3 class="service-title"><?php echo $service['name']; ?></h3>
                                <p class="service-description"><?php echo $service['description']; ?></p>
                                <div class="service-price">Starting from ৳<?php echo number_format($service['price'], 2); ?></div>
                                
                                <?php
                                // Get average rating for this service
                                $stmt = $conn->prepare("
                                    SELECT AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                                    FROM reviews r
                                    JOIN bookings b ON r.booking_id = b.id
                                    WHERE b.service_id = ?
                                ");
                                $stmt->bind_param("i", $service['id']);
                                $stmt->execute();
                                $rating_result = $stmt->get_result()->fetch_assoc();
                                $avg_rating = $rating_result['avg_rating'] ? round($rating_result['avg_rating'], 1) : 0;
                                $review_count = $rating_result['review_count'];
                                $stmt->close();
                                ?>
                                
                                <div class="service-rating">
                                    <div class="rating-stars">
                                        <?php
                                        $full_stars = floor($avg_rating);
                                        $half_star = $avg_rating - $full_stars >= 0.5;
                                        
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $full_stars) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i == $full_stars + 1 && $half_star) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <div class="rating-count"><?php echo $avg_rating; ?> (<?php echo $review_count; ?> reviews)</div>
                                </div>
                                
                                <div class="service-action">
                                    <a href="service-details.php?id=<?php echo $service['id']; ?>" class="btn-details">View Details</a>
                                    <a href="book-service.php?id=<?php echo $service['id']; ?>" class="btn-book">Book Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
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
                            <li><a href="#">Terms & Conditions</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                            <li><a href="#">Refund Policy</a></li>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const navMenu = document.querySelector('.nav-menu');
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    navMenu.style.display = navMenu.style.display === 'block' ? 'none' : 'block';
                });
            }
        });
    </script>
</body>
</html>

