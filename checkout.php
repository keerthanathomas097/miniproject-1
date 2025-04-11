<?php
session_start();
include 'connect.php';
require_once 'duplicate_protection.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: ls.php");
    exit();
}

// Get outfit ID from URL and validate it
$outfit_id = isset($_GET['outfit_id']) ? (int)$_GET['outfit_id'] : 0;

if ($outfit_id <= 0) {
    // Invalid outfit ID
    header("Location: outfit.php");
    exit();
}

// Update the outfit query to use image1 from tbl_outfit
$query = "SELECT o.*, d.description_text, b.subcategory_name as brand_name,
          (SELECT image_path FROM tbl_outfit_images 
           WHERE outfit_id = o.outfit_id AND uploaded_by = 'admin' 
           ORDER BY uploaded_at ASC LIMIT 1) as main_image
          FROM tbl_outfit o
          LEFT JOIN tbl_description d ON o.description_id = d.id
          LEFT JOIN tbl_subcategory b ON o.brand_id = b.id
          WHERE o.outfit_id = ?";

$outfit_stmt = $conn->prepare($query);
if ($outfit_stmt === false) {
    die("Error preparing outfit query: " . $conn->error);
}

$outfit_stmt->bind_param("i", $outfit_id);
if (!$outfit_stmt->execute()) {
    die("Error executing outfit query: " . $outfit_stmt->error);
}

$result = $outfit_stmt->get_result();
$outfit = $result->fetch_assoc();
$outfit_stmt->close();

if (!$outfit) {
    // Outfit not found
    header("Location: outfit.php");
    exit();
}

// Calculate rental price breakdown based on duration
$duration = isset($_SESSION['rental_duration']) ? (int)$_SESSION['rental_duration'] : 3;
$rental_percentage = 0.10; // Default 10%

switch($duration) {
    case 5:
        $rental_percentage = 0.12; // 12% for 5 days
        break;
    case 7:
        $rental_percentage = 0.14; // 14% for 7 days
        break;
    default:
        $rental_percentage = 0.10; // 10% for 3 days
}

$actual_rental = $outfit['mrp'] * $rental_percentage;
$security_deposit = $outfit['mrp'] * 0.10; // Security deposit remains 10%
$delivery_charge = 199.00;
$gst = $actual_rental * 0.18;
$total = $actual_rental + $security_deposit + $delivery_charge + $gst;

// Fetch user's measurements - use unique statement variable
$measurements_query = "SELECT * FROM tbl_measurements 
                      WHERE user_id = ? AND outfit_id = ? 
                      ORDER BY id DESC LIMIT 1";
                      
$measurements_stmt = $conn->prepare($measurements_query);
if ($measurements_stmt === false) {
    die("Error preparing measurements query: " . $conn->error);
}

$user_id = $_SESSION['id'];
$measurements_stmt->bind_param("ii", $user_id, $outfit_id);
if (!$measurements_stmt->execute()) {
    die("Error executing measurements query: " . $measurements_stmt->error);
}

$measurements_result = $measurements_stmt->get_result();
$measurements = $measurements_result->fetch_assoc();
$measurements_stmt->close();

if (!$measurements) {
    // No measurements found, redirect back to rentnow.php
    header("Location: rentnow.php?id=" . $outfit_id . "&error=no_measurements");
    exit();
}

// Add a query to fetch user details including address
$user_query = "SELECT user_id, name, phone, email FROM tbl_users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
if ($user_stmt === false) {
    die("Error preparing user query: " . $conn->error);
}

$user_stmt->bind_param("i", $user_id);
if (!$user_stmt->execute()) {
    die("Error executing user query: " . $user_stmt->error);
}

$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user) {
    // User not found
    header("Location: logout.php");
    exit();
}

// Check for duplicate submission
if (isset($_SESSION['last_order_time']) && 
    (time() - $_SESSION['last_order_time']) < 5) { // 5 seconds threshold
    die(json_encode([
        'success' => false,
        'message' => 'Order already submitted. Please wait a moment.'
    ]));
}

// Set the order time
$_SESSION['last_order_time'] = time();

// At the beginning of your order processing code
$conn->begin_transaction();

try {
    // Check if order already exists
    $check_query = "SELECT id FROM tbl_orders WHERE order_reference = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $order_reference);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Order already exists, abort
        $conn->rollback();
        exit("Order already exists. Please do not refresh the checkout page.");
    }
    
    // Continue with order insertion
    // [Your existing order insertion code goes here]
    
    // If everything is successful, commit the transaction
    $conn->commit();
    
} catch (Exception $e) {
    // If any error occurs, roll back the transaction
    $conn->rollback();
    error_log("Order processing error: " . $e->getMessage());
    exit("An error occurred during order processing. Please try again.");
}

// Inside your form submission handling (after generating order_reference)
if (!preventDuplicateOrder($conn, $order_reference)) {
    // This is a duplicate order - handle gracefully
    $_SESSION['order_message'] = "Your order has already been processed. Please do not refresh the checkout page.";
    header("Location: order_confirmation.php?ref=" . $order_reference);
    exit();
}

// Only proceed with order insertion if it's not a duplicate
// Rest of your order processing code here...
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Clover Rentals</title>
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
        
        .checkout-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            padding: 0;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .checkout-header {
            background-color: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .checkout-header h2 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            color: #212529;
        }
        
        .checkout-body {
            padding: 30px;
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
        
        .order-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        
        .product-card {
            display: flex;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 20px;
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
            margin-top: 20px;
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
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .address-select {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .address-select.selected {
            border-color: #198754;
            background-color: #f0f9f4;
        }
        
        .address-select h5 {
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .address-select p {
            margin-bottom: 3px;
            font-size: 0.9rem;
        }
        
        .address-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        
        .address-btn {
            font-size: 0.8rem;
            padding: 3px 10px;
        }
        
        .payment-option {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .payment-option.selected {
            border-color: #198754;
            background-color: #f0f9f4;
        }
        
        .payment-option input {
            margin-right: 15px;
        }
        
        .payment-info {
            flex-grow: 1;
        }
        
        .payment-logo {
            height: 30px;
            margin-left: 10px;
        }
        
        .place-order-btn {
            background-color: #212529;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .place-order-btn:hover {
            background-color: #343a40;
            transform: translateY(-2px);
        }
        
        .delivery-estimate {
            background-color: #e6f7ff;
            border-left: 4px solid #1890ff;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .security-note {
            background-color: #fff9e6;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 0.9rem;
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
        
        .checkout-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 0 20px;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        .step-number {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #fff;
            border: 2px solid #198754;
            color: #198754;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .step.active .step-number {
            background-color: #198754;
            color: #fff;
        }
        
        .step.completed .step-number {
            background-color: #198754;
            color: #fff;
        }
        
        .step-name {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .step.active .step-name {
            color: #198754;
            font-weight: 600;
        }
        
        .steps-line {
            position: absolute;
            top: 17px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #e9ecef;
            z-index: 0;
        }
        
        .steps-progress {
            position: absolute;
            top: 17px;
            left: 0;
            width: 50%;
            height: 2px;
            background-color: #198754;
            z-index: 0;
        }
        
        .promo-code {
            display: flex;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        
        .promo-code input {
            flex-grow: 1;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .promo-code button {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        
        .price-row small.text-success {
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .price-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: #495057;
        }
        
        .total-row {
            border-top: 2px solid #dee2e6;
            margin-top: 15px;
            padding-top: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #212529;
        }
    </style>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
                <div class="checkout-container">
                    <div class="checkout-header">
                        <h2><i class="bi bi-bag-check me-2"></i>Checkout</h2>
                    </div>
                    
                    <div class="checkout-body">
                        <!-- Checkout Progress -->
                        <div class="position-relative mb-4">
                            <div class="steps-line"></div>
                            <div class="steps-progress"></div>
                            <div class="checkout-steps">
                                <div class="step completed">
                                    <div class="step-number">
                                        <i class="bi bi-check"></i>
                                    </div>
                                    <div class="step-name">Measurements</div>
                                </div>
                                <div class="step active">
                                    <div class="step-number">2</div>
                                    <div class="step-name">Checkout</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">3</div>
                                    <div class="step-name">Payment</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">4</div>
                                    <div class="step-name">Confirmation</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Left Column: Form Sections -->
                            <div class="col-lg-8">
                                <!-- Rental Period Information -->
                                <div class="rental-period">
                                    <p><strong>Rental Period:</strong> <?php echo $duration; ?> Days</p>
                                    <div class="rental-dates">
                                        <div class="date-box">
                                            <p>Start Date</p>
                                            <strong><?php echo date('d M Y', strtotime($measurements['start_date'])); ?></strong>
                                        </div>
                                        <div class="date-arrow">
                                            <i class="bi bi-arrow-right"></i>
                                        </div>
                                        <div class="date-box">
                                            <p>End Date</p>
                                            <strong><?php echo date('d M Y', strtotime($measurements['end_date'])); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Measurement Information -->
                                 <!-- Measurement Information -->
                                <div class="form-section">
                                    <h3 class="section-title"><i class="bi bi-rulers me-2"></i>Your Measurements</h3>
                                    <div class="measurement-info">
                                        <p>We'll use these measurements to ensure your outfit fits perfectly.</p>
                                        <table class="measurement-table">
                                            <tr>
                                                <td>Height:</td>
                                                <td><?php echo htmlspecialchars($measurements['height']); ?> inches</td>
                                            </tr>
                                            <tr>
                                                <td>Shoulder Width:</td>
                                                <td><?php echo htmlspecialchars($measurements['shoulder']); ?> inches</td>
                                            </tr>
                                            <tr>
                                                <td>Bust:</td>
                                                <td><?php echo htmlspecialchars($measurements['bust']); ?> inches</td>
                                            </tr>
                                            <tr>
                                                <td>Waist:</td>
                                                <td><?php echo htmlspecialchars($measurements['waist']); ?> inches</td>
                                            </tr>
                                        </table>
                                        <div class="mt-2">
                                            <a href="#" class="btn btn-sm btn-outline-secondary">Edit Measurements</a>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Shipping Information -->
                                <div class="form-section">
                                    <h3 class="section-title"><i class="bi bi-truck me-2"></i>Shipping Address</h3>
                                    
                                    <!-- Saved Address -->
                                    <div class="address-select selected">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="shipping_address" id="address1" checked>
                                            <label class="form-check-label" for="address1">
                                                <h5>Delivery Address</h5>
                                                <p><?php echo htmlspecialchars($user['name'] ?? 'Name not available'); ?></p>
                                                <p><?php echo htmlspecialchars($outfit['address'] ?? 'Address not available'); ?></p>
                                                <p><?php echo htmlspecialchars($user['phone'] ?? 'Phone not available'); ?></p>
                                                <p><?php echo htmlspecialchars($user['email'] ?? 'Email not available'); ?></p>
                                            </label>
                                        </div>
                                        <div class="address-actions">
                                            <button class="btn btn-sm btn-outline-secondary address-btn">Edit</button>
                                        </div>
                                    </div>
                                    
                                    <!-- Add New Address Button -->
                                    <button class="btn btn-outline-dark mt-2">
                                        <i class="bi bi-plus-circle me-2"></i>Add New Address
                                    </button>
                                    
                                    <!-- Delivery Estimate -->
                                    <div class="delivery-estimate">
                                        <p class="mb-0"><i class="bi bi-clock me-2"></i><strong>Estimated Delivery:</strong> 
                                            <?php echo date('d M Y', strtotime($measurements['start_date'] . ' -1 day')); ?></p>
                                        <small class="text-muted">Your outfit will arrive one day before your rental start date.</small>
                                    </div>
                                </div>
                                
                                <!-- Payment Method -->
                                <div class="form-section">
                                    <h3 class="section-title"><i class="bi bi-credit-card me-2"></i>Payment Method</h3>
                                    
                                    <!-- Credit Card Option -->
                                    <div class="payment-option selected">
                                        <input type="radio" name="payment_method" id="credit_card" checked>
                                        <div class="payment-info">
                                            <h5 class="mb-1">Credit/Debit Card</h5>
                                            <p class="mb-0 text-muted">Pay securely with your credit or debit card</p>
                                        </div>
                                        <div class="payment-icons">
                                            <i class="fab fa-cc-visa mx-1 fa-lg"></i>
                                            <i class="fab fa-cc-mastercard mx-1 fa-lg"></i>
                                            <i class="fab fa-cc-amex mx-1 fa-lg"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- UPI Option -->
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="upi">
                                        <div class="payment-info">
                                            <h5 class="mb-1">UPI</h5>
                                            <p class="mb-0 text-muted">Pay using UPI apps like GPay, PhonePe, Paytm</p>
                                        </div>
                                        <div class="payment-icons">
                                            <i class="bi bi-phone mx-1 fa-lg"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- Cash on Delivery Option -->
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="cod">
                                        <div class="payment-info">
                                            <h5 class="mb-1">Cash on Delivery</h5>
                                            <p class="mb-0 text-muted">Pay when your outfit is delivered</p>
                                        </div>
                                        <div class="payment-icons">
                                            <i class="bi bi-cash mx-1 fa-lg"></i>
                                        </div>
                                    </div>
                                    
                                    <!-- Security Note -->
                                    <div class="security-note">
                                        <p class="mb-0"><i class="bi bi-shield-lock me-2"></i><strong>Secure Transaction:</strong> Your payment information is encrypted and secure.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column: Order Summary -->
                            <div class="col-lg-4">
                                <div class="order-summary">
                                    <h3 class="section-title"><i class="bi bi-receipt me-2"></i>Order Summary</h3>
                                    
                                    <!-- Product Display -->
                                    <div class="product-card">
                                        <img src="<?php echo !empty($outfit['main_image']) ? htmlspecialchars($outfit['main_image']) : 'images/placeholder.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($outfit['description_text']); ?>" 
                                             class="product-image">
                                        <div class="product-details">
                                            <h5><?php echo htmlspecialchars($outfit['description_text']); ?></h5>
                                            <p class="mb-1">Brand: <?php echo htmlspecialchars($outfit['brand_name']); ?></p>
                                            <p class="mb-1">Rental: <?php echo $duration; ?> Days</p>
                                            <p class="product-price">₹<?php echo number_format($actual_rental, 2); ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Promo Code -->
                                    <div class="promo-code">
                                        <input type="text" class="form-control" placeholder="Promo Code">
                                        <button class="btn btn-outline-dark">Apply</button>
                                    </div>
                                    
                                    <!-- Price Details -->
                                    <div class="price-details">
                                        <div class="price-row">
                                            <span>Base Rental Charge (<?php echo $rental_percentage * 100; ?>% of MRP)</span>
                                            <span>₹<?php echo number_format($actual_rental, 2); ?></span>
                                        </div>
                                        <div class="price-row">
                                            <span>Security Deposit (10% of MRP)</span>
                                            <span>₹<?php echo number_format($security_deposit, 2); ?> 
                                                <small class="text-success d-block">(Refundable)</small>
                                            </span>
                                        </div>
                                        <div class="price-row">
                                            <span>Delivery Charges</span>
                                            <span>₹<?php echo number_format($delivery_charge, 2); ?></span>
                                        </div>
                                        <div class="price-row">
                                            <span>GST (18% on rental charge)</span>
                                            <span>₹<?php echo number_format($gst, 2); ?></span>
                                        </div>
                                        <div class="total-row">
                                            <span>Total Amount</span>
                                            <span>₹<?php echo number_format($total, 2); ?></span>
                                        </div>
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle"></i> Security deposit will be refunded after the outfit is returned in good condition.
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <!-- Place Order Button -->
                                    <button class="place-order-btn mt-3">
                                        <i class="bi bi-lock-fill me-2"></i>Place Order
                                    </button>
                                    
                                    <!-- Additional Information -->
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>By placing your order, you agree to our 
                                            <a href="#">Terms of Service</a> and <a href="#">Rental Policy</a>.
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-truck me-1"></i>Free pickup and return available for orders above ₹3,999.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <h5>About Clover Rentals</h5>
                    <p class="text-muted">Making fashion accessible and sustainable through our outfit rental service.</p>
                </div>
                <div class="col-md-3 mb-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted">How It Works</a></li>
                        <li><a href="#" class="text-muted">FAQs</a></li>
                        <li><a href="#" class="text-muted">Rental Policy</a></li>
                        <li><a href="#" class="text-muted">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-3">
                    <h5>Connect With Us</h5>
                    <div class="d-flex gap-3 fs-5">
                        <a href="#" class="text-light"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-light"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="text-light"><i class="bi bi-pinterest"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <h5>Customer Care</h5>
                    <p class="text-muted">
                        <i class="bi bi-telephone-fill me-2"></i>+91 98765 43210
                    </p>
                    <p class="text-muted">
                        <i class="bi bi-envelope-fill me-2"></i>care@clover.com
                    </p>
                </div>
            </div>
            <hr class="my-3 bg-secondary">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-muted mb-md-0">© 2025 Clover Outfit Rentals. All Rights Reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="text-muted mb-0">
                        <a href="#" class="text-muted me-3">Privacy Policy</a>
                        <a href="#" class="text-muted me-3">Terms of Service</a>
                        <a href="#" class="text-muted">Sitemap</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle payment option selection
            const paymentOptions = document.querySelectorAll('.payment-option');
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    paymentOptions.forEach(opt => opt.classList.remove('selected'));
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    // Check the radio button
                    this.querySelector('input[type="radio"]').checked = true;
                });
            });
            
            // Handle address selection
            const addressSelects = document.querySelectorAll('.address-select');
            addressSelects.forEach(address => {
                address.addEventListener('click', function() {
                    addressSelects.forEach(addr => addr.classList.remove('selected'));
                    this.classList.add('selected');
                    this.querySelector('input[type="radio"]').checked = true;
                });
            });
            
            // Promo code functionality
            const promoButton = document.querySelector('.promo-code button');
            if (promoButton) {
                promoButton.addEventListener('click', function() {
                    const promoInput = document.querySelector('.promo-code input');
                    if (promoInput.value.trim() !== '') {
                        alert('Promo code applied successfully!');
                        promoInput.value = '';
                    } else {
                        alert('Please enter a valid promo code');
                    }
                });
            }
            
            // Place order button functionality with Razorpay integration
            const placeOrderBtn = document.querySelector('.place-order-btn');
            if (placeOrderBtn) {
                placeOrderBtn.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default action
                    
                    // Change button state to show processing
                    this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
                    this.disabled = true;
                    
                    // Get selected payment method
                    const selectedPayment = document.querySelector('.payment-option.selected input').id;
                    
                    // If COD is selected, handle directly
                    if (selectedPayment === 'cod') {
                        handleCODOrder();
                        return;
                    }
                    
                    // For online payments (credit card or UPI)
                    handleOnlinePayment(selectedPayment);
                });
            }
            
            // Function to handle Cash on Delivery orders
            function handleCODOrder() {
                // Create order data
                const orderData = {
                    outfit_id: <?php echo $outfit_id; ?>,
                    amount: <?php echo $total; ?>,
                    payment_method: 'COD',
                    user_id: <?php echo $user_id; ?>,
                    start_date: '<?php echo $measurements['start_date']; ?>',
                    end_date: '<?php echo $measurements['end_date']; ?>'
                };
                
                // Convert to URL encoded string for POST
                const formBody = Object.keys(orderData)
                    .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(orderData[key]))
                    .join('&');
                
                // Send direct request to create order
                fetch('direct_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: formBody
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        // Try to parse as JSON if possible
                        const data = JSON.parse(text);
                        if (data.success && data.order_id) {
                            window.location.href = 'confirmation.php?order_id=' + data.order_id;
                        } else {
                            throw new Error(data.message || 'Unknown error occurred');
                        }
                    } catch (e) {
                        // If it's not JSON or has parsing error, check if it contains a redirect
                        if (text.includes('confirmation.php')) {
                            // Extract order ID if present in the response
                            const match = text.match(/confirmation\.php\?order_id=(\d+)/);
                            if (match && match[1]) {
                                window.location.href = 'confirmation.php?order_id=' + match[1];
                        return;
                            }
                        }
                        // Otherwise go to confirmation page without order_id
                        window.location.href = 'confirmation.php';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your order. Please try again.');
                    const placeOrderBtn = document.querySelector('.place-order-btn');
                    placeOrderBtn.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Place Order';
                    placeOrderBtn.disabled = false;
                });
            }
            
            // Function to handle online payments (credit card or UPI)
            function handleOnlinePayment(paymentMethod) {
                // Create order data
                const amount = <?php echo $total * 100; ?>; // Razorpay expects amount in paise
                const orderData = {
                    outfit_id: <?php echo $outfit_id; ?>,
                    amount: <?php echo $total; ?>, // actual amount
                    payment_method: paymentMethod,
                    user_id: <?php echo $user_id; ?>,
                    start_date: '<?php echo $measurements['start_date']; ?>',
                    end_date: '<?php echo $measurements['end_date']; ?>'
                };
                
                console.log('Sending order data:', orderData);
                
                // Convert to URL encoded string for POST
                const formBody = Object.keys(orderData)
                    .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(orderData[key]))
                    .join('&');
                
                // Send request to create Razorpay order
                fetch('create_razorpay_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: formBody
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        console.error('Bad response status:', response.status);
                    }
                    return response.text();
                })
                .then(text => {
                    // Log the raw response for debugging
                    console.log('Raw response:', text);
                    
                    // Check if the response starts with HTML/whitespace
                    if (text.trim().startsWith('<') || text.includes('<br>')) {
                        // Response contains HTML, which indicates a PHP error or warning
                        console.error('Response contains HTML/PHP output:', text);
                        alert('The server response contains errors. Please contact support.');
                        throw new Error('Invalid server response format');
                    }
                    
                    try {
                        // Try to parse the JSON
                        const data = JSON.parse(text);
                        
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to create order');
                        }
                        
                        // Check if razorpay_order_id exists
                        if (!data.razorpay_order_id) {
                            console.error('Missing razorpay_order_id in response');
                            throw new Error('Invalid response format: Missing Razorpay order ID');
                        }
                        
                        console.log('Razorpay order created successfully:', data);
                        
                        // Initialize Razorpay payment
                        const options = {
                            key: 'rzp_test_a2JV5WZK9Vupym', // Your Razorpay Key ID
                            amount: amount, // amount in paise
                            currency: 'INR',
                            name: 'Clover Outfit Rentals',
                            description: 'Rental Payment',
                            order_id: data.razorpay_order_id,
                            handler: function(response) {
                                // This handles successful payment
                                handlePaymentSuccess(response);
                            },
                            prefill: {
                                name: "<?php echo htmlspecialchars($user['name'] ?? 'Customer'); ?>",
                                email: "<?php echo htmlspecialchars($user['email'] ?? ''); ?>",
                                contact: "<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            },
                            theme: {
                                color: "#212529"
                            },
                            modal: {
                                ondismiss: function() {
                                    // Re-enable the button if payment is dismissed
                                    const placeOrderBtn = document.querySelector('.place-order-btn');
                                    placeOrderBtn.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Place Order';
                                    placeOrderBtn.disabled = false;
                                }
                            }
                        };
                        
                        console.log('Initializing Razorpay with options:', options);
                        const rzp = new Razorpay(options);
                        
                        rzp.on('payment.failed', function(response) {
                            console.error('Payment failed:', response.error);
                            alert('Payment failed: ' + response.error.description);
                            const placeOrderBtn = document.querySelector('.place-order-btn');
                            placeOrderBtn.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Place Order';
                            placeOrderBtn.disabled = false;
                        });
                        
                        // Open Razorpay payment form
                        rzp.open();
                        
                    } catch (e) {
                        console.error('Error processing payment:', e);
                        alert('An error occurred while processing your payment. Please try again or choose COD.');
                        const placeOrderBtn = document.querySelector('.place-order-btn');
                        placeOrderBtn.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Place Order';
                        placeOrderBtn.disabled = false;
                        
                        // As a fallback, suggest Cash on Delivery
                        if (confirm('Would you like to place your order with Cash on Delivery instead?')) {
                            handleCODOrder();
                        }
                    }
                })
                .catch(error => {
                    console.error('Network error:', error);
                    alert('An error occurred while setting up payment. Please try again.');
                    const placeOrderBtn = document.querySelector('.place-order-btn');
                    placeOrderBtn.innerHTML = '<i class="bi bi-lock-fill me-2"></i>Place Order';
                    placeOrderBtn.disabled = false;
                    
                    // As a fallback, suggest Cash on Delivery
                    if (confirm('Would you like to place your order with Cash on Delivery instead?')) {
                        handleCODOrder();
                    }
                });
            }
            
            // Function to complete payment after successful Razorpay payment
            function handlePaymentSuccess(response) {
                console.log('Payment successful:', response);
                
                // Get the order ID from storage
                const orderId = localStorage.getItem('current_order_id') || 
                               sessionStorage.getItem('current_order_id');
                
                // Prepare verification data
                const verificationData = new FormData();
                verificationData.append('razorpay_payment_id', response.razorpay_payment_id);
                verificationData.append('razorpay_order_id', response.razorpay_order_id);
                verificationData.append('razorpay_signature', response.razorpay_signature);
                verificationData.append('order_id', orderId);
                
                // Send verification request
                fetch('verify_payment.php', {
                    method: 'POST',
                    body: verificationData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Raw verification response:', text);
                    
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            // Clear any stored order IDs
                            localStorage.removeItem('current_order_id');
                            sessionStorage.removeItem('current_order_id');
                            
                            // Store outfit_id in session storage
                            if (data.outfit_id) {
                                sessionStorage.setItem('current_outfit_id', data.outfit_id);
                            }
                            
                            // Redirect to confirmation page with both IDs
                            if (data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                // Fallback if redirect_url is not provided
                                const outfitId = data.outfit_id || <?php echo $outfit_id; ?>;
                                window.location.href = 'confirmation.php?order_id=' + data.order_id + '&outfit_id=' + outfitId;
                            }
                        } else {
                            throw new Error(data.message || 'Payment verification failed');
                        }
                    } catch (e) {
                        console.error('Error completing payment:', e);
                        // Try to redirect anyway if we have an order ID
                        if (orderId) {
                            const outfitId = <?php echo $outfit_id; ?>;
                            window.location.href = 'confirmation.php?order_id=' + orderId + '&outfit_id=' + outfitId;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Your payment was processed, but we encountered an error. Please contact customer support.');
                    // Try to redirect anyway if we have an order ID
                    if (orderId) {
                        const outfitId = <?php echo $outfit_id; ?>;
                        window.location.href = 'confirmation.php?order_id=' + orderId + '&outfit_id=' + outfitId;
                    }
                });
            }
        });
    </script>
    <script>
    // This code ensures redirection to the correct confirmation page
    (function() {
        // Function to handle order placement and redirection
        function handleOrderRedirection() {
            // Save the original fetch method
            const originalFetch = window.fetch;
            
            // Override fetch to capture responses from save_order.php
            window.fetch = function() {
                const fetchPromise = originalFetch.apply(this, arguments);
                
                // Check if this is a request to save_order.php
                if (arguments[0] && arguments[0].toString().includes('save_order.php')) {
                    // Process the response
                    fetchPromise.then(response => {
                        // Clone the response so we can read it multiple times
                        const clonedResponse = response.clone();
                        
                        // Process the response text
                        clonedResponse.text().then(text => {
                            console.log('Raw response from save_order.php:', text);
                            
                            try {
                                const data = JSON.parse(text);
                                
                                if (data.success && data.order_id) {
                                    console.log('Order created successfully with ID:', data.order_id);
                                    
                                    // Store the order ID in localStorage and sessionStorage for redundancy
                                    localStorage.setItem('current_order_id', data.order_id);
                                    sessionStorage.setItem('current_order_id', data.order_id);
                                    
                                    // CRITICAL: Force redirection to the correct page with a slight delay
                                    // This helps override any other redirects that might be happening
                                    setTimeout(function() {
                                        const redirectUrl = 'confirmation.php?order_id=' + data.order_id;
                                        console.log('Redirecting to:', redirectUrl);
                                        
                                        // Use replace instead of href to avoid browser history issues
                                        window.location.replace(redirectUrl);
                                    }, 100);
                                    
                                    // Also prevent any other redirects by canceling them
                                    const originalReplaceState = history.replaceState;
                                    const originalPushState = history.pushState;
                                    
                                    history.replaceState = function() {
                                        console.log('Blocked history.replaceState');
                                        return null;
                                    };
                                    
                                    history.pushState = function() {
                                        console.log('Blocked history.pushState');
                                        return null;
                                    };
                                    
                                    // Restore after our redirect should have happened
                                    setTimeout(function() {
                                        history.replaceState = originalReplaceState;
                                        history.pushState = originalPushState;
                                    }, 500);
                                }
                            } catch (e) {
                                console.error('Error parsing JSON response:', e);
                            }
                        });
                    });
                }
                
                return fetchPromise;
            };
        }
        
        // Apply the redirection handler as soon as possible
        handleOrderRedirection();
        
        // Also attach to the Place Order button
        document.addEventListener('DOMContentLoaded', function() {
            const placeOrderBtn = document.querySelector('.place-order-btn');
            
            if (placeOrderBtn) {
                placeOrderBtn.addEventListener('click', function(e) {
                    console.log('Place Order button clicked');
                    
                    // Store the current timestamp as a marker
                    sessionStorage.setItem('order_clicked_time', Date.now());
                });
            }
        });
    })();
    </script>
    <!-- Add this somewhere visible on your checkout page -->
    <div style="margin-top: 20px; text-align: center;">
        <a href="check_order.php" style="padding: 10px 15px; background: #f8f9fa; border: 1px solid #ddd; text-decoration: none; border-radius: 4px;">
            <i class="bi bi-receipt"></i> View Recent Orders
        </a>
    </div>

    <!-- Add this script to ensure orders are stored before payment -->
    <script>
    // This script will ensure orders are stored in the database before proceeding with payment
    document.addEventListener('DOMContentLoaded', function() {
        // Intercept the main checkout process
        function interceptCheckout() {
            console.log('Setting up order storage interception');
            
            // Find the place order button
            const placeOrderBtn = document.querySelector('.place-order-btn');
            
            if (placeOrderBtn) {
                console.log('Found Place Order button, adding storage handler');
                
                // Backup original click handler
                const originalOnclick = placeOrderBtn.onclick;
                
                // Replace with our handler that will first store the order
                placeOrderBtn.onclick = async function(e) {
                    e.preventDefault();
                    
                    // Change button appearance
                    const originalText = placeOrderBtn.innerHTML;
                    placeOrderBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
                    placeOrderBtn.disabled = true;
                    
                    try {
                        console.log('Storing order data before payment');
                        
                        // Get payment method
                        let selectedPayment = 'cod';
                        const paymentOptions = document.querySelectorAll('.payment-option');
                        paymentOptions.forEach(option => {
                            if (option.classList.contains('selected')) {
                                const input = option.querySelector('input');
                                if (input) selectedPayment = input.id;
                            }
                        });
                        
                        // Get outfit ID and amount
                        const outfitId = <?php echo $outfit_id ?? 0; ?>;
                        const amount = <?php echo $total ?? 0; ?>;
                        
                        console.log('Order data:', { outfit_id: outfitId, amount: amount, payment_method: selectedPayment });
                        
                        // Create form data
                        const formData = new FormData();
                        formData.append('outfit_id', outfitId);
                        formData.append('amount', amount);
                        formData.append('payment_method', selectedPayment);
                        
                        // Store the order first
                        const response = await fetch('direct_order_storage.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const text = await response.text();
                        console.log('Storage response:', text);
                        
                        const data = JSON.parse(text);
                        
                        if (data.success) {
                            console.log('Order stored with ID:', data.order_id);
                            
                            // Store in localStorage for later use
                            localStorage.setItem('current_order_id', data.order_id);
                            sessionStorage.setItem('current_order_id', data.order_id);
                            
                            // Now proceed with original payment flow
                            console.log('Proceeding with payment processing');
                            
                            // If original handler exists, call it
                            if (typeof originalOnclick === 'function') {
                                // Execute the original handler to continue with payment
                                const continueWithPayment = originalOnclick.call(placeOrderBtn, e);
                                
                                // If it returns false or Promise that resolves to false, we need to handle it
                                if (continueWithPayment === false) {
                                    placeOrderBtn.innerHTML = originalText;
                                    placeOrderBtn.disabled = false;
                                }
                                
                                return continueWithPayment;
                            } else {
                                // Default behavior - submit the form if any
                                const form = placeOrderBtn.closest('form');
                                if (form) form.submit();
                            }
                        } else {
                            throw new Error(data.message || 'Failed to store order');
                        }
                    } catch (error) {
                        console.error('Error storing order:', error);
                        alert('An error occurred while processing your order: ' + error.message);
                        
                        // Reset button
                        placeOrderBtn.innerHTML = originalText;
                        placeOrderBtn.disabled = false;
                    }
                };
            }
        }
        
        // Add a listener for successful payment completion to redirect to the right order
        function setupSuccessRedirect() {
            console.log('Setting up success redirect handler');
            
            // Monitor for navigation changes
            const originalPushState = history.pushState;
            const originalReplaceState = history.replaceState;
            
            // Override pushState
            history.pushState = function() {
                // Call original
                originalPushState.apply(this, arguments);
                
                // Check if this is a redirect to confirmation page
                if (arguments[2] && arguments[2].includes('confirmation.php')) {
                    const orderId = localStorage.getItem('current_order_id') || 
                                   sessionStorage.getItem('current_order_id');
                    
                    if (orderId && !arguments[2].includes('order_id=' + orderId)) {
                        console.log('Intercepting redirect to confirmation page, fixing order_id');
                        // Delay slightly to ensure we override other redirects
                        setTimeout(() => {
                            window.location.href = 'confirmation.php?order_id=' + orderId;
                        }, 10);
                    }
                }
            };
            
            // Override replaceState
            history.replaceState = function() {
                // Call original
                originalReplaceState.apply(this, arguments);
                
                // Check if this is a redirect to confirmation page
                if (arguments[2] && arguments[2].includes('confirmation.php')) {
                    const orderId = localStorage.getItem('current_order_id') || 
                                   sessionStorage.getItem('current_order_id');
                    
                    if (orderId && !arguments[2].includes('order_id=' + orderId)) {
                        console.log('Intercepting replaceState to confirmation page, fixing order_id');
                        // Delay slightly to ensure we override other redirects
                        setTimeout(() => {
                            window.location.href = 'confirmation.php?order_id=' + orderId;
                        }, 10);
                    }
                }
            };
        }
        
        // Initialize both handlers
        interceptCheckout();
        setupSuccessRedirect();
    });
    </script>

    <!-- Fallback button if regular checkout fails -->
    <div style="display: none; text-align: center; margin-top: 20px;" id="fallbackOrderContainer">
        <p>If you're having trouble with the checkout process, you can try our simplified checkout:</p>
        <button id="fallbackOrderBtn" class="btn btn-warning">
            Create Order & Continue to Payment
        </button>
        <script>
            document.getElementById('fallbackOrderBtn').addEventListener('click', async function() {
                this.disabled = true;
                this.innerHTML = 'Processing...';
                
                try {
                    const outfitId = <?php echo $outfit_id ?? 0; ?>;
                    const amount = <?php echo $total ?? 0; ?>;
                    
                    const formData = new FormData();
                    formData.append('outfit_id', outfitId);
                    formData.append('amount', amount);
                    formData.append('payment_method', 'cod');
                    
                    const response = await fetch('direct_order_storage.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('Order created successfully!');
                        window.location.href = 'confirmation.php?order_id=' + data.order_id;
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                    this.disabled = false;
                    this.innerHTML = 'Create Order & Continue to Payment';
                }
            });
            
            // Show fallback after 5 seconds if the page hasn't progressed
            setTimeout(function() {
                document.getElementById('fallbackOrderContainer').style.display = 'block';
            }, 5000);
        </script>
    </div>

    <!-- Add this script at the end of your checkout.php file, just before </body> -->
    <script>
    // Script to store orders with rental rate and security deposit information
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Setting up order storage with rental and deposit data');
        
        // Calculate rental rate and security deposit
        function calculateRentalAndDeposit() {
            const outfitPrice = <?php echo $outfit['mrp'] ?? 0; ?>; // Get the outfit MRP from PHP variable
            const rentalRate = outfitPrice * 0.10; // 10% of MRP
            const securityDeposit = outfitPrice * 0.10; // 10% of MRP
            
            return {
                rental_rate: rentalRate,
                security_deposit: securityDeposit
            };
        }
        
        // Function to store the order
        async function storeOrderWithDetails() {
            try {
                const outfitId = <?php echo $outfit_id ?? 0; ?>;
                const amount = <?php echo $total ?? 0; ?>;
                
                // Get payment method
                let selectedPayment = 'cod';
                const paymentOptions = document.querySelectorAll('.payment-option');
                paymentOptions.forEach(option => {
                    if (option.classList.contains('selected')) {
                        const input = option.querySelector('input');
                        if (input) selectedPayment = input.id;
                    }
                });
                
                // Calculate rental and deposit
                const { rental_rate, security_deposit } = calculateRentalAndDeposit();
                
                console.log('Order data:', { 
                    outfit_id: outfitId, 
                    amount: amount, 
                    payment_method: selectedPayment,
                    rental_rate: rental_rate,
                    security_deposit: security_deposit
                });
                
                // Create form data
                const formData = new FormData();
                formData.append('outfit_id', outfitId);
                formData.append('amount', amount);
                formData.append('payment_method', selectedPayment);
                formData.append('rental_rate', rental_rate);
                formData.append('security_deposit', security_deposit);
                
                // Send to PHP script
                const response = await fetch('direct_order_storage.php', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                console.log('Storage response:', text);
                
                try {
                    return JSON.parse(text);
                } catch (err) {
                    console.error('Failed to parse response:', err);
                    return { success: false, message: 'Invalid response format' };
                }
            } catch (error) {
                console.error('Error storing order:', error);
                return { success: false, message: error.message };
            }
        }
        
        // Find the place order button
        const placeOrderBtn = document.querySelector('.place-order-btn');
        
        if (placeOrderBtn) {
            console.log('Found place order button, adding handler');
            
            // Add a click handler that runs before the original
            placeOrderBtn.addEventListener('click', async function(e) {
                console.log('Place order button clicked');
                e.preventDefault();
                
                // Store original button text
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
                this.disabled = true;
                
                // Store the order with details
                const result = await storeOrderWithDetails();
                
                if (result.success) {
                    console.log('Order stored successfully with ID:', result.order_id);
                    
                    // Store the order ID
                    localStorage.setItem('current_order_id', result.order_id);
                    sessionStorage.setItem('current_order_id', result.order_id);
                    
                    // Continue with payment processing
                    // Execute the original click handler if available
                    if (typeof this.onclick === 'function') {
                        this.onclick.call(this, e);
                    }
                    
                    // Set a timer to redirect to confirmation page if payment process takes too long
                    setTimeout(() => {
                        if (result.order_id) {
                            window.location.href = 'confirmation.php?order_id=' + result.order_id;
                        }
                    }, 8000);
                } else {
                    console.error('Failed to store order:', result.message);
                    alert('Error storing order: ' + result.message);
                    
                    // Reset button
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            }, true);
        }
        
        // Add payment success handler
        window.addEventListener('payment_success', function(e) {
            const orderId = localStorage.getItem('current_order_id') || 
                           sessionStorage.getItem('current_order_id');
            
            if (orderId) {
                // Redirect to confirmation page
                window.location.href = 'confirmation.php?order_id=' + orderId;
            }
        });
    });
    </script>

    <!-- Add a fallback button in case the normal process fails -->
    <div id="fallbackOrderContainer" style="display:none; margin-top:20px; text-align:center;">
        <p>If you're having trouble processing your order, try our alternative checkout:</p>
        <button id="fallbackOrderBtn" class="btn btn-warning">
            Create Order Directly
        </button>
        
        <script>
            // Show fallback after 10 seconds
            setTimeout(function() {
                document.getElementById('fallbackOrderContainer').style.display = 'block';
            }, 10000);
            
            // Add handler for fallback button
            document.getElementById('fallbackOrderBtn').addEventListener('click', async function() {
                this.disabled = true;
                this.innerHTML = 'Processing...';
                
                // Calculate rental and deposit
                const outfitPrice = <?php echo $outfit['mrp'] ?? 0; ?>;
                const rentalRate = outfitPrice * 0.10;
                const securityDeposit = outfitPrice * 0.10;
                
                // Create form data
                const formData = new FormData();
                formData.append('outfit_id', <?php echo $outfit_id ?? 0; ?>);
                formData.append('amount', <?php echo $total ?? 0; ?>);
                formData.append('payment_method', 'cod');
                formData.append('rental_rate', rentalRate);
                formData.append('security_deposit', securityDeposit);
                
                try {
                    const response = await fetch('direct_order_storage.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert('Order created successfully!');
                        window.location.href = 'confirmation.php?order_id=' + data.order_id;
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                    this.disabled = false;
                    this.innerHTML = 'Create Order Directly';
                }
            });
        </script>
    </div>

    <!-- Add this to your existing JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkoutForm = document.querySelector('form'); // Adjust selector if needed
        
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function() {
                // Find all submit buttons
                const submitButtons = this.querySelectorAll('button[type="submit"], input[type="submit"]');
                
                // Disable all buttons to prevent double-click
                submitButtons.forEach(function(button) {
                    button.disabled = true;
                    
                    if (button.tagName === 'BUTTON') {
                        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    } else {
                        button.value = 'Processing...';
                    }
                });
                
                // Store in sessionStorage that we've submitted this form
                sessionStorage.setItem('formSubmitted', 'true');
                
                return true; // Allow the form to submit
            });
            
            // Check if we're returning to the page after a form submission
            if (sessionStorage.getItem('formSubmitted') === 'true') {
                // Clear the flag
                sessionStorage.removeItem('formSubmitted');
                
                // Show a message if needed
                alert("Please don't refresh or resubmit the form to avoid duplicate orders.");
            }
        }
    });
    </script>

    <!-- Add this to your existing JavaScript -->
    <script>
    document.querySelector('form').addEventListener('submit', function(e) {
        // Disable all submit buttons
        var submitButtons = document.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitButtons.forEach(function(button) {
            button.disabled = true;
            if (button.tagName === 'BUTTON') {
                button.innerHTML = 'Processing Order...';
            } else {
                button.value = 'Processing Order...';
            }
        });
        
        // Add a message to prevent page refresh/resubmission
        window.onbeforeunload = function() {
            return "Your order is being processed. Leaving this page may result in duplicate orders.";
        };
        
        // Allow the form to submit normally
        return true;
        });
    </script>
</body>
</html>
                                 