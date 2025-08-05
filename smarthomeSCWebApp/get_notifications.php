<?php
set_time_limit(0); // Prevent the script from timing out
ignore_user_abort(true); // Keep running even if the connection to the client is lost

// Database connection setup
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SmartHome1";

// Create connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    exit;
}

while (true) {
    $apiUrl = "http://192.168.0.237:5000/get_notification";

    // Use cURL to fetch the notification from the API
    $notificationCurl = curl_init($apiUrl);
    curl_setopt($notificationCurl, CURLOPT_RETURNTRANSFER, true);
    $notificationsRaw = curl_exec($notificationCurl);

    if (curl_errno($notificationCurl)) {
        error_log("cURL error: " . curl_error($notificationCurl));
        curl_close($notificationCurl);
        sleep(5); // Retry after 5 seconds
        continue;
    } else {
        $notificationsDecoded = json_decode($notificationsRaw, true);

        if ($notificationsDecoded && isset($notificationsDecoded['notification'])) {
            $notificationMessage = htmlspecialchars($notificationsDecoded['notification']);

            // Check the most recent notification in the database
            $result = $conn->query("SELECT message FROM notifications1 ORDER BY id DESC LIMIT 1");
            if ($result) {
                $lastNotification = $result->fetch_assoc();
                if ($lastNotification && $lastNotification['message'] === $notificationMessage) {
                    // Skip inserting if the message is the same as the last one
                    error_log("Duplicate notification: The message is the same as the last one.");
                    $result->free();
                    sleep(5); // Wait 5 seconds before next check
                    continue;
                }
                $result->free();
            }

            // Insert the new notification into the database
            $stmt = $conn->prepare("INSERT INTO notifications1 (message) VALUES (?)");
            if ($stmt) {
                $stmt->bind_param("s", $notificationMessage);
                if (!$stmt->execute()) {
                    error_log("Error storing notification: " . $stmt->error);
                }
                $stmt->close();
            } else {
                error_log("Error preparing statement: " . $conn->error);
            }
        } else {
            error_log("Invalid response from API or no notification found.");
        }
    }

    curl_close($notificationCurl);

    sleep(5); // Wait 5 seconds before the next execution
}

$conn->close();
?>
