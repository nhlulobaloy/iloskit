<?php
include("db_connection.php");

if (isset($_GET['query'])) {
    $search = $conn->real_escape_string($_GET['query']);
    $sql = "SELECT * FROM products WHERE name LIKE '%$search%' OR description LIKE '%$search%' ORDER BY id DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($product = $result->fetch_assoc()) {
            echo '<div class="col-6 col-md-4 col-lg-3 gallery-item">';
            echo '<img src="kit_images/' . htmlspecialchars($product['image_url']) . '" class="gallery-img img-thumbnail" alt="' . htmlspecialchars($product['name']) . '" data-fullimg="kit_images/' . htmlspecialchars($product['image_url']) . '">';
            echo '<div class="gallery-details">';
            echo '<h5 class="gallery-title">' . htmlspecialchars($product['name']) . '</h5>';
            echo '<p class="gallery-price">R' . number_format($product['price'], 2) . '</p>';
            echo '<form class="gallery-form" method="post">';
            echo '<input type="hidden" name="product_id" value="' . $product['id'] . '">';
            echo '<select name="size" class="form-select" required>
                    <option value="" disabled selected>Select Size</option>
                    <option value="S">S</option>
                    <option value="M">M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                  </select>';
            echo '<input type="number" name="quantity" value="1" min="1" max="10" class="form-control" placeholder="Qty">';
            echo '<button type="submit" name="add_to_cart" class="btn btn-primary btn-sm"><i class="fas fa-cart-plus me-1"></i> Add to Cart</button>';
            echo '</form></div></div>';
        }
    } else {
        echo '<div class="col-12 text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h4>No products found</h4>
              </div>';
    }
}
?>
