<?php
session_start();
include "db_connection.php";

// Initialize variables
$errors = [];
$cart = $_SESSION['cart'] ?? [];
$products = [];
$subtotal = 0;

// Redirect if not logged in or cart is empty
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?message=Please+login+to+checkout");
    exit;
}

if (empty($cart)) {
    header("Location: cart.php?message=Your+cart+is+empty");
    exit;
}

// Fetch product prices
$productIds = array_unique(array_map(fn($item) => $item['product_id'], $cart));
$idsString = implode(',', array_map('intval', $productIds));
$query = "SELECT id, price FROM products WHERE id IN ($idsString)";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $products[$row['id']] = $row['price'];
}

// Calculate subtotal
foreach ($cart as $item) {
    $pid = $item['product_id'];
    $subtotal += ($products[$pid] ?? 0) * $item['quantity'];
}

// Apply promo discount if exists
$discount = 0;
$promoCodeUsed = null;

if (isset($_SESSION['applied_promo'])) {
    $promo = $_SESSION['applied_promo'];
    $promoCodeUsed = $promo['code'];

    if ($promo['discount_type'] === 'percentage') {
        $discount = $subtotal * ($promo['discount_value'] / 100);
    } else {
        $discount = $promo['discount_value'];
    }
}

$subtotalAfterDiscount = $subtotal - $discount;

// Define delivery fees per province
$paxiLocations = [
    'Gauteng' => 60,
    'KwaZulu-Natal' => 60,
    'Western Cape' => 60,
    'Eastern Cape' => 50,
    'Free State' => 60,
    'Limpopo' => 60,
    'Mpumalanga' => 60,
    'Northern Cape' => 60,
    'North West' => 60
];

$defaultLocation = 'Gauteng';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $selectedLocation = $_POST['paxi_location'] ?? $defaultLocation;
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');

    if (!array_key_exists($selectedLocation, $paxiLocations)) {
        $errors[] = "Please select a valid province for Paxi location.";
    }

    if (empty($deliveryAddress)) {
        $errors[] = "Please enter your delivery address.";
    }

    if (empty($errors)) {
        $normalizedAddress = strtolower($deliveryAddress);
        $courierFee = (strpos($normalizedAddress, 'grahamstown') !== false || strpos($normalizedAddress, 'makhanda') !== false)
            ? 0
            : $paxiLocations[$selectedLocation];

        $total = $subtotalAfterDiscount + $courierFee;
        $orderId = 'ILO-' . time() . '-' . rand(1000, 9999);
        $orderDate = date('Y-m-d H:i:s');
        $paymentMethod = 'Bank Transfer';
        $paymentReference = $orderId;
        $orderStatus = 'pending';
        $deliveryOption = 'paxi';

        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (
            id, user_id, order_date, status, total_amount,
            province, delivery_fee, delivery_option,
            delivery_address, payment_method,
            payment_reference
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "sissdsdssss",
            $orderId, $userId, $orderDate, $orderStatus, $total,
            $selectedLocation, $courierFee, $deliveryOption,
            $deliveryAddress, $paymentMethod, $paymentReference
        );
        $stmt->execute();
        $stmt->close();

        // Insert order items
        $itemStmt = $conn->prepare("INSERT INTO order_items (
            order_id, product_id, quantity, price_at_purchase, status, size
        ) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($cart as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            $price = $products[$productId] ?? 0;
            $status = 'pending';
            $size = $item['size'] ?? 'M';

            $itemStmt->bind_param("siidss", $orderId, $productId, $quantity, $price, $status, $size);
            $itemStmt->execute();
        }

        $itemStmt->close();

        // Decrease uses_remaining for promo
        if ($promoCodeUsed) {
            $updatePromo = $conn->prepare("UPDATE promo_codes SET uses_remaining = uses_remaining - 1 WHERE code = ? AND uses_remaining > 0");
            $updatePromo->bind_param("s", $promoCodeUsed);
            $updatePromo->execute();
            $updatePromo->close();
        }

        $_SESSION['pending_order'] = [
            'user_id' => $userId,
            'order_id' => $orderId,
            'total_amount' => $total,
            'province' => $selectedLocation,
            'delivery_address' => $deliveryAddress,
            'payment_method' => $paymentMethod
        ];

        unset($_SESSION['cart'], $_SESSION['applied_promo']);
        header("Location: bank_transfer_instructions.php?order_id=$orderId");
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checkout - Ilo's Kit</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .item-img { width: 120px; border-radius: 4px; }
        .error-card { background-color: #f8d7da; padding: 1rem; border-radius: 5px; color: #842029; margin-bottom: 1rem; }
        .discount-badge { background-color: #d1e7dd; color: #0f5132; padding: 0.2em 0.5em; border-radius: 4px; font-weight: 600; margin-left: 0.5em; }
        .summary-card { background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 2rem; }
        .checkout-header { font-weight: 700; }
        #freeNotice { color: green; font-weight: 600; display: none; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="checkout-header mb-4"><i class="fas fa-shopping-bag me-2"></i>Checkout</h1>

            <?php if (!empty($errors)): ?>
                <div class="error-card mb-4">
                    <h4><i class="fas fa-exclamation-triangle me-2"></i>Please fix these errors:</h4>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Your order will be delivered to your nearest <strong>Paxi</strong> location for easy pickup. 
                Please select your province and specify your exact address or preferred Paxi location below.
            </div>

            <form method="post">
                <h5 class="mb-3"><i class="fas fa-truck me-2"></i>Delivery Information</h5>

                <div class="mb-4">
                    <label for="paxi_location" class="form-label">Select your province:</label>
                    <select id="paxi_location" name="paxi_location" class="form-select form-select-lg" required>
                        <option value="">-- Select Province --</option>
                        <?php foreach ($paxiLocations as $location => $fee): ?>
                            <option value="<?= htmlspecialchars($location) ?>" <?= 
                                (($_POST['paxi_location'] ?? $defaultLocation) === $location) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location) ?> (R<?= number_format($fee, 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="delivery_address" class="form-label">Delivery Address:</label>
                    <textarea id="delivery_address" name="delivery_address" class="form-control" rows="3" required 
                        placeholder="e.g.Pretoria 345 Jane Doe OR specify your nearest Paxi location."><?= htmlspecialchars($_POST['delivery_address'] ?? '') ?></textarea>
                    <small class="text-muted">You can specify your street address or the nearest Paxi location.</small>
                </div>

                <div class="d-flex justify-content-end gap-3 mt-4">
                    <a href="cart.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Cart</a>
                    <button type="submit" class="btn btn-success"><i class="fas fa-lock me-2"></i>Proceed to Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    const paxiFees = <?= json_encode($paxiLocations) ?>;
    const subtotal = <?= $subtotalAfterDiscount ?>;

    function updateTotal() {
        const location = document.getElementById('paxi_location').value;
        const address = document.getElementById('delivery_address').value.toLowerCase();
        const freeNotice = document.getElementById('freeNotice');

        let courierFee = 0;
        if (!address.includes('grahamstown') && !address.includes('makhanda')) {
            courierFee = paxiFees[location] || 0;
            freeNotice.style.display = 'none';
        } else {
            courierFee = 0;
            freeNotice.style.display = 'block';
        }

        document.getElementById('courier_fee').textContent = courierFee.toFixed(2);
        document.getElementById('total_amount').textContent = (subtotal + courierFee).toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('paxi_location').addEventListener('change', updateTotal);
        document.getElementById('delivery_address').addEventListener('input', updateTotal);
        updateTotal(); // Call initially
    });
</script>
</body>
</html>
