<?php
require_once "../admin_auth.php";
require_once "../db_connection.php";

$deliveryId = $_GET['id'] ?? 0;

$query = "SELECT d.*, o.*, 
          CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
          u.email AS customer_email,
          u.phone AS customer_phone
          FROM delivery_details d
          JOIN orders o ON d.order_id = o.order_id
          JOIN users u ON o.user_id = u.id
          WHERE d.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $deliveryId);
$stmt->execute();
$delivery = $stmt->get_result()->fetch_assoc();

if (!$delivery) {
    header("Location: deliveries.php?error=not_found");
    exit;
}

// Get order items
$itemsQuery = "SELECT oi.*, p.name, p.image 
               FROM order_items oi
               JOIN products p ON oi.product_id = p.id
               WHERE oi.order_id = ?";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->bind_param("s", $delivery['order_id']);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!-- HTML similar to the list page but with full details -->