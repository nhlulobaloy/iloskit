<?php
session_start();
include("db_connection.php");

// Add to cart functionality
if (isset($_POST['add_to_cart'])) {
  $product_id = $_POST['product_id'];
  $size = $_POST['size'];
  $quantity = $_POST['quantity'];

  if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
  }

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

    body {
      padding-top: 56px;
    }

    .gallery-img {
      height: 180px;
      object-fit: cover;
      width: 100%;
      cursor: zoom-in;
      transition: transform 0.3s;
    }

    .gallery-img:hover {
      transform: scale(1.02);
    }

    .gallery-item {
      margin-bottom: 1.5rem;
      transition: all 0.3s;
    }

    .gallery-item:hover {
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .gallery-details {
      padding: 10px 0;
    }

    .gallery-title {
      font-size: 0.95rem;
      margin-bottom: 0.2rem;
      font-weight: 600;
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
      border: 1px solid #ddd;
      border-radius: 4px;
    }

    .gallery-form button {
      padding: 0.3rem;
      font-size: 0.8rem;
      background: var(--primary-color);
      border: none;
    }

    .gallery-form button:hover {
      background: var(--secondary-color);
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
      background: rgba(0, 0, 0, 0.9);
      z-index: 1000;
      display: none;
      align-items: center;
      justify-content: center;
    }

    #lightbox-img {
      max-width: 90%;
      max-height: 90%;
      border: 3px solid white;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
    }

    #lightbox-close {
      position: absolute;
      top: 20px;
      right: 20px;
      color: white;
      font-size: 2rem;
      cursor: pointer;
      background: rgba(0, 0, 0, 0.5);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .added-notification {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
      display: none;
      animation: slideIn 0.5s forwards;
    }

    @keyframes slideIn {
      from {
        transform: translateX(100%);
      }

      to {
        transform: translateX(0);
      }
    }

    .back-to-top {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 999;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: var(--primary-color);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
      opacity: 0;
      transition: opacity 0.3s;
    }

    .back-to-top.visible {
      opacity: 1;
    }

    .back-to-top:hover {
      background: var(--secondary-color);
      transform: translateY(-3px);
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="index.php"><i class="fas fa-tshirt me-2"></i>Ilo's Kit</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i> Home</a></li>
          <li class="nav-item"><a class="nav-link active" href="gallery.php"><i class="fas fa-images me-1"></i>
              Gallery</a></li>
          <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart me-1"></i> Cart
              <?php if (!empty($_SESSION['cart'])): ?><span
                  class="cart-badge"><?= count($_SESSION['cart']) ?></span><?php endif; ?></a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Notification -->
  <div class="added-notification alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i> Item added to cart!
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>

  <div class="container py-5 mt-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-1"><i class="fas fa-images me-2"></i>Product Gallery</h1>
      <p class="text-muted">Browse our full collection of custom kits</p>
    </div>

    <!-- Live Search -->
    <div class="row mb-4">
      <div class="col-12">
        <input type="text" id="live-search" class="form-control" placeholder="Search products...">
      </div>
    </div>

    <div class="row" id="gallery-container">
      <?php
      $query = "SELECT * FROM products ORDER BY id DESC";
      $result = $conn->query($query);

      if ($result->num_rows > 0):
        while ($product = $result->fetch_assoc()):
          ?>
          <div class="col-6 col-md-4 col-lg-3 gallery-item">
            <img src="kit_images/<?= htmlspecialchars($product['image_url']) ?>" class="gallery-img img-thumbnail"
              alt="<?= htmlspecialchars($product['name']) ?>"
              data-fullimg="kit_images/<?= htmlspecialchars($product['image_url']) ?>">
            <div class="gallery-details">
              <h5 class="gallery-title"><?= htmlspecialchars($product['name']) ?></h5>
              <p class="gallery-price">R<?= number_format($product['price'], 2) ?></p>
              <form class="gallery-form" method="post">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <select name="size" class="form-select" required>
                  <option value="" disabled selected>Select Size</option>
                  <option value="S">S</option>
                  <option value="M">M</option>
                  <option value="L">L</option>
                  <option value="XL">XL</option>
                </select>
                <input type="number" name="quantity" value="1" min="1" max="10" class="form-control" placeholder="Qty">
                <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm"><i
                    class="fas fa-cart-plus me-1"></i> Add to Cart</button>
              </form>
            </div>
          </div>
          <?php
        endwhile;
      else:
        ?>
        <div class="col-12 text-center py-5">
          <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
          <h4>No products found</h4>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Lightbox -->
  <div id="lightbox">
    <span id="lightbox-close">&times;</span>
    <img id="lightbox-img" src="" alt="">
  </div>

  <a href="#" class="back-to-top"><i class="fas fa-arrow-up"></i></a>

  <footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <h5><i class="fas fa-tshirt me-2"></i>Ilo's Kit</h5>
          <p>Premium custom teamwear for all occasions</p>
        </div>
        <div class="col-md-6 text-md-end">
          <h5>Quick Links</h5>
          <ul class="list-unstyled">
            <li><a href="index.php" class="text-white">Home</a></li>
            <li><a href="gallery.php" class="text-white">Gallery</a></li>
            <li><a href="cart.php" class="text-white">Cart</a></li>
          </ul>
        </div>
      </div>
      <hr>
      <div class="text-center">&copy; <?= date('Y') ?> Ilo's Kit. All rights reserved.</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Lightbox
    function initLightbox() {
      const lightbox = document.getElementById('lightbox');
      const lightboxImg = document.getElementById('lightbox-img');
      const closeBtn = document.getElementById('lightbox-close');
      document.querySelectorAll('.gallery-img').forEach(img => {
        img.addEventListener('click', function () {
          lightboxImg.src = this.dataset.fullimg;
          lightbox.style.display = 'flex';
          document.body.style.overflow = 'hidden';
        });
      });
      closeBtn.addEventListener('click', function () {
        lightbox.style.display = 'none';
        document.body.style.overflow = 'auto';
      });
      lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) {
          lightbox.style.display = 'none';
          document.body.style.overflow = 'auto';
        }
      });
    }
    initLightbox();

    // Added notification
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('added')) {
      const notification = document.querySelector('.added-notification');
      notification.style.display = 'block';
      setTimeout(() => { notification.classList.add('show'); }, 100);
      setTimeout(() => { notification.classList.remove('show'); setTimeout(() => { notification.style.display = 'none'; }, 500); }, 3000);
      history.replaceState(null, '', window.location.pathname);
    }

    // Back to top
    const backToTopButton = document.querySelector('.back-to-top');
    window.addEventListener('scroll', function () {
      backToTopButton.classList.toggle('visible', window.pageYOffset > 300);
    });
    backToTopButton.addEventListener('click', function (e) { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });

    // Live search
    const searchInput = document.getElementById('live-search');
    const galleryContainer = document.getElementById('gallery-container');
    searchInput.addEventListener('input', function () {
      const query = this.value.trim();
      const xhr = new XMLHttpRequest();
      xhr.open('GET', 'live_search.php?query=' + encodeURIComponent(query), true);
      xhr.onload = function () {
        if (xhr.status === 200) {
          galleryContainer.innerHTML = xhr.responseText;
          initLightbox(); // reattach lightbox to new images
        }
      };
      xhr.send();
    });
  </script>
</body>

</html>