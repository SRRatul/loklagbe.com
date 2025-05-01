<?php
require_once 'config.php';


if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('profile.php');
}

$booking_id = $_GET['id'];


$stmt = $conn->prepare("
    SELECT b.*, s.name as service_name, s.description as service_description, 
           u.name as provider_name
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN service_providers sp ON b.provider_id = sp.id
    JOIN users u ON sp.user_id = u.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking_result = $stmt->get_result();

if ($booking_result->num_rows === 0) {
    redirect('profile.php');
}

$booking = $booking_result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - LokLagbe</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .confirmation-header h1 {
            font-size: 2.5rem;
            color: #1b5e20;
            margin-bottom: 10px;
        }
        
        .confirmation-header p {
            color: #555;
        }
        
        .confirmation-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .confirmation-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .confirmation-icon i {
            font-size: 5rem;
            color: #4caf50;
        }
        
        .booking-details {
            margin-top: 30px;
        }
        
        .booking-details h2 {
            font-size: 1.5rem;
            color: #1b5e20;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-label {
            width: 200px;
            font-weight: 500;
            color: #555;
        }
        
        .detail-value {
            flex-grow: 1;
            color: #333;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        
        .btn-primary {
            background-color: #d21f50;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #c4184a;
        }
        
        .btn-secondary {
            background-color: #1b5e20;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #164a19;
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
                        <li><a href="services.php">Services</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="profile.php" class="nav-btn">My Profile</a></li>
                        <li><a href="logout.php" class="nav-btn primary">Logout</a></li>
                    </ul>
                </nav>
                <div class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Confirmation Section -->
    <section class="confirmation-container">
        <div class="confirmation-header">
            <h1>Booking Confirmed</h1>
            <p>Your service has been booked successfully</p>
        </div>
        
        <div class="confirmation-card">
            <div class="confirmation-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <div class="text-center">
                <h2>Thank You for Your Booking!</h2>
                <p>Your booking has been confirmed. A confirmation email has been sent to your registered email address.</p>
            </div>
            
            <div class="booking-details">
                <h2>Booking Details</h2>
                
                <div class="detail-row">
                    <div class="detail-label">Booking ID:</div>
                    <div class="detail-value">#<?php echo $booking['id']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Service:</div>
                    <div class="detail-value"><?php echo $booking['service_name']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Provider:</div>
                    <div class="detail-value"><?php echo $booking['provider_name']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Date & Time:</div>
                    <div class="detail-value">
                        <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?> at 
                        <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Address:</div>
                    <div class="detail-value"><?php echo $booking['address']; ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Price:</div>
                    <div class="detail-value">৳<?php echo number_format($booking['price'], 2); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span class="status-<?php echo $booking['status']; ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </div>
                </div>
                
                <?php if (!empty($booking['notes'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Special Instructions:</div>
                        <div class="detail-value"><?php echo $booking['notes']; ?></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <a href="profile.php" class="btn btn-secondary">View My Bookings</a>
                <a href="services.php" class="btn btn-primary">Book Another Service</a>
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

