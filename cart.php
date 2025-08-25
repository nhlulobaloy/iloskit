<?php
session_start();
include "db_connection.php";

// Display messages
foreach (['error','success','promo_error','promo_success'] as $msg) {
    if(isset($_SESSION[$msg])){
        $alertType = strpos($msg,'error')!==false?'danger':'success';
        echo '<div class="alert alert-'.$alertType.' alert-dismissible fade show" role="alert">'
            .$_SESSION[$msg].
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION[$msg]);
    }
}

// Clear promo if user cleared cart
if(isset($_GET['clear_cart'])){
    unset($_SESSION['cart']);
    unset($_SESSION['applied_promo']);
    unset($_SESSION['discount']);
}

// Fetch cart
$cart = $_SESSION['cart'] ?? [];
if(empty($cart)){
    echo '<!DOCTYPE html>
    <html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Ilo\'s Kit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body{background:#f8f9fa;font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;min-height:100vh;display:flex;align-items:center;}
        .empty-cart-container{max-width:500px;margin:0 auto;padding:40px;background:white;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.05);text-align:center;}
        .empty-cart-icon{font-size:5rem;color:#6c757d;margin-bottom:20px;opacity:0.7;}
        .btn-shopping{padding:10px 25px;border-radius:50px;font-weight:600;margin-top:15px;}
        .btn-back{margin-top:20px;padding:8px 20px;border-radius:50px;}
    </style></head>
    <body><div class="empty-cart-container">
        <div class="empty-cart-icon"><i class="fas fa-shopping-basket"></i></div>
        <h2 class="mb-3">Your cart feels lonely</h2>
        <p class="text-muted mb-4">Your shopping cart is empty. Let\'s find something special for you!</p>
        <a href="index.php" class="btn btn-primary btn-shopping"><i class="fas fa-store me-2"></i>Start Shopping</a>
        <div><a href="javascript:history.back()" class="btn btn-outline-secondary btn-back"><i class="fas fa-arrow-left me-2"></i>Go Back</a></div>
    </div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script></body></html>';
    exit;
}

// Extract unique product IDs
$productIds = array_unique(array_column($cart,'product_id'));
$idsString = implode(',',array_map('intval',$productIds));
$sql = "SELECT * FROM products WHERE id IN ($idsString)";
$result = $conn->query($sql);

$products = [];
while($row = $result->fetch_assoc()) $products[$row['id']] = $row;

// Calculate subtotal
$subtotal=0;
foreach($cart as $key=>$item){
    $pid=$item['product_id'];
    if(isset($products[$pid])) $subtotal += $products[$pid]['price'] * $item['quantity'];
}

// Calculate discount
$discount = 0;
$discountAmount = 0;
if(isset($_SESSION['applied_promo'])){
    $promo=$_SESSION['applied_promo'];
    $discountAmount = ($promo['discount_type']=='percentage')
        ? $subtotal*($promo['discount_value']/100)
        : min($promo['discount_value'],$subtotal);
    $discount = $discountAmount;
}

$grandTotal = $subtotal - $discount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Cart - Ilo's Kit</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body{background:#f8f9fa;font-family:Segoe UI,Tahoma,Geneva,Verdana,sans-serif;}
.cart-container{max-width:1200px;margin:30px auto;padding:20px;background:white;border-radius:10px;box-shadow:0 0 20px rgba(0,0,0,0.05);}
.cart-header{border-bottom:1px solid #eee;padding-bottom:15px;margin-bottom:30px;}
.product-img{width:80px;height:80px;object-fit:cover;border-radius:5px;}
.quantity-control{display:flex;align-items:center;}
.quantity-input{width:60px;text-align:center;margin:0 10px;}
.cart-total{background:#f8f9fa;padding:20px;border-radius:5px;margin-top:20px;}
.btn-checkout{padding:10px 30px;font-weight:600;border-radius:50px;}
.btn-continue{border-radius:50px;padding:10px 20px;}
.action-buttons{display:flex;gap:10px;}
.alert{max-width:1200px;margin:20px auto 0;}
.promo-code-container{margin-bottom:20px;}
.discount-text{color:#28a745;}
</style>
</head>
<body>
<div class="cart-container">
    <div class="cart-header d-flex justify-content-between align-items-center">
        <h2 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Your Shopping Cart</h2>
        <a href="?clear_cart=1" class="btn btn-outline-danger"><i class="fas fa-trash me-2"></i>Clear Cart & Promo</a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead class="table-light">
                <tr><th style="width:40%">Product</th><th>Size</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach($cart as $key=>$item):
                    $pid=$item['product_id'];
                    if(!isset($products[$pid])) continue;
                    $product=$products[$pid];
                    $size=htmlspecialchars($item['size']);
                    $qty=(int)$item['quantity'];
                    $itemSubtotal=$product['price']*$qty;
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <?php if(!empty($product['image_url'])): ?>
                                <img src="kit_images/<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img me-3">
                            <?php endif; ?>
                            <div><h6 class="mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                            <small class="text-muted">SKU: <?= $product['id'] ?></small></div>
                        </div>
                    </td>
                    <td class="align-middle"><?= $size ?></td>
                    <td class="align-middle">R<?= number_format($product['price'],2) ?></td>
                    <td class="align-middle">
                        <div class="quantity-control">
                            <form action="update_cart.php" method="post" class="d-inline">
                                <input type="hidden" name="cart_key" value="<?= htmlspecialchars($key) ?>">
                                <input type="hidden" name="quantity" value="<?= $qty-1 ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" <?= $qty<=1?'disabled':'' ?>><i class="fas fa-minus"></i></button>
                            </form>
                            <span class="quantity-input"><?= $qty ?></span>
                            <form action="update_cart.php" method="post" class="d-inline">
                                <input type="hidden" name="cart_key" value="<?= htmlspecialchars($key) ?>">
                                <input type="hidden" name="quantity" value="<?= $qty+1 ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-plus"></i></button>
                            </form>
                        </div>
                    </td>
                    <td class="align-middle">R<?= number_format($itemSubtotal,2) ?></td>
                    <td class="align-middle">
                        <form action="remove_from_cart.php" method="post" onsubmit="return confirm('Are you sure?');">
                            <input type="hidden" name="cart_key" value="<?= $key ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="row mt-4">
        <div class="col-md-5 ms-auto">
            <div class="promo-code-container">
                <form action="apply_promo.php" method="POST">
                    <div class="input-group">
                        <input type="text" name="promo_code" class="form-control" placeholder="Enter promo code" value="<?= $_SESSION['applied_promo']['code'] ?? '' ?>">
                        <?php if(isset($_SESSION['applied_promo'])): ?>
                            <button type="submit" name="remove_promo" class="btn btn-danger">Remove</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary">Apply</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="cart-total">
                <div class="d-flex justify-content-between mb-2"><span class="fw-bold">Subtotal:</span><span>R<?= number_format($subtotal,2) ?></span></div>
                <?php if(isset($_SESSION['applied_promo'])): ?>
                <div class="d-flex justify-content-between mb-2 discount-text"><span class="fw-bold">Discount (<?= $_SESSION['applied_promo']['code'] ?>):</span><span>-R<?= number_format($discountAmount,2) ?></span></div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mb-3"><span class="fw-bold">Shipping:</span><span>Calculated at checkout</span></div>
                <hr>
                <div class="d-flex justify-content-between"><h5 class="fw-bold">Total:</h5><h5 class="fw-bold">R<?= number_format($grandTotal,2) ?></h5></div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="index.php" class="btn btn-outline-secondary btn-continue"><i class="fas fa-store me-2"></i>Continue Shopping</a>
                <a href="checkout.php" class="btn btn-success btn-checkout">Proceed to Checkout<i class="fas fa-arrow-right ms-2"></i></a>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
