<?php
require_once '../config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$error = '';
$success = '';
$debug_message = '';

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Determine which section to display
$section = isset($_GET['section']) && in_array($_GET['section'], ['users', 'services', 'hero_image']) ? $_GET['section'] : 'messages';

// Fetch contact messages
$stmt = $conn->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 10");
if (!$stmt) {
    $error = "Error preparing contact messages query: " . $conn->error;
    error_log($error);
} else {
    if (!$stmt->execute()) {
        $error = "Error executing contact messages query: " . $stmt->error;
        error_log($error);
    } else {
        $messages_result = $stmt->get_result();
        $messages = [];
        while ($message = $messages_result->fetch_assoc()) {
            $messages[] = $message;
        }
        $debug_message = "Fetched " . count($messages) . " contact messages.";
        if (empty($messages)) {
            error_log("No contact messages found in the database.");
        }
        $stmt->close();
    }
}

// Handle View Message
$view_message = null;
if (isset($_GET['view_message']) && is_numeric($_GET['view_message'])) {
    $message_id = $_GET['view_message'];
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $view_message = $result->fetch_assoc();
    }
    $stmt->close();
}

// Fetch services
$stmt = $conn->prepare("SELECT s.*, c.name as category_name FROM services s JOIN service_categories c ON s.category_id = c.id ORDER BY s.name");
$stmt->execute();
$services_result = $stmt->get_result();
$services = [];
while ($service = $services_result->fetch_assoc()) {
    $services[] = $service;
}
$stmt->close();

// Fetch users
$stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$users_result = $stmt->get_result();
$users = [];
while ($user = $users_result->fetch_assoc()) {
    $users[] = $user;
}
$stmt->close();

// Fetch categories
$stmt = $conn->prepare("SELECT id, name FROM service_categories ORDER BY name");
$stmt->execute();
$categories_result = $stmt->get_result();
$categories = [];
while ($category = $categories_result->fetch_assoc()) {
    $categories[] = $category;
}
$stmt->close();

// Handle Add New Service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $csrf_token) {
    $name = sanitize_input($_POST['name']);
    $category_id = sanitize_input($_POST['category_id']);
    $description = sanitize_input($_POST['description']);
    $price = sanitize_input($_POST['price']);

    if (empty($name) || empty($category_id) || empty($description) || empty($price)) {
        $error = "All service fields are required.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Price must be a valid positive number.";
    } else {
        $stmt = $conn->prepare("INSERT INTO services (name, category_id, description, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sisd", $name, $category_id, $description, $price);

        if ($stmt->execute()) {
            $success = "Service added successfully.";
            header("Location: index.php?section=services");
            exit;
        } else {
            $error = "Error adding service: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Edit Service
$edit_service = null;
if (isset($_GET['edit_service']) && is_numeric($_GET['edit_service'])) {
    $service_id = $_GET['edit_service'];
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_service = $result->fetch_assoc();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_service']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $csrf_token) {
    $service_id = $_POST['service_id'];
    $name = sanitize_input($_POST['name']);
    $category_id = sanitize_input($_POST['category_id']);
    $description = sanitize_input($_POST['description']);
    $price = sanitize_input($_POST['price']);

    if (empty($name) || empty($category_id) || empty($description) || empty($price)) {
        $error = "All service fields are required.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Price must be a valid positive number.";
    } else {
        $stmt = $conn->prepare("UPDATE services SET name = ?, category_id = ?, description = ?, price = ? WHERE id = ?");
        $stmt->bind_param("sisdi", $name, $category_id, $description, $price, $service_id);

        if ($stmt->execute()) {
            $success = "Service updated successfully.";
            header("Location: index.php?section=services");
            exit;
        } else {
            $error = "Error updating service: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Delete Service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_service']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $csrf_token) {
    $service_id = $_POST['service_id'];
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);

    if ($stmt->execute()) {
        $success = "Service deleted successfully.";
        header("Location: index.php?section=services");
        exit;
    } else {
        $error = "Error deleting service: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Add New User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $csrf_token) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $role = sanitize_input($_POST['role']);
    $password = password_hash(sanitize_input($_POST['password']), PASSWORD_DEFAULT);

    if (empty($name) || empty($email) || empty($phone) || empty($role) || empty($_POST['password'])) {
        $error = "All user fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, role, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $name, $email, $phone, $role, $password);

        if ($stmt->execute()) {
            $success = "User added successfully.";
            header("Location: index.php?section=users");
            exit;
        } else {
            $error = "Error adding user: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Edit User
$edit_user = null;
if (isset($_GET['edit_user']) && is_numeric($_GET['edit_user'])) {
    $user_id = $_GET['edit_user'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_user = $result->fetch_assoc();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $csrf_token) {
    $user_id = $_POST['user_id'];
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $role = sanitize_input($_POST['role']);
    $password = !empty($_POST['password']) ? password_hash(sanitize_input($_POST['password']), PASSWORD_DEFAULT) : null;

    if (empty($name) || empty($email) || empty($phone) || empty($role)) {
        $error = "All user fields (except password) are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        if ($password) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $name, $email, $phone, $role, $password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $name, $email, $phone, $role, $user_id);
        }

        if ($stmt->execute()) {
            $success = "User updated successfully.";
            header("Location: index.php?section=users");
            exit;
        } else {
            $error = "Error updating user: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $csrf_token) {
    $user_id = $_POST['user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $success = "User deleted successfully.";
        header("Location: index.php?section=users");
        exit;
    } else {
        $error = "Error deleting user: " . $stmt->error;
    }
    $stmt->close();
}

// Hero Image Management
$current_image = null;
$images = [];
if ($section == 'hero_image') {
    // Get current hero image
    $stmt = $conn->prepare("SELECT * FROM hero_images WHERE is_active = TRUE ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $current_image = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Process image upload
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_hero_image']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $csrf_token) {
        if (isset($_FILES["hero_image"]) && $_FILES["hero_image"]["error"] == 0) {
            $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png"];
            $filename = $_FILES["hero_image"]["name"];
            $filetype = $_FILES["hero_image"]["type"];
            $filesize = $_FILES["hero_image"]["size"];

            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (!array_key_exists($ext, $allowed)) {
                $error = "Error: Please select a valid file format (jpg, jpeg, gif, png).";
            }

            $maxsize = 5 * 1024 * 1024;
            if ($filesize > $maxsize) {
                $error = "Error: File size is larger than the allowed limit (5MB).";
            }

            if (empty($error) && in_array($filetype, $allowed)) {
                $target_dir = "../Uploads/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $new_filename = uniqid() . "." . $ext;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($_FILES["hero_image"]["tmp_name"], $target_file)) {
                    $image_path = "Uploads/" . $new_filename;
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
            } else if (empty($error)) {
                $error = "Error: Invalid file type.";
            }
        } else {
            $error = "Error: " . ($_FILES["hero_image"]["error"] ?? "No file uploaded.");
        }
    }

    // Fetch all hero images
    $stmt = $conn->prepare("SELECT * FROM hero_images ORDER BY created_at DESC");
    $stmt->execute();
    $all_images = $stmt->get_result();
    $images = [];
    while ($image = $all_images->fetch_assoc()) {
        $images[] = $image;
    }
    $stmt->close();

    // Handle Activate Image
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
            $stmt->close();

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

    // Handle Delete Image
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $image_id = $_GET['delete'];

        $stmt = $conn->prepare("SELECT image_path, is_active FROM hero_images WHERE id = ?");
        $stmt->bind_param("i", $image_id);
        $stmt->execute();
        $image_to_delete = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($image_to_delete['is_active']) {
            $error = "Cannot delete the active hero image. Please activate another image first.";
        } else {
            $stmt = $conn->prepare("DELETE FROM hero_images WHERE id = ?");
            $stmt->bind_param("i", $image_id);

            if ($stmt->execute()) {
                $file_path = "../" . $image_to_delete['image_path'];
                if (file_exists($file_path) && $image_to_delete['image_path'] != '/placeholder.svg?height=400&width=500') {
                    unlink($file_path);
                }

                $success = "Hero image deleted successfully.";
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
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LokLagbe</title>
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

        .menu-item:hover,
        .menu-item.active {
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

        .dashboard-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .form-container {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 20px 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 500;
            color: var(--text-light);
        }

        .messages-table,
        .services-table,
        .users-table {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
            cursor: pointer;
        }

        .btn-view {
            background-color: rgba(33, 150, 243, 0.1);
            color: var(--info-color);
        }

        .btn-edit {
            background-color: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
            padding: 6px 12px;
        }

        .btn-delete:hover {
            background-color: #d32f2f;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-primary:hover {
            background-color: #164a19;
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

        .debug-message {
            background-color: rgba(33, 150, 243, 0.1);
            color: var(--info-color);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .message-view {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
        }

        /* Hero Image Styles */
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

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 1rem;
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
            width: 60px;
            height: 30px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            font-size: 0.8rem;
            text-decoration: none;
        }

        .btn-activate {
            background-color: var(--success-color);
        }

        .btn-delete {
            background-color: var(--danger-color);
        }

        .card-btn.btn-delete:hover {
            background-color: #d32f2f;
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
                <a href="index.php" class="menu-item <?php echo $section == 'messages' ? 'active' : ''; ?>"><i
                        class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="index.php?section=users"
                    class="menu-item <?php echo $section == 'users' ? 'active' : ''; ?>"><i class="fas fa-users"></i>
                    Users</a>
                <a href="index.php?section=services"
                    class="menu-item <?php echo $section == 'services' ? 'active' : ''; ?>"><i class="fas fa-tools"></i>
                    Services</a>
                <a href="index.php?section=hero_image"
                    class="menu-item <?php echo $section == 'hero_image' ? 'active' : ''; ?>"><i
                        class="fas fa-image"></i> Hero Image</a>
                <a href="bookings.php" class="menu-item"><i class="fas fa-calendar-check"></i> Bookings</a>
                <a href="reviews.php" class="menu-item"><i class="fas fa-star"></i> Reviews</a>
                <a href="../index.php" class="menu-item"><i class="fas fa-home"></i> Back to Website</a>
                <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <!-- Header -->
            <header class="header">
                <button class="toggle-sidebar" id="toggle-sidebar"><i class="fas fa-bars"></i></button>
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <h1 class="page-title">Admin Dashboard</h1>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($debug_message) && $section == 'messages'): ?>
                    <div class="debug-message"><?php echo htmlspecialchars($debug_message); ?></div>
                <?php endif; ?>

                <?php if ($section == 'messages'): ?>
                    <!-- Contact Messages -->
                    <div class="dashboard-section">
                        <h2 class="section-title">Contact Messages</h2>
                        <div class="messages-table">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Message</th>
                                            <th>Received</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($messages) > 0): ?>
                                            <?php foreach ($messages as $message): ?>
                                                <tr>
                                                    <td><?php echo $message['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($message['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($message['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($message['phone']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($message['message'], 0, 50)) . (strlen($message['message']) > 50 ? '...' : ''); ?>
                                                    </td>
                                                    <td><?php echo date('M j, Y H:i', strtotime($message['created_at'])); ?></td>
                                                    <td>
                                                        <a href="index.php?view_message=<?php echo $message['id']; ?>"
                                                            class="action-btn btn-view"><i class="fas fa-eye"></i> View</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" style="text-align: center;">No messages found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ($view_message): ?>
                            <div class="message-view">
                                <h3>Message Details: <?php echo htmlspecialchars($view_message['name']); ?></h3>
                                <p><strong>ID:</strong> <?php echo $view_message['id']; ?></p>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($view_message['name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($view_message['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($view_message['phone']); ?></p>
                                <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($view_message['message'])); ?>
                                </p>
                                <p><strong>Received:</strong>
                                    <?php echo date('M j, Y H:i', strtotime($view_message['created_at'])); ?></p>
                                <a href="index.php" class="btn btn-primary">Back to Messages</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($section == 'services'): ?>
                    <!-- Add New Service -->
                    <div class="dashboard-section">
                        <h2 class="section-title">Add New Service</h2>
                        <div class="form-container">
                            <form method="POST" action="index.php?section=services">
                                <input type="hidden" name="add_service" value="1">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <div class="form-group">
                                    <label for="service_name">Service Name</label>
                                    <input type="text" id="service_name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="category_id">Category</label>
                                    <select id="category_id" name="category_id" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="price">Price (BDT)</label>
                                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Service</button>
                            </form>
                        </div>
                    </div>

                    <!-- Manage Services -->
                    <div class="dashboard-section">
                        <h2 class="section-title">Manage Services</h2>
                        <div class="services-table">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($services) > 0): ?>
                                            <?php foreach ($services as $service): ?>
                                                <tr>
                                                    <td><?php echo $service['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($service['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($service['category_name']); ?></td>
                                                    <td>৳<?php echo number_format($service['price'], 2); ?></td>
                                                    <td>
                                                        <a href="index.php?section=services&edit_service=<?php echo $service['id']; ?>"
                                                            class="action-btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                                        <form method="POST" action="index.php?section=services"
                                                            style="display: inline;"
                                                            onsubmit="return confirm('Are you sure you want to delete this service?');">
                                                            <input type="hidden" name="delete_service" value="1">
                                                            <input type="hidden" name="csrf_token"
                                                                value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="service_id"
                                                                value="<?php echo $service['id']; ?>">
                                                            <button type="submit" class="action-btn btn-delete">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" style="text-align: center;">No services found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ($edit_service): ?>
                            <div class="form-container">
                                <h3>Edit Service: <?php echo htmlspecialchars($edit_service['name']); ?></h3>
                                <form method="POST" action="index.php?section=services">
                                    <input type="hidden" name="edit_service" value="1">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="service_id" value="<?php echo $edit_service['id']; ?>">
                                    <div class="form-group">
                                        <label for="edit_service_name">Service Name</label>
                                        <input type="text" id="edit_service_name" name="name"
                                            value="<?php echo htmlspecialchars($edit_service['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_category_id">Category</label>
                                        <select id="edit_category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo $edit_service['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_description">Description</label>
                                        <textarea id="edit_description" name="description"
                                            required><?php echo htmlspecialchars($edit_service['description']); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_price">Price (BDT)</label>
                                        <input type="number" id="edit_price" name="price" step="0.01" min="0"
                                            value="<?php echo htmlspecialchars($edit_service['price']); ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Service</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($section == 'users'): ?>
                    <!-- Add New User -->
                    <div class="dashboard-section">
                        <h2 class="section-title">Add New User</h2>
                        <div class="form-container">
                            <form method="POST" action="index.php?section=users">
                                <input type="hidden" name="add_user" value="1">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <div class="form-group">
                                    <label for="user_name">Name</label>
                                    <input type="text" id="user_name" name="name" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="tel" id="phone" name="phone" required>
                                </div>
                                <div class="form-group">
                                    <label for="role">Role</label>
                                    <select id="role" name="role" required>
                                        <option value="">Select Role</option>
                                        <option value="admin">Admin</option>
                                        <option value="user">User</option>
                                        <option value="provider">Provider</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Add User</button>
                            </form>
                        </div>
                    </div>

                    <!-- Manage Users -->
                    <div class="dashboard-section">
                        <h2 class="section-title">Manage Users</h2>
                        <div class="users-table">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($users) > 0): ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo $user['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo ucfirst($user['role']); ?></td>
                                                    <td>
                                                        <a href="index.php?section=users&edit_user=<?php echo $user['id']; ?>"
                                                            class="action-btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                                        <form method="POST" action="index.php?section=users"
                                                            style="display: inline;"
                                                            onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                            <input type="hidden" name="delete_user" value="1">
                                                            <input type="hidden" name="csrf_token"
                                                                value="<?php echo $csrf_token; ?>">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" class="action-btn btn-delete">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" style="text-align: center;">No users found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ($edit_user): ?>
                            <div class="form-container">
                                <h3>Edit User: <?php echo htmlspecialchars($edit_user['name']); ?></h3>
                                <form method="POST" action="index.php?section=users">
                                    <input type="hidden" name="edit_user" value="1">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                                    <div class="form-group">
                                        <label for="edit_user_name">Name</label>
                                        <input type="text" id="edit_user_name" name="name"
                                            value="<?php echo htmlspecialchars($edit_user['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_email">Email</label>
                                        <input type="email" id="edit_email" name="email"
                                            value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_phone">Phone</label>
                                        <input type="tel" id="edit_phone" name="phone"
                                            value="<?php echo htmlspecialchars($edit_user['phone']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_role">Role</label>
                                        <select id="edit_role" name="role" required>
                                            <option value="admin" <?php echo $edit_user['role'] == 'admin' ? 'selected' : ''; ?>>
                                                Admin</option>
                                            <option value="user" <?php echo $edit_user['role'] == 'user' ? 'selected' : ''; ?>>
                                                User</option>
                                            <option value="provider" <?php echo $edit_user['role'] == 'provider' ? 'selected' : ''; ?>>Provider</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="edit_password">Password (leave blank to keep unchanged)</label>
                                        <input type="password" id="edit_password" name="password">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update User</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($section == 'hero_image'): ?>
                    <!-- Hero Image Management -->
                    <div class="dashboard-section">
                        <h2 class="section-title">Manage Hero Image</h2>
                        <div class="hero-image-container">
                            <div class="current-image">
                                <h3>Current Hero Image</h3>
                                <?php if ($current_image): ?>
                                    <img src="../<?php echo htmlspecialchars($current_image['image_path']); ?>"
                                        alt="Current Hero Image" class="image-preview">
                                    <div class="image-details">
                                        <div class="detail-row">
                                            <div class="detail-label">Title:</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($current_image['title']); ?>
                                            </div>
                                        </div>
                                        <div class="detail-row">
                                            <div class="detail-label">Uploaded:</div>
                                            <div class="detail-value">
                                                <?php echo date('F j, Y, g:i a', strtotime($current_image['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p>No hero image set. Please upload one.</p>
                                <?php endif; ?>
                            </div>

                            <div class="upload-form">
                                <h3>Upload New Hero Image</h3>
                                <form action="index.php?section=hero_image" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="upload_hero_image" value="1">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <div class="form-group">
                                        <label for="hero_image">Select Image</label>
                                        <input type="file" id="hero_image" name="hero_image" class="form-control" required
                                            accept="image/*">
                                        <small>Recommended size: 1000x500 pixels. Max file size: 5MB.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="title">Image Title</label>
                                        <input type="text" id="title" name="title" class="form-control" required
                                            placeholder="Enter a descriptive title">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Upload Image</button>
                                </form>
                            </div>
                        </div>

                        <div class="image-history">
                            <h3>Image History</h3>
                            <?php if (count($images) > 0): ?>
                                <div class="image-grid">
                                    <?php foreach ($images as $image): ?>
                                        <div class="image-card">
                                            <?php if ($image['is_active']): ?>
                                                <div class="active-badge">Active</div>
                                            <?php endif; ?>
                                            <img src="../<?php echo htmlspecialchars($image['image_path']); ?>"
                                                alt="<?php echo htmlspecialchars($image['title']); ?>">
                                            <div class="image-card-overlay">
                                                <div class="image-card-title"><?php echo htmlspecialchars($image['title']); ?></div>
                                                <div class="image-card-date">
                                                    <?php echo date('M j, Y', strtotime($image['created_at'])); ?>
                                                </div>
                                            </div>
                                            <div class="image-card-actions">
                                                <?php if (!$image['is_active']): ?>
                                                    <a href="index.php?section=hero_image&activate=<?php echo $image['id']; ?>"
                                                        class="card-btn btn-activate" title="Activate">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="index.php?section=hero_image&delete=<?php echo $image['id']; ?>"
                                                        class="card-btn btn-delete" title="Delete"
                                                        onclick="return confirm('Are you sure you want to delete this image?')">
                                                        Delete
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
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggleSidebar = document.getElementById('toggle-sidebar');
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');

            // Sidebar toggle functionality
            toggleSidebar.addEventListener('click', function () {
                sidebar.classList.toggle('active');
                mainContent.classList.toggle('pushed');
            });

            // Responsive sidebar behavior
            function handleResize() {
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                    mainContent.classList.remove('pushed');
                } else {
                    sidebar.classList.add('active');
                    mainContent.classList.add('pushed');
                }
            }

            handleResize();
            window.addEventListener('resize', handleResize);
        });
    </script>
</body>

</html>