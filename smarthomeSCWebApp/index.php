<?php
// Get the 'notif' parameter from the URL
$notif = isset($_GET['notif']) ? $_GET['notif'] : '';

// Check if the parameter is not empty
if (!empty($notif)) {
    // Database credentials
    $servername = "localhost";
    $username = "root"; // Your username
    $password = ""; // Your password
    $dbname = "SmartHome1"; // Your database name

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert the notif value into the database
    $stmt = $conn->prepare("INSERT INTO notifications (message) VALUES (?)");
    $stmt->bind_param("s", $notif);

    if ($stmt->execute()) {
        echo "Notification saved successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close connection
    $stmt->close();
    $conn->close();
} else {
    echo "No notification to save.";
}
?>

