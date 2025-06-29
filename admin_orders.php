<?php 
session_start();
include "db_connection.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?message=Please+login+as+admin");
    exit;
}

$userId = $_SESSION['user_id'];

// Check admin rights
$stmtAdmin = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmtAdmin->bind_param("i", $userId);
$stmtAdmin->execute();
$stmtAdmin->bind_result($isAdmin);
$stmtAdmin->fetch();
$stmtAdmin->close();

if (!$isAdmin) {
    die("Access denied. Admins only.");
}

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['order_status'];

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $orderId);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_orders.php");
    exit;
}

// Update item status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item_status'])) {
    $itemId = (int)$_POST['item_id'];
    $newStatus = $_POST['item_status'];

    $stmt = $conn->prepare("UPDATE order_items SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $itemId);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_orders.php");
    exit;
}

// Get orders with user details including phone number
$sql = "SELECT o.*, u.name AS user_name, u.email, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.order_date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Orders - Ilo's Kit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-nav {
            background-color: #343a40;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 5px;
        }
        .admin-nav .nav-link {
            color: rgba(255,255,255,.8);
            margin-right: 1rem;
            transition: all 0.3s;
        }
        .admin-nav .nav-link:hover {
            color: white;
            transform: translateY(-2px);
        }
        .admin-nav .nav-link.active {
            color: white;
            font-weight: bold;
        }
        .order-card {
            transition: all 0.3s;
            margin-bottom: 1.5rem;
        }
        .order-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }
        .item-img {
            width: 60px;
            height: auto;
            object-fit: contain;
            border-radius: 4px;
        }
        .status-select {
            min-width: 120px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Admin Navigation -->
            <nav class="admin-nav mb-4">
                <div class="d-flex flex-wrap align-items-center">
                    <h4 class="text-white me-4 mb-0">Admin Panel</h4>
                    <a href="admin_dashboard.php" class="nav-link">Dashboard</a>
                    <a href="admin_orders.php" class="nav-link active">Orders</a>
                    <a href="admin_products.php" class="nav-link">Products</a>
                    <a href="admin_users.php" class="nav-link">Users</a>
                    <a href="upload_image.php" class="nav-link">Upload Products</a>
                    <a href="index.php" class="nav-link ms-auto">View Store</a>
                </div>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">Order Management</h2>

            <?php if ($result->num_rows === 0): ?>
                <div class="alert alert-info">No orders found.</div>
            <?php else: ?>
                <?php while ($order = $result->fetch_assoc()): ?>
                    <div class="card order-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Order #<?= htmlspecialchars($order['id']) ?></strong> - 
                                <?= htmlspecialchars($order['user_name']) ?> 
                                <span class="badge bg-<?= 
                                    $order['status'] == 'pending' ? 'warning' : 
                                    ($order['status'] == 'processing' ? 'info' : 
                                    ($order['status'] == 'shipped' ? 'primary' : 
                                    ($order['status'] == 'delivered' ? 'success' : 'danger')))
                                ?> status-badge ms-2">
                                    <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                </span>
                            </div>
                            <div class="text-muted">
                                <?= date("F j, Y, g:i a", strtotime($order['order_date'])) ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p><strong>Customer:</strong> <?= htmlspecialchars($order['user_name']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                                    <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone'] ?? 'N/A') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Amount:</strong> R<?= number_format($order['total_amount'], 2) ?></p>
                                    <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order['delivery_address']) ?></p>
                                </div>
                            </div>

                            <!-- Order status form -->
                            <form method="post" class="mb-4">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <label for="order_status_<?= htmlspecialchars($order['id']) ?>" class="form-label">Update Order Status:</label>
                                    </div>
                                    <div class="col-md-6">
                                        <select name="order_status" id="order_status_<?= htmlspecialchars($order['id']) ?>" class="form-select status-select">
                                            <?php
                                            $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                                            foreach ($statuses as $status):
                                            ?>
                                                <option value="<?= htmlspecialchars($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>>
                                                    <?= ucfirst(htmlspecialchars($status)) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                                        <button type="submit" name="update_order_status" class="btn btn-primary w-100">Update</button>
                                    </div>
                                </div>
                            </form>

                            <!-- Order items -->
                            <?php
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

                            <h5 class="mb-3">Order Items</h5>
                            <?php if ($itemsResult->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Product</th>
                                                <th>Image</th>
                                                <th>Size</th>
                                                <th>Qty</th>
                                                <th>Price (R)</th>
                                                <th>Status</th>
                                                <th>Update</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($item = $itemsResult->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['name']) ?></td>
                                                    <td>
                                                        <img src="kit_images/<?= htmlspecialchars($item['image_url']) ?>" 
                                                             alt="<?= htmlspecialchars($item['name']) ?>" 
                                                             class="item-img">
                                                    </td>
                                                    <td><?= htmlspecialchars($item['size']) ?></td>
                                                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                                                    <td><?= number_format($item['price_at_purchase'], 2) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= 
                                                            $item['status'] == 'pending' ? 'warning' : 
                                                            ($item['status'] == 'shipped' ? 'primary' : 
                                                            ($item['status'] == 'delivered' ? 'success' : 'secondary'))
                                                        ?>">
                                                            <?= ucfirst(htmlspecialchars($item['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <form method="post" class="d-flex">
                                                            <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['id']) ?>">
                                                            <select name="item_status" class="form-select form-select-sm me-2">
                                                                <?php
                                                                $itemStatuses = ['pending', 'shipped', 'delivered', 'returned'];
                                                                foreach ($itemStatuses as $istat):
                                                                ?>
                                                                    <option value="<?= htmlspecialchars($istat) ?>" <?= $item['status'] === $istat ? 'selected' : '' ?>>
                                                                        <?= ucfirst(htmlspecialchars($istat)) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button type="submit" name="update_item_status" class="btn btn-sm btn-outline-primary">Update</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">No items found for this order.</div>
                            <?php endif; ?>

                            <?php $stmtItems->close(); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>