<?php
session_start();
require "db_connection.php";

// Check if user is admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// Get user ID from URL
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch user details
$stmt = $conn->prepare("SELECT id, name, email, phone, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found");
}

// Fetch user's orders
$ordersStmt = $conn->prepare("
    SELECT id, order_date, total_amount, status 
    FROM orders 
    WHERE user_id = ?
    ORDER BY order_date DESC
");
$ordersStmt->bind_param("i", $userId);
$ordersStmt->execute();
$orders = $ordersStmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Details - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .user-card {
            max-width: 800px;
            margin: 2rem auto;
        }
        .order-table {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card user-card">
            <div class="card-header bg-primary text-white">
                <h2>User Details</h2>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> <?= $user['id'] ?></p>
                        <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'N/A') ?></p>
                        <p><strong>Member Since:</strong> <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>

                <h3>Order History</h3>
                <?php if ($orders->num_rows > 0): ?>
                    <div class="table-responsive order-table">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $order['id'] ?></td>
                                        <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                        <td>R<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $order['status'] == 'pending' ? 'warning' : 
                                                ($order['status'] == 'completed' ? 'success' : 'info')
                                            ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin_order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No orders found for this user.</div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="admin_users.php" class="btn btn-secondary">Back to Users</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
$conn->close();
?>