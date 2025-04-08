<?php
session_start();
include 'connect.php';

// This page is for direct insertion without AJAX
// It can be accessed via a link or form submission

// Get outfit_id from URL or session
$outfit_id = isset($_GET['outfit_id']) ? (int)$_GET['outfit_id'] : 0;
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 1999.00; // Default amount
$user_id = $_SESSION['id'] ?? 0;

if ($outfit_id <= 0 || $user_id <= 0) {
    die("Missing required data. Please provide outfit_id and ensure you're logged in.");
}

echo "<h1>Emergency Order Creation</h1>";
echo "<p>Creating order for:<br>";
echo "User ID: $user_id<br>";
echo "Outfit ID: $outfit_id<br>";
echo "Amount: $amount</p>";

// Generate order data
$order_reference = 'EMER-' . strtoupper(substr(md5(time() . rand()), 0, 8));
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');
$payment_method = 'cod';
$order_status = 'CONFIRMED';
$payment_status = 'PAID';

// Direct insertion
$sql = "INSERT INTO tbl_orders (user_id, outfit_id, order_reference, amount, 
         payment_method, order_status, payment_status, created_at, updated_at) 
        VALUES ($user_id, $outfit_id, '$order_reference', $amount, 
         '$payment_method', '$order_status', '$payment_status', '$created_at', '$updated_at')";

echo "<p>Executing SQL:<br>" . htmlspecialchars($sql) . "</p>";

$result = $conn->query($sql);

if ($result) {
    $order_id = $conn->insert_id;
    echo "<p style='color:green'>SUCCESS! Order created with ID: $order_id</p>";
    
    // Store in session
    $_SESSION['current_order_id'] = $order_id;
    
    echo "<p><a href='confirmation.php?order_id=$order_id'>Go to confirmation page</a></p>";
} else {
    echo "<p style='color:red'>ERROR: " . $conn->error . "</p>";
    
    // Try to determine the issue
    echo "<h2>Debugging Information:</h2>";
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'tbl_orders'");
    if ($table_check->num_rows == 0) {
        echo "<p>ERROR: The table 'tbl_orders' does not exist!</p>";
    } else {
        // Check table structure
        echo "<h3>Table Structure:</h3>";
        $columns = $conn->query("DESCRIBE tbl_orders");
        echo "<pre>";
        while ($col = $columns->fetch_assoc()) {
            print_r($col);
        }
        echo "</pre>";
    }
}

// Always show recent orders for verification
echo "<h2>Recent Orders:</h2>";
$recent = $conn->query("SELECT * FROM tbl_orders ORDER BY id DESC LIMIT 5");
if ($recent && $recent->num_rows > 0) {
    echo "<table border='1'>";
    $first = true;
    while ($row = $recent->fetch_assoc()) {
        if ($first) {
            echo "<tr>";
            foreach ($row as $key => $val) {
                echo "<th>" . htmlspecialchars($key) . "</th>";
            }
            echo "</tr>";
            $first = false;
        }
        
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No orders found in database.</p>";
}
?> 