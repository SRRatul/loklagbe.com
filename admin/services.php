<?php
require_once '../config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

// Handle service deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $service_id = $_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $service_id);
    
    if ($stmt->execute()) {
        $success = "Service deleted successfully.";
    } else {
        $error = "Error deleting service: " . $stmt->error;
    }
    $stmt->close();
}

// Get all services
$stmt = $conn->prepare("
    SELECT s.*, c.name as category_name
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - LokLagbe Admin</title>
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
        
        /* Services Page Styles */
        .page-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background-color: var(--card-bg);
            border-radius: 5px;
            padding: 5px 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .search-box input {
            border: none;
            padding: 8px;
            font-size: 0.9rem;
            outline: none;
            width: 250px;
        }
        
        .search-box i {
            color: var(--text-light);
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: #164a19;
        }
        
        .services-table {
            background-color: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background-color: rgba(0, 0, 0, 0.02);
            font-weight: 500;
            color: var(--text-light);
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .service-category {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
            background-color: rgba(27, 94, 32, 0.1);
            color: var(--primary-color);
        }
        
        .service-price {
            font-weight: 500;
            color: var(--secondary-color);
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
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
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
            
            .page-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-box input {
                width: 100%;
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
                <a href="services.php" class="menu-item active">
                    <i class="fas fa-tools"></i> Services
                </a>
                <a href="bookings.php" class="menu-item">
                    <i class="fas fa-calendar-check"></i> Bookings
                </a>
                <a href="reviews.php" class="menu-item">
                    <i class="fas fa-star"></i> Reviews
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
                <h1 class="page-title">Manage Services</h1>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="page-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search services...">
                    </div>
                    
                    <a href="add-service.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Service
                    </a>
                </div>
                
                <div class="services-table">
                    <div class="table-responsive">
                        <table id="servicesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($services) > 0): ?>
                                    <?php foreach ($services as $service): ?>
                                        <tr>
                                            <td><?php echo $service['id']; ?></td>
                                            <td><?php echo $service['name']; ?></td>
                                            <td>
                                                <span class="service-category">
                                                    <?php echo $service['category_name']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo substr($service['description'], 0, 50) . (strlen($service['description']) > 50 ? '...' : ''); ?></td>
                                            <td class="service-price">৳<?php echo number_format($service['price'], 2); ?></td>
                                            <td>
                                                <a href="edit-service.php?id=<?php echo $service['id']; ?>" class="action-btn btn-edit">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="services.php?delete=<?php echo $service['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this service?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">No services found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
            
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            const servicesTable = document.getElementById('servicesTable');
            const rows = servicesTable.getElementsByTagName('tr');
            
            searchInput.addEventListener('keyup', function() {
                const searchTerm = searchInput.value.toLowerCase();
                
                for (let i = 1; i < rows.length; i++) {
                    let found = false;
                    const cells = rows[i].getElementsByTagName('td');
                    
                    for (let j = 0; j < cells.length; j++) {
                        const cellText = cells[j].textContent.toLowerCase();
                        
                        if (cellText.indexOf(searchTerm) > -1) {
                            found = true;
                            break;
                        }
                    }
                    
                    if (found) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>

