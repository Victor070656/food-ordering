<?php
/**
 * API - Customer Endpoint
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

$customer = new Customer();

$id = $_GET['id'] ?? null;

if ($id) {
    $result = $customer->getById($id);
} else {
    $result = null;
}

if ($result) {
    echo json_encode(['success' => true, 'customer' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'Customer not found']);
}
