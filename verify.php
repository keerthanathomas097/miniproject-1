<?php
include 'connect.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Prepare statement to find user with this token
    $stmt = $conn->prepare("SELECT user_id, is_verified FROM tbl_users WHERE verification_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($user['is_verified'] == 0) {
            // Update user as verified
            $update_stmt = $conn->prepare("UPDATE tbl_users SET is_verified = 1, verification_token = NULL WHERE user_id = ?");
            $update_stmt->bind_param("i", $user['user_id']);
            
            if ($update_stmt->execute()) {
                echo "<script>
                    alert('Email verification successful! You can now login.');
                    window.location.href = 'ls.php';
                </script>";
            } else {
                echo "<script>
                    alert('Verification failed. Please try again or contact support.');
                    window.location.href = 'ls.php';
                </script>";
            }
            $update_stmt->close();
        } else {
            echo "<script>
                alert('This account is already verified. You can login.');
                window.location.href = 'ls.php';
            </script>";
        }
    } else {
        echo "<script>
            alert('Invalid verification token.');
            window.location.href = 'ls.php';
        </script>";
    }
    $stmt->close();
} else {
    header("Location: ls.php");
    exit;
}
?>
