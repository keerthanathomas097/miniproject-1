<?php
// Update order status after successful payment
session_start();
include 'connect.php';

// Set content type
header('Content-Type: application/json');

// Log for debugging
$log_file = "payment_success_log.txt";
file_put_contents($log_file, "=== Payment Success " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($log_file, "POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents($log_file, "SESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Get order ID from session or POST
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : ($_SESSION['current_order_id'] ?? 0);
$payment_id = isset($_POST['payment_id']) ? $_POST['payment_id'] : '';

if ($order_id <= 0) {
    file_put_contents($log_file, "ERROR: No order ID provided\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'No order ID provided']);
    exit();
}

// Update order status
$update_sql = "UPDATE tbl_orders SET 
              order_status = 'CONFIRMED', 
              payment_status = 'PAID', 
              updated_at = NOW()";

// Add payment ID if provided
if (!empty($payment_id)) {
    $update_sql .= ", razorpay_payment_id = '" . $conn->real_escape_string($payment_id) . "'";
}

$update_sql .= " WHERE id = $order_id";

file_put_contents($log_file, "Update SQL: $update_sql\n", FILE_APPEND);

$result = $conn->query($update_sql);

if ($result) {
    file_put_contents($log_file, "SUCCESS: Order $order_id updated to CONFIRMED/PAID\n", FILE_APPEND);
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Order status updated successfully'
    ]);
} else {
    file_put_contents($log_file, "ERROR: Failed to update order status: " . $conn->error . "\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update order status: ' . $conn->error
    ]);
}
?> 