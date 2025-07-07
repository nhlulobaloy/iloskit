<?php
require_once "../admin_auth.php";
require_once "../db_connection.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryId = $_POST['delivery_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $trackingNumber = $_POST['tracking_number'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Validate inputs
    if (empty($deliveryId) || !in_array($status, ['processing', 'dispatched', 'in_transit', 'at_paxi_point', 'delivered', 'failed'])) {
        header("Location: deliveries.php?error=invalid_input");
        exit;
    }
    
    // Update delivery status
    $updateStmt = $conn->prepare("UPDATE delivery_details 
                                 SET status = ?, 
                                     tracking_number = ?,
                                     notes = ?,
                                     updated_at = NOW()
                                 WHERE id = ?");
    $updateStmt->bind_param("sssi", $status, $trackingNumber, $notes, $deliveryId);
    $updateStmt->execute();
    
    // If delivered, update delivery date
    if ($status === 'delivered') {
        $conn->query("UPDATE delivery_details SET actual_delivery = NOW() WHERE id = $deliveryId");
        $conn->query("UPDATE orders SET delivery_date = NOW() WHERE order_id = '{$delivery['order_id']}'");
    }
    
    // If dispatched, update dispatch date
    if ($status === 'dispatched') {
        $conn->query("UPDATE orders SET dispatch_date = NOW() WHERE order_id = '{$delivery['order_id']}'");
    }
    
    // Redirect with success message
    header("Location: delivery_details.php?id=$deliveryId&success=status_updated");
    exit;
}

header("Location: deliveries.php");
exit;