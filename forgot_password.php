<?php
session_start();
include("db_connection.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    
    // Rate limiting (max 3 attempts per IP per hour)
    $ip = $_SERVER['REMOTE_ADDR'];
    $hour_ago = date("Y-m-d H:i:s", time() - 3600);
    $attempts = $conn->query("SELECT COUNT(*) as count FROM password_reset_attempts WHERE ip = '$ip' AND created_at > '$hour_ago'")->fetch_assoc()['count'];

    if ($attempts >= 3) {
        $_SESSION['message'] = "Too many password reset attempts. Please try again later.";
        header("Location: login.php");
        exit();
    }

    // Check if email exists
    $result = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($result->num_rows == 0) {
        // Log attempt even if email doesn't exist
        $conn->query("INSERT INTO password_reset_attempts (ip, email) VALUES ('$ip', '$email')");
        
        $_SESSION['message'] = "If this email exists in our system, a password reset link has been sent.";
        header("Location: login.php");
        exit();
    }
    
    // Generate token and expiration (1 hour from now)
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", time() + 3600);
    
    // Store in database
    if (!$conn->query("UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE email = '$email'")) {
        error_log("Database error: " . $conn->error);
        $_SESSION['message'] = "An error occurred. Please try again.";
        header("Location: forgot_password.php");
        exit();
    }
    
    // Log the attempt
    $conn->query("INSERT INTO password_reset_attempts (ip, email) VALUES ('$ip', '$email')");
    
    // Send email
    $reset_link = "http://".$_SERVER['HTTP_HOST']."/reset_password.php?token=$token";
    $subject = "Password Reset Request";
    $message = "Click this link to reset your password: $reset_link\n\nThis link will expire in 1 hour.";
    
    // Email headers
    $headers = [
        'From' => 'no-reply@'.$_SERVER['HTTP_HOST'],
        'Reply-To' => 'support@'.$_SERVER['HTTP_HOST'],
        'X-Mailer' => 'PHP/' . phpversion(),
        'MIME-Version' => '1.0',
        'Content-type' => 'text/html; charset=utf-8'
    ];
    
    // Convert headers array to string
    $headers_string = '';
    foreach ($headers as $key => $value) {
        $headers_string .= "$key: $value\r\n";
    }
    
    // Send email
    $mail_sent = mail(
        $email,
        $subject,
        nl2br(htmlspecialchars($message)),
        $headers_string
    );
    
    if (!$mail_sent) {
        error_log("Email failed to send. Error: " . error_get_last()['message']);
        $_SESSION['message'] = "We couldn't send the email. Please contact support.";
        header("Location: forgot_password.php");
        exit();
    }
    
    $_SESSION['message'] = "If this email exists in our system, a password reset link has been sent.";
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 40px; }
        .auth-card { 
            max-width: 500px; 
            margin: 0 auto;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background: white;
        }
        .btn-primary { width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <h2 class="text-center mb-4">Forgot Password</h2>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-info"><?= $_SESSION['message'] ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
            
            <div class="text-center mt-3">
                <a href="index.php #login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>