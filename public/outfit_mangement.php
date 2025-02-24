<?php
session_start();
include 'connect.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle outfit publishing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['publish_outfit'])) {
    $outfit_id = $_POST['outfit_id'];
    $description = $_POST['description'];
    $gender = $_POST['gender'];
    $price_range = $_POST['price_range'];
    
    // Handle multiple occasion checkboxes
    $occasions = isset($_POST['occasion']) ? implode(",", $_POST['occasion']) : "";
    
    // Update the outfit with additional details
    $update_query = "UPDATE tbl_outfit SET 
                    description = ?,
                    gender = ?,
                    occasions = ?,
                    price_range = ?,
                    is_published = 1
                    WHERE outfit_id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssi", $description, $gender, $occasions, $price_range, $outfit_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Outfit published successfully!";
    } else {
        $_SESSION['message'] = "Error publishing outfit: " . $conn->error;
    }
    
    // Redirect to refresh page
    header("Location: outfit_management.php");
    exit();
}

// Fetch all approved outfits
$query = "SELECT o.*, u.name as user_name, u.email as user_email 
          FROM tbl_outfit o 
          JOIN tbl_users u ON o.user_id = u.user_id 
          WHERE o.status = 'approved' 
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
                                        <!-- <h5 class="card-title"><?= $outfit['outfit_name'] ?></h5> -->
                                        <p class="card-text">
                                            <small>Submitted by: <?= $outfit['user_name'] ?></small><br>
                                            <small>Status: <span class="badge badge-success">Approved</span></small>
                                        </p>
                                        
                                        <!-- Publish Form -->
                                        <form action="outfit_management.php" method="POST">
                                            <input type="hidden" name="outfit_id" value="<?= $outfit['outfit_id'] ?>">
                                            
                                            <div class="form-group">
                                                <label for="description-<?= $outfit['outfit_id'] ?>">Description</label>
                                                <textarea class="form-control" id="description-<?= $outfit['outfit_id'] ?>" 
                                                    name="description" rows="3" required><?= $outfit['description'] ?? '' ?></textarea>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="gender-<?= $outfit['outfit_id'] ?>">Gender</label>
                                                <select class="form-control" id="gender-<?= $outfit['outfit_id'] ?>" name="gender" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="male" <?= (isset($outfit['gender']) && $outfit['gender'] == 'male') ? 'selected' : '' ?>>Male</option>
                                                    <option value="female" <?= (isset($outfit['gender']) && $outfit['gender'] == 'female') ? 'selected' : '' ?>>Female</option>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Occasion (Multiple allowed)</label>
                                                <div class="occasion-checks">
                                                    <?php 
                                                    $saved_occasions = isset($outfit['occasions']) ? explode(',', $outfit['occasions']) : [];
                                                    ?>
                                                    <div class="occasion-item">
                                                        <input type="checkbox" id="all-<?= $outfit['outfit_id'] ?>" 
                                                            name="occasion[]" value="All"
                                                            <?= in_array('All', $saved_occasions) ? 'checked' : '' ?>>
                                                        <label for="all-<?= $outfit['outfit_id'] ?>">All</label>
                                                    </div>
                                                    <div class="occasion-item">
                                                        <input type="checkbox" id="wedding-<?= $outfit['outfit_id'] ?>" 
                                                            name="occasion[]" value="Wedding"
                                                            <?= in_array('Wedding', $saved_occasions) ? 'checked' : '' ?>>
                                                        <label for="wedding-<?= $outfit['outfit_id'] ?>">Wedding</label>
                                                    </div>
                                                    <div class="occasion-item">
                                                        <input type="checkbox" id="prewedding-<?= $outfit['outfit_id'] ?>" 
                                                            name="occasion[]" value="Pre-wedding"
                                                            <?= in_array('Pre-wedding', $saved_occasions) ? 'checked' : '' ?>>
                                                        <label for="prewedding-<?= $outfit['outfit_id'] ?>">Pre-wedding</label>
                                                    </div>
                                                    <div class="occasion-item">
                                                        <input type="checkbox" id="shoots-<?= $outfit['outfit_id'] ?>" 
                                                            name="occasion[]" value="Shoots"
                                                            <?= in_array('Shoots', $saved_occasions) ? 'checked' : '' ?>>
                                                        <label for="shoots-<?= $outfit['outfit_id'] ?>">Shoots</label>
                                                    </div>
                                                    <div class="occasion-item">
                                                        <input type="checkbox" id="other-<?= $outfit['outfit_id'] ?>" 
                                                            name="occasion[]" value="Other"
                                                            <?= in_array('Other', $saved_occasions) ? 'checked' : '' ?>>
                                                        <label for="other-<?= $outfit['outfit_id'] ?>">Other</label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="price-<?= $outfit['outfit_id'] ?>">Price Range</label>
                                                <select class="form-control" id="price-<?= $outfit['outfit_id'] ?>" name="price_range" required>
                                                    <option value="">Select Price Range</option>
                                                    <option value="below 5000" <?= (isset($outfit['price_range']) && $outfit['price_range'] == 'below 5000') ? 'selected' : '' ?>>Below ₹5,000</option>
                                                    <option value="below 10000" <?= (isset($outfit['price_range']) && $outfit['price_range'] == 'below 10000') ? 'selected' : '' ?>>Below ₹10,000</option>
                                                    <option value="below 20000" <?= (isset($outfit['price_range']) && $outfit['price_range'] == 'below 20000') ? 'selected' : '' ?>>Below ₹20,000</option>
                                                    <option value="above 20000" <?= (isset($outfit['price_range']) && $outfit['price_range'] == 'above 20000') ? 'selected' : '' ?>>Above ₹20,000</option>
                                                </select>
                                            </div>
                                            
                                            <!-- Publish Button -->
                                            <button type="submit" name="publish_outfit" class="btn publish-btn btn-block">
                                                <?= (isset($outfit['is_published']) && $outfit['is_published'] == 1) ? 'Update' : 'Publish' ?>
                                            </button>
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
    </script>
</body>
</html>