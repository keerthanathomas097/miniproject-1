<?php
session_start();
include 'connect.php';

require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $outfit_id = $_POST['outfit_id'];
    $new_status = $_POST['status'];
    $user_email = $_POST['user_email'];
    $user_name = $_POST['user_name'];

    // ✅ Update status in database
    $update_query = "UPDATE tbl_outfit SET status = ? WHERE outfit_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $outfit_id);
    
    if ($stmt->execute()) {
        // If status is approved, fetch user_id from tbl_outfit and update user role to lender
        if ($new_status == "approved") {
            // First, get the user_id associated with this outfit
            $get_user_query = "SELECT user_id FROM tbl_outfit WHERE outfit_id = ?";
            $user_stmt = $conn->prepare($get_user_query);
            $user_stmt->bind_param("i", $outfit_id);
            $user_stmt->execute();
            $result = $user_stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $user_id = $row['user_id'];
                
                // Now update the user role to lender
                $update_role_query = "UPDATE tbl_users SET role = 'lender' WHERE user_id = ?";
                $role_stmt = $conn->prepare($update_role_query);
                $role_stmt->bind_param("i", $user_id);
                
                if ($role_stmt->execute()) {
                    $_SESSION['message'] = "Status updated and user role changed to lender.";
                } else {
                    $_SESSION['message'] = "Status updated, but user role could not be changed.";
                }
                $role_stmt->close();
            } else {
                $_SESSION['message'] = "Status updated, but couldn't find associated user.";
            }
            $user_stmt->close();
        } else {
            $_SESSION['message'] = "Status updated successfully.";
        }
        
        // ✅ Initialize PHPMailer
        $mail = new PHPMailer(true);

        try {
            // ✅ SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'cloveroutfitrentals@gmail.com'; // Your email
            $mail->Password = 'ycqe ywxu vemp qnso'; // Use an App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // ✅ Email Details
            $mail->setFrom('cloveroutfitrentals@gmail.com', 'Clover Outfit Rentals');
            $mail->addAddress($user_email, $user_name);
            $mail->isHTML(true);

            // ✅ Email Subject & Body Based on Status
            if ($new_status == "approved") {
                $mail->Subject = "Lending Request Approved!";
                $mail->Body = "
                    <h2>Dear $user_name,</h2>
                    <p>Your request for lending has been <strong style='color:green;'>approved</strong>!</p>
                    <p>Your account has been upgraded to <strong>lender status</strong>. You can now manage your outfit in the lender dashboard.</p>
                    <a href='http://yourwebsite.com/lender_dashboard.php' 
                        style='display:inline-block; padding:12px 18px; color:#fff; background-color:maroon; text-decoration:none; border-radius:5px;'>
                        View Now
                    </a>
                    <p>Thank you for using our platform!</p>
                ";
            } elseif ($new_status == "rejected") {
                $mail->Subject = "Lending Request Rejected";
                $mail->Body = "
                    <h2>Dear $user_name,</h2>
                    <p>We regret to inform you that your lending request has been <strong style='color:red;'>rejected</strong> as your outfit does not meet the criteria.</p>
                    <p>You may review our guidelines and submit another request.</p>
                    <p>For any questions, please contact our support team.</p>
                ";
            }

            // ✅ Send the email
            $mail->send();
            $_SESSION['message'] .= " Email sent to $user_email.";
        } catch (Exception $e) {
            $_SESSION['message'] .= " However, email could not be sent. Error: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['message'] = "Error updating status.";
    }

    // Redirect back
    header("Location: admin_dashboard.php");
    exit();
}
?>