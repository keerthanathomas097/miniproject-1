<?php
session_start();
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_images'])) {
    $outfit_id = $_POST['outfit_id'];
    
    // Debug output
    error_log("Starting image upload for outfit_id: " . $outfit_id);
    
    // Check if files were uploaded
    if (isset($_FILES['outfit_images']) && !empty($_FILES['outfit_images']['name'][0])) {
        $files = $_FILES['outfit_images'];
        
        // Debug output
        error_log("Files received: " . print_r($files, true));
        
        // Count existing images for this outfit
        $count_query = "SELECT COUNT(*) as count FROM tbl_outfit_images WHERE outfit_id = ? AND uploaded_by = 'admin'";
        $stmt = $conn->prepare($count_query);
        $stmt->bind_param("i", $outfit_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing_count = $result->fetch_assoc()['count'];
        
        // Limit total images to 4
        $available_slots = 4 - $existing_count;
        
        if ($available_slots > 0) {
            // Create uploads directory if it doesn't exist
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            for ($i = 0; $i < min(count($files['name']), $available_slots); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $files['tmp_name'][$i];
                    $name = $files['name'][$i];
                    
                    // Generate unique filename
                    $extension = pathinfo($name, PATHINFO_EXTENSION);
                    $new_filename = uniqid('outfit_') . '.' . $extension;
                    $upload_path = 'uploads/' . $new_filename;
                    
                    // Debug output
                    error_log("Attempting to upload file to: " . $upload_path);
                    
                    // Move file and insert record
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        try {
                            $insert_query = "INSERT INTO tbl_outfit_images 
                                           (outfit_id, image_path, uploaded_by) 
                                           VALUES (?, ?, 'admin')";
                            $stmt = $conn->prepare($insert_query);
                            $stmt->bind_param("is", $outfit_id, $upload_path);
                            $stmt->execute();
                            
                            error_log("Successfully uploaded and inserted into database: " . $upload_path);
                        } catch (Exception $e) {
                            error_log("Database error: " . $e->getMessage());
                            $_SESSION['error'] = "Database error: " . $e->getMessage();
                        }
                    } else {
                        error_log("Failed to move uploaded file from " . $tmp_name . " to " . $upload_path);
                        $_SESSION['error'] = "Failed to move uploaded file";
                    }
                } else {
                    error_log("Upload error code: " . $files['error'][$i]);
                    $_SESSION['error'] = "Upload error occurred";
                }
            }
            $_SESSION['message'] = "Images uploaded successfully!";
        } else {
            $_SESSION['message'] = "Maximum number of images (4) already reached.";
        }
    } else {
        error_log("No files were uploaded");
        $_SESSION['error'] = "No files were selected";
    }
    
    header("Location: outfit_management.php");
    exit();
}

// Handle image deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_image'])) {
    $image_id = $_POST['image_id'];
    
    try {
        // Get image path before deleting
        $path_query = "SELECT image_path FROM tbl_outfit_images WHERE id = ?";
        $stmt = $conn->prepare($path_query);
        $stmt->bind_param("i", $image_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $image = $result->fetch_assoc();
        
        if ($image) {
            // Delete file from server
            if (file_exists($image['image_path'])) {
                unlink($image['image_path']);
            }
            
            // Delete record from database
            $delete_query = "DELETE FROM tbl_outfit_images WHERE id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param("i", $image_id);
            $stmt->execute();
            
            error_log("Successfully deleted image: " . $image['image_path']);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        error_log("Error deleting image: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}
?> 