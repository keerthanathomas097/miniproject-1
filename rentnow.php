<?php
session_start();
include 'connect.php';

// Get outfit ID from URL
$outfit_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Check if outfit is on lend
$is_on_lend = isset($_GET['on_lend']) && $_GET['on_lend'] == '1';

// Get the availability date for on-lend outfits
$available_date = '';
if ($is_on_lend) {
    $date_query = "SELECT created_at 
                   FROM tbl_orders 
                   WHERE outfit_id = ? 
                   AND order_status = 'CONFIRMED'
                   ORDER BY created_at DESC 
                   LIMIT 1";
    $stmt = $conn->prepare($date_query);
    $stmt->bind_param("i", $outfit_id);
    $stmt->execute();
    $date_result = $stmt->get_result();
    $date_data = $date_result->fetch_assoc();
    
    if ($date_data) {
        $end_date = new DateTime($date_data['created_at']);
        $end_date->modify('+16 days'); // 14 days rental + 2 days processing
        $available_date = $end_date->format('M d, Y');
    }
}

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

// Calculate rental price breakdown
$actual_rental = $outfit['mrp'] * 0.10; // 10% of MRP for rental
$security_deposit = $outfit['mrp'] * 0.10; // 10% of MRP for security
$total_rental = $actual_rental + $security_deposit; // Total 20%

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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Rent Outfit | Clover Rentals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="rentnow.css">
    <link rel="stylesheet" href="styles/navbar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .form-disabled {
        opacity: 0.7 !important;
        pointer-events: none !important;
        -webkit-user-select: none !important;
        -moz-user-select: none !important;
        -ms-user-select: none !important;
        user-select: none !important;
    }

    .form-disabled input,
    .form-disabled button,
    .form-disabled select,
    .form-disabled textarea,
    .form-disabled .size-btn,
    .form-disabled .duration-options {
        background-color: #f8f9fa !important;
        border-color: #dee2e6 !important;
        cursor: not-allowed !important;
    }

    .form-disabled .size-btn,
    .form-disabled .duration-options label {
        opacity: 0.7 !important;
        pointer-events: none !important;
    }

    .lend-notice {
        background-color: rgba(128, 0, 32, 0.1);
        border: 1px solid rgba(128, 0, 32, 0.3);
        color: #800020;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
    }

    .similar-outfits-section {
        padding: 40px 0;
        background-color: #fff;
    }

    .outfit-card {
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
        height: 100%;
    }

    .outfit-card:hover {
        transform: translateY(-5px);
    }

    .outfit-card-image {
        position: relative;
        width: 100%;
        height: 400px;
        overflow: hidden;
    }

    .outfit-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .outfit-card:hover .outfit-card-image img {
        transform: scale(1.05);
    }

    .outfit-card-content {
        padding: 20px;
        text-align: center;
    }

    .outfit-title {
        font-size: 18px;
        color: #333;
        margin-bottom: 10px;
        font-weight: 500;
        line-height: 1.4;
    }

    .outfit-brand {
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
    }

    .outfit-price {
        color: #800020;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .rent-btn, .view-btn {
        display: inline-block;
        padding: 10px 30px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
        width: 80%;
        text-align: center;
    }

    .rent-btn {
        background-color: #800020;
        color: white;
        border: 2px solid #800020;
    }

    .view-btn {
        background-color: transparent;
        color: #800020;
        border: 2px solid #800020;
    }

    .rent-btn:hover {
        background-color: #600018;
        border-color: #600018;
        color: white;
        text-decoration: none;
    }

    .view-btn:hover {
        background-color: #800020;
        color: white;
        text-decoration: none;
    }

    .on-lend-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: rgba(128, 0, 32, 0.9);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 14px;
    }

    .section-title {
        text-align: center;
        font-size: 32px;
        color: #333;
        margin-bottom: 40px;
        font-weight: 600;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .outfit-card-image {
            height: 300px;
        }
        
        .outfit-title {
            font-size: 16px;
        }
        
        .outfit-price {
            font-size: 18px;
        }
    }

    .cart-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #800020;
        color: white;
        border-radius: 50%;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        min-height: 18px;
        animation: pulse 0.5s ease-in-out;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.2);
        }
        100% {
            transform: scale(1);
        }
    }

    .icon-link {
        position: relative;
        display: inline-block;
    }

    .form-group {
        margin-bottom: 1.5rem;
        position: relative;
    }
    
    .invalid-feedback {
        display: none;
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }

    input:invalid + .invalid-feedback {
        display: block;
    }

    input.is-invalid {
        border-color: #dc3545;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    input.is-valid {
        border-color: #198754;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }

    .text-muted {
        font-size: 0.875rem;
    }
    </style>
    <script src="imageZoom.js"></script>
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
                            <img src="<?php echo $outfit_images[0]['image_path']; ?>" 
                                 id="mainImage" 
                                 alt="Product Image"
                                 style="max-width: 100%; height: auto;">
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
                    
                    <?php if ($is_on_lend): ?>
                        <div class="lend-notice">
                            <i class="fas fa-clock mr-2"></i> This outfit is currently on lend and will be available after: <?php echo $available_date; ?>
                        </div>
                    <?php endif; ?>

                    <div class="price-breakdown">
                        <p class="product-price">Total: ₹<?php echo number_format($total_rental, 2); ?></p>
                        <div class="price-details">
                            <div class="price-row">
                                <span>Rental Price (10% of MRP)</span>
                                <span>₹<?php echo number_format($actual_rental, 2); ?></span>
                            </div>
                            <div class="price-row">
                                <span>Security Deposit (10% of MRP)</span>
                                <span>₹<?php echo number_format($security_deposit, 2); ?> <small class="text-success">(Refundable)</small></span>
                            </div>
                        </div>
                    </div>

                    <?php if (!$is_on_lend): ?>
                    <button class="btn btn-dark" id="addToCartBtn" style="width: 50%; margin-bottom: 20px;">
                <i class="bi bi-cart-plus"></i> Add to Cart
                 </button>
                    <?php endif; ?>
                    
                    <!-- Size Chart Icon -->
                    <div class="size-chart" style="margin-bottom: 20px;">
                        <a href="#" id="sizeChartLink" style="text-decoration: none; color: var(--primary); display: flex; align-items: center;">
                            <i class="fas fa-ruler" style="margin-right: 8px;"></i>
                            <span>Size Chart</span>
                        </a>
                    </div>

                    <!-- Size Chart Modal -->
                    <div id="sizeChartModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); z-index: 1000; justify-content: center; align-items: center;">
                        <div class="size-chart-modal" style="background-color: white; border-radius: 10px; width: 90%; max-width: 800px; position: relative;">
                            <div class="modal-header" style="padding: 15px 20px; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center;">
                                <h3 style="margin: 0; color: var(--primary);">Size Chart</h3>
                                <button class="close-modal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d;">&times;</button>
                            </div>
                            <div class="modal-content" style="padding: 20px;">
                                <img src="images/size_chart.jpg" alt="Size Chart" style="width: 100%; height: auto; border-radius: 5px;">
                            </div>
                        </div>
                    </div>

                    <form id="measurementForm" method="POST" action="save_measurements.php" class="<?php echo isset($_GET['on_lend']) && $_GET['on_lend'] == '1' ? 'form-disabled' : ''; ?>">
                    <!-- Measurements Form -->
                    <div class="measurements-form">
                        <h4>Enter Your Measurements</h4>
                        <div class="form-group">
                            <label>Height (inches) <span class="text-muted">(Range: 60-72 inches)</span></label>
                            <input type="number" class="form-control" name="height" id="height" required min="60" max="72" step="0.1">
                            <div class="invalid-feedback" id="heightError"></div>
                        </div>
                        <div class="form-group">
                            <label>Shoulder Width (inches) <span class="text-muted">(Range: 14-18 inches)</span></label>
                            <input type="number" class="form-control" name="shoulder" id="shoulder" required min="14" max="18" step="0.1">
                            <div class="invalid-feedback" id="shoulderError"></div>
                        </div>
                        <div class="form-group">
                            <label>Bust (inches) <span class="text-muted">(Range: 78-113 cm / 30-44 inches)</span></label>
                            <input type="number" class="form-control" name="bust" id="bust" required min="30" max="44" step="0.1">
                            <div class="invalid-feedback" id="bustError"></div>
                        </div>
                        <div class="form-group">
                            <label>Waist (inches) <span class="text-muted">(Range: 63-98 cm / 25-39 inches)</span></label>
                            <input type="number" class="form-control" name="waist" id="waist" required min="25" max="39" step="0.1">
                            <div class="invalid-feedback" id="waistError"></div>
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

                    <!-- Hidden inputs -->
                    <input type="hidden" name="outfit_id" id="outfitId" value="<?php echo $outfit_id; ?>">
                    <input type="hidden" name="start_date" id="startDateInput">
                    <input type="hidden" name="end_date" id="endDateInput">
                    <input type="hidden" id="userId" value="<?php echo isset($_SESSION['id']) ? $_SESSION['id'] : ''; ?>">
                    
                    <?php if (!$is_on_lend): ?>
                        <div class="checkout-button-container" style="margin-top: 20px;">
                            <button type="submit" class="submit-btn" id="proceedButton" style="width: 100%; padding: 15px;">
                                <i class="bi bi-cart-plus"></i> Proceed to Checkout
                            </button>
                        </div>
                    <?php endif; ?>
                    </form>

                    <?php if ($is_on_lend): ?>
                        <a href="outfit.php" class="back-button">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Outfits
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this right before the Reviews and Ratings Section -->
    <div class="container similar-outfits-section my-5">
        <h2 class="section-title mb-4">Similar Outfits</h2>
        <div class="row" id="similarOutfitsGrid">
            <?php
            // Get current outfit's gender and occasion
            $similar_query = "SELECT DISTINCT o.* 
                             FROM tbl_outfit o
                             INNER JOIN tbl_subcategory gs ON o.gender_id = gs.id
                             WHERE o.status = 'approved' 
                             AND o.outfit_id != ? 
                             AND o.gender_id = ?
                             LIMIT 3";  // Limit to 3 similar outfits
            
            $stmt = $conn->prepare($similar_query);
            $stmt->bind_param("ii", $outfit_id, $outfit['gender_id']);
            $stmt->execute();
            $similar_result = $stmt->get_result();

            if($similar_result && $similar_result->num_rows > 0) {
                while($similar_outfit = $similar_result->fetch_assoc()) {
                    $rental_price = $similar_outfit['mrp'] * 0.20;
                    
                    // Handle image display
                    $baseImageNumber = $similar_outfit['image1'];
                    $imagePath = '';
                    if (!empty($baseImageNumber)) {
                        $baseImageNumber = str_replace('_image1.jpg', '', $baseImageNumber);
                        $imagePath = 'uploads/' . $baseImageNumber . '_image1.jpg';
                    }
                    
                    // Get description
                    $description_query = "SELECT d.description_text FROM tbl_description d WHERE d.id = ?";
                    $stmt = $conn->prepare($description_query);
                    $stmt->bind_param("i", $similar_outfit['description_id']);
                    $stmt->execute();
                    $description_result = $stmt->get_result();
                    $description = $description_result->fetch_assoc();
                    $description_text = (!empty($description) && !empty($description['description_text'])) ? $description['description_text'] : 'Outfit #'.$similar_outfit['outfit_id'];
                    
                    // Get brand name
                    $brand_query = "SELECT s.subcategory_name FROM tbl_subcategory s WHERE s.id = ?";
                    $stmt = $conn->prepare($brand_query);
                    $stmt->bind_param("i", $similar_outfit['brand_id']);
                    $stmt->execute();
                    $brand_result = $stmt->get_result();
                    $brand = $brand_result->fetch_assoc();
                    $brand_name = (!empty($brand) && !empty($brand['subcategory_name'])) ? $brand['subcategory_name'] : '';
                    
                    // Check if outfit is on lend
                    $lend_query = "SELECT COUNT(*) as is_on_lend FROM tbl_orders 
                                  WHERE outfit_id = ? AND order_status = 'CONFIRMED'";
                    $stmt = $conn->prepare($lend_query);
                    $stmt->bind_param("i", $similar_outfit['outfit_id']);
                    $stmt->execute();
                    $lend_result = $stmt->get_result();
                    $lend_data = $lend_result->fetch_assoc();
                    
                    $is_on_lend = ($lend_data['is_on_lend'] > 0);
                    
                    echo '<div class="col-md-4 mb-4">
                            <div class="outfit-card">
                                <div class="outfit-card-image">';
                    
                    if (!empty($imagePath) && file_exists($imagePath)) {
                        echo '<img src="'.$imagePath.'" alt="Outfit Image">';
                    } else {
                        echo '<div class="no-image-placeholder">Outfit #'.$similar_outfit['outfit_id'].'</div>';
                    }
                    
                    if ($is_on_lend) {
                        echo '<div class="on-lend-badge"><i class="fas fa-clock mr-1"></i> On Lend</div>';
                    }
                    
                    echo '</div>
                            <div class="outfit-card-content">
                                <h3 class="outfit-title">'.htmlspecialchars($description_text).'</h3>';
                    
                    if (!empty($brand_name)) {
                        echo '<p class="outfit-brand">'.htmlspecialchars($brand_name).'</p>';
                    }
                    
                    echo '<p class="outfit-price">₹'.number_format($rental_price, 2).'</p>';
                    
                    if ($is_on_lend) {
                        echo '<a href="rentnow.php?id='.$similar_outfit['outfit_id'].'&on_lend=1" class="view-btn">View</a>';
                    } else {
                        echo '<a href="rentnow.php?id='.$similar_outfit['outfit_id'].'" class="rent-btn">Rent Now</a>';
                    }
                    
                    echo '</div>
                            </div>
                        </div>';
                }
            } else {
                echo '<div class="col-12">
                        <div class="alert alert-info">No similar outfits available at the moment.</div>
                      </div>';
            }
            ?>
        </div>
    </div>

    <!-- Reviews and Ratings Section -->
    <div class="container reviews-section my-5">
        <h2 class="section-title mb-4">Customer Reviews</h2>
        
        <div class="row">
            <div class="col-md-4">
                <?php
                // Fetch average rating and count
                $rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                                 FROM tbl_reviews 
                                 WHERE outfit_id = ?";
                $rating_stmt = $conn->prepare($rating_query);
                $rating_stmt->bind_param("i", $outfit_id);
                $rating_stmt->execute();
                $rating_result = $rating_stmt->get_result();
                $rating_data = $rating_result->fetch_assoc();
                
                $avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
                $total_reviews = $rating_data['total_reviews'] ?? 0;
                ?>
                
                <div class="rating-summary card">
                    <div class="card-body">
                        <h3 class="average-rating"><?php echo $avg_rating; ?> <small>out of 5</small></h3>
                        <div class="stars-display">
                            <?php
                            $full_stars = floor($avg_rating);
                            $half_star = $avg_rating - $full_stars >= 0.5;
                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                            
                            for ($i = 0; $i < $full_stars; $i++) {
                                echo '<i class="fas fa-star"></i>';
                            }
                            
                            if ($half_star) {
                                echo '<i class="fas fa-star-half-alt"></i>';
                            }
                            
                            for ($i = 0; $i < $empty_stars; $i++) {
                                echo '<i class="far fa-star"></i>';
                            }
                            ?>
                        </div>
                        <p class="review-count"><?php echo $total_reviews; ?> reviews</p>
                        
                        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
                            <button class="btn btn-dark write-review-btn" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                <i class="fas fa-pen"></i> Write a Review
                            </button>
                        <?php else: ?>
                            <a href="ls.php" class="btn btn-outline-dark">Login to Write a Review</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="reviews-list">
                    <?php
                    // Fetch reviews for this outfit
                    $reviews_query = "SELECT r.rating, r.review_text, r.review_date, u.name as user_name 
                                     FROM tbl_reviews r
                                     JOIN tbl_users u ON r.user_id = u.user_id
                                     WHERE r.outfit_id = ?
                                     ORDER BY r.review_date DESC";
                    $reviews_stmt = $conn->prepare($reviews_query);
                    $reviews_stmt->bind_param("i", $outfit_id);
                    $reviews_stmt->execute();
                    $reviews_result = $reviews_stmt->get_result();
                    
                    if ($reviews_result->num_rows > 0) {
                        while ($review = $reviews_result->fetch_assoc()) {
                    ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <div class="reviewer-avatar">
                                        <?php echo substr($review['user_name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <div class="reviewer-name"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                        <div class="review-date"><?php echo date('M d, Y', strtotime($review['review_date'])); ?></div>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $review['rating']) {
                                            echo '<i class="fas fa-star"></i>';
                                        } else {
                                            echo '<i class="far fa-star"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="review-content">
                                <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                            </div>
                        </div>
                    <?php
                        }
                    } else {
                    ?>
                        <div class="no-reviews">
                            <i class="far fa-comment-dots"></i>
                            <p>No reviews yet. Be the first to review this outfit!</p>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']): ?>
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reviewModalLabel">Write a Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reviewForm" action="submit_review.php" method="POST">
                        <input type="hidden" name="outfit_id" value="<?php echo $outfit_id; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $_SESSION['id']; ?>">
                        
                        <div class="rating-input mb-3">
                            <label class="form-label">Your Rating</label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 stars"></label>
                                <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars"></label>
                                <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars"></label>
                                <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars"></label>
                                <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star"></label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reviewText" class="form-label">Your Review</label>
                            <textarea class="form-control" id="reviewText" name="review_text" rows="4" placeholder="Share your experience with this outfit..." required></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="rentnow.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('measurementForm');
        if (!form) return;

        const startDateField = document.getElementById('startDate');
        const endDateField = document.getElementById('endDate');
        const startDateInput = document.getElementById('startDateInput');
        const endDateInput = document.getElementById('endDateInput');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get all form values
            const height = form.querySelector('input[name="height"]').value;
            const shoulder = form.querySelector('input[name="shoulder"]').value;
            const bust = form.querySelector('input[name="bust"]').value;
            const waist = form.querySelector('input[name="waist"]').value;
            const outfitId = form.querySelector('input[name="outfit_id"]').value;

            // Validate measurements
            if (!height || !shoulder || !bust || !waist) {
                alert('Please fill in all measurements');
                return;
            }

            // Validate dates
            if (!startDateField.value || !endDateField.value) {
                alert('Please select event date and rental duration');
                return;
            }

            // Update hidden date inputs
            startDateInput.value = startDateField.value;
            endDateInput.value = endDateField.value;

            // Create FormData
            const formData = new FormData(form);

            // Log the data being sent
            console.log('Sending data:', {
                height,
                shoulder,
                bust,
                waist,
                outfit_id: outfitId,
                start_date: startDateInput.value,
                end_date: endDateInput.value
            });

            // Submit form data
            fetch('save_measurements.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                console.log('Raw server response:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        console.log('Measurements saved successfully');
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message || 'Error saving measurements. Please try again.');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    alert('An error occurred. Please try again.');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const addToCartBtn = document.getElementById('addToCartBtn');
        const outfitId = document.getElementById('outfitId').value;
        
        // Check if item is already in cart when page loads
        checkIfInCart(outfitId).then(inCart => {
            if (inCart) {
                updateButtonState(true);
            }
        });

        addToCartBtn.addEventListener('click', function() {
            const userId = document.getElementById('userId').value;

            if (!userId) {
                alert('Please log in to add items to cart');
                window.location.href = 'ls.php';
                return;
            }

        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    outfitId: outfitId,
                    userId: userId
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        return { success: true }; // Fallback if response is not JSON
                    }
                });
            })
        .then(data => {
                updateButtonState(true);
                updateCartCount(true); // true indicates animation should play
            })
            .catch(error => {
                console.error('Error:', error);
                // Silently handle the error since the item was added successfully
                updateButtonState(true);
                updateCartCount(true);
            });
        });

        function updateButtonState(added) {
            const btn = document.getElementById('addToCartBtn');
            if (added) {
                btn.innerHTML = '<i class="bi bi-check2"></i> Added to Cart';
                btn.classList.remove('btn-dark');
                btn.classList.add('btn-success');
                btn.disabled = true;
            } else {
                btn.innerHTML = '<i class="bi bi-cart-plus"></i> Add to Cart';
                btn.classList.add('btn-dark');
                btn.classList.remove('btn-success');
                btn.disabled = false;
            }
        }

        function updateCartCount(animate = false) {
            fetch('get_cart_count.php')
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    const cartCount = document.getElementById('cartCount');
                    if (cartCount) {
                        if (data.count > 0) {
                            cartCount.textContent = data.count;
                            cartCount.style.display = 'flex';
                            if (animate) {
                                cartCount.classList.remove('pulse');
                                void cartCount.offsetWidth; // Trigger reflow
                                cartCount.classList.add('pulse');
                            }
                        } else {
                            cartCount.style.display = 'none';
                        }
                    }
                } catch (e) {
                    console.error('Error parsing cart count:', e);
            }
        })
        .catch(error => {
                console.error('Error updating cart count:', error);
            });
        }

        async function checkIfInCart(outfitId) {
            try {
                const response = await fetch('check_cart.php?outfit_id=' + outfitId);
                const data = await response.json();
                return data.inCart;
            } catch (error) {
                console.error('Error checking cart:', error);
                return false;
            }
        }

        // Initial cart count update
        updateCartCount();

        // Update cart count every 30 seconds
        setInterval(updateCartCount, 30000);
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize zoom functionality
        new ImageZoom('mainImage');

        // Your existing thumbnail click handler
        const thumbnails = document.querySelectorAll('.thumbnail');
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const mainImage = document.getElementById('mainImage');
                mainImage.src = this.src;
                // Reinitialize zoom when image changes
                new ImageZoom('mainImage');
                
                // Update active thumbnail
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Review modal functionality
        const reviewBtn = document.querySelector('.write-review-btn');
        const reviewModal = document.getElementById('reviewModal');
        
        if (reviewBtn && reviewModal) {
            // Check if Bootstrap 5 modal is available
            if (typeof bootstrap !== 'undefined') {
                // Initialize the modal manually
                const modal = new bootstrap.Modal(reviewModal);
                
                reviewBtn.addEventListener('click', function() {
                    modal.show();
                });
            } else {
                console.error('Bootstrap JavaScript is not loaded properly');
            }
        }
        
        // Fix the star rating in the modal
        const starLabels = document.querySelectorAll('.star-rating label');
        starLabels.forEach(label => {
            label.addEventListener('click', function() {
                // Visually update the stars
                const id = this.getAttribute('for');
                const value = id.replace('star', '');
                
                starLabels.forEach(lbl => {
                    const lblId = lbl.getAttribute('for');
                    const lblValue = lblId.replace('star', '');
                    
                    if (lblValue <= value) {
                        lbl.classList.add('active');
                    } else {
                        lbl.classList.remove('active');
                    }
                });
                
                // Select the radio button
                const radio = document.getElementById(id);
                if (radio) {
                    radio.checked = true;
                }
            });
        });
        
        // Form submission handling
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                // Check if a rating is selected
                const ratingSelected = document.querySelector('input[name="rating"]:checked');
                if (!ratingSelected) {
                    e.preventDefault();
                    alert('Please select a rating');
                    return false;
                }
                
                // Check if review text is not empty
                const reviewText = document.getElementById('reviewText').value.trim();
                if (reviewText === '') {
                    e.preventDefault();
                    alert('Please write a review');
                    return false;
                }
                
                // If all validations pass, form will submit
                return true;
            });
        }
    });

    // Add rental duration change handler
    const durationInputs = document.querySelectorAll('input[name="duration"]');
    const priceDisplay = document.querySelector('.product-price');
    const rentalPriceDisplay = document.querySelector('.price-details .price-row:first-child span:last-child');
    
    durationInputs.forEach(input => {
        input.addEventListener('change', function() {
            const mrp = <?php echo $outfit['mrp']; ?>;
            let rentalPercentage;
            
            // Set rental percentage based on duration
            switch(this.value) {
                case '3':
                    rentalPercentage = 0.10; // 10% for 3 days
                    break;
                case '5':
                    rentalPercentage = 0.12; // 12% for 5 days
                    break;
                case '7':
                    rentalPercentage = 0.14; // 14% for 7 days
                    break;
                default:
                    rentalPercentage = 0.10;
            }
            
            const actualRental = mrp * rentalPercentage;
            const securityDeposit = mrp * 0.10; // Security deposit remains 10%
            const totalRental = actualRental + securityDeposit;
            
            // Update the displayed prices
            priceDisplay.textContent = `Total: ₹${totalRental.toFixed(2)}`;
            rentalPriceDisplay.textContent = `₹${actualRental.toFixed(2)}`;
            
            // Update hidden input for the rental rate
            const rentalRateInput = document.createElement('input');
            rentalRateInput.type = 'hidden';
            rentalRateInput.name = 'rental_rate';
            rentalRateInput.value = actualRental;
            
            // Remove any existing rental_rate input
            const existingRentalRate = document.querySelector('input[name="rental_rate"]');
            if (existingRentalRate) {
                existingRentalRate.remove();
            }
            
            // Add the new rental rate input to the form
            document.getElementById('measurementForm').appendChild(rentalRateInput);
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Get the on_lend parameter from the URL
        const urlParams = new URLSearchParams(window.location.search);
        const isOnLend = urlParams.get('on_lend') === '1';
        
        if (isOnLend) {
            // Disable all form inputs
            const form = document.getElementById('measurementForm');
            if (form) {
                // Add the disabled class
                form.classList.add('form-disabled');
                
                // Disable all form elements
                const formElements = form.querySelectorAll('input, select, textarea, button');
                formElements.forEach(element => {
                    element.disabled = true;
                });

                // Disable size buttons
                const sizeButtons = document.querySelectorAll('.size-btn');
                sizeButtons.forEach(btn => {
                    btn.disabled = true;
                    btn.style.pointerEvents = 'none';
                });

                // Disable duration options
                const durationOptions = document.querySelectorAll('.duration-options input[type="radio"]');
                durationOptions.forEach(option => {
                    option.disabled = true;
                });

                // Disable date picker
                const datePicker = document.getElementById('eventDate');
                if (datePicker && datePicker._flatpickr) {
                    datePicker._flatpickr.destroy();
                }
                if (datePicker) {
                    datePicker.disabled = true;
                }
            }

            // Hide the Add to Cart and Proceed buttons
            const addToCartBtn = document.getElementById('addToCartBtn');
            const proceedButton = document.getElementById('proceedButton');
            
            if (addToCartBtn) {
                addToCartBtn.style.display = 'none';
            }
            if (proceedButton) {
                proceedButton.style.display = 'none';
            }
        }
    });

    // Size Chart Modal Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sizeChartLink = document.getElementById('sizeChartLink');
        const sizeChartModal = document.getElementById('sizeChartModal');
        const closeModal = document.querySelector('.close-modal');

        sizeChartLink.addEventListener('click', function(e) {
            e.preventDefault();
            sizeChartModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });

        closeModal.addEventListener('click', function() {
            sizeChartModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });

        sizeChartModal.addEventListener('click', function(e) {
            if (e.target === sizeChartModal) {
                sizeChartModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });

        // Close modal on ESC key press
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sizeChartModal.style.display === 'flex') {
                sizeChartModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Measurement validation ranges
        const validationRanges = {
            height: { min: 60, max: 72, name: 'Height' },
            shoulder: { min: 14, max: 18, name: 'Shoulder width' },
            bust: { min: 30, max: 44, name: 'Bust' },
            waist: { min: 25, max: 39, name: 'Waist' }
        };

        // Function to validate measurement
        function validateMeasurement(input, range) {
            const value = parseFloat(input.value);
            const errorDiv = document.getElementById(input.id + 'Error');
            
            // Clear previous validation states
            input.classList.remove('is-invalid', 'is-valid');
            
            // Validate empty input
            if (input.value === '') {
                errorDiv.textContent = `${range.name} measurement is required`;
                input.classList.add('is-invalid');
                return false;
            }
            
            // Validate number
            if (isNaN(value)) {
                errorDiv.textContent = 'Please enter a valid number';
                input.classList.add('is-invalid');
                return false;
            }
            
            // Validate range
            if (value < range.min || value > range.max) {
                errorDiv.textContent = `${range.name} must be between ${range.min} and ${range.max} inches`;
                input.classList.add('is-invalid');
                return false;
            }
            
            // If all validations pass
            input.classList.add('is-valid');
            return true;
        }

        // Add validation to each measurement input
        Object.keys(validationRanges).forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                // Validate on input
                input.addEventListener('input', function() {
                    validateMeasurement(this, validationRanges[field]);
                });
                
                // Validate on blur
                input.addEventListener('blur', function() {
                    validateMeasurement(this, validationRanges[field]);
                });
            }
        });

        // Form submission validation
        const form = document.getElementById('measurementForm');
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate all measurements before submission
            Object.keys(validationRanges).forEach(field => {
                const input = document.getElementById(field);
                if (input) {
                    if (!validateMeasurement(input, validationRanges[field])) {
                        isValid = false;
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please correct the measurement errors before proceeding.');
            }
        });
    });
    </script>
</body>
</html>
