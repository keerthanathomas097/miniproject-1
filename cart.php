<?php
session_start();
include 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['loggedin'])) {
    header("Location: ls.php");
    exit();
}

// Get the user's cart
$userId = $_SESSION['id'];
$cartQuery = "SELECT c.cart_id FROM tbl_carts c WHERE c.user_id = ? AND c.status = 'active'";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bind_param("i", $userId);
$cartStmt->execute();
$cartResult = $cartStmt->get_result();

if ($cartResult->num_rows > 0) {
    $cart = $cartResult->fetch_assoc();
    $cartId = $cart['cart_id'];

    // Fetch items in the cart
    $itemsQuery = "SELECT ci.*, o.description_id, o.mrp, d.description_text, 
                   (SELECT image_path FROM tbl_outfit_images WHERE outfit_id = o.outfit_id LIMIT 1) as first_image
                   FROM tbl_cart_items ci 
                   JOIN tbl_outfit o ON ci.outfit_id = o.outfit_id 
                   JOIN tbl_description d ON o.description_id = d.id
                   WHERE ci.cart_id = ?";
    $itemsStmt = $conn->prepare($itemsQuery);
    $itemsStmt->bind_param("i", $cartId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
} else {
    $itemsResult = []; // No items in the cart
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | Clover Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/navbar.css">
    <link rel="stylesheet" href="cart.css"> <!-- Optional: Custom styles for cart -->
</head>
<body>
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="Clover Logo" height="60">
                <div>
                    <h1 class="company-name">Clover</h1>
                    <p class="company-subtitle">Outfit Rentals</p>
                </div>
            </a>
            <div class="collapse navbar-collapse" id="navbarContent">
                <div class="nav-links ms-auto">
                    <a href="outfit.php" class="nav-link">RENT OUTFITS</a>
                    <a href="lending.php" class="nav-link">EARN THROUGH US</a>
                    <a href="cart.php" class="nav-link active-link">CART</a>
                    <a href="profile.php" class="nav-link">PROFILE</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>Your Cart</h2>
        <?php if ($itemsResult->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $itemsResult->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($item['first_image']); ?>" alt="Outfit Image" style="width: 50px; height: auto;">
                                <?php echo htmlspecialchars($item['description_text']); ?>
                            </td>
                            <td>₹<?php echo number_format($item['mrp'], 2); ?></td>
                            <td>
                                <form method="POST" action="remove_from_cart.php" style="display:inline;">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                </form>
                                <a href="rentnow.php?id=<?php echo $item['outfit_id']; ?>" class="btn btn-success btn-sm">Checkout Now</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Your cart is empty.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
