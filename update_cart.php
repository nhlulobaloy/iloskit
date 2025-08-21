<?php
session_start();

// Validate form submission
if (!isset($_POST['cart_key'], $_POST['quantity'])) {
    $_SESSION['error'] = 'Invalid cart update request.';
    header('Location: cart.php');
    exit;
}

$cartKey = $_POST['cart_key'];
$newQuantity = (int) $_POST['quantity'];

// Check if the cart key exists in the session cart
if (!isset($_SESSION['cart'][$cartKey])) {
    $_SESSION['error'] = 'Item not found in cart.';
    header('Location: cart.php');
    exit;
}

// If quantity is less than 1, remove the item
if ($newQuantity < 1) {
    unset($_SESSION['cart'][$cartKey]);
    $_SESSION['success'] = 'Item removed from cart.';
} else {
    // Update quantity
    $_SESSION['cart'][$cartKey]['quantity'] = $newQuantity;
    $_SESSION['success'] = 'Cart updated successfully.';
}

// Redirect back to the cart
header('Location: cart.php');
exit;
?>
