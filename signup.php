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
        $phone = isset($_POST["phone"]) ? $_POST["phone"] : ''; // Optional phone
        $email = $_POST["email"];
        $passwordRaw = $_POST["password"];

        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            // Email exists, show friendly message without error
            $message = "❌ Email already registered. Please login or use another email.";
            $checkStmt->close();
        } else {
            $checkStmt->close();
            // Hash password
            $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, phone, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $phone, $email, $password);

            if ($stmt->execute()) {
                $message = "✅ Signup successful. <a href='index.php#login' style='color: white; text-decoration: underline;'>Login here</a>.";
            } else {
                $message = "❌ Error: " . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Signup</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .signup-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            margin: 20px;
            transition: all 0.3s ease;
        }
        
        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .signup-header h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .signup-header p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background-color: #3498db;
            color: white;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .login-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        /* Enhanced pop-up animation styles */
        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            padding: 20px 30px;
            border-radius: 12px;
            background-color: #ff4444;
            color: white;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
            text-align: center;
            max-width: 90%;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .popup.success {
            background-color: #00C851;
        }
        
        .popup.show {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        
        @media (max-width: 480px) {
            .signup-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-header">
            <h1>Create Your Account</h1>
            <p>Join our community and start your journey</p>
        </div>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" class="form-control" id="name" name="name" required placeholder="John Doe">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="john@example.com">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number (Optional)</label>
                <input type="tel" class="form-control" id="phone" name="phone" placeholder="+1 234 567 8900">
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn">Sign Up</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="index.php#login">Log in</a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="popup <?php echo strpos($message, '✅') !== false ? 'success' : ''; ?>" id="messagePopup">
            <?= $message ?>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const popup = document.getElementById('messagePopup');
                popup.classList.add('show');

                setTimeout(function() {
                    popup.classList.remove('show');
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