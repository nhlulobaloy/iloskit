<?php
session_start();
include("db_connection.php");

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php?message=Access+denied.");
    exit;
}

// Get product ID
$productId = intval($_GET['id'] ?? 0);

if ($productId <= 0) {
    die("Invalid product ID.");
}

// Delete the product
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$stmt->close();

// Redirect back to admin products
header("Location: admin_products.php?message=Product+deleted");
exit;
