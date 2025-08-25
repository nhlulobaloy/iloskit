<?php
session_start();
require "db_connection.php";

// Only allow logged-in admins
if (!isset($_SESSION['user_id'])) exit('Unauthorized');
$stmtAdmin = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmtAdmin->bind_param("i", $_SESSION['user_id']);
$stmtAdmin->execute();
$stmtAdmin->bind_result($isAdmin);
$stmtAdmin->fetch();
$stmtAdmin->close();
if (!$isAdmin) exit('Unauthorized');

// Get POST data
$orderId = $_POST['order_id'] ?? '';
$newStatus = $_POST['order_status'] ?? '';

if (!$orderId || !$newStatus) {
    exit('Missing parameters');
}

// Update order
$stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
$stmt->bind_param("si", $newStatus, $orderId); // s = string, i = integer

if($stmt->execute()){
    if($stmt->affected_rows > 0){
        echo 'success';
    } else {
        echo 'No rows updated. Check order id.';
    }
} else {
    echo 'DB error: '.$conn->error;
}

$stmt->close();
$conn->close();
