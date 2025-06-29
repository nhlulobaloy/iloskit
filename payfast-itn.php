<?php
session_start();
include "db_connection.php";

// --- Validate the PayFast IPN ---
function validatePayfastITN($postData) {
    $pfParamString = '';

    foreach ($postData as $key => $val) {
        if ($key !== 'signature') {
            $pfParamString .= $key . '=' . urlencode(trim($val)) . '&';
        }
    }
    $pfParamString = rtrim($pfParamString, '&');

    // Check signature
    if (md5($pfParamString) !== $postData['signature']) {
        return false;
    }

    // Verify source IP
    $validHosts = ['www.payfast.co.za', 'sandbox.payfast.co.za', 'w1w.payfast.co.za', 'w2w.payfast.co.za'];
    $validIps = [];

    foreach ($validHosts as $host) {
        $ips = gethostbynamel($host);
        if ($ips !== false) {
            $validIps = array_merge($validIps, $ips);
        }
    }

    $validIps = array_unique($validIps);

    return in_array($_SERVER['REMOTE_ADDR'], $validIps);
}

// --- Main ITN handler ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = $_POST;
    $isValid = validatePayfastITN($postData);

    // Always log the ITN attempt
    $logData = json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'data' => $postData,
        'valid' => $isValid
    ]);

    $conn->query("INSERT INTO payment_logs (order_id, payment_data) VALUES ('".($postData['m_payment_id'] ?? 'unknown')."', '". $conn->real_escape_string($logData) ."')");

    if ($isValid && $postData['payment_status'] === 'COMPLETE') {
        $userId = $postData['custom_int1'] ?? null;
        $orderData = $_SESSION['pending_order'] ?? null;

        if ($orderData && $userId == $orderData['user_id']) {
            // Generate order ID
            $orderId = 'ILO' . date('Ymd') . $userId . mt_rand(1000, 9999);

            // Promo code from ITN data (sent via custom_str2)
            $promoCode = $postData['custom_str2'] ?? '';

            // Insert order (make sure promo_code column exists in your 'orders' table)
            $stmt = $conn->prepare("INSERT INTO orders (id, user_id, order_date, status, total_amount, delivery_option, delivery_address, courier_fee, promo_code) 
                                    VALUES (?, ?, NOW(), 'processing', ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisdsiss", $orderId, $userId, $orderData['total_amount'], 
                              $orderData['delivery_option'], $orderData['delivery_address'], $orderData['courier_fee'], $promoCode);
            $stmt->execute();

            // Insert order items
            $stmtItems = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, size, price_at_purchase, status) 
                                         VALUES (?, ?, ?, ?, ?, 'processing')");

            foreach ($orderData['cart_items'] as $item) {
                $price = $orderData['products'][$item['product_id']] ?? 0;
                $stmtItems->bind_param("iiisd", $orderId, $item['product_id'], $item['quantity'], $item['size'], $price);
                $stmtItems->execute();
            }

            // Clear cart and pending order from session
            unset($_SESSION['cart']);
            unset($_SESSION['pending_order']);
            unset($_SESSION['payfast_data']);
            unset($_SESSION['applied_promo']);
        }
    }

    // Always return HTTP 200 OK
    header('HTTP/1.0 200 OK');
    exit();
}

// Invalid access fallback
header('HTTP/1.0 400 Bad Request');
exit();
?>
