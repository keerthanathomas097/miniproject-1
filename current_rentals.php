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
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Fetch orders and statistics
$orders = [];
$totalOrders = 0;
$activeOrders = 0;
$completedOrders = 0;
$cancelledOrders = 0;
$totalRevenue = 0;

try {
    // Count statistics
    $statsQuery = "SELECT 
                    COUNT(DISTINCT order_reference) as total_orders,
                    SUM(CASE WHEN o.order_status = 'CONFIRMED' THEN 1 ELSE 0 END) / COUNT(*) * COUNT(DISTINCT order_reference) as active_orders,
                    SUM(CASE WHEN o.order_status = 'COMPLETED' THEN 1 ELSE 0 END) / COUNT(*) * COUNT(DISTINCT order_reference) as completed_orders,
                    SUM(CASE WHEN o.order_status = 'CANCELLED' THEN 1 ELSE 0 END) / COUNT(*) * COUNT(DISTINCT order_reference) as cancelled_orders,
                    SUM(o.amount) / COUNT(*) * COUNT(DISTINCT order_reference) as total_revenue
                  FROM tbl_orders o
                  JOIN tbl_outfit outfit ON o.outfit_id = outfit.outfit_id
                  WHERE outfit.email = ?";
    
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->bind_param("s", $lenderEmail);
    $statsStmt->execute();
    $statsResult = $statsStmt->get_result();
    
    if ($statsResult->num_rows > 0) {
        $stats = $statsResult->fetch_assoc();
        $totalOrders = $stats['total_orders'];
        $activeOrders = $stats['active_orders'];
        $completedOrders = $stats['completed_orders'];
        $cancelledOrders = $stats['cancelled_orders'];
        $totalRevenue = $stats['total_revenue'] ?: 0;
    }
    
    $statsStmt->close();

    // Fetch orders with all necessary details
    $query = "SELECT DISTINCT 
                o.id as order_id, 
                o.user_id as renter_id,
                o.outfit_id, 
                o.order_reference, 
                o.amount, 
                o.rental_rate,
                o.security_deposit,
                o.payment_method, 
                o.order_status, 
                o.payment_status, 
                o.created_at as order_date,
                
                outfit.mrp, 
                outfit.image1,
                outfit.description_id,
                
                renter.name as renter_name, 
                renter.email as renter_email, 
                renter.phone as renter_phone,
                
                d.description_text,
                
                brand.subcategory_name as brand_name,
                type.subcategory_name as outfit_type,
                size.subcategory_name as size,
                
                m.start_date, 
                m.end_date, 
                m.height, 
                m.shoulder, 
                m.bust, 
                m.waist
                
              FROM tbl_orders o
              JOIN tbl_outfit outfit ON o.outfit_id = outfit.outfit_id
              JOIN tbl_users renter ON o.user_id = renter.user_id
              LEFT JOIN tbl_description d ON outfit.description_id = d.id
              LEFT JOIN tbl_subcategory brand ON outfit.brand_id = brand.id
              LEFT JOIN tbl_subcategory type ON outfit.type_id = type.id
              LEFT JOIN tbl_subcategory size ON outfit.size_id = size.id
              LEFT JOIN tbl_measurements m ON m.user_id = o.user_id AND m.outfit_id = o.outfit_id
              WHERE outfit.email = ?
              GROUP BY o.order_reference
              ORDER BY o.created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparing orders query: " . $conn->error);
    }
    
    $stmt->bind_param("s", $lenderEmail);
    if (!$stmt->execute()) {
        throw new Exception("Error executing orders query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Handle image path processing
        if (!empty($row['image1'])) {
            $baseImageNumber = str_replace('_image1.jpg', '', $row['image1']);
            $row['image_path'] = 'uploads/' . $baseImageNumber . '_image1.jpg';
        } else {
            $row['image_path'] = '';
        }
        
        // Add the row to orders array
        $orders[] = $row;
    }
    
    $stmt->close();

    // Add this after the query execution
    foreach ($orders as $order) {
        error_log("Order Reference: " . $order['order_reference'] . 
                  ", Outfit: " . $order['description_text'] . 
                  ", Customer: " . $order['renter_name'] . 
                  ", Date: " . $order['order_date']);
    }
} catch (Exception $e) {
    $error = "Error fetching orders: " . $e->getMessage();
    $orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Orders | Fashion Share</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        :root {
            --primary-color: #7c0a02;
            --primary-light: #9a1c11;
            --accent-color: #ffc107;
            --text-dark: #343a40;
            --text-light: #f8f9fa;
            --border-color: #dee2e6;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: var(--text-dark);
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 250px;
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            overflow-y: auto;
            z-index: 100;
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
            background-color: var(--primary-light);
            text-decoration: none;
            color: white;
        }
        
        .menu-item i {
            margin-right: 10px;
            width: 20px;
        }
        
        .menu-item.active {
            background-color: var(--primary-light);
            border-left: 4px solid var(--accent-color);
        }
        
        .main-content {
            margin-left: 240px;
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            min-width: 200px;
            background: white;
            border-radius: 8px;
            margin-right: 20px;
            margin-bottom: 20px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card:last-child {
            margin-right: 0;
        }
        
        .stat-card .stat-title {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .stat-card .stat-icon {
            float: right;
            font-size: 40px;
            opacity: 0.2;
            margin-top: -40px;
        }
        
        .stat-primary { border-top: 3px solid #007bff; }
        .stat-success { border-top: 3px solid #28a745; }
        .stat-warning { border-top: 3px solid #ffc107; }
        .stat-danger { border-top: 3px solid #dc3545; }
        
        .orders-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }
        
        .orders-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .orders-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }
        
        .search-container {
            position: relative;
            max-width: 400px;
            margin-bottom: 20px;
        }
        
        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ced4da;
            border-radius: 50px;
            font-size: 14px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(124, 10, 2, 0.25);
        }
        
        .table-responsive {
            margin-bottom: 20px;
        }
        
        .order-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .order-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            text-align: left;
        }
        
        .order-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        .order-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .customer-info {
            display: flex;
            align-items: center;
        }
        
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: 600;
            color: #6c757d;
        }
        
        .customer-details {
            flex-grow: 1;
        }
        
        .customer-name {
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        .customer-email {
            font-size: 12px;
            color: #6c757d;
        }
        
        .outfit-info {
            display: flex;
            align-items: center;
        }
        
        .outfit-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 10px;
        }
        
        .outfit-details {
            flex-grow: 1;
        }
        
        .outfit-name {
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        .outfit-brand {
            font-size: 12px;
            color: #6c757d;
        }
        
        .badge-status {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-confirmed {
            background-color: #e3f2fd;
            color: #0d6efd;
        }
        
        .status-completed {
            background-color: #d1e7dd;
            color: #198754;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #dc3545;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #ffc107;
        }
        
        .action-btn {
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 4px;
        }
        
        .view-btn {
            background-color: var(--accent-color);
            color: var(--text-dark);
            border: none;
        }
        
        .view-btn:hover {
            background-color: #e0a800;
            color: var(--text-dark);
        }
        
        .no-orders {
            padding: 40px 20px;
            text-align: center;
        }
        
        .no-orders i {
            font-size: 60px;
            color: #e9ecef;
            margin-bottom: 20px;
        }
        
        .no-orders-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #495057;
        }
        
        .no-orders-message {
            color: #6c757d;
            max-width: 500px;
            margin: 0 auto 20px;
        }
        
        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .detail-section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .detail-label {
            font-weight: 500;
            width: 130px;
            color: #6c757d;
        }
        
        .detail-value {
            flex: 1;
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            
            .sidebar-header h3, .sidebar-header p, .nav-link span {
                display: none;
            }
            
            .nav-link {
                padding: 15px 0;
                justify-content: center;
            }
            
            .nav-link i {
                margin-right: 0;
                font-size: 20px;
            }
            
            .main-content {
                margin-left: 80px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            
            .stat-card {
                min-width: calc(50% - 15px);
                margin-right: 15px;
            }
            
            .orders-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .orders-title {
                margin-bottom: 15px;
            }
            
            .search-container {
                width: 100%;
                max-width: none;
            }
        }
        
        @media (max-width: 576px) {
            .stat-card {
                min-width: 100%;
                margin-right: 0;
            }
            
            .customer-email, .outfit-brand {
                display: none;
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
            <a href="lender_dashboard.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="lending.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-plus-circle"></i> Lend Outfit
            </a>
            <a href="my_outfits.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-tshirt"></i> My Outfits
            </a>
            <a href="current_rentals.php" class="menu-item active" style="text-decoration: none; color: white;">
                <i class="fas fa-exchange-alt"></i> Rentals
            </a>
            <a href="earnings.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-money-bill-wave"></i> Earnings
            </a>
            <a href="profile.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-user"></i> Profile 
            </a>
            <a href="settings.php" class="menu-item" style="text-decoration: none; color: white;">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1 class="page-title">Rental Orders</h1>
            <p class="text-muted">Manage all your outfit rental orders in one place</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card stat-primary">
                <div class="stat-title">Total Orders</div>
                <div class="stat-value"><?php echo $totalOrders; ?></div>
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
            </div>
            
            <div class="stat-card stat-success">
                <div class="stat-title">Active Orders</div>
                <div class="stat-value"><?php echo $activeOrders; ?></div>
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            </div>
            
            <div class="stat-card stat-warning">
                <div class="stat-title">Completed</div>
                <div class="stat-value"><?php echo $completedOrders; ?></div>
                <div class="stat-icon"><i class="fas fa-flag-checkered"></i></div>
            </div>
            
            <div class="stat-card stat-danger">
                <div class="stat-title">Cancelled</div>
                <div class="stat-value"><?php echo $cancelledOrders; ?></div>
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            </div>
            
            <div class="stat-card stat-primary">
                <div class="stat-title">Total Revenue</div>
                <div class="stat-value">₹<?php echo number_format($totalRevenue, 2); ?></div>
                <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
            </div>
        </div>
        
        <!-- Orders Container -->
        <div class="orders-container">
            <div class="orders-header">
                <h3 class="orders-title">All Rental Orders</h3>
            </div>
            
            <!-- Search Box -->
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="orderSearchInput" class="search-input" placeholder="Search orders by customer, outfit, order ID...">
            </div>
            
            <?php if (count($orders) > 0): ?>
                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="order-table" id="orderTable">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Outfit</th>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-avatar">
                                                <?php echo substr($order['renter_name'] ?? 'U', 0, 1); ?>
                                            </div>
                                            <div class="customer-details">
                                                <div class="customer-name"><?php echo $order['renter_name'] ?? 'Unknown User'; ?></div>
                                                <div class="customer-email"><?php echo $order['renter_email'] ?? 'No email'; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="outfit-info">
                                            <?php if (!empty($order['image_path'])): ?>
                                                <img src="<?php echo htmlspecialchars($order['image_path']); ?>" alt="Outfit" class="outfit-image">
                                            <?php else: ?>
                                                <div class="outfit-image d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-tshirt text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="outfit-details">
                                                <div class="outfit-name"><?php echo $order['description_text'] ?? 'Unnamed Outfit'; ?></div>
                                                <div class="outfit-brand"><?php echo $order['brand_name'] ?? 'Unknown Brand'; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $order['order_reference']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                    <td>₹<?php echo number_format($order['amount'], 2); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($order['order_status']) {
                                            case 'CONFIRMED':
                                                $statusClass = 'status-confirmed';
                                                break;
                                            case 'COMPLETED':
                                                $statusClass = 'status-completed';
                                                break;
                                            case 'CANCELLED':
                                                $statusClass = 'status-cancelled';
                                                break;
                                            default:
                                                $statusClass = 'status-pending';
                                        }
                                        ?>
                                        <span class="badge-status <?php echo $statusClass; ?>"><?php echo $order['order_status']; ?></span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn action-btn view-btn" data-toggle="modal" data-target="#orderDetailsModal" data-order-id="<?php echo $order['order_id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <!-- No Orders Message -->
                <div class="no-orders">
                    <i class="fas fa-box-open"></i>
                    <h3 class="no-orders-title">No Orders Yet</h3>
                    <p class="no-orders-message">
                        You don't have any rental orders for your outfits yet. Once customers start renting your collection, they'll appear here.
                    </p>
                    <a href="lending.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle mr-2"></i> Add More Outfits
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Customer Information -->
                            <div class="detail-section">
                                <h6 class="section-title">Customer Information</h6>
                                <div class="detail-row">
                                    <div class="detail-label">Name:</div>
                                    <div class="detail-value" id="customerName"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Email:</div>
                                    <div class="detail-value" id="customerEmail"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Phone:</div>
                                    <div class="detail-value" id="customerPhone"></div>
                                </div>
                            </div>
                            
                            <!-- Rental Period -->
                            <div class="detail-section">
                                <h6 class="section-title">Rental Period</h6>
                                <div class="detail-row">
                                    <div class="detail-label">Order Date:</div>
                                    <div class="detail-value" id="orderDate"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Start Date:</div>
                                    <div class="detail-value" id="startDate"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">End Date:</div>
                                    <div class="detail-value" id="endDate"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Duration:</div>
                                    <div class="detail-value" id="rentalDuration"></div>
                                </div>
                            </div>
                            
                            <!-- Customer Measurements -->
                            <div class="detail-section">
                                <h6 class="section-title">Customer Measurements</h6>
                                <div class="detail-row">
                                    <div class="detail-label">Height:</div>
                                    <div class="detail-value" id="customerHeight"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Shoulder:</div>
                                    <div class="detail-value" id="customerShoulder"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Bust:</div>
                                    <div class="detail-value" id="customerBust"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Waist:</div>
                                    <div class="detail-value" id="customerWaist"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Outfit Details -->
                            <div class="detail-section">
                                <h6 class="section-title">Outfit Details</h6>
                                <div class="text-center mb-3">
                                    <img id="outfitImage" src="" alt="Outfit" class="img-fluid" style="max-height: 150px; border-radius: 5px;">
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Name:</div>
                                    <div class="detail-value" id="outfitName"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Brand:</div>
                                    <div class="detail-value" id="outfitBrand"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Type:</div>
                                    <div class="detail-value" id="outfitType"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Size:</div>
                                    <div class="detail-value" id="outfitSize"></div>
                                </div>
                            </div>
                            
                            <!-- Payment Details -->
                            <div class="detail-section">
                                <h6 class="section-title">Payment Details</h6>
                                <div class="detail-row">
                                    <div class="detail-label">Order ID:</div>
                                    <div class="detail-value" id="orderReference"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Rental Rate:</div>
                                    <div class="detail-value" id="rentalRate"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Security Deposit:</div>
                                    <div class="detail-value" id="securityDeposit"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Total Amount:</div>
                                    <div class="detail-value" id="totalAmount"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Payment Method:</div>
                                    <div class="detail-value" id="paymentMethod"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Payment Status:</div>
                                    <div class="detail-value" id="paymentStatus"></div>
                                </div>
                            </div>
                            
                            <!-- Order Status -->
                            <div class="detail-section">
                                <h6 class="section-title">Order Status</h6>
                                <div class="text-center">
                                    <span class="badge-status" id="orderStatus" style="font-size: 14px; padding: 8px 15px;"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Order search functionality
            $("#orderSearchInput").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#orderTable tbody tr").filter(function() {
                    var rowText = $(this).text().toLowerCase();
                    $(this).toggle(rowText.indexOf(value) > -1);
                });
            });
            
            // Store order data for modal display
            var orderData = <?php echo json_encode($orders); ?>;
            
            // Modal data population
            $('#orderDetailsModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var orderId = button.data('order-id');
                var modal = $(this);
                
                // Find the order in our data
                var order = orderData.find(function(o) {
                    return o.order_id == orderId;
                });
                
                if (order) {
                    // Customer Information
                    modal.find('#customerName').text(order.renter_name || 'Not available');
                    modal.find('#customerEmail').text(order.renter_email || 'Not available');
                    modal.find('#customerPhone').text(order.renter_phone || 'Not available');
                    
                    // Rental Period
                    var orderDate = new Date(order.order_date);
                    modal.find('#orderDate').text(orderDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    }));
                    
                    var startDate = order.start_date ? new Date(order.start_date) : null;
                    var endDate = order.end_date ? new Date(order.end_date) : null;
                    
                    modal.find('#startDate').text(startDate ? startDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    }) : 'Not specified');
                    
                    modal.find('#endDate').text(endDate ? endDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    }) : 'Not specified');
                    
                    // Calculate duration if dates are available
                    if (startDate && endDate) {
                        var diffTime = Math.abs(endDate - startDate);
                        var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        modal.find('#rentalDuration').text(diffDays + ' days');
                    } else {
                        modal.find('#rentalDuration').text('Not available');
                    }
                    
                    // Measurements
                    modal.find('#customerHeight').text(order.height ? order.height + ' cm' : 'Not provided');
                    modal.find('#customerShoulder').text(order.shoulder ? order.shoulder + ' inches' : 'Not provided');
                    modal.find('#customerBust').text(order.bust ? order.bust + ' inches' : 'Not provided');
                    modal.find('#customerWaist').text(order.waist ? order.waist + ' inches' : 'Not provided');
                    
                    // Outfit Details
                    if (order.image_path) {
                        modal.find('#outfitImage').attr('src', order.image_path).show();
                    } else {
                        modal.find('#outfitImage').hide();
                    }
                    
                    modal.find('#outfitName').text(order.description_text || 'Not available');
                    modal.find('#outfitBrand').text(order.brand_name || 'Not available');
                    modal.find('#outfitType').text(order.outfit_type || 'Not available');
                    modal.find('#outfitSize').text(order.size || 'Not available');
                    
                    // Payment Details
                    modal.find('#orderReference').text('#' + order.order_reference);
                    modal.find('#rentalRate').text('₹' + (parseFloat(order.rental_rate || 0).toFixed(2)));
                    modal.find('#securityDeposit').text('₹' + (parseFloat(order.security_deposit || 0).toFixed(2)));
                    modal.find('#totalAmount').text('₹' + (parseFloat(order.amount).toFixed(2)));
                    modal.find('#paymentMethod').text(order.payment_method || 'Not available');
                    modal.find('#paymentStatus').text(order.payment_status || 'Not available');
                    
                    // Order Status
                    var statusElement = modal.find('#orderStatus');
                    statusElement.text(order.order_status);
                    
                    // Remove any existing status classes
                    statusElement.removeClass('status-confirmed status-completed status-cancelled status-pending');
                    
                    // Add appropriate status class
                    switch (order.order_status) {
                        case 'CONFIRMED':
                            statusElement.addClass('status-confirmed');
                            break;
                        case 'COMPLETED':
                            statusElement.addClass('status-completed');
                            break;
                        case 'CANCELLED':
                            statusElement.addClass('status-cancelled');
                            break;
                        default:
                            statusElement.addClass('status-pending');
                    }
                }
            });
        });
    </script>
</body>
</html>