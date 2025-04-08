<?php
session_start();
include 'connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ls.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log received data for debugging
    error_log("Review form submitted: " . print_r($_POST, true));
    
    // Validate inputs
    if (!isset($_POST['outfit_id']) || !isset($_POST['user_id']) || !isset($_POST['rating']) || !isset($_POST['review_text'])) {
        $_SESSION['review_error'] = "Missing required fields";
        header('Location: rentnow.php?id=' . ($_POST['outfit_id'] ?? ''));
        exit;
    }
    
    $outfit_id = $_POST['outfit_id'];
    $user_id = $_POST['user_id'];
    $rating = intval($_POST['rating']);
    $review_text = trim($_POST['review_text']);
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $_SESSION['review_error'] = "Invalid rating";
        header('Location: rentnow.php?id=' . $outfit_id);
        exit;
    }
    
    try {
        // Check if user already reviewed this outfit
        $check_query = "SELECT review_id FROM tbl_reviews WHERE outfit_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $outfit_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing review
            $review = $result->fetch_assoc();
            $update_query = "UPDATE tbl_reviews SET rating = ?, review_text = ?, review_date = CURRENT_TIMESTAMP WHERE review_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("isi", $rating, $review_text, $review['review_id']);
            
            if ($update_stmt->execute()) {
                $_SESSION['review_success'] = "Your review has been updated!";
            } else {
                throw new Exception("Error updating review: " . $conn->error);
            }
        } else {
            // Insert new review
            $insert_query = "INSERT INTO tbl_reviews (outfit_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iiis", $outfit_id, $user_id, $rating, $review_text);
            
            if ($insert_stmt->execute()) {
                $_SESSION['review_success'] = "Thank you for your review!";
            } else {
                throw new Exception("Error submitting review: " . $conn->error);
            }
        }
        
    } catch (Exception $e) {
        error_log("Error in review submission: " . $e->getMessage());
        $_SESSION['review_error'] = "Error processing review: " . $e->getMessage();
    }
    
    // Redirect back to the outfit page
    header('Location: rentnow.php?id=' . $outfit_id);
    exit;
}

// If not a POST request, redirect to homepage
header('Location: index.php');
exit;
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fix the star rating in the modal
    const starLabels = document.querySelectorAll('.star-rating label');
    starLabels.forEach(label => {
        label.addEventListener('click', function() {
            // Get the corresponding radio input
            const radio = document.getElementById(this.getAttribute('for'));
            if (radio) {
                radio.checked = true;
            }
        });
    });
});
</script> 