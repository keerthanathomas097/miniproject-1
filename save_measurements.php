<?php
session_start();
include 'connect.php';

// Prevent any output before headers
ob_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display errors to prevent them from corrupting JSON

// Set response header to JSON
header('Content-Type: application/json');

// Clear any previous output
ob_clean();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get and validate form data
        $user_id = $_SESSION['id'];
        $outfit_id = isset($_POST['outfit_id']) ? intval($_POST['outfit_id']) : 0;
        $height = isset($_POST['height']) ? floatval($_POST['height']) : 0;
        $shoulder = isset($_POST['shoulder']) ? floatval($_POST['shoulder']) : 0;
        $bust = isset($_POST['bust']) ? floatval($_POST['bust']) : 0;
        $waist = isset($_POST['waist']) ? floatval($_POST['waist']) : 0;
        
        // Get and validate dates
        $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
        $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';

        // Validate date format
        $date_format = 'Y-m-d';
        $start_date_obj = DateTime::createFromFormat($date_format, $start_date);
        $end_date_obj = DateTime::createFromFormat($date_format, $end_date);

        if (!$start_date_obj || !$end_date_obj) {
            throw new Exception("Invalid date format. Expected format: YYYY-MM-DD");
        }

        // Format dates for MySQL
        $start_date = $start_date_obj->format('Y-m-d');
        $end_date = $end_date_obj->format('Y-m-d');

        // Validate required fields
        if ($outfit_id <= 0) {
            throw new Exception("Invalid outfit ID");
        }

        if ($height <= 0 || $shoulder <= 0 || $bust <= 0 || $waist <= 0) {
            throw new Exception("All measurements must be greater than 0");
        }

        if (empty($start_date) || empty($end_date)) {
            throw new Exception("Dates are required");
        }

        // First, check if a measurement already exists for this user and outfit
        $check_query = "SELECT id FROM tbl_measurements WHERE user_id = ? AND outfit_id = ?";
        $check_stmt = $conn->prepare($check_query);
        if (!$check_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
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
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
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
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("iiddddss", 
                $user_id, $outfit_id, $height, $shoulder, $bust, $waist,
                $start_date, $end_date
            );
        }

        // Execute the query and check for success
        if ($stmt->execute()) {
            ob_clean(); // Clear any potential output before sending JSON
            echo json_encode([
                'success' => true,
                'redirect' => 'checkout.php?outfit_id=' . $outfit_id,
                'message' => 'Measurements saved successfully'
            ]);
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }

    } catch (Exception $e) {
        ob_clean(); // Clear any potential output before sending JSON
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    ob_clean(); // Clear any potential output before sending JSON
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

// End output buffering and flush
ob_end_flush();
?>