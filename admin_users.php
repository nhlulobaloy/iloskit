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

// Block/Unblock user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_block'])) {
    $targetUserId = (int)$_POST['user_id'];
    $currentStatus = (int)$_POST['current_status'];
    
    // Prevent blocking yourself
    if ($targetUserId === $userId) {
        header("Location: admin_users.php?error=Cannot+block+yourself");
        exit;
    }
    
    $newStatus = $currentStatus ? 0 : 1;
    $stmt = $conn->prepare("UPDATE users SET is_blocked = ? WHERE id = ?");
    $stmt->bind_param("ii", $newStatus, $targetUserId);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin_users.php?success=User+status+updated");
    exit;
}

// Get user statistics
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$activeUsers = $conn->query("SELECT COUNT(*) FROM users WHERE is_blocked = 0")->fetch_row()[0];
$blockedUsers = $conn->query("SELECT COUNT(*) FROM users WHERE is_blocked = 1")->fetch_row()[0];
$adminUsers = $conn->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetch_row()[0];

// Get all users
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$whereClause = "";
if (!empty($search)) {
    $whereClause = "WHERE name LIKE '%$search%' OR email LIKE '%$search%'";
}

$usersQuery = "SELECT id, name, email, phone, created_at, is_admin, is_blocked 
               FROM users $whereClause
               ORDER BY created_at DESC
               LIMIT $perPage OFFSET $offset";
$usersResult = $conn->query($usersQuery);

$totalResults = $conn->query("SELECT COUNT(*) FROM users $whereClause")->fetch_row()[0];
$totalPages = ceil($totalResults / $perPage);
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management - Ilo's Kit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .stats-card {
            transition: all 0.3s;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stats-card.total { border-left-color: #4e73df; }
        .stats-card.active { border-left-color: #1cc88a; }
        .stats-card.blocked { border-left-color: #e74a3b; }
        .stats-card.admins { border-left-color: #f6c23e; }
        .user-row:hover {
            background-color: #f8f9fa;
        }
        .badge-admin {
            background-color: #f6c23e;
            color: #000;
        }
        .badge-blocked {
            background-color: #e74a3b;
        }
        .badge-active {
            background-color: #1cc88a;
        }
        .search-box {
            max-width: 400px;
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
                    <a href="admin_orders.php" class="nav-link">Orders</a>
                    <a href="admin_products.php" class="nav-link">Products</a>
                    <a href="admin_users.php" class="nav-link active">Users</a>
                    <a href="upload_image.php" class="nav-link">Upload Products</a>
                    <a href="index.php" class="nav-link ms-auto">View Store</a>
                </div>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">User Management</h2>
            
            <!-- Display messages -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars(urldecode($_GET['error'])) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars(urldecode($_GET['success'])) ?></div>
            <?php endif; ?>
            
            <!-- User Statistics -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card total h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalUsers ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card active h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Active Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $activeUsers ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card blocked h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Blocked Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $blockedUsers ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-slash fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stats-card admins h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Admin Users</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $adminUsers ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Search and User List -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">User List</h6>
                    <form method="get" class="d-flex">
                        <div class="input-group search-box">
                            <input type="text" name="search" class="form-control" placeholder="Search users..." 
                                   value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="admin_users.php" class="btn btn-outline-secondary">Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registered</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($usersResult->num_rows > 0): ?>
                                    <?php while ($user = $usersResult->fetch_assoc()): ?>
                                        <tr class="user-row">
                                            <td><?= $user['id'] ?></td>
                                            <td>
                                                <?= htmlspecialchars($user['name']) ?>
                                                <?php if ($user['is_admin']): ?>
                                                    <span class="badge badge-admin">Admin</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                                            <td><?= date("M j, Y", strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <?php if ($user['is_blocked']): ?>
                                                    <span class="badge badge-blocked">Blocked</span>
                                                <?php else: ?>
                                                    <span class="badge badge-active">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <input type="hidden" name="current_status" value="<?= $user['is_blocked'] ?>">
                                                    <button type="submit" name="toggle_block" class="btn btn-sm <?= $user['is_blocked'] ? 'btn-success' : 'btn-warning' ?>">
                                                        <?= $user['is_blocked'] ? 'Unblock' : 'Block' ?>
                                                    </button>
                                                </form>
                                                <a href="admin_user_details.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No users found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>