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

// Get product prices
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
if (isset($_SESSION['applied_promo'])) {
    $promo = $_SESSION['applied_promo'];
    if ($promo['discount_type'] == 'percentage') {
        $discount = $subtotal * ($promo['discount_value'] / 100);
    } else {
        $discount = $promo['discount_value'];
    }
}
$subtotalAfterDiscount = $subtotal - $discount;

// Delivery fees
$paxiLocations = [
    'Johannesburg CBD' => 50,
    'Sandton' => 60,
    'Midrand' => 55,
    'Roodepoort' => 45,
    'Soweto' => 40
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $selectedLocation = $_POST['paxi_location'] ?? '';
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');

    // Validate inputs
    if (!array_key_exists($selectedLocation, $paxiLocations)) {
        $errors[] = "Please select a valid Paxi location.";
    }
    if (empty($deliveryAddress)) {
        $errors[] = "Please enter your delivery address.";
    }

    if (empty($errors)) {
        // Calculate total
        $courierFee = $paxiLocations[$selectedLocation];
        $total = $subtotalAfterDiscount + $courierFee;
        
        // Generate a unique order ID
        $orderId = 'ILO-' . time() . '-' . rand(1000, 9999);
        
        // Store order details in session for payment processing
        $_SESSION['pending_order'] = [
            'user_id' => $userId,
            'order_id' => $orderId,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'courier_fee' => $courierFee,
            'total_amount' => $total,
            'delivery_option' => 'paxi',
            'delivery_address' => $deliveryAddress,
            'paxi_location' => $selectedLocation,
            'cart_items' => $cart,
            'products' => $products,
            'promo_code' => $_SESSION['applied_promo']['code'] ?? null
        ];

        // PayFast API credentials (YOUR ACTUAL CREDENTIALS)
        $merchantId = '18931794';
        $merchantKey = 'ndvo45gntqyst';
        
        // PayFast payment data
        $payfastData = [
            'merchant_id' => $merchantId,
            'merchant_key' => $merchantKey,
            'return_url' => 'https://iloskit.co.za/payment_success.php',
            'cancel_url' => 'https://iloskit.co.za/payment_cancel.php',
            'notify_url' => 'https://iloskit.co.za/payment_notify.php',
            'name_first' => $_SESSION['user_firstname'] ?? '',
            'name_last' => $_SESSION['user_lastname'] ?? '',
            'email_address' => $_SESSION['user_email'] ?? '',
            'm_payment_id' => $orderId,
            'amount' => number_format($total, 2, '.', ''),
            'item_name' => 'Order #' . $orderId,
            'item_description' => 'Products from Ilo\'s Kit',
            'custom_int1' => $userId,
            'custom_str1' => $deliveryAddress,
            'custom_str2' => $_SESSION['applied_promo']['code'] ?? ''
        ];

        // Generate signature
        $signature = md5(http_build_query($payfastData));
        $payfastData['signature'] = $signature;

        // Store PayFast data in session
        $_SESSION['payfast_data'] = $payfastData;
        
        // Redirect to payment processor
        header("Location: https://www.payfast.co.za/eng/process?" . http_build_query($payfastData));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checkout - Ilo's Kit</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6c63ff;
            --secondary-color: #f8f9fa;
            --accent-color: #ff6b6b;
            --dark-color: #343a40;
            --light-color: #ffffff;
        }
        
        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .checkout-container {
            background-color: var(--light-color);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .checkout-header {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .summary-card {
            background-color: var(--secondary-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(108, 99, 255, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #5a52d6;
            border-color: #5a52d6;
        }
        
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
            padding: 10px 25px;
            font-weight: 600;
        }
        
        .btn-secondary {
            padding: 10px 25px;
        }
        
        .total-display {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--dark-color);
        }
        
        .error-card {
            background-color: #ffebee;
            border-left: 4px solid #f44336;
            padding: 25px;
        }
        
        .discount-badge {
            background-color: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="checkout-container">
                    <h1 class="checkout-header mb-4">
                        <i class="fas fa-shopping-bag me-2"></i>Checkout
                    </h1>

                    <?php if (!empty($errors)): ?>
                        <div class="error-card mb-4">
                            <h4 class="mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Please fix these errors:</h4>
                            <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="summary-card">
                        <h5 class="mb-3"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-box-open me-2"></i>Total items:</strong> <?= array_sum(array_column($cart, 'quantity')) ?></p>
                                <p><strong><i class="fas fa-tag me-2"></i>Subtotal:</strong> R<?= number_format($subtotal, 2) ?></p>
                                <?php if (isset($_SESSION['applied_promo'])): ?>
                                    <p class="text-success">
                                        <strong><i class="fas fa-tag me-2"></i>Discount:</strong> 
                                        -R<?= number_format($discount, 2) ?>
                                        <span class="discount-badge"><?= $_SESSION['applied_promo']['code'] ?></span>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-truck me-2"></i>Courier fee:</strong> R<span id="courier_fee">0.00</span></p>
                                <p class="total-display"><strong><i class="fas fa-money-bill-wave me-2"></i>Total amount:</strong> R<span id="total_amount"><?= number_format($subtotalAfterDiscount, 2) ?></span></p>
                            </div>
                        </div>
                    </div>

                    <form method="post" novalidate>
                        <h5 class="mb-3"><i class="fas fa-truck me-2"></i>Delivery Information</h5>
                        
                        <div class="mb-4">
                            <label for="paxi_location" class="form-label">Select nearest Paxi location:</label>
                            <select id="paxi_location" name="paxi_location" class="form-select form-select-lg" required>
                                <option value="">-- Select Location --</option>
                                <?php foreach ($paxiLocations as $location => $fee): ?>
                                    <option value="<?= htmlspecialchars($location) ?>" <?= (($_POST['paxi_location'] ?? '') === $location) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($location) ?> (R<?= number_format($fee, 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">We'll deliver to your nearest Paxi pickup point</div>
                        </div>

                        <div class="mb-4">
                            <label for="delivery_address" class="form-label">Delivery Address:</label>
                            <textarea id="delivery_address" name="delivery_address" class="form-control" rows="3" required placeholder="Provide your full delivery address"><?= htmlspecialchars($_POST['delivery_address'] ?? '') ?></textarea>
                            <div class="form-text">Please include any special delivery instructions</div>
                        </div>

                        <div class="d-grid gap-3 d-md-flex justify-content-md-end mt-4">
                            <a href="cart.php" class="btn btn-secondary me-md-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Cart
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-lock me-2"></i>Proceed to Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateTotal() {
            const subtotal = <?= $subtotalAfterDiscount ?>;
            const paxiFees = <?= json_encode($paxiLocations) ?>;
            const location = document.getElementById('paxi_location').value;
            let courierFee = paxiFees[location] || 0;

            document.getElementById('courier_fee').textContent = courierFee.toFixed(2);
            document.getElementById('total_amount').textContent = (subtotal + courierFee).toFixed(2);
        }

        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('paxi_location').addEventListener('change', updateTotal);
            updateTotal();
        });
    </script>
</body>
</html>