<?php
session_start();
include "db_connection.php";

// Make sure the user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

// Optional: you could fetch the latest order from DB
$userId = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM orders WHERE user_id = $userId ORDER BY id DESC LIMIT 1");
$order = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Successful - Ilo's Kit</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {background:#f5f7fa;font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
.success-container {max-width:700px;margin:3rem auto;padding:2rem;background:#fff;border-radius:8px;box-shadow:0 2px 15px rgba(0,0,0,0.05);text-align:center;}
.success-header {color:#28a745;font-size:2rem;margin-bottom:1rem;}
.success-message {font-size:1.1rem;margin-bottom:2rem;}
.btn-home {padding:0.75rem 1.5rem;border-radius:8px;font-weight:500;}
</style>
</head>
<body>
<div class="container">
    <div class="success-container">
        <div class="success-header">
            <i class="fas fa-check-circle"></i> Payment Successful!
        </div>
        <div class="success-message">
            Thank you for your order<?= $order ? ", order #".$order['id'] : "" ?>.<br>
            Your items will be processed and shipped shortly.
        </div>
        <a href="index.php" class="btn btn-success btn-home"><i class="fas fa-home me-2"></i>Back to Home</a>
    </div>
</div>
</body>
</html>
