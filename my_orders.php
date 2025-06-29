<?php
session_start();
include "db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?message=Please+login+to+view+your+orders");
    exit;
}

$userId = $_SESSION['user_id'];

// Get all user orders including delivery location and address
$stmtOrders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmtOrders->bind_param("i", $userId);
$stmtOrders->execute();
$ordersResult = $stmtOrders->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Ilo's Kit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6c63ff;
            --secondary-color: #f8f9fa;
            --accent-color: #ff6b6b;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .order-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .order-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-bottom: none;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-processing {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .back-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background-color: #5a52d6;
            transform: translateX(-5px);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 5rem;
            color: #d1d5db;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fas fa-receipt me-2"></i> My Orders</h2>
        <a href="index.php" class="btn back-btn">
            <i class="fas fa-arrow-left me-2"></i> Back to Home
        </a>
    </div>

    <?php if ($ordersResult->num_rows === 0): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3 class="mb-3">No Orders Yet</h3>
            <p class="text-muted mb-4">You haven't placed any orders with us yet. Let's change that!</p>
            <a href="index.php" class="btn btn-primary px-4 py-2">
                <i class="fas fa-shopping-bag me-2"></i> Start Shopping
            </a>
        </div>
    <?php else: ?>
        <?php while ($order = $ordersResult->fetch_assoc()): ?>
            <div class="card order-card mb-4">
                <div class="order-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Order #<?= htmlspecialchars($order['id']) ?></h5>
                        <small class="text-white-50">Placed on <?= date("F j, Y, g:i a", strtotime($order['order_date'])) ?></small>
                    </div>
                    <span class="status-badge status-<?= strtolower(htmlspecialchars($order['status'])) ?>">
                        <?= ucfirst(htmlspecialchars($order['status'])) ?>
                    </span>
                </div>
                
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-wallet me-2 text-muted"></i>
                                <div>
                                    <small class="text-muted">Total Amount</small>
                                    <h5 class="mb-0">R<?= number_format($order['total_amount'], 2) ?></h5>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-truck me-2 text-muted"></i>
                                <div>
                                    <small class="text-muted">Delivery Method</small>
                                    <h5 class="mb-0"><?= ucfirst(htmlspecialchars($order['delivery_option'])) ?></h5>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                <div>
                                    <small class="text-muted">Delivery Address</small>
                                    <h5 class="mb-0"><?= nl2br(htmlspecialchars($order['delivery_address'] ?? 'Not specified')) ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3"><i class="fas fa-boxes me-2"></i> Order Items</h5>
                    
                    <?php
                    // Fetch items for this order
                    $stmtItems = $conn->prepare("
                        SELECT oi.*, p.name, p.image_url 
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?
                    ");
                    $stmtItems->bind_param("i", $order['id']);
                    $stmtItems->execute();
                    $itemsResult = $stmtItems->get_result();
                    ?>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Size</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $itemsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">

                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                                    <small class="text-muted">Status: <?= ucfirst(htmlspecialchars($item['status'])) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= (int)$item['quantity'] ?></td>
                                        <td>R<?= number_format($item['price_at_purchase'], 2) ?></td>
                                        <td>R<?= number_format($item['price_at_purchase'] * $item['quantity'], 2) ?></td>
                                        <td><?= htmlspecialchars($item['size']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php $stmtItems->close(); ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>