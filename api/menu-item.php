<?php
/**
 * API - Menu Item Endpoint
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

$menuItem = new MenuItem();

$id = $_GET['id'] ?? null;

if ($id) {
    $result = $menuItem->getById($id);
} else {
    $result = null;
}

if ($result) {
    echo json_encode(['success' => true, 'item' => $result]);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
}
