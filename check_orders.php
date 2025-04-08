<?php
session_start();
include 'connect.php';

// Security check - only allow logged-in users
if (!isset($_SESSION['loggedin'])) {
    die("Please log in to access this page");
}

echo "<h1>Database Diagnostic Tool</h1>";

// Check table structure
echo "<h2>Table Structure</h2>";
$result = $conn->query("DESCRIBE tbl_orders");
if ($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error fetching table structure: " . $conn->error;
}

// Recent orders
echo "<h2>Recent Orders</h2>";
$orders = $conn->query("SELECT * FROM tbl_orders ORDER BY id DESC LIMIT 10");
if ($orders) {
    if ($orders->num_rows > 0) {
        echo "<table border='1'><tr>";
        // Get column names for headers
        $firstRow = $orders->fetch_assoc();
        foreach ($firstRow as $key => $value) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        // Display the first row we already fetched
        echo "<tr>";
        foreach ($firstRow as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
        
        // Display the rest of the rows
        while ($row = $orders->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No orders found.";
    }
} else {
    echo "Error fetching orders: " . $conn->error;
}

// Check session data
echo "<h2>Current Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check environment
echo "<h2>PHP Info</h2>";
echo "PHP Version: " . phpversion();
echo "<br>MySQL Client Version: " . $conn->client_info;
?> 