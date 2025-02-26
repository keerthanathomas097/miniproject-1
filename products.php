<?php
session_start();
include 'connect.php';

// Check login status
if (!isset($_SESSION['loggedin'])) {
    header("Location: ls.php");
    exit();
}

// Different views based on user role
if ($_SESSION['role'] === 'lender') {
    // Show lender's products
    $stmt = $conn->prepare("SELECT * FROM tbl_outfit WHERE lender_id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
} elseif ($_SESSION['role'] === 'admin') {
    // Show all products with management options
    $stmt = $conn->prepare("SELECT * FROM tbl_outfit");
} else {
    // Show available products for customers
    $stmt = $conn->prepare("SELECT * FROM tbl_outfit WHERE status = 'available'");
}

$stmt->execute();
$results = $stmt->get_result(); 