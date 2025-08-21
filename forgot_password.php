<?php
session_start();
include("db_connection.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load PHPMailer
require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string(trim($_POST['email']));

    // Rate limiting (max 3 attempts per IP per hour)
    $ip = $_SERVER['REMOTE_ADDR'];
    $hour_ago = date("Y-m-d H:i:s", time() - 3600);
    $attempts_query = $conn->query("SELECT COUNT(*) as count FROM password_reset_attempts WHERE ip = '$ip' AND created_at > '$hour_ago'");
    
    if ($attempts_query) {
        $attempts = $attempts_query->fetch_assoc()['count'];
        if ($attempts >= 3) {
            $message = "Too many password reset attempts. Please try again later.";
        }
    } else {
        error_log("Rate limit query failed: " . $conn->error);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please provide a valid email address.";
    }

    if ($message == "") {
        $result = $conn->query("SELECT id, name FROM users WHERE email = '$email'");

        // Log attempt
        $conn->query("INSERT INTO password_reset_attempts (ip, email) VALUES ('$ip', '$email')");

        if ($result->num_rows == 0) {
            $message = "If this email exists in our system, a password reset link has been sent.";
        } else {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $user_name = $user['name'];

            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", time() + 3600);

            // Store token
            if (!$conn->query("UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE id = $user_id")) {
                error_log("Database error: " . $conn->error);
                $message = "An internal error occurred. Please try again later.";
            } else {
                // Send email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'iloskitreset@gmail.com';
                    $mail->Password   = 'rmbuegibxkkapdyr';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('iloskitreset@gmail.com', 'Ilo\'s Kit');
                    $mail->addAddress($email, $user_name);
                    $mail->addReplyTo('iloskitreset@gmail.com', 'Ilo\'s Kit Support');

                    $reset_link = "https://iloskit.co.za/reset_password.php?token=$token";
                    $mail->isHTML(true);
                    $mail->Subject = 'Ilo\'s Kit Password Reset Request';
                    $mail->Body    = "
                        <div style='font-family: Poppins, sans-serif; max-width:600px; margin:0 auto; padding:20px;'>
                            <h2 style='color:#3498db;'>Hello " . htmlspecialchars($user_name) . ",</h2>
                            <p>We received a request to reset your Ilo's Kit password. Click the button below to reset it:</p>
                            <p><a href='$reset_link' style='display:inline-block;padding:12px 25px;background:#3498db;color:white;border-radius:5px;text-decoration:none;margin-top:10px;'>Reset My Password</a></p>
                            <p>Or copy and paste this link into your browser:<br><code>$reset_link</code></p>
                            <p><strong>This link will expire in 1 hour.</strong></p>
                            <p>If you did not request this, ignore this email.</p>
                            <p style='margin-top:20px;'>Cheers,<br>The Ilo's Kit Team</p>
                        </div>
                    ";
                    $mail->AltBody = "Hello $user_name,\n\nWe received a request to reset your Ilo's Kit password.\n\nReset your password using this link:\n$reset_link\n\nThis link expires in 1 hour.\n\nIf you did not request this, ignore this email.";

                    $mail->send();
                    $message = "We've sent a password reset link to your email. Please check your inbox (and spam folder).";
                } catch (Exception $e) {
                    error_log("PHPMailer Error: " . $mail->ErrorInfo);
                    $message = "We could not send the email at this time. Please try again later or contact support.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #f8f9fa; padding-top: 40px; }
.auth-card { max-width: 500px; margin:0 auto; padding:30px; border-radius:10px; box-shadow:0 0 15px rgba(0,0,0,0.1); background:white; }
.btn-primary { width:100%; padding:10px; }
</style>
</head>
<body>
<div class="container">
<div class="auth-card">
<h2 class="text-center mb-4">Forgot Your Password?</h2>
<p class="text-center text-muted mb-4">Enter your email address and we'll send you a link to reset your password.</p>

<?php if ($message != ""): ?>
    <div class="alert alert-info"><?php echo $message; ?></div>
<?php endif; ?>

<form method="POST" action="">
<div class="mb-3">
<label for="email" class="form-label">Email Address</label>
<input type="email" class="form-control" id="email" name="email" required autocomplete="email" autofocus>
</div>
<button type="submit" class="btn btn-primary btn-lg">Send Reset Link</button>
</form>

<div class="text-center mt-3">
    <a href="index.php#login">← Back to Login</a>
</div>
</div>
</div>
</body>
</html>
