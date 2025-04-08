<?php
session_start();
include 'connect.php';

// Check if user is logged in and has admin role
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'admin') {
//     header("Location: outfit_management.php");
//     exit();
// }

// Add at the top of the file after session_start()
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle outfit publishing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['publish_outfit'])) {
    $outfit_id = $_POST['outfit_id'];
    $success = true;
    $conn->begin_transaction();

    try {
        // First, insert the description
        $desc_query = "INSERT INTO tbl_description (description_text) VALUES (?)";
        $desc_stmt = $conn->prepare($desc_query);
        if (!$desc_stmt) {
            throw new Exception("Failed to prepare description statement: " . $conn->error);
        }
        
        $desc_stmt->bind_param("s", $_POST['description']);
        if (!$desc_stmt->execute()) {
            throw new Exception("Failed to insert description: " . $desc_stmt->error);
        }
        $description_id = $conn->insert_id;
        $desc_stmt->close();

        // Then, update the outfit
        $update_query = "UPDATE tbl_outfit SET description_id = ?, price_id = ? WHERE outfit_id = ?";
        $update_stmt = $conn->prepare($update_query);
        if (!$update_stmt) {
            throw new Exception("Failed to prepare update statement: " . $conn->error);
        }
        
        $update_stmt->bind_param("iii", $description_id, $_POST['price_range'], $outfit_id);
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update outfit: " . $update_stmt->error);
        }
        $update_stmt->close();

        // Finally, handle occasions if any are selected
        if (!empty($_POST['occasion'])) {
            // First, delete existing occasions for this outfit
            $delete_query = "DELETE FROM tbl_outfit_occasion WHERE outfit_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            if (!$delete_stmt) {
                throw new Exception("Failed to prepare delete statement: " . $conn->error);
            }
            
            $delete_stmt->bind_param("i", $outfit_id);
            if (!$delete_stmt->execute()) {
                throw new Exception("Failed to delete existing occasions: " . $delete_stmt->error);
            }
            $delete_stmt->close();

            // Then insert new occasions
            $occasion_query = "INSERT INTO tbl_outfit_occasion (outfit_id, occasion_id) VALUES (?, ?)";
            $occasion_stmt = $conn->prepare($occasion_query);
            if (!$occasion_stmt) {
                throw new Exception("Failed to prepare occasion statement: " . $conn->error);
            }

            foreach ($_POST['occasion'] as $occasion_id) {
                $occasion_stmt->bind_param("ii", $outfit_id, $occasion_id);
                if (!$occasion_stmt->execute()) {
                    throw new Exception("Failed to insert occasion: " . $occasion_stmt->error);
                }
            }
            $occasion_stmt->close();
        }
        
        // If we got here, everything worked
        $conn->commit();
        $_SESSION['message'] = "Outfit details updated successfully!";
        
    } catch (Exception $e) {
        // Something went wrong
        $conn->rollback();
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $success = false;
    }
    
    // Redirect regardless of success/failure
    header("Location: outfit_management.php");
    exit();
}

// Update your query to include description if available
$query = "SELECT 
    o.outfit_id,
    o.email,
    o.brand_id,
    o.size_id,
    o.gender_id,
    o.type_id,
    o.mrp,
    o.price_id,
    o.image1,
    o.image2,
    o.image3,
    o.status,
    o.purchase_year,
    o.city,
    o.address,
    o.description_id,
    d.description_text,
    u.name as user_name,
    u.email as user_email
          FROM tbl_outfit o 
LEFT JOIN tbl_users u ON o.email = u.email 
LEFT JOIN tbl_description d ON o.description_id = d.id
WHERE o.status = 'approved' 
          ORDER BY o.outfit_id DESC";

$result = $conn->query($query);
if (!$result) {
    die("Query failed: " . $conn->error);
}

// For gender query
$gender_query = "SELECT s.id, s.subcategory_name FROM tbl_subcategory s 
                JOIN tbl_category c ON s.category_id = c.id 
                WHERE c.category_name = ?";
$stmt = $conn->prepare($gender_query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$category_name = 'Gender';
$stmt->bind_param("s", $category_name);
$stmt->execute();
$gender_result = $stmt->get_result();

// For occasion query
$occasion_query = "SELECT s.id, s.subcategory_name 
                  FROM tbl_subcategory s 
                  JOIN tbl_category c ON s.category_id = c.id 
                  WHERE c.category_name = ?";
$stmt = $conn->prepare($occasion_query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$category_name = 'Occasion';
$stmt->bind_param("s", $category_name);
$stmt->execute();
$occasion_result = $stmt->get_result();

// For price query
$price_query = "SELECT s.id, s.subcategory_name 
               FROM tbl_subcategory s 
               JOIN tbl_category c ON s.category_id = c.id 
               WHERE c.category_name = ?";
$stmt = $conn->prepare($price_query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$category_name = 'Price';
$stmt->bind_param("s", $category_name);
$stmt->execute();
$price_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outfit Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { 
            --sidebar-width: 250px; 
        }
        
        /* Sidebar styles remain unchanged */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: rgb(91, 9, 9);
            color: white;
            padding-top: 20px;
        }
        
        /* Update main content and card styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            width: calc(100% - var(--sidebar-width));
        }

        .outfit-card {
            height: 100%;
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background: white;
        }

        .outfit-image {
            height: 250px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border-bottom: 1px solid #eee;
        }

        .outfit-image.no-image {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .no-image-placeholder {
            color: #6c757d;
            font-size: 16px;
            text-align: center;
        }

        .card-body {
            padding: 20px;
        }

        .row.outfit-grid {
            margin: 0 -15px;
            display: flex;
            flex-wrap: wrap;
        }

        .outfit-grid .col-md-4 {
            padding: 15px;
            width: 33.333%;
            flex: 0 0 33.333%;
        }

        @media (max-width: 1200px) {
            .outfit-grid .col-md-4 {
                width: 50%;
                flex: 0 0 50%;
            }
        }

        @media (max-width: 768px) {
            .outfit-grid .col-md-4 {
                width: 100%;
                flex: 0 0 100%;
            }
        }

        .sidebar-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: 0.3s;
        }
        .sidebar-link:hover { 
            background: rgb(147, 42, 42); 
            color: #ecf0f1; 
        }
        .publish-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
        }
        .occasion-checks {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .occasion-item {
            flex: 1 0 45%;
        }
        .message-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        select[multiple] {
            height: 150px;
            padding: 10px;
        }
        select[multiple] option {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-success:hover {
            background-color: #218838;
            opacity: 0.9;
        }
        .image-preview {
            display: inline-block;
            position: relative;
            margin: 5px;
        }

        .image-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }

        .delete-image {
            position: absolute;
            top: -10px;
            right: -10px;
            border-radius: 50%;
            padding: 0 6px;
        }

        .current-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #000;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .btn-secondary {
            opacity: 0.7;
        }
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-input {
            padding-left: 40px; /* Make room for the icon */
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        /* Style for no results message */
        .no-results {
            width: 100%;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: center;
            margin-top: 20px;
        }

        /* Exact sidebar styling */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 240px;
            background-color: #932A2A;
            color: white;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }
        
        /* Brand title styling */
        .brand-container {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
        }
        
        .brand-name {
            font-weight: 600;
            font-size: 22px;
            margin: 0;
            padding: 0;
            letter-spacing: 0.5px;
            color: white;
        }
        
        .brand-subtitle {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 3px;
            font-weight: 300;
        }
        
        /* Section headers */
        .sidebar-section {
            margin-top: 20px;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        .sidebar-section-header {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.4);
            font-weight: 500;
            margin-bottom: 10px;
            padding-left: 5px;
        }
        
        /* Navigation links */
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-nav-item {
            margin-bottom: 2px;
        }
        
        .sidebar-nav-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.75);
            padding: 10px 15px;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 400;
        }
        
        .sidebar-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar-nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 500;
        }
        
        .sidebar-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        
        /* Footer */
        .sidebar-footer {
            position: absolute;
            bottom: 15px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            padding: 10px 0;
        }
        
        /* Main content area */
        .main-content {
            margin-left: 240px;
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Add Sidebar -->
    <div class="sidebar">
        <div class="brand-container">
            <h1 class="brand-name">Clover Outfit Rentals</h1>
            <div class="brand-subtitle">Admin Dashboard</div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-header">DASHBOARD</div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="admin_dashboard.php" class="sidebar-nav-link">
                        <i class="fas fa-home sidebar-icon"></i>
                        Dashboard
                    </a>
                </li>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-header">MANAGEMENT</div>
                <ul class="sidebar-nav">
                    <li class="sidebar-nav-item">
                        <a href="users.php" class="sidebar-nav-link">
                            <i class="fas fa-users sidebar-icon"></i>
                            Users
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="outfit_management.php" class="sidebar-nav-link active">
                            <i class="fas fa-tshirt sidebar-icon"></i>
                            Outfits
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="orders_admin.php" class="sidebar-nav-link">
                            <i class="fas fa-shopping-cart sidebar-icon"></i>
                            Orders
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-header">ANALYTICS</div>
                <ul class="sidebar-nav">
                    <li class="sidebar-nav-item">
                        <a href="admin_reports.php" class="sidebar-nav-link">
                            <i class="fas fa-chart-bar sidebar-icon"></i>
                            Reports
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <div class="sidebar-section-header">SETTINGS</div>
                <ul class="sidebar-nav">
                    <li class="sidebar-nav-item">
                        <a href="admin_profile.php" class="sidebar-nav-link">
                            <i class="fas fa-user sidebar-icon"></i>
                            Profile
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="settings.php" class="sidebar-nav-link">
                            <i class="fas fa-cog sidebar-icon"></i>
                            Settings
                        </a>
                    </li>
                    <li class="sidebar-nav-item">
                        <a href="logout.php" class="sidebar-nav-link">
                            <i class="fas fa-sign-out-alt sidebar-icon"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-footer">
                © 2025 Clover Outfit Rentals
            </div>
        </div>
    </div>

    <!-- Update the main content div to use the new margin -->
    <div class="main-content">
        <h2>Outfit Management</h2>
        <p>Manage approved outfits and publish them to make available for rent</p>
        
        <!-- Add this right after the heading and before the outfit cards section -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="search-container">
                    <input type="text" class="form-control search-input" id="outfitSearchInput" placeholder="Search outfits...">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-success message-alert" id="message-alert">
            <?= $_SESSION['message']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['message']); endif; ?>
        
        <div class="row outfit-grid mt-4">
            <?php if($result && $result->num_rows > 0): ?>
                <?php while($outfit = $result->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="outfit-card">
                            <?php
                            // Outfit Image
                            $baseImageNumber = $outfit['image1'];
                            $imagePath = '';
                            
                            if (!empty($baseImageNumber)) {
                                // Remove any existing '_image1.jpg' suffix to avoid duplication
                                $baseImageNumber = str_replace('_image1.jpg', '', $baseImageNumber);
                                $imagePath = 'uploads/' . $baseImageNumber . '_image1.jpg';
                                
                                if (file_exists($imagePath)) {
                                    echo '<div class="outfit-image" style="background-image: url(\'' . $imagePath . '\');"></div>';
                                } else {
                                    echo '<div class="outfit-image no-image">';
                                    echo '<div class="no-image-placeholder">No Image Available</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="outfit-image no-image">';
                                echo '<div class="no-image-placeholder">No Image Available</div>';
                                echo '</div>';
                            }
                            ?>
                            
                            <div class="card-body">
                                <!-- Image Upload Section First -->
                                <div class="form-group mb-4">
                                    <h5>Upload Professional Images</h5>
                                    <form action="upload_outfit_images.php" method="POST" enctype="multipart/form-data" class="image-upload-form">
                                        <input type="hidden" name="outfit_id" value="<?= $outfit['outfit_id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Select Images (Max 4)</label>
                                            <input type="file" class="form-control" name="outfit_images[]" multiple accept="image/*">
                                        </div>
                                        <div class="current-images">
                                            <?php
                                            // Fetch and display existing admin uploaded images
                                            $image_query = "SELECT * FROM tbl_outfit_images 
                                                          WHERE outfit_id = ? AND uploaded_by = 'admin'
                                                          ORDER BY uploaded_at DESC";
                                            $stmt = $conn->prepare($image_query);
                                            $stmt->bind_param("i", $outfit['outfit_id']);
                                            $stmt->execute();
                                            $images = $stmt->get_result();
                                            
                                            while($image = $images->fetch_assoc()) {
                                                echo '<div class="image-preview">
                                                        <img src="'.$image['image_path'].'" alt="Outfit Image" class="img-thumbnail">
                                                        <button type="button" class="btn btn-sm btn-danger delete-image" 
                                                                data-image-id="'.$image['id'].'">×</button>
                                                      </div>';
                                            }
                                            ?>
                                        </div>
                                        <button type="submit" name="upload_images" class="btn btn-primary mt-2">Upload Images</button>
                                    </form>
                                </div>
                                
                                <!-- Outfit Details -->
                                <p class="card-text">
                                    <small>Submitted by: <?= $outfit['user_name'] ?></small><br>
                                    <small>Status: <span class="badge badge-success">Approved</span></small><br>
                                    <small>MRP: ₹<?= number_format($outfit['mrp'], 2) ?></small><br>
                                    <small>Rental Price: ₹<?= number_format($outfit['mrp'] * 0.20, 2) ?></small>
                                </p>
                                
                                <!-- Publish Form -->
                                <form action="outfit_management.php" method="POST">
                                    <input type="hidden" name="outfit_id" value="<?= $outfit['outfit_id'] ?>">
                                    
                                    <div class="form-group">
                                        <label for="description-<?= $outfit['outfit_id'] ?>">Description</label>
                                        <textarea class="form-control" 
                                                  id="description-<?= $outfit['outfit_id'] ?>" 
                                                  name="description" 
                                                  rows="3" 
                                                  required><?= htmlspecialchars($outfit['description_text'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <!-- Instead, you can display the gender as read-only if needed -->
                                    <div class="form-group">
                                        <label>Gender</label>
                                            <?php
                                        $gender_query = "SELECT s.subcategory_name 
                                                        FROM tbl_subcategory s 
                                                        WHERE s.id = ?";
                                        $stmt = $conn->prepare($gender_query);
                                        if ($stmt) {
                                            $stmt->bind_param("i", $outfit['gender_id']);
                                            $stmt->execute();
                                            $gender_result = $stmt->get_result();
                                            $gender = $gender_result->fetch_assoc();
                                            echo '<p class="form-control-static">' . htmlspecialchars($gender['subcategory_name']) . '</p>';
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Occasion (Multiple allowed)</label>
                                        <div class="occasion-checks">
                                            <?php 
                                            // First, get the currently selected occasions for this outfit
                                            $selected_occasions_query = "SELECT occasion_id FROM tbl_outfit_occasion WHERE outfit_id = ?";
                                            $stmt = $conn->prepare($selected_occasions_query);
                                            $stmt->bind_param("i", $outfit['outfit_id']);
                                            $stmt->execute();
                                            $selected_result = $stmt->get_result();
                                            
                                            // Create an array of selected occasion IDs
                                            $selected_occasions = array();
                                            while($row = $selected_result->fetch_assoc()) {
                                                $selected_occasions[] = $row['occasion_id'];
                                            }

                                            // Reset the occasion result pointer
                                            mysqli_data_seek($occasion_result, 0);
                                            
                                            // Now display checkboxes with selected occasions checked
                                            while($occasion = mysqli_fetch_assoc($occasion_result)) {
                                                $checked = in_array($occasion['id'], $selected_occasions) ? 'checked' : '';
                                                echo "<div class='occasion-item'>
                                                        <input type='checkbox' 
                                                               id='occasion-".$occasion['id']."-".$outfit['outfit_id']."' 
                                                               name='occasion[]' 
                                                               value='".$occasion['id']."'
                                                               ".$checked.">
                                                        <label for='occasion-".$occasion['id']."-".$outfit['outfit_id']."'>
                                                            ".$occasion['subcategory_name']."
                                                        </label>
                                                      </div>";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="price-<?= $outfit['outfit_id'] ?>">Price Range</label>
                                        <select class="form-control" id="price-<?= $outfit['outfit_id'] ?>" name="price_range" required>
                                            <option value="">Select Price Range</option>
                                            <?php
                                            $price_query = "SELECT s.id, s.subcategory_name 
                                                           FROM tbl_subcategory s 
                                                           JOIN tbl_category c ON s.category_id = c.id 
                                                           WHERE c.category_name = 'Price'";
                                            $price_result = mysqli_query($conn, $price_query);
                                            while($price = mysqli_fetch_assoc($price_result)) {
                                                $selected = ($outfit['price_id'] == $price['id']) ? 'selected' : '';
                                                echo "<option value='".$price['id']."' ".$selected.">".$price['subcategory_name']."</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Modified Publish Button -->
                                    <?php
                                    // Check if outfit has professional images
                                    $image_count_query = "SELECT COUNT(*) as count FROM tbl_outfit_images 
                                                        WHERE outfit_id = ? AND uploaded_by = 'admin'";
                                    $stmt = $conn->prepare($image_count_query);
                                    $stmt->bind_param("i", $outfit['outfit_id']);
                                    $stmt->execute();
                                    $image_count = $stmt->get_result()->fetch_assoc()['count'];

                                    $hasImages = $image_count > 0;
                                    $isPublished = !empty($outfit['description_id']);
                                    
                                    if (!$hasImages) {
                                        echo '<button type="submit" name="publish_outfit" class="btn btn-block btn-secondary" disabled>
                                                Upload Images First
                                              </button>';
                                    } elseif ($isPublished) {
                                        echo '<button type="submit" name="publish_outfit" class="btn btn-block btn-warning">
                                                Republish with Changes
                                              </button>';
                                    } else {
                                        echo '<button type="submit" name="publish_outfit" class="btn btn-block publish-btn">
                                                Publish
                                              </button>';
                                    }
                                    ?>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">No approved outfits found.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        $(document).ready(function(){
            setTimeout(function(){
                $("#message-alert").alert('close');
            }, 5000);
        });
        // Add to your existing JavaScript
$(document).ready(function() {
    $('.delete-image').click(function() {
        const imageId = $(this).data('image-id');
        const imagePreview = $(this).parent();
        
        if (confirm('Are you sure you want to delete this image?')) {
            $.ajax({
                url: 'upload_outfit_images.php',
                method: 'POST',
                data: { delete_image: true, image_id: imageId },
                success: function(response) {
                    const result = JSON.parse(response);
                    if(result.success) {
                        imagePreview.remove();
                        location.reload(); // Reload to update publish button state
                    }
                }
            });
        }
    });
});
    </script>
    <script>
        $(document).ready(function(){
            // Auto-hide alerts (existing code)
            setTimeout(function(){
                $("#message-alert").alert('close');
            }, 5000);
            
            // Add search functionality
            $('#outfitSearchInput').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                const outfitCards = $('.outfit-card').closest('.col-md-4');
                let visibleCount = 0;
                
                outfitCards.each(function() {
                    const cardText = $(this).text().toLowerCase();
                    const matchesSearch = cardText.includes(searchTerm);
                    
                    $(this).toggle(matchesSearch);
                    
                    if (matchesSearch) {
                        visibleCount++;
                    }
                });
                
                // Show message if no results
                if (visibleCount === 0 && searchTerm !== '') {
                    if ($('#no-results-message').length === 0) {
                        $('.row.mt-4').append('<div id="no-results-message" class="col-12 no-results">No outfits found matching "' + searchTerm + '"</div>');
                    } else {
                        $('#no-results-message').text('No outfits found matching "' + searchTerm + '"').show();
                    }
                } else {
                    $('#no-results-message').hide();
        }
    });
});
    </script>
</body>
</html>