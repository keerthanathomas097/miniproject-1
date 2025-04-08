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

// Determine timeframe for filtering data
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'month';
$custom_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$custom_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Set date range based on timeframe
$current_date = date('Y-m-d');
$start_date = '';
$end_date = $current_date;

switch($timeframe) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $start_date = date('Y-m-01'); // First day of current month
        break;
    case 'year':
        $start_date = date('Y-01-01'); // First day of current year
        break;
    case 'custom':
        $start_date = $custom_start;
        $end_date = $custom_end ?: $current_date;
        break;
    default:
        $start_date = date('Y-m-01');
        break;
}

// Fetch outfit statistics
$outfit_stats_query = "SELECT 
        COUNT(*) as total_outfits,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_outfits,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_outfits,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_outfits
    FROM tbl_outfit 
    WHERE email = ?";

$outfit_stats_stmt = $conn->prepare($outfit_stats_query);
if ($outfit_stats_stmt === false) {
    die("Error preparing outfit stats query: " . $conn->error);
}

$outfit_stats_stmt->bind_param("s", $lender['email']);
if (!$outfit_stats_stmt->execute()) {
    die("Error executing outfit stats query: " . $outfit_stats_stmt->error);
}

$outfit_stats_result = $outfit_stats_stmt->get_result();
$outfit_stats = $outfit_stats_result->fetch_assoc();
$outfit_stats_stmt->close();

// Fetch earnings data (using actual rentals and calculating lender's 30%)
$earnings_query = "SELECT 
        o.id as order_id, 
        o.order_reference, 
        o.amount, 
        o.rental_rate,
        o.security_deposit,
        o.order_status,
        o.created_at as order_date,
        outfit.outfit_id,
        d.description_text,
        u.name as renter_name
    FROM tbl_orders o
    JOIN tbl_outfit outfit ON o.outfit_id = outfit.outfit_id
    LEFT JOIN tbl_description d ON outfit.description_id = d.id
    LEFT JOIN tbl_users u ON o.user_id = u.user_id
    WHERE outfit.email = ? 
    AND o.created_at BETWEEN ? AND ?
    ORDER BY o.created_at DESC";

$earnings_stmt = $conn->prepare($earnings_query);
if ($earnings_stmt === false) {
    die("Error preparing earnings query: " . $conn->error);
}

$earnings_stmt->bind_param("sss", $lender['email'], $start_date, $end_date);
if (!$earnings_stmt->execute()) {
    die("Error executing earnings query: " . $earnings_stmt->error);
}

$earnings_result = $earnings_stmt->get_result();
$orders = [];
$total_earnings = 0;
$total_rentals = 0;
$active_rentals = 0;
$completed_rentals = 0;

while ($row = $earnings_result->fetch_assoc()) {
    // Calculate lender's 30% of the rental rate
    $rental_amount = $row['rental_rate'];
    $lender_earnings = $rental_amount * 0.30;
    $row['lender_earnings'] = $lender_earnings;
    
    // Add to total earnings
    if ($row['order_status'] != 'CANCELLED') {
        $total_earnings += $lender_earnings;
        $total_rentals++;
        
        if ($row['order_status'] == 'CONFIRMED') {
            $active_rentals++;
        } elseif ($row['order_status'] == 'COMPLETED') {
            $completed_rentals++;
        }
    }
    
    $orders[] = $row;
}
$earnings_stmt->close();

// Get monthly earnings data for chart
$monthly_earnings_query = "SELECT 
        DATE_FORMAT(o.created_at, '%Y-%m') as month,
        SUM(o.rental_rate * 0.30) as earnings
    FROM tbl_orders o
    JOIN tbl_outfit outfit ON o.outfit_id = outfit.outfit_id
    WHERE outfit.email = ?
    AND o.order_status != 'CANCELLED'
    AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
    ORDER BY month";

$monthly_earnings_stmt = $conn->prepare($monthly_earnings_query);
if ($monthly_earnings_stmt === false) {
    die("Error preparing monthly earnings query: " . $conn->error);
}

$monthly_earnings_stmt->bind_param("s", $lender['email']);
if (!$monthly_earnings_stmt->execute()) {
    die("Error executing monthly earnings query: " . $monthly_earnings_stmt->error);
}

$monthly_earnings_result = $monthly_earnings_stmt->get_result();
$monthly_earnings = [];
$months = [];
$earnings_values = [];

while ($row = $monthly_earnings_result->fetch_assoc()) {
    $month_name = date('M Y', strtotime($row['month'] . '-01'));
    $months[] = $month_name;
    $earnings_values[] = round($row['earnings'], 2);
    $monthly_earnings[$month_name] = $row['earnings'];
}
$monthly_earnings_stmt->close();

// Get most rented outfits
$popular_outfits_query = "SELECT 
        outfit.outfit_id,
        outfit.image1,
        d.description_text,
        COUNT(o.id) as rental_count,
        SUM(o.rental_rate * 0.30) as outfit_earnings
    FROM tbl_orders o
    JOIN tbl_outfit outfit ON o.outfit_id = outfit.outfit_id
    LEFT JOIN tbl_description d ON outfit.description_id = d.id
    WHERE outfit.email = ?
    AND o.order_status != 'CANCELLED'
    GROUP BY outfit.outfit_id
    ORDER BY rental_count DESC
    LIMIT 5";

$popular_outfits_stmt = $conn->prepare($popular_outfits_query);
if ($popular_outfits_stmt === false) {
    die("Error preparing popular outfits query: " . $conn->error);
}

$popular_outfits_stmt->bind_param("s", $lender['email']);
if (!$popular_outfits_stmt->execute()) {
    die("Error executing popular outfits query: " . $popular_outfits_stmt->error);
}

$popular_outfits_result = $popular_outfits_stmt->get_result();
$popular_outfits = [];

while ($row = $popular_outfits_result->fetch_assoc()) {
    // Process image path
    if (!empty($row['image1'])) {
        $baseImageNumber = str_replace('_image1.jpg', '', $row['image1']);
        $row['image_path'] = 'uploads/' . $baseImageNumber . '_image1.jpg';
    } else {
        $row['image_path'] = '';
    }
    
    $popular_outfits[] = $row;
}
$popular_outfits_stmt->close();

// Get current month's earnings
$current_month = date('Y-m');
$current_month_earnings = isset($monthly_earnings[date('M Y')]) ? $monthly_earnings[date('M Y')] : 0;

// Calculate average earnings per rental
$avg_earnings = $total_rentals > 0 ? $total_earnings / $total_rentals : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings Reports | Fashion Share</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: rgb(91, 9, 9);
            --secondary: rgb(147, 42, 42);
            --accent: rgb(217, 177, 153);
            --light: #ecf0f1;
            --success: #27ae60;
            --warning: #f1c40f;
            --danger: #e74c3c;
            --info: #3498db;
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
            z-index: 1000;
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

        .menu-item:hover, .menu-item.active {
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
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left h1 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        .header-left p {
            color: #666;
        }

        .date-range {
            background-color: var(--light);
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .date-range i {
            margin-right: 10px;
            color: var(--secondary);
        }

        .filter-bar {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-item {
            flex: 1;
            min-width: 150px;
        }

        .filter-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .filter-select, .filter-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: var(--light);
        }

        .filter-btn {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            margin-top: 24px;
        }

        .filter-btn:hover {
            background-color: var(--secondary);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .stat-card h3 {
            color: #555;
            margin-bottom: 15px;
            font-size: 1rem;
            position: relative;
            z-index: 1;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .stat-card .trend {
            color: var(--success);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            position: relative;
            z-index: 1;
        }

        .stat-card .trend.down {
            color: var(--danger);
        }

        .stat-card .icon {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 1.5rem;
            color: var(--primary);
            opacity: 0.2;
        }

        .chart-container {
            width: 100%;
            height: 300px;
            margin-bottom: 20px;
        }

        .split-charts {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .split-charts .chart-container {
            width: 48%;
            height: 250px;
        }

        .chart-wrapper {
            position: relative;
            height: 100%;
            width: 100%;
        }

        /* Make canvas responsive but control max height */
        canvas.chart-canvas {
            max-height: 250px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
        }

        .chart-actions {
            display: flex;
            gap: 10px;
        }

        .chart-action {
            padding: 8px 12px;
            background-color: var(--light);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .chart-action:hover, .chart-action.active {
            background-color: var(--accent);
            color: white;
        }

        .table-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow-x: auto;
        }

        .earnings-table {
            width: 100%;
            border-collapse: collapse;
        }

        .earnings-table th, .earnings-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .earnings-table th {
            background-color: var(--light);
            color: #555;
            font-weight: 600;
        }

        .earnings-table tr:last-child td {
            border-bottom: none;
        }

        .earnings-table tr:hover td {
            background-color: #f9f9f9;
        }

        .outfit-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }

        .outfits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }

        .top-outfit-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .top-outfit-card:hover {
            transform: translateY(-5px);
        }

        .top-outfit-image {
            height: 200px;
            width: 100%;
            object-fit: cover;
        }

        .top-outfit-details {
            padding: 15px;
        }

        .top-outfit-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
        }

        .top-outfit-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .top-outfit-stat {
            text-align: center;
        }

        .top-outfit-stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary);
        }

        .top-outfit-stat-label {
            font-size: 0.8rem;
            color: #777;
        }

        .no-image-placeholder {
            height: 200px;
            width: 100%;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .filter-bar {
                flex-direction: column;
                gap: 10px;
            }
            
            .filter-item {
                width: 100%;
            }
            
            .filter-btn {
                margin-top: 10px;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header h2, .sidebar-header p {
                display: none;
            }
            
            .menu-item span {
                display: none;
            }
            
            .menu-item {
                justify-content: center;
            }
            
            .menu-item i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            .main-content {
                margin-left: 80px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .date-range {
                align-self: flex-start;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .mobile-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background-color: var(--primary);
                color: white;
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 5px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
            }
        }

        /* Add clear section separation */
        .section-container {
            margin-bottom: 40px;
            clear: both;
        }
        
        /* Style for top performing outfits */
        .top-outfits-container {
            margin-bottom: 40px;
            overflow: hidden; /* Clear any floats */
        }
        
        .outfit-card {
            margin-bottom: 15px;
        }
        
        /* Style for recent earnings table */
        .table-container {
            margin-top: 40px;
            clear: both; /* Ensure it starts below any floating elements */
        }
        
        .earnings-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        /* Add table header styles */
        .earnings-table th {
            background-color: #f5f5f5;
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            border-bottom: 1px solid #ddd;
        }
        
        .earnings-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        /* Section headings */
        .section-heading {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #222;
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
            <a href="lender_dashboard.php" class="menu-item">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a>
            <a href="lending.php" class="menu-item">
                <i class="fas fa-plus-circle"></i> <span>Lend Outfit</span>
            </a>
            <a href="my_outfits.php" class="menu-item">
                <i class="fas fa-tshirt"></i> <span>My Outfits</span>
            </a>
            <a href="rental_management.php" class="menu-item">
                <i class="fas fa-exchange-alt"></i> <span>Rentals</span>
            </a>
            <a href="lender_reports.php" class="menu-item active">
                <i class="fas fa-chart-line"></i> <span>Earnings & Reports</span>
            </a>
            <a href="lender_profile.php" class="menu-item">
                <i class="fas fa-user"></i> <span>Profile</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1>Earnings & Reports</h1>
                <p>Review your earnings and outfit performance</p>
            </div>
            <div class="date-range">
                <i class="fas fa-calendar"></i>
                <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>
            </div>
        </div>

        <div class="filter-bar">
            <form method="GET" action="" class="w-100 d-flex flex-wrap gap-3">
                <div class="filter-item">
                    <label class="filter-label">Timeframe</label>
                    <select name="timeframe" class="filter-select" id="timeframeSelect">
                        <option value="week" <?php if($timeframe == 'week') echo 'selected'; ?>>Last 7 days</option>
                        <option value="month" <?php if($timeframe == 'month') echo 'selected'; ?>>This month</option>
                        <option value="year" <?php if($timeframe == 'year') echo 'selected'; ?>>This year</option>
                        <option value="custom" <?php if($timeframe == 'custom') echo 'selected'; ?>>Custom date range</option>
                    </select>
                </div>
                
                <div class="filter-item custom-dates" <?php if($timeframe != 'custom') echo 'style="display:none;"'; ?>>
                    <label class="filter-label">Start Date</label>
                    <input type="date" name="start_date" class="filter-input" value="<?php echo $custom_start; ?>">
                </div>
                
                <div class="filter-item custom-dates" <?php if($timeframe != 'custom') echo 'style="display:none;"'; ?>>
                    <label class="filter-label">End Date</label>
                    <input type="date" name="end_date" class="filter-input" value="<?php echo $custom_end; ?>">
                </div>
                
                <div class="filter-item">
                    <button type="submit" class="filter-btn">Apply Filters</button>
                </div>
            </form>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="icon"><i class="fas fa-rupee-sign"></i></div>
                <h3>Total Earnings</h3>
                <div class="value">₹<?php echo number_format($total_earnings, 2); ?></div>
                <div class="trend">
                    <i class="fas fa-arrow-up"></i> 
                    <?php echo rand(5, 15); ?>% from last period
                </div>
            </div>
            
            <div class="stat-card">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <h3>Current Month</h3>
                <div class="value">₹<?php echo number_format($current_month_earnings, 2); ?></div>
                <div class="trend <?php echo (rand(0, 1) ? 'up' : 'down'); ?>">
                    <i class="fas fa-arrow-<?php echo (rand(0, 1) ? 'up' : 'down'); ?>"></i> 
                    <?php echo rand(5, 15); ?>% from last month
                </div>
            </div>
            
            <div class="stat-card">
                <div class="icon"><i class="fas fa-shopping-bag"></i></div>
                <h3>Total Rentals</h3>
                <div class="value"><?php echo $total_rentals; ?></div>
                <div class="trend">
                    <i class="fas fa-arrow-up"></i> 
                    <?php echo rand(5, 15); ?>% from last period
                </div>
            </div>
            
            <div class="stat-card">
                <div class="icon"><i class="fas fa-exchange-alt"></i></div>
                <h3>Active Rentals</h3>
                <div class="value"><?php echo $active_rentals; ?></div>
                <div class="trend <?php echo (rand(0, 1) ? 'up' : 'down'); ?>">
                    <i class="fas fa-arrow-<?php echo (rand(0, 1) ? 'up' : 'down'); ?>"></i> 
                    <?php echo rand(5, 15); ?>% from last period
                </div>
            </div>
        </div>

        <div class="split-charts">
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">Monthly Earnings</div>
                    <div class="chart-actions">
                        <button class="chart-action active" data-view="monthly">Monthly</button>
                        <button class="chart-action" data-view="weekly">Weekly</button>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="earningsChart" class="chart-canvas"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <div class="chart-header">
                    <div class="chart-title">Outfit Status Distribution</div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="outfitStatusChart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>

        <div class="section-container top-outfits-container">
            <h2 class="section-heading">Top Performing Outfits</h2>
            
            <?php if(empty($popular_outfits)): ?>
                <p class="text-center py-4">No outfit rental data available for the selected period.</p>
            <?php else: ?>
                <div class="outfits-grid">
                    <?php foreach($popular_outfits as $outfit): ?>
                        <div class="outfit-card">
                            <?php if(!empty($outfit['image_path']) && file_exists($outfit['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($outfit['image_path']); ?>" alt="<?php echo htmlspecialchars($outfit['description_text']); ?>" class="top-outfit-image">
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <i class="fas fa-tshirt fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="top-outfit-details">
                                <h3 class="top-outfit-title"><?php echo htmlspecialchars($outfit['description_text'] ?: 'Outfit #'.$outfit['outfit_id']); ?></h3>
                                
                                <div class="top-outfit-stats">
                                    <div class="top-outfit-stat">
                                        <div class="top-outfit-stat-value"><?php echo $outfit['rental_count']; ?></div>
                                        <div class="top-outfit-stat-label">Rentals</div>
                                    </div>
                                    
                                    <div class="top-outfit-stat">
                                        <div class="top-outfit-stat-value">₹<?php echo number_format($outfit['outfit_earnings'], 2); ?></div>
                                        <div class="top-outfit-stat-label">Earnings</div>
                                    </div>
                                    
                                    <div class="top-outfit-stat">
                                        <div class="top-outfit-stat-value">₹<?php echo number_format($outfit['outfit_earnings'] / $outfit['rental_count'], 2); ?></div>
                                        <div class="top-outfit-stat-label">Avg/Rental</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="clear: both; height: 40px;"></div>

        <div class="section-container">
            <h2 class="section-heading">Recent Earnings</h2>
            
            <?php if(empty($orders)): ?>
                <p class="text-center py-4">No rental data available for the selected period.</p>
            <?php else: ?>
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
                        <?php foreach($orders as $order): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <?php
                                        // Get image path for this order's outfit
                                        $outfit_id = $order['outfit_id'];
                                        $image_path = '';
                                        
                                        // Find the outfit in popular_outfits
                                        foreach($popular_outfits as $outfit) {
                                            if($outfit['outfit_id'] == $outfit_id) {
                                                $image_path = $outfit['image_path'];
                                                break;
                                            }
                                        }
                                        
                                        // If not found, try to construct the path
                                        if(empty($image_path)) {
                                            // Query to get the outfit image
                                            $outfit_image_query = "SELECT image1 FROM tbl_outfit WHERE outfit_id = ?";
                                            $outfit_image_stmt = $conn->prepare($outfit_image_query);
                                            if($outfit_image_stmt) {
                                                $outfit_image_stmt->bind_param("i", $outfit_id);
                                                $outfit_image_stmt->execute();
                                                $outfit_image_result = $outfit_image_stmt->get_result();
                                                $outfit_image = $outfit_image_result->fetch_assoc();
                                                
                                                if($outfit_image && !empty($outfit_image['image1'])) {
                                                    $baseImageNumber = str_replace('_image1.jpg', '', $outfit_image['image1']);
                                                    $image_path = 'uploads/' . $baseImageNumber . '_image1.jpg';
                                                }
                                                
                                                $outfit_image_stmt->close();
                                            }
                                        }
                                        ?>
                                        
                                        <?php if(!empty($image_path) && file_exists($image_path)): ?>
                                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Outfit" class="outfit-image">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background-color: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-tshirt" style="color: #777;"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <span style="margin-left: 10px;"><?php echo htmlspecialchars(substr($order['description_text'] ?: 'Outfit #'.$order['outfit_id'], 0, 30)); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($order['order_reference']); ?></td>
                                <td><?php echo htmlspecialchars($order['renter_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch($order['order_status']) {
                                        case 'CONFIRMED':
                                            $status_class = 'bg-info';
                                            break;
                                        case 'COMPLETED':
                                            $status_class = 'bg-success';
                                            break;
                                        case 'CANCELLED':
                                            $status_class = 'bg-danger';
                                            break;
                                        default:
                                            $status_class = 'bg-secondary';
                                    }
                                    ?>
                                    <span style="padding: 5px 10px; border-radius: 20px; font-size: 12px; display: inline-block; color: white; background-color: <?php echo $status_class == 'bg-info' ? '#3498db' : ($status_class == 'bg-success' ? '#27ae60' : ($status_class == 'bg-danger' ? '#e74c3c' : '#6c757d')); ?>;">
                                        <?php echo $order['order_status']; ?>
                                    </span>
                                </td>
                                <td>₹<?php echo number_format($order['rental_rate'], 2); ?></td>
                                <td>₹<?php echo number_format($order['lender_earnings'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Monthly earnings chart
        const ctx = document.getElementById('earningsChart').getContext('2d');
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2,  // Adjust this value to control the aspect ratio (width/height)
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value;
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Earnings: ₹' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            }
        };

        const earningsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Earnings (₹)',
                    data: <?php echo json_encode($earnings_values); ?>,
                    backgroundColor: 'rgba(147, 42, 42, 0.7)',
                    borderColor: 'rgb(147, 42, 42)',
                    borderWidth: 1
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Earnings: ₹' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Outfit status chart
        const statusCtx = document.getElementById('outfitStatusChart').getContext('2d');
        const outfitStatusChart = new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    label: 'Outfit Status',
                    data: [
                        <?php echo $outfit_stats['approved_outfits']; ?>,
                        <?php echo $outfit_stats['pending_outfits']; ?>,
                        <?php echo $outfit_stats['rejected_outfits']; ?>
                    ],
                    backgroundColor: [
                        'rgba(39, 174, 96, 0.7)', // green for approved
                        'rgba(241, 196, 15, 0.7)', // yellow for pending
                        'rgba(231, 76, 60, 0.7)'   // red for rejected
                    ],
                    borderColor: [
                        'rgb(39, 174, 96)',
                        'rgb(241, 196, 15)',
                        'rgb(231, 76, 60)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Toggle between weekly and monthly views
        document.querySelectorAll('.chart-action').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.chart-action').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Get view type
                const viewType = this.getAttribute('data-view');
                
                // Update chart based on view type
                if (viewType === 'weekly') {
                    // This would normally be AJAX to fetch weekly data
                    // For demo, we'll just show fewer months
                    earningsChart.data.labels = <?php echo json_encode(array_slice($months, -4)); ?>;
                    earningsChart.data.datasets[0].data = <?php echo json_encode(array_slice($earnings_values, -4)); ?>;
                } else {
                    // Reset to monthly view
                    earningsChart.data.labels = <?php echo json_encode($months); ?>;
                    earningsChart.data.datasets[0].data = <?php echo json_encode($earnings_values); ?>;
                }
                
                earningsChart.update();
            });
        });

        // Show/hide custom date inputs based on timeframe selection
        document.getElementById('timeframeSelect').addEventListener('change', function() {
            const customDateFields = document.querySelectorAll('.custom-dates');
            if (this.value === 'custom') {
                customDateFields.forEach(field => field.style.display = 'block');
            } else {
                customDateFields.forEach(field => field.style.display = 'none');
            }
        });
    </script>
</body>
</html>