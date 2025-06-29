<?php
session_start();
include "db_connection.php";

// For development/demo testing:
// $_SESSION['user_id'] = 1;
// $_SESSION['user_firstname'] = 'Demo';
// $_SESSION['user_lastname'] = 'User';
// $_SESSION['user_email'] = 'demo@example.com';
// $_SESSION['user_phone'] = '0712345678';
// $_SESSION['cart'] = [
//     ['product_id' => 1, 'quantity' => 2, 'price' => 150.00, 'size' => 'M'],
//     ['product_id' => 2, 'quantity' => 1, 'price' => 200.00, 'size' => 'L']
// ];

// Check user and cart session
if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header("Location: index.php?message=Missing+user+info+or+cart.");
    exit();
}

$userId = $_SESSION['user_id'];
$cart = $_SESSION['cart'];

// Calculate total
function calculateTotal($cart) {
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['quantity'] * $item['price'];
    }
    return $total;
}

$total = calculateTotal($cart);
$deliveryFee = 60.00; // Optional: make dynamic
$grandTotal = $total + $deliveryFee;

// Prepare data for order and IPN
$productPrices = [];
foreach ($cart as $item) {
    $productPrices[$item['product_id']] = $item['price'];
}

$_SESSION['pending_order'] = [
    'user_id' => $userId,
    'total_amount' => $grandTotal,
    'delivery_option' => 'standard',
    'delivery_address' => '123 Sample Street', // Replace with actual
    'courier_fee' => $deliveryFee,
    'cart_items' => $cart,
    'products' => $productPrices
];

// Build PayFast data
$payfastData = [
    'merchant_id' => '18931794',
    'merchant_key' => 'ndvo45gntqyst',
    'return_url' => 'https://yourdomain.com/payment_success.php',
    'cancel_url' => 'https://yourdomain.com/payment_cancel.php',
    'notify_url' => 'https://yourdomain.com/payfast-itn.php',
    'm_payment_id' => 'ILO-' . uniqid(),
    'amount' => number_format($grandTotal, 2, '.', ''),
    'item_name' => "Ilo's Kit Purchase",
    'item_description' => 'Custom teamwear order',
    'name_first' => $_SESSION['user_firstname'],
    'name_last' => $_SESSION['user_lastname'],
    'email_address' => $_SESSION['user_email'],
    'cell_number' => $_SESSION['user_phone'],
    'custom_int1' => $userId
];

// Generate PayFast signature
$pfOutput = '';
foreach ($payfastData as $key => $val) {
    if (!empty($val)) {
        $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
    }
}
$pfOutput = rtrim($pfOutput, '&');
$payfastData['signature'] = md5($pfOutput);

// Store and redirect
$_SESSION['payfast_data'] = $payfastData;
header("Location: process-payfast.php");
exit();
?>
