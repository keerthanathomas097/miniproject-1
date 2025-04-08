<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$userId = $_SESSION['id'];

// Get active cart items count
$query = "SELECT COUNT(ci.cart_item_id) as count 
          FROM tbl_cart_items ci 
          JOIN tbl_carts c ON ci.cart_id = c.cart_id 
          WHERE c.user_id = ? AND c.status = 'active'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode(['count' => (int)$data['count']]);

$stmt->close();
$conn->close();
?> 