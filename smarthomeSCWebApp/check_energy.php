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

// Fetch the latest energy consumption record
$sql = "SELECT total_energy FROM energy_consumption ORDER BY timestamp DESC LIMIT 1";
$result = $conn->query($sql);

// Prepare response
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['total_energy' => htmlspecialchars($row['total_energy'])]);
} else {
    echo json_encode(['total_energy' => 'No data available']);
}

$conn->close();
?>
