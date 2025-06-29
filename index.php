<?php
session_start();
include("db_connection.php");

// Handle search input
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Ilo's Kit - Premium Custom Teamwear</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { background-color: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
    .navbar-brand { font-weight: bold; }
    .hero-section {
      background: linear-gradient(135deg, #4e73df, #2e59d9);
      padding: 5rem 0;
      color: white;
      text-align: center;
    }
    .search-box { max-width: 600px; margin: 0 auto; }
    .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .card-img-top { height: 200px; object-fit: cover; border-radius: 10px 10px 0 0; }
    footer { background-color: #1a1a2e; color: white; padding: 2rem 0; }
    .auth-section { margin-top: 3rem; }
    .developer-card {
      background: linear-gradient(135deg, #f5f7fa, #e4e8f0);
      border-radius: 10px;
      padding: 20px;
      margin-top: 30px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .contact-badge {
      background-color: #4e73df;
      color: white;
      padding: 8px 15px;
      border-radius: 20px;
      display: inline-block;
      margin: 5px;
      transition: all 0.3s;
    }
    .contact-badge:hover {
      background-color: #2e59d9;
      transform: translateY(-2px);
    }
    .developer-title {
      position: relative;
      display: inline-block;
    }
    .developer-title:after {
      content: '';
      position: absolute;
      width: 100%;
      height: 3px;
      bottom: -5px;
      left: 0;
      background: linear-gradient(90deg, #4e73df, #2e59d9);
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container">
    <a class="navbar-brand" href="#"><i class="fas fa-tshirt me-2"></i>Ilo's Kit</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navLinks">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navLinks">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="#products"><i class="fas fa-store me-1"></i> Products</a></li>

        <?php if (isset($_SESSION['user_id'])): ?>
          <?php if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="upload_image.php"><i class="fas fa-upload me-1"></i> Upload</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="user_dashboard.php"><i class="fas fa-user-circle me-1"></i> Profile</a></li>
            <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart me-1"></i> Cart</a></li>
            <li class="nav-item"><a class="nav-link" href="my_orders.php"><i class="fas fa-clipboard-list me-1"></i> Orders</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="#login"><i class="fas fa-sign-in-alt me-1"></i> Login</a></li>
          <li class="nav-item"><a class="nav-link" href="#signup"><i class="fas fa-user-plus me-1"></i> Signup</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<header class="hero-section">
  <h1 class="display-4 fw-bold mb-3">Welcome to Ilo's Kit</h1>
  <p class="lead">Premium custom kits for your team or event</p>
  <form id="search-form" class="search-box mt-4">
    <div class="input-group">
      <input type="text" id="search-input" name="search" class="form-control form-control-lg" 
             placeholder="Search products..." value="<?= htmlspecialchars($searchTerm) ?>">
      <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
    </div>
  </form>
</header>

<div class="container mt-4">
  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-info alert-dismissible fade show">
      <?= htmlspecialchars($_GET['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div id="search-status" class="alert alert-light mt-4" style="display: none;">
    <h4 class="mb-0">
      <span id="search-text"></span>
      <span id="results-count" class="badge bg-primary ms-2"></span>
    </h4>
    <a href="index.php" class="btn btn-sm btn-outline-secondary mt-2">Clear search</a>
  </div>
</div>

<section id="products" class="container my-5 py-4">
  <h2 class="text-center mb-5" id="products-title">Featured Products</h2>
  <div class="text-center my-5">
    <div class="spinner-border text-primary" id="loading-spinner" style="display:none;"></div>
  </div>
  <div id="search-results" class="row g-4">
    <!-- Products will load here via AJAX -->
  </div>
  <div id="view-all" class="text-center mt-5" style="display: none;">
    <a href="gallery.php" class="btn btn-outline-primary px-4">
      <i class="fas fa-images me-2"></i> View Gallery
    </a>
  </div>
</section>

<!-- Developer Contact Section -->
<div class="container">
  <div class="developer-card text-center">
    <h3 class="developer-title mb-4">Web Application Developed By</h3>
    <h4 class="mb-3">Nhlulo Baloye</h4>
    <p class="mb-4">Professional Web Developer & Designer</p>
    
    <div class="d-flex flex-wrap justify-content-center">
      <a href="mailto:nhlulobaloy22@gmail.com" class="contact-badge">
        <i class="fas fa-envelope me-2"></i>nhlulobaloy22@gmail.com
      </a>
      <a href="tel:+27717974493" class="contact-badge">
        <i class="fas fa-phone me-2"></i>071 797 4493
      </a>
      <a href="https://wa.me/27717974493" class="contact-badge">
        <i class="fab fa-whatsapp me-2"></i>WhatsApp
      </a>
    </div>
    
    <p class="mt-4 mb-0">Need a similar website or web application for your business?</p>
    <p>Contact me for professional web development services!</p>
  </div>
</div>

<?php if (!isset($_SESSION['user_id'])): ?>
  <section id="login" class="container auth-section">
    <div class="row justify-content-center">
      <div class="col-lg-6">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="card-title text-center mb-4"><i class="fas fa-sign-in-alt me-2"></i>Login</h2>
            <form action="login.php" method="post">
              <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <div class="d-grid">
                <button type="submit" class="btn btn-primary">Login <i class="fas fa-arrow-right ms-2"></i></button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="signup" class="container auth-section">
    <div class="row justify-content-center">
      <div class="col-lg-6">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h2 class="card-title text-center mb-4"><i class="fas fa-user-plus me-2"></i>Sign Up</h2>
            <form action="signup.php" method="post">
              <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control" pattern="[0-9]{10,15}" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
              </div>
              <div class="d-grid">
                <button type="submit" class="btn btn-success">Create Account <i class="fas fa-user-check ms-2"></i></button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>

<footer class="mt-5">
  <div class="container py-4">
    <div class="row">
      <div class="col-md-6 text-center text-md-start">
        <p>&copy; <?= date("Y") ?> Ilo's Kit. All rights reserved.</p>
      </div>
      <div class="col-md-6 text-center text-md-end">
        <p>Web Application by <strong>Nhlulo Baloye</strong></p>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('search-input');
  const searchForm = document.getElementById('search-form');
  const searchResults = document.getElementById('search-results');
  const loadingSpinner = document.getElementById('loading-spinner');
  const searchText = document.getElementById('search-text');
  const resultsCount = document.getElementById('results-count');
  const productsTitle = document.getElementById('products-title');
  const viewAll = document.getElementById('view-all');
  const searchStatus = document.getElementById('search-status');

  loadProducts('');

  searchInput.addEventListener('input', debounce(function () {
    const term = this.value.trim();
    updateUrl(term);
    loadProducts(term);
  }, 300));

  searchForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const term = searchInput.value.trim();
    updateUrl(term);
    loadProducts(term);
  });

  function updateUrl(term) {
    const url = new URL(window.location);
    if (term) url.searchParams.set('search', term);
    else url.searchParams.delete('search');
    history.pushState({}, '', url);
  }

  function loadProducts(term) {
    loadingSpinner.style.display = 'block';
    searchResults.innerHTML = '';
    fetch(`search_products.php?search=${encodeURIComponent(term)}`)
      .then(res => res.text())
      .then(html => {
        searchResults.innerHTML = html;
        const count = document.querySelectorAll('#search-results .col-md-6').length;
        searchText.textContent = `Search results for: "${term}"`;
        resultsCount.textContent = `${count} found`;
        productsTitle.textContent = term ? 'Search Results' : 'Featured Products';
        searchStatus.style.display = term ? 'block' : 'none';
        viewAll.style.display = term ? 'none' : 'block';
      })
      .catch(() => {
        searchResults.innerHTML = '<div class="text-center text-danger">Error loading products</div>';
      })
      .finally(() => loadingSpinner.style.display = 'none');
  }

  function debounce(fn, delay) {
    let timeout;
    return function () {
      clearTimeout(timeout);
      timeout = setTimeout(() => fn.apply(this, arguments), delay);
    };
  }
});
</script>
</body>
</html>