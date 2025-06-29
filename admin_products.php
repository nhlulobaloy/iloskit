<?php
session_start();
include("db_connection.php");

// Only allow admins
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php?message=Access+denied.+Admin+only.");
    exit;
}

// Fetch all products
$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Products - Ilo's Kit</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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
    
    .navbar-brand {
      font-weight: 700;
      letter-spacing: 0.5px;
    }
    
    .table-container {
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }
    
    .table thead {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
    }
    
    .table th {
      font-weight: 500;
      text-transform: uppercase;
      font-size: 0.8rem;
      letter-spacing: 0.5px;
    }
    
    .product-img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 5px;
      border: 1px solid #eee;
      transition: transform 0.3s ease;
    }
    
    .product-img:hover {
      transform: scale(1.1);
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
    }
    
    .btn-primary:hover {
      background-color: var(--secondary-color);
      border-color: var(--secondary-color);
    }
    
    .page-title {
      color: var(--dark-color);
      position: relative;
      padding-bottom: 10px;
      margin-bottom: 25px;
    }
    
    .page-title:after {
      content: '';
      position: absolute;
      left: 0;
      bottom: 0;
      width: 50px;
      height: 3px;
      background: var(--primary-color);
    }
    
    .action-btns .btn {
      margin-right: 5px;
      min-width: 70px;
    }
    
    .no-products {
      padding: 40px;
      text-align: center;
      color: #6c757d;
    }
    
    .no-products i {
      font-size: 2rem;
      margin-bottom: 15px;
      color: #dee2e6;
    }
    
    .image-preview-container {
      position: relative;
      display: inline-block;
      overflow: hidden;
      border-radius: 5px;
    }
    
    .image-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s;
    }
    
    .image-preview-container:hover .image-overlay {
      opacity: 1;
    }
    
    .image-overlay i {
      color: white;
      font-size: 1.2rem;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color))">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
      <i class="bi bi-shop me-2"></i>
      <span>Ilo's Kit Admin</span>
    </a>
    <div class="d-flex">
      <a href="logout.php" class="btn btn-outline-light">
        <i class="bi bi-box-arrow-right me-1"></i> Logout
      </a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title">
      <i class="bi bi-box-seam me-2"></i>Manage Products
    </h2>
    <a href="upload_image.php" class="btn btn-primary">
      <i class="bi bi-plus-lg me-1"></i> Add New Product
    </a>
  </div>

  <div class="table-container">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>ID</th>
          <th>Image</th>
          <th>Name</th>
          <th>Price</th>
          <th>Description</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td class="align-middle"><?= $row['id'] ?></td>
              <td class="align-middle">
                <?php if (!empty($row['image_url'])): ?>
                  <div class="image-preview-container">
                    <img src="kit_images/<?= htmlspecialchars($row['image_url']) ?>" alt="Product Image" class="product-img" />
                    <div class="image-overlay">
                      <i class="bi bi-zoom-in"></i>
                    </div>
                  </div>
                <?php else: ?>
                  <div class="product-img bg-light d-flex align-items-center justify-content-center">
                    <i class="bi bi-image text-muted"></i>
                  </div>
                <?php endif; ?>
              </td>
              <td class="align-middle fw-medium"><?= htmlspecialchars($row['name']) ?></td>
              <td class="align-middle">R<?= number_format($row['price'], 2) ?></td>
              <td class="align-middle text-muted"><?= htmlspecialchars($row['description']) ?></td>
              <td class="align-middle action-btns">
                <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">
                  <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="delete_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?');">
                  <i class="bi bi-trash"></i> Delete
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6">
              <div class="no-products">
                <i class="bi bi-box"></i>
                <h5>No products found</h5>
                <p class="mb-0">Add your first product to get started</p>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Product Image Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-0">
        <img id="modalImage" src="" alt="Product Image Preview" class="img-fluid w-100">
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Initialize image preview functionality
  document.addEventListener('DOMContentLoaded', function() {
    const previewContainers = document.querySelectorAll('.image-preview-container');
    const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
    const modalImage = document.getElementById('modalImage');
    
    previewContainers.forEach(container => {
      container.addEventListener('click', function() {
        const imgElement = this.querySelector('img');
        if (imgElement) {
          modalImage.src = imgElement.src;
          modal.show();
        }
      });
    });
  });
</script>
</body>
</html>