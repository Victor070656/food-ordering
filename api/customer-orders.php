<?php
/**
 * API - Customer Orders Endpoint
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

$customer = new Customer();

$id = $_GET['id'] ?? null;

if ($id) {
    $result = $customer->getOrderHistory($id, 10);
} else {
    $result = [];
}

echo json_encode(['success' => true, 'orders' => $result]);
