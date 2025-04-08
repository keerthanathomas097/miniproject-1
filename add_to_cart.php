<?php
session_start();
include 'connect.php';

// Get the JSON data from the request
$data = json_decode(file_get_contents("php://input"), true);
$outfitId = $data['outfitId'];
$userId = $data['userId'];

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Check if the cart already exists for the user
    $cartQuery = "SELECT cart_id FROM tbl_carts WHERE user_id = ? AND status = 'active'";
    $cartStmt = $conn->prepare($cartQuery);
    $cartStmt->bind_param("i", $userId);
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result();

    if ($cartResult->num_rows > 0) {
        // Cart exists, get the cart_id
        $cart = $cartResult->fetch_assoc();
        $cartId = $cart['cart_id'];
    } else {
        // Create a new cart
        $createCartQuery = "INSERT INTO tbl_carts (user_id, status) VALUES (?, 'active')";
        $createCartStmt = $conn->prepare($createCartQuery);
        $createCartStmt->bind_param("i", $userId);
        $createCartStmt->execute();
        $cartId = $conn->insert_id;
    }

    // Check if item already exists in cart
    $checkItemQuery = "SELECT cart_item_id FROM tbl_cart_items WHERE cart_id = ? AND outfit_id = ?";
    $checkItemStmt = $conn->prepare($checkItemQuery);
    $checkItemStmt->bind_param("ii", $cartId, $outfitId);
    $checkItemStmt->execute();
    
    if ($checkItemStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Item already in cart']);
        exit;
    }

    // Add the outfit to the cart items
    $addItemQuery = "INSERT INTO tbl_cart_items (cart_id, outfit_id) VALUES (?, ?)";
    $addItemStmt = $conn->prepare($addItemQuery);
    $addItemStmt->bind_param("ii", $cartId, $outfitId);
    $addItemStmt->execute();

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to add item to cart: ' . $e->getMessage()]);
}

// Close statements
if (isset($cartStmt)) $cartStmt->close();
if (isset($createCartStmt)) $createCartStmt->close();
if (isset($checkItemStmt)) $checkItemStmt->close();
if (isset($addItemStmt)) $addItemStmt->close();
$conn->close();
?> 