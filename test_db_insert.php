<?php
// Basic database connection and direct insertion test
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Database Insert Test</h2>";

// Start session for user ID
session_start();
echo "Session started<br>";

// Include database connection
include 'connect.php';
echo "Database connection included<br>";

// Create sample data
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : 1; // Fallback to 1 if not set
$outfit_id = 1; // Sample outfit ID
$order_reference = 'TEST-' . rand(1000, 9999);
$amount = 100.00;
$payment_method = 'COD';
$order_status = 'CONFIRMED';
$payment_status = 'PENDING';
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');

echo "Test data prepared:<br>";
echo "- User ID: $user_id<br>";
echo "- Outfit ID: $outfit_id<br>";
echo "- Order Reference: $order_reference<br>";
echo "- Amount: $amount<br>";
echo "- Created at: $created_at<br>";

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connection to database successful<br>";

// Test if we can select from the table
echo "Testing SELECT query...<br>";
$test_select = "SELECT * FROM tbl_orders LIMIT 1";
$select_result = $conn->query($test_select);

if ($select_result === FALSE) {
    echo "Error in SELECT query: " . $conn->error . "<br>";
} else {
    echo "SELECT query successful, found " . $select_result->num_rows . " rows<br>";
}

// Try a simple insert with explicit column names
echo "Attempting INSERT...<br>";

$insert_sql = "INSERT INTO tbl_orders 
               (user_id, outfit_id, order_reference, amount, payment_method, order_status, payment_status, created_at, updated_at) 
               VALUES 
               (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insert_sql);
if (!$stmt) {
    echo "Prepare statement failed: " . $conn->error . "<br>";
    // Try direct insert as fallback
    $direct_sql = "INSERT INTO tbl_orders 
                  (user_id, outfit_id, order_reference, amount, payment_method, order_status, payment_status, created_at, updated_at) 
                  VALUES 
                  ($user_id, $outfit_id, '$order_reference', $amount, '$payment_method', '$order_status', '$payment_status', '$created_at', '$updated_at')";
    
    echo "Attempting direct SQL: " . htmlspecialchars($direct_sql) . "<br>";
    
    if ($conn->query($direct_sql) === TRUE) {
        $new_id = $conn->insert_id;
        echo "Direct insert successful! New order ID: $new_id<br>";
        echo "<a href='confirmation.php?order_id=$new_id'>View Order Confirmation</a>";
    } else {
        echo "Direct insert failed: " . $conn->error . "<br>";
        
        // Extreme fallback - try creating the table
        echo "Attempting to verify/create table structure...<br>";
        $create_table = "CREATE TABLE IF NOT EXISTS tbl_orders (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) NOT NULL,
            outfit_id int(11) NOT NULL,
            order_reference varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_method varchar(20) NOT NULL,
            order_status varchar(20) NOT NULL,
            payment_status varchar(20) NOT NULL DEFAULT 'PENDING',
            razorpay_order_id varchar(100) DEFAULT NULL,
            razorpay_payment_id varchar(100) DEFAULT NULL,
            notes text DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id)
        )";
        
        if ($conn->query($create_table) === TRUE) {
            echo "Table structure verified/created. Attempting insert again...<br>";
            
            if ($conn->query($direct_sql) === TRUE) {
                $new_id = $conn->insert_id;
                echo "Insert after table verification successful! New order ID: $new_id<br>";
                echo "<a href='confirmation.php?order_id=$new_id'>View Order Confirmation</a>";
            } else {
                echo "Insert after table verification failed: " . $conn->error . "<br>";
            }
        } else {
            echo "Table creation failed: " . $conn->error . "<br>";
        }
    }
} else {
    echo "Prepare statement successful<br>";
    
    if (!$stmt->bind_param("iisdssss", $user_id, $outfit_id, $order_reference, $amount, $payment_method, $order_status, $payment_status, $created_at, $updated_at)) {
        echo "Binding parameters failed: " . $stmt->error . "<br>";
    } else {
        echo "Parameters bound successfully<br>";
        
        if (!$stmt->execute()) {
            echo "Execution failed: " . $stmt->error . "<br>";
        } else {
            $new_id = $stmt->insert_id;
            echo "Execution successful! New order ID: $new_id<br>";
            echo "<a href='confirmation.php?order_id=$new_id'>View Order Confirmation</a>";
        }
    }
    
    $stmt->close();
}

// Check permissions on database user
echo "<h3>Database User Permissions</h3>";
$permissions_query = "SHOW GRANTS FOR CURRENT_USER()";
$permissions_result = $conn->query($permissions_query);

if ($permissions_result === FALSE) {
    echo "Error checking permissions: " . $conn->error . "<br>";
} else {
    echo "<ul>";
    while ($row = $permissions_result->fetch_row()) {
        echo "<li>" . htmlspecialchars($row[0]) . "</li>";
    }
    echo "</ul>";
}

$conn->close();
?> 