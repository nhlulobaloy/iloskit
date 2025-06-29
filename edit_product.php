<?php
session_start();
include("db_connection.php");

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php?message=Access+denied.");
    exit;
}

// Get product ID from URL
$productId = intval($_GET['id'] ?? 0);

if ($productId <= 0) {
    die("Invalid product ID.");
}

// Fetch product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    die("Product not found.");
}

$success = false;
$error = "";

// Handle update form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);

    if ($name && $price > 0) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sdsi", $name, $price, $description, $productId);
        $success = $stmt->execute();
        $stmt->close();

        // Refresh product data
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
    } else {
        $error = "Name and price are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product - Ilo's Kit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #6c63ff;
            --secondary-color: #4d44db;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .edit-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .page-header {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title {
            color: var(--dark-color);
            margin: 0;
            font-weight: 600;
        }
        
        .page-title i {
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .form-control {
            border-radius: 6px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(108, 99, 255, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-secondary {
            border-radius: 6px;
            padding: 8px 20px;
            font-weight: 500;
        }
        
        .alert {
            border-radius: 6px;
            padding: 12px 15px;
        }
        
        .product-image-container {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            margin: 0 auto 20px;
            border: 1px solid #eee;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .product-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-pencil-square"></i>Edit Product #<?= $productId ?>
            </h1>
            <a href="admin_products.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Products
            </a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i>
                Product updated successfully!
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="product-image-container">
            <?php if (!empty($product['image'])): ?>
                <img src="images/<?= htmlspecialchars($product['image']) ?>" alt="Product Image" class="product-image">
            <?php else: ?>
                <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
            <?php endif; ?>
        </div>

        <form method="post">
            <div class="mb-4">
                <label class="form-label">Product Name</label>
                <input type="text" name="name" class="form-control form-control-lg" required 
                       value="<?= htmlspecialchars($product['name']) ?>">
            </div>

            <div class="mb-4">
                <label class="form-label">Price (R)</label>
                <div class="input-group">
                    <span class="input-group-text">R</span>
                    <input type="number" name="price" step="0.01" class="form-control form-control-lg" required 
                           value="<?= htmlspecialchars($product['price']) ?>">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Update Product
                </button>
                <a href="admin_products.php" class="btn btn-secondary">
                    <i class="bi bi-x"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>