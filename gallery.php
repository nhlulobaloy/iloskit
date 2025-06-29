<?php
session_start();
include("db_connection.php");

// Get all products
$query = "SELECT * FROM products ORDER BY id DESC";
$result = $conn->query($query);

// Add to cart functionality (only if logged in)
if (isset($_POST['add_to_cart']) && isset($_SESSION['user_id'])) {
    $product_id = $_POST['product_id'];
    $size = $_POST['size'];
    $quantity = $_POST['quantity'];
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add item to cart
    $_SESSION['cart'][] = [
        'product_id' => $product_id,
        'size' => $size,
        'quantity' => $quantity,
        'added_at' => time()
    ];
    
    header("Location: gallery.php?added=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Ilo's Kit - Product Gallery</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #4e73df;
      --secondary-color: #2e59d9;
    }
    .gallery-img {
      height: 180px;
      object-fit: cover;
      width: 100%;
      transition: transform 0.3s;
      cursor: zoom-in;
    }
    .gallery-item {
      margin-bottom: 1.5rem;
      position: relative;
    }
    .gallery-details {
      padding: 10px 0;
    }
    .gallery-title {
      font-size: 0.95rem;
      margin-bottom: 0.2rem;
    }
    .gallery-price {
      font-size: 0.9rem;
      font-weight: bold;
      color: var(--primary-color);
      margin-bottom: 5px;
    }
    .gallery-form {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-top: 10px;
    }
    .gallery-form select, 
    .gallery-form input {
      padding: 0.25rem 0.5rem;
      font-size: 0.8rem;
    }
    .gallery-form button {
      padding: 0.3rem;
      font-size: 0.8rem;
    }
    .login-prompt {
      font-size: 0.8rem;
      color: #dc3545;
      margin-top: 5px;
    }
    .cart-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background: var(--primary-color);
      color: white;
      border-radius: 50%;
      width: 20px;
      height: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.7rem;
    }
    #lightbox {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.9);
      z-index: 1000;
      display: none;
    }
    #lightbox-img {
      max-width: 90%;
      max-height: 90%;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }
    #lightbox-close {
      position: absolute;
      top: 20px;
      right: 20px;
      color: white;
      font-size: 2rem;
      cursor: pointer;
    }
    .added-notification {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
      display: none;
    }
  </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <i class="fas fa-tshirt me-2"></i>Ilo's Kit
    </a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="cart.php">
            <i class="fas fa-shopping-cart"></i>
            <?php if (!empty($_SESSION['cart'])): ?>
              <span class="cart-badge"><?= count($_SESSION['cart']) ?></span>
            <?php endif; ?>
          </a>
        </li>
        <!-- ... other nav items ... -->
      </ul>
    </div>
  </div>
</nav>

<!-- Added to cart notification -->
<div class="added-notification alert alert-success alert-dismissible fade show" role="alert">
  Item added to cart!
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-5">
    <h1>Product Gallery</h1>
    <a href="index.php" class="btn btn-outline-primary">
      <i class="fas fa-th-list me-1"></i> List View
    </a>
  </div>
  
  <div class="row">
    <?php if ($result->num_rows > 0): ?>
      <?php while ($product = $result->fetch_assoc()): ?>
        <div class="col-6 col-md-4 col-lg-3 gallery-item">
          <!-- Product Image (clickable for lightbox) -->
          <img src="kit_images/<?= htmlspecialchars($product['image_url']) ?>" 
               class="gallery-img img-thumbnail" 
               alt="<?= htmlspecialchars($product['name']) ?>"
               data-fullimg="kit_images/<?= htmlspecialchars($product['image_url']) ?>">
          
          <!-- Product Details -->
          <div class="gallery-details">
            <h5 class="gallery-title"><?= htmlspecialchars($product['name']) ?></h5>
            <p class="gallery-price">R<?= number_format($product['price'], 2) ?></p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
              <!-- Add to Cart Form (visible when logged in) -->
              <form class="gallery-form" method="post">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                
                <select name="size" class="form-select" required>
                  <option value="" disabled selected>Size</option>
                  <option value="S">S</option>
                  <option value="M">M</option>
                  <option value="L">L</option>
                  <option value="XL">XL</option>
                </select>
                
                <input type="number" name="quantity" value="1" min="1" class="form-control">
                
                <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm">
                  <i class="fas fa-cart-plus me-1"></i> Add to Cart
                </button>
              </form>
            <?php else: ?>
              <!-- Login Prompt (visible when not logged in) -->
              <div class="login-prompt">
                <a href="index.php  #login" class="text-danger">
                  <i class="fas fa-sign-in-alt"></i> Login to purchase
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="col-12 text-center py-5">
        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
        <h4>No products available</h4>
        <p class="text-muted">Check back later for new products</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Lightbox for image preview -->
<div id="lightbox">
  <span id="lightbox-close">&times;</span>
  <img id="lightbox-img" src="" alt="">
</div>

<!-- Footer -->
<footer class="bg-dark text-white py-4 mt-5">
  <div class="container text-center">
    <p>&copy; <?= date('Y') ?> Ilo's Kit. All rights reserved.</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Lightbox functionality
document.addEventListener('DOMContentLoaded', function() {
  const lightbox = document.getElementById('lightbox');
  const lightboxImg = document.getElementById('lightbox-img');
  const closeBtn = document.getElementById('lightbox-close');
  
  // Open lightbox when gallery image is clicked
  document.querySelectorAll('.gallery-img').forEach(img => {
    img.addEventListener('click', function() {
      lightboxImg.src = this.dataset.fullimg;
      lightbox.style.display = 'block';
      document.body.style.overflow = 'hidden';
    });
  });
  
  // Close lightbox
  closeBtn.addEventListener('click', function() {
    lightbox.style.display = 'none';
    document.body.style.overflow = 'auto';
  });
  
  // Close when clicking outside image
  lightbox.addEventListener('click', function(e) {
    if (e.target === lightbox) {
      lightbox.style.display = 'none';
      document.body.style.overflow = 'auto';
    }
  });
  
  // Show added notification if URL has added=1
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('added')) {
    const notification = document.querySelector('.added-notification');
    notification.style.display = 'block';
    setTimeout(() => {
      notification.classList.add('show');
    }, 100);
    
    // Remove the parameter from URL
    history.replaceState(null, '', window.location.pathname);
  }
});
</script>
</body>
</html>