<?php
session_start();
include 'connect.php';

// Add this at the top of confirmation.php after session_start()
function getLatestOrderId($conn, $userId) {
    $sql = "SELECT id FROM tbl_orders WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    return 0;
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// If order ID is missing or is 1 (the problematic ID), try to get the latest
if ($order_id <= 1 && isset($_SESSION['id'])) {
    $latest_id = getLatestOrderId($conn, $_SESSION['id']);
    
    if ($latest_id > 1) {
        // Redirect to the correct order
        header("Location: confirmation.php?order_id=$latest_id");
        exit();
    }
}

// Check if we're being redirected to order_id=1 by mistake
if (isset($_GET['order_id']) && $_GET['order_id'] == 1) {
    // Check if we have a more recent order ID in the session
    if (isset($_SESSION['current_order_id']) && $_SESSION['current_order_id'] > 1) {
        $correct_order_id = $_SESSION['current_order_id'];
        
        // Log this redirection issue
        $redirect_log = fopen("redirect_fix_log.txt", "a");
        fwrite($redirect_log, date('Y-m-d H:i:s') . " - Fixing redirect from order 1 to " . $correct_order_id . "\n");
        fwrite($redirect_log, "Session data: " . print_r($_SESSION, true) . "\n");
        fwrite($redirect_log, "GET data: " . print_r($_GET, true) . "\n\n");
        fclose($redirect_log);
        
        // Redirect to the correct order confirmation
        header("Location: confirmation.php?order_id=" . $correct_order_id);
        exit();
    }
}

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: ls.php");
    exit();
}

// Get order ID from URL and validate it
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    // Invalid order ID
    header("Location: index.php");
    exit();
}

// Fetch order details from the database with correct column references
$query = "SELECT o.*, 
          u.name as user_name, u.email as user_email, u.phone as user_phone,
          outfit.outfit_id, outfit.mrp as outfit_price, outfit.image1 as outfit_image,
          d.description_text
               FROM tbl_orders o
          LEFT JOIN tbl_users u ON o.user_id = u.user_id
          LEFT JOIN tbl_outfit outfit ON o.outfit_id = outfit.outfit_id
          LEFT JOIN tbl_description d ON outfit.description_id = d.id
          WHERE o.id = ?";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing order query: " . $conn->error);
}

$stmt->bind_param("i", $order_id);
if (!$stmt->execute()) {
    die("Error executing order query: " . $stmt->error);
}

$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if (!$order) {
    // Order not found
    echo "<div class='alert alert-danger'>Order not found!</div>";
    echo "<p><a href='index.php'>Return to home page</a></p>";
    exit();
}

// Check for start_date and end_date, use session values if available
if (!isset($order['start_date']) && isset($_SESSION['rental_start_date'])) {
    $order['start_date'] = $_SESSION['rental_start_date'];
}

if (!isset($order['end_date']) && isset($_SESSION['rental_end_date'])) {
    $order['end_date'] = $_SESSION['rental_end_date'];
}

// If still no dates, use default values (current date + 5 days)
if (!isset($order['start_date'])) {
    $order['start_date'] = date('Y-m-d');
}

if (!isset($order['end_date'])) {
    $order['end_date'] = date('Y-m-d', strtotime($order['start_date'] . ' +5 days'));
}

// Get user details - use order's user_id instead of relying on already joined data
$user_id = $order['user_id'] ?? 0;
if ($user_id > 0) {
    $user_query = "SELECT name, email, phone FROM tbl_users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_query);
    
    if ($user_stmt === false) {
        $user = [
            'name' => 'Customer',
            'email' => 'N/A',
            'phone' => 'N/A'
        ];
    } else {
        $user_stmt->bind_param("i", $user_id);
        if ($user_stmt->execute()) {
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
        } else {
            $user = [
                'name' => 'Customer',
                'email' => 'N/A',
                'phone' => 'N/A'
            ];
        }
$user_stmt->close();
    }
} else {
    $user = [
        'name' => 'Customer',
        'email' => 'N/A',
        'phone' => 'N/A'
    ];
}

// Calculate order details
$order_total = $order['amount'] ?? 0;
$actual_rental = ($order['outfit_price'] ?? 0) * 0.10; // 10% of MRP
$security_deposit = ($order['outfit_price'] ?? 0) * 0.10; // 10% of MRP
$delivery_charge = 199.00;
$gst = $actual_rental * 0.18; // GST on rental amount

// Generate tracking number and confirmation code if not exists
$tracking_number = $order['tracking_number'] ?? 'TR' . strtoupper(substr(md5(uniqid()), 0, 8));
$confirmation_code = $order['confirmation_code'] ?? strtoupper(substr(md5($order_id . $order['user_id']), 0, 8));

// Get estimated delivery date (1 day before rental start date)
$delivery_date = date('Y-m-d', strtotime($order['start_date'] . ' -1 day'));
$order_date = date('Y-m-d H:i:s', strtotime($order['created_at'] ?? 'now')); // Use order creation time if available

// Update order with tracking and confirmation code if needed - check if these columns exist first
$column_check = $conn->query("SHOW COLUMNS FROM tbl_orders LIKE 'tracking_number'");
if ($column_check && $column_check->num_rows > 0) {
    // Only update if the column exists
    $update_query = "UPDATE tbl_orders SET 
                     tracking_number = ?, confirmation_code = ?
                    WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    if ($update_stmt) {
    $update_stmt->bind_param("ssi", $tracking_number, $confirmation_code, $order_id);
    $update_stmt->execute();
    $update_stmt->close();
    }
}

$outfit_id = isset($_GET['outfit_id']) ? $_GET['outfit_id'] : null;

if (!$outfit_id) {
    header("Location: outfit.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | Clover Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/navbar.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #212529;
        }
        
        .main-content {
            padding: 30px 0;
        }
        
        .confirmation-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            padding: 0;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .confirmation-header {
            background-color: #f0f9f4;
            padding: 30px;
            border-bottom: 1px solid #e9ecef;
            text-align: center;
        }
        
        .confirmation-icon {
            width: 80px;
            height: 80px;
            background-color: #198754;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
        }
        
        .confirmation-header h2 {
            margin: 0 0 10px;
            font-size: 2rem;
            font-weight: 600;
            color: #198754;
        }
        
        .confirmation-header p {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 0;
        }
        
        .confirmation-body {
            padding: 30px;
        }
        
        .order-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
        }
        
        .detail-label {
            width: 40%;
            font-weight: 500;
            color: #6c757d;
        }
        
        .detail-value {
            width: 60%;
            font-weight: 500;
        }
        
        .product-card {
            display: flex;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
            margin-bottom: 20px;
        }
        
        .product-image {
            width: 100px;
            height: 120px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        
        .product-details h5 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .product-price {
            font-weight: 600;
            color: #495057;
        }
        
        .price-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            font-size: 1.1rem;
            font-weight: 700;
        }
        
        .confirmation-box {
            background-color: #e6f7ff;
            border-left: 4px solid #1890ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .rental-period {
            background-color: #f0f9f4;
            border-left: 4px solid #198754;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .rental-period p {
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .rental-period strong {
            color: #198754;
        }
        
        .rental-dates {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        
        .date-box {
            text-align: center;
            flex: 1;
            padding: 5px;
        }
        
        .date-box p {
            margin: 0;
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .date-box strong {
            font-size: 0.9rem;
        }
        
        .date-arrow {
            display: flex;
            align-items: center;
            padding: 0 10px;
            color: #6c757d;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .action-btn {
            flex: 1;
            padding: 12px;
            border-radius: 5px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .primary-btn {
            background-color: #212529;
            color: #fff;
            border: none;
        }
        
        .primary-btn:hover {
            background-color: #343a40;
            transform: translateY(-2px);
        }
        
        .secondary-btn {
            background-color: transparent;
            color: #212529;
            border: 1px solid #212529;
        }
        
        .secondary-btn:hover {
            background-color: #f8f9fa;
            transform: translateY(-2px);
        }
        
        .order-steps {
            margin: 30px 0;
            padding: 0;
        }
        
        .step-item {
            display: flex;
            margin-bottom: 30px;
            position: relative;
        }
        
        .step-item:last-child {
            margin-bottom: 0;
        }
        
        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 30px;
            left: 15px;
            width: 2px;
            height: calc(100% - 15px);
            background-color: #198754;
        }
        
        .step-icon {
            width: 30px;
            height: 30px;
            background-color: #198754;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
            position: relative;
            z-index: 1;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .step-desc {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .qr-code {
            width: 150px;
            height: 150px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }
        
        .qr-code img {
            width: 80%;
            height: auto;
        }
        
        .confirmation-footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            border-top: 1px solid #e9ecef;
            text-align: center;
        }
        
        .help-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .social-share {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        
        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }
        
        .share-btn:hover {
            transform: translateY(-3px);
        }
        
        .fb {
            background-color: #3b5998;
        }
        
        .tw {
            background-color: #1da1f2;
        }
        
        .wa {
            background-color: #25d366;
        }
        
        .ig {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }
        
        .measurement-info {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .measurement-table {
            width: 100%;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .measurement-table td {
            padding: 5px 10px;
        }
        
        .measurement-table td:first-child {
            font-weight: 600;
            width: 40%;
        }
        
        @media (max-width: 768px) {
            .detail-row {
                flex-direction: column;
                margin-bottom: 20px;
            }
            
            .detail-label, .detail-value {
                width: 100%;
            }
            
            .detail-label {
                margin-bottom: 5px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f0f;
            opacity: 0;
            top: 0;
            animation: confetti-fall 5s ease-in-out infinite;
        }
        
        @keyframes confetti-fall {
            0% {
                opacity: 1;
                top: -10px;
                transform: translateX(0) rotate(0deg);
            }
            100% {
                opacity: 0;
                top: 100vh;
                transform: translateX(100px) rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="Clover Logo" height="60">
                <div>
                    <h1 class="company-name">Clover</h1>
                    <p class="company-subtitle">Outfit Rentals</p>
                </div>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <div class="nav-links ms-auto">
                    <a href="outfit.php" class="nav-link">RENT OUTFITS</a>
                    <a href="lending.php" class="nav-link">EARN THROUGH US</a>
                    <a href="outfit.php?gender=male" class="nav-link">MEN</a>
                    <a href="outfit.php?occasion=wedding" class="nav-link">BRIDAL</a>
                    
                    <span class="nav-link user-name">Welcome, <?php echo htmlspecialchars($user['name'] ?? 'Guest'); ?></span>
                    
                    <div class="nav-icons">
                        <a href="cart.php" class="icon-link">
                            <i class="bi bi-bag"></i>
                        </a>
                        <div class="dropdown">
                            <a class="icon-link" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person"></i>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="profileDropdown">
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </div>
                        <a href="index.php" class="icon-link">
                            <i class="bi bi-house"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container main-content">
        <div class="row">
            <div class="col-12">
                <div class="confirmation-container">
                    <!-- Confirmation Header with Success Icon -->
                    <div class="confirmation-header">
                        <div class="confirmation-icon">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <h2>Order Confirmed!</h2>
                        <p>Thank you for your order. Your confirmation code is <strong><?php echo $confirmation_code; ?></strong></p>
                    </div>
                    
                    <div class="confirmation-body">
                        <div class="row">
                            <!-- Left Column: Order Details -->
                            <div class="col-lg-8">
                                <!-- Confirmation Message -->
                                <div class="confirmation-box mb-4">
                                    <h5 class="mb-2"><i class="bi bi-envelope-check me-2"></i>Confirmation Email Sent</h5>
                                    <p class="mb-0">We've sent a confirmation email to <strong><?php echo htmlspecialchars($user['email'] ?? 'your email'); ?></strong> with all your order details.</p>
                                </div>
                                
                                <!-- Order Information -->
                                <div class="order-details">
                                    <h3 class="section-title"><i class="bi bi-receipt me-2"></i>Order Information</h3>
                                    <div class="detail-row">
                                        <div class="detail-label">Order Number:</div>
                                        <div class="detail-value">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Order Date:</div>
                                        <div class="detail-value"><?php echo date('d M Y, h:i A', strtotime($order_date)); ?></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Payment Method:</div>
                                        <div class="detail-value">
                                            <?php 
                                            $payment_method = $order['payment_method'] ?? 'Credit Card';
                                            if ($payment_method == 'credit_card') echo 'Credit/Debit Card';
                                            elseif ($payment_method == 'upi') echo 'UPI';
                                            elseif ($payment_method == 'cod') echo 'Cash on Delivery';
                                            else echo $payment_method;
                                            ?>
                                        </div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Payment Status:</div>
                                        <div class="detail-value"><span class="badge bg-success">Paid</span></div>
                                    </div>
                                    <div class="detail-row">
                                        <div class="detail-label">Tracking Number:</div>
                                        <div class="detail-value"><?php echo $tracking_number; ?></div>
                                    </div>
                                </div>
                                
                                <!-- Rental Period Information -->
                                <div class="rental-period mt-4">
                                    <p><strong>Rental Period:</strong> 5 Days</p>
                                    <div class="rental-dates">
                                        <div class="date-box">
                                            <p>Start Date</p>
                                            <strong><?php echo date('d M Y', strtotime($order['start_date'])); ?></strong>
                                        </div>
                                        <div class="date-arrow">
                                            <i class="bi bi-arrow-right"></i>
                                        </div>
                                        <div class="date-box">
                                            <p>End Date</p>
                                            <strong><?php echo date('d M Y', strtotime($order['end_date'])); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Product Information -->
                                <h3 class="section-title mt-4"><i class="bi bi-bag-check me-2"></i>Outfit Information</h3>
                                <div class="product-card">
                                    <img src="<?php echo htmlspecialchars($order['outfit_image'] ?? 'images/placeholder.jpg'); ?>" 
                                         alt="Product Image" class="img-fluid">
                                    <div class="product-details">
                                        <h5><?php echo htmlspecialchars($order['description_text'] ?? 'Product Details'); ?></h5>
                                        <h6><?php echo htmlspecialchars($order['description_text'] ?? 'Product Details'); ?></h6>
                                        <p class="mb-1">Brand: <?php echo htmlspecialchars($order['brand_name']); ?></p>
                                        <p class="mb-1">Rental: 5 Days</p>
                                        <p class="product-price">₹<?php echo number_format($actual_rental, 2); ?></p>
                                    </div>
                                </div>
                                
                                <!-- Measurement Information -->
                                <div class="mt-4">
                                    <h3 class="section-title"><i class="bi bi-rulers me-2"></i>Your Measurements</h3>
                                    <div class="measurement-info">
                                        <table class="measurement-table">
                                            <tr>
                                                <td>Height:</td>
                                                <td><?php echo htmlspecialchars($order['height'] ?? 'Not specified'); ?> inches</td>
                                            </tr>
                                            <tr>
                                                <td>Shoulder Width:</td>
                                                <td><?php echo htmlspecialchars($order['shoulder'] ?? 'Not specified'); ?> inches</td>
                                            </tr>
                                            <tr>
                                                <td>Bust:</td>
                                                <td><?php echo htmlspecialchars($order['bust'] ?? 'Not specified'); ?> inches</td>
                                            </tr>
                                            <tr>
                                                <td>Waist:</td>
                                                <td><?php echo htmlspecialchars($order['waist'] ?? 'Not specified'); ?> inches</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Delivery Information -->
                                <h3 class="section-title mt-4"><i class="bi bi-truck me-2"></i>Delivery Information</h3>
                                <div class="confirmation-box">
                                    <h5 class="mb-2">Estimated Delivery: <?php echo date('d M Y', strtotime($delivery_date)); ?></h5>
                                    <p class="mb-0">Your outfit will be delivered to:</p>
                                    <p class="mb-0"><strong><?php echo htmlspecialchars($user['name']); ?></strong></p>
                                    <p class="mb-0"><?php echo htmlspecialchars($order['address'] ?? 'Address not available'); ?></p>
                                    <p class="mb-0"><?php echo htmlspecialchars($user['phone']); ?></p>
                                </div>
                                
                                <!-- Order Timeline -->
                                <h3 class="section-title mt-4"><i class="bi bi-clock-history me-2"></i>What Happens Next?</h3>
                                <div class="order-steps">
                                    <div class="step-item">
                                        <div class="step-icon">
                                            <i class="bi bi-check-lg"></i>
                                        </div>
                                        <div class="step-content">
                                            <div class="step-title">Order Confirmed</div>
                                            <div class="step-desc">Your order has been confirmed and is now being processed.</div>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-icon">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                        <div class="step-content">
                                            <div class="step-title">Outfit Preparation</div>
                                            <div class="step-desc">We're preparing your outfit according to your measurements for a perfect fit.</div>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-icon">
                                            <i class="bi bi-truck"></i>
                                        </div>
                                        <div class="step-content">
                                            <div class="step-title">Shipping</div>
                                            <div class="step-desc">Your outfit will be shipped to arrive one day before your rental start date.</div>
                                        </div>
                                    </div>
                                    <div class="step-item">
                                        <div class="step-icon">
                                            <i class="bi bi-calendar-check"></i>
                                        </div>
                                        <div class="step-content">
                                            <div class="step-title">Return</div>
                                            <div class="step-desc">Return shipping label is included. Just pack and ship on your end date.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column: Order Summary -->
                            <div class="col-lg-4">
                                <div class="order-summary">
                                    <h3 class="section-title"><i class="bi bi-receipt me-2"></i>Order Summary</h3>
                                    
                                    <!-- Add Download Invoice Button -->
                                    <div class="text-center mb-4">
                                        <a href="generate_invoice.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary" target="_blank">
                                            <i class="bi bi-download me-2"></i>Download Invoice
                                        </a>
                                    </div>
                                    
                                    <!-- Price Details -->
                                    <div class="price-details">
                                        <div class="price-row">
                                            <span>Base Rental Charge</span>
                                            <span>₹<?php echo number_format($order['rental_rate'] ?? $actual_rental, 2); ?></span>
                                        </div>
                                        <div class="price-row">
                                            <span>Security Deposit</span>
                                            <span>₹<?php echo number_format($order['security_deposit'] ?? $security_deposit, 2); ?> 
                                                <small class="text-success d-block">(Refundable)</small>
                                            </span>
                                        </div>
                                        <div class="price-row">
                                            <span>Delivery Charges</span>
                                            <span>₹<?php echo number_format($delivery_charge, 2); ?></span>
                                        </div>
                                        <div class="price-row">
                                            <span>GST (18%)</span>
                                            <span>₹<?php echo number_format($gst, 2); ?></span>
                                        </div>
                                        <div class="total-row">
                                            <span>Total Amount</span>
                                            <span>₹<?php echo number_format($order_total, 2); ?></span>
                                        </div>
                                    </div>
                                    
                                    <!-- QR Code for Order Tracking -->
                                    <div class="text-center mt-4 mb-4">
                                        <h5 class="mb-3">Scan to Track Your Order</h5>
                                        <div class="qr-code">
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode('https://clover-rentals.com/track.php?order=' . $order_id); ?>" alt="Track Order QR Code">
                                        </div>
                                        <p class="mt-2 text-muted">Or use your tracking number</p>
                                    </div>
                                    
                                    <!-- Return Information -->
                                    <div class="confirmation-box mt-4">
                                        <h5 class="mb-2"><i class="bi bi-box-arrow-left me-2"></i>Return Information</h5>
                                        <p class="mb-0">Return your outfit by <?php echo date('d M Y', strtotime($order['end_date'] . ' +1 day')); ?> using the prepaid return label included in your package.</p>
                                    </div>
                                    
                                    <!-- Help Box -->
                                    <div class="confirmation-box mt-4">
                                        <h5 class="mb-2"><i class="bi bi-question-circle me-2"></i>Need Help?</h5>
                                        <p class="mb-0">If you have any questions about your order, please contact our customer service team.</p>
                                        <div class="mt-3">
                                            <a href="tel:+918887776666" class="d-block mb-1"><i class="bi bi-telephone me-2"></i>+91 8887776666</a>
                                            <a href="mailto:support@clover-rentals.com" class="d-block"><i class="bi bi-envelope me-2"></i>support@clover-rentals.com</a>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="action-buttons">
                                        <a href="order_history.php" class="action-btn primary-btn">View Orders</a>
                                        <a href="outfit.php" class="action-btn secondary-btn">Continue Shopping</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Confirmation Footer -->
                    <div class="confirmation-footer">
                        <p class="help-text">Share your shopping experience with friends!</p>
                        <div class="social-share">
                            <a href="#" class="share-btn fb"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="share-btn tw"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="share-btn wa"><i class="fab fa-whatsapp"></i></a>
                            <a href="#" class="share-btn ig"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confetti Effect -->
    <script>
        function createConfetti() {
            const confettiCount = 100;
            const container = document.body;
            
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                
                // Random position
                confetti.style.left = Math.random() * 100 + 'vw';
                
                // Random color
                const colors = ['#f94144', '#f3722c', '#f8961e', '#f9c74f', '#90be6d', '#43aa8b', '#577590'];
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                
                // Random size
                const size = Math.random() * 10 + 5;
                confetti.style.width = size + 'px';
                confetti.style.height = size + 'px';
                
                // Random rotation
                confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
                
                // Random animation duration and delay
                confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                confetti.style.animationDelay = Math.random() * 5 + 's';
                
                container.appendChild(confetti);
                
                // Remove confetti after animation completes
                setTimeout(() => {
                    confetti.remove();
                }, 8000);
            }
        }
        
        // Create confetti on page load
        window.addEventListener('load', createConfetti);
    </script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>