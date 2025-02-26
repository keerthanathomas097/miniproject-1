<?php
session_start();
include 'connect.php';

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
        // Get user_id based on logged in user's email
        $email = $_POST['email'];
        $user_id = null;
        
        // First fetch user_id based on the email
        $stmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $user_id = $row['user_id'];
            
            // Now proceed with outfit insertion
            $size_id = $_POST['size_id'];
            $type_id = $_POST['type_id'];
            $brand_id = $_POST['brand_id'];
            $mrp = $_POST['mrp'];
            $purchase_year = $_POST['purchaseYear'];
            $city = $_POST['city'];
            $status = 'Pending';

            $stmt = $conn->prepare("INSERT INTO tbl_outfit
                                (user_id, size_id, type_id, brand_id, mrp, purchase_year, city, status, image1, image2, image3, proof_image)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param(
                "iiiidissssss",  // Changed parameter types to match the data types
                $user_id,
                $size_id,
                $type_id,
                $brand_id,
                $mrp,
                $purchase_year,
                $city,
                $status,
                $imagePaths[0],
                $imagePaths[1],
                $imagePaths[2],
                $proofImagePath
            );
            
            if ($stmt->execute()) {
                echo "Outfit listed successfully!";
            } else {
                echo "Database error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $errors[] = "No user found with this email address.";
        }
        $conn->close();
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
                    <a href="outfit.php" class="nav-link">RENT OUTFITS</a>
                    <a href="lending.php" class="nav-link active-link">EARN THROUGH US</a>
                    <a href="men.php" class="nav-link">MEN</a>
                    <a href="bridal.php" class="nav-link">BRIDAL</a>
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

            <form method="POST" enctype="multipart/form-data">
        <h4 class="section-title">Outfit Images</h4>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="image-preview">
                    <label class="form-label">Image 1</label>
                    <input type="file" class="form-control" name="image1" accept="image/*">
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
                $brand_query = "SELECT s.id, s.subcategory_name FROM tbl_subcategory s 
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
                    $size_query = "SELECT s.id, s.subcategory_name FROM tbl_subcategory s 
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
                <label class="form-label">Category</label>
                <select class="form-select" name="type_id" required>
                    <option value="">Select Category</option>
                    <?php
                    $type_query = "SELECT s.id, s.subcategory_name FROM tbl_subcategory s 
                                JOIN tbl_category c ON s.category_id = c.id 
                                WHERE c.category_name = 'Type'";
                    $type_result = mysqli_query($conn, $type_query);
                    while($type = mysqli_fetch_assoc($type_result)) {
                        echo "<option value='".$type['id']."'>".$type['subcategory_name']."</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Year of Purchase</label>
                <input type="number" class="form-control" name="purchaseYear" value="<?php echo htmlspecialchars($_POST['purchaseYear'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">MRP (₹)</label>
                <input type="number" class="form-control" name="mrp" value="<?php echo htmlspecialchars($_POST['mrp'] ?? ''); ?>">
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

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-submit">Submit Outfit Details</button>
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
</body>
</html>

