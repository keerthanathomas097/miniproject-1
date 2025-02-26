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

// Get lender ID from session
$admin_id = $_SESSION['id'];

// Fetch lender details from tbl_users
$query = "SELECT *, DATE_FORMAT(created_at, '%M %d, %Y') as join_date 
          FROM tbl_users 
          WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            display: flex;
            background-color: #f5f5f5;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color:rgb(91, 9, 9);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 5px #444;
            position: fixed;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgb(147, 42, 42);
        }
        
        .sidebar-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .sidebar-header p {
            margin: 5px 0 0;
            opacity: 0.7;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 12px 20px;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
        }
        
        .menu-item:hover {
            background-color:rgb(147, 42, 42);
        }
        
        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
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
        <div class="sidebar-header">
            <h2>Fashion Share</h2>
            <p>Lender Dashboard</p>
        </div>
        <div class="sidebar-menu">
        <a href="lender_dashboard.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="lending.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-plus-circle"></i> Lend Outfit
            </a>
            <div class="menu-item">
                <i class="fas fa-tshirt"></i> My Outfits
            </div>
            <div class="menu-item">
                <i class="fas fa-exchange-alt"></i> Rentals
            </div>
            <div class="menu-item">
                <i class="fas fa-money-bill-wave"></i> Earnings
            </div>
            <a href="lender_profile.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-user"></i> Profile
            </a>
            <div class="menu-item">
                <i class="fas fa-cog"></i> Settings
            </div>
        </div>
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
                    <label>Admin Since</label>
                    <span><?php echo htmlspecialchars($admin['join_date']); ?></span>
                </div>
                <div class="info-item">
                    
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