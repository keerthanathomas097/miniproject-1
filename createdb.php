<?php
include 'connect.php';

$database_name = "miniproject";
$sql = "CREATE DATABASE $database_name";

if (mysqli_query($conn, $sql)) {
    echo "Database created successfully";
} else {
    echo "Error creating database: " . mysqli_error($conn);
}

mysqli_close($conn);
?>

