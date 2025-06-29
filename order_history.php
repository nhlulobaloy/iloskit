<?php
session_start();
include "db_connection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?message=Please+login+to+view+your+orders");
    exit;
}

$userId = $_SESSION['user_id'];

// Get user's orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$ordersResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Orders - Ilo's Kit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3">

<h2>Your Order History</h2>

<?php if ($ordersResult->num_rows === 0): ?>
    <p>You have not placed any orders yet.</p>
<?php else: ?>
    <?php while ($order = $ordersResult->fetch_assoc()): ?>
        <div class="card mb-4">
            <div class="card-header">
                <strong>Order #<?= $order['id'] ?></strong> - <?= date("F j, Y, g:i a", strtotime($order['order_date'])) ?>
                <span class="badge bg-info text-dark ms-2"><?= htmlspecialchars($order['status']) ?></span>
                <span class="float-end">Total: R<?= number_format($order['total_amount'], 2) ?></span>
            </div>
            <div class="card-body">
                <?php
                // Get order items
                $stmtItems = $conn->prepare("
                    SELECT oi.*, p.name 
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?
                ");
                $stmtItems->bind_param("i", $order['id']);
                $stmtItems->execute();
                $itemsResult = $stmtItems->get_result();
                ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Price (R)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $itemsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= number_format($item['price_at_purchase'], 2) ?></td>
                                <td><?= htmlspecialchars($item['status']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php $stmtItems->close(); ?>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<p><a href="index.php" class="btn btn-secondary">Back to Home</a></p>

</body>
</html>
