<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Redirect to login page if not authenticated
    header('Location: index.php');
    exit;
}

header('Content-Type: application/json');

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "LOCK";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get the last known notification ID from the query parameter
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

// Fetch only new notifications
$sql = "SELECT * FROM notifications WHERE id > ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $last_id);
$stmt->execute();
$result = $stmt->get_result();

// Prepare an array to store new notifications
$new_notifications = [];

// Fetch new notifications
while ($row = $result->fetch_assoc()) {
    $new_notifications[] = [
        'id' => htmlspecialchars($row['id']),
        'message' => htmlspecialchars($row['message']),
        'created_at' => htmlspecialchars($row['created_at'])
    ];
}

// Send new notifications as JSON
echo json_encode($new_notifications);

$stmt->close();
$conn->close();
?>
