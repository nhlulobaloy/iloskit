<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

include("db_connection.php");

$message = '';
$popupType = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['name'], $_POST['email'], $_POST['password'])) {
        $message = "❌ Please fill all required fields.";
        $popupType = 'error';
    } else {
        $name = $_POST["name"];
        $phone = $_POST["phone"] ?? '';
        $email = $_POST["email"];
        $passwordRaw = $_POST["password"];

        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $message = "❌ Email already registered. Please login or use another email.";
            $popupType = 'error';
            $checkStmt->close();
        } else {
            $checkStmt->close();
            $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

            // Generate verification token
            $verification_token = bin2hex(random_bytes(16));

            // Insert user but email_verified = 0
            $stmt = $conn->prepare("INSERT INTO users (name, phone, email, password, email_verified, verification_token) VALUES (?, ?, ?, ?, 0, ?)");
            $stmt->bind_param("sssss", $name, $phone, $email, $password, $verification_token);

            if ($stmt->execute()) {
                $popupType = 'success';

                // Send verification email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'iloskit1219@gmail.com';
                    $mail->Password = 'xaqcyejonjgopinw';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('iloskit1219@gmail.com', 'Ilo\'s Kit');
                    $mail->addAddress($email, $name);

                    $mail->isHTML(true);
                    $mail->Subject = 'Verify your email - Ilo\'s Kit';
                    $mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: "Poppins", Arial, sans-serif;
            background-color: #f7f9fc;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #3498db, #8e44ad);
            padding: 30px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            color: white;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .content {
            padding: 30px;
        }
        .welcome {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        .message {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
            text-align: center;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .verify-button {
            display: inline-block;
            padding: 15px 35px;
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            transition: all 0.3s ease;
        }
        .verify-button:hover {
            background: linear-gradient(to right, #2980b9, #3498db);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
            transform: translateY(-2px);
        }
        .promo-section {
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        .promo-title {
            font-size: 20px;
            font-weight: 700;
            color: #d35400;
            margin-bottom: 10px;
        }
        .promo-code {
            font-size: 28px;
            font-weight: 800;
            color: #c0392b;
            letter-spacing: 3px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        .discount {
            font-size: 18px;
            font-weight: 600;
            color: #16a085;
        }
        .image-container {
            text-align: center;
            margin: 30px 0;
        }
        .product-image {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        .footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 8px 8px;
            font-size: 14px;
        }
        .social-links {
            margin: 15px 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #3498db;
            background: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            line-height: 36px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Ilo\'s Kit</h1>
        </div>
        
        <div class="content">
            <div class="welcome">Welcome to Ilo\'s Kit, ' . $name . '!</div>
            
            <p class="message">Thanks for signing up! We\'re excited to have you on board. Please verify your email address to start enjoying our products and exclusive offers.</p>
            
            <div class="button-container">
                <a href="https://www.iloskit.co.za/verify_email.php?email=' . $email . '&token=' . $verification_token . '" class="verify-button">Verify Email Address</a>
            </div>
            
            <div class="promo-section">
                <div class="promo-title">🎉 SPECIAL WELCOME OFFER 🎉</div>
                <p>Use this promo code for <span class="discount">15% OFF</span> your first purchase!</p>
                <div class="promo-code">ILOFIRST</div>
                <p>Redeem this code at checkout to claim your discount!</p>
            </div>
            
            <p class="message">After verifying your email, you can log in and explore our amazing collection of football kits and accessories.</p>
            
            <div class="image-container">
                <img src="https://iloskit.co.za/kit_images/football-kit.webp" alt="Football Kit" class="product-image" style="max-width: 300px;">
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; ' . date('Y') . ' Ilo\'s Kit. All rights reserved.</p>
            <p>If you have any questions, contact us at support@iloskit.co.za</p>
            <div class="social-links">
                <a href="#" style="color: #3498db;">F</a>
                <a href="#" style="color: #e74c3c;">I</a>
                <a href="#" style="color: #1da1f2;">T</a>
            </div>
        </div>
    </div>
</body>
</html>
';

                    $mail->send();
                    $message = "✅ Signup successful! Check your email to verify your account.";
                } catch (Exception $e) {
                    $message = "✅ Signup successful! But verification email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }

            } else {
                $message = "❌ Error: " . htmlspecialchars($stmt->error);
                $popupType = 'error';
            }
            $stmt->close();
        }
    }
}
?>

<!-- Your popup HTML / JS here (unchanged) -->

<?php if (!empty($message)): ?>
    <div class="popup <?= $popupType ?>" id="messagePopup">
        <p><?= $message ?></p>
    </div>

    <style>
        .popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            padding: 20px 30px;
            border-radius: 16px;
            background-color: #ff4444;
            color: white;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
            text-align: center;
            max-width: 90%;
            font-family: Poppins, sans-serif;
            font-size: 16px;
            font-weight: 500;
        }

        .popup.success {
            background: #00C851;
        }

        .popup.show {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const popup = document.getElementById('messagePopup');
            popup.classList.add('show');
            setTimeout(function () {
                popup.classList.remove('show');
                setTimeout(function () {
                    popup.remove();
                    <?php if ($popupType === 'success'): ?>
                        window.location.href = 'index.php#login';
                    <?php endif; ?>
                }, 300);
            }, 3500);
        });
    </script>
<?php endif; ?>