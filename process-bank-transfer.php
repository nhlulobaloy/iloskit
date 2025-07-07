<?php
session_start();
require_once 'config.php'; // Your database connection

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'];
    $paymentReference = $_POST['payment_reference'];
    $senderName = $_POST['sender_name'];
    
    // File upload handling
    $uploadDir = 'payment_proofs/';
    $fileName = $paymentReference . '_' . basename($_FILES['proof']['name']);
    $targetFile = $uploadDir . $fileName;
    
    // Check if file is uploaded
    if (move_uploaded_file($_FILES['proof']['tmp_name'], $targetFile)) {
        // Update database
        $stmt = $pdo->prepare("UPDATE orders SET 
            payment_method = 'bank_transfer',
            payment_reference = ?,
            payment_proof = ?,
            sender_name = ?,
            order_status = 'payment_pending',
            updated_at = NOW()
            WHERE order_id = ?");
            
        $stmt->execute([$paymentReference, $fileName, $senderName, $orderId]);
        
        // Send confirmation email
        // (You would implement your email function here)
        
        // Redirect to confirmation page
        header('Location: order-confirmation.php?order_id=' . $orderId);
        exit();
    } else {
        // Handle upload error
        die("Error uploading payment proof. Please try again.");
    }
} else {
    header('Location: checkout.php');
    exit();
}