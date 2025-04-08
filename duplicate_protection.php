<?php
// This is a helper function to prevent duplicate order submissions
function preventDuplicateOrder($conn, $order_reference) {
    // Check if order already exists
    $check_sql = "SELECT id FROM tbl_orders WHERE order_reference = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    if (!$check_stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return false; // Error in preparation
    }
    
    $check_stmt->bind_param("s", $order_reference);
    
    if (!$check_stmt->execute()) {
        error_log("Failed to execute statement: " . $check_stmt->error);
        $check_stmt->close();
        return false; // Error in execution
    }
    
    $result = $check_stmt->get_result();
    $exists = $result->num_rows > 0;
    $check_stmt->close();
    
    if ($exists) {
        return false; // Order already exists
    }
    
    // Also check session to prevent double submission
    if (isset($_SESSION['last_order_reference']) && $_SESSION['last_order_reference'] === $order_reference) {
        $time_diff = time() - $_SESSION['last_order_time'];
        if ($time_diff < 5) { // Within 5 seconds
            return false; // Likely a duplicate submission
        }
    }
    
    // Set session variables to track this submission
    $_SESSION['last_order_reference'] = $order_reference;
    $_SESSION['last_order_time'] = time();
    
    return true; // Safe to proceed
}
?> 