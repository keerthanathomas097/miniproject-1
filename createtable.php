<?php
include 'connect.php';

$database_name = "miniproject ";

// Select the database
mysqli_select_db($conn, $database_name);

$sql = "CREATE TABLE tbl_users (
    user_id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'User ID starts from 100',
    name VARCHAR(50) NOT NULL COMMENT 'User name',
    email VARCHAR(50) NOT NULL COMMENT 'User email',
    password VARCHAR(255) NOT NULL COMMENT 'User password',
    phone INT COMMENT 'User phone number',
    address TEXT COMMENT 'User address',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation time',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Record update time'
) AUTO_INCREMENT=100";

if (mysqli_query($conn, $sql)) {
    echo "Table 'tbl_users' created successfully";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
