<?php
session_start();
include 'connect.php';

// Check if user is logged in and has admin role
// if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'admin') {
//     header("Location: outfit_management.php");
//     exit();
// }

// Handle outfit publishing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['publish_outfit'])) {
    $outfit_id = $_POST['outfit_id'];
    
    // Check if outfit already has a description
    $check_query = "SELECT description_id FROM tbl_outfit WHERE outfit_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $outfit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    try {
        $conn->begin_transaction();
        
        // Insert description and get its ID
        $stmt = $conn->prepare("INSERT INTO tbl_description (description_text) VALUES (?)");
        $stmt->bind_param("s", $_POST['description']);
        $stmt->execute();
        $description_id = $conn->insert_id;
        
        // Update existing outfit with new information
        $update_query = "UPDATE tbl_outfit SET 
                        description_id = ?,
                        gender_id = ?,
                        price_range_id = ?
                        WHERE outfit_id = ?";
                        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("iiii", $description_id, $_POST['gender'], $_POST['price_range'], $outfit_id);
        $stmt->execute();
        
        // Insert occasions into junction table
        if (!empty($_POST['occasion'])) {
            $occasion_stmt = $conn->prepare("INSERT INTO tbl_outfit_occasion (outfit_id, occasion_id) VALUES (?, ?)");
            foreach ($_POST['occasion'] as $occasion_id) {
                $occasion_stmt->bind_param("ii", $outfit_id, $occasion_id);
                $occasion_stmt->execute();
            }
        }
        
        $conn->commit();
        $_SESSION['message'] = "Outfit details updated successfully!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Error: " . $e->getMessage();
    }
    
    header("Location: outfit_management.php");
    exit();
}

// Fetch all approved outfits
$query = "SELECT o.*, u.name as user_name, u.email as user_email 
          FROM tbl_outfit o 
          JOIN tbl_users u ON o.user_id = u.user_id 
          WHERE o.status IN ('approved', 'Published') 
          ORDER BY o.outfit_id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outfit Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        .outfit-card {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .outfit-image {
            height: 250px;
            background-size: cover;
            background-position: center;
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Include your sidebar here -->
            <div class="col-md-2 bg-dark sidebar">
                <!-- Sidebar content -->
            </div>
            
            <div class="col-md-10 p-4">
                <h2>Outfit Management</h2>
                <p>Manage approved outfits and publish them to make available for rent</p>
                <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                
                <!-- Success/Error Messages -->
                <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-success message-alert" id="message-alert">
                    <?= $_SESSION['message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['message']); endif; ?>
                
                <div class="row mt-4">
                    <?php if($result->num_rows > 0): ?>
                        <?php while($outfit = $result->fetch_assoc()): ?>
                            <div class="col-md-4">
                                <div class="outfit-card">
                                    <!-- Outfit Image -->
                                    <div class="outfit-image" style="background-image: url('uploads/<?= $outfit['image1'] ?>');"></div>
                                    
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
                                                <textarea class="form-control" id="description-<?= $outfit['outfit_id'] ?>" 
                                                    name="description" rows="3" required><?php 
                                                    // Show existing description if any
                                                    if($outfit['description_id']) {
                                                        $desc_query = "SELECT description_text FROM tbl_description WHERE id = ?";
                                                        $stmt = $conn->prepare($desc_query);
                                                        $stmt->bind_param("i", $outfit['description_id']);
                                                        $stmt->execute();
                                                        $desc_result = $stmt->get_result();
                                                        if($desc_row = $desc_result->fetch_assoc()) {
                                                            echo htmlspecialchars($desc_row['description_text']);
                                                        }
                                                    }
                                                ?></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="gender-<?= $outfit['outfit_id'] ?>">Gender</label>
                                                <select class="form-control" id="gender-<?= $outfit['outfit_id'] ?>" name="gender" required>
                                                    <option value="">Select Gender</option>
                                                    <?php
                                                    $gender_query = "SELECT s.id, s.subcategory_name FROM tbl_subcategory s 
                                                                    JOIN tbl_category c ON s.category_id = c.id 
                                                                    WHERE c.category_name = 'Gender'";
                                                    $gender_result = mysqli_query($conn, $gender_query);
                                                    while($gender = mysqli_fetch_assoc($gender_result)) {
                                                        $selected = ($outfit['gender_id'] == $gender['id']) ? 'selected' : '';
                                                        echo "<option value='".$gender['id']."' ".$selected.">".$gender['subcategory_name']."</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Occasion (Multiple allowed)</label>
                                                <div class="occasion-checks">
                                                    <?php 
                                                    $occasion_query = "SELECT s.id, s.subcategory_name 
                                                                      FROM tbl_subcategory s 
                                                                      JOIN tbl_category c ON s.category_id = c.id 
                                                                      WHERE c.category_name = 'Occasion'";
                                                    $occasion_result = mysqli_query($conn, $occasion_query);
                                                    while($occasion = mysqli_fetch_assoc($occasion_result)) {
                                                        echo "<div class='occasion-item'>
                                                                <input type='checkbox' id='occasion-".$occasion['id']."-".$outfit['outfit_id']."' 
                                                                    name='occasion[]' value='".$occasion['id']."'>
                                                                <label for='occasion-".$occasion['id']."-".$outfit['outfit_id']."'>".$occasion['subcategory_name']."</label>
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
                                                        echo "<option value='".$price['id']."'>".$price['subcategory_name']."</option>";
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
</body>
</html>