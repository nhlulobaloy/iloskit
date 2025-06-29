<?php
session_start();
include "db_connection.php";

// Check if there's an order to cancel
if (!isset($_SESSION['pending_order'])) {
    header("Location: index.php");
    exit;
}

// Get order details from session
$userId = $_SESSION['user_id'];
$orderData = $_SESSION['pending_order'];

// Generate order ID (but don't save to database yet)
$orderId = 'ILO' . date('Ymd') . $userId . mt_rand(1000, 9999);

// Create cancelled order record
$stmt = $conn->prepare("INSERT INTO orders (id, user_id, order_date, status, total_amount, delivery_option, delivery_address, courier_fee) 
                       VALUES (?, ?, NOW(), 'cancelled', ?, ?, ?, ?)");
$stmt->bind_param("sisdsi", $orderId, $userId, $orderData['total_amount'], 
                 $orderData['delivery_option'], $orderData['delivery_address'], $orderData['courier_fee']);
$stmt->execute();

// Add cancelled order items
$stmtItems = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, size, price_at_purchase, status) 
                            VALUES (?, ?, ?, ?, ?, 'cancelled')");

foreach ($orderData['cart_items'] as $item) {
    $price = $orderData['products'][$item['product_id']] ?? 0;
    $stmtItems->bind_param("iiisd", $orderId, $item['product_id'], $item['quantity'], $item['size'], $price);
    $stmtItems->execute();
}

// Log the cancellation
$conn->query("INSERT INTO payment_logs (order_id, payment_data, status) 
             VALUES ('$orderId', 'Payment cancelled by user', 'cancelled')");

// Clear session data
unset($_SESSION['pending_order']);
unset($_SESSION['payfast_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Cancelled - Ilo's Kit</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6c63ff;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
        }
        
        .cancel-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 2.5rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            border-top: 5px solid var(--danger-color);
        }
        
        .cancel-icon {
            font-size: 4rem;
            color: var(--danger-color);
            margin-bottom: 1.5rem;
        }
        
        .order-details {
            background-color: var(--light-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        
        .order-details p {
            margin-bottom: 0.5rem;
        }
        
        .btn-retry {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.5rem 1.5rem;
        }
        
        .btn-retry:hover {
            background-color: #5a52d6;
            border-color: #5a52d6;
        }
        
        @media (max-width: 768px) {
            .cancel-container {
                padding: 1.5rem;
                margin: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="cancel-container text-center">
            <div class="cancel-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <h1 class="mb-3">Payment Cancelled</h1>
            <p class="lead text-muted mb-4">Your payment was not completed.</p>
            
            <div class="order-details">
                <h5 class="mb-3"><i class="fas fa-receipt me-2"></i>Order Details</h5>
                <p><strong>Order ID:</strong> <?= htmlspecialchars($orderId) ?></p>
                <p><strong>Total Amount:</strong> R<?= number_format($orderData['total_amount'], 2) ?></p>
                <p><strong>Delivery To:</strong> <?= htmlspecialchars($orderData['delivery_address']) ?></p>
            </div>
            
            <div class="alert alert-warning mb-4">
                <p class="mb-0"><i class="fas fa-info-circle me-2"></i>Your order has been cancelled. No payment was processed.</p>
            </div>
            
            <div class="d-flex flex-column flex-md-row justify-content-center gap-3">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i> Return Home
                </a>
                <a href="cart.php" class="btn btn-retry text-white">
                    <i class="fas fa-shopping-cart me-2"></i> Retry Order
                </a>
            </div>
            
            <div class="mt-4 pt-3 border-top">
                <p class="text-muted small mb-2">Need help with your order?</p>
                <a href="mailto:iloskit1219@gmail.com" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-envelope me-1"></i> Contact Support
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>