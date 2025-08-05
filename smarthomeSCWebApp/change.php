<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    // Redirect to login page if not authenticated
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Your Account Password</title>
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
        .container {
            background: #444;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #fff;
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
        .input-box {
            margin-bottom: 15px;
        }
        .input-box label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
        }
        .input-box input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            background: #555;
            color: #fff;
            border: 1px solid #666;
            border-radius: 4px;
        }
        .save-button {
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
        .save-button:hover {
            background-color: #0056b3;
        }
        .response {
            text-align: center;
            margin-top: 20px;
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

        /* Mobile Styles */
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .container {
                width: 80%;
                max-width: 80%;
                margin: 0;
            }

            h2 {
                font-size: 20px;
            }

            .input-box input {
                font-size: 14px;
                padding: 10px;
            }

            .save-button {
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
  <div class="container">
    <h2>Change Your Account Password</h2>
    <div class="image-container">
            <a href="login.php"> <img class="pic" src="bbq.png" alt="Image"></a>
        </div>
    <form method="POST" action="">
      <div class="input-box">
        <label for="id">ID</label>
        <input type="text" id="id" name="id" required>
      </div>
      <div class="input-box">
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" required>
      </div>
      <div class="input-box">
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" required>
      </div>
      <button type="submit" class="save-button">SAVE</button>
    </form>

    <div class="response">
      <?php
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id = $_POST['id'];
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];

        // Validate inputs
        if (empty($id) || empty($current_password)) {
          echo "Invalid ID or current password.";
        } elseif (empty($new_password)) {
          echo "New password cannot be blank.";
        } else {
          // Send data to Flask server
          $url = "http://192.168.0.237:5000/change_password";
          $data = [
            "id" => $id,
            "current_password" => $current_password,
            "new_password" => $new_password
          ];

          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_POST, true);
          curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/x-www-form-urlencoded"
          ]);

          $response = curl_exec($ch);
          $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);

          if ($http_code == 200) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showModal('success', 'SUCCESSFUL: Password changed successfully');
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 2000);
                    });
                </script>"; 
          } elseif ($http_code == 402) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showModal('error', 'ERROR: Invalid ID or current password.');
                    });
                </script>";
          } elseif ($http_code == 400) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showModal('error', 'ERROR: New password cannot be blank.');
                    });
                </script>";
          } else {
             echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showModal('error', 'ERROR: Unexpected error occurred. HTTP Code: $http_code');
                    });
                </script>";          }
        }
      }
      ?>
    </div>
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

    // Listen for the 'popstate' event (triggered by pressing the back button)
    window.onpopstate = function(event) {
        window.location.href = 'app_interface.php'; // Redirect to index.php
    };

    // Push a new state into history to intercept the back button press
    history.pushState(null, null, location.href);

</script>

</body>
</html>
