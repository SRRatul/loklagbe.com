<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Get current hero image
$stmt = $conn->prepare("SELECT * FROM hero_images WHERE is_active = TRUE ORDER BY id DESC LIMIT 1");
$stmt->execute();
$current_image = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Process image upload
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_FILES["hero_image"]) && $_FILES["hero_image"]["error"] == 0) {
        $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png"];
        $filename = $_FILES["hero_image"]["name"];
        $filetype = $_FILES["hero_image"]["type"];
        $filesize = $_FILES["hero_image"]["size"];
    

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed)) {
            $error = "Error: Please select a valid file format.";
        }
    

        $maxsize = 5 * 1024 * 1024;
        if ($filesize > $maxsize) {
            $error = "Error: File size is larger than the allowed limit (5MB).";
        }
    

        if (in_array($filetype, $allowed)) {

            $target_dir = "../uploads/";
            
 
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $new_filename = uniqid() . "." . $ext;
            $target_file = $target_dir . $new_filename;
            
  
            if (move_uploaded_file($_FILES["hero_image"]["tmp_name"], $target_file)) {
                $image_path = "uploads/" . $new_filename;
                $title = sanitize_input($_POST['title']);
                
                $stmt = $conn->prepare("UPDATE hero_images SET is_active = FALSE");
                $stmt->execute();
                $stmt->close();
                

                $stmt = $conn->prepare("INSERT INTO hero_images (image_path, title, is_active) VALUES (?, ?, TRUE)");
                $stmt->bind_param("ss", $image_path, $title);
                
                if ($stmt->execute()) {
                    $success = "Hero image uploaded successfully.";
                    

                    $stmt = $conn->prepare("SELECT * FROM hero_images WHERE is_active = TRUE ORDER BY id DESC LIMIT 1");
                    $stmt->execute();
                    $current_image = $stmt->get_result()->fetch_assoc();
                } else {
                    $error = "Error saving image to database: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Error uploading file.";
            }
        } else {
            $error = "Error: There was a problem with your upload. Please try again.";
        }
    } else {
        $error = "Error: " . $_FILES["hero_image"]["error"];
    }
}


$stmt = $conn->prepare("SELECT * FROM hero_images ORDER BY created_at DESC");
$stmt->execute();
$all_images = $stmt->get_result();
$images = [];
while ($image = $all_images->fetch_assoc()) {
    $images[] = $image;
}
$stmt->close();


if (isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    $image_id = $_GET['activate'];
    

    $stmt = $conn->prepare("UPDATE hero_images SET is_active = FALSE");
    $stmt->execute();
    $stmt->close();
    

    $stmt = $conn->prepare("UPDATE hero_images SET is_active = TRUE WHERE id = ?");
    $stmt->bind_param("i", $image_id);
    
    if ($stmt->execute()) {
        $success = "Hero image activated successfully.";
        
 
        $stmt = $conn->prepare("SELECT * FROM hero_images WHERE is_active = TRUE ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $current_image = $stmt->get_result()->fetch_assoc();
        

        $stmt = $conn->prepare("SELECT * FROM hero_images ORDER BY created_at DESC");
        $stmt->execute();
        $all_images = $stmt->get_result();
        $images = [];
        while ($image = $all_images->fetch_assoc()) {
            $images[] = $image;
        }
    } else {
        $error = "Error activating image: " . $stmt->error;
    }
    $stmt->close();
}

// Handle deletion of image
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $image_id = $_GET['delete'];
    
    // Get image path before deleting
    $stmt = $conn->prepare("SELECT image_path, is_active FROM hero_images WHERE id = ?");
    $stmt->bind_param("i", $image_id);
    $stmt->execute();
    $image_to_delete = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Don't allow deleting active image
    if ($image_to_delete['is_active']) {
        $error = "Cannot delete the active hero image. Please activate another image first.";
    } else {
        // Delete image from database
        $stmt = $conn->prepare("DELETE FROM hero_images WHERE id = ?");
        $stmt->bind_param("i", $image_id);
        
        if ($stmt->execute()) {
            // Delete file from server
            $file_path = "../" . $image_to_delete['image_path'];
            if (file_exists($file_path) && $image_to_delete['image_path'] != '/placeholder.svg?height=400&width=500') {
                unlink($file_path);
            }
            
            $success = "Hero image deleted successfully.";
            
            // Refresh all images
            $stmt = $conn->prepare("SELECT * FROM hero_images ORDER BY created_at DESC");
            $stmt->execute();
            $all_images = $stmt->get_result();
            $images = [];
            while ($image = $all_images->fetch_assoc()) {
                $images[] = $image;
            }
        } else {
            $error = "Error deleting image: " . $stmt->error;
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
    <title>Manage Hero Image - LokLagbe Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #1b5e20;
            --secondary-color: #d21f50;
            --light-bg: #f5f5f5;
            --card-bg: #ffffff;
            --text-color: #333333;
            --text-light: #666666;
            --border-color: #e0e0e0;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --info-color: #2196f3;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }
        
        /* Admin Layout */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-logo {
            max-width: 150px;
            height: auto;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: white;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
        }
        
        .header {
            height: var(--header-height);
            background-color: var(--card-bg);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-color);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .user-info {
            margin-right: 15px;
            text-align: right;
        }
        
        .user-name {
            font-weight: 500;
        }
        
        .user-role {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .content {
            padding: 20px;
        }
        
        .page-title {
            margin-bottom: 20px;
            font-size: 1.8rem;
            color: var(--primary-color);
        }
        
        /* Hero Image Page Styles */
        .hero-image-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .current-image {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .current-image h2 {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .image-preview {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .image-details {
            margin-top: 15px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            width: 100px;
            font-weight: 500;
            color: var(--text-light);
        }
        
        .detail-value {
            flex: 1;
        }
        
        .upload-form {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .upload-form h2 {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #164a19;
        }
        
        .image-history {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-top: 30px;
        }
        
        .image-history h2 {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .image-card {
            background-color: var(--light-bg);
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }
        
        .image-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .image-card-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px;
        }
        
        .image-card-title {
            font-size: 0.9rem;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .image-card-date {
            font-size: 0.8rem;
            color: #ccc;
        }
        
        .image-card-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }
        
        .card-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .btn-activate {
            background-color: var(--success-color);
        }
        
        .btn-delete {
            background-color: var(--danger-color);
        }
        
        .active-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: var(--success-color);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(76, 175, 80, 0.2);
        }
        
        .alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(244, 67, 54, 0.2);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.pushed {
                margin-left: var(--sidebar-width);
            }
            
            .hero-image-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="../loklagbe.jpeg" alt="LokLagbe Logo" class="sidebar-logo">
            </div>
            <nav class="sidebar-menu">
                <a href="index.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="users.php" class="menu-item">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="services.php" class="menu-item">
                    <i class="fas fa-tools"></i> Services
                </a>
                <a href="bookings.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i> Bookings
                </a>
                <a href="reviews.php" class="menu-item">
                    <i class="fas fa-star"></i> Reviews
                </a>
                <a href="hero-image.php" class="menu-item active">
                    <i class="fas fa-image"></i> Hero Image
                </a>
                <a href="../index.php" class="menu-item">
                    <i class="fas fa-home"></i> Back to Website
                </a>
                <a href="../logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <!-- Header -->
            <header class="header">
                <button class="toggle-sidebar" id="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?php echo $_SESSION['user_name']; ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <h1 class="page-title">Manage Hero Image</h1>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="hero-image-container">
                    <div class="current-image">
                        <h2>Current Hero Image</h2>
                        <?php if ($current_image): ?>
                            <img src="../<?php echo $current_image['image_path']; ?>" alt="Current Hero Image" class="image-preview">
                            <div class="image-details">
                                <div class="detail-row">
                                    <div class="detail-label">Title:</div>
                                    <div class="detail-value"><?php echo $current_image['title']; ?></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Uploaded:</div>
                                    <div class="detail-value"><?php echo date('F j, Y, g:i a', strtotime($current_image['created_at'])); ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p>No hero image set. Please upload one.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="upload-form">
                        <h2>Upload New Hero Image</h2>
                        <form action="hero-image.php" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="hero_image">Select Image</label>
                                <input type="file" id="hero_image" name="hero_image" class="form-control" required accept="image/*">
                                <small>Recommended size: 1000x500 pixels. Max file size: 5MB.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="title">Image Title</label>
                                <input type="text" id="title" name="title" class="form-control" required placeholder="Enter a descriptive title">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Upload Image</button>
                        </form>
                    </div>
                </div>
                
                <div class="image-history">
                    <h2>Image History</h2>
                    
                    <?php if (count($images) > 0): ?>
                        <div class="image-grid">
                            <?php foreach ($images as $image): ?>
                                <div class="image-card">
                                    <?php if ($image['is_active']): ?>
                                        <div class="active-badge">Active</div>
                                    <?php endif; ?>
                                    
                                    <img src="../<?php echo $image['image_path']; ?>" alt="<?php echo $image['title']; ?>">
                                    
                                    <div class="image-card-overlay">
                                        <div class="image-card-title"><?php echo $image['title']; ?></div>
                                        <div class="image-card-date"><?php echo date('M j, Y', strtotime($image['created_at'])); ?></div>
                                    </div>
                                    
                                    <div class="image-card-actions">
                                        <?php if (!$image['is_active']): ?>
                                            <a href="hero-image.php?activate=<?php echo $image['id']; ?>" class="card-btn btn-activate" title="Activate">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            
                                            <a href="hero-image.php?delete=<?php echo $image['id']; ?>" class="card-btn btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this image?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No images found in history.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleSidebar = document.getElementById('toggle-sidebar');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            
            toggleSidebar.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                mainContent.classList.toggle('pushed');
            });
            
            // Handle responsive behavior
            function handleResize() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('pushed');
                } else {
                    sidebar.classList.add('active');
                    mainContent.classList.add('pushed');
                }
            }
            
            // Initial check
            handleResize();
            
            // Listen for window resize
            window.addEventListener('resize', handleResize);
        });
    </script>
</body>
</html>
