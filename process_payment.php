<?php
session_start();
include 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get post data
$payment_id = $_POST['payment_id'] ?? '';
$outfit_id = (int)($_POST['outfit_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$payment_method = $_POST['payment_method'] ?? 'credit_card';
$user_id = (int)($_POST['user_id'] ?? 0);
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';

// Validate data
if (empty($payment_id) || $outfit_id <= 0 || $amount <= 0 || $user_id <= 0 || empty($start_date) || empty($end_date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit();
}

// In a real implementation, you would verify the payment with Razorpay API here
// For the simplicity of this example, we'll assume payment verification was successful

// Generate a unique order ID
$order_id = 'CLV' . date('YmdHis') . rand(100, 999);

// Calculate rental components again to ensure consistency
$query = "SELECT mrp FROM tbl_outfit WHERE outfit_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $outfit_id);
$stmt->execute();
$result = $stmt->get_result();
$outfit = $result->fetch_assoc();
$stmt->close();

if (!$outfit) {
    echo json_encode(['success' => false, 'message' => 'Outfit not found']);
    exit();
}

$actual_rental = $outfit['mrp'] * 0.10; // 10% of MRP
$security_deposit = $outfit['mrp'] * 0.10; // 10% of MRP
$delivery_charge = 199.00;
$gst = $actual_rental * 0.18; // GST on rental amount

// Save order to database
$order_query = "INSERT INTO tbl_orders (order_id, user_id, outfit_id, amount, 
                rental_amount, security_deposit, delivery_charge, gst,
                payment_id, payment_method, payment_status, order_status, 
                start_date, end_date, order_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'processing', ?, ?, NOW())";

$order_stmt = $conn->prepare($order_query);
$payment_status = 'paid';

$order_stmt->bind_param("siiiddddsssss", 
    $order_id, 
    $user_id, 
    $outfit_id, 
    $amount, 
    $actual_rental, 
    $security_deposit, 
    $delivery_charge, 
    $gst, 
    $payment_id, 
    $payment_method, 
    $start_date, 
    $end_date
);

if ($order_stmt->execute()) {
    echo json_encode(['success' => true, 'order_id' => $order_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error saving order: ' . $order_stmt->error]);
}
$order_stmt->close();
?>