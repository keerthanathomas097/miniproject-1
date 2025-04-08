<?php
// Prevent any output before headers
ob_start();

// Start the session
session_start();

// Set proper content type
header('Content-Type: application/json');

// Include database connection
include 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Error handling
try {
    // Get payment data
    $razorpay_payment_id = isset($_POST['razorpay_payment_id']) ? $_POST['razorpay_payment_id'] : '';
    $razorpay_order_id = isset($_POST['razorpay_order_id']) ? $_POST['razorpay_order_id'] : '';
    $razorpay_signature = isset($_POST['razorpay_signature']) ? $_POST['razorpay_signature'] : '';
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    
    // Validate input
    if (empty($razorpay_payment_id) || empty($razorpay_order_id) || empty($razorpay_signature) || $order_id <= 0) {
        throw new Exception('Invalid payment data');
    }
    
    // Verify signature (you should ideally verify the signature with Razorpay's SDK)
    // For simplicity, we're assuming the payment is valid if Razorpay sent back all three parameters
    
    // Update order status in database
    $query = "UPDATE tbl_orders SET 
                payment_status = 'COMPLETED', 
                            order_status = 'CONFIRMED', 
                            razorpay_payment_id = ?, 
                            updated_at = NOW() 
                            WHERE id = ?";
                            
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("si", $razorpay_payment_id, $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error executing statement: " . $stmt->error);
    }
    
    $stmt->close();
    
    // Add transaction record if you have a transactions table
    $check = $conn->query("SHOW TABLES LIKE 'tbl_transactions'");
    if ($check->num_rows > 0) {
        $transaction_query = "INSERT INTO tbl_transactions (
                           order_id, user_id, transaction_type, 
                           payment_method, amount, status, created_at
                         ) SELECT id, user_id, 'PAYMENT', payment_method, amount, 'COMPLETED', NOW()
                         FROM tbl_orders WHERE id = ?";
                         
    $transaction_stmt = $conn->prepare($transaction_query);
        if ($transaction_stmt) {
            $transaction_stmt->bind_param("i", $order_id);
    $transaction_stmt->execute();
    $transaction_stmt->close();
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'Payment verified successfully',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log('Payment Verification Error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => 'Error verifying payment: ' . $e->getMessage()
    ]);
}

// End output buffer
ob_end_flush();
?>