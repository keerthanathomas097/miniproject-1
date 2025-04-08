<?php
// Disable error output in the response
ini_set('display_errors', 0);
error_reporting(0);

// Start session and include database connection
session_start();
include 'connect.php';

// Set content type to JSON
header('Content-Type: application/json');

// Log the error to a file instead of displaying it
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'rental_errors.log');
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit();
    }

    // Check if order_id parameter exists
    if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit();
    }

    $order_id = (int)$_GET['order_id'];
    $user_id = $_SESSION['id'];

    // Fetch the lender's email
    $lender_stmt = $conn->prepare("SELECT email FROM tbl_users WHERE user_id = ?");
    if (!$lender_stmt) {
        throw new Exception("Error preparing lender query: " . $conn->error);
    }
    
    $lender_stmt->bind_param("i", $user_id);
    $lender_stmt->execute();
    $lender_result = $lender_stmt->get_result();
    
    if ($lender_result->num_rows === 0) {
        throw new Exception("Lender not found");
    }
    
    $lender = $lender_result->fetch_assoc();
    $lender_email = $lender['email'];
    $lender_stmt->close();

    // Comprehensive query to fetch all required details
    $query = "SELECT
                o.id as order_id,
                o.user_id as renter_user_id,
                o.outfit_id,
                o.order_reference,
                o.amount,
                o.rental_rate,
                o.security_deposit,
                o.payment_method,
                o.order_status,
                o.payment_status,
                o.created_at as order_date,
                o.updated_at,
                
                outfit.mrp,
                outfit.image1,
                outfit.email as lender_email,
                
                renter.name as renter_name,
                renter.email as renter_email,
                renter.phone as renter_phone,
                
                d.description_text,
                
                brand.subcategory_name as brand_name,
                type.subcategory_name as type_name,
                size.subcategory_name as size_name,
                
                m.start_date,
                m.end_date,
                m.height,
                m.bust,
                m.waist,
                m.hip,
                m.shoulder
                
              FROM tbl_orders o
              JOIN tbl_outfit outfit ON o.outfit_id = outfit.outfit_id
              JOIN tbl_users renter ON o.user_id = renter.user_id
              LEFT JOIN tbl_description d ON outfit.description_id = d.id
              LEFT JOIN tbl_subcategory brand ON outfit.brand_id = brand.id
              LEFT JOIN tbl_subcategory type ON outfit.type_id = type.id
              LEFT JOIN tbl_subcategory size ON outfit.size_id = size.id
              LEFT JOIN tbl_measurements m ON o.id = m.order_id
              WHERE o.id = ? AND outfit.email = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error preparing statement: ' . $conn->error);
    }

    $stmt->bind_param("is", $order_id, $lender_email);
    if (!$stmt->execute()) {
        throw new Exception('Database error executing query: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Try a simplified query to see if the order exists at all
        $basic_stmt = $conn->prepare("SELECT id FROM tbl_orders WHERE id = ?");
        $basic_stmt->bind_param("i", $order_id);
        $basic_stmt->execute();
        $basic_result = $basic_stmt->get_result();
        
        if ($basic_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
        } else {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to view this order']);
        }
        
        $basic_stmt->close();
        exit();
    }

    $rental = $result->fetch_assoc();
    $stmt->close();

    // Calculate rental duration if dates are available
    if (!empty($rental['start_date']) && !empty($rental['end_date'])) {
        $start = new DateTime($rental['start_date']);
        $end = new DateTime($rental['end_date']);
        $interval = $start->diff($end);
        $rental['duration'] = $interval->days . ' days';
    } else {
        $rental['duration'] = 'Not available';
    }

    // Success response with rental data
    echo json_encode(['success' => true, 'rental' => $rental]);
    exit();
    
} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit();
}
?> 