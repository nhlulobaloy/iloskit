<?php
session_start();
include("db_connection.php");

$message = '';
$popupType = '';

// Check if email and token exist in URL
if (isset($_GET['email'], $_GET['token'])) {
    $email = $_GET['email'];
    $token = $_GET['token'];

    // Prepare statement to verify token
    $stmt = $conn->prepare("SELECT id, email_verified FROM users WHERE email = ? AND verification_token = ?");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $email_verified);
        $stmt->fetch();

        if ($email_verified == 1) {
            $message = "ℹ️ Your email is already verified. You can log in now.";
            $popupType = 'info';
        } else {
            // Update email_verified to 1 and remove token
            $updateStmt = $conn->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
            $updateStmt->bind_param("i", $id);
            if ($updateStmt->execute()) {
                $message = "✅ Your email has been verified! You can now log in.";
                $popupType = 'success';
            } else {
                $message = "❌ Something went wrong. Please try again later.";
                $popupType = 'error';
            }
            $updateStmt->close();
        }
    } else {
        $message = "❌ Invalid verification link.";
        $popupType = 'error';
    }
    $stmt->close();
} else {
    $message = "❌ Invalid request.";
    $popupType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Ilo's Kit</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        
        .header {
            background: linear-gradient(135deg, #3498db, #8e44ad);
            padding: 30px 20px;
            color: white;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .success .icon {
            color: #2ecc71;
        }
        
        .error .icon {
            color: #e74c3c;
        }
        
        .info .icon {
            color: #3498db;
        }
        
        .message {
            font-size: 18px;
            margin-bottom: 30px;
            color: #2c3e50;
            line-height: 1.5;
        }
        
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .button:hover {
            background: linear-gradient(to right, #2980b9, #3498db);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
            transform: translateY(-2px);
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            color: #6c757d;
            font-size: 14px;
        }
        
        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            padding: 20px 30px;
            border-radius: 16px;
            background-color: #ff4444;
            color: white;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
            z-index: 1000;
            text-align: center;
            max-width: 90%;
            font-family: Poppins, sans-serif;
            font-size: 16px;
            font-weight: 500;
        }
        
        .popup.success { background: #00C851; }
        .popup.info { background: #33b5e5; }
        .popup.show { opacity:1; transform: translate(-50%, -50%) scale(1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ilo's Kit</h1>
            <p>Email Verification</p>
        </div>
        
        <div class="content <?= $popupType ?>">
            <?php if ($popupType === 'success'): ?>
                <div class="icon">✓</div>
                <div class="message">Your email has been successfully verified! You can now log in to your account and start shopping.</div>
                <a href="index.php#login" class="button">Go to Login</a>
            <?php elseif ($popupType === 'info'): ?>
                <div class="icon">ℹ️</div>
                <div class="message">Your email is already verified. You can log in to your account.</div>
                <a href="index.php#login" class="button">Go to Login</a>
            <?php else: ?>
                <div class="icon">⚠️</div>
                <div class="message">There was a problem with your verification. Please try again or contact support.</div>
                <a href="index.php" class="button">Return to Home</a>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>&copy; <?= date('Y') ?> Ilo's Kit. All rights reserved.</p>
        </div>
    </div>

    <div class="popup <?= $popupType ?>" id="messagePopup">
        <p><?= htmlspecialchars($message) ?></p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const popup = document.getElementById('messagePopup');
            popup.classList.add('show');
            setTimeout(function() {
                popup.classList.remove('show');
                setTimeout(function() {
                    popup.remove();
                    // Redirect to login page if verified
                    <?php if ($popupType === 'success' || $popupType === 'info'): ?>
                        setTimeout(function() {
                            window.location.href = 'index.php#login';
                        }, 1000);
                    <?php endif; ?>
                }, 300);
            }, 3500);
        });
    </script>
</body>
</html>