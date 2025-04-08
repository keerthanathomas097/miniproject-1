<?php
// Start session and output buffering
ob_start();
session_start();

// Include database connection
include 'connect.php';

// Set content type to JSON for AJAX response
header('Content-Type: application/json');

// Create log for debugging
$log_file = "order_save_log.txt";
file_put_contents($log_file, "=== New Request " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
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

// Get post data with validation
$outfit_id = isset($_POST['outfit_id']) ? (int)$_POST['outfit_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cod';

// Store dates in session
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime('+5 days'));
$_SESSION['rental_start_date'] = $start_date;
$_SESSION['rental_end_date'] = $end_date;

file_put_contents($log_file, "Processed data: user_id=$user_id, outfit_id=$outfit_id, amount=$amount, payment_method=$payment_method\n", FILE_APPEND);

// Validate essential data
if ($outfit_id <= 0 || $amount <= 0 || $user_id <= 0) {
    file_put_contents($log_file, "Error: Invalid input data\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

// Generate a unique order reference
$order_reference = 'CLV-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

// Set timestamps and status
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');
$order_status = 'CONFIRMED';
$payment_status = 'PAID';

try {
    // Using direct query (this worked in the test)
    $insert_sql = "INSERT INTO tbl_orders (user_id, outfit_id, order_reference, amount, 
                    payment_method, order_status, payment_status, created_at, updated_at) 
                   VALUES ($user_id, $outfit_id, '$order_reference', $amount, 
                    '$payment_method', '$order_status', '$payment_status', '$created_at', '$updated_at')";

    file_put_contents($log_file, "SQL Query: $insert_sql\n", FILE_APPEND);
    
    $result = $conn->query($insert_sql);
    
    if ($result) {
        // Get the new order ID
        $order_id = $conn->insert_id;
        file_put_contents($log_file, "Success! Order inserted with ID: $order_id\n", FILE_APPEND);
        
        // CRITICAL: Store the new order ID in the session
        $_SESSION['current_order_id'] = $order_id;
        
        // Return success with the new order ID
        echo json_encode([
            'success' => true,
            'order_id' => $order_id,
            'message' => 'Order created successfully'
        ]);
    } else {
        throw new Exception("Database error: " . $conn->error);
    }
} catch (Exception $e) {
    file_put_contents($log_file, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error creating order: ' . $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
?>