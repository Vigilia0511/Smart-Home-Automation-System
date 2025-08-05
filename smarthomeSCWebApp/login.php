<?php
session_start(); // Start session at the top

// Get the user IP address
$user_ip = $_SERVER['REMOTE_ADDR'];

// Set the number of failed attempts before blocking
$max_failed_attempts = 3;
$block_time = 60; // Block time in seconds

// Check if the user IP is blocked
if (isset($_SESSION['blocked_' . $user_ip]) && $_SESSION['blocked_' . $user_ip] > time()) {
    // Blocked: show the message or handle accordingly
    $time_left = $_SESSION['blocked_' . $user_ip] - time();
    //echo "Your IP is blocked. Try again in " . $time_left . " seconds.";
    exit;
}

// Initialize or get failed attempts counter
if (!isset($_SESSION['failed_attempts_' . $user_ip])) {
    $_SESSION['failed_attempts_' . $user_ip] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $recipient_email = isset($_POST['recipient_email']) ? trim($_POST['recipient_email']) : '';

    if (empty($id) || empty($password) || empty($recipient_email)) {
        // Display an error modal if any field is empty
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                showModal('error', 'All fields are required.');
            });
        </script>";
    } else {
        $url = 'http://192.168.0.237:5000/verify_credentials';
        $data = http_build_query([
            'id' => $id,
            'password' => $password,
            'recipient_email' => $recipient_email
        ]);

        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // Execute the request
        $result = curl_exec($ch);
        
        // Check for errors
        if ($result === false) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showModal('error', 'Error connecting to the server. Please try again later.');
                });
            </script>";
        } else {
            $result = trim($result);  // Trim any unwanted whitespace from the response

            // Debug the result from the server
            echo "<script>console.log('Response from server: " . $result . "');</script>";

            // Check if 'success' exists in the response string
            if (strpos($result, 'success') !== false) {
                $_SESSION['authenticated'] = true; // Set session
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showModal('success', 'Login successful. Redirecting...');
                        setTimeout(function() {
                            window.location.href = 'dashboard.php';
                        }, 2000);
                    });
                </script>";
            } else if (strpos($result, 'error') !== false) {
                // Increment the failed attempts counter
                $_SESSION['failed_attempts_' . $user_ip]++;

                // If the user has exceeded the maximum failed attempts, block their IP for 60 seconds
                if ($_SESSION['failed_attempts_' . $user_ip] >= $max_failed_attempts) {
                    $_SESSION['blocked_' . $user_ip] = time() + $block_time; // Block the user for 60 seconds
                } else {
                    // Display an error modal for invalid credentials
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            showModal('error', 'Invalid login credentials. Please try again.');
                        });
                    </script>";
                }
            }
        }

        // Close cURL
        curl_close($ch);
    }
}

// If the block time has passed, remove the block from the session
if (isset($_SESSION['blocked_' . $user_ip]) && $_SESSION['blocked_' . $user_ip] <= time()) {
    unset($_SESSION['blocked_' . $user_ip]);
    unset($_SESSION['failed_attempts_' . $user_ip]); // Reset failed attempts after the block is removed
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Interface</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #333;
            color: white;
        }
        .login-container {
            background: #444;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #fff;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            background: #555;
            color: #fff;
            border: 1px solid #666;
            border-radius: 4px;
        }
        .btn {
          width: 50%;
          padding: 10px;
          background-color: #007bff;
          color: white;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          font-size: 16px;
          margin-left: 25%;



        }
        .btn:hover {
            background-color: #0056b3;
        }
        /* Modal Style for Pop-Up Messages */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #444;
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            width: 300px;
        }
        .modal-content p {
            margin: 0;
            padding: 10px 0;
        }
        .modal-content .success {
            color: #6fff6f;
        }
        .modal-content .error {
            color: #ff6f6f;
        }
        .modal-close {
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
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

        /* Mobile Styles */
@media (max-width: 600px) {
    body {
        padding: 10px;
    }

    .login-container {
        width: 80%;
        max-width: 80%;
        margin: 0;
    }

    .login-container h2 {
        font-size: 20px;
    }

    .form-group input {
        font-size: 14px;
        padding: 10px;
    }

    .btn {
        font-size: 14px;
        padding: 12px;
    }

    .modal-content {
        width: 90%;
    }

    .pic {
        width: 40px;
        height: 40px;
    }
}

    </style>
</head>
<body>
    <div class="login-container">
        <div class="image-container">
           <a href="login.php"> <img class="pic" src="bbq.png" alt="Image"></a>
        </div>
        <h2>Login</h2>
        <form id="loginForm" method="POST">
            <div class="form-group">
                <label for="id">User ID:</label>
                <input type="text" id="id" name="id" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="recipient_email">Recipient Email:</label>
                <input type="email" id="recipient_email" name="recipient_email" required>
            </div><br>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>

    <!-- Modal for messages -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <p id="modal-message"></p>
            <button class="modal-close">Close</button>
        </div>
    </div>


<script>
    document.querySelector('.modal-close').addEventListener('click', function() {
        document.getElementById('modal').style.display = 'none';
    });

function showModal(type, message) {
    const modal = document.getElementById('modal');
    const modalMessage = document.getElementById('modal-message');
    modalMessage.innerHTML = message;
    modalMessage.className = type;
    modal.style.display = 'flex';
}

</script>
</body>
</html>
