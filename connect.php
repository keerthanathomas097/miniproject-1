<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "minipro1"; // Ensure this matches the database name you created

// Connect to MySQL server and select the database
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo " <br>";
}
?>
