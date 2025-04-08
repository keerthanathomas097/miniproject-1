<?php
// Script for manual order creation
session_start();
include 'connect.php';

// Require login
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    die("Please log in to create an order.");
}

// Get parameters from URL
$outfit_id = isset($_GET['outfit_id']) ? (int)$_GET['outfit_id'] : 0;
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

// Display form if not submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && ($outfit_id <= 0 || $amount <= 0)) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Create Manual Order</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .form-group { margin-bottom: 15px; }
            label { display: block; margin-bottom: 5px; }
            input, select { padding: 8px; width: 300px; }
            button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <h1>Create Manual Order</h1>
        <form method="post">
            <div class="form-group">
                <label for="outfit_id">Outfit ID:</label>
                <input type="number" id="outfit_id" name="outfit_id" value="<?php echo $outfit_id; ?>" required>
            </div>
            <div class="form-group">
                <label for="amount">Amount:</label>
                <input type="number" id="amount" name="amount" value="<?php echo $amount ?: 1999; ?>" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="payment_method">Payment Method:</label>
                <select id="payment_method" name="payment_method">
                    <option value="cod">Cash on Delivery</option>
                    <option value="card">Credit/Debit Card</option>
                    <option value="upi">UPI</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit">Create Order</button>
            </div>
        </form>
    </body>
    </html>
    <?php
    exit;
}

// Process form submission
$user_id = $_SESSION['id'];
$outfit_id = isset($_POST['outfit_id']) ? (int)$_POST['outfit_id'] : $outfit_id;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : $amount;
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cod';

// Validate
if ($outfit_id <= 0 || $amount <= 0) {
    die("Invalid outfit ID or amount");
}

// Generate order reference and timestamps
$order_reference = 'MANUAL-' . strtoupper(substr(md5(time() . rand()), 0, 8));
$created_at = date('Y-m-d H:i:s');
$updated_at = date('Y-m-d H:i:s');
$order_status = 'CONFIRMED';
$payment_status = 'PAID';

// Insert order
$sql = "INSERT INTO tbl_orders (user_id, outfit_id, order_reference, amount, 
         payment_method, order_status, payment_status, created_at, updated_at) 
        VALUES ($user_id, $outfit_id, '$order_reference', $amount, 
         '$payment_method', '$order_status', '$payment_status', '$created_at', '$updated_at')";

$result = $conn->query($sql);

if ($result) {
    $order_id = $conn->insert_id;
    
    // Store in session
    $_SESSION['current_order_id'] = $order_id;
    
    // Show success and link to confirmation
    echo "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Order Created</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .success { color: green; font-weight: bold; }
            .btn { display: inline-block; padding: 10px 15px; background: #4CAF50; color: white; text-decoration: none; margin-top: 20px; }
        </style>
    </head>
    <body>
        <h1>Order Created Successfully</h1>
        <p class='success'>Your order has been created with ID: $order_id</p>
        <p>Order Reference: $order_reference</p>
        <p>Amount: ₹$amount</p>
        <p>Payment Method: $payment_method</p>
        
        <a href='confirmation.php?order_id=$order_id' class='btn'>Go to Confirmation Page</a>
    </body>
    </html>";
} else {
    echo "<h1>Error</h1>";
    echo "<p>Failed to create order: " . $conn->error . "</p>";
    echo "<p>SQL: $sql</p>";
}
?> 