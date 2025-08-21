<?php
session_start();
include "db_connection.php";

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Check if token is sent
if (!isset($_POST['token'])) {
    echo json_encode(['success' => false, 'error' => 'Missing payment token']);
    exit;
}

$token = $_POST['token'];
$delivery_address = $_POST['delivery_address'] ?? '';
$province = $_POST['province'] ?? ''; // <-- TYPO HERE? Should be 'province'
$delivery_option = $_POST['delivery_option'] ?? '';
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

// ... [Your product & total calculation code remains exactly the same] ...
$productIds = array_unique(array_map(fn($i) => $i['product_id'], $cart));
$idsString = implode(',', array_map('intval', $productIds));
$result = $conn->query("SELECT id, name, price FROM products WHERE id IN ($idsString)");
$products = [];
while ($row = $result->fetch_assoc()) $products[$row['id']] = $row;

$subtotal = 0;
foreach ($cart as $item) {
    $pid = $item['product_id'];
    $subtotal += ($products[$pid]['price'] ?? 0) * $item['quantity'];
}

$discount = 0;
if (isset($_SESSION['applied_promo'])) {
    $promo = $_SESSION['applied_promo'];
    $discount = $promo['discount_type'] == 'percentage' ? $subtotal * ($promo['discount_value'] / 100) : $promo['discount_value'];
}

$totalAmount = $subtotal - $discount;
$paxiFees = ['Gauteng' => 60, 'KwaZulu-Natal' => 60, 'Western Cape' => 60, 'Eastern Cape' => 50, 'Free State' => 60, 'Limpopo' => 60, 'Mpumalanga' => 60, 'Northern Cape' => 60, 'North West' => 60];
$deliveryFee = ($delivery_option == 'paxi') ? ($paxiFees[$province] ?? 60) : 90;
$totalAmount += $deliveryFee;
$totalInCents = round($totalAmount * 100);

$userId = $_SESSION['user_id'];
$userEmail = 'test@example.com';
$res = $conn->query("SELECT email FROM users WHERE id = $userId LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $userEmail = $row['email'];
}
// ... [End of your calculation code] ...

// ====================================================================
// DEBUG LOGGING - This will help us see the exact request
// ====================================================================
$secretKey = "sk_test_f92bb5020eZaoJg756a44308d35b";
$apiUrl = "https://api.yoco.com/v1/charges/";

$data = [
    'token' => $token,
    'amountInCents' => $totalInCents,
    'currency' => 'ZAR',
];
$jsonData = json_encode($data);

// Create a temporary log file to see the raw request
$logFile = fopen('yoco_debug.log', 'a');
fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] Attempting request to: " . $apiUrl . "\n");
fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] Request Data: " . $jsonData . "\n");
fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] Using Secret Key: " . $secretKey . "\n");

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $secretKey
]);
// Verbose logging for cURL - This is crucial
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);
// ⚠️ TEMPORARY FOR TESTING:
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

// Log the verbose cURL output
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] cURL Verbose Output:\n" . $verboseLog . "\n");
fclose($verbose);

fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] HTTP Code: " . $httpCode . "\n");
fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] Response: " . $response . "\n");
fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] cURL Error: " . $curlError . "\n");
fwrite($logFile, "----------------------------------------\n");
fclose($logFile);

curl_close($ch);

// ... [The rest of your error handling and database code remains the same] ...
if ($curlError) {
    echo json_encode(['success' => false, 'error' => 'cURL Error: ' . $curlError]);
    exit;
}

$result = json_decode($response, true);
if ($httpCode !== 201) {
    $errorMsg = 'Payment failed. ';
    if (isset($result['message'])) {
        $errorMsg .= 'Yoco: ' . $result['message'];
    } else {
        $errorMsg .= 'HTTP Code: ' . $httpCode;
    }
    echo json_encode([
        'success' => false,
        'error' => $errorMsg,
        'http_code' => $httpCode,
        'raw_response' => $response
    ]);
    exit;
}
if (!$result || !isset($result['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid response from payment gateway',
        'raw_response' => $response
    ]);
    exit;
}

// ... [Your database insertion code] ...
$stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, province, delivery_option) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("idsss", $userId, $totalAmount, $delivery_address, $province, $delivery_option);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'error' => 'Order failed. Please contact support. Error: ' . $conn->error]);
    exit;
}
$orderId = $stmt->insert_id;

$stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, size, price_at_purchase) VALUES (?, ?, ?, ?, ?)");
foreach ($cart as $item) {
    $pid = $item['product_id'];
    $qty = $item['quantity'];
    $size = $item['size'];
    $price = $products[$pid]['price'];
    $stmtItem->bind_param("iiisd", $orderId, $pid, $qty, $size, $price);
    $stmtItem->execute();
}

unset($_SESSION['cart']);
unset($_SESSION['applied_promo']);

echo json_encode(['success' => true, 'message' => 'Payment successful! Order #' . $orderId . ' created.']);
exit;
?>