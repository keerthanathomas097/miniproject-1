<?php
session_start();
include 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Check required fields
if (!isset($_POST['outfit_id']) || !isset($_POST['amount']) || !isset($_POST['payment_method'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$outfit_id = (int)$_POST['outfit_id'];
$amount = (float)$_POST['amount'];
$payment_method = $_POST['payment_method'];
$user_id = $_SESSION['id'];

// Validate data
if ($outfit_id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit();
}

// Generate a unique order ID for our database
$order_reference = 'CLV' . time() . rand(100, 999);

// Insert order into database with pending status
$insert_query = "INSERT INTO tbl_orders (user_id, outfit_id, order_reference, amount, payment_method, order_status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'PENDING', NOW())";

$stmt = $conn->prepare($insert_query);
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("iisds", $user_id, $outfit_id, $order_reference, $amount, $payment_method);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to create order: ' . $stmt->error]);
    exit();
}

$order_id = $stmt->insert_id;
$stmt->close();

// If payment method is COD, we don't need to create a Razorpay order
if ($payment_method === 'cod') {
    echo json_encode(['success' => true, 'order_id' => $order_id]);
    exit();
}

// For online payments, create a Razorpay order
// Include Razorpay PHP SDK - you need to download this from https://github.com/razorpay/razorpay-php
// Assuming it's in a folder called 'razorpay-php'
require_once 'razorpay-php/Razorpay.php';

// Initialize Razorpay API
$api_key = 'rzp_test_RoLPqd8lDB9nb1';
$api_secret = 'YOUR_API_SECRET'; // Replace with your actual API secret

$api = new Razorpay\Api\Api($api_key, $api_secret);

// Create order payload for Razorpay
try {
    $razorpay_order = $api->order->create([
        'receipt' => $order_reference,
        'amount' => $amount * 100, // Convert to paise
        'currency' => 'INR',
        'payment_capture' => 1 // Auto-capture
    ]);
    
    // Update our order with Razorpay order ID
    $update_query = "UPDATE tbl_orders SET razorpay_order_id = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $razorpay_order_id = $razorpay_order['id'];
    $update_stmt->bind_param("si", $razorpay_order_id, $order_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Send response back to client
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'razorpay_order_id' => $razorpay_order_id
    ]);
    
} catch (Exception $e) {
    // If Razorpay order creation fails, update order status and return error
    $error_query = "UPDATE tbl_orders SET order_status = 'FAILED', notes = ? WHERE id = ?";
    $error_stmt = $conn->prepare($error_query);
    $error_message = "Razorpay Error: " . $e->getMessage();
    $error_stmt->bind_param("si", $error_message, $order_id);
    $error_stmt->execute();
    $error_stmt->close();
    
    echo json_encode(['success' => false, 'message' => $error_message]);
}
?>