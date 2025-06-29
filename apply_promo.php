<?php
session_start();
include "db_connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_promo'])) {
        unset($_SESSION['applied_promo']);
        $_SESSION['promo_success'] = "Promo code removed successfully";
        header("Location: cart.php");
        exit;
    }

    $promoCode = trim($_POST['promo_code'] ?? '');

    if (empty($promoCode)) {
        $_SESSION['promo_error'] = "Please enter a promo code";
        header("Location: cart.php");
        exit;
    }

    // Check if promo code exists and is valid
    $currentDate = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("SELECT * FROM promo_codes 
                           WHERE code = ? 
                           AND start_date <= ? 
                           AND end_date >= ? 
                           AND (uses_remaining IS NULL OR uses_remaining > 0)");
    $stmt->bind_param("sss", $promoCode, $currentDate, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['promo_error'] = "Invalid or expired promo code";
        header("Location: cart.php");
        exit;
    }

    $promo = $result->fetch_assoc();

    // Calculate cart total using product prices from DB
    $cartTotal = 0;

    if (!empty($_SESSION['cart'])) {
        $productIds = array_column($_SESSION['cart'], 'product_id');
        $productIds = array_unique($productIds);
        $idsString = implode(',', array_map('intval', $productIds));
        $sql = "SELECT id, price FROM products WHERE id IN ($idsString)";
        $priceResult = $conn->query($sql);

        $prices = [];
        while ($row = $priceResult->fetch_assoc()) {
            $prices[$row['id']] = $row['price'];
        }

        foreach ($_SESSION['cart'] as $item) {
            $prodId = $item['product_id'];
            $qty = $item['quantity'];
            if (isset($prices[$prodId])) {
                $cartTotal += $prices[$prodId] * $qty;
            }
        }
    }

    if ($cartTotal < $promo['min_order']) {
        $_SESSION['promo_error'] = "This promo code requires a minimum order of R" . number_format($promo['min_order'], 2);
        header("Location: cart.php");
        exit;
    }

    // Store promo in session
    $_SESSION['applied_promo'] = [
        'code' => $promo['code'],
        'discount_type' => $promo['discount_type'],
        'discount_value' => $promo['discount_value']
    ];

    // REMOVE decrement of uses_remaining here!

    $_SESSION['promo_success'] = "Promo code applied successfully!";
}

header("Location: cart.php");
exit;
