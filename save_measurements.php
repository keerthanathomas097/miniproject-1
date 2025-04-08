<?php
ob_start(); // Start output buffering
session_start();
include 'connect.php';

// Clear any previous output
ob_clean();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
error_log("POST data received: " . print_r($_POST, true));

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get and validate form data
        $user_id = intval($_SESSION['id']);
        $outfit_id = isset($_POST['outfit_id']) ? intval($_POST['outfit_id']) : 0;
        $height = isset($_POST['height']) ? floatval($_POST['height']) : 0;
        $shoulder = isset($_POST['shoulder']) ? floatval($_POST['shoulder']) : 0;
        $bust = isset($_POST['bust']) ? floatval($_POST['bust']) : 0;
        $waist = isset($_POST['waist']) ? floatval($_POST['waist']) : 0;

        // Debug log the received values
        error_log("Received values - user_id: $user_id, outfit_id: $outfit_id, height: $height, shoulder: $shoulder, bust: $bust, waist: $waist");
        
        // Validate required fields
        if ($outfit_id <= 0) {
            throw new Exception("Invalid outfit ID");
        }

        if ($height <= 0 || $shoulder <= 0 || $bust <= 0 || $waist <= 0) {
            throw new Exception("All measurements must be greater than 0");
        }

        // Convert dates
        $start_date_str = $_POST['start_date'] ?? '';
        $end_date_str = $_POST['end_date'] ?? '';
        
        error_log("Received dates - start: $start_date_str, end: $end_date_str");

        if (empty($start_date_str) || empty($end_date_str)) {
            throw new Exception("Dates are required");
        }

        // Convert dates to MySQL format
        $start_date = date('Y-m-d', strtotime($start_date_str));
        $end_date = date('Y-m-d', strtotime($end_date_str));

        if ($start_date === false || $end_date === false) {
            throw new Exception("Invalid date format");
        }

        // First, check if a measurement already exists for this user and outfit
        $check_query = "SELECT id FROM tbl_measurements WHERE user_id = ? AND outfit_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $user_id, $outfit_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing measurement
            $query = "UPDATE tbl_measurements 
                     SET height = ?, shoulder = ?, bust = ?, waist = ?, 
                         start_date = ?, end_date = ? 
                     WHERE user_id = ? AND outfit_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ddddssii", 
                $height, $shoulder, $bust, $waist,
                $start_date, $end_date, $user_id, $outfit_id
            );
        } else {
            // Insert new measurement
            $query = "INSERT INTO tbl_measurements 
                     (user_id, outfit_id, height, shoulder, bust, waist, start_date, end_date)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($query);
            $stmt->bind_param("iiddddss", 
                $user_id, $outfit_id, $height, $shoulder, $bust, $waist,
                $start_date, $end_date
            );
        }

        // Execute the query and check for success
if ($stmt->execute()) {
            error_log("Successfully saved measurements to database");
            echo json_encode([
        'success' => true,
                'redirect' => 'checkout.php?outfit_id=' . $outfit_id,
                'message' => 'Measurements saved successfully'
            ]);
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }

    } catch (Exception $e) {
        error_log("Error in save_measurements.php: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

// Debug log the final response
error_log("Script completed execution");
exit();
?>