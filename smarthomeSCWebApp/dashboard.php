<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SmartHome1";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user ID
$user_id = $_SESSION['user_id'];

// Get initial alerts count
$alerts_count_query = "SELECT COUNT(*) as count FROM notifications1";
$alerts_count_result = $conn->query($alerts_count_query);
$alerts_count_row = $alerts_count_result->fetch_assoc();
$alerts_count = $alerts_count_row['count'] ?? 0;

// Get the last notification ID
$last_id_query = "SELECT MAX(id) as max_id FROM notifications1";
$last_id_result = $conn->query($last_id_query);
$last_notification_id = $last_id_result->fetch_assoc()['max_id'] ?? 0;


?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Home Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
        }
        /* Side navigation */
        .side-nav {
            width: 250px;
            background-color: #333;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .side-nav .top-section {
            padding: 20px;
        }
        .side-nav h2 {
            color: #fff;
            margin-bottom: 20px;
        }
.side-nav a {
    display: flex;
    align-items: center;
    color: white;
    padding: 10px 15px;
    text-decoration: none;
    margin-bottom: 10px;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}
.side-nav a img.nav-icon {
    width: 20px;
    height: 20px;
    margin-right: 10px;
}
.side-nav a:hover {
    background-color: #575757;
}

        .side-nav .profile {
            padding: 20px;
            border-top: 1px solid #444;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .side-nav .profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }
        .side-nav .profile div {
            color: white;
        }
        .side-nav .profile div strong {
            display: block;
        }
        .side-nav .profile div span {
            font-size: 0.9em;
            color: #ccc;
        }
        .side-nav .logout {
            padding: 20px;
            border-top: 1px solid #444;
        }
        /* Content area */
        .content {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 20px;
            text-align: center;
        }
        .container {
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card {
            display: inline-block;
            width: 20%;
            margin: 1%;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .card h3 {
            margin: 0 0 10px;
        }
        .card p {
            margin: 0;
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;

        }
        th {
            background-color: #575757;
            color: white;
            text-align: center;
        }
        .new-notification {
            background-color: #e6f7ff;
            animation: fadeIn 1s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .video-feed {
            display: inline-block;
            width: 32%;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
    </style>
</head>
<body>
   <div class="side-nav">
    <div class="top-section">
        <h2>Smart Home</h2>
        <a href="dashboard.php"><img src="dashboard.png" alt="Dashboard Icon" class="nav-icon"> Dashboard</a>
        <a href="change.php"><img src="setting-removebg-preview.png" alt="Settings Icon" class="nav-icon"> Settings</a>
        <a href="https://localhost/MIT/app_interface.php"><img src="interface.png" alt="Interface Icon" class="nav-icon"> Interface</a>
        <a href="energy_dash.php"><img src="energy-removebg-preview.png" alt="Energy Icon" class="nav-icon"> Energy Consumption</a>
    </div>
    <div class="profile">
        <img src="profile-removebg-preview.png" alt="Profile Picture">
        <div>
            <strong>User ID</strong>
            <span>051123</span>
        </div>
    </div>
    <div class="logout">
        <a href="logout.php"><img src="logout-removebg-preview.png" alt="Logout Icon" class="nav-icon"> Logout</a>
    </div>
</div>


    <!-- Content Area -->
    <div class="content">
        <div class="container">
<div class="card">
    <h3>Active Devices</h3>
    <p id="active-devices-count">1</p> <!-- Add an ID for dynamic updates -->
</div>
            <div class="card">
                <h3>Energy Consumption</h3>
                <p id="energy-display">Loading...</p>
            </div>
            <div class="card">
                <h3>Alerts</h3>
                <p id="alerts-count">0</p>
            </div>
            <div class="video-feed">
                <h3>Live Video Feed</h3>
                <img 
    src="http://192.168.0.237:5000/video_feed" 
    width="60%" 
    height="230" 
    title="Live Video Feed">
</img>

            </div>

            <h2>Dashboard</h2>
            <table>
                <thead>
                    <tr>
                        <th>Message</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody id="dashboard-body">
                    <?php
                    // Fetch initial notifications
                    $sql = "SELECT * FROM notifications1 ORDER BY created_at DESC LIMIT 50";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['message']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
let activeDevicesCount = 0; // Track the active devices count
let lastNotificationId = <?php echo $last_notification_id; ?>; // Track the last notification ID

// Function to fetch and update active devices count
function updateActiveDevicesCount() {
    fetch(`fetch_device_status.php?last_id=${lastNotificationId}`)
        .then(response => response.json())
        .then(newNotifications => {
            if (newNotifications.length > 0) {
                newNotifications.forEach(notification => {
                    // Check if the notification affects the active devices count
                    if (notification.message === "Door Opened" || 
                        notification.message === "Fan Open" || 
                        notification.message === "Garage Open" || 
                        notification.message === "Indoor Lights On" || 
                        notification.message === "Outdoor Lights On") {
                        activeDevicesCount++; // Increase the count
                    } else if (notification.message === "Door Locked" || 
                               notification.message === "Fan Off" || 
                               notification.message === "Garage Closing" || 
                               notification.message === "Indoor Lights Off" || 
                               notification.message === "Outdoor Lights Off") {
                        activeDevicesCount--; // Decrease the count
                    }

                    // Update the last notification ID
                    lastNotificationId = Math.max(lastNotificationId, notification.id);
                });

                // Update the active devices count in the DOM
                document.getElementById("active-devices-count").textContent = activeDevicesCount;
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

// Function to fetch and update energy consumption
function fetchEnergyConsumption() {
    fetch("check_energy.php")
        .then(response => response.json())
        .then(data => {
            if (data.total_energy) {
                document.getElementById("energy-display").innerText = data.total_energy + " kWh";
            } else {
                document.getElementById("energy-display").innerText = "No data available";
            }
        })
        .catch(error => console.error("Error fetching energy data:", error));
}

// Function to fetch and display new notifications
function checkNewNotifications() {
    fetch(`check_notifications.php?last_id=${lastNotificationId}`)
        .then(response => response.json())
        .then(newNotifications => {
            if (newNotifications.length > 0) {
                const tbody = document.getElementById('dashboard-body');
                const alertsCount = document.getElementById('alerts-count');
                newNotifications.forEach(notification => {
                    const newRow = document.createElement('tr');
                    newRow.classList.add('new-notification');
                    newRow.innerHTML = `
                        <td>${notification.message}</td>
                        <td>${notification.created_at}</td>
                    `;
                    tbody.insertBefore(newRow, tbody.firstChild);
                    lastNotificationId = Math.max(lastNotificationId, notification.id);
                });
                alertsCount.textContent = parseInt(alertsCount.textContent) + newNotifications.length;
            }
        })
        .catch(error => console.error('Error:', error));
}

// Set intervals for periodic updates
setInterval(updateActiveDevicesCount, 2000); // Update active devices count every 2 seconds
setInterval(fetchEnergyConsumption, 5000); // Update energy consumption every 5 seconds
setInterval(checkNewNotifications, 2000); // Check for new notifications every 2 seconds

// Initial fetches
updateActiveDevicesCount(); // Fetch active devices count on page load
fetchEnergyConsumption(); // Fetch energy consumption on page load
checkNewNotifications(); // Fetch notifications on page load
    </script>

</body>
</html>
<?php
$conn->close();
?>