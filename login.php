<?php
session_start();
include("db_connection.php");

// Initialize variables
$message = '';
$redirect = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Prepare query to fetch user data including block status
    $stmt = $conn->prepare("SELECT id, name, password, is_admin, is_blocked FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        // Bind results to variables
        $stmt->bind_result($id, $name, $hashedPassword, $is_admin, $is_blocked);
        $stmt->fetch();

        // Check if user is blocked
        if ($is_blocked == 1) {
            $message = "❌ Your account has been blocked. Please contact support.";
            $redirect = true;
        } else {
            // Verify password
            if (password_verify($password, $hashedPassword)) {
                // Set session variables
                $_SESSION["user_id"] = $id;
                $_SESSION["user_name"] = $name;
                $_SESSION["is_admin"] = $is_admin;

                // Redirect based on admin status
                if ($is_admin == 1) {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: user_dashboard.php");
                }
                exit();
            } else {
                $message = "❌ Incorrect password.";
                $redirect = true;
            }
        }
    } else {
        $message = "❌ Email not found.";
        $redirect = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* Centered pop-up animation styles */
        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            padding: 20px 30px;
            border-radius: 8px;
            background-color: #ff4444;
            color: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: all 0.3s ease-in-out;
            z-index: 1000;
            text-align: center;
            max-width: 80%;
        }
        
        .popup.show {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
    </style>
</head>
<body>
    <!-- Your login form HTML here -->
    
    <?php if (!empty($message)): ?>
        <div class="popup" id="messagePopup">
            <?php echo htmlspecialchars($message); ?>
        </div>
        
        <script>
            // Show popup
            document.addEventListener('DOMContentLoaded', function() {
                const popup = document.getElementById('messagePopup');
                popup.classList.add('show');
                
                // Hide popup after 3 seconds and redirect if needed
                setTimeout(function() {
                    popup.classList.remove('show');
                    
                    // Remove element after animation completes
                    setTimeout(function() {
                        popup.remove();
                        <?php if ($redirect): ?>
                            window.location.href = 'index.php';
                        <?php endif; ?>
                    }, 300);
                }, 3000); // Reduced display time to 3 seconds
            });
        </script>
    <?php endif; ?>
</body>
</html>