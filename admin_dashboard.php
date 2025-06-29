<?php
session_start();
include "db_connection.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php?message=Access+denied.+Admin+only.");
    exit;
}

// Handle promo code actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_promo'])) {
        // Validate and add new promo code
        $code = trim($_POST['code']);
        $discountType = $_POST['discount_type'];
        $discountValue = floatval($_POST['discount_value']);
        $minOrder = floatval($_POST['min_order']);
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $usesRemaining = !empty($_POST['uses_remaining']) ? intval($_POST['uses_remaining']) : NULL;

        // Basic validation
        if (empty($code) ){
            $_SESSION['admin_error'] = "Promo code cannot be empty";
        } elseif ($discountValue <= 0) {
            $_SESSION['admin_error'] = "Discount value must be positive";
        } elseif (strtotime($endDate) < strtotime($startDate)) {
            $_SESSION['admin_error'] = "End date must be after start date";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO promo_codes 
                    (code, discount_type, discount_value, min_order, start_date, end_date, uses_remaining)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssddssi", $code, $discountType, $discountValue, $minOrder, $startDate, $endDate, $usesRemaining);
                $stmt->execute();
                $_SESSION['admin_success'] = "Promo code added successfully!";
            } catch (mysqli_sql_exception $e) {
                $_SESSION['admin_error'] = "Error adding promo code: " . (strpos($e->getMessage(), 'Duplicate entry') ? 'Code already exists' : $e->getMessage());
            }
        }
    } elseif (isset($_POST['delete_promo'])) {
        // Delete promo code
        $promoId = intval($_POST['promo_id']);
        $conn->query("DELETE FROM promo_codes WHERE id = $promoId");
        $_SESSION['admin_success'] = "Promo code deleted successfully!";
    }
}

// Get all promo codes
$promoCodes = $conn->query("SELECT * FROM promo_codes ORDER BY start_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard - Ilo's Kit</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <style>
    .dashboard-card {
      transition: all 0.3s ease;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .dashboard-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    }
    .promo-table th {
      background-color: #343a40;
      color: white;
    }
    .active-promo {
      background-color: #e8f5e9;
    }
    .expired-promo {
      background-color: #ffebee;
    }
  </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Ilo's Kit Admin</a>
    <div class="d-flex">
      <a href="logout.php" class="btn btn-outline-light">Logout</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <?= $_SESSION['admin_error'] ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['admin_error']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['admin_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= $_SESSION['admin_success'] ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['admin_success']); ?>
  <?php endif; ?>

  <h1>Welcome, Admin <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>!</h1>
  <p class="lead">Manage your store from here.</p>

  <div class="row mt-4">
    <div class="col-md-4 mb-4">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-shopping-cart me-2"></i>Orders</h5>
          <p class="card-text">Manage customer orders and fulfillment.</p>
          <a href="admin_orders.php" class="btn btn-dark">Manage Orders</a>
        </div>
      </div>
    </div>
    
    <div class="col-md-4 mb-4">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-box-open me-2"></i>Products</h5>
          <p class="card-text">Add, edit, or remove products.</p>
          <a href="admin_products.php" class="btn btn-dark">Manage Products</a>
        </div>
      </div>
    </div>
    
    <div class="col-md-4 mb-4">
      <div class="card dashboard-card h-100">
        <div class="card-body">
          <h5 class="card-title"><i class="fas fa-images me-2"></i>Media</h5>
          <p class="card-text">Upload and manage product images.</p>
          <a href="upload_image.php" class="btn btn-dark">Manage Media</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Promo Code Management Section -->
  <div class="card mt-4">
    <div class="card-header bg-dark text-white">
      <h4 class="mb-0"><i class="fas fa-tags me-2"></i>Promo Code Management</h4>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <h5>Add New Promo Code</h5>
          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Promo Code</label>
              <input type="text" name="code" class="form-control" required>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Discount Type</label>
                <select name="discount_type" class="form-select" required>
                  <option value="percentage">Percentage</option>
                  <option value="fixed">Fixed Amount</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Discount Value</label>
                <div class="input-group">
                  <span class="input-group-text">R</span>
                  <input type="number" step="0.01" min="0.01" name="discount_value" class="form-control" required>
                  <span class="input-group-text discount-type-text">%</span>
                </div>
              </div>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Minimum Order</label>
                <div class="input-group">
                  <span class="input-group-text">R</span>
                  <input type="number" step="0.01" min="0" name="min_order" class="form-control" value="0">
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Uses Remaining</label>
                <input type="number" min="1" name="uses_remaining" class="form-control" placeholder="Leave empty for unlimited">
              </div>
            </div>
            
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Start Date</label>
                <input type="datetime-local" name="start_date" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">End Date</label>
                <input type="datetime-local" name="end_date" class="form-control" required>
              </div>
            </div>
            
            <button type="submit" name="add_promo" class="btn btn-success">
              <i class="fas fa-plus-circle me-1"></i> Add Promo Code
            </button>
          </form>
        </div>
        
        <div class="col-md-6">
          <h5>Current Promo Codes</h5>
          <div class="table-responsive">
            <table class="table table-bordered promo-table">
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Discount</th>
                  <th>Min Order</th>
                  <th>Valid Until</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($promo = $promoCodes->fetch_assoc()): 
                  $isActive = strtotime($promo['start_date']) <= time() && strtotime($promo['end_date']) >= time();
                  $isExpired = strtotime($promo['end_date']) < time();
                ?>
                <tr class="<?= $isActive ? 'active-promo' : ($isExpired ? 'expired-promo' : '') ?>">
                  <td><?= htmlspecialchars($promo['code']) ?></td>
                  <td>
                    <?= $promo['discount_type'] == 'percentage' ? 
                        $promo['discount_value'].'%' : 
                        'R'.number_format($promo['discount_value'], 2) ?>
                  </td>
                  <td>R<?= number_format($promo['min_order'], 2) ?></td>
                  <td><?= date('M j, Y', strtotime($promo['end_date'])) ?></td>
                  <td>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="promo_id" value="<?= $promo['id'] ?>">
                      <button type="submit" name="delete_promo" class="btn btn-sm btn-danger" 
                              onclick="return confirm('Are you sure you want to delete this promo code?');">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </form>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Update discount value suffix when type changes
  document.querySelector('select[name="discount_type"]').addEventListener('change', function() {
    const suffix = this.value === 'percentage' ? '%' : '';
    document.querySelector('.discount-type-text').textContent = suffix;
  });
  
  // Set default dates for convenience
  document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    const later = new Date();
    later.setMonth(later.getMonth() + 1);
    
    // Format for datetime-local input
    function toLocalDatetime(d) {
      return d.toISOString().slice(0, 16);
    }
    
    document.querySelector('input[name="start_date"]').value = toLocalDatetime(now);
    document.querySelector('input[name="end_date"]').value = toLocalDatetime(later);
  });
</script>
</body>
</html>