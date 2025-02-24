<?php
include 'connect.php';

// Admin credentials
$admin_name = "Anna Thomas";
$admin_email = "keerthanathomas9697@gmail.com";
$admin_password = "adminpassword"; // Change this to a more secure password
$admin_phone = "8075643884";
$admin_role = "admin";

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Check if admin already exists
$check_stmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE email = ?");
$check_stmt->bind_param("s", $admin_email);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    // Insert admin user
    $stmt = $conn->prepare("INSERT INTO tbl_users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $admin_name, $admin_email, $hashed_password, $admin_phone, $admin_role);
    
    if ($stmt->execute()) {
        echo "Admin account created successfully!<br>";
        echo "Email: " . $admin_email . "<br>";
        echo "Password: " . $admin_password . "<br>";
        echo "Please save these credentials and delete this file after use.";
    } else {
        echo "Error creating admin account: " . $conn->error;
    }
    $stmt->close();
} else {
    echo "Admin account already exists!";
}

$check_stmt->close();
$conn->close();
?>
