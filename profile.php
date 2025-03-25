<?php
require_once 'config.php';


if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';


$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();


$stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$address_result = $stmt->get_result();
$address = $address_result->num_rows > 0 ? $address_result->fetch_assoc() : null;
$stmt->close();


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    

    if (empty($name) || empty($email) || empty($phone)) {
        $error = "Please fill in all required fields";
    } else {

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already exists";
        } else {

            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $stmt->bind_param("si", $phone, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Phone number already exists";
            } else {

                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
                
                if ($stmt->execute()) {
                    $success = "Profile updated successfully";

                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;

                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $user = $stmt->get_result()->fetch_assoc();
                } else {
                    $error = "Error updating profile: " . $stmt->error;
                }
            }
        }
        $stmt->close();
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_address'])) {
    $street = sanitize_input($_POST['street']);
    $city = sanitize_input($_POST['city']);
    $state = sanitize_input($_POST['state']);
    $postal_code = sanitize_input($_POST['postal_code']);
    $country = sanitize_input($_POST['country']);
    

    if (empty($street) || empty($city) || empty($state) || empty($postal_code) || empty($country)) {
        $error = "Please fill in all address fields";
    } else {
        if ($address) {

            $stmt = $conn->prepare("UPDATE user_addresses SET street = ?, city = ?, state = ?, postal_code = ?, country = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $street, $city, $state, $postal_code, $country, $address['id']);
        } else {

            $stmt = $conn->prepare("INSERT INTO user_addresses (user_id, street, city, state, postal_code, country) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $user_id, $street, $city, $state, $postal_code, $country);
        }
        
        if ($stmt->execute()) {
            $success = "Address updated successfully";
            

            $stmt = $conn->prepare("SELECT * FROM user_addresses WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $address_result = $stmt->get_result();
            $address = $address_result->num_rows > 0 ? $address_result->fetch_assoc() : null;
        } else {
            $error = "Error updating address: " . $stmt->error;
        }
        $stmt->close();
    }
}


$stmt = $conn->prepare("
    SELECT b.*, s.name as service_name, s.description as service_description, 
           u.name as provider_name, b.status
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN service_providers sp ON b.provider_id = sp.id
    JOIN users u ON sp.user_id = u.id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC, b.booking_time DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = [];
while ($booking = $bookings_result->fetch_assoc()) {
    $bookings[] = $booking;
}
$stmt->close();


$stmt = $conn->prepare("
    SELECT r.*, s.name as service_name, b.booking_date
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.id
    JOIN services s ON b.service_id = s.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = [];
while ($review = $reviews_result->fetch_assoc()) {
    $reviews[] = $review;
}
$stmt->close();


$stmt = $conn->prepare("
    SELECT b.*, s.name as service_name, s.description as service_description, 
           u.name as provider_name
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN service_providers sp ON b.provider_id = sp.id
    JOIN users u ON sp.user_id = u.id
    LEFT JOIN reviews r ON b.id = r.booking_id
    WHERE b.user_id = ? AND b.status = 'completed' AND r.id IS NULL
    ORDER BY b.booking_date DESC, b.booking_time DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviewable_bookings_result = $stmt->get_result();
$reviewable_bookings = [];
while ($booking = $reviewable_bookings_result->fetch_assoc()) {
    $reviewable_bookings[] = $booking;
}
$stmt->close();


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $booking_id = sanitize_input($_POST['booking_id']);
    $provider_id = sanitize_input($_POST['provider_id']);
    $rating = sanitize_input($_POST['rating']);
    $comment = sanitize_input($_POST['comment']);
    

    if (empty($booking_id) || empty($provider_id) || empty($rating)) {
        $error = "Please provide all required review information";
    } elseif ($rating < 1 || $rating > 5) {
        $error = "Rating must be between 1 and 5";
    } else {

        $stmt = $conn->prepare("INSERT INTO reviews (booking_id, user_id, provider_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiiss", $booking_id, $user_id, $provider_id, $rating, $comment);
        
        if ($stmt->execute()) {
            $success = "Review submitted successfully";
            

            update_provider_rating($conn, $provider_id);
            

            $stmt = $conn->prepare("
                SELECT b.*, s.name as service_name, s.description as service_description, 
                       u.name as provider_name
                FROM bookings b
                JOIN services s ON b.service_id = s.id
                JOIN service_providers sp ON b.provider_id = sp.id
                JOIN users u ON sp.user_id = u.id
                LEFT JOIN reviews r ON b.id = r.booking_id
                WHERE b.user_id = ? AND b.status = 'completed' AND r.id IS NULL
                ORDER BY b.booking_date DESC, b.booking_time DESC
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $reviewable_bookings_result = $stmt->get_result();
            $reviewable_bookings = [];
            while ($booking = $reviewable_bookings_result->fetch_assoc()) {
                $reviewable_bookings[] = $booking;
            }
            

            $stmt = $conn->prepare("
                SELECT r.*, s.name as service_name, b.booking_date
                FROM reviews r
                JOIN bookings b ON r.booking_id = b.id
                JOIN services s ON b.service_id = s.id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $reviews_result = $stmt->get_result();
            $reviews = [];
            while ($review = $reviews_result->fetch_assoc()) {
                $reviews[] = $review;
            }
        } else {
            $error = "Error submitting review: " . $stmt->error;
        }
        $stmt->close();
    }
}


function update_provider_rating($conn, $provider_id) {
    $stmt = $conn->prepare("
        SELECT AVG(rating) as avg_rating
        FROM reviews
        WHERE provider_id = ?
    ");
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $avg_rating = $result->fetch_assoc()['avg_rating'];
    $stmt->close();
    
    $stmt = $conn->prepare("UPDATE service_providers SET rating = ? WHERE id = ?");
    $stmt->bind_param("di", $avg_rating, $provider_id);
    $stmt->execute();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - LokLagbe</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #d21f50;
            margin-right: 20px;
        }
        
        .profile-info h1 {
            font-size: 2rem;
            margin-bottom: 5px;
            color: #1b5e20;
        }
        
        .profile-info p {
            color: #555;
            margin: 0;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 500;
        }
        
        .tab.active {
            border-bottom-color: #d21f50;
            color: #d21f50;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
        }
        
        .btn-primary {
            background-color: #d21f50;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #c4184a;
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .booking-card {
            border-left: 5px solid #1b5e20;
        }
        
        .booking-card.pending {
            border-left-color: #ff9800;
        }
        
        .booking-card.completed {
            border-left-color: #4caf50;
        }
        
        .booking-card.cancelled {
            border-left-color: #f44336;
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .booking-title {
            font-size: 1.2rem;
            font-weight: 500;
            color: #1b5e20;
        }
        
        .booking-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #e65100;
        }
        
        .status-confirmed {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #1b5e20;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #b71c1c;
        }
        
        .booking-details {
            margin-top: 15px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .booking-detail {
            margin-bottom: 10px;
        }
        
        .booking-detail-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .booking-detail-value {
            font-weight: 500;
        }
        
        .review-form {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .star-rating {
            display: flex;
            margin-bottom: 15px;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #ddd;
            margin-right: 5px;
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #ffb400;
        }
        
        .review-card {
            border-left: 5px solid #d21f50;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .review-service {
            font-size: 1.2rem;
            font-weight: 500;
            color: #1b5e20;
        }
        
        .review-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .review-rating {
            margin-bottom: 10px;
            color: #ffb400;
        }
        
        .review-comment {
            color: #333;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #1b5e20;
            border: 1px solid #c8e6c9;
        }
        
        .alert-danger {
            background-color: #ffebee;
            color: #b71c1c;
            border: 1px solid #ffcdd2;
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
                        <li><a href="profile.php" class="nav-btn active">My Profile</a></li>
                        <li><a href="logout.php" class="nav-btn primary">Logout</a></li>
                    </ul>
                </nav>
                <div class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Profile Section -->
    <section class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-info">
                <h1><?php echo $user['name']; ?></h1>
                <p><?php echo $user['email']; ?></p>
                <p>Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" data-tab="personal-info">Personal Information</div>
            <div class="tab" data-tab="address">Address</div>
            <div class="tab" data-tab="bookings">My Bookings</div>
            <div class="tab" data-tab="reviews">My Reviews</div>
        </div>
        
        <!-- Personal Information Tab -->
        <div class="tab-content active" id="personal-info">
            <div class="card">
                <h2>Personal Information</h2>
                <form action="profile.php" method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo $user['name']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo $user['phone']; ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
        
        <!-- Address Tab -->
        <div class="tab-content" id="address">
            <div class="card">
                <h2>Address Information</h2>
                <form action="profile.php" method="POST">
                    <div class="form-group">
                        <label for="street">Street Address</label>
                        <input type="text" id="street" name="street" class="form-control" value="<?php echo $address ? $address['street'] : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" class="form-control" value="<?php echo $address ? $address['city'] : ''; ?>" required>
                    </div>
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
                    <button type="submit" name="update_address" class="btn btn-primary">Update Address</button>
                </form>
            </div>
        </div>
        
        <!-- Bookings Tab -->
        <div class="tab-content" id="bookings">
            <h2>My Bookings</h2>
            
            <?php if (count($bookings) > 0): ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="card booking-card <?php echo $booking['status']; ?>">
                        <div class="booking-header">
                            <div class="booking-title"><?php echo $booking['service_name']; ?></div>
                            <div class="booking-status status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></div>
                        </div>
                        <div class="booking-details">
                            <div class="booking-detail">
                                <div class="booking-detail-label">Date & Time</div>
                                <div class="booking-detail-value">
                                    <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?> at 
                                    <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                </div>
                            </div>
                            <div class="booking-detail">
                                <div class="booking-detail-label">Service Provider</div>
                                <div class="booking-detail-value"><?php echo $booking['provider_name']; ?></div>
                            </div>
                            <div class="booking-detail">
                                <div class="booking-detail-label">Price</div>
                                <div class="booking-detail-value">৳<?php echo number_format($booking['price'], 2); ?></div>
                            </div>
                            <div class="booking-detail">
                                <div class="booking-detail-label">Booking ID</div>
                                <div class="booking-detail-value">#<?php echo $booking['id']; ?></div>
                            </div>
                        </div>
                        <?php if ($booking['status'] === 'completed'): ?>
                            <?php
                            // Check if booking has been reviewed
                            $stmt = $conn->prepare("SELECT id FROM reviews WHERE booking_id = ?");
                            $stmt->bind_param("i", $booking['id']);
                            $stmt->execute();
                            $reviewed = $stmt->get_result()->num_rows > 0;
                            $stmt->close();
                            ?>
                            
                            <?php if (!$reviewed): ?>
                                <div class="review-form">
                                    <h3>Leave a Review</h3>
                                    <form action="profile.php" method="POST">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <input type="hidden" name="provider_id" value="<?php echo $booking['provider_id']; ?>">
                                        
                                        <div class="form-group">
                                            <label>Rating</label>
                                            <div class="star-rating">
                                                <input type="radio" id="star5-<?php echo $booking['id']; ?>" name="rating" value="5" required>
                                                <label for="star5-<?php echo $booking['id']; ?>"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="star4-<?php echo $booking['id']; ?>" name="rating" value="4">
                                                <label for="star4-<?php echo $booking['id']; ?>"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="star3-<?php echo $booking['id']; ?>" name="rating" value="3">
                                                <label for="star3-<?php echo $booking['id']; ?>"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="star2-<?php echo $booking['id']; ?>" name="rating" value="2">
                                                <label for="star2-<?php echo $booking['id']; ?>"><i class="fas fa-star"></i></label>
                                                <input type="radio" id="star1-<?php echo $booking['id']; ?>" name="rating" value="1">
                                                <label for="star1-<?php echo $booking['id']; ?>"><i class="fas fa-star"></i></label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="comment-<?php echo $booking['id']; ?>">Comment</label>
                                            <textarea id="comment-<?php echo $booking['id']; ?>" name="comment" class="form-control" rows="3" required></textarea>
                                        </div>
                                        
                                        <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="review-form">
                                    <p><i class="fas fa-check-circle" style="color: #4caf50;"></i> You have already reviewed this service.</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card">
                    <p>You haven't booked any services yet.</p>
                    <a href="services.php" class="btn btn-primary">Browse Services</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Reviews Tab -->
        <div class="tab-content" id="reviews">
            <h2>My Reviews</h2>
            
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="card review-card">
                        <div class="review-header">
                            <div class="review-service"><?php echo $review['service_name']; ?></div>
                            <div class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
                        </div>
                        <div class="review-  ?></div>
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
                <div class="card">
                    <p>You haven't reviewed any services yet.</p>
                </div>
            <?php endif; ?>
            
            <?php if (count($reviewable_bookings) > 0): ?>
                <h3>Services You Can Review</h3>
                <?php foreach ($reviewable_bookings as $booking): ?>
                    <div class="card booking-card completed">
                        <div class="booking-header">
                            <div class="booking-title"><?php echo $booking['service_name']; ?></div>
                            <div class="booking-status status-completed">Completed</div>
                        </div>
                        <div class="booking-details">
                            <div class="booking-detail">
                                <div class="booking-detail-label">Date & Time</div>
                                <div class="booking-detail-value">
                                    <?php echo date('F j, Y', strtotime($booking['booking_date'])); ?> at 
                                    <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                </div>
                            </div>
                            <div class="booking-detail">
                                <div class="booking-detail-label">Service Provider</div>
                                <div class="booking-detail-value"><?php echo $booking['provider_name']; ?></div>
                            </div>
                        </div>
                        <div class="review-form">
                            <h3>Leave a Review</h3>
                            <form action="profile.php" method="POST">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <input type="hidden" name="provider_id" value="<?php echo $booking['provider_id']; ?>">
                                
                                <div class="form-group">
                                    <label>Rating</label>
                                    <div class="star-rating">
                                        <input type="radio" id="star5-<?php echo $booking['id']; ?>" name="rating" value="5" required>
                                        <label for="star5-<?php echo $booking['id']; ?>"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star4-<?php echo $booking['id']; ?>" name="rating" value="4">
                                        <label for="star4-<?php echo $booking['id']; ?>"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star3-<?php echo $booking['id']; ?>" name="rating" value="3">
                                        <label for="star3-<?php echo $booking['id']; ?>"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star2-<?php echo $booking['id']; ?>" name="rating" value="2">
                                        <label for="star2-<?php echo $booking['id']; ?>"><i class="fas fa-star"></i></label>
                                        <input type="radio" id="star1-<?php echo $booking['id']; ?>" name="rating" value="1">
                                        <label for="star1-<?php echo $booking['id']; ?>"><i class="fas fa-star"></i></label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comment-<?php echo $booking['id']; ?>">Comment</label>
                                    <textarea id="comment-<?php echo $booking['id']; ?>" name="comment" class="form-control" rows="3" required></textarea>
                                </div>
                                
                                <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
            // Tab switching
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
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

