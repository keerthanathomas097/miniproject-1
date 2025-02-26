<?php
session_start();
include 'connect.php';

// Check if user is logged in and is a lender
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'lender') {
    header("Location: ls.php");
    exit();
}

// Access lender information
$lender_id = $_SESSION['id'];
$lender_name = $_SESSION['username'];

// Fetch outfits for this lender
$query = "SELECT o.*, d.description_text, 
          s1.subcategory_name as type_name,
          s2.subcategory_name as size_name,
          s3.subcategory_name as brand_name
          FROM tbl_outfit o
          LEFT JOIN tbl_description d ON o.description_id = d.id
          LEFT JOIN tbl_subcategory s1 ON o.type_id = s1.id
          LEFT JOIN tbl_subcategory s2 ON o.size_id = s2.id
          LEFT JOIN tbl_subcategory s3 ON o.brand_id = s3.id
          WHERE o.user_id = ?
          ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lender_id);
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_outfits,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_outfits,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_outfits
    FROM tbl_outfit 
    WHERE user_id = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $lender_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lender Dashboard | Fashion Share</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: rgb(91, 9, 9);
            --secondary:rgb(147, 42, 42);
            --accent:rgb(217, 177, 153);
            --light: #ecf0f1;
            --success: #27ae60;
            --warning: #f1c40f;
            --danger: #e74c3c;
        }

        body {
            background-color: #f5f6fa;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 250px;
            background-color: var(--primary);
            color: white;
            padding: 20px;
            overflow-y: auto;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            margin-top: 30px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 5px;
            margin-bottom: 5px;
            color: white;
            text-decoration: none;
        }

        .menu-item:hover {
            background-color: var(--secondary);
            text-decoration: none;
            color: white;
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            color: var(--secondary);
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
        }

        .recent-activities {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }

        .status-active {
            background-color: var(--success);
            color: white;
        }

        .status-pending {
            background-color: var(--warning);
            color: var(--primary);
        }

        .outfits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .outfit-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .outfit-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .outfit-details {
            padding: 15px;
        }

        .outfit-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin-top: 10px;
        }

        .status-pending { background-color: var(--warning); color: #000; }
        .status-approved { background-color: var(--success); color: white; }
        .status-rejected { background-color: var(--danger); color: white; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Fashion Share</h2>
            <p>Lender Dashboard</p>
        </div>
        <div class="sidebar-menu">
            <div class="menu-item">
                <i class="fas fa-home"></i> Dashboard
            </div>
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

    <div class="main-content">
        <div class="header">
            <h1>Welcome back, <?php echo htmlspecialchars($lender_name); ?>!</h1>
            <p>Here's your outfit lending overview</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Outfits</h3>
                <div class="value"><?php echo $stats['total_outfits']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Approved Outfits</h3>
                <div class="value"><?php echo $stats['approved_outfits']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Approval</h3>
                <div class="value"><?php echo $stats['pending_outfits']; ?></div>
            </div>
        </div>

        <div class="recent-activities">
            <h2>Your Published Outfits</h2>
            <div class="outfits-grid">
                <?php while ($outfit = $result->fetch_assoc()): ?>
                    <div class="outfit-card">
                        <img src="uploads/<?php echo htmlspecialchars($outfit['image1']); ?>" 
                             alt="Outfit Image" 
                             class="outfit-image">
                        <div class="outfit-details">
                            <h4><?php echo htmlspecialchars($outfit['description_text']); ?></h4>
                            <p>Type: <?php echo htmlspecialchars($outfit['type_name']); ?></p>
                            <p>Size: <?php echo htmlspecialchars($outfit['size_name']); ?></p>
                            <p>Brand: <?php echo htmlspecialchars($outfit['brand_name']); ?></p>
                            <p>MRP: ₹<?php echo htmlspecialchars($outfit['mrp']); ?></p>
                            <span class="outfit-status status-<?php echo strtolower($outfit['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($outfit['status'])); ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>