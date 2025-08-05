<?php
header('Content-Type: application/json');

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SmartHome1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get the last known notification ID from the query parameter
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

// Fetch only new notifications
$sql = "SELECT * FROM notifications1 WHERE id > ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $last_id);
$stmt->execute();
$result = $stmt->get_result();

// Prepare an array to store new notifications
$new_notifications = [];

// Define which messages increase or decrease the active devices count
$active_messages = [
    "Door Opened" => 1,
    "Fan Open" => 1,
    "Garage Open" => 1,
    "Indoor Lights On" => 1,
    "Outdoor Lights On" => 1
];

$inactive_messages = [
    "Door Locked" => -1,
    "Fan Off" => -1,
    "Garage Closing" => -1,
    "Indoor Lights Off" => -1,
    "Outdoor Lights Off" => -1
];

// Fetch new notifications
while ($row = $result->fetch_assoc()) {
    $message = htmlspecialchars($row['message']);
    $id = htmlspecialchars($row['id']);
    $created_at = htmlspecialchars($row['created_at']);

    // Check if the message affects the active devices count
    if (isset($active_messages[$message])) {
        $new_notifications[] = [
            'id' => $id,
            'message' => $message,
            'created_at' => $created_at,
            'count_change' => $active_messages[$message] // Indicates an increase in active devices
        ];
    } elseif (isset($inactive_messages[$message])) {
        $new_notifications[] = [
            'id' => $id,
            'message' => $message,
            'created_at' => $created_at,
            'count_change' => $inactive_messages[$message] // Indicates a decrease in active devices
        ];
    } else {
        // If the message doesn't affect the active devices count, include it without a count change
        $new_notifications[] = [
            'id' => $id,
            'message' => $message,
            'created_at' => $created_at,
            'count_change' => 0 // No change in active devices count
        ];
    }
}

// Send new notifications as JSON
echo json_encode($new_notifications);

$stmt->close();
$conn->close();
?>