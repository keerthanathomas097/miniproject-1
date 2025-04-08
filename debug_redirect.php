<?php
session_start();
header('Content-Type: text/plain');

echo "=== Redirect Debugging ===\n\n";

echo "Current Session Data:\n";
print_r($_SESSION);
echo "\n\n";

echo "Local Storage Check (client-side only, will be empty here):\n";
echo "This script will check localStorage in your browser.\n\n";

echo "Recent Orders:\n";
include 'connect.php';

$user_id = $_SESSION['id'] ?? 0;
if ($user_id > 0) {
    $sql = "SELECT id, outfit_id, amount, created_at FROM tbl_orders WHERE user_id = ? ORDER BY id DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "Order #" . $row['id'] . " - Outfit: " . $row['outfit_id'] . " - Amount: " . $row['amount'] . " - Date: " . $row['created_at'] . "\n";
        }
    } else {
        echo "No orders found for user ID $user_id\n";
    }
} else {
    echo "Not logged in or invalid user ID\n";
}

echo "\n=== Debug Commands ===\n";
echo "To view your latest order: confirmation.php?order_id=[latest_id]\n";
echo "To check your session: check_order.php\n";
?>

<script>
// This will display localStorage info in the console
document.addEventListener('DOMContentLoaded', function() {
    console.log('Current localStorage data:');
    console.log('current_order_id:', localStorage.getItem('current_order_id'));
    
    const debugDiv = document.createElement('div');
    debugDiv.style.fontFamily = 'monospace';
    debugDiv.style.margin = '20px';
    debugDiv.style.padding = '10px';
    debugDiv.style.backgroundColor = '#f5f5f5';
    debugDiv.style.border = '1px solid #ddd';
    
    debugDiv.innerHTML = '<h3>Browser Storage</h3>' +
        '<p>localStorage.current_order_id: ' + localStorage.getItem('current_order_id') + '</p>' +
        '<p>sessionStorage.current_order_id: ' + sessionStorage.getItem('current_order_id') + '</p>';
    
    document.body.appendChild(debugDiv);
});
</script> 