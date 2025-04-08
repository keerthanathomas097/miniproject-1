<?php
session_start();
require 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: ls.php");
    exit();
}

// Get order ID
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    die("Invalid order ID");
}

// Fetch order details
$query = "SELECT o.*, 
          u.name as user_name, u.email as user_email, u.phone as user_phone,
          outfit.outfit_id, outfit.mrp as outfit_price,
          d.description_text
          FROM tbl_orders o
          LEFT JOIN tbl_users u ON o.user_id = u.user_id
          LEFT JOIN tbl_outfit outfit ON o.outfit_id = outfit.outfit_id
          LEFT JOIN tbl_description d ON outfit.description_id = d.id
          WHERE o.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Order not found");
}

// Calculate totals
$security_deposit = $order['security_deposit'];
$delivery_charge = 199.00;
$gst = $order['rental_rate'] * 0.18;
$total = $order['amount'];

// Set headers for download
header('Content-Type: text/html');
header('Content-Disposition: attachment; filename="Invoice_' . str_pad($order_id, 6, '0', STR_PAD_LEFT) . '.html"');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .invoice-header h1 {
            color: #198754;
            margin: 0;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .bill-to, .invoice-info {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .totals {
            width: 300px;
            margin-left: auto;
        }
        .totals tr td:last-child {
            text-align: right;
        }
        .terms {
            margin-top: 40px;
            font-size: 0.9em;
        }
        @media print {
            body {
                padding: 0;
            }
            .invoice-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1>Clover Outfit Rentals</h1>
            <h2>INVOICE</h2>
        </div>

        <div class="invoice-details">
            <div class="bill-to">
                <h3>Bill To:</h3>
                <p><?php echo htmlspecialchars($order['user_name']); ?><br>
                   <?php echo htmlspecialchars($order['address']); ?><br>
                   Phone: <?php echo htmlspecialchars($order['user_phone']); ?><br>
                   Email: <?php echo htmlspecialchars($order['user_email']); ?></p>
            </div>
            <div class="invoice-info">
                <h3>Invoice Details:</h3>
                <p>Invoice #: <?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?><br>
                   Date: <?php echo date('d/m/Y', strtotime($order['created_at'])); ?><br>
                   Order Ref: #<?php echo htmlspecialchars($order['order_reference']); ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Rental Days</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($order['description_text']); ?></td>
                    <td>5 Days</td>
                    <td>₹<?php echo number_format($order['rental_rate'], 2); ?></td>
                    <td>₹<?php echo number_format($order['rental_rate'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td>Security Deposit (Refundable):</td>
                <td>₹<?php echo number_format($security_deposit, 2); ?></td>
            </tr>
            <tr>
                <td>Delivery Charge:</td>
                <td>₹<?php echo number_format($delivery_charge, 2); ?></td>
            </tr>
            <tr>
                <td>GST (18%):</td>
                <td>₹<?php echo number_format($gst, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Total Amount:</strong></td>
                <td><strong>₹<?php echo number_format($total, 2); ?></strong></td>
            </tr>
        </table>

        <div class="terms">
            <h3>Terms and Conditions:</h3>
            <ol>
                <li>Security deposit is fully refundable upon return of the outfit in good condition.</li>
                <li>Rental period is strictly for 5 days from the start date.</li>
                <li>Late returns will incur additional charges.</li>
                <li>Any damage to the outfit may result in deductions from the security deposit.</li>
            </ol>
        </div>
    </div>
</body>
</html>
?> 