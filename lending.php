<?php
session_start();
include 'connect.php';
$termsContent = "1. LISTING OF THE PRODUCT

Preliminary approval:

The Owner shall fill the form provided at http://rentanattire.com/earn-through-us. The Company will receive a request from the Owner containing the images of Product and its general information, the company will approve or reject the Product after checking the same.
On approval of the Product, the Owner will receive an email from the company. Owner then shall, after receiving such communication from the Company have to submit the Product to the Company along with other details as may be required by the Company. The Company cannot be held liable for any damages arising to the Product while it is in transit to the Company's warehouse.
The Owner shall not submit any Product which has not been approved by the Company. The Company shall not be liable for the loss of such Products or any damages incurred in relation thereto.
"; 
$userName = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$isLoggedIn = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;

$isLender = isset($_SESSION['loggedin']) && $_SESSION['role'] === 'lender';

$errors = [];
$success_message = '';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $designer = trim($_POST['designer'] ?? '');
    $size = $_POST['size'] ?? '';
    $category = $_POST['category'] ?? '';
    $purchaseYear = trim($_POST['purchaseYear'] ?? '');
    $mrp = trim($_POST['mrp'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $hasBill = $_POST['hasBill'] ?? '';


    // Validation
    if (empty($designer)) $errors[] = "Designer/Brand is required";
    if (empty($size)) $errors[] = "Size is required";
    if (empty($category)) $errors[] = "Category is required";


    // Validate email
if (empty($_POST['email'])) {
    $errors[] = "Email is required";
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address";
}
   
    // Validate purchase year
    if (empty($purchaseYear)) {
        $errors[] = "Purchase year is required";
    } elseif (!preg_match("/^20[0-9]{2}$/", $purchaseYear) || $purchaseYear > date("Y")) {
        $errors[] = "Please enter a valid purchase year (2000-" . date("Y") . ")";
    }


    // Validate MRP
    if (empty($mrp)) {
        $errors[] = "MRP is required";
    } elseif (!preg_match("/^[0-9]+$/", $mrp)) {
        $errors[] = "MRP must be a valid number";
    }


    // Validate address and city
    if (empty($address)) $errors[] = "Pickup address is required";
    if (empty($city)) $errors[] = "City is required";
    if (empty($hasBill)) $errors[] = "Please specify if you have a bill";


    // Image validation
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 5 * 1024 * 1024; // 5MB


    for ($i = 1; $i <= 3; $i++) {
        if (!isset($_FILES["image$i"]) || $_FILES["image$i"]['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = "Image $i is required";
        } else {
            $file = $_FILES["image$i"];
            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = "Image $i must be JPG, JPEG or PNG";
            }
            if ($file['size'] > $max_size) {
                $errors[] = "Image $i must be less than 5MB";
            }
        }
    }


    // Validate proof of purchase image
    if (!isset($_FILES["proofImage"]) || $_FILES["proofImage"]['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "Proof of purchase image is required";
    } else {
        $file = $_FILES["proofImage"];
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = "Proof of purchase image must be JPG, JPEG or PNG";
        }
        if ($file['size'] > $max_size) {
            $errors[] = "Proof of purchase image must be less than 5MB";
        }
    }


    // If no errors, proceed
    if (empty($errors)) {
        $success_message = "Form validated successfully!";
    }


    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }


    $imagePaths = [];
    $errors = [];


    // Handle image uploads (up to 3 images)
    for ($i = 1; $i <= 3; $i++) {
        if (!empty($_FILES["image$i"]["name"])) {
            $file = $_FILES["image$i"];
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $uniqueFileName = time() . "_image$i." . $fileExtension;
            $targetFilePath = $uploadDir . $uniqueFileName;


            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                $imagePaths[] = $uniqueFileName;
            } else {
                $errors[] = "Failed to upload Image $i.";
                $imagePaths[] = null;
            }
        } else {
            $imagePaths[] = null;
        }
    }


    // Handle proof of purchase image upload
    $proofImagePath = null;
    if (!empty($_FILES["proofImage"]["name"])) {
        $file = $_FILES["proofImage"];
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueFileName = time() . "_proof." . $fileExtension;
        $targetFilePath = $uploadDir . $uniqueFileName;


        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            $proofImagePath = $uniqueFileName;
        } else {
            $errors[] = "Failed to upload proof of purchase image.";
        }
    }


    // Proceed only if there are no errors
    if (empty($errors)) {
        // Check if database connection exists and is valid
        if (!$conn || $conn->connect_error) {
            throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "Connection not established"));
        } else {
            try {
                // Get user_id based on email
        $email = $_POST['email'];
        $stmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE email = ?");
                if (!$stmt) {
                    throw new Exception("Error preparing user query: " . $conn->error);
                }
                
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_id = $row['user_id'];
                    $stmt->close();
                    
                    // Insert outfit data with correct column names
                    $insert_query = "INSERT INTO tbl_outfit (
                        email,
                        brand_id,
                        size_id,
                        gender_id,
                        type_id,
                        mrp,
                        price_id,
                        image1,
                        image2,
                        image3,
                        status,
                        purchase_year,
                        city,
                        address,
                        proof_image
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($insert_query);
                    if (!$stmt) {
                        throw new Exception("Error preparing insert query: " . $conn->error);
                    }
                    
                    $status = 'pending';
                    $mrp = floatval($_POST['mrp']);
                    $purchase_year = intval($_POST['purchaseYear']);
                    
                    // Add price_id selection based on MRP
                    $price_id = null;
                    if ($mrp <= 50000) {
                        $price_id = 1; // ID for price range <= 50000
                    } elseif ($mrp <= 100000) {
                        $price_id = 2; // ID for price range 50001-100000
                    } else {
                        $price_id = 3; // ID for price range > 100000
                    }

            $stmt->bind_param(
                        "siiiiidiissssss",
                        $_POST['email'],
                        $_POST['brand_id'],
                        $_POST['size_id'],
                        $_POST['gender_id'],
                        $_POST['type_id'],
                $mrp,
                        $price_id,
                $imagePaths[0],
                $imagePaths[1],
                $imagePaths[2],
                        $status,
                        $purchase_year,
                        $_POST['city'],
                        $_POST['address'],
                $proofImagePath
            );
            
            if ($stmt->execute()) {
                        $success_message = "Outfit listed successfully!";
                        $_POST = array();
            } else {
                        throw new Exception("Error inserting data: " . $stmt->error);
                    }
                } else {
                    throw new Exception("No user found with this email address.");
            }

            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            } finally {
                if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
                if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Your Outfit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <?php if (!$isLender): ?>
        <link rel="stylesheet" href="styles/navbar.css">
    <?php endif; ?>
    <style>
        /* Add sidebar styles if user is lender */
        <?php if ($isLender): ?>
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: 250px;
            background-color: rgb(91, 9, 9);
            color: white;
            padding: 20px;
            overflow-y: auto;
        }

        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            margin-top: 30px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 5px;
            margin-bottom: 5px;
            color: white;
            text-decoration: none;
        }

        .menu-item:hover {
            background-color: rgb(147, 42, 42);
            text-decoration: none;
            color: white;
        }

        .menu-item i {
            margin-right: 10px;
            width: 20px;
        }

        /* Adjust main content margin when sidebar is present */
        .main-content {
            margin-left: 250px;
        }
        <?php endif; ?>

        /* Original styles remain unchanged */
        body {
            background-color: #f8f9fa;
            color: #333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .form-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0,0,0,0.1);
        }
        .form-title {
            color: #800020;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
        }
        .form-label {
            font-weight: 500;
            color: #444;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #800020;
            box-shadow: 0 0 0 0.2rem rgba(128,0,32,0.25);
        }
        .btn-submit {
            background-color: #800020;
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 1.1rem;
        }
        .btn-submit:hover {
            background-color: #600018;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(128,0,32,0.3);
        }
        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }
        .success-message {
            color: #198754;
            margin-top: 20px;
        }
        .image-preview {
            border: 2px dashed #ddd;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 10px;
        }
        .section-title {
            color: #800020;
            margin: 30px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s, box-shadow 0.3s;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .card-body {
            padding: 2rem;
        }
        .card-title {
            color: #800020;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .card-text {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .user-name {
    color: #800020 !important;
    font-weight: 500;
    padding: 8px 15px;
    margin-right: 10px;
    border-radius: 4px;
    background-color: rgba(128, 0, 32, 0.1);
}

.nav-link.user-name:hover {
    background-color: rgba(128, 0, 32, 0.15);
    cursor: default;
}
    </style>
</head>
<body>
    <?php if ($isLender): ?>
    <!-- Sidebar for lender -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Fashion Share</h2>
            <p>Lender Dashboard</p>
        </div>
        <div class="sidebar-menu">
            <a href="lender_dashboard.php" class="menu-item">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="lending.php" class="menu-item" style="background-color: rgb(147, 42, 42);">
                <i class="fas fa-plus-circle"></i> Lend Outfit
            </a>
            <div class="menu-item">
                <i class="fas fa-tshirt"></i> My Outfits
            </div>
            <div class="menu-item">
                <i class="fas fa-exchange-alt"></i> Rentals
            </div>
            <div class="menu-item">
                <i class="fas fa-money-bill-wave"></i> Earnings
            </div>
            <a href="lender_profile.php" class="menu-item">
                <i class="fas fa-user"></i> Profile
            </a>
            <div class="menu-item">
                <i class="fas fa-cog"></i> Settings
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Regular navbar for customers -->
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
    
    <?php if ($isLoggedIn): ?>
        <span class="nav-link user-name">Welcome, <?php echo htmlspecialchars($userName); ?></span>
    <?php else: ?>
        <a href="ls.php?showModal=true" class="nav-link">SIGN UP</a>
    <?php endif; ?>
    
    <div class="nav-icons">
        <a href="cart.php" class="icon-link">
            <i class="bi bi-bag"></i>
        </a>
        <?php if ($isLoggedIn): ?>
            <div class="dropdown">
                <a class="icon-link" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person"></i>
                </a>
                <ul class="dropdown-menu" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        <?php endif; ?>
        <a href="index.php" class="icon-link">
            <i class="bi bi-house"></i>
        </a>
    </div>
</div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <div class="<?php echo $isLender ? 'main-content' : 'container'; ?>">
        <!-- Original cards section remains unchanged -->
        <div class="container mt-5">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-money-bill-wave fa-3x mb-3" style="color: #800020;"></i>
                            <h5 class="card-title">Earn Money</h5>
                            <p class="card-text">List your outfit and earn money by lending it to others.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-tshirt fa-3x mb-3" style="color: #800020;"></i>
                            <h5 class="card-title">Share Your Style</h5>
                            <p class="card-text">Let others enjoy your designer outfits when you're not using them.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-shield-alt fa-3x mb-3" style="color: #800020;"></i>
                            <h5 class="card-title">Safe & Secure</h5>
                            <p class="card-text">Our secure platform ensures safe transactions and outfit protection.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="container form-container">
            <h2 class="form-title">List Your Outfit</h2>
        
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>


            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="outfitForm" novalidate>
        <h4 class="section-title">Outfit Images</h4>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="image-preview">
                    <label class="form-label">Image 1 *</label>
                    <input type="file" class="form-control" name="image1" id="image1" accept="image/*">
                    <span class="error-message" id="image1Error"></span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="image-preview">
                    <label class="form-label">Image 2</label>
                    <input type="file" class="form-control" name="image2" accept="image/*">
                </div>
            </div>
            <div class="col-md-4">
                <div class="image-preview">
                    <label class="form-label">Image 3</label>
                    <input type="file" class="form-control" name="image3" accept="image/*">
                </div>
            </div>
        </div>

        <h4 class="section-title">Outfit Details</h4>
        <div class="mb-3">
            <label class="form-label">Designer/Brand</label>
            <select class="form-control" name="brand_id" required>
                <option value="">Select Brand</option>
                <?php
                $brand_query = "SELECT s.id, s.subcategory_name 
                                FROM tbl_subcategory s 
                            JOIN tbl_category c ON s.category_id = c.id 
                            WHERE c.category_name = 'Brand'";
                $brand_result = mysqli_query($conn, $brand_query);
                while($brand = mysqli_fetch_assoc($brand_result)) {
                    echo "<option value='".$brand['id']."'>".$brand['subcategory_name']."</option>";
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Size</label>
                <select class="form-select" name="size_id" required>
                    <option value="">Select Size</option>
                    <?php
                    $size_query = "SELECT s.id, s.subcategory_name 
                                   FROM tbl_subcategory s 
                                JOIN tbl_category c ON s.category_id = c.id 
                                WHERE c.category_name = 'Size'";
                    $size_result = mysqli_query($conn, $size_query);
                    while($size = mysqli_fetch_assoc($size_result)) {
                        echo "<option value='".$size['id']."'>".$size['subcategory_name']."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Gender</label>
                <select class="form-select" name="gender_id" required>
                    <option value="">Select Gender</option>
                    <?php
                    $gender_query = "SELECT s.id, s.subcategory_name 
                                    FROM tbl_subcategory s 
                                JOIN tbl_category c ON s.category_id = c.id 
                                    WHERE c.category_name = 'Gender'";
                    $gender_result = mysqli_query($conn, $gender_query);
                    while($gender = mysqli_fetch_assoc($gender_result)) {
                        echo "<option value='".$gender['id']."'>".$gender['subcategory_name']."</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Year of Purchase *</label>
                <input type="number" class="form-control" id="purchaseYear" name="purchaseYear" 
                       min="2000" max="2024" value="<?php echo htmlspecialchars($_POST['purchaseYear'] ?? ''); ?>">
                <span class="error-message" id="purchaseYearError"></span>
            </div>
            <div class="col-md-6">
                <label class="form-label">MRP (₹) *</label>
                <input type="number" class="form-control" id="mrp" name="mrp" 
                       min="30000" value="<?php echo htmlspecialchars($_POST['mrp'] ?? ''); ?>">
                <span class="error-message" id="mrpError"></span>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Proof of Purchase</label>
            <div class="image-preview">
                <input type="file" class="form-control" name="proofImage" accept="image/*">
                <small class="text-muted mt-2 d-block">Upload bill, QR code, packaging, or self declaration</small>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Pickup Address</label>
            <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label">City</label>
                <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Do you have a bill?</label>
                <select class="form-select" name="hasBill">
                    <option value="">Select</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Type</label>
            <select class="form-select" name="type_id" required>
                <option value="">Select Type</option>
                <?php
                $type_query = "SELECT s.id, s.subcategory_name 
                               FROM tbl_subcategory s 
                               JOIN tbl_category c ON s.category_id = c.id 
                               WHERE c.category_name = 'Type'";
                $type_result = mysqli_query($conn, $type_query);
                while($type = mysqli_fetch_assoc($type_result)) {
                    echo "<option value='".$type['id']."'>".$type['subcategory_name']."</option>";
                }
                ?>
            </select>
        </div>

<div class="mb-4">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="termsCheckbox" required>
        <label class="form-check-label" for="termsCheckbox">
            I agree to the <a href="#" class="terms-link">Terms and Conditions</a>
        </label>
        <div class="invalid-feedback">
            You must agree to the terms and conditions
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <button type="submit" class="btn btn-submit" disabled>Submit Outfit Details</button>
</div>
</form>
        
      
            
            
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const designerInput = document.querySelector('input[name="designer"]');
        const sizeInput = document.querySelector('select[name="size"]');
        const categoryInput = document.querySelector('select[name="category"]');
        const purchaseYearInput = document.querySelector('input[name="purchaseYear"]');
        const mrpInput = document.querySelector('input[name="mrp"]');
        const addressInput = document.querySelector('input[name="address"]');
        const cityInput = document.querySelector('input[name="city"]');
        const emailInput = document.querySelector('input[name="email"]');
        const hasBillInput = document.querySelector('select[name="hasBill"]');

        const errorMessages = {
            designer: document.createElement('div'),
            size: document.createElement('div'),
            category: document.createElement('div'),
            purchaseYear: document.createElement('div'),
            mrp: document.createElement('div'),
            address: document.createElement('div'),
            city: document.createElement('div'),
            email: document.createElement('div'),
            hasBill: document.createElement('div'),
        };

        for (const key in errorMessages) {
            errorMessages[key].className = 'error-message text-danger';
            errorMessages[key].style.display = 'none';
            document.querySelector(`input[name="${key}"]`).parentNode.appendChild(errorMessages[key]);
        }

        // Live validation for each field
        designerInput.addEventListener('input', function() {
            if (designerInput.value.trim() === '') {
                errorMessages.designer.textContent = 'Designer/Brand is required.';
                errorMessages.designer.style.display = 'block';
            } else {
                errorMessages.designer.style.display = 'none';
            }
        });

        sizeInput.addEventListener('change', function() {
            if (sizeInput.value === '') {
                errorMessages.size.textContent = 'Size is required.';
                errorMessages.size.style.display = 'block';
            } else {
                errorMessages.size.style.display = 'none';
            }
        });

        categoryInput.addEventListener('change', function() {
            if (categoryInput.value === '') {
                errorMessages.category.textContent = 'Category is required.';
                errorMessages.category.style.display = 'block';
            } else {
                errorMessages.category.style.display = 'none';
            }
        });

        purchaseYearInput.addEventListener('input', function() {
            const yearPattern = /^20[0-9]{2}$/;
            if (purchaseYearInput.value.trim() === '') {
                errorMessages.purchaseYear.textContent = 'Purchase year is required.';
                errorMessages.purchaseYear.style.display = 'block';
            } else if (!yearPattern.test(purchaseYearInput.value) || purchaseYearInput.value > new Date().getFullYear()) {
                errorMessages.purchaseYear.textContent = 'Please enter a valid purchase year (2000-' + new Date().getFullYear() + ').';
                errorMessages.purchaseYear.style.display = 'block';
            } else {
                errorMessages.purchaseYear.style.display = 'none';
            }
        });

        mrpInput.addEventListener('input', function() {
            if (mrpInput.value.trim() === '') {
                errorMessages.mrp.textContent = 'MRP is required.';
                errorMessages.mrp.style.display = 'block';
            } else if (!/^[0-9]+$/.test(mrpInput.value)) {
                errorMessages.mrp.textContent = 'MRP must be a valid number.';
                errorMessages.mrp.style.display = 'block';
            } else {
                errorMessages.mrp.style.display = 'none';
            }
        });

        addressInput.addEventListener('input', function() {
            if (addressInput.value.trim() === '') {
                errorMessages.address.textContent = 'Pickup address is required.';
                errorMessages.address.style.display = 'block';
            } else {
                errorMessages.address.style.display = 'none';
            }
        });

        cityInput.addEventListener('input', function() {
            if (cityInput.value.trim() === '') {
                errorMessages.city.textContent = 'City is required.';
                errorMessages.city.style.display = 'block';
            } else {
                errorMessages.city.style.display = 'none';
            }
        });

        emailInput.addEventListener('input', function() {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailInput.value.trim() === '') {
                errorMessages.email.textContent = 'Email is required.';
                errorMessages.email.style.display = 'block';
            } else if (!emailPattern.test(emailInput.value)) {
                errorMessages.email.textContent = 'Please enter a valid email address.';
                errorMessages.email.style.display = 'block';
            } else {
                errorMessages.email.style.display = 'none';
            }
        });

        hasBillInput.addEventListener('change', function() {
            if (hasBillInput.value === '') {
                errorMessages.hasBill.textContent = 'Please specify if you have a bill.';
                errorMessages.hasBill.style.display = 'block';
            } else {
                errorMessages.hasBill.style.display = 'none';
            }
        });
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('outfitForm');
        const submitBtn = document.getElementById('submitBtn');
        
        // Validation rules
        const validationRules = {
            image1: {
                validate: (input) => {
                    if (!input.files.length) return 'Image 1 is required';
                    const file = input.files[0];
                    if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                        return 'Please upload a valid image file (JPG, JPEG, PNG)';
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        return 'Image must be less than 5MB';
                    }
                    return '';
                }
            },
            brand_id: {
                validate: (input) => input.value ? '' : 'Please select a brand'
            },
            email: {
                validate: (input) => {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!input.value) return 'Email is required';
                    if (!emailRegex.test(input.value)) return 'Please enter a valid email address';
                    return '';
                }
            },
            size_id: {
                validate: (input) => input.value ? '' : 'Please select a size'
            },
            gender_id: {
                validate: (input) => input.value ? '' : 'Please select a category'
            },
            purchaseYear: {
                validate: (input) => {
                    const year = parseInt(input.value);
                    const currentYear = new Date().getFullYear();
                    if (!input.value) return 'Purchase year is required';
                    if (year < 2000 || year > currentYear) {
                        return `Year must be between 2000 and ${currentYear}`;
                    }
                    return '';
                }
            },
            mrp: {
                validate: (input) => {
                    if (!input.value) return 'MRP is required';
                    if (!/^\d+$/.test(input.value)) return 'Please enter a valid number';
                    if (parseInt(input.value) <= 0) return 'MRP must be greater than 0';
                    return '';
                }
            },
            address: {
                validate: (input) => {
                    if (!input.value.trim()) return 'Address is required';
                    if (input.value.trim().length < 10) return 'Please enter a complete address';
                    return '';
                }
            },
            city: {
                validate: (input) => {
                    if (!input.value.trim()) return 'City is required';
                    if (input.value.trim().length < 3) return 'Please enter a valid city name';
                    return '';
                }
            },
            hasBill: {
                validate: (input) => input.value ? '' : 'Please specify if you have a bill'
            }
        };

        // Function to validate a single field
        function validateField(input) {
            const rule = validationRules[input.id];
            if (!rule) return true;

            const errorElement = document.getElementById(`${input.id}Error`);
            if (!errorElement) return true;

            const errorMessage = rule.validate(input);
            errorElement.textContent = errorMessage;
            errorElement.style.display = errorMessage ? 'block' : 'none';

            return !errorMessage;
        }

        // Add validation listeners to all form fields
        Object.keys(validationRules).forEach(fieldId => {
            const input = document.getElementById(fieldId);
            if (input) {
                input.addEventListener('input', () => {
                    validateField(input);
                    checkFormValidity();
                });
                input.addEventListener('change', () => {
                    validateField(input);
                    checkFormValidity();
                });
            }
        });

        // Check if all fields are valid
        function checkFormValidity() {
            const isValid = Object.keys(validationRules).every(fieldId => {
                const input = document.getElementById(fieldId);
                return input && validateField(input);
            });
            
            submitBtn.disabled = !isValid;
            return isValid;
        }

        // Form submission handler
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (checkFormValidity()) {
                // If using AJAX:
                const formData = new FormData(form);
                fetch('lending.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.includes('success')) {
                        alert('Outfit listed successfully!');
                        form.reset();
                    } else {
                        alert('There was an error. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        });
    });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all form elements
    const form = document.getElementById('outfitForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    const submitButton = form.querySelector('button[type="submit"]');

    // Validation rules
    const validationRules = {
        brand_id: {
            required: true,
            message: 'Please select a brand'
        },
        email: {
            required: true,
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            message: 'Please enter a valid email address'
        },
        size_id: {
            required: true,
            message: 'Please select a size'
        },
        gender_id: {
            required: true,
            message: 'Please select a category'
        },
        purchaseYear: {
            required: true,
            validate: (value) => {
                const year = parseInt(value);
                if (isNaN(year)) return 'Please enter a valid year';
                if (year < 2000) return 'Year must be 2000 or later';
                if (year > 2024) return 'Year cannot be greater than 2024';
                return '';
            }
        },
        mrp: {
            required: true,
            validate: (value) => {
                const price = parseInt(value);
                if (isNaN(price)) return 'Please enter a valid price';
                if (price < 30000) return 'MRP must be at least ₹30,000';
                if (price > 1000000) return 'MRP seems too high, please verify';
                return '';
            }
        },
        address: {
            required: true,
            minLength: 10,
            message: 'Please enter a complete address (minimum 10 characters)'
        },
        city: {
            required: true,
            minLength: 3,
            message: 'Please enter a valid city name'
        },
        hasBill: {
            required: true,
            message: 'Please specify if you have a bill'
        }
    };

    // Function to validate a single field
    function validateField(input) {
        const fieldName = input.id || input.name;
        const errorSpan = document.getElementById(`${fieldName}Error`) || 
                         input.parentElement.querySelector('.error-message');
        
        if (!errorSpan) return true;

        const rule = validationRules[fieldName];
        if (!rule) return true;

        let errorMessage = '';

        // Check if empty when required
        if (rule.required && !input.value.trim()) {
            errorMessage = `This field is required`;
        }
        // Check custom validation if exists
        else if (rule.validate && input.value.trim()) {
            errorMessage = rule.validate(input.value.trim());
        }
        // Check pattern if exists
        else if (rule.pattern && input.value.trim() && !rule.pattern.test(input.value.trim())) {
            errorMessage = rule.message;
        }
        // Check minLength if exists
        else if (rule.minLength && input.value.trim().length < rule.minLength) {
            errorMessage = rule.message;
        }

        // Show/hide error message
        errorSpan.textContent = errorMessage;
        errorSpan.style.display = errorMessage ? 'block' : 'none';

        // Add/remove invalid class
        if (errorMessage) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
        } else {
            input.classList.add('is-valid');
            input.classList.remove('is-invalid');
        }

        return !errorMessage;
    }

    // Add live validation to all inputs
    inputs.forEach(input => {
        ['input', 'change', 'blur'].forEach(eventType => {
            input.addEventListener(eventType, function() {
                validateField(this);
                checkFormValidity();
            });
        });
    });

    // Check entire form validity
    function checkFormValidity() {
        let isValid = true;
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });
        submitButton.disabled = !isValid;
        return isValid;
    }

    // Form submission handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (checkFormValidity()) {
            // Proceed with form submission
            this.submit();
        } else {
            // Show all error messages
            inputs.forEach(input => validateField(input));
            
            // Scroll to first error
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
});
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Combine all your event listeners and validation logic here
    const form = document.getElementById('outfitForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    const submitButton = form.querySelector('button[type="submit"]');
    const termsLink = document.querySelector('.terms-link');
    const termsCheckbox = document.getElementById('termsCheckbox');

    // Terms and conditions handlers
    termsLink.addEventListener('click', function(e) {
        e.preventDefault();
        const termsModal = new bootstrap.Modal(document.getElementById('termsModal'));
        termsModal.show();
    });

    termsCheckbox.addEventListener('change', function() {
        checkFormValidity();
    });

    // Your existing validation code...
    // ... rest of your validation logic ...
});
</script>

<style>
.error-message {
    color: #dc3545;
    font-size: 0.875em;
    margin-top: 0.25rem;
    display: none;
}

.form-control.is-invalid,
.form-select.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-valid,
.form-select.is-valid {
    border-color: #198754;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}
.terms-link {
    color: #800020;
    text-decoration: none;
}

.terms-link:hover {
    text-decoration: underline;
}

.terms-content {
    line-height: 1.6;
    color: #333;
}

.modal-dialog-scrollable {
    max-height: 80vh;
}

.form-check-input:checked {
    background-color: #800020;
    border-color: #800020;
}
</style>
<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Your terms and conditions content -->
                <div class="terms-content">
                    <?php echo nl2br(htmlspecialchars($termsContent)); // Your provided content ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <!-- ... modal content ... -->
    
 
</div> 

</body>
</html>

