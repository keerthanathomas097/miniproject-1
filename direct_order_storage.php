<?php
// Direct Order Storage with rental_rate and security_deposit
// Start session and buffering
ob_start();
session_start();
include 'connect.php';

// Set content type for AJAX response
header('Content-Type: application/json');

// Create detailed log for debugging
$log_file = "order_storage_log.txt";
file_put_contents($log_file, "=== New Order Request " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($log_file, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "SESSION data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Get user ID from session
$user_id = $_SESSION['id'] ?? 0;

// Get basic order data from POST
$outfit_id = isset($_POST['outfit_id']) ? (int)$_POST['outfit_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cod';

// Get rental rate and security deposit from POST
$rental_rate = isset($_POST['rental_rate']) ? (float)$_POST['rental_rate'] : 0;
$security_deposit = isset($_POST['security_deposit']) ? (float)$_POST['security_deposit'] : 0;

// If not provided, calculate based on outfit price
if ($rental_rate <= 0 || $security_deposit <= 0) {
    // Query to get outfit price from database
    $outfit_query = "SELECT mrp FROM tbl_outfit WHERE outfit_id = ?";
    $outfit_stmt = $conn->prepare($outfit_query);
    
    if ($outfit_stmt) {
        $outfit_stmt->bind_param("i", $outfit_id);
        $outfit_stmt->execute();
        $outfit_result = $outfit_stmt->get_result();
        
        if ($outfit_row = $outfit_result->fetch_assoc()) {
            $outfit_price = $outfit_row['mrp'];
            
            // Calculate rental rate and security deposit if not provided
            if ($rental_rate <= 0) {
                $rental_rate = $outfit_price * 0.10; // 10% of MRP
            }
            
            if ($security_deposit <= 0) {
                $security_deposit = $outfit_price * 0.10; // 10% of MRP
            }
        }
        
        $outfit_stmt->close();
    }
}

file_put_contents($log_file, "Processed data: user_id=$user_id, outfit_id=$outfit_id, amount=$amount, " .
                          "rental_rate=$rental_rate, security_deposit=$security_deposit\n", FILE_APPEND);

// Simple validation
if ($user_id <= 0) {
    file_put_contents($log_file, "ERROR: User not logged in\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if ($outfit_id <= 0 || $amount <= 0) {
    file_put_contents($log_file, "ERROR: Invalid outfit ID or amount\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Invalid outfit ID or amount']);
    exit();
}

// Generate order data
$order_reference = 'ORD-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');
$order_status = 'CONFIRMED'; 
$payment_status = 'PAID';

// Use direct query insertion - proven to work in test_order_insert.php
// Now including rental_rate and security_deposit
$sql = "INSERT INTO tbl_orders (
            user_id, outfit_id, order_reference, amount, 
            rental_rate, security_deposit,
            payment_method, order_status, payment_status, 
            created_at, updated_at
        ) VALUES (
            $user_id, $outfit_id, '$order_reference', $amount, 
            $rental_rate, $security_deposit,
            '$payment_method', '$order_status', '$payment_status', 
            '$created_at', '$updated_at'
        )";

file_put_contents($log_file, "SQL Query: $sql\n", FILE_APPEND);

// Execute the query
$result = $conn->query($sql);

if ($result) {
    $order_id = $conn->insert_id;
    file_put_contents($log_file, "SUCCESS! Order inserted with ID: $order_id\n", FILE_APPEND);
    
    // Save order ID in session for later use
    $_SESSION['current_order_id'] = $order_id;
    $_SESSION['temp_outfit_id'] = $outfit_id;
    $_SESSION['temp_amount'] = $amount;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Order created successfully, ready for payment processing'
    ]);
} else {
    $error = $conn->error;
    file_put_contents($log_file, "ERROR: Database error: $error\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create order: ' . $error
    ]);
}

ob_end_flush();
?> 