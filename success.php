<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "db_connection.php"; // your working DB connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';
require 'phpmailer/Exception.php';

// --- Yoco Payment Verification ---
if (isset($_GET['id'])) {
    $paymentId = $_GET['id'];
    $yocoSecretKey = $env['YOCO_SECRET_KEY'] ?? '';

    $ch = curl_init("https://online.yoco.com/v1/charges/" . $paymentId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Auth-Secret-Key: " . $yocoSecretKey
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $payment = json_decode($response, true);

if (!$payment || $payment['status'] !== 'successful') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Pending - Ilo's Kit</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { font-family: 'Segoe UI', sans-serif; background:#f8f9fa; color:#343a40; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
            .alert-container { background:white; padding:2rem; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1); text-align:center; max-width:500px; }
            .alert-icon { font-size:3rem; color:#ffc107; margin-bottom:1rem; }
            h2 { margin-bottom:1rem; }
            a.btn-primary { background:#4e54c8; border:none; color:white; padding:0.75rem 2rem; border-radius:50px; text-decoration:none; display:inline-block; transition:all 0.3s; }
            a.btn-primary:hover { background:linear-gradient(to right, #8f94fb, #4e54c8); transform:translateY(-2px); box-shadow:0 5px 15px rgba(78,84,200,0.3);}
        </style>
    </head>
    <body>
        <div class="alert-container">
            <div class="alert-icon">&#9888;</div>
            <h2>Payment Not Confirmed</h2>
            <p>Your payment has not been confirmed yet. Please wait a moment or contact support.</p>
            <a href="index.php" class="btn-primary">Go to Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}
}
// --- Check for pending order and cart ---
if (!isset($_SESSION['pending_order']) || !isset($_SESSION['cart'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>No Pending Order - Ilo's Kit</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { font-family: 'Segoe UI', sans-serif; background:#f8f9fa; color:#343a40; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
            .alert-container { background:white; padding:2rem; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1); text-align:center; max-width:500px; }
            .alert-icon { font-size:3rem; color:#dc3545; margin-bottom:1rem; }
            h2 { margin-bottom:1rem; }
            a.btn-primary { background:#4e54c8; border:none; color:white; padding:0.75rem 2rem; border-radius:50px; text-decoration:none; display:inline-block; transition:all 0.3s; }
            a.btn-primary:hover { background:linear-gradient(to right, #8f94fb, #4e54c8); transform:translateY(-2px); box-shadow:0 5px 15px rgba(78,84,200,0.3);}
        </style>
    </head>
    <body>
        <div class="alert-container">
            <div class="alert-icon">&#10060;</div>
            <h2>No Pending Order</h2>
            <p>We could not find any pending order or cart information.</p>
            <a href="index.php" class="btn-primary">Go to Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}


$pending = $_SESSION['pending_order'];
$cart = $_SESSION['cart'];

// --- Generate unique order ID ---
function generateUniqueOrderId($conn) {
    $maxAttempts = 5;
    $attempt = 0;
    do {
        $order_id = 'ORD' . mt_rand(1000000, 9999999);
        $stmtCheck = $conn->prepare("SELECT id FROM orders WHERE order_id=? LIMIT 1");
        $stmtCheck->bind_param("s", $order_id);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        $exists = $stmtCheck->num_rows > 0;
        $stmtCheck->close();
        $attempt++;
        if ($attempt > $maxAttempts) {
            throw new Exception("Could not generate unique order ID after multiple attempts.");
        }
    } while ($exists);
    return $order_id;
}

try {
    $uniqueOrderId = generateUniqueOrderId($conn);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// --- Insert order into DB ---
$stmt = $conn->prepare("INSERT INTO orders (order_id, user_id, amount, total_amount, delivery_address, delivery_option, province, status, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'processing', NOW())");
$stmt->bind_param(
    "siidsss",
    $uniqueOrderId,
    $pending['user_id'],
    $pending['amount'],
    $pending['amount'],
    $pending['delivery_address'],
    $pending['delivery_option'],
    $pending['province']
);


$stmt->execute();
$stmt->close();

// --- Insert order items ---
$orderItems = [];
foreach ($cart as $item) {
    $res = $conn->query("SELECT price, image_url, name FROM products WHERE id = {$item['product_id']} LIMIT 1");
    $row = $res->fetch_assoc();

    $price_at_purchase = $row['price'] ?? 0;
    $imageFilename = $row['image_url'] ?? 'default.png';
    $webImageUrl = '/kit_images/' . $imageFilename; // browser URL
    $absoluteImagePath = $_SERVER['DOCUMENT_ROOT'] . '/kit_images/' . $imageFilename; // server path for PHPMailer
    if (!file_exists($absoluteImagePath)) {
        $absoluteImagePath = $_SERVER['DOCUMENT_ROOT'] . '/kit_images/default.png';
        $webImageUrl = '/kit_images/default.png';
    }
    $name = $row['name'] ?? 'Product';

    $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase, size, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtItem->bind_param(
        "siiiss",
        $uniqueOrderId,
        $item['product_id'],
        $item['quantity'],
        $price_at_purchase,
        $item['size'],
        $imageFilename
    );
    $stmtItem->execute();
    $stmtItem->close();

    $orderItems[] = [
        'name' => $name,
        'size' => $item['size'],
        'quantity' => $item['quantity'],
        'price_at_purchase' => $price_at_purchase,
        'image_path' => $absoluteImagePath,
        'web_image_url' => $webImageUrl
    ];
}

// --- Clear session ---
unset($_SESSION['cart'], $_SESSION['pending_order'], $_SESSION['promo'], $_SESSION['discount']);

// --- Fetch user info ---
$userRes = $conn->query("SELECT email, name FROM users WHERE id = {$pending['user_id']} LIMIT 1");
$userRow = $userRes->fetch_assoc();
$userEmail = $userRow['email'];
$userName  = $userRow['name'];

// --- Prepare email content ---
$subtotal = 0;
$emailContent = '<!DOCTYPE html><html><head><style>
body { font-family:"Segoe UI", sans-serif; color:#343a40; background:#f8f9fa; margin:0; padding:20px;}
.email-container { max-width:600px; margin:0 auto; background:white; border-radius:12px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.1);}
.email-header { text-align:center; padding:25px; background:linear-gradient(to right,#8f94fb,#4e54c8); color:white;}
.email-body { padding:25px; }
.order-details { background:#f8f9fa; padding:20px; border-radius:8px; margin:20px 0; }
.order-item { display:flex; border-bottom:1px solid #ddd; padding:15px 0; align-items:center;}
.order-item:last-child { border-bottom:none;}
.order-item img { width:70px; height:70px; object-fit:cover; margin-right:15px; border-radius:8px; border:1px solid #dee2e6;}
.order-item-info { flex:1; }
.order-item-name { font-weight:600; margin-bottom:5px; }
.order-item-meta { color:#6c757d; font-size:0.9rem;}
.order-total { font-weight:bold; font-size:1.1rem; margin-top:20px; padding-top:15px; border-top:2px dashed #dee2e6;}
.detail-row { display:flex; justify-content:space-between; margin-bottom:8px; }
.thank-you { text-align:center; margin-top:25px; padding-top:15px; border-top:1px solid #dee2e6; color:#6c757d;}
</style></head><body>
<div class="email-container">
<div class="email-header"><h2>Thank you for your purchase, '.$userName.'!</h2></div>
<div class="email-body">
<p>Your order <strong>'.$uniqueOrderId.'</strong> has been confirmed and is now being processed.</p>
<div class="order-details"><h3 style="margin-top:0;color:#4e54c8;">Order Summary</h3>';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'iloskit1219@gmail.com';
    $mail->Password = 'xaqcyejonjgopinw'; // app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('iloskit1219@gmail.com', 'Ilo\'s Kit');
    $mail->addAddress($userEmail, $userName);
    $mail->isHTML(true);
    $mail->Subject = "Your Ilo's Kit Order Confirmation";

    foreach ($orderItems as $item) {
        $itemTotal = $item['price_at_purchase'] * $item['quantity'];
        $subtotal += $itemTotal;

        $cid = md5($item['name']);
        if (file_exists($item['image_path'])) {
            $mail->AddEmbeddedImage($item['image_path'], $cid);
        }

        $emailContent .= '<div class="order-item">
        <img src="cid:'.$cid.'" alt="'.$item['name'].'">
        <div class="order-item-info">
            <div class="order-item-name">'.$item['name'].'</div>
            <div class="order-item-meta">Size: '.$item['size'].' | Quantity: '.$item['quantity'].'</div>
        </div>
        <div style="font-weight:600;color:#4e54c8;">R'.number_format($itemTotal,2).'</div>
        </div>';
    }

    $emailContent .= '<div class="order-total">
    <div class="detail-row"><span>Subtotal:</span><span>R'.number_format($subtotal,2).'</span></div>
    <div class="detail-row"><span>Delivery Option:</span><span>'.$pending['delivery_option'].'</span></div>
    <div class="detail-row"><span>Delivery Address:</span><span>'.$pending['delivery_address'].'</span></div>
    <div class="detail-row" style="font-size:1.2rem;margin-top:10px;"><span>Total Paid:</span><span>R'.number_format($pending['amount'],2).'</span></div>
    </div></div>
    <div class="thank-you">
    <p>If you have any questions about your order, please contact our support team.</p>
    <p>Thank you for shopping with <strong>Ilo\'s Kit</strong>!</p>
    </div></div></body></html>';

    $mail->Body = $emailContent;
    $mail->send();
} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
}

$_SESSION['cart'] = []; // creates an empty cart array

// Optional: clear any checkout details
unset($_SESSION['checkout_details']);
unset($_SESSION['pending_order']);
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Successful - Ilo's Kit</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #4e54c8;
    --primary-gradient: linear-gradient(to right, #8f94fb, #4e54c8);
    --success: #28a745;
    --light-bg: #f8f9fa;
    --dark-text: #343a40;
    --border-radius: 12px;
    --box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
body { background: var(--light-bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--dark-text); padding-bottom:2rem;}
.success-container { background:#fff; border-radius:var(--border-radius); padding:2.5rem; max-width:850px; margin:2rem auto; box-shadow:var(--box-shadow); border-top:5px solid var(--success);}
.success-header { text-align:center; margin-bottom:2rem; }
.success-icon { display:inline-flex; justify-content:center; align-items:center; width:80px; height:80px; background:var(--success); color:white; border-radius:50%; font-size:2.5rem; margin-bottom:1rem; animation:pulse 1.5s infinite;}
@keyframes pulse { 0%{transform:scale(1);}50%{transform:scale(1.05);}100%{transform:scale(1);} }
.order-details { background:var(--light-bg); border-radius:var(--border-radius); padding:1.5rem; margin-top:1.5rem; }
.order-id { color:var(--primary); font-weight:700; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:2px solid #e9ecef; }
.order-item { display:flex; align-items:center; padding:1rem 0; border-bottom:1px solid #e9ecef; transition: background-color 0.2s; }
.order-item:hover { background-color: rgba(78,84,200,0.03); }
.order-item img { width:70px; height:70px; object-fit:cover; border-radius:8px; margin-right:1rem; border:1px solid #dee2e6; }
.order-item-info { flex:1; }
.order-item-name { font-weight:600; margin-bottom:0.25rem; }
.order-item-meta { color:#6c757d; font-size:0.9rem; }
.order-item-price { font-weight:600; color:var(--primary); }
.detail-row { display:flex; justify-content:space-between; padding:0.5rem 0; border-bottom:1px dashed #dee2e6; }
.detail-label { font-weight:500; }
.detail-value { font-weight:600; }
.total-row { display:flex; justify-content:space-between; padding:1rem 0; font-weight:700; font-size:1.1rem; color:var(--primary); border-top:2px solid #dee2e6; margin-top:0.5rem; }
.btn-primary { background:var(--primary-gradient); border:none; padding:0.75rem 2rem; font-weight:600; border-radius:50px; margin-top:1.5rem; transition:all 0.3s; width:100%; max-width:300px; margin-left:auto; margin-right:auto; display:block; }
.btn-primary:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(78,84,200,0.3);}
.email-note { text-align:center; margin-top:1rem; color:#6c757d; font-size:0.9rem;}
</style>
</head>
<body>
<div class="success-container">
    <div class="success-header">
        <div class="success-icon"><i class="fas fa-check"></i></div>
        <h2>Payment Successful!</h2>
        <p>Your order <span class="order-id"><?php echo $uniqueOrderId; ?></span> is confirmed.</p>
    </div>
    <div class="order-details">
        <?php foreach($orderItems as $item): ?>
        <div class="order-item">
            <img src="<?php echo $item['web_image_url']; ?>" alt="<?php echo $item['name']; ?>">
            <div class="order-item-info">
                <div class="order-item-name"><?php echo $item['name']; ?></div>
                <div class="order-item-meta">Size: <?php echo $item['size']; ?> | Quantity: <?php echo $item['quantity']; ?></div>
            </div>
            <div class="order-item-price">R<?php echo number_format($item['price_at_purchase'] * $item['quantity'],2); ?></div>
        </div>
        <?php endforeach; ?>
        <div class="detail-row"><span class="detail-label">Subtotal:</span><span class="detail-value">R<?php echo number_format($subtotal,2); ?></span></div>
        <div class="detail-row"><span class="detail-label">Delivery Option:</span><span class="detail-value"><?php echo $pending['delivery_option']; ?></span></div>
        <div class="detail-row"><span class="detail-label">Delivery Address:</span><span class="detail-value"><?php echo $pending['delivery_address']; ?></span></div>
        <div class="total-row"><span>Total Paid:</span><span>R<?php echo number_format($pending['amount'],2); ?></span></div>
    </div>
    <a href="index.php" class="btn btn-primary">Continue Shopping</a>
    <p class="email-note">A confirmation email has been sent to <?php echo $userEmail; ?>.</p>
</div>
</body>
</html>
