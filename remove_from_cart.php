<?php
session_start();

// Check if request is valid
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cart_key'])) {
    $_SESSION['error'] = "Invalid request";
    header("Location: cart.php");
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get the cart key
$cartKey = $_POST['cart_key'];

// Remove the item if it exists
if (isset($_SESSION['cart'][$cartKey])) {
    $productName = $_SESSION['cart'][$cartKey]['name'] ?? 'item';
    unset($_SESSION['cart'][$cartKey]);
    $_SESSION['success'] = "$productName has been removed from your cart.";
    
    // Reindex array after removal
    $_SESSION['cart'] = array_values($_SESSION['cart']);
} else {
    $_SESSION['error'] = "The item was not found in your cart.";
}

header("Location: cart.php");
exit;
?>