<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    // User not logged in - redirect with message
    header("Location: index.php?message=Please+login+to+add+items+to+cart");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['size'])) {
    $productId = (int)$_POST['product_id'];
    $size = $_POST['size'];
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Use productId and size as combined key to differentiate sizes
    $cartKey = $productId . '_' . $size;

    if (isset($_SESSION['cart'][$cartKey])) {
        $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$cartKey] = [
            'product_id' => $productId,
            'size' => $size,
            'quantity' => $quantity
        ];
    }

    // Redirect back to homepage with a success message
    header("Location: index.php?message=Added+to+cart");
    exit;
} else {
    // If accessed without required POST data, redirect to homepage
    header("Location: index.php");
    exit;
}
