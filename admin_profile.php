<?php
// Start the session at the beginning of the file
session_start();

// Check if user is logged in and is a lender
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: ls.php");
    exit();
}

// Include database connection
require_once 'connect.php';

// Get admin ID from session
$admin_id = $_SESSION['id'];

// Fetch admin details from tbl_users with error handling
$query = "SELECT * FROM tbl_users WHERE user_id = ?";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $admin_id);
if (!$stmt->execute()) {
    die("Error executing query: " . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die("Error getting result: " . $stmt->error);
}

$admin = $result->fetch_assoc();
if (!$admin) {
    die("No admin found with ID: " . $admin_id);
}

$stmt->close();

// Fetch lender statistics from tbl_outfit
// $stats_query = "SELECT 
//     COUNT(*) as total_outfits,
//     SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_outfits,
//     SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_outfits,
//     SUM(CASE WHEN status = 'approved' THEN mrp * 0.2 ELSE 0 END) as potential_earnings
//     FROM tbl_outfit 
//     WHERE user_id = ?";
// $stats_stmt = $conn->prepare($stats_query);
// $stats_stmt->bind_param("i", $lender_id);
// $stats_stmt->execute();
// $stats = $stats_stmt->get_result()->fetch_assoc();

// Handle logout action
if (isset($_POST['logout'])) {
    // Unset all session variables
    $_SESSION = array();
    
    // If you're using cookies to store the session ID, destroy the cookie as well
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: ls.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lender Profile - Fashion Share</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 250px; }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: rgb(91, 9, 9);
            color: white;
            padding-top: 20px;
            z-index: 100;
        }
        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 20px;
        }
        .sidebar-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: 0.3s;
            margin-bottom: 5px;
            border-radius: 5px;
        }
        .sidebar-link:hover { 
            background: rgb(147, 42, 42); 
            color: #ecf0f1; 
        }
        .sidebar-link.active {
            background: rgb(147, 42, 42);
            color: white;
        }
        .sidebar-link i {
            width: 24px;
            text-align: center;
            margin-right: 8px;
        }
        
        /* Responsive behavior */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            .sidebar h4, .sidebar-link span {
                display: none;
            }
            .main-content {
                margin-left: 80px;
            }
            .sidebar-link i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            .sidebar-link {
                text-align: center;
                padding: 15px 5px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar-link {
                display: inline-block;
                width: auto;
            }
            .sidebar h4 {
                display: block;
            }
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: calc(100% - 250px);
        }
        
        .profile-header {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .profile-title h1 {
            margin: 0;
            font-size: 28px;
        }
        
        .profile-title p {
            margin: 5px 0 0;
            color: #666;
        }
        
        .profile-logout form {
            margin: 0;
        }
        
        .logout-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
        }
        
        .logout-btn i {
            margin-right: 8px;
        }
        
        .logout-btn:hover {
            background-color: #d32f2f;
        }
        
        .profile-section {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .profile-section h2 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-item label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #666;
        }
        
        .info-item span {
            font-size: 16px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .verified {
            background-color: #d3f5d3;
            border: 1px solid #a3d1a3;
        }
        
        .pending {
            background-color: #fff3f3;
            border: 1px solid #f5c6c6;
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h4 class="text-center mb-4">Fashion Rental</h4>
        <nav>
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-home"></i> Dashboard</a>
            <a href="user_management.php" class="sidebar-link"><i class="fas fa-users"></i> User Management</a>
            <a href="outfit_management.php" class="sidebar-link"><i class="fas fa-tshirt"></i> Outfit Management</a>
            <a href="orders_admin.php" class="sidebar-link"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="admin_reports.php" class="sidebar-link"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="admin_profile.php" class="sidebar-link active"><i class="fas fa-user"></i> Profile</a>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Profile Header with Logout Button -->
        <div class="profile-header">
            <div class="profile-title">
                <h1>My Profile</h1>
                <p>Welcome, <?php echo htmlspecialchars($admin['name']); ?></p>
            </div>
            <div class="profile-logout">
                <form method="POST">
                    <button type="submit" name="logout" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Personal Information Section -->
        <div class="profile-section">
            <h2>Personal Information</h2>
            <div class="profile-info">
                <div class="info-item">
                    <label>Name</label>
                    <span><?php echo htmlspecialchars($admin['name']); ?></span>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <span><?php echo htmlspecialchars($admin['email']); ?></span>
                </div>
                <div class="info-item">
                    <label>Phone</label>
                    <span><?php echo htmlspecialchars($admin['phone']); ?></span>
                </div>
                <div class="info-item">
                    <label>Role</label>
                    <span><?php echo htmlspecialchars($admin['role']); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Lender Statistics Section -->
        <!-- <div class="profile-section">
            <h2>Lender Statistics</h2>
            <div class="profile-info">
                <div class="info-item">
                    <label>Total Outfits Listed</label>
                    <span><?php echo htmlspecialchars($stats['total_outfits']); ?></span>
                </div>
                <div class="info-item">
                    <label>Approved Outfits</label>
                    <span><?php echo htmlspecialchars($stats['approved_outfits']); ?></span>
                </div>
                <div class="info-item">
                    <label>Pending Approval</label>
                    <span><?php echo htmlspecialchars($stats['pending_outfits']); ?></span>
                </div>
                <div class="info-item">
                    <label>Potential Earnings</label>
                    <span>₹<?php echo number_format($stats['potential_earnings'], 2); ?></span>
                </div>
            </div>
        </div> -->
    </div>
</body>
</html>