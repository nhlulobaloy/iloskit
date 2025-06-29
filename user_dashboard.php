<?php
session_start();
include("db_connection.php");

// Redirect to login if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php#login");
    exit();
}

// Fetch user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $email, $created_at);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard - Ilo's Kit</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <span class="navbar-brand mb-0 h1">Ilo's Kit - Dashboard</span>
    <a href="logout.php" class="btn btn-outline-light">Logout</a>
  </div>
</nav>

<div class="container mt-4">
  <div class="alert alert-success">
    <h4 class="alert-heading">Welcome, <?= htmlspecialchars($name) ?>!</h4>
    <p>You successfully logged in. Below is your account information.</p>
  </div>

  <div class="card">
    <div class="card-header">Your Profile</div>
    <div class="card-body">
      <p><strong>Name:</strong> <?= htmlspecialchars($name) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
      <p><strong>Member Since:</strong> <?= htmlspecialchars($created_at) ?></p>
    </div>
  </div>

  <div class="mt-4">
    <h5>Start Shopping:</h5>
    <a href="index.php#products" class="btn btn-primary">View Products</a>
  </div>
</div>

</body>
</html>
