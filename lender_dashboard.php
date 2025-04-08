<?php
session_start();
include 'connect.php';

// Check if user is logged in and is a lender
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    header("Location: ls.php");
    exit();
}

$user_id = $_SESSION['id'];

// Fetch lender details
$lender_query = "SELECT name, email FROM tbl_users WHERE user_id = ?";
$lender_stmt = $conn->prepare($lender_query);

if ($lender_stmt === false) {
    die("Error preparing lender query: " . $conn->error);
}

$lender_stmt->bind_param("i", $user_id);
if (!$lender_stmt->execute()) {
    die("Error executing lender query: " . $lender_stmt->error);
}

$lender_result = $lender_stmt->get_result();
$lender = $lender_result->fetch_assoc();
$lender_stmt->close();

// Fetch active listings count
$listings_query = "SELECT COUNT(*) as count FROM tbl_outfit WHERE email = ?";
$listings_stmt = $conn->prepare($listings_query);

if ($listings_stmt === false) {
    die("Error preparing listings query: " . $conn->error);
}

$listings_stmt->bind_param("s", $lender['email']);
if (!$listings_stmt->execute()) {
    die("Error executing listings query: " . $listings_stmt->error);
}

$listings_result = $listings_stmt->get_result();
$listings_count = $listings_result->fetch_assoc()['count'];
$listings_stmt->close();

// Fetch recent outfits
$activities_query = "SELECT o.outfit_id, o.status, o.mrp, o.image1, d.description_text, 
                    b.subcategory_name as brand_name, t.subcategory_name as type_name,
                    s.subcategory_name as size_name
          FROM tbl_outfit o
          LEFT JOIN tbl_description d ON o.description_id = d.id
                    LEFT JOIN tbl_subcategory b ON o.brand_id = b.id
                    LEFT JOIN tbl_subcategory t ON o.type_id = t.id
                    LEFT JOIN tbl_subcategory s ON o.size_id = s.id
                    WHERE o.email = ? 
                    ORDER BY o.created_at DESC LIMIT 3";
$activities_stmt = $conn->prepare($activities_query);

if ($activities_stmt === false) {
    die("Error preparing activities query: " . $conn->error);
}

$activities_stmt->bind_param("s", $lender['email']);
if (!$activities_stmt->execute()) {
    die("Error executing activities query: " . $activities_stmt->error);
}

$activities_result = $activities_stmt->get_result();
$activities = $activities_result->fetch_all(MYSQLI_ASSOC);
$activities_stmt->close();

// Add this before the outfits-grid div to check image paths
echo "<!-- Debug Info:\n";
foreach ($activities as $activity) {
    $baseImageNumber = str_replace('_image1.jpg', '', $activity['image1']);
    $imagePath = 'uploads/' . $baseImageNumber . '_image1.jpg';
    echo "Original image1: " . $activity['image1'] . "\n";
    echo "Constructed path: " . $imagePath . "\n";
}
echo "-->";
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            padding: 10px 0;
            justify-items: left;
        }

        .outfit-dashboard-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            width: 85%;
            max-width: 400px;
            margin: 0 auto;
        }

        .outfit-dashboard-card:hover {
            transform: translateY(-5px);
        }

        .outfit-image-container {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
        }

        .outfit-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .outfit-dashboard-card:hover .outfit-image {
            transform: scale(1.05);
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 8px 15px;
            border-radius: 20px;
            color: white;
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: capitalize;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .status-pending { background-color: var(--warning); }
        .status-approved { background-color: var(--success); }
        .status-rejected { background-color: var(--danger); }

        .outfit-details {
            padding: 15px;
        }

        .outfit-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .outfit-info p {
            margin: 5px 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .outfit-info i {
            width: 20px;
            color: var(--secondary);
        }

        .price-info {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .price-info .mrp {
            color: #888;
            text-decoration: line-through;
            font-size: 0.9rem;
        }

        .price-info .rental {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .no-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            color: #6c757d;
            font-size: 16px;
        }
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
            <a href="my_outfits.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-tshirt"></i> My Outfits
            </a>
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
            <h1>Welcome back, <?php echo htmlspecialchars($lender['name'] ?? 'Lender'); ?>!</h1>
            <p>Here's your outfit lending overview</p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Active Listings</h3>
                <div class="value"><?php echo $listings_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Current Rentals</h3>
                <div class="value">-</div>
            </div>
            <div class="stat-card">
                <h3>Total Earnings</h3>
                <div class="value">₹-</div>
            </div>
            <div class="stat-card">
                <h3>Rating</h3>
                <div class="value">- ⭐</div>
            </div>
        </div>

        <div class="recent-activities">
            <h2>My Recent Outfits</h2>
            <div class="outfits-grid">
                <?php if (empty($activities)): ?>
                    <p>No outfits listed yet</p>
                <?php else: ?>
                    <?php foreach ($activities as $activity): 
                        // Calculate rental price (20% of MRP)
                        $rental_price = $activity['mrp'] * 0.20;
                        
                        // Handle image path - using the same logic as outfit.php
                        $imagePath = '';
                        if (!empty($activity['image1'])) {
                            $baseImageNumber = str_replace('_image1.jpg', '', $activity['image1']);
                            $imagePath = 'uploads/' . $baseImageNumber . '_image1.jpg';
                        }
                    ?>
                        <div class="outfit-dashboard-card">
                            <div class="outfit-image-container">
                                <?php if (!empty($imagePath)): ?>
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                         alt="<?php echo htmlspecialchars($activity['description_text']); ?>"
                             class="outfit-image">
                                <?php else: ?>
                                    <div class="no-image-placeholder">No Image Available</div>
                                <?php endif; ?>
                                <span class="status-badge status-<?php echo strtolower($activity['status']); ?>">
                                    <?php echo htmlspecialchars($activity['status']); ?>
                                </span>
                            </div>
                        <div class="outfit-details">
                                <h3 class="outfit-title"><?php echo htmlspecialchars($activity['description_text']); ?></h3>
                                <div class="outfit-info">
                                    <p><i class="fas fa-tag"></i> <?php echo htmlspecialchars($activity['brand_name']); ?></p>
                                    <p><i class="fas fa-tshirt"></i> <?php echo htmlspecialchars($activity['type_name']); ?></p>
                                    <p><i class="fas fa-ruler"></i> Size: <?php echo htmlspecialchars($activity['size_name']); ?></p>
                                    <div class="price-info">
                                        <p class="mrp">MRP: ₹<?php echo number_format($activity['mrp'], 2); ?></p>
                                        <p class="rental">Rental: ₹<?php echo number_format($rental_price, 2); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>