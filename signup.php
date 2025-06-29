<?php
include("db_connection.php");

// Initialize message variable
$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if all required fields are set
    if (!isset($_POST['name']) || !isset($_POST['email']) || !isset($_POST['password'])) {
        $message = "❌ Please fill all required fields.";
    } else {
        $name = $_POST["name"];
        $phone = isset($_POST["phone"]) ? $_POST["phone"] : ''; // Make phone optional
        $email = $_POST["email"];
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

        // Insert including phone number (if provided)
        $stmt = $conn->prepare("INSERT INTO users (name, phone, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $phone, $email, $password);

        if ($stmt->execute()) {
            $message = "✅ Signup successful. <a href='index.php#login' style='color: white; text-decoration: underline;'>Login here</a>.";
        } else {
            $message = "❌ Error: " . htmlspecialchars($stmt->error);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <style>
        /* Centered pop-up animation styles */
        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            padding: 20px 30px;
            border-radius: 8px;
            background-color: #ff4444; /* Red for errors */
            color: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: all 0.3s ease-in-out;
            z-index: 1000;
            text-align: center;
            max-width: 80%;
        }
        
        .popup.success {
            background-color: #00C851; /* Green for success */
        }
        
        .popup.show {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
    </style>
</head>
<body>
    <!-- Your signup form HTML here -->
    <!-- Make sure your form includes the phone field if you want to collect it -->
    <!-- <input type="text" name="phone" placeholder="Phone (optional)"> -->
    
    <?php if (!empty($message)): ?>
        <div class="popup <?php echo strpos($message, '✅') !== false ? 'success' : ''; ?>" id="messagePopup">
            <?php echo $message; ?>
        </div>
        
        <script>
            // Show popup
            document.addEventListener('DOMContentLoaded', function() {
                const popup = document.getElementById('messagePopup');
                popup.classList.add('show');
                
                // Hide popup after 3 seconds
                setTimeout(function() {
                    popup.classList.remove('show');
                    // Remove element after animation completes
                    setTimeout(function() {
                        popup.remove();
                        <?php if (strpos($message, '✅') !== false): ?>
                            window.location.href = 'index.php#login';
                        <?php endif; ?>
                    }, 300);
                }, 3000);
            });
        </script>
    <?php endif; ?>
</body>
</html>