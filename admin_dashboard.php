<?php 
session_start();
include 'connect.php';

// Include PHPMailer files
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/SMTP.php';

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if user is logged in and is an admin
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: ls.php");
    exit();
}

// Now you can safely use session variables
$admin_id = $_SESSION['id'];
$admin_name = $_SESSION['username'];

// Fetch total user count
$sql = "SELECT COUNT(*) AS user_count FROM tbl_users"; 
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_count = $row['user_count'];
} else {
    $user_count = 0;
}
    
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    // Validate input data
    if (!isset($_POST['outfit_id']) || !isset($_POST['status'])) {
        die("Error: Required fields are missing");
    }

    $outfit_id = $_POST['outfit_id'];
    $new_status = strtolower($_POST['status']);
    $user_email = $_POST['user_email']; // Make sure this is being passed from the form
    $user_name = $_POST['user_name']; // Make sure this is being passed from the form
    
    // Validate status values
    $allowed_statuses = ['pending', 'approved', 'rejected'];
    if (!in_array($new_status, $allowed_statuses)) {
        die("Error: Invalid status value");
    }

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update outfit status
    $stmt = $conn->prepare("UPDATE tbl_outfit SET status = ? WHERE outfit_id = ?");
    if (!$stmt) {
            throw new Exception("Error preparing outfit update statement: " . $conn->error);
    }

    // Bind parameters and execute
        $stmt->bind_param("ss", $new_status, $outfit_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating outfit status: " . $stmt->error);
        }
        $stmt->close();
        
        // If status is changed to 'approved', update user role to 'lender' and send email
        if ($new_status === 'approved') {
            // Log the email address for debugging
            error_log("Updating user role for email: $user_email");

            // Update user role to lender
            $update_user_stmt = $conn->prepare("UPDATE tbl_users SET role = 'lender' WHERE email = ? AND role = 'user'");
            if (!$update_user_stmt) {
                throw new Exception("Error preparing user update statement: " . $conn->error);
            }
            
            $update_user_stmt->bind_param("s", $user_email);
            if (!$update_user_stmt->execute()) {
                throw new Exception("Error updating user role: " . $update_user_stmt->error);
            }
            $update_user_stmt->close();
            
            // Send email notification
            sendApprovalEmail($user_email, $user_name, $outfit_id);
        }
        
        // Commit transaction
        $conn->commit();
        $_SESSION['success_message'] = "Status updated successfully!";
        
    } catch (Exception $e) {
        // Roll back transaction if any error occurred
        $conn->rollback();
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        error_log("Error in update process: " . $e->getMessage());
    }

    // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
}

// Display messages if they exist
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
    unset($_SESSION['error_message']);
}

// Fetch all outfit requests
$query = "SELECT o.outfit_id, u.name, u.email, u.phone, o.type_id, o.size_id, o.brand_id, 
                 o.mrp, o.purchase_year, o.city, o.status, o.created_at, 
                 o.image1, o.image2, o.image3 
          FROM tbl_outfit o 
          LEFT JOIN tbl_users u ON o.email = u.email 
          ORDER BY o.created_at DESC";
$result = $conn->query($query);

// Add error checking
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Function to send approval email
function sendApprovalEmail($email, $name, $outfit_id) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->SMTPDebug = 0;  // Set to 2 for debugging
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'cloveroutfitrentals@gmail.com'; // Update with your email
        $mail->Password = 'dxrh atrf fgqr dlzp'; // Update with your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('cloveroutfitrentals@gmail.com', 'Clover Outfit Rentals');
        $mail->addAddress($email, $name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Outfit Approved - Welcome to Clover Outfit Rentals!';
        
        $mail->Body = "
            <h2>Congratulations, $name!</h2>
            <p>Your outfit (ID: $outfit_id) has been approved by our admin team.</p>
            <p>You are now registered as a lender on our platform. You can log in to your lender dashboard to manage your outfits and view rental orders.</p>
            <p>Thank you for joining Clover Outfit Rentals!</p>
            <p>Best regards,<br>The Clover Outfit Rentals Team</p>
        ";

        $mail->send();
        error_log("Approval email sent successfully to $email for outfit ID $outfit_id");
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Fashion Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    /* Exact sidebar styling to match admin_reports.php */
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0;
        padding: 0;
    }
    
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 240px;
        background-color: #932A2A; /* Deep maroon color */
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
    <style>
        :root { --sidebar-width: 250px; }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background:rgb(91, 9, 9);
            color: white;
            padding-top: 20px;
        }
        .main-content { margin-left: var(--sidebar-width); padding: 20px; }
        .sidebar-link {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: 0.3s;
        }
        .sidebar-link:hover { background:rgb(147, 42, 42); color: #ecf0f1; }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .stat-card { background: linear-gradient(45deg,rgb(205, 38, 38),rgb(99, 7, 7)); color: white; }
        .table { font-size: 0.9rem; }
        .table thead th { background-color: #f8f9fa; font-weight: 600; }
        .badge { padding: 6px 12px; font-weight: 500; letter-spacing: 0.3px; }
        .table-responsive { overflow-x: auto; }
        img { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; }
        select, button { padding: 5px; margin: 5px; }
        /* Main table container */
  /* Reset any potential conflicting styles */
.admin-dashboard-table table,
.admin-dashboard-table th,
.admin-dashboard-table td,
.admin-dashboard-table tr,
.admin-dashboard-table thead,
.admin-dashboard-table tbody {
    margin: 0;
    padding: 0;
    border: none;
    font-size: 100%;
    font: inherit;
    vertical-align: baseline;
}

/* Main container styles */
.admin-dashboard-table {
    width: 100% !important;
    margin: 20px 0 !important;
    padding: 20px !important;
    background: #ffffff !important;
    border-radius: 8px !important;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1) !important;
    overflow-x: auto !important;
}

/* Table base styles */
.admin-dashboard-table .admin-table {
    width: 100% !important;
    border-collapse: collapse !important;
    background-color: #ffffff !important;
    border: 1px solid #e0e0e0 !important;
}

/* Header styles */
.admin-dashboard-table .admin-table thead tr {
    background-color: #f8f9fa !important;
}

.admin-dashboard-table .admin-table th {
    padding: 15px !important;
    text-align: left !important;
    font-weight: bold !important;
    color: #333333 !important;
    border-bottom: 2px solid #dee2e6 !important;
    font-size: 14px !important;
}

/* Body styles */
.admin-dashboard-table .admin-table tbody tr {
    border-bottom: 1px solid #e0e0e0 !important;
}

.admin-dashboard-table .admin-table tbody tr:hover {
    background-color: #f5f5f5 !important;
}

.admin-dashboard-table .admin-table td {
    padding: 12px 15px !important;
    vertical-align: middle !important;
    font-size: 14px !important;
}

/* Image cell styles */
.admin-dashboard-table .image-cell {
    display: flex !important;
    gap: 5px !important;
    flex-wrap: wrap !important;
}

.admin-dashboard-table .image-cell img {
    width: 50px !important;
    height: 50px !important;
    object-fit: cover !important;
    border-radius: 4px !important;
    border: 1px solid #dee2e6 !important;
}

/* Status badge styles */
.admin-dashboard-table .status-badge {
    padding: 5px 10px !important;
    border-radius: 15px !important;
    font-size: 12px !important;
    font-weight: bold !important;
    text-transform: capitalize !important;
    display: inline-block !important;
}

.admin-dashboard-table .status-badge.approved {
    background-color: #d4edda !important;
    color: #155724 !important;
}

.admin-dashboard-table .status-badge.pending {
    background-color: #fff3cd !important;
    color: #856404 !important;
}

.admin-dashboard-table .status-badge.rejected {
    background-color: #f8d7da !important;
    color: #721c24 !important;
}

/* Action cell styles */
.admin-dashboard-table .action-cell {
    min-width: 150px !important;
}

.admin-dashboard-table .status-select {
    width: 100% !important;
    padding: 6px 10px !important;
    margin-bottom: 5px !important;
    border: 1px solid #ced4da !important;
    border-radius: 4px !important;
    font-size: 14px !important;
}

.admin-dashboard-table .update-button {
    width: 100% !important;
    padding: 6px 12px !important;
    background-color: #007bff !important;
    color: white !important;
    border: none !important;
    border-radius: 4px !important;
    cursor: pointer !important;
    font-size: 14px !important;
}

.admin-dashboard-table .update-button:hover {
    background-color: #0056b3 !important;
}

/* Responsive styles */
@media screen and (max-width: 1024px) {
    .admin-dashboard-table {
        padding: 10px !important;
    }
    
    .admin-dashboard-table .admin-table {
        min-width: 1000px !important;
    }
}
/* Enhanced table styling */
.admin-table {
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    background: white !important;
    margin: 20px 0 !important;
    border: 1px solid #e0e0e0 !important;
}

/* Header styling */
.admin-table th {
    background-color: #f8f9fa !important;
    padding: 15px 12px !important;
    border-bottom: 2px solid #dee2e6 !important;
    border-right: 1px solid #dee2e6 !important;
    font-weight: 600 !important;
    text-align: left !important;
    white-space: nowrap !important;
    color: #333 !important;
    font-size: 14px !important;
}

/* Column specific widths */
/* Column specific widths */
.admin-table th:nth-child(1), /* Outfit ID */
.admin-table td:nth-child(1) {
    width: 60px !important;
    min-width: 60px !important;
    
}

.admin-table th:nth-child(2), /* User Name */
.admin-table td:nth-child(2) {
    width: 100px !important;
    min-width: 100px !important;
}

.admin-table th:nth-child(3), /* Email */
.admin-table td:nth-child(3) {
    width: 150px !important;
    min-width: 150px !important;
}

.admin-table th:nth-child(4), /* Phone */
.admin-table td:nth-child(4) {
    width: 100px !important;
    min-width: 100px !important;
}

.admin-table th:nth-child(5), /* Category */
.admin-table td:nth-child(5) {
    width: 80px !important;
    min-width: 80px !important;
}

.admin-table th:nth-child(6), /* Size */
.admin-table td:nth-child(6) {
    width: 60px !important;
    min-width: 60px !important;
}

.admin-table th:nth-child(7), /* Brand */
.admin-table td:nth-child(7) {
    width: 90px !important;
    min-width: 90px !important;
}

.admin-table th:nth-child(8), /* MRP */
.admin-table td:nth-child(8) {
    width: 70px !important;
    min-width: 70px !important;
}

.admin-table th:nth-child(9), /* Year */
.admin-table td:nth-child(9) {
    width: 60px !important;
    min-width: 60px !important;
}

.admin-table th:nth-child(10), /* City */
.admin-table td:nth-child(10) {
    width: 80px !important;
    min-width: 80px !important;
}

.admin-table th:nth-child(11), /* Images */
.admin-table td:nth-child(11) {
    width: 120px !important;
    min-width: 120px !important;
}

/* Status cell */
.admin-table td:nth-last-child(2) {
    width: 80px !important;
    min-width: 80px !important;
}

/* Action cell */
.admin-table td:last-child {
    width: 120px !important;
    min-width: 120px !important;
}

/* Add this to ensure text doesn't wrap awkwardly */
.admin-table td {
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

/* Add this for email cells to show ellipsis for long emails */
.admin-table td:nth-child(3) {
    text-overflow: ellipsis !important;
    overflow: hidden !important;
}

/* Status select and button styling */
.status-select {
    width: 100% !important;
    padding: 6px 8px !important;
    border: 1px solid #ced4da !important;
    border-radius: 4px !important;
    margin-bottom: 5px !important;
}

.update-button {
    width: 100% !important;
    padding: 6px 12px !important;
    background-color:rgb(2, 33, 66) !important;
    color: white !important;
    border: none !important;
    border-radius: 4px !important;
    cursor: pointer !important;
}

/* Container styling */
.admin-dashboard-table {
    margin: 20px !important;
    padding: 20px !important;
    background: white !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    overflow-x: auto !important;
}

/* Responsive handling */
@media screen and (max-width: 1200px) {
    .admin-table {
        min-width: 1200px !important;
    }
}
/* Carousel Container */
/* Updated Carousel Container Styles */
/* Modal container */
#carouselModal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    justify-content: center;
    align-items: center;
}

/* Modal carousel content */
.modal-carousel {
    width: 90%;  /* Increased width */
    height: 90vh; /* Increased height */
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
}

/* Modal image */
#modalCarouselImage {
    max-width: 90%;  /* Increased from previous value */
    max-height: 90vh; /* Increased from previous value */
    width: auto;
    height: auto;
    object-fit: contain;
}

/* Navigation buttons in modal */
#carouselModal .prev-btn,
#carouselModal .next-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    cursor: pointer;
    padding: 20px;  /* Increased padding */
    font-size: 24px;  /* Increased font size */
    z-index: 1001;
    width: 60px;  /* Set explicit width */
    height: 60px;  /* Set explicit height */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

#carouselModal .prev-btn { left: 30px; }
#carouselModal .next-btn { right: 30px; }

/* Close button */
#closeCarouselModal {
    position: absolute;
    top: 20px;
    right: 30px;
    color: white;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    z-index: 1001;
}

/* Ensure small carousel in table remains small */
.carousel-container {
    position: relative;
    width: 100px;  /* Size for table view */
    height: 60px;
    overflow: hidden;
}

.carousel-container .carousel img {
    width: 100px;
    height: 60px;
    object-fit: cover;
}
.update-button {
    display: inline-block;
    padding: 5px 10px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.update-button:hover {
    background-color: #0056b3;
}
.status-badge {
    padding: 5px 10px;
    border-radius: 4px;
    display: inline-block;
}

.pending { background-color: #ffd700; }
.approved { background-color: #90ee90; }
.rejected { background-color: #ffcccb; }

.alert {
    padding: 15px;
    margin: 20px 0;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

/* Wrap table inside a scrollable container */
.admin-table-container {
    width: 100%;
    overflow-x: auto; /* Enables horizontal scrolling if needed */
}

/* Make table fit screen */
.admin-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px; /* Reduce font size to fit content */
}

/* Table Header Styling */
.admin-table thead th {
    background-color: #f4f4f4;
    padding: 10px;
    text-align: left;
    white-space: nowrap; /* Prevents header text from wrapping */
}

/* Table Cell Styling */
.admin-table td {
    padding: 8px;
    text-align: left;
    vertical-align: middle;
    white-space: nowrap; /* Prevents text from wrapping */
}

/* Responsive images inside table */
.admin-table img {
    max-width: 60px; /* Adjust image size */
    height: auto;
    display: block;
}

/* Styling for the status dropdown */
.status-select {
    padding: 5px;
    font-size: 12px;
}

/* Update button styling */
.update-button {
    padding: 6px 10px;
    font-size: 12px;
    cursor: pointer;
}

/* Hide overflow for large content */
.admin-table td.action-cell {
    white-space: nowrap;
}

/* Reduce button and select width */
.action-cell form {
    display: flex;
    gap: 5px;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
    .admin-table {
        font-size: 12px; /* Reduce font size on smaller screens */
    }
    
    .admin-table img {
        max-width: 40px; /* Smaller images on mobile */
    }
    
    .status-select,
    .update-button {
        font-size: 10px;
        padding: 4px;
    }
}

/* Ensure table fits inside the screen */
.admin-table-container {
    width: 100%;
    overflow-x: auto;
}

/* Make table fully responsive */
.admin-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed; /* Ensures columns distribute evenly */
    font-size: 12px;
}

/* Table Header Styling */
.admin-table thead th {
    background-color: #f4f4f4;
    padding: 8px;
    text-align: left;
    white-space: nowrap;
    font-size: 13px;
}

/* Table Cell Styling */
.admin-table td {
    padding: 6px;
    text-align: left;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis; /* Truncate text with '...' */
    white-space: nowrap;
}

/* Reduce column widths */
.admin-table th:nth-child(1), /* Outfit ID */
.admin-table td:nth-child(1),
.admin-table th:nth-child(6), /* Size */
.admin-table td:nth-child(6),
.admin-table th:nth-child(9), /* Year */
.admin-table td:nth-child(9) {
    width: 5%; /* Narrow columns */
}

/* Make columns with longer text take more space */
.admin-table th:nth-child(2), /* User Name */
.admin-table td:nth-child(2),
.admin-table th:nth-child(3), /* Email */
.admin-table td:nth-child(3),
.admin-table th:nth-child(10), /* City */
.admin-table td:nth-child(10) {
    width: 15%;
}

/* Responsive images */
.admin-table img {
    max-width: 50px;
    height: auto;
    display: block;
}

/* Responsive dropdown & button */
.status-select {
    padding: 4px;
    font-size: 11px;
    max-width: 80px;
}

.update-button {
    padding: 4px 6px;
    font-size: 11px;
}

/* Responsive layout for smaller screens */
@media (max-width: 1024px) {
    .admin-table {
        font-size: 11px;
    }

    .admin-table th, .admin-table td {
        padding: 4px;
    }

    .admin-table img {
        max-width: 40px;
    }

    .status-select {
        font-size: 10px;
    }

    .update-button {
        font-size: 10px;
        padding: 3px 5px;
    }
}

/* Mobile-friendly adjustments */
@media (max-width: 768px) {
    .admin-table {
        font-size: 10px;
    }

    .admin-table img {
        max-width: 30px;
    }

    /* Stack some columns into two rows */
    .admin-table td:nth-child(3),
    .admin-table td:nth-child(4),
    .admin-table td:nth-child(5),
    .admin-table td:nth-child(10) {
        display: block;
        width: 100%;
        text-align: left;
    }
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.95);
    overflow: hidden;
}

.modal-content {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.modal-image-container {
    position: relative;
    width: 90vw;
    height: 85vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

#carouselImage {
    max-width: 85vw;
    max-height: 80vh;
    width: auto;
    height: auto;
    object-fit: contain;
}

.close-modal {
    position: fixed;
    top: 20px;
    right: 30px;
    color: #fff;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    z-index: 10000;
}

.carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: none;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    z-index: 10000;
    transition: all 0.3s ease;
}

.carousel-btn:hover {
    background: rgba(255, 255, 255, 0.4);
    transform: translateY(-50%) scale(1.1);
}

.carousel-btn.prev { left: 20px; }
.carousel-btn.next { right: 20px; }

.image-counter {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    color: white;
    font-size: 18px;
    background: rgba(0, 0, 0, 0.5);
    padding: 8px 15px;
    border-radius: 20px;
}

.close-button {
    position: fixed;
    top: 20px;
    right: 20px;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    z-index: 10001;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.close-button:hover {
    background: rgba(0, 0, 0, 0.8);
    transform: scale(1.1);
}

/* Search bar styling */
.control-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 15px;
    border-radius: 6px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.06);
}

.search-container {
    flex: 1;
    max-width: 400px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 10px 15px 10px 40px;
    border-radius: 30px;
    border: 1px solid #e9ecef;
    font-size: 14px;
    transition: all 0.3s;
    background-color: #f7f7f7;
}

.search-input:focus {
    outline: none;
    border-color: rgb(217, 177, 153);
    box-shadow: 0 0 0 3px rgba(217, 177, 153, 0.2);
}

.search-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

/* Add this CSS to your existing <style> section */
<style>
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 250px;
        background-color: #932A2A;
        padding-top: 1rem;
        z-index: 100;
    }

    .brand-title {
        color: white;
        padding: 0 1.5rem;
        margin-bottom: 2rem;
    }

    .brand-title h1 {
        font-size: 24px;
        margin: 0;
    }

    .brand-title span {
        font-size: 14px;
        opacity: 0.8;
    }

    .nav-section {
        margin-bottom: 1.5rem;
    }

    .nav-section-title {
        color: rgba(255, 255, 255, 0.4);
        font-size: 12px;
        font-weight: 500;
        padding: 0 1.5rem;
        margin-bottom: 0.5rem;
        letter-spacing: 0.5px;
    }

    .nav-pills .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 0.75rem 1.5rem;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s;
    }

    .nav-pills .nav-link:hover {
        color: white;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .nav-pills .nav-link.active {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .nav-pills .nav-link i {
        width: 20px;
        text-align: center;
        font-size: 16px;
    }

    .sidebar-footer {
        position: absolute;
        bottom: 1rem;
        left: 0;
        right: 0;
        padding: 0 1.5rem;
        color: rgba(255, 255, 255, 0.4);
        font-size: 12px;
    }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand-container">
            <h1 class="brand-name">Clover Outfit Rentals</h1>
            <div class="brand-subtitle">Admin Dashboard</div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-section-header">DASHBOARD</div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="admin_dashboard.php" class="sidebar-nav-link active">
                        <i class="fas fa-home sidebar-icon"></i>
                        Dashboard
                    </a>
                </li>
            </ul>
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
                    <a href="outfit_management.php" class="sidebar-nav-link">
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h2>Dashboard Overview</h2>

            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <h5><i class="fas fa-users me-2"></i> Total Users</h5>
                            <h3><?php echo number_format($user_count); ?></h3> 
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add this right after the Stats Cards section, before the h2 heading for Outfit Requests -->
            <div class="control-bar mb-4">
                <div class="search-container">
                    <input type="text" class="search-input" id="outfitSearchInput" placeholder="Search requests...">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>

            <h2>Admin Dashboard - Outfit Requests</h2>
            <div class="table-dashboard-table">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Outfit ID</th>
                            <th>User Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Category</th>
                            <th>Size</th>
                            <th>Brand</th>
                            <th>MRP</th>
                            <th>Year</th>
                            <th>City</th>
                            <th>Images</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['outfit_id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php 
                                $type_query = "SELECT subcategory_name FROM tbl_subcategory WHERE id = ?";
                                $stmt = $conn->prepare($type_query);
                                if ($stmt === false) {
                                    echo "Error preparing statement: " . $conn->error;
                                    $type_name = "Unknown";
                                } else {
                                $stmt->bind_param("i", $row['type_id']);
                                $stmt->execute();
                                $type_result = $stmt->get_result();
                                $type = $type_result->fetch_assoc();
                                    $type_name = $type ? $type['subcategory_name'] : "Unknown";
                                    $stmt->close();
                                }
                                echo htmlspecialchars($type_name); 
                            ?></td>
                            <td><?php 
                                $size_query = "SELECT subcategory_name FROM tbl_subcategory WHERE id = ?";
                                $stmt = $conn->prepare($size_query);
                                if ($stmt === false) {
                                    echo "Error preparing statement: " . $conn->error;
                                    $size_name = "Unknown";
                                } else {
                                $stmt->bind_param("i", $row['size_id']);
                                $stmt->execute();
                                $size_result = $stmt->get_result();
                                $size = $size_result->fetch_assoc();
                                    $size_name = $size ? $size['subcategory_name'] : "Unknown";
                                    $stmt->close();
                                }
                                echo htmlspecialchars($size_name); 
                            ?></td>
                            <td><?php 
                                $brand_query = "SELECT subcategory_name FROM tbl_subcategory WHERE id = ?";
                                $stmt = $conn->prepare($brand_query);
                                if ($stmt === false) {
                                    echo "Error preparing statement: " . $conn->error;
                                    $brand_name = "Unknown";
                                } else {
                                $stmt->bind_param("i", $row['brand_id']);
                                $stmt->execute();
                                $brand_result = $stmt->get_result();
                                $brand = $brand_result->fetch_assoc();
                                    $brand_name = $brand ? $brand['subcategory_name'] : "Unknown";
                                    $stmt->close();
                                }
                                echo htmlspecialchars($brand_name); 
                            ?></td>
                            <td><?php echo $row['mrp']; ?></td>
                            <td><?php echo $row['purchase_year']; ?></td>
                            <td><?php echo $row['city']; ?></td>
                            <td>
                                <?php 
                                    $baseImageNumber = $row['image1'];
                                    if (!empty($baseImageNumber)) {
                                        $baseImageNumber = str_replace('_image1.jpg', '', $baseImageNumber);
                                    }
                                    
                                    // Get the actual image paths
                                    $actualImage1 = $baseImageNumber . '_image1.jpg';
                                    $actualImage2 = $baseImageNumber . '_image2.jpg';
                                    $actualImage3 = $baseImageNumber . '_image3.jpg';
                                    
                                    // Verify file existence
                                    $actualImage1 = file_exists(__DIR__ . '/uploads/' . $actualImage1) ? $actualImage1 : false;
                                    $actualImage2 = file_exists(__DIR__ . '/uploads/' . $actualImage2) ? $actualImage2 : false;
                                    $actualImage3 = file_exists(__DIR__ . '/uploads/' . $actualImage3) ? $actualImage3 : false;
                                    
                                    // Create array of valid images
                                    $validImages = array_filter([$actualImage1, $actualImage2, $actualImage3]);
                                ?>
                                <div class="image-cell">
                                    <?php if ($actualImage1): ?>
                                        <img src="uploads/<?php echo $actualImage1; ?>" 
                                             alt="Thumbnail" 
                                             style="width: 80px; height: 80px; cursor: pointer; object-fit: cover; border-radius: 4px;"
                                             onclick='openCarousel(<?php echo json_encode($validImages); ?>)'>
                                    <?php else: ?>
                                        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Crect width='80' height='80' fill='%23f0f0f0'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='12' fill='%23999'%3ENo Image%3C/text%3E%3C/svg%3E"
                                             alt="No Image" 
                                             style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                                        <?php echo "<br><small style='color: red;'>Image not found</small>"; ?>
                                    <?php endif; ?>
    </div>
</td>

<td>
    <span class="status-badge <?php echo htmlspecialchars(strtolower($row['status'])); ?>">
        <?php echo htmlspecialchars(ucfirst($row['status'])); ?>
    </span>
</td>

<td class="action-cell">
                                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="status-form">
        <input type="hidden" name="outfit_id" value="<?php echo htmlspecialchars($row['outfit_id']); ?>">
        <input type="hidden" name="user_email" value="<?php echo htmlspecialchars($row['email']); ?>">
        <input type="hidden" name="user_name" value="<?php echo htmlspecialchars($row['name']); ?>">

        <select name="status" class="status-select">
            <option value="pending" <?php echo ($row['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
            <option value="approved" <?php echo ($row['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
            <option value="rejected" <?php echo ($row['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
        </select>
        <p></p>
        <button type="submit" name="update_status" class="update-button">Update</button>
    </form>
</td>
               </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- Enlarged Image Carousel Modal -->
<div id="carouselModal">
    <span id="closeCarouselModal" class="close">&times;</span>
    <button class="prev-btn" id="prevModalImg">&#10094;</button>
    <div class="modal-carousel">
        <img id="modalCarouselImage" src="" alt="Enlarged Preview">
    </div>
    <button class="next-btn" id="nextModalImg">&#10095;</button>
</div>

    <!-- Add this modal structure just before closing body tag -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <button class="close-button">&times;</button>
            <div class="modal-image-container">
                <img id="carouselImage" src="" alt="Carousel Image">
                <button class="carousel-btn prev">&larr;</button>
                <button class="carousel-btn next">&rarr;</button>
            </div>
            <div class="image-counter">Image <span id="currentImage">1</span> of <span id="totalImages">3</span></div>
        </div>
    </div>

    <script>
        let currentImageIndex = 0;
        let images = [];

        function openCarousel(imageUrls) {
            // Clear previous images and debug output
            images = [];
            console.log('Received image URLs:', imageUrls);
            
            // Ensure imageUrls is an array
            if (!Array.isArray(imageUrls)) {
                console.error('imageUrls is not an array:', imageUrls);
                return;
            }
            
            // Filter out empty values and create full paths
            images = imageUrls
                .filter(url => url && url !== 'false' && url !== 'null' && url !== '' && url !== 'No')
                .map(url => {
                    const path = url.includes('uploads/') ? url : 'uploads/' + url;
                    console.log('Processing image path:', path);
                    return path;
                });
            
            console.log('Final images array:', images);
            
            if (images.length > 0) {
                currentImageIndex = 0;
                const modal = document.getElementById('imageModal');
                const carouselImage = document.getElementById('carouselImage');
                
                // Update UI elements
                document.getElementById('totalImages').textContent = images.length;
                
                // Show/hide navigation buttons
                const prevBtn = document.querySelector('.carousel-btn.prev');
                const nextBtn = document.querySelector('.carousel-btn.next');
                prevBtn.style.display = images.length > 1 ? 'block' : 'none';
                nextBtn.style.display = images.length > 1 ? 'block' : 'none';
                
                // Show first image
                carouselImage.src = images[0];
                updateImageCounter();
                
                // Display modal
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                // Set up event handlers
                document.addEventListener('keydown', handleKeyPress);
            }
        }

        function updateImageCounter() {
            const currentElement = document.getElementById('currentImage');
            const totalElement = document.getElementById('totalImages');
            if (currentElement && totalElement) {
                currentElement.textContent = (currentImageIndex + 1);
                totalElement.textContent = images.length;
            }
        }

        function changeImage(direction) {
            if (images.length > 0) {
                currentImageIndex = (currentImageIndex + direction + images.length) % images.length;
                const carouselImage = document.getElementById('carouselImage');
                if (carouselImage) {
                    console.log('Changing to image:', images[currentImageIndex]);
                    carouselImage.src = images[currentImageIndex];
                    updateImageCounter();
                }
            }
        }

        function handleKeyPress(e) {
            const modal = document.getElementById('imageModal');
            if (modal.style.display === 'block') {
                switch (e.key) {
                    case 'ArrowLeft':
                        changeImage(-1);
                        break;
                    case 'ArrowRight':
                        changeImage(1);
                        break;
                    case 'Escape':
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                        document.removeEventListener('keydown', handleKeyPress);
                        break;
                }
            }
        }

        // Clean up event listener when modal is closed
        document.querySelector('.close-button').addEventListener('click', function() {
            document.getElementById('imageModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            document.removeEventListener('keydown', handleKeyPress);
        });

        // Update the openCarousel function to include this after setting up other event handlers
        const closeButton = document.querySelector('.close-button');
        closeButton.onclick = () => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            document.removeEventListener('keydown', handleKeyPress);
        };

        // Add search functionality
        document.getElementById('outfitSearchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.admin-table tbody tr');
            
            tableRows.forEach(row => {
                let matchFound = false;
                
                // Check all cells except the last one (which contains the action buttons)
                const cells = row.querySelectorAll('td:not(:last-child)');
                
                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(searchTerm)) {
                        matchFound = true;
                    }
                });
                
                // Show/hide based on match
                row.style.display = matchFound ? '' : 'none';
            });
            
            // Show message if no results
            const visibleRows = document.querySelectorAll('.admin-table tbody tr[style="display: "]').length + 
                               document.querySelectorAll('.admin-table tbody tr:not([style])').length;
                               
            const noResultsMsg = document.getElementById('no-results-message');
            
            if (visibleRows === 0 && searchTerm !== '') {
                if (!noResultsMsg) {
                    const table = document.querySelector('.admin-table');
                    const tbody = table.querySelector('tbody');
                    const tr = document.createElement('tr');
                    tr.id = 'no-results-message';
                    tr.innerHTML = `<td colspan="13" style="text-align: center; padding: 20px;">No outfits found matching "${searchTerm}"</td>`;
                    tbody.appendChild(tr);
                } else {
                    noResultsMsg.querySelector('td').textContent = `No outfits found matching "${searchTerm}"`;
                    noResultsMsg.style.display = '';
                }
            } else if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        });
</script>
</body>
</html>