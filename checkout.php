<?php
session_start();
include "db_connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?message=Please+login+to+checkout");
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: cart.php?message=Your+cart+is+empty");
    exit;
}

$productIds = array_unique(array_map(fn($i) => $i['product_id'], $cart));
$idsString = implode(',', array_map('intval', $productIds));
$result = $conn->query("SELECT id, name, price, image_url FROM products WHERE id IN ($idsString)");
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
$subtotalAfterDiscount = $subtotal - $discount;

$deliveryOptions = ['paxi' => 'Paxi', 'postnet' => 'PostNet', 'courier_guy' => 'Courier Guy'];
$paxiFees = ['Gauteng' => 60, 'KwaZulu-Natal' => 60, 'Western Cape' => 60, 'Eastern Cape' => 50, 'Free State' => 60, 'Limpopo' => 60, 'Mpumalanga' => 60, 'Northern Cape' => 60, 'North West' => 60];
$defaultLocation = 'Gauteng';
$defaultDelivery = 'paxi';

$userEmail = '';
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $res = $conn->query("SELECT email FROM users WHERE id = $uid LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $userEmail = $row['email'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout - Ilo's Kit</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root { --primary: #4a6bff; --secondary: #6c757d; --success: #28a745; --border-radius: 8px; }
body { background: #f5f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.checkout-container { max-width: 800px; margin: 2rem auto; padding: 2rem; background: #fff; border-radius: var(--border-radius); box-shadow: 0 2px 15px rgba(0,0,0,0.05); }
.checkout-header { color: var(--primary); font-weight: 700; border-bottom: 2px solid #eee; padding-bottom: 1rem; margin-bottom: 1.5rem; }
.section-title { color: var(--primary); font-weight: 600; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem; }
.form-control, .form-select { padding: 0.75rem 1rem; border-radius: var(--border-radius); border: 1px solid #ddd; }
.form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 0.25rem rgba(74,107,255,0.25); }
.btn-secondary, .btn-success { padding: 0.75rem 1.5rem; border-radius: var(--border-radius); font-weight: 500; border: none; }
.btn-secondary { background: var(--secondary); }
.btn-success { background: var(--success); }
.error-card { background: #f8d7da; padding: 1rem; border-radius: var(--border-radius); color: #842029; margin-bottom: 1.5rem; border-left: 4px solid #dc3545; }
.info-card { background: #e7f1ff; color: #0d6efd; padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.5rem; border-left: 4px solid var(--primary); }
.form-label { font-weight: 500; margin-bottom: 0.5rem; }
.text-muted { font-size: 0.85rem; color: #6c757d !important; }
.action-buttons { display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #eee; }
#payment_debug { margin-top: 1rem; color: red; }
@media (max-width: 768px) { .checkout-container { padding: 1.5rem; } .action-buttons { flex-direction: column-reverse; } .btn { width: 100%; } }
</style>
</head>
<body>
<div class="container py-4">
    <div class="checkout-container">
        <h1 class="checkout-header"><i class="fas fa-shopping-bag me-2"></i>Complete Your Order</h1>

        <form id="checkoutForm">
            <h5 class="section-title"><i class="fas fa-truck"></i> Delivery Information</h5>

            <div class="mb-4">
                <label class="form-label">Select your province:</label>
                <select id="paxi_location" class="form-select" required>
                    <option value="">-- Select Province --</option>
                    <?php foreach ($paxiFees as $loc => $fee): ?>
                        <option value="<?= $loc ?>" <?= ($defaultLocation == $loc) ? 'selected' : '' ?>><?= $loc ?> (R<?= number_format($fee, 2) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label">Select delivery option:</label>
                <select id="delivery_option" class="form-select" required>
                    <?php foreach ($deliveryOptions as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ($defaultDelivery == $key) ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label">Delivery Address:</label>
                <textarea id="delivery_address" class="form-control" rows="4" required placeholder="Street address or nearest pickup"></textarea>
                <small class="text-muted">Enter street address (e.g. 263 Paul Kruger, Pretoria) or pickup location.</small>

            </div>

            <div class="mb-4">
                <p><strong>Subtotal:</strong> R<span id="subtotal"><?= number_format($subtotalAfterDiscount, 2) ?></span></p>
                <p><strong>Delivery Fee:</strong> R<span id="delivery_fee">0.00</span></p>
                <p><strong>Total:</strong> R<span id="total_amount"><?= number_format($subtotalAfterDiscount, 2) ?></span></p>
            </div>

            <div id="payment_debug"></div>

            <div class="action-buttons">
                <a href="cart.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back to Cart</a>
                <button type="button" id="pay-button" class="btn btn-success"><i class="fas fa-lock me-2"></i>Pay with Card</button>
            </div>
        </form>
    </div>
</div>

<script>
// Pass PHP values to JavaScript correctly
const paxiFees = <?= json_encode($paxiFees) ?>;
const subtotal = <?= $subtotalAfterDiscount ?>; // This is the critical fix - use the calculated subtotal from PHP
const customerEmail = '<?= $userEmail ?>';

console.log("Subtotal from PHP:", subtotal); // Debugging line

function calculateDeliveryFee() {
    const option = document.getElementById('delivery_option').value;
    const province = document.getElementById('paxi_location').value;
    const address = document.getElementById('delivery_address').value.toLowerCase();
    let fee = 0;
    if (option === 'paxi') {
        fee = (address.includes('grahamstown') || address.includes('makhanda')) ? 0 : (paxiFees[province] ?? 60);
    } else {
        fee = 90; // Default fee for other carriers
    }
    return fee;
}

function updateTotals() {
    const deliveryFee = calculateDeliveryFee();
    const total = subtotal + deliveryFee; // Use the subtotal from PHP

    // Update the displayed values
    document.getElementById('delivery_fee').textContent = deliveryFee.toFixed(2);
    document.getElementById('total_amount').textContent = total.toFixed(2);
    
    console.log("UpdateTotals - Subtotal:", subtotal, "Delivery Fee:", deliveryFee, "Total:", total); // Debugging
}

// Add event listeners to update totals when inputs change
document.getElementById('delivery_option').addEventListener('change', updateTotals);
document.getElementById('paxi_location').addEventListener('change', updateTotals);
document.getElementById('delivery_address').addEventListener('input', updateTotals);

// Initialize totals on page load
window.addEventListener('load', function() {
    updateTotals();
    console.log("Page loaded. Initial total:", document.getElementById('total_amount').textContent); // Debugging
});

// NEW Yoco Checkout API Integration
document.getElementById('pay-button').addEventListener('click', async function (e) {
    e.preventDefault();
    const payButton = this;
    const originalText = payButton.innerHTML;
    
    // Show loading state
    payButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    payButton.disabled = true;

    // Get the current total from the page (should be correctly calculated now)
    const totalText = document.getElementById('total_amount').textContent;
    const total = parseFloat(totalText.replace(/,/g, '')); // Remove commas for parsing
    
    console.log("Pay button clicked. Total parsed:", total); // Debugging
    
    if (isNaN(total) || total < 0.5) { 
        alert("Total must be at least R0.50. Current total: R" + total); 
        payButton.innerHTML = originalText;
        payButton.disabled = false;
        return; 
    }
    
    const amountInCents = Math.round(total * 100);
    console.log("Amount in cents:", amountInCents); // Debugging

    const checkoutData = {
        amountInCents: amountInCents,
        delivery_address: document.getElementById('delivery_address').value,
        province: document.getElementById('paxi_location').value,
        delivery_option: document.getElementById('delivery_option').value
    };

    try {
        const response = await fetch('create_checkout_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(checkoutData)
        });

        const result = await response.json();
        if (result.success) {
            window.location.href = result.redirectUrl;
        } else {
            console.error("Server error:", result);
            let errorMsg = "Failed to initialize payment. Please try again.";
            if (result.error) errorMsg = result.error;
            alert(errorMsg);
            document.getElementById('payment_debug').textContent = "Error: " + JSON.stringify(result);
        }
    } catch (error) {
        console.error("Fetch error:", error);
        alert("A network error occurred. Please check your connection.");
        document.getElementById('payment_debug').textContent = "Network Error: " + error.message;
    } finally {
        // Reset button state
        payButton.innerHTML = originalText;
        payButton.disabled = false;
    }
});
</script>
</body>
</html>