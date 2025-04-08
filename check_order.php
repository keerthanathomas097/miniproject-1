<?php
session_start();
include 'connect.php';

// This is a helper script to check orders and redirect to the correct confirmation

// Get the user ID from session
$user_id = $_SESSION['id'] ?? 0;

if ($user_id <= 0) {
    die("You must be logged in to view your orders.");
}

// Get recent orders for this user
$sql = "SELECT id, outfit_id, created_at FROM tbl_orders WHERE user_id = ? ORDER BY id DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h1>Your Recent Orders</h1>";

if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Order ID</th><th>Outfit ID</th><th>Date</th><th>Action</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['outfit_id'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "<td><a href='confirmation.php?order_id=" . $row['id'] . "' style='padding: 5px 10px; background: #4CAF50; color: white; text-decoration: none;'>View Confirmation</a></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // If there are orders, automatically redirect to the most recent one after 3 seconds
    if ($result->num_rows > 0) {
        $result->data_seek(0);
        $first_row = $result->fetch_assoc();
        $latest_order_id = $first_row['id'];
        
        echo "<p>Redirecting to your most recent order (#$latest_order_id) in 3 seconds...</p>";
        echo "<script>setTimeout(function() { window.location.href = 'confirmation.php?order_id=$latest_order_id'; }, 3000);</script>";
    }
} else {
    echo "<p>You don't have any orders yet.</p>";
}

// Store the most recent order ID in the session
if (isset($latest_order_id)) {
    $_SESSION['current_order_id'] = $latest_order_id;
}
?> 