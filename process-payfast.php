<?php
session_start();

// Check for stored payment data
if (!isset($_SESSION['payfast_data'])) {
    header("Location: checkout.php");
    exit;
}

// Replace merchant credentials with actual ones
$_SESSION['payfast_data']['merchant_id'] = '18931794';
$_SESSION['payfast_data']['merchant_key'] = 'ndvo45gntqyst';

// Rebuild signature
$payfastData = $_SESSION['payfast_data'];
$signatureString = '';

foreach ($payfastData as $key => $val) {
    if (!empty($val) && $key !== 'signature') {
        $signatureString .= $key . '=' . urlencode(trim($val)) . '&';
    }
}
$signatureString = rtrim($signatureString, '&');
$payfastData['signature'] = md5($signatureString);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting to PayFast...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h3>Redirecting to PayFast for secure payment...</h3>
            <p>If you are not redirected, <strong><a href="#" onclick="document.getElementById('payfastForm').submit()">click here</a></strong>.</p>
        </div>
    </div>

    <!-- PayFast sandbox form -->
    <form id="payfastForm" action="https://sandbox.payfast.co.za/eng/process" method="post">
        <?php foreach ($payfastData as $name => $value): ?>
            <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value) ?>">
        <?php endforeach; ?>
    </form>

    <script>
        setTimeout(() => {
            document.getElementById('payfastForm').submit();
        }, 1000);
    </script>
</body>
</html>
