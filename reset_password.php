<?php
session_start();
include("db_connection.php");

if (!isset($_GET['token']) || empty($_GET['token'])) {
    $_SESSION['message'] = "Invalid or missing password reset token.";
    header("Location: index.php#login");
    exit();
}

$token = $conn->real_escape_string($_GET['token']);
$result = $conn->query("SELECT id, name, reset_expires FROM users WHERE reset_token = '$token'");

if ($result->num_rows == 0) {
    $_SESSION['message'] = "Invalid password reset link.";
    header("Location: index.php#login");
    exit();
}

$user = $result->fetch_assoc();
$user_id = $user['id'];
$user_name = $user['name'];
$expires = strtotime($user['reset_expires']);

if ($expires < time()) {
    $_SESSION['message'] = "This password reset link has expired. Please request a new one.";
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($password) || empty($confirm_password)) {
        $_SESSION['message'] = "Please fill in both fields.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['message'] = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->query("UPDATE users SET password='$hashed_password', reset_token=NULL, reset_expires=NULL WHERE id=$user_id");
        if ($update) {
            $_SESSION['message'] = "Your password has been successfully reset. You can now log in.";
            header("Location: index.php#login");
            exit();
        } else {
            $_SESSION['message'] = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
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
<h2 class="text-center mb-4">Reset Your Password</h2>
<?php if(isset($_SESSION['message'])): ?>
<div class="alert alert-info"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>
<form method="POST" action="">
<div class="mb-3">
<label for="password" class="form-label">New Password</label>
<input type="password" class="form-control" id="password" name="password" minlength="8" required>
</div>
<div class="mb-3">
<label for="confirm_password" class="form-label">Confirm New Password</label>
<input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
</div>
<button type="submit" class="btn btn-primary btn-lg">Reset Password</button>
</form>
<div class="text-center mt-3"><a href="index.php#login">← Back to Login</a></div>
</div>
</div>
</body>
</html>
