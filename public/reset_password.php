<?php
session_start();
include 'connect.php';

if (!isset($_GET['token'])) {
    header("Location: ls.php");
    exit;
}

$token = $_GET['token'];
$current_time = date('Y-m-d H:i:s');

// Verify token and check expiry
$stmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE reset_token = ? AND reset_token_expiry > ?");
$stmt->bind_param("ss", $token, $current_time);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $error = "Invalid or expired reset link.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($error)) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE tbl_users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $hashed_password, $token);
        
        if ($stmt->execute()) {
            $success = "Password has been reset successfully. You can now login with your new password.";
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Clover Outfit Rentals</title>
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
        <h2>Reset Password</h2>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
            <a href="ls.php" class="back-link">Back to Login</a>
        <?php elseif (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
            <a href="ls.php" class="back-link">Back to Login</a>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <input type="password" name="password" placeholder="New Password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                </div>
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
