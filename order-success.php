<?php
session_start();
include "db_connection.php";

// 1. Verify PayFast payment data (critical for security)
if (!isset($_POST['pf_payment_id']) || !isset($_SESSION['user_id'])) {
    header("Location: cart.php?error=invalid_payment");
    exit;
}

// 2. Validate PayFast signature
function validatePayFastSignature($pfData, $merchantKey) {
    $signature = md5(http_build_query($pfData) . "&passphrase=" . urlencode($merchantKey));
    return ($signature === $pfData['signature']);
}

$merchantKey = 'ndvo45gntqyst'; // Your PayFast merchant key
$isValid = validatePayFastSignature($_POST, $merchantKey);

if (!$isValid) {
    // Log failed verification attempt
    error_log("PayFast signature verification failed for payment: " . $_POST['pf_payment_id']);
    header("Location: payment_failed.php?reason=security_validation");
    exit;
}

// 3. Get the order from database using PayFast's m_payment_id
$orderId = $_POST['m_payment_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("si", $orderId, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: index.php?error=order_not_found");
    exit;
}

// 4. Update order status to 'paid' if payment succeeded
if ($_POST['payment_status'] === 'COMPLETE') {
    $update = $conn->prepare("UPDATE orders SET status = 'paid', pf_payment_id = ? WHERE order_id = ?");
    $update->bind_param("ss", $_POST['pf_payment_id'], $orderId);
    $update->execute();
    
    // Clear cart and pending order
    unset($_SESSION['cart']);
    unset($_SESSION['pending_order']);
    unset($_SESSION['applied_promo']);
}

// 5. Prepare order details for display
$orderItems = $conn->query("
    SELECT p.name, p.image_url, oi.quantity, oi.price 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = '$orderId'
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - Ilo's Kit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .confirmation-card {
            border-left: 5px solid #28a745;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }
        .product-img { max-height: 80px; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card confirmation-card">
                    <div class="card-header bg-white">
                        <h2 class="text-success mb-0"><i class="fas fa-check-circle me-2"></i>Order Confirmed!</h2>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <h5 class="alert-heading">Thank you for your order!</h5>
                            <p class="mb-0">Your payment was successful. Order ID: <strong><?= $orderId ?></strong></p>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5><i class="fas fa-truck me-2"></i>Delivery Info</h5>
                                <p><?= htmlspecialchars($order['delivery_address']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                                <table class="table table-sm">
                                    <tr>
                                        <td>Subtotal:</td>
                                        <td class="text-end">R<?= number_format($order['subtotal'], 2) ?></td>
                                    </tr>
                                    <?php if ($order['discount'] > 0): ?>
                                    <tr class="text-success">
                                        <td>Discount:</td>
                                        <td class="text-end">-R<?= number_format($order['discount'], 2) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td>Shipping:</td>
                                        <td class="text-end">R<?= number_format($order['courier_fee'], 2) ?></td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td>Total Paid:</td>
                                        <td class="text-end">R<?= number_format($order['total_amount'], 2) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <h5 class="mb-3"><i class="fas fa-boxes me-2"></i>Your Items</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <tbody>
                                    <?php while ($item = $orderItems->fetch_assoc()): ?>
                                    <tr>
                                        <td style="width: 80px">
                                            <img src="kit_images/<?= htmlspecialchars($item['image_url']) ?>" 
                                                 alt="<?= htmlspecialchars($item['name']) ?>" 
                                                 class="product-img img-thumbnail">
                                        </td>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td>x<?= $item['quantity'] ?></td>
                                        <td class="text-end">R<?= number_format($item['price'], 2) ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="index.php" class="btn btn-outline-secondary me-md-2">
                                <i class="fas fa-home me-1"></i> Back to Home
                            </a>
                            <a href="order_history.php" class="btn btn-primary">
                                <i class="fas fa-history me-1"></i> View Order History
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>