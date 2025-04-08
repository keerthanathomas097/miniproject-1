<?php
// Start output buffering
ob_start();

// Set content type to JSON
header('Content-Type: application/json');

// Disable error display
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Enable session if needed
session_start();

// Test log file (make sure directory is writable)
$log_file = fopen("razorpay_debug_simple.log", "a");
fwrite($log_file, "\n\n" . date('Y-m-d H:i:s') . " - Request started\n");

try {
    // Get request parameters
    $outfit_id = isset($_POST['outfit_id']) ? (int)$_POST['outfit_id'] : 0;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    fwrite($log_file, "Received data: outfit_id=$outfit_id, amount=$amount, payment_method=$payment_method, user_id=$user_id\n");
    
    // Define Razorpay keys
    $razorpay_key = 'rzp_test_a2JV5WZK9Vupym';
    $razorpay_secret = 'UI6ejdHJM0UqUz9EXpB2ivT6';
    
    // Generate a unique order reference
    $order_reference = 'CLV-' . date('YmdHis') . '-' . rand(100, 999);
    
    // Order data for Razorpay
    $razorpay_data = [
        'amount' => $amount * 100, // Convert to paise
        'currency' => 'INR',
        'receipt' => $order_reference,
        'notes' => [
            'outfit_id' => $outfit_id,
            'user_id' => $user_id
        ]
    ];
    
    fwrite($log_file, "Razorpay request data: " . json_encode($razorpay_data) . "\n");
    
    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($razorpay_data));
    curl_setopt($ch, CURLOPT_USERPWD, $razorpay_key . ':' . $razorpay_secret);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // Execute cURL request
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    
    fwrite($log_file, "HTTP status: $http_status\n");
    fwrite($log_file, "Response: $response\n");
    
    if ($err) {
        fwrite($log_file, "cURL error: $err\n");
        throw new Exception("cURL Error: $err");
    }
    
    if ($http_status != 200) {
        fwrite($log_file, "API Error. Status: $http_status, Response: $response\n");
        throw new Exception("API Error: " . $response);
    }
    
    curl_close($ch);
    
    // Parse the Razorpay response
    $razorpay_response = json_decode($response, true);
    
    if (!isset($razorpay_response['id'])) {
        fwrite($log_file, "Invalid Razorpay response: Missing order ID\n");
        throw new Exception("Invalid Razorpay response: Missing order ID");
    }
    
    $razorpay_order_id = $razorpay_response['id'];
    fwrite($log_file, "Order ID from Razorpay: $razorpay_order_id\n");
    
    // For now we'll skip database operations and just return the order info
    // In production, you would save this to your database
    
    // Return formatted success response that matches what your JS expects
    echo json_encode([
        'success' => true,
        'order_id' => 1, // Placeholder - in production this would be your database ID
        'razorpay_order_id' => $razorpay_order_id,
        'amount' => $amount * 100
    ]);
    
} catch (Exception $e) {
    fwrite($log_file, "Error: " . $e->getMessage() . "\n");
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error creating order: ' . $e->getMessage()
    ]);
}

fwrite($log_file, "Request completed at " . date('Y-m-d H:i:s') . "\n");
fclose($log_file);

// End output buffering
ob_end_flush();
exit();
?> 