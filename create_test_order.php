<?php
// Emergency order creation page
session_start();
include 'connect.php';

// Check if logged in
if (!isset($_SESSION['loggedin'])) {
    die("Please log in first");
}

// Get outfit ID from URL or use default
$outfit_id = isset($_GET['outfit_id']) ? (int)$_GET['outfit_id'] : 0;
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

// Show form if values not provided
if ($outfit_id <= 0 || $amount <= 0) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Create Test Order</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { padding: 30px; }
            .container { max-width: 800px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Create Test Order</h1>
            <p>Use this form to directly create an order in the database.</p>
            
            <form method="post" class="mt-4">
                <div class="mb-3">
                    <label for="outfit_id" class="form-label">Outfit ID:</label>
                    <input type="number" class="form-control" id="outfit_id" name="outfit_id" required>
                </div>
                
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount:</label>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" value="1999.00" required>
                </div>
                
                <div class="mb-3">
                    <label for="payment_method" class="form-label">Payment Method:</label>
                    <select class="form-control" id="payment_method" name="payment_method">
                        <option value="cod">Cash on Delivery</option>
                        <option value="card">Credit Card</option>
                        <option value="upi">UPI</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Order</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $outfit_id = isset($_POST['outfit_id']) ? (int)$_POST['outfit_id'] : $outfit_id;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : $amount;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cod';
}

// Get user ID
$user_id = $_SESSION['id'] ?? 0;

if ($user_id <= 0) {
    die("Invalid user ID");
}

// Generate order data
$order_reference = 'TEST-' . strtoupper(substr(md5(uniqid()), 0, 8));
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');
$order_status = 'CONFIRMED';
$payment_status = 'PAID';

// Use the same direct query approach that works in test_order_insert.php
$sql = "INSERT INTO tbl_orders (user_id, outfit_id, order_reference, amount, 
         payment_method, order_status, payment_status, created_at, updated_at) 
        VALUES ($user_id, $outfit_id, '$order_reference', $amount, 
         '$payment_method', '$order_status', '$payment_status', '$created_at', '$updated_at')";

echo "<!DOCTYPE html>
<html>
<head>
    <title>Create Test Order</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 30px; }
        .container { max-width: 800px; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Creating Test Order</h1>
        <p>Attempting to create a test order with:</p>
        <ul>
            <li>User ID: $user_id</li>
            <li>Outfit ID: $outfit_id</li>
            <li>Amount: $amount</li>
            <li>Payment Method: $payment_method</li>
        </ul>
        
        <h3>SQL Query:</h3>
        <pre>" . htmlspecialchars($sql) . "</pre>";

$result = $conn->query($sql);

if ($result) {
    $order_id = $conn->insert_id;
    
    // Store in session for confirmation page
    $_SESSION['current_order_id'] = $order_id;
    
    echo "<div class='alert alert-success'>
            <h4>Success!</h4>
            <p>Order created with ID: $order_id</p>
          </div>
          
          <div class='mt-4'>
            <a href='confirmation.php?order_id=$order_id' class='btn btn-primary'>View Order Confirmation</a>
            <a href='index.php' class='btn btn-secondary ms-2'>Return to Home</a>
          </div>";
} else {
    echo "<div class='alert alert-danger'>
            <h4>Error!</h4>
            <p>Failed to create order: " . $conn->error . "</p>
          </div>";
}

echo "</div></body></html>";
?> 