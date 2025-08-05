<?php
// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SmartHome1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Notifications</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .new-notification {
            background-color: #e6f7ff;
            animation: fadeIn 1s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Notifications</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Message</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody id="notifications-body">
                <?php
                // Fetch initial notifications
                $sql = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 50";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['message']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <script>
    // Keep track of the last notification ID
    let lastNotificationId = <?php 
        // Get the last notification ID
        $last_id_query = "SELECT MAX(id) as max_id FROM notifications";
        $last_id_result = $conn->query($last_id_query);
        $last_id_row = $last_id_result->fetch_assoc();
        echo $last_id_row['max_id'] ?? 0;
    ?>;

    function checkNewNotifications() {
        fetch(`check_notifications.php?last_id=${lastNotificationId}`)
            .then(response => response.json())
            .then(newNotifications => {
                if (newNotifications.length > 0) {
                    const tbody = document.getElementById('notifications-body');
                    
                    // Add new notifications to the top of the table
                    newNotifications.forEach(notification => {
                        const newRow = document.createElement('tr');
                        newRow.classList.add('new-notification');
                        newRow.innerHTML = `
                            <td>${notification.id}</td>
                            <td>${notification.message}</td>
                            <td>${notification.created_at}</td>
                        `;
                        
                        // Insert new row at the top
                        tbody.insertBefore(newRow, tbody.firstChild);
                        
                        // Update the last notification ID
                        lastNotificationId = Math.max(lastNotificationId, notification.id);
                    });
                }
            })
            .catch(error => {
                console.error('Error checking notifications:', error);
            });
    }

    // Check for new notifications every 2 seconds
    setInterval(checkNewNotifications, 2000);
    </script>
</body>
</html>
<?php
$conn->close();
?>
