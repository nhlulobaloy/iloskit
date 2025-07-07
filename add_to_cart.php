<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?message=Please+login+to+add+items+to+cart");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['size'])) {
    $productId = (int)$_POST['product_id'];
    $size = trim($_POST['size']);
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Use productId and size combined as unique key
    $cartKey = $productId . '_' . $size;

    if (isset($_SESSION['cart'][$cartKey])) {
        // Increase quantity if item already in cart with same size
        $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
    } else {
        // Add new item to cart
        $_SESSION['cart'][$cartKey] = [
            'product_id' => $productId,
            'size' => $size,
            'quantity' => $quantity
        ];
    }

    // Redirect back with success message
    header("Location: index.php?message=Added+to+cart");
    exit;
} else {
    // Redirect to homepage if accessed incorrectly
    header("Location: index.php");
    exit;
}
