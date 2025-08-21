<?php
session_start();
include("db_connection.php");

// Initialize variables
$message = '';
$redirect = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Prepare query to fetch user data
    $stmt = $conn->prepare("SELECT id, name, password, is_admin, is_blocked, email_verified FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $name, $hashedPassword, $is_admin, $is_blocked, $email_verified);
        $stmt->fetch();

        // Convert to integer to avoid string comparison issues
        $is_blocked = (int)$is_blocked;
        $email_verified = (int)$email_verified;

        // Check if user is blocked
        if ($is_blocked === 1) {
            $message = "❌ Your account has been blocked. Please contact support.";
            $redirect = true;
        }
        // Check if email is verified
        else if ($email_verified === 0) {
            $message = "❌ Please verify your email before logging in. Check your inbox.";
            $redirect = true;
        }
        else {
            // Verify password
            if (password_verify($password, $hashedPassword)) {
                // Set session variables
                $_SESSION["user_id"] = $id;
                $_SESSION["user_name"] = $name;
                $_SESSION["is_admin"] = $is_admin;

                // Redirect based on admin status
                if ((int)$is_admin === 1) {
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

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Ilo's Kit</title>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    .login-card {
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
    }
    .login-card h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }
    .login-card input {
        width: 100%;
        padding: 12px;
        margin: 8px 0;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 16px;
    }
    .login-card button {
        width: 100%;
        padding: 12px;
        background: linear-gradient(to right, #3498db, #2980b9);
        border: none;
        border-radius: 6px;
        color: white;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .login-card button:hover {
        background: linear-gradient(to right, #2980b9, #3498db);
    }
    .login-card a {
        display: block;
        text-align: center;
        margin-top: 12px;
        color: #3498db;
        text-decoration: none;
    }

    /* Popup */
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
        font-weight: 500;
    }
    .popup.success { background: #00C851; }
    .popup.show { opacity: 1; transform: translate(-50%, -50%) scale(1); }
</style>
</head>
<body>

<?php if (!empty($message)): ?>
    <div class="popup" id="messagePopup">
        <?php echo htmlspecialchars($message); ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const popup = document.getElementById('messagePopup');
            popup.classList.add('show');
            setTimeout(function() {
                popup.classList.remove('show');
                setTimeout(function() {
                    popup.remove();
                    <?php if ($redirect): ?>
                        window.location.href = 'index.php';
                    <?php endif; ?>
                }, 300);
            }, 3000);
        });
    </script>
<?php endif; ?>

</body>
</html>
