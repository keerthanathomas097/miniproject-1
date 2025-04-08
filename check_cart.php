<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['inCart' => false]);
    exit;
}

$userId = $_SESSION['id'];
$outfitId = isset($_GET['outfit_id']) ? (int)$_GET['outfit_id'] : 0;

$query = "SELECT ci.cart_item_id 
          FROM tbl_cart_items ci 
          JOIN tbl_carts c ON ci.cart_id = c.cart_id 
          WHERE c.user_id = ? AND c.status = 'active' AND ci.outfit_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $userId, $outfitId);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['inCart' => $result->num_rows > 0]);

$stmt->close();
$conn->close();
?> 