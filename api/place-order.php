<?php
/**
 * API - Place Order (Customer only)
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

// Check if user is logged in as customer
if (!isLoggedIn() || !hasRole('customer')) {
    echo json_encode(['success' => false, 'message' => 'Please login to place an order']);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    try {
        // Parse items
        $items = json_decode($_POST['items_json'], true);

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items in order']);
            exit;
        }

        // Get logged-in customer info
        $customerUser = new CustomerUser();
        $profile = $customerUser->getProfile($_SESSION['user_id']);

        if (!$profile || !$profile['customer_id']) {
            echo json_encode(['success' => false, 'message' => 'Customer profile not found']);
            exit;
        }

        $paymentMethod = sanitize($_POST['payment_method'] ?? 'cash_on_delivery');
        $screenshotPath = null;

        // Handle screenshot upload for bank_transfer or pos
        if (in_array($paymentMethod, ['bank_transfer', 'pos'])) {
            $fileInput = $paymentMethod === 'bank_transfer' ? 'payment_screenshot' : 'pos_payment_screenshot';

            if (isset($_FILES[$fileInput]) && $_FILES[$fileInput]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$fileInput];

                // Validate file type
                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedTypes)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload an image.']);
                    exit;
                }

                // Validate file size (max 5MB)
                if ($file['size'] > 5 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
                    exit;
                }

                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('payment_', true) . '_' . time() . '.' . $extension;
                $uploadDir = dirname(__DIR__) . '/uploads/payment-screenshots/';

                // Ensure directory exists
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $screenshotPath = 'uploads/payment-screenshots/' . $filename;
                }
            }
        }

        // Create order data with logged-in user's info
        $data = [
            'customer_id' => $profile['customer_id'],
            'customer_name' => $profile['name'],
            'customer_phone' => $profile['phone'],
            'delivery_address' => sanitize($_POST['delivery_address'] ?? $profile['address'] ?? ''),
            'items' => $items,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'special_instructions' => sanitize($_POST['special_instructions'] ?? ''),
        ];

        // Add screenshot path if uploaded
        if ($screenshotPath) {
            $data['payment_screenshot'] = $screenshotPath;
        }

        $order = new Order();
        $result = $order->createForCustomer($data);

        if ($result['success']) {
            // Send notification
            $order->sendNotification($result['order_id'], 'order_confirmation');

            echo json_encode([
                'success' => true,
                'order_id' => $result['order_id'],
                'order_number' => $result['order_number']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Failed to place order']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
