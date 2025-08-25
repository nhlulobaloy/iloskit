<?php
header('Content-Type: application/json');
session_start();
require_once "db_connection.php"; // this now loads .env and DB settings

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Get POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);
if (!$data || !isset($data['amountInCents'], $data['delivery_address'], $data['province'], $data['delivery_option'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

$amountInCents = $data['amountInCents'];
$amount = $amountInCents / 100;
$delivery_address = $data['delivery_address'];
$province = $data['province'];
$delivery_option = $data['delivery_option'];

// Generate unique IDs for order (not inserted yet)
$order_id = "ORD" . mt_rand(1000000, 9999999); // temporary internal ID
$id = "ILO" . mt_rand(100000000, 999999999);  // temporary external order ID

// Store temporary order info in session
$_SESSION['pending_order'] = [
    'id' => $id,
    'order_id' => $order_id,
    'user_id' => $_SESSION['user_id'],
    'amount' => $amount,
    'delivery_address' => $delivery_address,
    'delivery_option' => $delivery_option,
    'province' => $province
];

// -----------------------
// Prepare request to Yoco
// -----------------------
// Load Yoco secret key from .env
$yocoSecretKey = $env['YOCO_SECRET_KEY'] ?? '';
if (!$yocoSecretKey) {
    echo json_encode(['success' => false, 'error' => 'Yoco secret key not set in .env']);
    exit;
}

$url = 'https://payments.yoco.com/api/checkouts';
$payload = [
    'amount' => $amountInCents,
    'currency' => 'ZAR',
    'successUrl' => 'https://iloskit.co.za/success.php', // Yoco will redirect here after payment
    'cancelUrl' => 'https://iloskit.co.za/checkout.php',
    'metadata' => [
        'user_id' => $_SESSION['user_id'],
        'id' => $id,
        'order_id' => $order_id
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $yocoSecretKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Handle Yoco response
if ($httpCode >= 200 && $httpCode < 300) {
    $responseData = json_decode($response, true);
    if ($responseData && isset($responseData['redirectUrl'])) {
        echo json_encode(['success' => true, 'redirectUrl' => $responseData['redirectUrl']]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid response from Yoco',
            'raw_response' => $response
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to create checkout session',
        'details' => $response
    ]);
}
