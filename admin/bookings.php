<?php
require_once '../config.php';


if (!is_logged_in() || !is_admin()) {
    redirect('../login.php');
}

$error = '';
$success = '';

if (isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['status'])) {
    $booking_id = $_GET['id'];
    $status = $_GET['status'];
    
    if (in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        
        if ($stmt->execute()) {
            $success = "Booking status updated successfully.";
        } else {
            $error = "Error updating booking status: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Invalid status value.";
    }
}


$stmt = $conn->prepare("
    SELECT b.*, u.name as user_name, s.name as service_name, 
           sp.id as provider_id, prov.name as provider_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN services s ON b.service_id = s.id
    JOIN service_providers sp ON b.provider_id = sp.id
    JOIN users prov ON sp.user_id = prov.id
    ORDER BY b.created_at DESC
");
$stmt->execute();
$bookings_result = $stmt->get_result();
$bookings = [];
while ($booking = $bookings_result->fetch_assoc()) {
    $bookings[] = $booking;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - LokLagbe Admin</title>
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
        
        /* Bookings Page Styles */
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
        
        .filter-dropdown {
            position: relative;
            margin-left: 10px;
        }
        
        .filter-btn {
            background-color: var(--card-bg);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .filter-btn i {
            margin-right: 5px;
        }
        
        .filter-dropdown-content {
            display: none;
            position: absolute;
            background-color: var(--card-bg);
            min-width: 160px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1;
            border-radius: 5px;
            top: 100%;
            right: 0;
            margin-top: 5px;
        }
        
        .filter-dropdown-content a {
            color: var(--text-color);
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            font-size: 0.9rem;
        }
        
        .filter-dropdown-content a:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .filter-dropdown:hover .filter-dropdown-content {
            display: block;
        }
        
        .bookings-table {
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
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-pending {
            background-color: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
        }
        
        .status-confirmed {
            background-color: rgba(33, 150, 243, 0.1);
            color: var(--info-color);
        }
        
        .status-completed {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }
        
        .status-cancelled {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
        }
        
        .booking-price {
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
        
        .btn-confirm {
            background-color: rgba(33, 150, 243, 0.1);
            color: var(--info-color);
        }
        
        .btn-complete {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }
        
        .btn-cancel {
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
                <a href="services.php" class="menu-item">
                    <i class="fas fa-tools"></i> Services
                </a>
                <a href="bookings.php" class="menu-item active">
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
                <h1 class="page-title">Manage Bookings</h1>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="page-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search bookings...">
                    </div>
                    
                    <div class="filter-dropdown">
                        <button class="filter-btn">
                            <i class="fas fa-filter"></i> Filter by Status
                        </button>
                        <div class="filter-dropdown-content">
                            <a href="#" data-status="all">All Bookings</a>
                            <a href="#" data-status="pending">Pending</a>
                            <a href="#" data-status="confirmed">Confirmed</a>
                            <a href="#" data-status="completed">Completed</a>
                            <a href="#" data-status="cancelled">Cancelled</a>
                        </div>
                    </div>
                </div>
                
                <div class="bookings-table">
                    <div class="table-responsive">
                        <table id="bookingsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Provider</th>
                                    <th>Date & Time</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($bookings) > 0): ?>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr data-status="<?php echo $booking['status']; ?>">
                                            <td>#<?php echo $booking['id']; ?></td>
                                            <td><?php echo $booking['user_name']; ?></td>
                                            <td><?php echo $booking['service_name']; ?></td>
                                            <td><?php echo $booking['provider_name']; ?></td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?> at 
                                                <?php echo date('g:i A', strtotime($booking['booking_time'])); ?>
                                            </td>
                                            <td class="booking-price">৳<?php echo number_format($booking['price'], 2); ?></td>
                                            <td>
                                                <span class="status status-<?php echo $booking['status']; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="action-btn btn-view">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <a href="bookings.php?id=<?php echo $booking['id']; ?>&status=confirmed" class="action-btn btn-confirm" onclick="return confirm('Confirm this booking?')">
                                                        <i class="fas fa-check"></i> Confirm
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($booking['status'] === 'confirmed'): ?>
                                                    <a href="bookings.php?id=<?php echo $booking['id']; ?>&status=completed" class="action-btn btn-complete" onclick="return confirm('Mark this booking as completed?')">
                                                        <i class="fas fa-check-double"></i> Complete
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($booking['status'] === 'pending' || $booking['status'] === 'confirmed'): ?>
                                                    <a href="bookings.php?id=<?php echo $booking['id']; ?>&status=cancelled" class="action-btn btn-cancel" onclick="return confirm('Cancel this booking?')">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center;">No bookings found</td>
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
            

            handleResize();
            

            window.addEventListener('resize', handleResize);
            

            const searchInput = document.getElementById('searchInput');
            const bookingsTable = document.getElementById('bookingsTable');
            const rows = bookingsTable.getElementsByTagName('tr');
            
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
            

            const filterLinks = document.querySelectorAll('.filter-dropdown-content a');
            
            filterLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const status = this.getAttribute('data-status');
                    
                    for (let i = 1; i < rows.length; i++) {
                        if (status === 'all') {
                            rows[i].style.display = '';
                        } else {
                            const rowStatus = rows[i].getAttribute('data-status');
                            
                            if (rowStatus === status) {
                                rows[i].style.display = '';
                            } else {
                                rows[i].style.display = 'none';
                            }
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>

