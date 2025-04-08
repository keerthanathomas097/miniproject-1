<?php
// Disable output buffering for direct debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Output debugging information
echo "Starting order process...<br>";

// Start session
session_start();
echo "Session started.<br>";

// Include database connection
include 'connect.php';
echo "Database connection included.<br>";

// Get user ID from session
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 0;
echo "User ID: {$user_id}<br>";

// Get outfit ID from POST
$outfit_id = isset($_POST['outfit_id']) ? intval($_POST['outfit_id']) : 0;
echo "Outfit ID: {$outfit_id}<br>";

// Get amount from POST
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
echo "Amount: {$amount}<br>";

// Get or set other values
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'COD';
echo "Payment method: {$payment_method}<br>";

// Generate order reference
$order_reference = 'CLV' . strtoupper(substr(md5(uniqid()), 0, 8));
echo "Order reference: {$order_reference}<br>";

// Set timestamps
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');

// Set statuses
$order_status = 'CONFIRMED';
$payment_status = 'PENDING';

// Direct insertion without prepared statement first for debugging
echo "Attempting direct SQL insertion...<br>";

try {
    // Test database connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "Database connection successful.<br>";
    
    // First, let's try a simple query to test database functionality
    $test_query = "SELECT 1 AS test";
    $test_result = $conn->query($test_query);
    
    if (!$test_result) {
        throw new Exception("Test query failed: " . $conn->error);
    }
    
    echo "Test query successful.<br>";
    
    // Now try a simple insert with direct values (safer for debugging)
    $sql = "INSERT INTO tbl_orders 
            (user_id, outfit_id, order_reference, amount, payment_method, order_status, payment_status, created_at, updated_at) 
            VALUES 
            ($user_id, $outfit_id, '$order_reference', $amount, '$payment_method', '$order_status', '$payment_status', '$created_at', '$updated_at')";
    
    echo "SQL Query: " . htmlspecialchars($sql) . "<br>";
    
    if ($conn->query($sql) === TRUE) {
        $order_id = $conn->insert_id;
        echo "Order inserted successfully. Order ID: {$order_id}<br>";
        
        // Redirect to confirmation page
        echo "<script>
            alert('Order created successfully! Order ID: {$order_id}');
            window.location.href = 'confirmation.php?order_id={$order_id}';
        </script>";
        
    } else {
        throw new Exception("Insert failed: " . $conn->error);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    
    // Show database error information
    if (isset($conn)) {
        echo "Database error: " . $conn->error . "<br>";
        echo "Database errno: " . $conn->errno . "<br>";
    }
}

// End of file - no closing PHP tag to prevent whitespace issues 