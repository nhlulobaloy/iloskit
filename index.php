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
  /* Existing styles remain the same */
  body {
    background-color: #f8f9fc;
    font-family: 'Segoe UI', sans-serif;
  }

  .navbar-brand {
    font-weight: bold;
  }

  .hero-section {
    background: linear-gradient(135deg, #4e73df, #2e59d9);
    padding: 5rem 0;
    color: white;
    text-align: center;
  }

  .search-box {
    max-width: 600px;
    margin: 0 auto;
  }

  .card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }

  .card-img-top {
    height: 200px;
    object-fit: cover;
    border-radius: 10px 10px 0 0;
  }

  footer {
    background-color: #1a1a2e;
    color: white;
    padding: 2rem 0;
  }

  .auth-section {
    margin-top: 3rem;
  }

  .developer-card {
    background: linear-gradient(135deg, #f5f7fa, #e4e8f0);
    border-radius: 10px;
    padding: 20px;
    margin-top: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

  /* Password toggle button */
  .toggle-password-btn {
    position: absolute;
    top: 38px;
    right: 10px;
    background: transparent;
    border: none;
    transform: translateY(0);
    cursor: pointer;
    color: #6c757d;
    font-size: 1.1rem;
    padding: 0;
  }

  .position-relative {
    position: relative;
  }

  /* Mobile-specific improvements for the search bar */
  @media (max-width: 768px) {
    .hero-section {
      padding: 3rem 1rem;
    }
    
    .search-box {
      width: 90%;
      margin: 0 auto;
      padding: 0 15px;
    }
    
    .input-group {
      flex-direction: column;
      gap: 10px;
    }
    
    #search-input {
      width: 100% !important;
      margin-bottom: 10px;
      border-radius: 8px !important;
    }
    
    .input-group .btn {
      width: 100%;
      border-radius: 8px;
    }
    
    #search-status {
      margin: 15px;
      padding: 15px;
      border-radius: 10px;
    }
    
    #search-results {
      margin: 0 10px;
    }
    
    #products {
      margin: 20px 10px;
    }
  }

  @media (max-width: 576px) {
    .hero-section {
      padding: 2rem 0.5rem;
    }
    
    .hero-section h1 {
      font-size: 2rem;
      margin-bottom: 1rem;
    }
    
    .hero-section p {
      font-size: 1rem;
    }
    
    .search-box {
      width: 95%;
      padding: 0 10px;
    }
    
    #search-input {
      font-size: 16px;
      padding: 12px;
    }
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
            <li class="nav-item"><a class="nav-link" href="admin_dashboard"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="upload_image"><i class="fas fa-upload me-1"></i> Upload</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="user_dashboard"><i class="fas fa-user-circle me-1"></i> Profile</a></li>
            <li class="nav-item"><a class="nav-link" href="#cart"><i class="fas fa-shopping-cart me-1"></i> Cart</a></li>
            <li class="nav-item"><a class="nav-link" href="my_orders"><i class="fas fa-clipboard-list me-1"></i> Orders</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="logout"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
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

<!-- Cart Section -->
<?php if (isset($_SESSION['user_id'])): ?>
<section id="cart" class="container my-5">
  <h2 class="text-center mb-4"><i class="fas fa-shopping-cart me-2"></i>Your Cart</h2>
  <?php if (!empty($_SESSION['cart'])): ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-dark">
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $total = 0;
          foreach($_SESSION['cart'] as $id => $item):
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
          ?>
          <tr>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td>R<?= number_format($item['price'],2) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td>R<?= number_format($subtotal,2) ?></td>
            <td>
              <a href="cart_remove.php?id=<?= $id ?>" class="btn btn-sm btn-danger">
                <i class="fas fa-trash"></i> Remove
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="3" class="text-end">Total:</th>
            <th colspan="2">R<?= number_format($total,2) ?></th>
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="text-end">
      <a href="checkout.php" class="btn btn-success btn-lg"><i class="fas fa-credit-card me-2"></i>Proceed to Checkout</a>
    </div>
  <?php else: ?>
    <p class="text-center fs-5">Your cart is empty. Add products to see them here.</p>
  <?php endif; ?>
</section>
<?php endif; ?>

<!-- Contact Section -->
<section id="contact" class="container my-5">
  <div class="card shadow">
    <div class="card-body p-4">
      <h2 class="text-center mb-4"><i class="fas fa-headset me-2"></i> Need Help?</h2>
      <div class="row">
        <div class="col-md-6 mb-4">
          <div class="p-4 h-100" style="background-color: #f8f9fa; border-radius: 8px;">
            <h4><i class="fas fa-envelope me-2"></i> Customer Support</h4>
            <p class="mb-3">We're here to help with any questions about your order.</p>
            <a href="mailto:iloskit1219@gmail.com" class="btn btn-primary">
              <i class="fas fa-paper-plane me-2"></i> Email Us
            </a>
            <div class="mt-3">
              <p><strong>Response Time:</strong> Within 24 hours</p>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="p-4 h-100" style="background-color: #f8f9fa; border-radius: 8px;">
            <h4><i class="fas fa-question-circle me-2"></i> Common Questions</h4>
            <div class="accordion mt-3" id="faqAccordion">
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                    My order hasn't arrived
                  </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                  <div class="accordion-body">
                    Email us at <a href="mailto:iloskit1219@gmail.com">iloskit1219@gmail.com</a> with your order number.
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                    Need custom designs?
                  </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                  <div class="accordion-body">
                    Contact us for personalized teamwear at <a href="mailto:iloskit1219@gmail.com">iloskit1219@gmail.com</a>.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Developer Section -->
<div class="container">
  <div class="developer-card text-center">
    <h3 class="developer-title mb-4">Web Application Developed By</h3>
    <h4 class="mb-3">Nhlulo Baloye</h4>
    <p class="mb-4">Professional Web Developer & Designer</p>

    <div class="d-flex flex-wrap justify-content-center">
      <a href="mailto:n.baloye@outlook.com" class="contact-badge">
        <i class="fas fa-envelope me-2"></i>n.baloye@outlook.com
      </a>
      <a href="tel:+27717974493" class="contact-badge">
        <i class="fas fa-phone me-2"></i>071 797 4493
      </a>
      <a href="https://wa.me/27717964493" class="contact-badge">
        <i class="fab fa-whatsapp me-2"></i>WhatsApp
      </a>
    </div>

    <p class="mt-4 mb-0">Need a similar website or web application for your business?</p>
    <p>Contact me for professional web development services!</p>
  </div>
</div>

<?php if (!isset($_SESSION['user_id'])): ?>
<!-- Login Section -->
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
            <div class="mb-3 text-end">
              <a href="forgot_password.php" class="text-decoration-none">Forgot password?</a>
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

<!-- Signup Section -->
<section id="signup" class="container auth-section">
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h2 class="card-title text-center mb-4"><i class="fas fa-user-plus me-2"></i>Sign Up</h2>
          <form id="signup-form" action="signup.php" method="post" onsubmit="return validateSignupPasswords()">
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
            <div class="mb-3 position-relative">
              <label class="form-label">Password</label>
              <input type="password" name="password" id="signup-password" class="form-control" required>
              <button type="button" class="toggle-password-btn" onclick="togglePassword('signup-password')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <div class="mb-3 position-relative">
              <label class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" id="signup-confirm-password" class="form-control" required>
              <button type="button" class="toggle-password-btn" onclick="togglePassword('signup-confirm-password')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <div class="d-grid">
              <button type="submit" class="btn btn-success">Sign Up <i class="fas fa-arrow-right ms-2"></i></button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<footer class="text-center mt-5">
  <p>&copy; <?= date('Y') ?> Ilo's Kit. All rights reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword(id) {
  const field = document.getElementById(id);
  field.type = field.type === 'password' ? 'text' : 'password';
}

// Optional: Signup password validation
function validateSignupPasswords() {
  const pass = document.getElementById('signup-password').value;
  const confirm = document.getElementById('signup-confirm-password').value;
  if(pass !== confirm){
    alert("Passwords do not match!");
    return false;
  }
  return true;
}
</script>

</body>
</html>
