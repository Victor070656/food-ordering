<?php
/**
 * API - Order Endpoint
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

$order = new Order();

// Check authentication for write operations
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    requireLogin();
}

$id = $_GET['id'] ?? null;

if ($id) {
    $result = $order->getById($id);
} else {
    $result = ['success' => false, 'message' => 'Order ID required'];
}

if ($result) {
    echo json_encode(['success' => true, 'order' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
}
