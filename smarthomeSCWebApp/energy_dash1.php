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

// Get the last ID from the query parameter
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

// Fetch new data
$sql = "SELECT * FROM energy_consumption WHERE id > $last_id ORDER BY timestamp DESC";
$result = $conn->query($sql);

$newEnergyData = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $newEnergyData[] = $row;
    }
}

echo json_encode($newEnergyData);
$conn->close();
?>