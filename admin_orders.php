<?php
session_start();
require "db_connection.php";

// Check admin login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?message=Please+login+as+admin");
    exit;
}

$stmtAdmin = $conn->prepare("SELECT is_admin FROM users WHERE id=?");
$stmtAdmin->bind_param("i", $_SESSION['user_id']);
$stmtAdmin->execute();
$stmtAdmin->bind_result($isAdmin);
$stmtAdmin->fetch();
$stmtAdmin->close();
if (!$isAdmin) die("Access denied.");

// Handle status update if this is an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['order_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['order_status'];
    
    // Update order status using order_id (varchar) instead of id
    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE order_id=?");
    $stmt->bind_param("ss", $newStatus, $orderId);
    
    if($stmt->execute()){
        if($stmt->affected_rows > 0){
            echo 'success';
        } else {
            echo 'No rows updated. Check order id.';
        }
    } else {
        echo 'DB error: '.$conn->error;
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

// Fetch orders for display
$sql = "SELECT o.*, u.name AS user_name, u.email, u.phone
        FROM orders o
        JOIN users u ON o.user_id=u.id
        ORDER BY o.order_date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders - Ilo's Kit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color: #343a40;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            background-color: #f8f9fa;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-nav {
            background: var(--primary-color);
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .admin-nav .nav-link {
            color: rgba(255,255,255,.8);
            margin-right: 1.5rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .admin-nav .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        
        .admin-nav .nav-link.active {
            color: white;
            font-weight: bold;
            background-color: rgba(255,255,255,0.2);
        }
        
        .order-card {
            margin-bottom: 1.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.5rem 0.8rem;
            border-radius: 50px;
        }
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 12px;
            border: 1px solid #dee2e6;
        }
        
        .table th {
            border-top: none;
            background-color: #f8f9fa;
        }
        
        .status-select {
            width: 150px;
        }
        
        .page-title {
            color: var(--primary-color);
            margin-bottom: 2rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 15px;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            text-align: center;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stats-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        .filter-bar {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Admin Navigation -->
        <nav class="admin-nav mb-4">
            <div class="d-flex flex-wrap align-items-center">
                <h4 class="text-white me-4 mb-0"><i class="fas fa-cog me-2"></i>Admin Panel</h4>
                <a href="admin_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                <a href="admin_orders.php" class="nav-link active"><i class="fas fa-shopping-cart me-1"></i> Orders</a>
                <a href="admin_products.php" class="nav-link"><i class="fas fa-box me-1"></i> Products</a>
                <a href="admin_users.php" class="nav-link"><i class="fas fa-users me-1"></i> Users</a>
                <a href="upload_image.php" class="nav-link"><i class="fas fa-upload me-1"></i> Upload Products</a>
                <a href="index.php" class="nav-link ms-auto"><i class="fas fa-store me-1"></i> View Store</a>
            </div>
        </nav>

        <h2 class="page-title"><i class="fas fa-list-alt me-2"></i>Order Management</h2>
        
        <!-- Stats Overview -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $result->num_rows; ?></div>
                    <div class="stats-label">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">
                        <?php 
                        $pending = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'");
                        echo $pending->fetch_row()[0];
                        ?>
                    </div>
                    <div class="stats-label">Pending Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">
                        <?php 
                        $processing = $conn->query("SELECT COUNT(*) FROM orders WHERE status='processing'");
                        echo $processing->fetch_row()[0];
                        ?>
                    </div>
                    <div class="stats-label">Processing</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number">
                        <?php 
                        $delivered = $conn->query("SELECT COUNT(*) FROM orders WHERE status='delivered'");
                        echo $delivered->fetch_row()[0];
                        ?>
                    </div>
                    <div class="stats-label">Delivered</div>
                </div>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar d-flex justify-content-between align-items-center">
            <div>
                <button class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-filter me-1"></i> Filter</button>
                <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-download me-1"></i> Export</button>
            </div>
            <div class="d-flex">
                <input type="text" class="form-control form-control-sm me-2" placeholder="Search orders...">
                <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <?php if($result->num_rows===0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No orders found.
        </div>
        <?php else: ?>
        <?php while($order=$result->fetch_assoc()): ?>
        <div class="card order-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong>Order #<?=htmlspecialchars($order['order_id'] ?: $order['id'])?></strong> - <?=htmlspecialchars($order['user_name'])?>
                    <span class="badge status-badge bg-<?= 
                        $order['status']=='pending'?'warning':
                        ($order['status']=='processing'?'info':
                        ($order['status']=='shipped'?'primary':
                        ($order['status']=='delivered'?'success':'danger')))
                    ?>">
                        <i class="fas fa-<?= 
                            $order['status']=='pending'?'clock':
                            ($order['status']=='processing'?'cog':
                            ($order['status']=='shipped'?'shipping-fast':
                            ($order['status']=='delivered'?'check-circle':'times-circle')))
                        ?> me-1"></i>
                        <?=ucfirst($order['status'])?>
                    </span>
                </div>
                <div class="text-muted">
                    <i class="far fa-clock me-1"></i><?=date("F j, Y, g:i a", strtotime($order['order_date']))?>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-envelope me-1"></i> Email:</strong> <?=htmlspecialchars($order['email'])?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-phone me-1"></i> Phone:</strong> <?=htmlspecialchars($order['phone'] ?? 'N/A')?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong><i class="fas fa-truck me-1"></i> Delivery Option:</strong> <?=htmlspecialchars($order['delivery_option'])?></p>
                    </div>
                </div>
                
                <p><strong><i class="fas fa-map-marker-alt me-1"></i> Delivery Address:</strong><br><?=nl2br(htmlspecialchars($order['delivery_address']))?></p>

                <?php 
                $orderKey = $order['order_id'] ?: $order['id'];
                $stmtItems = $conn->prepare("
                    SELECT oi.*, p.name, p.image_url
                    FROM order_items oi
                    JOIN products p ON oi.product_id=p.id
                    WHERE oi.order_id=?
                ");
                $stmtItems->bind_param("s",$orderKey);
                $stmtItems->execute();
                $itemsResult = $stmtItems->get_result();
                $subtotal = 0;
                ?>

                <h5 class="mt-4 mb-3"><i class="fas fa-shopping-basket me-2"></i>Order Items</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Size</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item=$itemsResult->fetch_assoc()): 
                                $totalItem = $item['price_at_purchase']*$item['quantity'];
                                $subtotal += $totalItem;
                            ?>
                            <tr>
                                <td class="d-flex align-items-center">
                                    <img src="kit_images/<?=htmlspecialchars($item['image_url'])?>" class="product-img">
                                    <span><?=htmlspecialchars($item['name'])?></span>
                                </td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($item['size']) ?></span></td>
                                <td>R<?= number_format($item['price_at_purchase'],2) ?></td>
                                <td class="fw-bold">R<?= number_format($totalItem,2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                <td class="fw-bold">R<?=number_format($subtotal,2)?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php $stmtItems->close(); ?>

                <!-- Status Update (AJAX) -->
                <div class="order-actions">
                    <select class="form-select status-select">
                        <?php $statuses=['pending','processing','shipped','delivered','cancelled'];
                        foreach($statuses as $status): ?>
                            <option value="<?=$status?>" <?=$order['status']==$status?'selected':''?>><?=ucfirst($status)?></option>
                        <?php endforeach; ?>
                    </select>
                    <!-- UPDATED: Use order_id instead of id -->
                    <button type="button" class="btn btn-primary update-status-btn" data-order-id="<?= $order['order_id'] ?>">
                        <i class="fas fa-sync-alt me-1"></i>Update Status
                    </button>
                    <button class="btn btn-outline-secondary">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <div class="status-msg ms-2"></div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>

    </div>

    <script>
    $(document).ready(function(){
        $('.update-status-btn').click(function(){
            let card = $(this).closest('.order-card');
            let orderId = $(this).data('order-id');
            let newStatus = card.find('.status-select').val();
            let badge = card.find('.status-badge');
            let msgDiv = card.find('.status-msg');

            if(!orderId || !newStatus){
                alert("Error: missing order ID or status.");
                return;
            }

            // Show loading state
            msgDiv.html('<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Updating...</span>');
            
            $.post('admin_orders.php', {order_id: orderId, order_status: newStatus}, function(response){
                response = response.trim();
                if(response === 'success'){
                    badge.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));

                    let colorClass = 'bg-secondary';
                    let iconClass = 'fas fa-times-circle';
                    if(newStatus=='pending') {
                        colorClass='bg-warning';
                        iconClass='fas fa-clock';
                    }
                    else if(newStatus=='processing') {
                        colorClass='bg-info';
                        iconClass='fas fa-cog';
                    }
                    else if(newStatus=='shipped') {
                        colorClass='bg-primary';
                        iconClass='fas fa-shipping-fast';
                    }
                    else if(newStatus=='delivered') {
                        colorClass='bg-success';
                        iconClass='fas fa-check-circle';
                    }
                    else if(newStatus=='cancelled') {
                        colorClass='bg-danger';
                        iconClass='fas fa-times-circle';
                    }

                    badge.removeClass('bg-warning bg-info bg-primary bg-success bg-danger bg-secondary').addClass(colorClass);
                    
                    // Update icon
                    let icon = badge.find('i');
                    icon.removeClass().addClass(iconClass + ' me-1');
                    
                    msgDiv.html('<span class="text-success"><i class="fas fa-check-circle me-1"></i>Status updated to '+newStatus+'</span>');
                    
                    // Clear message after 3 seconds
                    setTimeout(function(){
                        msgDiv.fadeOut();
                    }, 3000);
                } else {
                    msgDiv.html('<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Error: '+response+'</span>');
                }
            }).fail(function() {
                msgDiv.html('<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Request failed. Please try again.</span>');
            });
        });
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>
