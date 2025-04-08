<?php
// Direct order storage with minimal logic - just to store the data
// Start session and output buffering
ob_start();
session_start();

// Include database connection
include 'connect.php';

// Set content type for AJAX response
header('Content-Type: application/json');

// Create a detailed log file
$log_file = "order_insertion_log.txt";
file_put_contents($log_file, "======== NEW REQUEST " . date('Y-m-d H:i:s') . " ========\n", FILE_APPEND);
file_put_contents($log_file, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "SESSION data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    file_put_contents($log_file, "Error: User not logged in\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get user ID from session
$user_id = $_SESSION['id'];

// Get required data from POST
$outfit_id = isset($_POST['outfit_id']) ? (int)$_POST['outfit_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cod';

file_put_contents($log_file, "Extracted data: user_id=$user_id, outfit_id=$outfit_id, amount=$amount, payment=$payment_method\n", FILE_APPEND);

// Basic validation
if ($outfit_id <= 0 || $amount <= 0 || $user_id <= 0) {
    file_put_contents($log_file, "Validation error: Missing required data\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

// Create order data - same approach as the test script that works
$order_reference = 'ORD-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');
$order_status = 'CONFIRMED';
$payment_status = 'PAID';

// This is the same SQL approach used in test_order_insert.php
$insert_sql = "INSERT INTO tbl_orders (user_id, outfit_id, order_reference, amount, 
               payment_method, order_status, payment_status, created_at, updated_at) 
               VALUES ($user_id, $outfit_id, '$order_reference', $amount, 
               '$payment_method', '$order_status', '$payment_status', '$created_at', '$updated_at')";

file_put_contents($log_file, "SQL Query: $insert_sql\n", FILE_APPEND);

// Execute the query directly (same as test_order_insert.php)
$result = $conn->query($insert_sql);

if ($result) {
    $order_id = $conn->insert_id;
    file_put_contents($log_file, "SUCCESS! Order inserted with ID: $order_id\n", FILE_APPEND);
    
    // Store in session for confirmation page
    $_SESSION['current_order_id'] = $order_id;
    
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Order saved successfully'
    ]);
} else {
    $error = $conn->error;
    file_put_contents($log_file, "ERROR: Database insertion failed: $error\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error creating order: ' . $error,
        'sql_error' => $error
    ]);
}

// End output buffering
ob_end_flush();
?> 