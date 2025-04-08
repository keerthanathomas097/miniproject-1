<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: ls.php");
    exit();
}

// Process security deposit refund
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['refund_deposit'])) {
    $order_id = $_POST['order_id'];
    
    // Update order to mark security deposit as refunded
    // Since there's no security_deposit_refunded column, we'll use notes field or add it
    $refund_query = "UPDATE tbl_orders SET notes = CONCAT(IFNULL(notes, ''), ' [Security deposit refunded on " . date('Y-m-d H:i:s') . "]') WHERE id = ?";
    $stmt = $conn->prepare($refund_query);
    $stmt->bind_param("i", $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Security deposit refund has been recorded successfully!";
    } else {
        $_SESSION['error_message'] = "Error processing refund: " . $conn->error;
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch order statistics
try {
    // Modified stats query to be more compatible
    $stats_query = "SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN order_status = 'CONFIRMED' THEN 1 ELSE 0 END) as active_orders,
        SUM(CASE WHEN order_status = 'COMPLETED' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN order_status = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(amount) as total_revenue
    FROM tbl_orders";
    
    $stats_result = $conn->query($stats_query);
    
    if ($stats_result === false) {
        throw new Exception("Error in statistics query: " . $conn->error);
    }
    
    $stats = $stats_result->fetch_assoc();
    
    // Count returned outfits separately to avoid issues with dates
    $returned_query = "SELECT COUNT(*) as returned_count 
                      FROM tbl_orders o 
                      JOIN tbl_measurements m ON o.user_id = m.user_id AND o.outfit_id = m.outfit_id
                      WHERE m.end_date < CURDATE()";
    $returned_result = $conn->query($returned_query);
    
    if ($returned_result === false) {
        $stats['returned_outfits'] = 0;
    } else {
        $returned_data = $returned_result->fetch_assoc();
        $stats['returned_outfits'] = $returned_data['returned_count'];
    }
    
} catch (Exception $e) {
    $error = "Error fetching statistics: " . $e->getMessage();
    $stats = [
        'total_orders' => 0,
        'active_orders' => 0,
        'completed_orders' => 0, 
        'cancelled_orders' => 0,
        'total_revenue' => 0,
        'returned_outfits' => 0
    ];
}

// Fetch all orders with lender and customer details
$query = "SELECT 
    o.id as order_id,
    o.order_reference,
    o.amount,
    o.rental_rate,
    o.security_deposit,
    o.payment_method,
    o.order_status,
    o.payment_status,
    o.created_at as order_date,
    o.notes,
    
    outfit.outfit_id,
    outfit.mrp,
    outfit.image1,
    outfit.email as lender_email,
    
    customer.name as customer_name,
    customer.email as customer_email,
    customer.phone as customer_phone,
    
    lender.name as lender_name,
    lender.phone as lender_phone,
    
    d.description_text,
    brand.subcategory_name as brand_name,
    type.subcategory_name as outfit_type,
    size.subcategory_name as size,
    
    m.start_date,
    m.end_date
    
FROM tbl_orders o
JOIN tbl_outfit outfit ON o.outfit_id = outfit.outfit_id
JOIN tbl_users customer ON o.user_id = customer.user_id
JOIN tbl_users lender ON outfit.email = lender.email
LEFT JOIN tbl_description d ON outfit.description_id = d.id
LEFT JOIN tbl_subcategory brand ON outfit.brand_id = brand.id
LEFT JOIN tbl_subcategory type ON outfit.type_id = type.id
LEFT JOIN tbl_subcategory size ON outfit.size_id = size.id
LEFT JOIN tbl_measurements m ON o.user_id = m.user_id AND o.outfit_id = m.outfit_id
ORDER BY o.created_at DESC";

try {
    $result = $conn->query($query);
    
    if ($result === false) {
        throw new Exception("Error in orders query: " . $conn->error);
    }
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Process image paths
        if (!empty($row['image1'])) {
            $baseImageNumber = str_replace('_image1.jpg', '', $row['image1']);
            $row['image_path'] = 'uploads/' . $baseImageNumber . '_image1.jpg';
        } else {
            $row['image_path'] = '';
        }
        
        // Check if security deposit was refunded based on notes
        $row['security_deposit_refunded'] = (strpos($row['notes'] ?? '', 'Security deposit refunded') !== false);
        
        $orders[] = $row;
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
    <title>Order Management | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #7c0a02;
            --primary-light: #9a1c11;
            --accent-color: #ffc107;
            --text-dark: #343a40;
            --text-light: #f8f9fa;
            --border-color: #dee2e6;
            --sidebar-width: 250px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-dark);
            margin: 0;
            padding: 0;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 240px;
            background-color: #932A2A;
            color: white;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }
        
        .brand-container {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
        }
        
        .brand-name {
            font-weight: 600;
            font-size: 22px;
            margin: 0;
            padding: 0;
            letter-spacing: 0.5px;
            color: white;
        }
        
        .brand-subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 3px;
            font-weight: 300;
        }
        
        .sidebar-section {
            margin-top: 20px;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        .sidebar-section-header {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            font-weight: 500;
            margin-bottom: 10px;
            padding-left: 5px;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav-item {
            margin-bottom: 2px;
        }
        
        .sidebar-nav-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.75);
            padding: 10px 15px;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 400;
        }
        
        .sidebar-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar-nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 500;
        }
        
        .sidebar-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 15px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            padding: 10px 0;
        }
        
        .main-content {
            margin-left: 240px;
            padding: 20px;
        }
        
        .stat-card {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .card-body {
            padding: 20px;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 10px;
        }
        
        .orders-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .order-tabs {
            margin-bottom: 20px;
        }

        .order-tabs .nav-link {
            color: #495057;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 10px 20px;
        }

        .order-tabs .nav-link.active {
            color: #7c0a02;
            border-bottom: 2px solid #7c0a02;
            background: none;
        }

        .search-container {
            position: relative;
            max-width: 400px;
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ced4da;
            border-radius: 50px;
            font-size: 14px;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .refund-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }

        .refund-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        .returned-alert {
            background-color: #fff3cd;
            color: #856404;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }

        /* Responsive table styles */
        .table-responsive {
            overflow-x: auto;
        }

        .order-table th {
            white-space: nowrap;
            background-color: #f8f9fa;
        }

        .order-table td {
            vertical-align: middle;
        }

        .outfit-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        /* Success message styles */
        .alert {
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Modal styles */
        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }
        
        .detail-section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .detail-label {
            width: 140px;
            font-weight: 500;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 200px;
            }
            
            .sidebar {
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand-container">
            <h1 class="brand-name">Clover Outfit Rentals</h1>
            <div class="brand-subtitle">Admin Dashboard</div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-header">DASHBOARD</div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="admin_dashboard.php" class="sidebar-nav-link">
                        <i class="fas fa-home sidebar-icon"></i>
                        Dashboard
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-header">MANAGEMENT</div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="users.php" class="sidebar-nav-link">
                        <i class="fas fa-users sidebar-icon"></i>
                        Users
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="outfit_management.php" class="sidebar-nav-link">
                        <i class="fas fa-tshirt sidebar-icon"></i>
                        Outfits
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="orders_admin.php" class="sidebar-nav-link active">
                        <i class="fas fa-shopping-cart sidebar-icon"></i>
                        Orders
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-header">ANALYTICS</div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="admin_reports.php" class="sidebar-nav-link">
                        <i class="fas fa-chart-bar sidebar-icon"></i>
                        Reports
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-header">SETTINGS</div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="admin_profile.php" class="sidebar-nav-link">
                        <i class="fas fa-user sidebar-icon"></i>
                        Profile
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="settings.php" class="sidebar-nav-link">
                        <i class="fas fa-cog sidebar-icon"></i>
                        Settings
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="logout.php" class="sidebar-nav-link">
                        <i class="fas fa-sign-out-alt sidebar-icon"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            © 2025 Clover Outfit Rentals
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <h2 class="mb-4">Order Management</h2>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-2">
                <div class="card bg-primary text-white stat-card">
                    <div class="card-body">
                        <h6>Total Orders</h6>
                        <h3><?php echo $stats['total_orders'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white stat-card">
                    <div class="card-body">
                        <h6>Active Orders</h6>
                        <h3><?php echo $stats['active_orders'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white stat-card">
                    <div class="card-body">
                        <h6>Completed</h6>
                        <h3><?php echo $stats['completed_orders'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-dark stat-card">
                    <div class="card-body">
                        <h6>Returned</h6>
                        <h3><?php echo $stats['returned_outfits'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white stat-card">
                    <div class="card-body">
                        <h6>Cancelled</h6>
                        <h3><?php echo $stats['cancelled_orders'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-dark text-white stat-card">
                    <div class="card-body">
                        <h6>Total Revenue</h6>
                        <h3>₹<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Container -->
        <div class="orders-container">
            <!-- Order Tabs -->
            <ul class="nav nav-tabs order-tabs" id="orderTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-orders-tab" data-bs-toggle="tab" data-bs-target="#all-orders" type="button" role="tab" aria-controls="all-orders" aria-selected="true">All Orders</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="returned-tab" data-bs-toggle="tab" data-bs-target="#returned" type="button" role="tab" aria-controls="returned" aria-selected="false">Returned Outfits</button>
                </li>
            </ul>

            <!-- Search Box -->
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="orderSearchInput" class="search-input" placeholder="Search orders...">
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="orderTabContent">
                <!-- All Orders Tab -->
                <div class="tab-pane fade show active" id="all-orders" role="tabpanel" aria-labelledby="all-orders-tab">
                    <div class="table-responsive">
                        <table class="table order-table" id="allOrdersTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Outfit</th>
                                    <th>Customer</th>
                                    <th>Lender</th>
                                    <th>Rental Period</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($orders) > 0): ?>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['order_reference']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($order['image_path']) && file_exists($order['image_path'])): ?>
                                                    <img src="<?php echo $order['image_path']; ?>" class="outfit-image me-2" alt="Outfit">
                                                <?php else: ?>
                                                    <div class="outfit-image me-2 bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-tshirt text-secondary"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-bold"><?php echo $order['description_text'] ?? 'Unnamed Outfit'; ?></div>
                                                    <small class="text-muted"><?php echo $order['brand_name'] ?? 'Unknown Brand'; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><?php echo $order['customer_name']; ?></div>
                                            <small class="text-muted"><?php echo $order['customer_phone']; ?></small>
                                        </td>
                                        <td>
                                            <div><?php echo $order['lender_name']; ?></div>
                                            <small class="text-muted"><?php echo $order['lender_phone']; ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($order['start_date']) && !empty($order['end_date'])): ?>
                                                <div><?php echo date('d M Y', strtotime($order['start_date'])); ?></div>
                                                <div><?php echo date('d M Y', strtotime($order['end_date'])); ?></div>
                                                <?php 
                                                    $today = new DateTime();
                                                    $endDate = new DateTime($order['end_date']);
                                                    if ($endDate < $today): 
                                                ?>
                                                    <span class="badge bg-warning text-dark">Returned</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div>₹<?php echo number_format($order['amount'], 2); ?></div>
                                            <small class="text-muted">Deposit: ₹<?php echo number_format($order['security_deposit'], 2); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = '';
                                                switch($order['order_status']) {
                                                    case 'CONFIRMED':
                                                        $statusClass = 'bg-primary';
                                                        break;
                                                    case 'COMPLETED':
                                                        $statusClass = 'bg-success';
                                                        break;
                                                    case 'CANCELLED':
                                                        $statusClass = 'bg-danger';
                                                        break;
                                                    default:
                                                        $statusClass = 'bg-secondary';
                                                }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo $order['order_status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary view-details" 
                                                    data-bs-toggle="modal" data-bs-target="#orderDetailsModal" 
                                                    data-order-id="<?php echo $order['order_id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                            <p class="mb-0">No orders available</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Returned Outfits Tab -->
                <div class="tab-pane fade" id="returned" role="tabpanel" aria-labelledby="returned-tab">
                    <div class="table-responsive">
                        <table class="table order-table" id="returnedTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Outfit</th>
                                    <th>Customer</th>
                                    <th>Lender</th>
                                    <th>Return Date</th>
                                    <th>Security Deposit</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $has_returned = false;
                                foreach ($orders as $order):
                                    if (!empty($order['end_date']) && strtotime($order['end_date']) < time()):
                                        $has_returned = true;
                                ?>
                                <tr>
                                    <td><?php echo $order['order_reference']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($order['image_path']) && file_exists($order['image_path'])): ?>
                                                <img src="<?php echo $order['image_path']; ?>" class="outfit-image me-2" alt="Outfit">
                                            <?php else: ?>
                                                <div class="outfit-image me-2 bg-light d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-tshirt text-secondary"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?php echo $order['description_text'] ?? 'Unnamed Outfit'; ?></div>
                                                <small class="text-muted"><?php echo $order['brand_name'] ?? 'Unknown Brand'; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo $order['customer_name']; ?></div>
                                        <small class="text-muted"><?php echo $order['customer_phone']; ?></small>
                                    </td>
                                    <td>
                                        <div><?php echo $order['lender_name']; ?></div>
                                        <small class="text-muted"><?php echo $order['lender_phone']; ?></small>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($order['end_date'])); ?></td>
                                    <td>₹<?php echo number_format($order['security_deposit'], 2); ?></td>
                                    <td>
                                        <?php if ($order['security_deposit_refunded']): ?>
                                            <span class="badge bg-success">Refunded</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending Refund</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$order['security_deposit_refunded']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                                <button type="submit" name="refund_deposit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-undo"></i> Refund Deposit
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-secondary" disabled>
                                                <i class="fas fa-check"></i> Refunded
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; endforeach; ?>
                                
                                <?php if (!$has_returned): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                        <p class="mb-0">No returned outfits available</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            
                            <!-- Lender Information -->
                            <div class="detail-section">
                                <h6 class="section-title">Lender Information</h6>
                                <div class="detail-row">
                                    <div class="detail-label">Name:</div>
                                    <div class="detail-value" id="lenderName"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Email:</div>
                                    <div class="detail-value" id="lenderEmail"></div>
                                </div>
                                <div class="detail-row">
                                    <div class="detail-label">Phone:</div>
                                    <div class="detail-value" id="lenderPhone"></div>
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
                            
                            <!-- Security Deposit Status -->
                            <div class="detail-section" id="depositSection">
                                <h6 class="section-title">Security Deposit Status</h6>
                                <div id="depositStatusBadge" class="text-center mb-3"></div>
                                <div id="depositActions" class="text-center">
                                    <form method="POST" id="refundForm" style="display: none;">
                                        <input type="hidden" name="order_id" id="modalOrderId">
                                        <button type="submit" name="refund_deposit" class="btn btn-success">
                                            <i class="fas fa-undo me-2"></i>Process Refund
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store order data for modal display
        const orderData = <?php echo json_encode($orders); ?>;
        
        // Search functionality
        document.getElementById('orderSearchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const activeTab = document.querySelector('.tab-pane.active');
            const tableRows = activeTab.querySelectorAll('tbody tr');

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
            
            // Show no results message if needed
            const visibleRows = activeTab.querySelectorAll('tbody tr[style="display: "]').length + 
                              activeTab.querySelectorAll('tbody tr:not([style])').length;
            
            let noResultsRow = activeTab.querySelector('.no-results-row');
            
            if (visibleRows === 0) {
                if (!noResultsRow) {
                    const tbody = activeTab.querySelector('tbody');
                    noResultsRow = document.createElement('tr');
                    noResultsRow.className = 'no-results-row';
                    noResultsRow.innerHTML = `
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-search fa-2x text-muted mb-3"></i>
                            <p>No orders found matching "${searchTerm}"</p>
                        </td>
                    `;
                    tbody.appendChild(noResultsRow);
                } else {
                    noResultsRow.style.display = '';
                    noResultsRow.querySelector('p').textContent = `No orders found matching "${searchTerm}"`;
                }
            } else if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
        });

        // Tab change handler - reset search
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('click', function() {
                document.getElementById('orderSearchInput').value = '';
                
                // Show all rows in the newly activated tab
                const targetId = this.getAttribute('data-bs-target');
                const targetTab = document.querySelector(targetId);
                const tableRows = targetTab.querySelectorAll('tbody tr');
                
                tableRows.forEach(row => {
                    if (!row.classList.contains('no-results-row')) {
                        row.style.display = '';
                    }
                });
                
                // Hide any no results message
                const noResultsRow = targetTab.querySelector('.no-results-row');
                if (noResultsRow) {
                    noResultsRow.style.display = 'none';
                }
            });
        });
        
        // Modal details population
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                populateModalWithData(orderId);
            });
        });
        
        function populateModalWithData(orderId) {
            // Find the order in our data
            const order = orderData.find(o => o.order_id == orderId);
            
            if (!order) return;
            
            // Update modal title
            document.getElementById('orderDetailsModalLabel').textContent = 
                `Order Details - ${order.order_reference}`;
            
            // Customer Information
            document.getElementById('customerName').textContent = order.customer_name || 'Not available';
            document.getElementById('customerEmail').textContent = order.customer_email || 'Not available';
            document.getElementById('customerPhone').textContent = order.customer_phone || 'Not available';
            
            // Lender Information
            document.getElementById('lenderName').textContent = order.lender_name || 'Not available';
            document.getElementById('lenderEmail').textContent = order.lender_email || 'Not available';
            document.getElementById('lenderPhone').textContent = order.lender_phone || 'Not available';
            
            // Rental Period
            const orderDate = new Date(order.order_date);
            document.getElementById('orderDate').textContent = orderDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
            
            const startDate = order.start_date ? new Date(order.start_date) : null;
            const endDate = order.end_date ? new Date(order.end_date) : null;
            
            document.getElementById('startDate').textContent = startDate ? 
                startDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 
                'Not specified';
            
            document.getElementById('endDate').textContent = endDate ? 
                endDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 
                'Not specified';
            
            // Calculate rental duration
            if (startDate && endDate) {
                const diffTime = Math.abs(endDate - startDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                document.getElementById('rentalDuration').textContent = `${diffDays} days`;
            } else {
                document.getElementById('rentalDuration').textContent = 'Not available';
            }
            
            // Outfit Details
            const outfitImage = document.getElementById('outfitImage');
            if (order.image_path && order.image_path.trim() !== '') {
                outfitImage.src = order.image_path;
                outfitImage.style.display = 'block';
            } else {
                outfitImage.style.display = 'none';
            }
            
            document.getElementById('outfitName').textContent = order.description_text || 'Not available';
            document.getElementById('outfitBrand').textContent = order.brand_name || 'Not available';
            document.getElementById('outfitType').textContent = order.outfit_type || 'Not available';
            document.getElementById('outfitSize').textContent = order.size || 'Not available';
            
            // Payment Details
            document.getElementById('orderReference').textContent = order.order_reference;
            document.getElementById('rentalRate').textContent = `₹${parseFloat(order.rental_rate || 0).toFixed(2)}`;
            document.getElementById('securityDeposit').textContent = `₹${parseFloat(order.security_deposit || 0).toFixed(2)}`;
            document.getElementById('totalAmount').textContent = `₹${parseFloat(order.amount || 0).toFixed(2)}`;
            document.getElementById('paymentMethod').textContent = order.payment_method || 'Not available';
            document.getElementById('paymentStatus').textContent = order.payment_status || 'Not available';
            
            // Security Deposit Status (only show for returned orders)
            const depositSection = document.getElementById('depositSection');
            const depositStatusBadge = document.getElementById('depositStatusBadge');
            const depositActions = document.getElementById('depositActions');
            const refundForm = document.getElementById('refundForm');
            const modalOrderId = document.getElementById('modalOrderId');
            
            // Check if order is past end date (returned)
            const isReturned = endDate && (new Date() > endDate);
            
            if (isReturned) {
                depositSection.style.display = 'block';
                
                if (order.security_deposit_refunded) {
                    depositStatusBadge.innerHTML = '<span class="badge bg-success p-2">Security Deposit Refunded</span>';
                    refundForm.style.display = 'none';
                } else {
                    depositStatusBadge.innerHTML = '<span class="badge bg-warning text-dark p-2">Security Deposit Pending Refund</span>';
                    refundForm.style.display = 'block';
                    modalOrderId.value = order.order_id;
                }
            } else {
                depositSection.style.display = 'none';
            }
        }
    </script>
</body>
</html>
</html>