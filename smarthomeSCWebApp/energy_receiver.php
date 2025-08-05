<?php
$servername = "localhost";  // Change if your database is on a different machine
$username = "root";         // Your database username
$password = "";             // Your database password
$dbname = "Energy";      // Your database name

// Connect to MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Get data from Raspberry Pi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $total_energy = $_POST["total_energy"];

    // Insert data into the database
    $sql = "INSERT INTO energy_consumption (total_energy) VALUES ('$total_energy')";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Energy data saved successfully.";
    } else {
        echo "❌ Error: " . $sql . "<br>" . $conn->error;
    }
}

// Close connection
$conn->close();
?>
