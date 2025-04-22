<?php
require_once 'config.php';


if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';


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


$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$address_result = $stmt->get_result();
$address = $address_result->num_rows > 0 ? $address_result->fetch_assoc() : null;
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


if (count($providers) === 0) {

    $stmt = $conn->prepare("SELECT sp.*, u.name as provider_name FROM service_providers sp JOIN users u ON sp.user_id = u.id LIMIT 1");
    $stmt->execute();
    $provider_result = $stmt->get_result();
    if ($provider_result->num_rows > 0) {
        $providers[] = $provider_result->fetch_assoc();
    } else {

        $providers[] = [
            'id' => 1,
            'provider_name' => 'Default Provider',
            'rating' => 4.5
        ];
    }
    $stmt->close();
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $provider_id = sanitize_input($_POST['provider_id']);
    $booking_date = sanitize_input($_POST['booking_date']);
    $booking_time = sanitize_input($_POST['booking_time']);
    $service_size = sanitize_input($_POST['service_size']);
    $service_type = sanitize_input($_POST['service_type']);
    $price = sanitize_input($_POST['price']);
    $notes = sanitize_input($_POST['notes']);
    

    $street = sanitize_input($_POST['street']);
    $city = sanitize_input($_POST['city']);
    $state = sanitize_input($_POST['state']);
    $postal_code = sanitize_input($_POST['postal_code']);
    $country = sanitize_input($_POST['country']);
    

    if (empty($provider_id) || empty($booking_date) || empty($booking_time) || empty($price) || 
        empty($street) || empty($city) || empty($state) || empty($postal_code) || empty($country)) {
        $error = "Please fill in all required fields";
    } else {

        $address_text = "$street, $city, $state $postal_code, $country";
        

        $stmt = $conn->prepare("
            INSERT INTO bookings (user_id, provider_id, service_id, booking_date, booking_time, 
                                 status, price, address, notes, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iiississ", $user_id, $provider_id, $service_id, $booking_date, $booking_time, 
                         $price, $address_text, $notes);
        
        if ($stmt->execute()) {
            $booking_id = $conn->insert_id;
            $success = "Booking successful! Your booking ID is #$booking_id";
            

            if ($address) {
                $stmt = $conn->prepare("
                    UPDATE user_addresses 
                    SET street = ?, city = ?, state = ?, postal_code = ?, country = ? 
                    WHERE user_id = ?
                ");
                $stmt->bind_param("sssssi", $street, $city, $state, $postal_code, $country, $user_id);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO user_addresses (user_id, street, city, state, postal_code, country)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("isssss", $user_id, $street, $city, $state, $postal_code, $country);
            }
            $stmt->execute();
            

            redirect("booking-confirmation.php?id=$booking_id");
        } else {
            $error = "Error creating booking: " . $stmt->error;
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
    <title>Book Service - <?php echo $service['name']; ?> - LokLagbe</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .booking-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .booking-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .booking-header h1 {
            font-size: 2.5rem;
            color: #1b5e20;
            margin-bottom: 10px;
        }
        
        .booking-header p {
            color: #555;
        }
        
        .booking-form {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h2 {
            font-size: 1.5rem;
            color: #1b5e20;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .provider-selection {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .provider-card {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            cursor: pointer;
            transition: border-color 0.3s ease, transform 0.3s ease;
        }
        
        .provider-card:hover {
            border-color: #1b5e20;
            transform: translateY(-5px);
        }
        
        .provider-card.selected {
            border-color: #1b5e20;
            background-color: #e8f5e9;
        }
        
        .provider-name {
            font-weight: 500;
            color: #1b5e20;
            margin-bottom: 5px;
        }
        
        .provider-rating {
            color: #ffb400;
            font-size: 0.9rem;
        }
        
        .price-calculator {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .price-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.2rem;
            color: #d21f50;
        }
        
        .price-label {
            color: #555;
        }
        
        .price-value {
            font-weight: 500;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
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
            background-color: #f0f0f0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: #ffebee;
            color: #b71c1c;
            border: 1px solid #ffcdd2;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #c8e6c9;
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

    <!-- Booking Section -->
    <section class="booking-container">
        <div class="booking-header">
            <h1>Book <?php echo $service['name']; ?></h1>
            <p>Fill in the details below to book this service</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form class="booking-form" method="POST" action="book-service.php?id=<?php echo $service_id; ?>">
            <div class="form-section">
                <h2>Service Details</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="service-size">Area Size</label>
                        <select id="service-size" name="service_size" class="form-control" required>
                            <option value="500">Up to 500 sq ft</option>
                            <option value="1000">501 - 1000 sq ft</option>
                            <option value="1500">1001 - 1500 sq ft</option>
                            <option value="2000">1501 - 2000 sq ft</option>
                            <option value="2500">2001 - 2500 sq ft</option>
                            <option value="3000">Above 2500 sq ft</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="service-type">Service Type</label>
                        <select id="service-type" name="service_type" class="form-control" required>
                            <option value="standard">Standard</option>
                            <option value="premium">Premium</option>
                            <option value="deluxe">Deluxe</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Select Service Provider</label>
                    <div class="provider-selection">
                        <?php foreach ($providers as $index => $provider): ?>
                            <div class="provider-card <?php echo $index === 0 ? 'selected' : ''; ?>" data-provider-id="<?php echo $provider['id']; ?>">
                                <div class="provider-name"><?php echo $provider['provider_name']; ?></div>
                                <div class="provider-rating">
                                    <?php
                                    $rating = $provider['rating'];
                                    $full_stars = floor($rating);
                                    $half_star = $rating - $full_stars >= 0.5;
                                    
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
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="provider-id" name="provider_id" value="<?php echo $providers[0]['id']; ?>" required>
                </div>
            </div>
            
            <div class="form-section">
                <h2>Schedule</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="booking-date">Date</label>
                        <input type="date" id="booking-date" name="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="booking-time">Time</label>
                        <select id="booking-time" name="booking_time" class="form-control" required>
                            <option value="09:00:00">9:00 AM</option>
                            <option value="10:00:00">10:00 AM</option>
                            <option value="11:00:00">11:00 AM</option>
                            <option value="12:00:00">12:00 PM</option>
                            <option value="13:00:00">1:00 PM</option>
                            <option value="14:00:00">2:00 PM</option>
                            <option value="15:00:00">3:00 PM</option>
                            <option value="16:00:00">4:00 PM</option>
                            <option value="17:00:00">5:00 PM</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2>Address</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="street">Street Address</label>
                        <input type="text" id="street" name="street" class="form-control" value="<?php echo $address ? $address['street'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" class="form-control" value="<?php echo $address ? $address['city'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="state">State/Division</label>
                        <input type="text" id="state" name="state" class="form-control" value="<?php echo $address ? $address['state'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="postal_code">Postal Code</label>
                        <input type="text" id="postal_code" name="postal_code" class="form-control" value="<?php echo $address ? $address['postal_code'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country" class="form-control" value="<?php echo $address ? $address['country'] : 'Bangladesh'; ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2>Additional Information</h2>
                
                <div class="form-group">
                    <label for="notes">Special Instructions (Optional)</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h2>Price Summary</h2>
                
                <div class="price-calculator">
                    <div class="price-row">
                        <div class="price-label">Base Price</div>
                        <div class="price-value">৳<span id="base-price"><?php echo number_format($service['price'], 2); ?></span></div>
                    </div>
                    <div class="price-row">
                        <div class="price-label">Area Size Adjustment</div>
                        <div class="price-value">৳<span id="size-adjustment">0.00</span></div>
                    </div>
                    <div class="price-row">
                        <div class="price-label">Service Type Adjustment</div>
                        <div class="price-value">৳<span id="type-adjustment">0.00</span></div>
                    </div>
                    <div class="price-row">
                        <div class="price-label">Total</div>
                        <div class="price-value">৳<span id="total-price"><?php echo number_format($service['price'], 2); ?></span></div>
                    </div>
                </div>
                <input type="hidden" id="price" name="price" value="<?php echo $service['price']; ?>">
            </div>
            
            <div class="form-actions">
                <a href="service-details.php?id=<?php echo $service_id; ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Confirm Booking</button>
            </div>
        </form>
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
            
 
            const providerCards = document.querySelectorAll('.provider-card');
            const providerIdInput = document.getElementById('provider-id');
            
            providerCards.forEach(card => {
                card.addEventListener('click', function() {

                    providerCards.forEach(c => c.classList.remove('selected'));
                    

                    this.classList.add('selected');
                    
  
                    providerIdInput.value = this.getAttribute('data-provider-id');
                });
            });
            

            const serviceSizeSelect = document.getElementById('service-size');
            const serviceTypeSelect = document.getElementById('service-type');
            const basePriceSpan = document.getElementById('base-price');
            const sizeAdjustmentSpan = document.getElementById('size-adjustment');
            const typeAdjustmentSpan = document.getElementById('type-adjustment');
            const totalPriceSpan = document.getElementById('total-price');
            const priceInput = document.getElementById('price');
            
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
                
                const sizeAdjustment = basePrice * (sizeMultiplier - 1);
                const typeAdjustment = basePrice * sizeMultiplier * (typeMultiplier - 1);
                const finalPrice = basePrice * sizeMultiplier * typeMultiplier;
                
                basePriceSpan.textContent = basePrice.toFixed(2);
                sizeAdjustmentSpan.textContent = sizeAdjustment.toFixed(2);
                typeAdjustmentSpan.textContent = typeAdjustment.toFixed(2);
                totalPriceSpan.textContent = finalPrice.toFixed(2);
                priceInput.value = finalPrice.toFixed(2);
            }
            
            serviceSizeSelect.addEventListener('change', calculatePrice);
            serviceTypeSelect.addEventListener('change', calculatePrice);
            

            calculatePrice();
            

            const bookingDateInput = document.getElementById('booking-date');
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const formattedDate = tomorrow.toISOString().split('T')[0];
            bookingDateInput.setAttribute('min', formattedDate);
            bookingDateInput.value = formattedDate;
        });
    </script>
</body>
</html>

