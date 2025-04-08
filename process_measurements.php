<?php
session_start();
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $outfit_id = $_POST['outfit_id'];
    $height = $_POST['height'];
    $shoulder = $_POST['shoulder'];
    $bust = $_POST['bust'];
    $waist = $_POST['waist'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Insert into database
    $sql = "INSERT INTO tbl_measurements (user_id, outfit_id, height, shoulder, bust, waist, start_date, end_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiddddss", 
        $_SESSION['id'],
        $outfit_id,
        $height,
        $shoulder,
        $bust,
        $waist,
        $start_date,
        $end_date
    );

    if ($stmt->execute()) {
        // Redirect to checkout page
        header("Location: checkout.php?outfit_id=" . $outfit_id);
        exit();
    } else {
        echo "Error saving measurements";
    }
}
?> 