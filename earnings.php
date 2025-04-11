<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and include database connection
session_start();
include 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Check if user is a lender
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lender') {
    header('Location: index.php');
    exit;
}

// Get lender details
$userId = $_SESSION['id'];
$lenderEmail = '';

try {
    $lenderStmt = $conn->prepare("SELECT email FROM tbl_users WHERE user_id = ?");
    $lenderStmt->bind_param("i", $userId);
    $lenderStmt->execute();
    $lenderResult = $lenderStmt->get_result();
    
    if ($lenderResult->num_rows > 0) {
        $lenderData = $lenderResult->fetch_assoc();
        $lenderEmail = $lenderData['email'];
    } else {
        throw new Exception("Lender information not found");
    }
    
    $lenderStmt->close();

    // Fetch earnings statistics
    $statsQuery = "SELECT 
                    COUNT(DISTINCT o.outfit_id) as total_rentals,
                    SUM(o.rental_rate) as total_rental_amount,
                    SUM(o.rental_rate) * 0.30 as total_earnings,
                    COUNT(CASE WHEN o.order_status = 'COMPLETED' THEN 1 END) as completed_rentals,
                    COUNT(CASE WHEN o.order_status = 'CONFIRMED' THEN 1 END) as active_rentals,
                    COUNT(CASE WHEN MONTH(o.created_at) = MONTH(CURRENT_DATE()) THEN 1 END) as rentals_this_month,
                    SUM(CASE WHEN MONTH(o.created_at) = MONTH(CURRENT_DATE()) THEN o.rental_rate * 0.30 END) as earnings_this_month
                  FROM (
                    SELECT o.*
                    FROM tbl_orders o
                    INNER JOIN (
                        SELECT outfit_id, MAX(id) as max_id
                        FROM tbl_orders
                        GROUP BY outfit_id
                    ) latest ON o.id = latest.max_id
                    JOIN tbl_outfit outfit ON o.outfit_id = outfit.outfit_id
                    WHERE outfit.email = ? AND o.order_status != 'CANCELLED'
                  ) o";
    
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->bind_param("s", $lenderEmail);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    $stats = $statsResult->fetch_assoc();
    $statsStmt->close();

    // Fetch recent earnings
    $recentQuery = "SELECT 
                        o.id,
                        o.order_reference,
                        o.rental_rate,
                        o.rental_rate * 0.30 as earning_amount,
                        o.created_at,
                        o.order_status,
                        outfit.image1,
                        d.description_text,
                        u.name as renter_name
                    FROM tbl_orders o
                    INNER JOIN (
                        SELECT outfit_id, MAX(id) as max_id
                        FROM tbl_orders
                        GROUP BY outfit_id
                    ) latest ON o.id = latest.max_id
                    JOIN tbl_outfit outfit ON o.outfit_id = outfit.outfit_id
                    LEFT JOIN tbl_description d ON outfit.description_id = d.id
                    JOIN tbl_users u ON o.user_id = u.user_id
                    WHERE outfit.email = ? AND o.order_status != 'CANCELLED'
                    ORDER BY o.created_at DESC
                    LIMIT 10";

    $recentStmt = $conn->prepare($recentQuery);
    $recentStmt->bind_param("s", $lenderEmail);
    $recentStmt->execute();
    $recentResult = $recentStmt->get_result();
    $recentEarnings = $recentResult->fetch_all(MYSQLI_ASSOC);
    $recentStmt->close();

} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings | Fashion Share</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: rgb(91, 9, 9);
            --secondary: rgb(147, 42, 42);
            --accent: rgb(217, 177, 153);
            --light: #ecf0f1;
        }

        body {
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .menu-item.active {
            background-color: var(--secondary);
            border-left: 4px solid var(--accent);
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .earnings-header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .earnings-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 30px;
            color: var(--accent);
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .recent-earnings {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .recent-earnings-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .earnings-table {
            width: 100%;
        }

        .earnings-table th {
            background-color: #f8f9fa;
            padding: 12px;
            font-weight: 600;
            color: #495057;
        }

        .earnings-table td {
            padding: 12px;
            vertical-align: middle;
        }

        .outfit-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .outfit-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-completed {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-confirmed {
            background-color: #cfe2ff;
            color: #084298;
        }

        .earnings-amount {
            font-weight: 600;
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }

            .sidebar-header h2,
            .sidebar-header p,
            .menu-item span {
                display: none;
            }

            .main-content {
                margin-left: 80px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Fashion Share</h2>
            <p>Lender Dashboard</p>
        </div>
        <div class="sidebar-menu">
            <a href="lender_dashboard.php" class="menu-item">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            <a href="lending.php" class="menu-item">
                <i class="fas fa-plus-circle"></i> <span>Lend Outfit</span>
            </a>
            <a href="my_outfits.php" class="menu-item">
                <i class="fas fa-tshirt"></i> <span>My Outfits</span>
            </a>
            <a href="current_rentals.php" class="menu-item">
                <i class="fas fa-exchange-alt"></i> <span>Rentals</span>
            </a>
            <a href="earnings.php" class="menu-item active">
                <i class="fas fa-money-bill-wave"></i> <span>Earnings</span>
            </a>
            <a href="profile.php" class="menu-item">
                <i class="fas fa-user"></i> <span>Profile</span>
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="earnings-header">
            <h1 class="earnings-title">My Earnings</h1>
            <p class="text-muted">Track your earnings from outfit rentals</p>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                <div class="stat-value">₹<?php echo number_format($stats['total_earnings'] ?? 0, 2); ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-value">₹<?php echo number_format($stats['earnings_this_month'] ?? 0, 2); ?></div>
                <div class="stat-label">This Month's Earnings</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-value"><?php echo $stats['total_rentals'] ?? 0; ?></div>
                <div class="stat-label">Total Rentals</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo $stats['active_rentals'] ?? 0; ?></div>
                <div class="stat-label">Active Rentals</div>
            </div>
        </div>

        <!-- Recent Earnings -->
        <div class="recent-earnings">
            <h2 class="recent-earnings-title">Recent Earnings</h2>
            
            <?php if (!empty($recentEarnings)): ?>
                <div class="table-responsive">
                    <table class="earnings-table">
                        <thead>
                            <tr>
                                <th>Outfit</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Rental Amount</th>
                                <th>Your Earnings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentEarnings as $earning): 
                                // Process image path
                                $imagePath = '';
                                if (!empty($earning['image1'])) {
                                    $baseImageNumber = str_replace('_image1.jpg', '', $earning['image1']);
                                    $imagePath = 'uploads/' . $baseImageNumber . '_image1.jpg';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="outfit-info">
                                            <?php if (!empty($imagePath)): ?>
                                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Outfit" class="outfit-image">
                                            <?php else: ?>
                                                <div class="outfit-image d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-tshirt text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($earning['description_text'] ?? 'Unnamed Outfit'); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($earning['order_reference']); ?></td>
                                    <td><?php echo htmlspecialchars($earning['renter_name']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($earning['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($earning['order_status']); ?>">
                                            <?php echo $earning['order_status']; ?>
                                        </span>
                                    </td>
                                    <td>₹<?php echo number_format($earning['rental_rate'], 2); ?></td>
                                    <td class="earnings-amount">₹<?php echo number_format($earning['earning_amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-coins fa-3x text-muted mb-3"></i>
                    <h3>No Earnings Yet</h3>
                    <p class="text-muted">Once your outfits are rented, your earnings will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 