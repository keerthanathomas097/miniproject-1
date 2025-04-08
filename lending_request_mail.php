<?php
session_start();
include 'connect.php';

require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    // Validate and sanitize inputs
    $outfit_id = isset($_POST['outfit_id']) ? intval($_POST['outfit_id']) : 0;
    $new_status = isset($_POST['status']) ? htmlspecialchars($_POST['status']) : '';
    $user_email = isset($_POST['user_email']) ? htmlspecialchars($_POST['user_email']) : '';
    $user_name = isset($_POST['user_name']) ? htmlspecialchars($_POST['user_name']) : '';

    // Validate that we have all required data
    if (!$outfit_id || !$new_status || !$user_email || !$user_name) {
        $_SESSION['error'] = "Missing required information";
        header("Location: admin_dashboard.php");
        exit();
    }

    // ✅ Update status in database with error checking
    $update_query = "UPDATE tbl_outfit SET status = ? WHERE outfit_id = ?";
    $stmt = $conn->prepare($update_query);
    
    // Check if prepare failed
    if ($stmt === false) {
        $_SESSION['error'] = "Database prepare failed: " . $conn->error;
        header("Location: admin_dashboard.php");
        exit();
    }

    // Bind parameters and execute
    $stmt->bind_param("si", $new_status, $outfit_id);
    
    if ($stmt->execute()) {
        // If status is approved, update user role
        if ($new_status == "approved") {
            // Get the email associated with this outfit
            $get_user_query = "SELECT email FROM tbl_outfit WHERE outfit_id = ?";
            $user_stmt = $conn->prepare($get_user_query);
            
            if ($user_stmt === false) {
                $_SESSION['error'] = "Failed to prepare user query: " . $conn->error;
                header("Location: admin_dashboard.php");
                exit();
            }

            $user_stmt->bind_param("i", $outfit_id);
            $user_stmt->execute();
            $result = $user_stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $outfit_email = $row['email'];
                
                // Update user role to lender
                $update_role_query = "UPDATE tbl_users SET role = 'lender' WHERE email = ?";
                $role_stmt = $conn->prepare($update_role_query);
                
                if ($role_stmt === false) {
                    $_SESSION['error'] = "Failed to prepare role update: " . $conn->error;
                    header("Location: admin_dashboard.php");
                    exit();
                }

                $role_stmt->bind_param("s", $outfit_email);
                
                if ($role_stmt->execute()) {
                    $_SESSION['message'] = "Status updated and user role changed to lender.";
                } else {
                    $_SESSION['message'] = "Status updated, but user role could not be changed.";
                }
                $role_stmt->close();
            }
            $user_stmt->close();
        } else {
            $_SESSION['message'] = "Status updated successfully.";
        }
        
        // ✅ Send email notification
        try {
            $mail = new PHPMailer(true);
            
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'cloveroutfitrentals@gmail.com';
            $mail->Password = 'ycqe ywxu vemp qnso';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('cloveroutfitrentals@gmail.com', 'Clover Outfit Rentals');
            $mail->addAddress($user_email, $user_name);
            $mail->isHTML(true);

            // Email content based on status
            if ($new_status == "approved") {
                $mail->Subject = "Lending Request Approved!";
                $mail->Body = "
                    <h2>Dear $user_name,</h2>
                    <p>Your request for lending has been <strong style='color:green;'>approved</strong>!</p>
                    <p>Your account has been upgraded to <strong>lender status</strong>. You can now manage your outfit in the lender dashboard.</p>
                    <p>Thank you for using our platform!</p>
                ";
            } elseif ($new_status == "rejected") {
                $mail->Subject = "Lending Request Rejected";
                $mail->Body = "
                    <h2>Dear $user_name,</h2>
                    <p>We regret to inform you that your lending request has been <strong style='color:red;'>rejected</strong>.</p>
                    <p>You may review our guidelines and submit another request.</p>
                    <p>For any questions, please contact our support team.</p>
                ";
            }

            $mail->send();
            $_SESSION['message'] .= " Email sent successfully.";
            
        } catch (Exception $e) {
            $_SESSION['message'] .= " However, email could not be sent. Error: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['error'] = "Error updating status: " . $stmt->error;
    }

    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: admin_dashboard.php");
    exit();
}
?>