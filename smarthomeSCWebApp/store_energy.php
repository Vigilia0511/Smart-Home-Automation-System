<?php
set_time_limit(0); // Prevent the script from timing out
ignore_user_abort(true); // Keep running even if the client disconnects

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
    $apiUrl = "http://192.168.0.237:5000/get_energy"; // Change IP if needed

    // Use cURL to fetch energy consumption data from Raspberry Pi
    $energyCurl = curl_init($apiUrl);
    curl_setopt($energyCurl, CURLOPT_RETURNTRANSFER, true);
    $energyRaw = curl_exec($energyCurl);

    if (curl_errno($energyCurl)) {
        error_log("cURL error: " . curl_error($energyCurl));
        curl_close($energyCurl);
        sleep(5); // Retry after 5 seconds
        continue;
    } else {
        $energyDecoded = json_decode($energyRaw, true);

        if ($energyDecoded && isset($energyDecoded['total_energy'])) {
            $totalEnergy = htmlspecialchars($energyDecoded['total_energy']);

            // Check the most recent energy value in the database
            $result = $conn->query("SELECT total_energy FROM energy_consumption ORDER BY id DESC LIMIT 1");
            if ($result) {
                $lastEnergy = $result->fetch_assoc();
                if ($lastEnergy && $lastEnergy['total_energy'] === $totalEnergy) {
                    // Skip inserting if the value is the same as the last one
                    error_log("Duplicate energy value: The value is the same as the last one.");
                    $result->free();
                    sleep(5); // Wait 5 seconds before next check
                    continue;
                }
                $result->free();
            }

            // Insert the new energy data into the database
            $stmt = $conn->prepare("INSERT INTO energy_consumption (total_energy) VALUES (?)");
            if ($stmt) {
                $stmt->bind_param("s", $totalEnergy);
                if (!$stmt->execute()) {
                    error_log("Error storing energy data: " . $stmt->error);
                }
                $stmt->close();
            } else {
                error_log("Error preparing statement: " . $conn->error);
            }
        } else {
            error_log("Invalid response from API or no energy data found.");
        }
    }

    curl_close($energyCurl);

    sleep(5); // Wait 5 seconds before the next execution
}

$conn->close();
?>
