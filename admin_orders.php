<?php  
session_start();
require "db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?message=Please+login+as+admin");
    exit;
}

// Check admin rights more securely
$stmtAdmin = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
if (!$stmtAdmin) {
    die("Database error: " . $conn->error);
}
$stmtAdmin->bind_param("i", $_SESSION['user_id']);
$stmtAdmin->execute();
$stmtAdmin->bind_result($isAdmin);
$stmtAdmin->fetch();
$stmtAdmin->close();

if (!$isAdmin) {
    die("Access denied. Admins only.");
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $orderId = $_POST['order_id']; // keep as string
    $newStatus = $conn->real_escape_string($_POST['order_status']);

    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }
    $stmt->bind_param("ss", $newStatus, $orderId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_message'] = "Order #$orderId status updated to " . ucfirst($newStatus);
    header("Location: admin_orders.php");
    exit;
}

// Handle filtering and searching
$statusFilter = $_GET['status'] ?? '';
$orderSearch = $_GET['order_search'] ?? '';

$whereClauses = [];
$params = [];
$paramTypes = "";

// Filter by status if provided and valid
$validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if ($statusFilter && in_array($statusFilter, $validStatuses)) {
    $whereClauses[] = "o.status = ?";
    $params[] = $statusFilter;
    $paramTypes .= "s";
}

// Search by order number (partial match)
if ($orderSearch) {
    $whereClauses[] = "o.id LIKE ?";
    $params[] = "%$orderSearch%";
    $paramTypes .= "s";
}

$whereSQL = "";
if (count($whereClauses) > 0) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

$sql = "SELECT o.*, u.name AS user_name, u.email, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        $whereSQL
        ORDER BY o.order_date DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
if (count($params) > 0) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

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
        }
        .admin-nav .nav-link:hover {
            color: white;
        }
        .admin-nav .nav-link.active {
            color: white;
            font-weight: bold;
        }
        .order-card {
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .status-select {
            min-width: 120px;
        }
        .delivery-option-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 8px;
            vertical-align: middle;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Success Message -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3">
            <?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

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

            <h2 class="mb-4">Order Management</h2>

            <!-- Filter & Search Form -->
            <form method="get" class="row g-3 mb-4">
                <div class="col-auto">
                    <select name="status" class="form-select">
                        <option value="">Filter by status (all)</option>
                        <?php foreach ($validStatuses as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                                <?= ucfirst($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <input 
                        type="text" 
                        name="order_search" 
                        class="form-control" 
                        placeholder="Search by order number" 
                        value="<?= htmlspecialchars($orderSearch) ?>"
                    >
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Filter/Search</button>
                    <a href="admin_orders.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <?php if ($result->num_rows === 0): ?>
                <div class="alert alert-info">No orders found.</div>
            <?php else: ?>
                <?php while ($order = $result->fetch_assoc()): ?>

                    <?php
                    // Fetch first product image for this order
                    $stmtImage = $conn->prepare("
                        SELECT p.image_url
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?
                        LIMIT 1
                    ");
                    $stmtImage->bind_param("s", $order['id']);
                    $stmtImage->execute();
                    $imgResult = $stmtImage->get_result();
                    $imageUrl = $imgResult->num_rows > 0 ? $imgResult->fetch_assoc()['image_url'] : null;
                    $stmtImage->close();

                    // fallback image if none found
                    if (!$imageUrl || trim($imageUrl) === '') {
                        $imageUrl = 'https://via.placeholder.com/40?text=No+Image';
                    }
                    ?>

                    <div class="card order-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Order #<?= htmlspecialchars($order['id']) ?></strong> - 
                                <?= htmlspecialchars($order['user_name']) ?> 
                                <span class="badge bg-<?= 
                                    $order['status'] == 'pending' ? 'warning' : 
                                    ($order['status'] == 'processing' ? 'info' : 
                                    ($order['status'] == 'shipped' ? 'primary' : 
                                    ($order['status'] == 'delivered' ? 'success' : 'danger')) )
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
                                    <p><strong>Province:</strong> <?= htmlspecialchars($order['province'] ?? 'N/A') ?></p>
                                    <p><strong>Shipping Address:</strong> <?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
                                    
                                    <p>
                                        <img src="kit_images/<?= htmlspecialchars($imageUrl) ?>" alt="Order Product Image" class="delivery-option-img">
                                        <strong>Delivery Option:</strong> <?= htmlspecialchars($order['delivery_option'] ?? 'N/A') ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Order status form -->
                            <form method="post" class="mb-4">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <label class="form-label">Update Order Status:</label>
                                    </div>
                                    <div class="col-md-6">
                                        <select name="order_status" class="form-select status-select">
                                            <?php foreach ($validStatuses as $status): ?>
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
<?php 
$stmt->close();
$conn->close();
?>
