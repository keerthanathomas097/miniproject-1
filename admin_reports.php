<?php 
session_start();
include 'connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: ls.php");
    exit();
}

// Get admin name for display
$admin_name = $_SESSION['username'];

// Date calculations
$current_month = date('m');
$current_year = date('Y');
$start_of_month = date('Y-m-01');
$end_of_month = date('Y-m-t');
$start_of_year = date('Y-01-01');
$end_of_year = date('Y-12-31');

// Filter parameters
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'month';
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : $start_of_month;
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : $end_of_month;

// Set date range based on timeframe
switch($timeframe) {
    case 'week':
        $date_start = date('Y-m-d', strtotime('-7 days'));
        $date_end = date('Y-m-d');
        break;
    case 'month':
        $date_start = $start_of_month;
        $date_end = $end_of_month;
        break;
    case 'year':
        $date_start = $start_of_year;
        $date_end = $end_of_year;
        break;
    case 'custom':
        // Use the provided date range
        break;
}

// --- Key Metrics Queries ---

// Total Orders
$orderQuery = "SELECT COUNT(*) as total_orders,
               SUM(amount) as total_revenue,
               COUNT(DISTINCT user_id) as unique_customers
               FROM tbl_orders
               WHERE created_at BETWEEN ? AND ?";
$stmt = $conn->prepare($orderQuery);
if ($stmt) {
    $stmt->bind_param("ss", $date_start, $date_end);
    $stmt->execute();
    $orderResult = $stmt->get_result()->fetch_assoc();
    $total_orders = $orderResult['total_orders'] ?? 0;
    $total_revenue = $orderResult['total_revenue'] ?? 0;
    $unique_customers = $orderResult['unique_customers'] ?? 0;
    $stmt->close();
} else {
    // Fallback to non-prepared statement if error
    $safe_date_start = $conn->real_escape_string($date_start);
    $safe_date_end = $conn->real_escape_string($date_end);
    $result = $conn->query("SELECT COUNT(*) as total_orders,
                          SUM(amount) as total_revenue,
                          COUNT(DISTINCT user_id) as unique_customers
                          FROM tbl_orders
                          WHERE created_at BETWEEN '$safe_date_start' AND '$safe_date_end'");
    if ($result) {
        $orderResult = $result->fetch_assoc();
        $total_orders = $orderResult['total_orders'] ?? 0;
        $total_revenue = $orderResult['total_revenue'] ?? 0;
        $unique_customers = $orderResult['unique_customers'] ?? 0;
    }
}

// Total Users
$userQuery = "SELECT COUNT(*) as total_users FROM tbl_users";
$result = $conn->query($userQuery);
if ($result) {
    $userResult = $result->fetch_assoc();
    $total_users = $userResult['total_users'] ?? 0;
}

// Total Outfits
$outfitQuery = "SELECT 
                COUNT(*) as total_outfits,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_outfits,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_outfits,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_outfits
                FROM tbl_outfit";
$result = $conn->query($outfitQuery);
if ($result) {
    $outfitResult = $result->fetch_assoc();
    $total_outfits = $outfitResult['total_outfits'] ?? 0;
    $approved_outfits = $outfitResult['approved_outfits'] ?? 0;
    $pending_outfits = $outfitResult['pending_outfits'] ?? 0;
    $rejected_outfits = $outfitResult['rejected_outfits'] ?? 0;
}

// Update the Order Status Distribution query and chart
// First, get a list of all possible order status values from your database
$statusValuesQuery = "SELECT DISTINCT order_status FROM tbl_orders";
$statusValuesResult = $conn->query($statusValuesQuery);
$orderStatusValues = [];

if ($statusValuesResult) {
    while ($row = $statusValuesResult->fetch_assoc()) {
        $orderStatusValues[] = $row['order_status'];
    }
}

// Then count occurrences of each status
$statusData = [];
foreach ($orderStatusValues as $status) {
    $statusCountQuery = "SELECT COUNT(*) as count FROM tbl_orders WHERE order_status = '$status'";
    $statusCountResult = $conn->query($statusCountQuery);
    if ($statusCountResult && $row = $statusCountResult->fetch_assoc()) {
        $statusData[$status] = $row['count'];
    } else {
        $statusData[$status] = 0;
    }
}

// Define colors for the chart based on status values
$statusColors = [
    'CONFIRMED' => 'rgb(40, 167, 69)',    // Green
    'COMPLETED' => 'rgb(0, 123, 255)',    // Blue
    'PENDING' => 'rgb(255, 193, 7)',      // Yellow
    'CANCELLED' => 'rgb(220, 53, 69)',    // Red
    'REFUNDED' => 'rgb(108, 117, 125)',   // Gray
    'PROCESSING' => 'rgb(23, 162, 184)',  // Cyan
    'DISPATCHED' => 'rgb(111, 66, 193)',  // Purple
    'DELIVERED' => 'rgb(0, 200, 81)',     // Light Green
    'RETURNED' => 'rgb(255, 102, 0)'      // Orange
];

// Set default color for any unmatched statuses
$defaultColor = 'rgb(102, 102, 102)';  // Dark Gray

// Daily Orders for Chart
$dailyOrdersQuery = "SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(amount) as revenue
                   FROM tbl_orders
                   GROUP BY DATE(created_at)
                   ORDER BY date
                   LIMIT 14";
$result = $conn->query($dailyOrdersQuery);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dates[] = date('M d', strtotime($row['date']));
        $ordersData[] = $row['orders'];
        $revenueData[] = $row['revenue'];
    }
}

// Most Popular Outfits
$popularOutfitsQuery = "SELECT o.outfit_id, d.description_text, o.image1, 
                         COUNT(ord.id) as rental_count,
                         SUM(ord.amount) as revenue
                       FROM tbl_orders ord
                       JOIN tbl_outfit o ON ord.outfit_id = o.outfit_id
                       LEFT JOIN tbl_description d ON o.description_id = d.id
                       GROUP BY o.outfit_id, d.description_text, o.image1
                       ORDER BY rental_count DESC
                       LIMIT 5";
$popularOutfitsResult = $conn->query($popularOutfitsQuery);

// Top Categories
$categoryQuery = "SELECT s.subcategory_name, COUNT(ord.id) as rental_count
                 FROM tbl_orders ord
                 JOIN tbl_outfit o ON ord.outfit_id = o.outfit_id
                 JOIN tbl_subcategory s ON o.type_id = s.id
                 GROUP BY s.subcategory_name
                 ORDER BY rental_count DESC
                 LIMIT 5";
$categoryResult = $conn->query($categoryQuery);

// Conversion Rate (Assumption: user visits are roughly 3x total users)
$user_visits = $total_users * 3;
$conversion_rate = ($user_visits > 0) ? ($total_orders / $user_visits) * 100 : 0;

// Average Order Value
$avg_order_value = ($total_orders > 0) ? $total_revenue / $total_orders : 0;

// Return Rate (Dummy data - replace with actual logic if available)
$return_rate = 2.5; // 2.5% return rate

// Calculate actual earnings (70% of rental price)
$earningsQuery = "SELECT SUM(amount) as total_revenue FROM tbl_orders";
$result = $conn->query($earningsQuery);
if ($result) {
    $earningsResult = $result->fetch_assoc();
    $total_revenue = $earningsResult['total_revenue'] ?? 0;
    
    // Calculate earnings as 70% of the rental price (excluding security deposits)
    // Assuming security deposit is roughly 30% of the total amount
    $rental_portion = $total_revenue * 0.7; // 70% of total is the actual rental portion
    $total_earnings = $rental_portion * 0.7; // 70% of rental portion as earnings
    
    // Calculate average earnings per order
    $avg_earnings = ($total_orders > 0) ? $total_earnings / $total_orders : 0;
} else {
    $total_revenue = 0;
    $total_earnings = 0;
    $avg_earnings = 0;
}

// Calculate revenue breakdown (near your other calculations)
$platform_earnings = $total_earnings; // Already calculated as 70% of rental portion
$lender_earnings = $rental_portion - $platform_earnings; // 30% of rental portion
$security_deposits = $total_revenue - $rental_portion; // 30% of total revenue

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard | Fashion Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --sidebar-width: 280px; 
            --primary-color: #8b0000;
            --primary-light: #b30000;
            --primary-dark: #660000;
            --secondary-color: #333;
            --text-color: #333;
            --text-muted: #6c757d;
            --border-color: #e9ecef;
            --card-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.12);
            --chart-colors: ["#8b0000", "#c45850", "#3c8dbc", "#3e95cd", "#8e5ea2"];
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            overflow-x: hidden;
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #d1d1d1;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #b1b1b1;
        }
        
        /* Sidebar styling */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: linear-gradient(to bottom, var(--primary-dark), var(--primary-color));
            color: white;
            padding-top: 20px;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1rem;
        }
        
        .sidebar-brand h4 {
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
            letter-spacing: 0.5px;
        }
        
        .sidebar-brand span {
            font-weight: 300;
        }
        
        .nav-section {
            margin-bottom: 1rem;
            padding: 0 1rem;
        }
        
        .nav-section-title {
            text-transform: uppercase;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.6);
            padding: 0.5rem 1rem;
        }
        
        .sidebar-link {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            margin-bottom: 0.375rem;
            font-weight: 500;
        }
        
        .sidebar-link i {
            width: 20px;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .sidebar-link:hover, 
        .sidebar-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar-link.active {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.6);
            text-align: center;
        }
        
        /* Main content area */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 0;
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
        .content-wrapper {
            padding: 2rem;
        }
        
        /* Navbar */
        .admin-navbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-tools {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .notification-bell {
            position: relative;
            color: var(--text-muted);
            font-size: 1.2rem;
            cursor: pointer;
        }
        
        .notification-indicator {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 12px;
            height: 12px;
            background: var(--primary-color);
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .admin-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .admin-info {
            display: flex;
            flex-direction: column;
        }
        
        .admin-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .admin-role {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        /* Report Header */
        .report-header {
            background: linear-gradient(45deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 2.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }
        
        .report-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }
        
        .report-subtitle {
            opacity: 0.85;
            font-size: 1rem;
            font-weight: 400;
            margin-bottom: 1.5rem;
        }
        
        .report-period {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .report-period i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        
        .report-actions {
            display: flex;
            gap: 1rem;
        }
        
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .action-btn.btn-export {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .action-btn.btn-export:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .action-btn.btn-print {
            background: white;
            color: var(--primary-color);
        }
        
        .action-btn.btn-print:hover {
            background: rgba(255, 255, 255, 0.9);
        }
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }
        
        .filter-title {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
        }
        
        .date-range-form .form-control,
        .date-range-form .form-select {
            border-radius: 8px;
            font-size: 0.9rem;
            padding: 0.6rem 1rem;
            border: 1px solid var(--border-color);
            box-shadow: none;
            transition: all 0.2s ease;
        }
        
        .date-range-form .form-control:focus,
        .date-range-form .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(139, 0, 0, 0.15);
        }
        
        .date-range-form .btn {
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .date-range-form .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .date-range-form .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        /* Section Header */
        .section-header {
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary-color);
            position: relative;
            padding-left: 1rem;
        }
        
        .section-title::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }
        
        /* Stats Card */
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid transparent;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
            border-color: rgba(139, 0, 0, 0.1);
        }
        
        .stats-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        
        .stats-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            background: rgba(139, 0, 0, 0.1);
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .stats-card-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
            margin: 0;
        }
        
        .stats-card-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0 0 0.75rem;
            color: var(--secondary-color);
        }
        
        .stats-trend {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .stats-trend-up {
            background: rgba(25, 135, 84, 0.1);
            color: #198754;
        }
        
        .stats-trend-down {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .stats-trend i {
            margin-right: 0.25rem;
            font-size: 0.7rem;
        }
        
        .stats-description {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.75rem;
        }
        
        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease;
        }
        
        .chart-container:hover {
            box-shadow: var(--hover-shadow);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .chart-options {
            display: flex;
            gap: 0.5rem;
        }
        
        .chart-options button {
            background: #f5f5f5;
            border: none;
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #555;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .chart-options button:hover,
        .chart-options button.active {
            background: var(--primary-color);
            color: white;
        }
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }
        
        .custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .custom-table thead th {
            padding: 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-muted);
            background-color: #f8f9fa;
            border-bottom: 1px solid var(--border-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .custom-table th:first-child {
            border-top-left-radius: 8px;
        }
        
        .custom-table th:last-child {
            border-top-right-radius: 8px;
        }
        
        .custom-table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.9rem;
        }
        
        .custom-table tr:last-child td {
            border-bottom: none;
        }
        
        .custom-table tr:hover td {
            background-color: rgba(248, 249, 250, 0.5);
        }
        
        .progress-container {
            width: 100%;
            margin-top: 0.5rem;
        }
        
        .progress {
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
            background-color: #f5f5f5;
        }
        
        .progress-bar {
            background-color: var(--primary-color);
            transition: width 0.5s ease;
        }
        
        .table-rank {
            width: 30px;
            height: 30px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .table-id {
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--text-muted);
            background: #f5f5f5;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        
        /* Footer */
        .app-footer {
            text-align: center;
            padding: 1.5rem;
            color: var(--text-muted);
            font-size: 0.85rem;
            border-top: 1px solid var(--border-color);
            margin-top: 2rem;
        }
        
        /* Tooltip */
        .tooltip-icon {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-left: 0.5rem;
            cursor: help;
        }
        
        /* Responsive styles */
        @media (max-width: 1200px) {
            .stats-card-value {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 992px) {
            :root {
                --sidebar-width: 70px;
            }
            
            .sidebar-brand h4, .sidebar-link span, .nav-section-title {
                display: none;
            }
            
            .sidebar-link {
                justify-content: center;
                padding: 0.75rem;
            }
            
            .sidebar-link i {
                margin-right: 0;
                font-size: 1.25rem;
            }
            
            .sidebar-footer {
                display: none;
            }
            
            .main-content {
                margin-left: var(--sidebar-width);
            }
            
            .admin-navbar {
                padding: 1rem;
            }
            
            .admin-info {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .content-wrapper {
                padding: 1.5rem;
            }
            
            .report-header {
                padding: 1.5rem;
            }
            
            .navbar-toggler {
                display: block;
            }
            
            .report-title {
                font-size: 1.5rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .chart-container {
                overflow-x: auto;
            }
        }
        
        /* Print styles */
        @media print {
            .sidebar, .admin-navbar, .filter-bar, .no-print {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            
            .report-header {
                background: none !important;
                color: black !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            
            .content-wrapper {
                padding: 0 !important;
            }
            
            .stats-card, .chart-container, .table-container {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #eee !important;
            }
        }
        
        /* Add these styles to your existing stylesheet */
        .stats-card {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }
        
        /* Add hover effects to make cards interactive */
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        /* Make the chart containers more interactive */
        .chart-container {
            transition: all 0.3s ease;
        }
        
        .chart-container:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transform: translateY(-3px);
        }
        
        /* Improve the look of the table */
        .custom-table thead th {
            background: linear-gradient(45deg, #f8f9fa, #ffffff);
            border-bottom: 2px solid rgba(205, 38, 38, 0.2);
            padding: 15px;
        }
        
        .custom-table tbody tr:hover {
            background-color: rgba(205, 38, 38, 0.03);
        }
        
        /* Make buttons more professional */
        .btn-outline-dark {
            border-color: #343a40;
            color: #343a40;
        }
        
        .btn-outline-dark:hover {
            background-color: #343a40;
            color: white;
        }
        
        /* Add tooltip styles */
        [data-tooltip] {
            position: relative;
            cursor: help;
        }
        
        [data-tooltip]:after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        [data-tooltip]:hover:after {
            opacity: 1;
            visibility: visible;
        }
        
        /* Additional styles for the new components */
        .metric-card {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .metric-title {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .metric-value.up {
            color: #28a745;
        }
        
        .metric-value.down {
            color: #dc3545;
        }
        
        .metric-trend {
            height: 50px;
        }
        
        #comparisonMetrics {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .comparison-table-container {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .comparison-table th, .comparison-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }
        
        .comparison-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .comparison-table td:first-child {
            text-align: left;
            font-weight: 500;
        }
        
        .comparison-table .up {
            color: #28a745;
        }
        
        .comparison-table .down {
            color: #dc3545;
        }
        
        .form-switch {
            padding-left: 2.5em;
        }
        
        .form-check-input:checked {
            background-color: rgb(205, 38, 38);
            border-color: rgb(205, 38, 38);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4>Fashion <span>Rental</span></h4>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Dashboard</div>
            <nav>
                <a href="admin_dashboard.php" class="sidebar-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </nav>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Management</div>
            <nav>
                <a href="user_management.php" class="sidebar-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="outfit_management.php" class="sidebar-link">
                    <i class="fas fa-tshirt"></i>
                    <span>Outfits</span>
                </a>
                <a href="orders_admin.php" class="sidebar-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </nav>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Analytics</div>
            <nav>
                <a href="admin_reports.php" class="sidebar-link active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </nav>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Settings</div>
            <nav>
                <a href="admin_profile.php" class="sidebar-link">
                    <i class="fas fa-user-cog"></i>
                    <span>Profile</span>
                    </a>
                <a href="system_settings.php" class="sidebar-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="sidebar-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
        
        <div class="sidebar-footer">
            &copy; 2025 Fashion Rental
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Admin Navbar -->
        <div class="admin-navbar">
            <div class="navbar-toggler d-lg-none">
                <i class="fas fa-bars"></i>
            </div>
            
            <div class="navbar-tools">
                <div class="notification-bell">
                    <i class="far fa-bell"></i>
                    <div class="notification-indicator"></div>
                </div>
                
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    </div>
                    <div class="admin-info">
                        <div class="admin-name"><?php echo $admin_name; ?></div>
                        <div class="admin-role">Administrator</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-wrapper">
            <!-- Report Header with Export Options -->
            <div class="report-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="mb-2">Analytics Reports</h1>
                        <p class="mb-0">Comprehensive analysis of sales, outfits, and user activity</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="d-flex justify-content-md-end align-items-center">
                            <div class="d-inline-block p-3 bg-white text-dark rounded me-3">
                                <span class="d-block">Report Period</span>
                                <h5 class="mb-0"><?= date('M d, Y', strtotime($date_start)) ?> - <?= date('M d, Y', strtotime($date_end)) ?></h5>
                            </div>
                            <div class="export-options">
                                <button class="btn btn-sm btn-outline-light me-2" onclick="printReport()">
                                    <i class="fas fa-print me-1"></i> Print
                                </button>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-download me-1"></i> Export
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#" onclick="exportPDF()"><i class="far fa-file-pdf me-2"></i>PDF</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="exportExcel()"><i class="far fa-file-excel me-2"></i>Excel</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="exportCSV()"><i class="far fa-file-csv me-2"></i>CSV</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <h5 class="filter-title">Filter Report</h5>
                
                <form action="" method="GET" class="date-range-form">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="timeframe" class="form-label">Timeframe</label>
                            <select name="timeframe" id="timeframe" class="form-select">
                                <option value="week" <?php echo $timeframe == 'week' ? 'selected' : ''; ?>>Last 7 days</option>
                                <option value="month" <?php echo $timeframe == 'month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="year" <?php echo $timeframe == 'year' ? 'selected' : ''; ?>>This Year</option>
                                <option value="custom" <?php echo $timeframe == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_start" class="form-label">Start Date</label>
                            <input type="date" name="date_start" id="date_start" class="form-control" value="<?php echo $date_start; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_end" class="form-label">End Date</label>
                            <input type="date" name="date_end" id="date_end" class="form-control" value="<?php echo $date_end; ?>">
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Add this right after your filter bar -->
            <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
                <h4 class="mb-0">Key Performance Metrics</h4>
                <button id="refreshData" class="btn btn-sm btn-outline-dark">
                    <i class="fas fa-sync-alt me-1"></i> Refresh Data
                </button>
            </div>
            
            <!-- Key Metrics -->
            <div class="section-header">
                <h2 class="section-title">Key Performance Metrics</h2>
            </div>
            
            <div class="row g-4 mb-4">
                <!-- Total Orders Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <div class="stats-card-icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <h3 class="stats-card-title">Total Orders</h3>
                        </div>
                        <h2 class="stats-card-value"><?php echo number_format($total_orders); ?></h2>
                        <div class="stats-trend stats-trend-up">
                            <i class="fas fa-arrow-up"></i>
                            12.5% vs. last period
                        </div>
                        <p class="stats-description">
                            Total number of rental orders for selected period
                        </p>
                    </div>
                </div>
                
                <!-- Revenue Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <div class="stats-card-icon">
                                <i class="fas fa-rupee-sign"></i>
                            </div>
                            <h3 class="stats-card-title">Total Earnings</h3>
                        </div>
                        <h2 class="stats-card-value">₹<?php echo number_format($total_earnings); ?></h2>
                        <div class="stats-trend stats-trend-up">
                            <i class="fas fa-arrow-up"></i>
                            70% of rental revenue
                        </div>
                        <p class="stats-description">
                            Total earnings from rentals
                        </p>
                    </div>
                </div>
                
                <!-- Users Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <div class="stats-card-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="stats-card-title">Total Users</h3>
                        </div>
                        <h2 class="stats-card-value"><?php echo number_format($total_users); ?></h2>
                        <div class="stats-trend stats-trend-up">
                            <i class="fas fa-arrow-up"></i>
                            5.2% vs. last period
                        </div>
                        <p class="stats-description">
                            Total registered users on platform
                        </p>
                    </div>
                </div>
                
                <!-- Conversion Rate Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <div class="stats-card-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 class="stats-card-title">Conversion Rate</h3>
                        </div>
                        <h2 class="stats-card-value"><?php echo number_format($conversion_rate, 1); ?>%</h2>
                        <div class="stats-trend stats-trend-up">
                            <i class="fas fa-arrow-up"></i>
                            1.8% vs. last period
                        </div>
                        <p class="stats-description">
                            Percentage of visits that result in orders
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="row g-4 mb-4">
                <!-- Avg Order Value Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <div class="stats-card-icon">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h3 class="stats-card-title">Avg. Earnings/Order</h3>
                        </div>
                        <h2 class="stats-card-value">₹<?php echo number_format($avg_earnings, 0); ?></h2>
                        <div class="stats-trend stats-trend-down">
                            <i class="fas fa-arrow-down"></i>
                            2.1% vs. last period
                        </div>
                        <p class="stats-description">
                            Average platform earnings per order
                        </p>
                    </div>
                </div>
                
                <!-- Unique Customers Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <div class="stats-card-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h3 class="stats-card-title">Unique Customers</h3>
                        </div>
                        <h2 class="stats-card-value"><?php echo number_format($unique_customers); ?></h2>
                        <div class="stats-trend stats-trend-up">
                            <i class="fas fa-arrow-up"></i>
                            7.4% vs. last period
                        </div>
                        <p class="stats-description">
                            Number of unique customers who placed orders
                        </p>
                    </div>
                </div>
                
                <!-- Total Outfits Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <div class="stats-card-icon">
                                <i class="fas fa-tshirt"></i>
                            </div>
                            <h3 class="stats-card-title">Total Outfits</h3>
                        </div>
                        <h2 class="stats-card-value"><?php echo number_format($total_outfits); ?></h2>
                        <div class="stats-trend stats-trend-up">
                            <i class="fas fa-arrow-up"></i>
                            3.5% vs. last period
                        </div>
                        <p class="stats-description">
                            Total number of outfits available
                        </p>
                    </div>
                </div>
                
                <!-- Return Rate Card -->
                <div class="col-md-6 col-lg-3">
                    <div class="stats-card">
                        <div class="stats-card-header">
                            <div class="stats-card-icon">
                                <i class="fas fa-undo"></i>
                            </div>
                            <h3 class="stats-card-title">Return Rate</h3>
                        </div>
                        <h2 class="stats-card-value"><?php echo number_format($return_rate, 1); ?>%</h2>
                        <div class="stats-trend stats-trend-down">
                            <i class="fas fa-arrow-down"></i>
                            0.5% vs. last period
                        </div>
                        <p class="stats-description">
                            Percentage of outfits returned with issues
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Order Status -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <!-- Daily Orders Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">Daily Orders & Revenue</h3>
                            <div class="chart-options">
                                <button class="active" data-type="orders">Orders</button>
                                <button data-type="revenue">Revenue</button>
                            </div>
                        </div>
                        <div style="height: 350px;">
                            <canvas id="dailyOrdersChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Order Status Distribution -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">Order Status</h3>
                        </div>
                        <div style="height: 350px;">
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Outfit Status -->
            <div class="section-header">
                <h2 class="section-title">Outfit Status</h2>
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <!-- Outfit Status Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">Outfit Status Distribution</h3>
                        </div>
                        <div style="height: 350px;">
                            <canvas id="outfitStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <!-- Popular Categories Chart -->
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3 class="chart-title">Popular Categories</h3>
                        </div>
                        <div style="height: 350px;">
                            <canvas id="categoriesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Outfits Table -->
            <div class="section-header">
                <h2 class="section-title">Top Performing Outfits</h2>
            </div>
            
            <div class="row mt-4">
                <div class="col-12">
            <div class="table-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Top Performing Outfits</h4>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="outfitFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Filter By
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="outfitFilterDropdown">
                                    <li><a class="dropdown-item active" href="#">Most Rented</a></li>
                                    <li><a class="dropdown-item" href="#">Highest Revenue</a></li>
                                    <li><a class="dropdown-item" href="#">Newest Additions</a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                                        <th style="width: 60px;">Rank</th>
                                        <th style="width: 100px;">Image</th>
                                        <th>Outfit Details</th>
                                        <th style="width: 120px;">Rental Count</th>
                                        <th style="width: 150px;">Revenue Generated</th>
                                        <th style="width: 180px;">Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $rank = 1;
                            $max_rentals = 0;
                            
                                    // If popularOutfitsResult doesn't exist or is empty, create a simple query
                                    if (!isset($popularOutfitsResult) || $popularOutfitsResult->num_rows == 0) {
                                        $popularOutfitsQuery = "SELECT o.outfit_id, d.description_text, o.image1, COUNT(ord.id) as rental_count,
                                                               SUM(ord.amount) as revenue
                                                               FROM tbl_orders ord
                                                               JOIN tbl_outfit o ON ord.outfit_id = o.outfit_id
                                                               LEFT JOIN tbl_description d ON o.description_id = d.id
                                                               GROUP BY o.outfit_id
                                                               ORDER BY rental_count DESC
                                                               LIMIT 5";
                                        $popularOutfitsResult = $conn->query($popularOutfitsQuery);
                                    }
                                    
                                    $popularOutfitsResult->data_seek(0);
                            while ($row = $popularOutfitsResult->fetch_assoc()) {
                                        if($rank == 1) $max_rentals = $row['rental_count'];
                                        $percentage = ($max_rentals > 0) ? ($row['rental_count'] / $max_rentals) * 100 : 0;
                                        
                                        // Get outfit image
                                        $imagePath = '';
                                        if (!empty($row['image1'])) {
                                            // Check if image path contains full path or just filename
                                            if (strpos($row['image1'], '/') !== false) {
                                                $imagePath = $row['image1']; // If it already has a path
                                            } else {
                                                // Clean the image name and construct the path
                                                $baseImageNumber = str_replace('_image1.jpg', '', $row['image1']);
                                                $imagePath = 'uploads/' . $baseImageNumber . '_image1.jpg';
                                                
                                                // If that path doesn't exist, try alternate formats
                                                if (!file_exists($imagePath)) {
                                                    $imagePath = 'uploads/' . $row['image1'];
                                                    
                                                    // If still doesn't exist, try without the _image1.jpg suffix
                                                    if (!file_exists($imagePath)) {
                                                        $imagePath = 'uploads/' . $baseImageNumber . '.jpg';
                                                    }
                                                }
                                            }
                                        }
                                        
                                        // Calculate revenue if available
                                        $revenue = isset($row['revenue']) ? $row['revenue'] : ($row['rental_count'] * 1500); // Fallback calculation
                                ?>
                                <tr>
                                        <td class="text-center">
                                            <span class="badge rounded-pill bg-secondary">#<?= $rank++ ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($imagePath) && file_exists($imagePath)): ?>
                                                <img src="<?= $imagePath ?>" class="img-thumbnail" alt="Outfit Image" 
                                                     style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;"
                                                     onclick="showOutfitImageModal('<?= $imagePath ?>', '<?= htmlspecialchars($row['description_text'] ?? 'Outfit #'.$row['outfit_id']) ?>')"
                                                 >
                                            <?php else: ?>
                                                <div class="no-image-placeholder" style="width: 80px; height: 80px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                                    <i class="fas fa-tshirt text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                    </td>
                                    <td>
                                            <div class="d-flex flex-column">
                                                <strong><?= htmlspecialchars($row['description_text'] ?? 'Outfit #'.$row['outfit_id']) ?></strong>
                                                <small class="text-muted">ID: <?= $row['outfit_id'] ?></small>
                                            </div>
                                    </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?= $row['rental_count'] ?></span>
                                                <i class="fas fa-shopping-bag text-success"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2">₹<?= number_format($revenue) ?></span>
                                                <i class="fas fa-rupee-sign text-primary"></i>
                                            </div>
                                        </td>
                                    <td>
                                        <div class="progress-container">
                                                <div class="progress-label">
                                                    <span>Performance</span>
                                                    <span><?= number_format($percentage) ?>%</span>
                                                </div>
                                            <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percentage ?>%" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                    <?php } ?>
                    </tbody>
                </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Categories Table -->
            <div class="section-header">
                <h2 class="section-title">Top Categories</h2>
            </div>
            
            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Category</th>
                            <th>Rentals</th>
                            <th>Popularity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($categoryResult && $categoryResult->num_rows > 0) {
                            $rank = 1;
                            $max_rentals = 0;
                            
                            // First pass to get max value for calculating percentages
                            $rows = [];
                            while ($row = $categoryResult->fetch_assoc()) {
                                $rows[] = $row;
                                if ($row['rental_count'] > $max_rentals) {
                                    $max_rentals = $row['rental_count'];
                                }
                            }
                            
                            // Second pass to display data
                            foreach ($rows as $row) {
                                $popularity_percent = ($max_rentals > 0) ? ($row['rental_count'] / $max_rentals) * 100 : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div class="table-rank"><?php echo $rank++; ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['subcategory_name']); ?></td>
                                    <td><?php echo $row['rental_count']; ?></td>
                                    <td>
                                        <div class="progress-container">
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo $popularity_percent; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center'>No data available</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Add this right after the "Key Performance Metrics" section -->
            <div class="chart-container mt-4">
                <div class="chart-header">
                    <div class="chart-title">Performance Summary</div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="comparePeriods">
                        <label class="form-check-label" for="comparePeriods">Compare with Previous Period</label>
                    </div>
                </div>
                
                <div class="row mt-3" id="summaryMetrics">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-title">Order Growth</div>
                            <div class="metric-value up">+12.5%</div>
                            <div class="metric-trend">
                                <canvas id="orderTrendMini" height="50"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-title">Revenue Growth</div>
                            <div class="metric-value up">+8.3%</div>
                            <div class="metric-trend">
                                <canvas id="revenueTrendMini" height="50"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-title">User Growth</div>
                            <div class="metric-value up">+15.7%</div>
                            <div class="metric-trend">
                                <canvas id="userTrendMini" height="50"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-title">Conversion Rate</div>
                            <div class="metric-value down">-2.1%</div>
                            <div class="metric-trend">
                                <canvas id="conversionTrendMini" height="50"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3" id="comparisonMetrics" style="display: none;">
                    <!-- This section will be populated with comparison data -->
                </div>
            </div>
            
            <!-- Add this section after your Key Metrics section -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">Revenue Breakdown</div>
                        </div>
                        <canvas id="revenueBreakdownChart" height="300"></canvas>
                    </div>
                </div>
                
                <!-- You can add another chart here if needed -->
                <div class="col-md-6">
                    <div class="chart-container">
                        <div class="chart-header">
                            <div class="chart-title">Monthly Earnings Trend</div>
                        </div>
                        <canvas id="monthlyEarningsChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="app-footer">
                <p>&copy; 2025 Fashion Rental. All rights reserved.</p>
            </footer>
        </div>
    </div>
    
    <!-- JavaScript for Charts and UI Interactions -->
    <script>
        // Initialize Charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Chart.js Colors
            const primaryColor = '#8b0000';
            const primaryLightColor = '#b30000';
            const secondaryColor = '#333333';
            const chartColors = ['#8b0000', '#c45850', '#3c8dbc', '#3e95cd', '#8e5ea2'];
            
            // Daily Orders Chart
            const dailyOrdersCtx = document.getElementById('dailyOrdersChart').getContext('2d');
            const dailyOrdersChart = new Chart(dailyOrdersCtx, {
                type: 'line',
                data: {
                    labels: <?php echo isset($dates) ? json_encode($dates) : '[]'; ?>,
                    datasets: [{
                        label: 'Orders',
                        data: <?php echo isset($ordersData) ? json_encode($ordersData) : '[]'; ?>,
                        borderColor: primaryColor,
                        backgroundColor: 'rgba(139, 0, 0, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: primaryColor,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.1)',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            // Order Status Chart
            const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
            const orderStatusData = {
                labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Returned'],
                datasets: [{
                    data: [
                        <?php echo isset($statusData['pending']) ? $statusData['pending'] : 0; ?>,
                        <?php echo isset($statusData['processing']) ? $statusData['processing'] : 0; ?>,
                        <?php echo isset($statusData['shipped']) ? $statusData['shipped'] : 0; ?>,
                        <?php echo isset($statusData['delivered']) ? $statusData['delivered'] : 0; ?>,
                        <?php echo isset($statusData['returned']) ? $statusData['returned'] : 0; ?>
                    ],
                    backgroundColor: chartColors,
                    borderWidth: 0
                }]
            };
            
            const orderStatusChart = new Chart(orderStatusCtx, {
                type: 'doughnut',
                data: orderStatusData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
            
            // Outfit Status Chart
            const outfitStatusCtx = document.getElementById('outfitStatusChart').getContext('2d');
            const outfitStatusData = {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    data: [
                        <?php echo $approved_outfits; ?>,
                        <?php echo $pending_outfits; ?>,
                        <?php echo $rejected_outfits; ?>
                    ],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderWidth: 0
                }]
            };
            
            const outfitStatusChart = new Chart(outfitStatusCtx, {
                type: 'pie',
                data: outfitStatusData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15
                            }
                        }
                    }
                }
            });
            
            // Categories Chart
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            const categoryLabels = [];
            const categoryData = [];
            
            <?php 
            if ($categoryResult) {
                $categoryResult->data_seek(0);
                while ($row = $categoryResult->fetch_assoc()) {
                    echo "categoryLabels.push('" . addslashes($row['subcategory_name']) . "');\n";
                    echo "categoryData.push(" . $row['rental_count'] . ");\n";
                }
            }
            ?>
            
            const categoriesChart = new Chart(categoriesCtx, {
                type: 'bar',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        label: 'Number of Rentals',
                        data: categoryData,
                        backgroundColor: primaryColor,
                        borderWidth: 0,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            // UI Interaction for Chart Options
            const chartOptions = document.querySelectorAll('.chart-options button');
            chartOptions.forEach(button => {
                button.addEventListener('click', function() {
                    const type = this.dataset.type;
                    const parent = this.parentElement;
                    
                    // Remove active class from all buttons in this group
                    parent.querySelectorAll('button').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Update chart data based on selection
                    if (type === 'orders') {
                        dailyOrdersChart.data.datasets[0].label = 'Orders';
                        dailyOrdersChart.data.datasets[0].data = <?php echo isset($ordersData) ? json_encode($ordersData) : '[]'; ?>;
                    } else if (type === 'revenue') {
                        dailyOrdersChart.data.datasets[0].label = 'Revenue ($)';
                        dailyOrdersChart.data.datasets[0].data = <?php echo isset($revenueData) ? json_encode($revenueData) : '[]'; ?>;
                    }
                    
                    dailyOrdersChart.update();
                });
            });
            
            // Toggle Sidebar on Mobile
            const navbarToggler = document.querySelector('.navbar-toggler');
            const sidebar = document.querySelector('.sidebar');
            
            if (navbarToggler) {
                navbarToggler.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
            
            // Timeframe Selection Logic
            const timeframeSelect = document.getElementById('timeframe');
            const dateStartInput = document.getElementById('date_start');
            const dateEndInput = document.getElementById('date_end');
            
            timeframeSelect.addEventListener('change', function() {
                const value = this.value;
                
                if (value !== 'custom') {
                    dateStartInput.disabled = true;
                    dateEndInput.disabled = true;
                } else {
                    dateStartInput.disabled = false;
                    dateEndInput.disabled = false;
                }
            });
            
            // Initialize based on current selection
            if (timeframeSelect.value !== 'custom') {
                dateStartInput.disabled = true;
                dateEndInput.disabled = true;
            }
            
            // Add this to your existing JavaScript
            document.getElementById('refreshData').addEventListener('click', function() {
                // Show loading state
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Refreshing...';
                this.disabled = true;
                
                // Get current filters
                const timeframe = document.getElementById('timeframe').value;
                const dateStart = document.getElementById('date_start').value;
                const dateEnd = document.getElementById('date_end').value;
                
                // Create URL with parameters
                const url = `admin_reports.php?timeframe=${timeframe}&date_start=${dateStart}&date_end=${dateEnd}&refresh=true`;
                
                // Simulate refresh with a reload
                setTimeout(() => {
                    window.location.href = url;
                }, 1000);
            });
            
            // Function for exporting to Excel
            function exportExcel() {
                alert('Excel export functionality would be implemented here with a library like SheetJS or server-side export.');
            }
            
            // Add animated entrance for cards
            const statsCards = document.querySelectorAll('.stats-card');
            statsCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
            
            // Mini trend charts
            const createMiniTrendChart = (id, color, data) => {
                const ctx = document.getElementById(id).getContext('2d');
                return new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: Array(data.length).fill(''),
                        datasets: [{
                            data: data,
                            borderColor: color,
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false }
                        },
                        scales: {
                            x: { display: false },
                            y: { display: false }
                        },
                        elements: {
                            line: { tension: 0.4 }
                        }
                    }
                });
            };
            
            // Sample data - replace with real trend data in production
            createMiniTrendChart('orderTrendMini', 'rgb(40, 167, 69)', [12, 15, 13, 14, 18, 17, 22]);
            createMiniTrendChart('revenueTrendMini', 'rgb(0, 123, 255)', [8000, 8500, 7800, 9200, 9800, 9400, 10500]);
            createMiniTrendChart('userTrendMini', 'rgb(255, 193, 7)', [45, 48, 51, 53, 57, 62, 68]);
            createMiniTrendChart('conversionTrendMini', 'rgb(220, 53, 69)', [5.6, 5.8, 5.5, 5.3, 5.1, 4.9, 5.2]);
            
            // Toggle comparison view
            document.getElementById('comparePeriods').addEventListener('change', function() {
                const comparisonMetrics = document.getElementById('comparisonMetrics');
                
                if (this.checked) {
                    // Show comparison metrics with animated entrance
                    comparisonMetrics.innerHTML = `
                        <div class="col-12">
                            <div class="comparison-table-container">
                                <table class="comparison-table">
                                    <thead>
                                        <tr>
                                            <th>Metric</th>
                                            <th>Current Period</th>
                                            <th>Previous Period</th>
                                            <th>Change</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Orders</td>
                                            <td>${total_orders}</td>
                                            <td>${Math.round(total_orders * 0.88)}</td>
                                            <td class="up">+12.5%</td>
                                        </tr>
                                        <tr>
                                            <td>Revenue</td>
                                            <td>₹${total_revenue.toLocaleString()}</td>
                                            <td>₹${Math.round(total_revenue * 0.92).toLocaleString()}</td>
                                            <td class="up">+8.3%</td>
                                        </tr>
                                        <tr>
                                            <td>New Users</td>
                                            <td>${total_users}</td>
                                            <td>${Math.round(total_users * 0.86)}</td>
                                            <td class="up">+15.7%</td>
                                        </tr>
                                        <tr>
                                            <td>Conversion Rate</td>
                                            <td>${conversion_rate.toFixed(1)}%</td>
                                            <td>${(conversion_rate * 1.02).toFixed(1)}%</td>
                                            <td class="down">-2.1%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                    comparisonMetrics.style.display = 'flex';
                    
                    // Animate entrance
                    setTimeout(() => {
                        comparisonMetrics.style.opacity = '1';
                    }, 100);
                } else {
                    // Hide comparison metrics with fade-out
                    comparisonMetrics.style.opacity = '0';
                    setTimeout(() => {
                        comparisonMetrics.style.display = 'none';
                    }, 300);
                }
            });
            
            // Revenue Breakdown Chart
            const revenueBreakdownCtx = document.getElementById('revenueBreakdownChart').getContext('2d');
            const revenueBreakdownChart = new Chart(revenueBreakdownCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Platform Earnings (70%)', 'Lender Earnings (30%)', 'Security Deposits'],
                    datasets: [{
                        data: [
                            <?= $platform_earnings ?>, 
                            <?= $lender_earnings ?>, 
                            <?= $security_deposits ?>
                        ],
                        backgroundColor: [
                            'rgba(205, 38, 38, 0.7)',  // Platform earnings - red
                            'rgba(40, 167, 69, 0.7)',  // Lender earnings - green
                            'rgba(0, 123, 255, 0.7)'   // Security deposits - blue
                        ],
                        borderColor: [
                            'rgb(205, 38, 38)',
                            'rgb(40, 167, 69)',
                            'rgb(0, 123, 255)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = 'Rs ' + context.parsed.toLocaleString();
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((context.parsed / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Monthly Earnings Chart (simulated data - replace with real data)
            const monthlyEarningsCtx = document.getElementById('monthlyEarningsChart').getContext('2d');
            const monthlyEarningsChart = new Chart(monthlyEarningsCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Platform Earnings',
                        data: [
                            Math.round(<?= $total_earnings ?> * 0.05),
                            Math.round(<?= $total_earnings ?> * 0.06),
                            Math.round(<?= $total_earnings ?> * 0.07),
                            Math.round(<?= $total_earnings ?> * 0.08),
                            Math.round(<?= $total_earnings ?> * 0.10),
                            Math.round(<?= $total_earnings ?> * 0.11),
                            Math.round(<?= $total_earnings ?> * 0.09),
                            Math.round(<?= $total_earnings ?> * 0.12),
                            Math.round(<?= $total_earnings ?> * 0.09),
                            Math.round(<?= $total_earnings ?> * 0.08),
                            Math.round(<?= $total_earnings ?> * 0.07),
                            Math.round(<?= $total_earnings ?> * 0.08)
                        ],
                        backgroundColor: 'rgba(205, 38, 38, 0.7)',
                        borderColor: 'rgb(205, 38, 38)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Earnings (₹)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Earnings: ₹' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

    <!-- Outfit Image Modal -->
    <div class="modal fade" id="outfitImageModal" tabindex="-1" aria-labelledby="outfitImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="outfitImageModalLabel">Outfit Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="outfitImagePreview" src="" alt="Outfit Preview" class="img-fluid" style="max-height: 70vh;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a id="outfitDetailLink" href="#" class="btn btn-primary">View Outfit Details</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this function to your JavaScript -->
    <script>
        // Function to show outfit image in modal
        function showOutfitImageModal(imageSrc, outfitName) {
            const modal = new bootstrap.Modal(document.getElementById('outfitImageModal'));
            document.getElementById('outfitImageModalLabel').textContent = outfitName;
            document.getElementById('outfitImagePreview').src = imageSrc;
            
            // Get outfit ID from the image path
            const outfitId = imageSrc.match(/(\d+)_image/);
            if (outfitId && outfitId[1]) {
                document.getElementById('outfitDetailLink').href = 'outfit_management.php?id=' + outfitId[1];
            } else {
                document.getElementById('outfitDetailLink').style.display = 'none';
            }
            
            modal.show();
        }
    </script>

    <!-- Order Status Distribution Chart -->
    <script>
        // Order Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusLabels = [];
        const statusValues = [];
        const statusColors = [];
        
        <?php
        // Generate JavaScript arrays from PHP data
        foreach ($statusData as $status => $count) {
            $color = $statusColors[$status] ?? $defaultColor;
            echo "statusLabels.push('$status');\n";
            echo "statusValues.push($count);\n";
            echo "statusColors.push('$color');\n";
        }
        ?>
        
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusValues,
                    backgroundColor: statusColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            // Make legend text match the exact case from database
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map(function(label, i) {
                                        const meta = chart.getDatasetMeta(0);
                                        const style = meta.controller.getStyle(i);
                                        
                                        return {
                                            text: label,
                                            fillStyle: data.datasets[0].backgroundColor[i],
                                            strokeStyle: data.datasets[0].borderColor ? data.datasets[0].borderColor[i] : '#fff',
                                            lineWidth: data.datasets[0].borderWidth,
                                            hidden: !chart.getDataVisibility(i),
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.formattedValue;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((context.raw / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>