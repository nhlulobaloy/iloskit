<?php
session_start();
include "db_connection.php";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}

// Validate required fields
$required = ['province', 'delivery_option', 'delivery_address', 'amount_in_cents', 'customer_email'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        die("Error: Missing required field '$field'");
    }
}

// Prepare checkout data for Yoco
$secretKey = "sk_test_f92bb5020eZaoJg756a44308d35b";
$data = [
    'amount' => (int)$_POST['amount_in_cents'],
    'currency' => 'ZAR',
'successUrl' => 'https://iloskit.co.za/success.php',
'cancelUrl' => 'https://iloskit.co.za/cart.php',

    'customerEmail' => $_POST['customer_email'],
    'metadata' => [
        'province' => $_POST['province'],
        'delivery_option' => $_POST['delivery_option'],
        'delivery_address' => $_POST['delivery_address']
    ]
];

// Store checkout details in session for success page
$_SESSION['checkout_details'] = [
    'amount' => $_POST['amount'],
    'province' => $_POST['province'],
    'delivery_option' => $_POST['delivery_option'],
    'delivery_address' => $_POST['delivery_address'],
    'customer_email' => $_POST['customer_email']
];

// Create checkout session with Yoco
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.yoco.com/v1/checkouts/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $secretKey
    ],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check for errors
if ($httpCode !== 201) {
    die("Error creating checkout session: " . $response);
}

// Decode response
$result = json_decode($response, true);
if (!isset($result['redirectUrl'])) {
    die("Error: No redirect URL in response");
}

// Redirect to Yoco's payment page
header("Location: " . $result['redirectUrl']);
exit;