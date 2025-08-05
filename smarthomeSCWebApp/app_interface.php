
<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Redirect to login page if not authenticated
    header('Location: login.php');
    exit;
}
?>
<?php
$public_ip = "http://192.168.0.237:5000";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice Access Notification</title>
    <style>
        /* Your existing styles here */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }

        .navbar {
            background-color: #333;
            color: white;
            padding: 10px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar span {
            background-color: white;
            color: black;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 16px;
        }

        .navbar .logout {
            background-color: #fdd835;
            color: black;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .control-row {
            background-color: #333;
            color: white;
            display: flex;
            flex-direction: row;
            align-items: center; /* Center items horizontally */
            justify-content: center; /* Center items vertically */
            padding: 20px 15px;
            margin-top: -1px;
            text-align: center; /* Ensure the text is centered */
             gap: 20px; /* Add horizontal space between buttons */
        }

        .door-lock-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .door-lock-container label {
            font-size: 16px;
            margin-bottom: 10px;
            color: white;
        }

        .door-lock-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .on-button,
        .off-button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }

        .on-button {
            background-color: #4caf50;
            color: white;
        }

        .off-button {
            background-color: #f44336;
            color: white;
        }

        .live-view {
            background-color: #303f9f;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }

        .live-view:hover {
            background-color: #283593;
        }

        .content {
            text-align: center;
            margin: 20px auto;
        }

        .notifications {
            margin-top: 20px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
            text-align: left;
        }

        .notifications h2 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
            text-align: center;
            text-transform: uppercase;
        }

        .notifications p {
            margin: 5px 0;
            color: #555;
            font-size: 14px;
        }

        #action-button {
            background-color: #303f9f;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        #action-button:hover {
            background-color: #283593;
        }

/* Modal styles */
.modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
    max-height: 80vh; /* Adjust height to fit within the viewport */
    overflow-y: auto; /* Makes the content scrollable */
    text-align: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Ensure the modal itself covers full screen */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
}

/* All notifications area (if needed) */
#all-notifications {
    max-height: 60vh; /* Adjust the size as needed */
    overflow-y: auto; /* Make the area scrollable */
}


        .modal-content h2 {
            margin-top: 0;
        }

        .modal-content p {
            margin: 10px 0;
        }

        .close-button {
            background: none;
            color: #f44336;
            border: none;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            padding-left: 90%;
            line-height: 0;
        }
        .close-btn{
             background: none;
            color: #f44336;
            border: none;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            padding-left: 90%;
            line-height: 0;
        }

        .close-button:hover {
            color: #d32f2f;
        }

        /* Highlight the first notification */
        .highlight {
            background-color: #fdd835;
            font-weight: bold;
        }

        .live-view-container {
            margin-top: 20px;
            display: none; /* Initially hidden */
            width: 100%;
            height: 400px; /* Adjust height as needed */
            background-color: #000;
        }
          .logout-link {
        text-decoration: none; /* Removes the underline */
    }
    .image-container {
        display: flex;
        justify-content: center;
        align-items: center;
        }

        .pic {
            width: 50px;
            height: 50px;
        }
        .space{
            margin-top: 2px;
        }
        .ID{
        padding: 8px 8px;
        font-size: 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        min-width: 30px;
        bacgroundcolor: white;
    }


       /* Mobile view styles */
@media screen and (max-width: 768px) {
    body {
        font-size: 14px;
    }

    .navbar {
        flex-direction: row; /* Correcting 'raw' to 'row' */
        align-items: flex-start;
        padding: 10px;
    }

    .navbar span {
        margin-bottom: 10px;
    }

    .control-row {
        flex-direction: row; /* Correcting 'raw' to 'row' */
        gap: 15px;
        padding: 15px 10px;
    }

    .door-lock-container {
        width: 100%;
        margin-bottom: 15px;
    }

    .door-lock-buttons {
        flex-direction: column;
        gap: 5px;
        padding: 15px 0;
    }

    /* Live view button on a new line */
    .live-view-button {
        display: block; /* Ensures the button is on its own line */
        margin-top: 10px; /* Adds some space above the button */
        background-color: darkred;
        color: white;
        font-size: 11px;
        padding: 8px 5px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        min-width: 80px; /* Adjust the minimum width */
    }

    .notifications {
        padding: 15px;
        margin: 10px;
        max-width: 100%;
        box-shadow: none;
    }

    .notifications h2 {
        font-size: 16px;
    }

    .modal-content {
        width: 90%;
        padding: 15px;
    }

    .close-button {
        padding-left: 80%;
    }

    .image-container {
        flex-direction: column;
    }

    .pic {
        width: 40px;
        height: 40px;
    }

    /* Button adjustments */
    .on-button, .off-button, .live-view, .logout, .id-button {
        padding: 8px 5px;
        font-size: 11px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        min-width: 30px;
    }

    .on-button {
        background-color: #4caf50;
        color: white;
    }

    .off-button {
        background-color: #f44336;
        color: white;
    }

    .logout-button {
        background-color: #9e9e9e;
        color: white;
    }

    .id-button {
        background-color: #ff9800;
        color: white;
    }

    #action-button {
        padding: 12px 8px;
        font-size: 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 15px;
        font-weight: bold;
        min-width: 80px;
    }

    #door {
        font-size: 11px;
    }
    .pic{
        height: 30px;
        width: 30px;
    }.ID{
        padding: 8px 5px;
        font-size: 11px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        min-width: 30px;
        bacgroundcolor: white;
    }





    </style>
</head>
<body>
    <div class="navbar">
        <a href="change.php" class="ID-link">
    <button class="ID">ID:051123</button>
</a>
       <a href="logout.php" class="logout-link">
    <button class="logout">LOGOUT</button>
</a>

    </div>
<div class="space"></div>

<div class="control-container">
    <div class="control-row">
        <div class="image-container">
            <a href="dashboard.php"> <img class="pic" src="bbq.png" alt="Image"></a>
            <div>
                <button type="button" class="live-view" onclick="openVideoFeedModal()">LIVE VIEW</button>
            </div>
            <br>
        </div>

        <!-- First group (Solenoid Lock Controls) -->
        <div class="control-group">
            <label id="door">OPEN DOOR LOCK</label>
            <div>
                <button id="doorLockOn" class="on-button" onclick="sendRequest('open_solenoid_lock_on')">OPEN</button>
                <button id="doorLockOff" class="off-button" onclick="sendRequest('open_solenoid_lock_off')">CLOSE</button>
            </div>
            <br>
        </div>

        <!-- Second group (Fan Controls) -->
        <div class="control-group">
            <label id="door">OPEN FAN</label>
            <div>
                <button id="fanOn" class="on-button" onclick="sendRequest('open_fan_on')">OPEN</button>
                <button id="fanOff" class="off-button" onclick="sendRequest('open_fan_off')">CLOSE</button>
            </div>
            <br>
        </div>

        <!-- Third group (Garage Controls) -->
        <div class="control-group">
            <label id="door">OPEN GARAGE</label>
            <div>
                <button id="garageOn" class="on-button" onclick="sendRequest('open_garage_on')">OPEN</button>
                <button id="garageOff" class="off-button" onclick="sendRequest('open_garage_off')">CLOSE</button>
            </div>
            <br>
        </div>

        <!-- Fourth group (Light Controls) -->
        <div class="control-group">
            <label id="door">INDOOR LIGHTS</label>
            <div>
                <button id="lightOn" class="on-button" onclick="sendRequest('open_light_on')">ON</button>
                <button id="lightOff" class="off-button" onclick="sendRequest('open_light_off')">OFF</button>
            </div>
            <br>
        </div>

         <div class="control-group">
            <label id="door">OUTDOOR LIGHTS</label>
            <div>
                <button id="lightOn" class="on-button" onclick="sendRequest('open_light_on1')">ON</button>
                <button id="lightOff" class="off-button" onclick="sendRequest('open_light_off1')">OFF</button>
            </div>
            <br>
        </div>
    </div>
</div>

            
        </form>
    </div>

    <div class="content">
        <div class="notifications" id="notifications-area">
            <h2>NOTIFICATION AREA</h2>
        </div>
        <div>
            <button id="action-button">View All Notifications</button>
        </div>
    </div>

    <!-- Modal for all notifications -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <span class="close-button" id="close-modal">&times;</span>
            <h2>All Notifications</h2>
            <div id="all-notifications"></div>
        </div>
    </div>

    <!-- Modal for Video Feed -->
    <div id="videoFeedModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeVideoFeed()">&times;</span>
            <h3>Live Video Feed</h3>
            <img 
    src="<?php echo $public_ip; ?>/video_feed" 
    width="100%" 
    height="400" 
    title="Live Video Feed">
</img>

        </div>
    </div>

    <script>
        let lastNotificationId = 0;
        let notifications = []; // Store the notifications array

        function playNotificationSound() {
    const audio = new Audio('mixkit-software-interface-start-2574.wav'); // Path to your notification sound file
    audio.play();
}

       function checkNewNotifications() {
    fetch(`check_notifications.php?last_id=${lastNotificationId}`)
        .then(response => response.json())
        .then(newNotifications => {
            if (newNotifications.length > 0) {
                const notificationsArea = document.getElementById('notifications-area');

                // Play the notification sound
                playNotificationSound();
                
                // Add new notifications to the notifications array
                notifications = [...newNotifications, ...notifications];

                // Limit the notifications array to the 5 most recent
                notifications = notifications.slice(0, 5);

                // Clear previous notifications to keep only the latest ones
                notificationsArea.innerHTML = '<h2>NOTIFICATION AREA</h2>';

                // Add new notifications to the notification area
                notifications.forEach((notification, index) => {
                    const notificationElement = document.createElement('p');
                    notificationElement.textContent = `${notification.message} ---- ${notification.created_at}`;
                    
                    // Highlight the first (most recent) notification
                    if (index === 0) {
                        notificationElement.classList.add('highlight');
                    }

                    notificationsArea.appendChild(notificationElement);

                    // Update the last notification ID
                    lastNotificationId = Math.max(lastNotificationId, notification.id);
                });
            }
        })
        .catch(error => {
            console.error('Error checking notifications:', error);
        });
}


        // Show all notifications in a modal
        document.getElementById('action-button').addEventListener('click', () => {
            fetch('check_notifications.php')
                .then(response => response.json())
                .then(allNotifications => {
                    const allNotificationsArea = document.getElementById('all-notifications');
                    allNotificationsArea.innerHTML = ''; // Clear previous notifications

                    allNotifications.forEach(notification => {
                        const notificationElement = document.createElement('p');
                        notificationElement.textContent = `${notification.message}  ---- ${notification.created_at}`;
                        allNotificationsArea.appendChild(notificationElement);
                    });

                    // Show the modal
                    document.getElementById('modal').style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error fetching all notifications:', error);
                });
        });

        // Close the modal
        document.getElementById('close-modal').addEventListener('click', () => {
            document.getElementById('modal').style.display = 'none';
        });

        // Open Video Feed Modal
        function openVideoFeedModal() {
            const modal = document.getElementById('videoFeedModal');
            modal.style.display = 'flex';
        }

        // Close Video Feed Modal
        function closeVideoFeed() {
            const modal = document.getElementById('videoFeedModal');
            modal.style.display = 'none';
        }

        // Close Modal on Outside Click
        window.onclick = function(event) {
            const modal = document.getElementById('videoFeedModal');
            if (event.target === modal) {
                closeVideoFeed();
            }
        };

        // Function to handle button click and update state
        function toggleButtonColor(activeButtonId, inactiveButtonId, actionValue) {
            const activeButton = document.getElementById(activeButtonId);
            const inactiveButton = document.getElementById(inactiveButtonId);

            // Update active and inactive button colors
            if (activeButton.classList.contains('on-button')) {
                setButtonState(activeButton, 'green', 'white');
                resetButtonState(inactiveButton, 'white', 'black');
                localStorage.setItem(activeButtonId, 'on');
            } else if (activeButton.classList.contains('off-button')) {
                setButtonState(activeButton, 'red', 'white');
                resetButtonState(inactiveButton, 'white', 'black');
                localStorage.setItem(activeButtonId, 'off');
            }

            // Remove the state of the inactive button
            localStorage.removeItem(inactiveButtonId);

            // Submit the form with the action value
            const form = activeButton.closest('form');
            const hiddenActionField = document.createElement("input");
            hiddenActionField.setAttribute("type", "hidden");
            hiddenActionField.setAttribute("name", "action");
            hiddenActionField.setAttribute("value", actionValue);
            form.appendChild(hiddenActionField);
            form.submit();
        }

        // Helper functions to set and reset button states
        function setButtonState(button, bgColor, textColor) {
            button.style.backgroundColor = bgColor;
            button.style.color = textColor;
        }

        function resetButtonState(button, bgColor, textColor) {
            button.style.backgroundColor = bgColor;
            button.style.color = textColor;
        }

// Poll for new notifications every 5 seconds (optional)
setInterval(checkNewNotifications, 5000);

        function sendRequest(action) {
            const notificationArea = document.getElementById('notification-area');

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action })
            })
            .then(response => response.json())
            .then(data => {
            
            })
            .catch(error => {
                notificationArea.textContent = `Error: ${error.message}`;
            });
        }


    // Listen for the 'popstate' event (triggered by pressing the back button)
    window.onpopstate = function(event) {
    window.location.href = 'dashboard.php'; // Redirect to index.php
    };

    // Push a new state into history to intercept the back button press
    history.pushState(null, null, location.href);

</script>
<?php
$responseMessage = "";

$public_ip = "http://192.168.0.237:5000";

// Map actions to their respective endpoints and payloads
$actionMapping = [
    'open_solenoid_lock_on' => ['url' => "{$public_ip}/control_solenoid", 'payload' => 'switch=on'],
    'open_solenoid_lock_off' => ['url' => "{$public_ip}/control_solenoid", 'payload' => 'switch=off'],
    'open_fan_on' => ['url' => "{$public_ip}/control_fan", 'payload' => 'switch2=on'],
    'open_fan_off' => ['url' => "{$public_ip}/control_fan", 'payload' => 'switch2=off'],
    'open_light_on' => ['url' => "{$public_ip}/control_lights", 'payload' => 'switch3=on'],
    'open_light_off' => ['url' => "{$public_ip}/control_lights", 'payload' => 'switch3=off'],
    'open_garage_on' => ['url' => "{$public_ip}/control_garage", 'payload' => 'switch4=on'],
    'open_garage_off' => ['url' => "{$public_ip}/control_garage", 'payload' => 'switch4=off'],
    'open_light_on1' => ['url' => "{$public_ip}/control_lights2", 'payload' => 'switch5=on'],
    'open_light_off1' => ['url' => "{$public_ip}/control_lights2", 'payload' => 'switch5=off'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputData = json_decode(file_get_contents('php://input'), true);
    $switchAction = $inputData['action'] ?? '';

    if (array_key_exists($switchAction, $actionMapping)) {
        $actionDetails = $actionMapping[$switchAction];
        $url = $actionDetails['url'];
        $postData = $actionDetails['payload'];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            echo json_encode(["status" => "error", "message" => curl_error($curl)]);
        } else {
            echo json_encode(["status" => "success"]);
        }

        curl_close($curl);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid action received."]);
    }
    exit;
}
?>

</body>
</html>
