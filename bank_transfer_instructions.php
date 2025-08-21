<?php
session_start();
include "db_connection.php";  // Added so we can query DB

$order = $_SESSION['pending_order'] ?? null;

if (!$order) {
    header("Location: index.php");
    exit;
}

// Fetch full order data from DB using order_id
$orderId = $order['order_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$orderDetails = $result->fetch_assoc();
$stmt->close();

if (!$orderDetails) {
    // Order not found, redirect or error
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bank Transfer Instructions - Ilo's Kit</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6c63ff;
            --secondary-color: #f8f9fa;
            --accent-color: #ff6b6b;
            --dark-color: #343a40;
            --light-color: #ffffff;
        }

        body {
            background-color: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .confirmation-container {
            background-color: var(--light-color);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 40px;
        }

        .confirmation-header {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .bank-card {
            background-color: var(--secondary-color);
            padding: 20px;
            border-left: 4px solid var(--primary-color);
            border-radius: 6px;
            height: 100%;
        }

        .bank-card li {
            margin-bottom: 10px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #5a52d6;
            border-color: #5a52d6;
        }

        .order-info strong {
            color: var(--dark-color);
        }
        
        .bank-header {
            color: var(--primary-color);
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="confirmation-container">
                <h1 class="confirmation-header">
                    <i class="fas fa-money-check-alt me-2"></i>Bank Transfer Instructions
                </h1>

                <p class="mb-4">Thank you for your order, <strong><?= htmlspecialchars($_SESSION['user_firstname'] ?? 'Customer') ?></strong>! To complete your purchase, please make a bank transfer using one of our accounts below.</p>

                <div class="row mb-4">
                    <!-- ABSA Bank Card -->
                    <div class="col-md-6 mb-3">
                        <div class="bank-card">
                            <h5 class="bank-header"><i class="fas fa-university me-2"></i>ABSA Bank</h5>
                            <ul class="list-unstyled">
                                <li><strong>Account Name:</strong> Witness Mashele</li>
                                <li><strong>Account Number:</strong> 4095738475</li>
                                <li><strong>Branch Code:</strong> 632005</li>
                                <li><strong>Account Type:</strong> Current Account</li>
                                <li><strong>Phone:</strong> 0686658111</li>
                                <li><strong>Payment Reference:</strong> <?= htmlspecialchars($orderDetails['id']) ?></li>
                                <li><strong>Amount:</strong> R<?= number_format($orderDetails['total_amount'], 2) ?></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Capitec Bank Card -->
                    <div class="col-md-6 mb-3">
                        <div class="bank-card">
                            <h5 class="bank-header"><i class="fas fa-university me-2"></i>Capitec Bank</h5>
                            <ul class="list-unstyled">
                                <li><strong>Account Name:</strong> Witness Mashele</li>
                                <li><strong>Account Number:</strong> 173755637</li>
                                <li><strong>Branch Code:</strong> 470010</li>
                                <li><strong>Account Type:</strong> Savings</li>
                                <li><strong>Phone:</strong> 0686658111</li>
                                <li><strong>Payment Reference:</strong> <?= htmlspecialchars($orderDetails['id']) ?></li>
                                <li><strong>Amount:</strong> R<?= number_format($orderDetails['total_amount'], 2) ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="order-info mb-4">
                    <p><strong>Delivery Address:</strong> <?= htmlspecialchars($orderDetails['delivery_address']) ?></p>
                    <p><strong>Province:</strong> <?= htmlspecialchars($orderDetails['province'] ?? 'N/A') ?></p>
                    <p><strong>Delivery Option:</strong> <?= htmlspecialchars(ucfirst($orderDetails['delivery_option'] ?? 'N/A')) ?></p>
                </div>

                <div class="alert alert-warning mt-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Please complete your bank transfer within <u>3 days</u> of placing your order. Failure to make payment within this period will result in automatic order cancellation.<br>
                    Once your payment is received with the correct reference, we will begin processing your order immediately.
                </div>

                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Send your proof of payment to:</strong><br>
                    Email: <a href="mailto:iloskit1219@gmail.com">iloskit1219@gmail.com</a><br>
                    WhatsApp: <a href="https://wa.me/2717952283" target="_blank">072 098 4545</a><br><br>
                    <em>Please include the exact <strong>payment reference</strong> shown above in your payment proof.</em><br>
                    Payments with incorrect or missing references may not be allocated to your order, and we cannot be held responsible for any delays caused.
                </div>

                <a href="index.php" class="btn btn-primary mt-3">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
