<?php
include("db_connection.php");

$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

if (!empty($searchTerm)) {
    $query = "SELECT * FROM products 
             WHERE name LIKE '%$searchTerm%' 
             OR description LIKE '%$searchTerm%' 
             LIMIT 8";
} else {
    $query = "SELECT * FROM products LIMIT 4";
}

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '
        <div class="col-md-6 col-lg-3">
          <div class="card h-100">
            <img src="kit_images/'.htmlspecialchars($row['image_url']).'" class="card-img-top" alt="'.htmlspecialchars($row['name']).'">
            <div class="card-body d-flex flex-column">
              <h5 class="card-title">'.htmlspecialchars($row['name']).'</h5>
              <p class="card-text text-primary fw-bold">R'.number_format($row['price'], 2).'</p>';
              
        if (!empty($row['description'])) {
            echo '<p class="card-text text-muted small">'.htmlspecialchars($row['description']).'</p>';
        }
        
        echo '
              <form action="add_to_cart.php" method="post" class="mt-auto">
                <input type="hidden" name="product_id" value="'.$row['id'].'">
                <div class="row g-2 mb-3">
                  <div class="col-6">
                    <select name="size" required class="form-select form-select-sm">
                      <option value="" disabled selected>Size</option>
                      <option value="S">S</option>
                      <option value="M">M</option>
                      <option value="L">L</option>
                      <option value="XL">XL</option>
                    </select>
                  </div>
                  <div class="col-6">
                    <input type="number" name="quantity" value="1" min="1" class="form-control form-control-sm">
                  </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                  <i class="fas fa-cart-plus me-2"></i>Add to Cart
                </button>
              </form>
            </div>
          </div>
        </div>';
    }
} else if (!empty($searchTerm)) {
    echo '
    <div class="col-12 text-center py-5">
      <i class="fas fa-search fa-3x text-muted mb-3"></i>
      <h4>No products found for "'.htmlspecialchars($searchTerm).'"</h4>
      <p class="text-muted">Try different keywords</p>
    </div>';
}
?>