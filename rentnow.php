<?php
session_start();
include 'connect.php';

// Get outfit ID from URL
$outfit_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Fetch outfit details with description, brand, and size
$query = "SELECT o.*, d.description_text, b.subcategory_name as brand_name, 
          s.subcategory_name as size_name, s.id as size_id
          FROM tbl_outfit o
          LEFT JOIN tbl_description d ON o.description_id = d.id
          LEFT JOIN tbl_subcategory b ON o.brand_id = b.id
          LEFT JOIN tbl_subcategory s ON o.size_id = s.id
          WHERE o.outfit_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $outfit_id);
$stmt->execute();
$result = $stmt->get_result();
$outfit = $result->fetch_assoc();

// Calculate rental price
$rental_price = $outfit['mrp'] * 0.20;

// Fetch all available sizes
$size_query = "SELECT s.* FROM tbl_subcategory s 
               JOIN tbl_category c ON s.category_id = c.id 
               WHERE c.category_name = 'Size'";
$size_result = $conn->query($size_query);

// Fetch admin uploaded images
$images_query = "SELECT * FROM tbl_outfit_images 
                WHERE outfit_id = ? AND uploaded_by = 'admin' 
                ORDER BY uploaded_at ASC";
$stmt = $conn->prepare($images_query);
$stmt->bind_param("i", $outfit_id);
$stmt->execute();
$images_result = $stmt->get_result();
$outfit_images = $images_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Outfit | Clover Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="rentnow.css">
    <link rel="stylesheet" href="styles/navbar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <div class="nav-links ms-auto">
                    <a href="outfit.php" class="nav-link active-link">RENT OUTFITS</a>
                    <a href="lending.php" class="nav-link">EARN THROUGH US</a>
                    <a href="outfit.php?gender=male" class="nav-link">MEN</a>
                    <a href="outfit.php?occasion=wedding" class="nav-link">BRIDAL</a>
                    <a href="ls.php?showModal=true" class="nav-link">SIGN UP</a>
                    
                    <div class="nav-icons">
                        <a href="cart.php" class="icon-link">
                            <i class="bi bi-bag"></i>
                        </a>
                        <a href="profile.php" class="icon-link">
                            <i class="bi bi-person"></i>
                        </a>
                        <a href="index.php" class="icon-link">
                            <i class="bi bi-house"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row product-details-container">
            <!-- Add to Cart Button -->
            

            <!-- Image Gallery Section -->
            <div class="col-md-6">
                <div class="image-gallery">
                    <div class="thumbnails">
                        <?php
                        // Display admin uploaded images
                        foreach($outfit_images as $index => $image) {
                            $active = ($index === 0) ? 'active' : '';
                            echo '<img src="'.$image['image_path'].'" class="thumbnail '.$active.'" 
                                      onclick="changeImage(this.src)" alt="Product Image">';
                        }
                        ?>
                    </div>
                    <div class="main-image">
                        <?php if(!empty($outfit_images)): ?>
                            <img src="<?php echo $outfit_images[0]['image_path']; ?>" id="mainImage" alt="Product Image">
                        <?php else: ?>
                            <div class="no-image">No professional images available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Product Details and Form Section -->
            <div class="col-md-6">
                <div class="product-info">
                    <h2 class="product-title"><?php echo htmlspecialchars($outfit['description_text']); ?></h2>
                    <p class="product-price">₹<?php echo number_format($rental_price, 2); ?></p>
                    <button class="btn btn-dark" id="addToCartBtn" style="width: 50%; margin-bottom: 20px;">
                <i class="bi bi-cart-plus"></i> Add to Cart
                 </button>
                    <!-- Size Chart Icon -->
                    <div class="size-chart">
                        <i class="fas fa-ruler"></i>
                        <span>Size Chart</span>
                    </div>

                    <!-- Measurements Form -->
                    <div class="measurements-form">
                        <h4>Enter Your Measurements</h4>
                        <div class="form-group">
                            <label>Height (inches)</label>
                            <input type="number" class="form-control" id="height">
                        </div>
                        <div class="form-group">
                            <label>Shoulder Width (inches)</label>
                            <input type="number" class="form-control" id="shoulder">
                        </div>
                        <div class="form-group">
                            <label>Bust (inches)</label>
                            <input type="number" class="form-control" id="bust">
                        </div>
                        <div class="form-group">
                            <label>Waist (inches)</label>
                            <input type="number" class="form-control" id="waist">
                        </div>
                    </div>

                    <!-- Available Sizes -->
                    <div class="size-selection">
                        <h4>Available Sizes</h4>
                        <div class="size-buttons">
                            <?php
                            while($size = $size_result->fetch_assoc()) {
                                $isActive = ($size['id'] == $outfit['size_id']) ? 'active' : '';
                                $isDisabled = ($size['id'] != $outfit['size_id']) ? 'disabled' : '';
                                echo '<button class="size-btn '.$isActive.'" '.$isDisabled.'>'
                                     .htmlspecialchars($size['subcategory_name']).
                                     '</button>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Rental Duration -->
                    <div class="rental-duration">
                        <h4><i class="bi bi-calendar3"></i> Rental Duration</h4>
                        <div class="duration-options">
                            <input type="radio" id="3days" name="duration" value="3" checked>
                            <label for="3days">3 Days</label>

                            <input type="radio" id="5days" name="duration" value="5">
                            <label for="5days">5 Days</label>

                            <input type="radio" id="7days" name="duration" value="7">
                            <label for="7days">7 Days</label>
                        </div>
                    </div>

                    <!-- Date Selection -->
                    <div class="date-selection">
                        <h4><i class="bi bi-calendar-event"></i> Select Event Date</h4>
                        <input type="text" id="eventDate" class="form-control datepicker">
                        
                        <div class="rental-dates">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="text" id="startDate" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="text" id="endDate" class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button class="submit-btn">
                        <i class="bi bi-cart-plus"></i> Proceed to Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="rentnow.js"></script>
    <script>
    document.getElementById('addToCartBtn').addEventListener('click', function() {
        const outfitId = <?php echo $outfit_id; ?>; // Get the outfit ID from PHP
        const userId = <?php echo $_SESSION['id']; ?>; // Get the user ID from session

        // Make an AJAX request to add the item to the cart
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ outfitId: outfitId, userId: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Item added to cart successfully!');
            } else {
                alert('Error adding item to cart: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
    </script>
</body>
</html>
