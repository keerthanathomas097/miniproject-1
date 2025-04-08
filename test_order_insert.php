<?php
// Basic script to test direct database insertion
session_start();
include 'connect.php';

echo "<h1>Direct Database Insertion Test</h1>";

// Create a simple test order
$user_id = $_SESSION['id'] ?? 1; // Use session ID or fallback to 1
$outfit_id = 1; // Test outfit ID
$order_reference = 'TEST-' . rand(1000, 9999);
$amount = 1999.00;
$payment_method = 'cod';
$order_status = 'CONFIRMED';
$payment_status = 'PAID';
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');

echo "<p>Attempting to insert test order with:<br>";
echo "user_id: $user_id<br>";
echo "outfit_id: $outfit_id<br>";
echo "reference: $order_reference<br>";
echo "amount: $amount</p>";

// First, let's check the table structure
$table_check = $conn->query("SHOW COLUMNS FROM tbl_orders");
echo "<h2>Table Structure:</h2>";
echo "<pre>";
$columns = [];
while ($row = $table_check->fetch_assoc()) {
    $columns[] = $row['Field'];
    print_r($row);
}
echo "</pre>";

// Here's what we're going to insert
echo "<h2>Simplified Insert Statement:</h2>";
$simple_query = "INSERT INTO tbl_orders (user_id, outfit_id, order_reference, amount, payment_method, order_status, payment_status, created_at, updated_at) 
                VALUES ($user_id, $outfit_id, '$order_reference', $amount, '$payment_method', '$order_status', '$payment_status', '$created_at', '$updated_at')";

echo "<p>" . htmlspecialchars($simple_query) . "</p>";
echo "<p>Executing direct query...</p>";

// Try direct insertion without prepare statement first
$result = $conn->query($simple_query);

if ($result) {
    $new_id = $conn->insert_id;
    echo "<p style='color:green'>SUCCESS! Inserted test order with ID: $new_id</p>";
    
    // Let's verify by selecting the record
    $verify = $conn->query("SELECT * FROM tbl_orders WHERE id = $new_id");
    if ($verify && $verify->num_rows > 0) {
        echo "<h3>Verification - Record found:</h3>";
        echo "<pre>";
        print_r($verify->fetch_assoc());
        echo "</pre>";
    }
} else {
    echo "<p style='color:red'>ERROR: " . $conn->error . "</p>";
}
?> 