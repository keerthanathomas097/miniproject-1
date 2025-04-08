<?php
// Remove any whitespace or BOM markers
ob_start();

// Start session and set error reporting
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Clear any previous output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Set JSON headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    require_once 'connect.php';

    // Get and decode JSON input
    $raw_input = file_get_contents('php://input');
    error_log("Received input: " . $raw_input); // Debug log
    
    $data = json_decode($raw_input, true);
    if (!$data) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    if (!isset($data['email']) || !isset($data['name']) || !isset($data['uid'])) {
        throw new Exception('Missing required data');
    }

    // Sanitize input
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $name = htmlspecialchars($data['name']);
    $google_id = htmlspecialchars($data['uid']);
    $profile_picture = isset($data['imageUrl']) ? filter_var($data['imageUrl'], FILTER_SANITIZE_URL) : null;

    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, name, role FROM tbl_users WHERE email = ? OR google_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ss", $email, $google_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Existing user
        $user = $result->fetch_assoc();
        
        // Update google_id if it's not set
        if (empty($user['google_id'])) {
            $update = $conn->prepare("UPDATE tbl_users SET google_id = ?, profile_picture = ? WHERE user_id = ?");
            $update->bind_param("ssi", $google_id, $profile_picture, $user['user_id']);
            $update->execute();
        }

        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $user['user_id'];
        $_SESSION['username'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        $response = [
            'success' => true,
            'message' => 'Login successful',
            'redirect' => 'index.php'
        ];
        
        echo json_encode($response);
        exit;
    } else {
        // New user registration
        $role = 'user';
        $is_verified = 1;
        $phone = ''; // Set empty phone initially
        
        $insert = $conn->prepare("INSERT INTO tbl_users (name, email, password, phone, google_id, profile_picture, is_verified, role) VALUES (?, ?, NULL, ?, ?, ?, ?, ?)");
        
        if (!$insert) {
            throw new Exception("Prepare insert failed: " . $conn->error);
        }

        $insert->bind_param("sssssss", $name, $email, $phone, $google_id, $profile_picture, $is_verified, $role);
        
        if (!$insert->execute()) {
            throw new Exception("Insert failed: " . $insert->error);
        }

        $new_user_id = $conn->insert_id;

        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $new_user_id;
        $_SESSION['username'] = $name;
        $_SESSION['role'] = $role;

        $response = [
            'success' => true,
            'message' => 'Registration successful',
            'redirect' => 'index.php'
        ];
        
        echo json_encode($response);
        exit;
    }

} catch (Exception $e) {
    error_log("Google Auth Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Authentication failed: ' . $e->getMessage()
    ]);
    exit;
}
?>
