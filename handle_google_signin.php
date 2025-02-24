<?php
session_start();
include 'connect.php';

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $google_id = $data['id'];
    $name = $data['name'];
    $email = $data['email'];
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id, name FROM tbl_users WHERE google_id = ? OR email = ?");
    $stmt->bind_param("ss", $google_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User exists - log them in
        $user = $result->fetch_assoc();
        $_SESSION['loggedin'] = true;
        $_SESSION['id'] = $user['user_id'];
        $_SESSION['username'] = $user['name'];
        echo json_encode(['success' => true]);
    } else {
        // New user - create account
        $stmt = $conn->prepare("INSERT INTO tbl_users (name, email, google_id) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $google_id);
        
        if ($stmt->execute()) {
            $_SESSION['loggedin'] = true;
            $_SESSION['id'] = $conn->insert_id;
            $_SESSION['username'] = $name;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create account']);
        }
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
}
use Kreait\Firebase\Factory;

$factory = (new Factory)
    ->withServiceAccount('/path/to/firebase_credentials.json')
    ->withDatabaseUri('https://my-project-default-rtdb.firebaseio.com');

$auth = $factory->createAuth();
$realtimeDatabase = $factory->createDatabase();
$cloudMessaging = $factory->createMessaging();
$remoteConfig = $factory->createRemoteConfig();
$cloudStorage = $factory->createStorage();
$firestore = $factory->createFirestore();
$conn->close();
?>
