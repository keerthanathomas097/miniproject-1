<?php
session_start();
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cart_item_id'])) {
    $cartItemId = $_POST['cart_item_id'];

    // Remove the item from the cart
    $removeQuery = "DELETE FROM tbl_cart_items WHERE cart_item_id = ?";
    $removeStmt = $conn->prepare($removeQuery);
    $removeStmt->bind_param("i", $cartItemId);
    $removeStmt->execute();

    // Redirect back to the cart page
    header("Location: cart.php");
    exit();
}
?> 