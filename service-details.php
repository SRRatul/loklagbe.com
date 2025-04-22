<?php
require_once 'config.php';


$logged_in = is_logged_in();
$user_id = $logged_in ? $_SESSION['user_id'] : null;


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('services.php');
}

$service_id = $_GET['id'];


$stmt = $conn->prepare("
    SELECT s.*, c.name as category_name, c.icon as category_icon
    FROM services s
    JOIN service_categories c ON s.category_id = c.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service_result = $stmt->get_result();

if ($service_result->num_rows === 0) {
    redirect('services.php');
}

$service = $service_result->fetch_assoc();
$stmt->close();


$stmt = $conn->prepare("
    SELECT sp.*, u.name as provider_name
    FROM service_providers sp
    JOIN provider_services ps ON sp.id = ps.provider_id
    JOIN users u ON sp.user_id = u.id
    WHERE ps.service_id = ?
    ORDER BY sp.rating DESC
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$providers_result = $stmt->get_result();
$providers = [];
while ($provider = $providers_result->fetch_assoc()) {
    $providers[] = $provider;
}
$stmt->close();


$stmt = $conn->prepare("
    SELECT r.*, u.name as user_name, b.booking_date
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.id
    JOIN users u ON r.user_id = u.id
    WHERE b.service_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = [];
while ($review = $reviews_result->fetch_assoc()) {
    $reviews[] = $review;
}
$stmt->close();


$stmt = $conn->prepare("
    SELECT AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.id
    WHERE b.service_id = ?
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$rating_result = $stmt->get_result()->fetch_assoc();
$avg_rating = $rating_result['avg_rating'] ? round($rating_result['avg_rating'], 1) : 0;
$review_count = $rating_result['review_count'];
$stmt->close();


$rating_distribution = [0, 0, 0, 0, 0];
if ($review_count > 0) {
    $stmt = $conn->prepare("
        SELECT r.rating, COUNT(r.id) as count
        FROM reviews r
        JOIN bookings b ON r.booking_id = b.id
        WHERE b.service_id = ?
        GROUP BY r.rating
        ORDER BY r.rating DESC
    ");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $distribution_result = $stmt->get_result();
    while ($row = $distribution_result->fetch_assoc()) {
        $rating_distribution[$row['rating'] - 1] = $row['count'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $service['name']; ?> - LokLagbe</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .service-details-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .service-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .service-icon {
            width: 80px;
            height: 80px;
            background-color: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            color: #1b5e20;
            font-size: 2rem;
        }
        
        .service-title h1 {
            font-size: 2.5rem;
            color: #1b5e20;
            margin-bottom: 5px;
        }
        
        .service-title p {
            color: #555;
        }
        
        .service-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        .service-description {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .service-description h2 {
            font-size: 1.8rem;
            color: #1b5e20;
            margin-bottom: 15px;
        }
        
        .service-description p {
            color: #333;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .service-features {
            margin-top: 20px;
        }
        
        .service-features h3 {
            font-size: 1.3rem;
            color: #1b5e20;
            margin-bottom: 15px;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
        }
        
        .features-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
        }
        
        .features-list li:last-child {
            border-bottom: none;
        }
        
        .features-list li i {
            color: #4caf50;
            margin-right: 10px;
        }
        
        .service-reviews {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .reviews-header h2 {
            font-size: 1.8rem;
            color: #1b5e20;
        }
        
        .overall-rating {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .rating-number {
            font-size: 3rem;
            font-weight: 700;
            color: #1b5e20;
            margin-right: 15px;
        }
        
        .rating-stars {
            color: #ffb400;
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .rating-count {
            color: #666;
        }
        
        .rating-bars {
            margin-bottom: 20px;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .rating-label {
            width: 30px;
            text-align: right;
            margin-right: 10px;
            font-weight: 500;
        }
        
        .bar-container {
            flex-grow: 1;
            height: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
            margin-right: 10px;
            overflow: hidden;
        }
        
        .bar-fill {
            height: 100%;
            background-color: #ffb400;
            border-radius: 5px;
        }
        
        .bar-count {
            width: 30px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .review-list {
            margin-top: 30px;
        }
        
        .review-card {
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .review-card:last-child {
            border-bottom: none;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .reviewer-name {
            font-weight: 500;
            color: #333;
        }
        
        .review-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .review-rating {
            color: #ffb400;
            margin-bottom: 10px;
        }
        
        .review-comment {
            color: #333;
            line-height: 1.5;
        }
        
        .booking-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
            position: sticky;
            top: 20px;
        }
        
        .booking-card h2 {
            font-size: 1.8rem;
            color: #1b5e20;
            margin-bottom: 15px;
        }
        
        .price-calculator {
            margin-bottom: 20px;
        }
        
        .calculator-group {
            margin-bottom: 15px;
        }
        
        .calculator-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .calculator-group select,
        .calculator-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .price-display {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .price-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .price-amount {
            font-size: 2rem;
            font-weight: 700;
            color: #d21f50;
        }
        
        .booking-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #d21f50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        
        .booking-btn:hover {
            background-color: #c4184a;
        }
        
        @media (max-width: 992px) {
            .service-content {
                grid-template-columns: 1fr;
            }
            
            .booking-card {
                position: static;
            }
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

    <!-- Service Details Section -->
    <section class="service-details-container">
        <div class="service-header">
            <div class="service-icon">
                <i class="fas <?php echo $service['category_icon']; ?>"></i>
            </div>
            <div class="service-title">
                <h1><?php echo $service['name']; ?></h1>
                <p>Category: <?php echo $service['category_name']; ?></p>
            </div>
        </div>
        
        <div class="service-content">
            <div class="service-info">
                <div class="service-description">
                    <h2>Service Description</h2>
                    <p><?php echo $service['description']; ?></p>
                    
                    <div class="service-features">
                        <h3>What's Included</h3>
                        <ul class="features-list">
                            <li><i class="fas fa-check-circle"></i> Professional service providers</li>
                            <li><i class="fas fa-check-circle"></i> Quality materials and equipment</li>
                            <li><i class="fas fa-check-circle"></i> Satisfaction guarantee</li>
                            <li><i class="fas fa-check-circle"></i> Flexible scheduling</li>
                            <li><i class="fas fa-check-circle"></i> Post-service cleanup</li>
                        </ul>
                    </div>
                </div>
                
                <div class="service-reviews">
                    <div class="reviews-header">
                        <h2>Customer Reviews</h2>
                    </div>
                    
                    <div class="overall-rating">
                        <div class="rating-number"><?php echo $avg_rating; ?></div>
                        <div>
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
                            <div class="rating-count"><?php echo $review_count; ?> reviews</div>
                        </div>
                    </div>
                    
                    <div class="rating-bars">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="rating-bar">
                                <div class="rating-label"><?php echo $i; ?></div>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: <?php echo $review_count > 0 ? ($rating_distribution[$i - 1] / $review_count) * 100 : 0; ?>%"></div>
                                </div>
                                <div class="bar-count"><?php echo $rating_distribution[$i - 1]; ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="review-list">
                        <?php if (count($reviews) > 0): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <div class="reviewer-name"><?php echo $review['user_name']; ?></div>
                                        <div class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="review-comment">
                                        <?php echo $review['comment']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No reviews yet. Be the first to review this service!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="booking-sidebar">
                <div class="booking-card">
                    <h2>Book This Service</h2>
                    
                    <div class="price-calculator">
                        <div class="calculator-group">
                            <label for="service-size">Area Size (sq ft)</label>
                            <select id="service-size" class="form-control">
                                <option value="500">Up to 500 sq ft</option>
                                <option value="1000">501 - 1000 sq ft</option>
                                <option value="1500">1001 - 1500 sq ft</option>
                                <option value="2000">1501 - 2000 sq ft</option>
                                <option value="2500">2001 - 2500 sq ft</option>
                                <option value="3000">Above 2500 sq ft</option>
                            </select>
                        </div>
                        
                        <div class="calculator-group">
                            <label for="service-type">Service Type</label>
                            <select id="service-type" class="form-control">
                                <option value="standard">Standard</option>
                                <option value="premium">Premium</option>
                                <option value="deluxe">Deluxe</option>
                            </select>
                        </div>
                        
                        <div class="price-display">
                            <div class="price-label">Estimated Price</div>
                            <div class="price-amount">৳<span id="calculated-price"><?php echo number_format($service['price'], 2); ?></span></div>
                        </div>
                    </div>
                    
                    <?php if ($logged_in): ?>
                        <a href="book-service.php?id=<?php echo $service['id']; ?>" class="booking-btn">Book Now</a>
                    <?php else: ?>
                        <a href="login.php" class="booking-btn">Login to Book</a>
                    <?php endif; ?>
                </div>
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
            
            // Price calculator
            const serviceSizeSelect = document.getElementById('service-size');
            const serviceTypeSelect = document.getElementById('service-type');
            const calculatedPriceSpan = document.getElementById('calculated-price');
            
            const basePrice = <?php echo $service['price']; ?>;
            
            function calculatePrice() {
                const sizeValue = parseInt(serviceSizeSelect.value);
                const typeValue = serviceTypeSelect.value;
                
                let sizeMultiplier = 1;
                if (sizeValue === 500) sizeMultiplier = 1;
                else if (sizeValue === 1000) sizeMultiplier = 1.5;
                else if (sizeValue === 1500) sizeMultiplier = 2;
                else if (sizeValue === 2000) sizeMultiplier = 2.5;
                else if (sizeValue === 2500) sizeMultiplier = 3;
                else if (sizeValue === 3000) sizeMultiplier = 3.5;
                
                let typeMultiplier = 1;
                if (typeValue === 'standard') typeMultiplier = 1;
                else if (typeValue === 'premium') typeMultiplier = 1.3;
                else if (typeValue === 'deluxe') typeMultiplier = 1.6;
                
                const finalPrice = basePrice * sizeMultiplier * typeMultiplier;
                calculatedPriceSpan.textContent = finalPrice.toFixed(2);
            }
            
            serviceSizeSelect.addEventListener('change', calculatePrice);
            serviceTypeSelect.addEventListener('change', calculatePrice);
            
            calculatePrice();
        });
    </script>
</body>
</html>

