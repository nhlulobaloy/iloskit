<?php
session_start();
include("db_connection.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$token = $conn->real_escape_string($_GET['token'] ?? '');

// Verify token
$result = $conn->query("SELECT id, reset_expires FROM users WHERE reset_token = '$token'");
$user = $result->fetch_assoc();

if (!$user || strtotime($user['reset_expires']) < time()) {
    $_SESSION['message'] = "Invalid or expired password reset link.";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords match
    if ($password !== $confirm_password) {
        $_SESSION['message'] = "Passwords do not match.";
        header("Location: reset_password.php?token=$token");
        exit();
    }
    
    // Validate password strength
    if (strlen($password) < 8) {
        $_SESSION['message'] = "Password must be at least 8 characters.";
        header("Location: reset_password.php?token=$token");
        exit();
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $user_id = $user['id'];
    
    // Update password and clear reset fields
    if (!$conn->query("UPDATE users SET password = '$hashed_password', reset_token = NULL, reset_expires = NULL WHERE id = $user_id")) {
        $_SESSION['message'] = "An error occurred. Please try again.";
        header("Location: reset_password.php?token=$token");
        exit();
    }
    
    $_SESSION['message'] = "Your password has been updated. Please login.";
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
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
            <h2 class="text-center mb-4">Reset Password</h2>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-info"><?= $_SESSION['message'] ?></div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>