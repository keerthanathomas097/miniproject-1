<?php
session_start();
include 'connect.php';

// Log the request for debugging
$log_file = "direct_order_log.txt";
file_put_contents($log_file, "Direct order request at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents($log_file, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Get form data
$outfit_id = isset($_POST['outfit_id']) ? (int)$_POST['outfit_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'COD';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime('+5 days'));
$user_id = $_SESSION['id'] ?? 0;

// Store dates in session
$_SESSION['rental_start_date'] = $start_date;
$_SESSION['rental_end_date'] = $end_date;

// Validate data
if ($outfit_id <= 0 || $amount <= 0 || $user_id <= 0) {
    file_put_contents($log_file, "Invalid input data\n", FILE_APPEND);
    header("Location: checkout.php?outfit_id=$outfit_id&error=invalid_data");
    exit();
}

// Generate order reference
$order_reference = 'CLV' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');
$order_status = 'CONFIRMED';
$payment_status = 'PENDING';

try {
    // Insert order record
    $query = "INSERT INTO tbl_orders (
                user_id, outfit_id, order_reference, amount, 
                payment_method, order_status, payment_status,
                created_at, updated_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("iisdsssss", 
        $user_id, 
        $outfit_id, 
        $order_reference, 
        $amount, 
        $payment_method, 
        $order_status, 
        $payment_status, 
        $created_at, 
        $updated_at
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing statement: " . $stmt->error);
    }
    
    $order_id = $stmt->insert_id;
    $stmt->close();
    
    file_put_contents($log_file, "Order created with ID: $order_id\n", FILE_APPEND);
    
    // Store order ID in session
    $_SESSION['current_order_id'] = $order_id;
    
    // Redirect to confirmation page
    header("Location: confirmation.php?order_id=$order_id");
    exit();
    
} catch (Exception $e) {
    file_put_contents($log_file, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    header("Location: checkout.php?outfit_id=$outfit_id&error=db_error");
    exit();
}
?> 