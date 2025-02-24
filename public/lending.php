<?php
session_start();
include 'connect.php';

$errors = [];
$success_message = '';

$categoryQuery = "SELECT id, category_name FROM tbl_subcategory ";
$categoryResult = $conn->query($categoryQuery);
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
    // Proceed only if there are no errors
    if (empty($errors)) {
        // Get user_id based on logged in user's email
        $email = $_POST['email']; // Get email from form
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
            $size = $_POST['size'];
            $category = $_POST['category'];
            $brand = $_POST['designer'];
            $mrp = $_POST['mrp'];
            $purchase_year = $_POST['purchaseYear'];
            $city = $_POST['city'];
            $status = 'Pending';
    
            $stmt = $conn->prepare("INSERT INTO tbl_outfit 
                                (user_id, size, category, brand, mrp, purchase_year, city, status, image1, image2, image3, proof_image) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
            $stmt->bind_param(
                "issssissssss",  // Changed parameter types to match the data
                $user_id,
                $size,
                $category,
                $brand,
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
    <style>
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
            color: #dc3545;
            margin-top: 20px;
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
                <input type="text" class="form-control" name="designer" value="<?php echo htmlspecialchars($_POST['designer'] ?? ''); ?>">
            </div>
            <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
</div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Size</label>
                    <select class="form-select" name="size">
                        <option value="">Select Size</option>
                        <option value="XS">XS</option>
                        <option value="S">S</option>
                        <option value="M">M</option>
                        <option value="L">L</option>
                        <option value="XL">XL</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                    <?php
                    if ($category_name->num_rows > 0) {
                        while($row = $category_name->fetch_assoc()) {
                            echo '<option value="' . $row["id"] . '">' . htmlspecialchars($row["subcategory_name"]) . '</option>';
                        }
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>