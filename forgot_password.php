<?php
session_start();
include 'connect.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendResetEmail($email, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'cloveroutfitrentals@gmail.com';
        $mail->Password = 'dxrh atrf fgqr dlzp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('cloveroutfitrentals@gmail.com', 'Clover Outfit Rentals');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';
        $resetLink = "http://localhost/miniproject1/reset_password.php?token=" . $token;
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #8d2626;'>Password Reset Request</h2>
                <p>You have requested to reset your password. Click the button below to proceed:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$resetLink' 
                       style='background-color: #8d2626; 
                              color: white; 
                              padding: 12px 30px; 
                              text-decoration: none; 
                              border-radius: 5px;
                              display: inline-block;'>
                        Reset Password
                    </a>
                </p>
                <p>If you didn't request this, you can safely ignore this email.</p>
                <p>This link will expire in 1 hour for security reasons.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Reset email sending error: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE email = ? AND is_verified = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $conn->prepare("UPDATE tbl_users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
            $stmt->bind_param("sss", $token, $expiry, $email);
            
            if ($stmt->execute() && sendResetEmail($email, $token)) {
                $success = "Password reset instructions have been sent to your email.";
            } else {
                $error = "Failed to process your request. Please try again.";
            }
        } else {
            $error = "No verified account found with this email address.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Clover Outfit Rentals</title>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f6f5f7;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        
        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
            padding: 40px;
            width: 400px;
            max-width: 90%;
            text-align: center;
        }
        
        h2 {
            color: #8d2626;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        button {
            background-color: #8d2626;
            color: white;
            padding: 12px 45px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: transform 80ms ease-in;
        }
        
        button:hover {
            background-color: #702020;
        }
        
        .error {
            color: #ff3860;
            margin-top: 10px;
        }
        
        .success {
            color: #23d160;
            margin-top: 10px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #8d2626;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
        
        <a href="ls.php" class="back-link">Back to Login</a>
    </div>
</body>
</html>
