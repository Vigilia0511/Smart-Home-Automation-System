<?php
// Start a session
session_start();

// Database connection setup
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SmartHome1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the ID and password from GET request
$user_id = $_GET['id'];
$password = $_GET['password'];

// SQL query to fetch user by ID
$sql = "SELECT * FROM users WHERE id = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $user_id, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Start session and set user ID
    $_SESSION['user_id'] = $user_id;

    // Return success response
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Login successful."]);
} else {
    // Invalid ID or password
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid ID or password."]);
}

$stmt->close();
$conn->close();
?>
