<?php
/**
 * Order Class
 * Handles order management
 */

class Order {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new order for logged-in customer
     */
    public function createForCustomer($data) {
        try {
            $this->db->beginTransaction();

            $customerId = $data['customer_id'];

            // Generate order number
            $orderNumber = generateOrderNumber();

            // Calculate totals
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += ($item['price'] * $item['quantity']);
            }
            $deliveryFee = $data['delivery_fee'] ?? DEFAULT_DELIVERY_FEE;
            $totalAmount = $subtotal + $deliveryFee;

            // Insert order
            $orderId = $this->db->insert(
                "INSERT INTO orders
                (order_number, customer_id, customer_name, customer_phone, delivery_address,
                 status, subtotal, delivery_fee, total_amount, payment_method, payment_status,
                 special_instructions)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $orderNumber,
                    $customerId,
                    $data['customer_name'],
                    $data['customer_phone'],
                    $data['delivery_address'],
                    $data['status'] ?? 'pending',
                    $subtotal,
                    $deliveryFee,
                    $totalAmount,
                    $data['payment_method'] ?? 'cash_on_delivery',
                    $data['payment_status'] ?? 'pending',
                    $data['special_instructions'] ?? null
                ]
            );

            if (!$orderId) {
                throw new Exception('Failed to create order');
            }

            // Insert order items
            foreach ($data['items'] as $item) {
                $this->db->insert(
                    "INSERT INTO order_items (order_id, item_name, quantity, unit_price, total_price, notes)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $orderId,
                        $item['name'],
                        $item['quantity'],
                        $item['price'],
                        ($item['price'] * $item['quantity']),
                        $item['notes'] ?? null
                    ]
                );
            }

            // Insert payment record
            $paymentStatus = $data['payment_status'] ?? 'pending';
            $this->db->insert(
                "INSERT INTO payments (order_id, amount, payment_method, payment_status, transaction_reference, notes, screenshot)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $orderId,
                    $totalAmount,
                    $data['payment_method'] ?? 'cash_on_delivery',
                    $paymentStatus,
                    $data['transaction_reference'] ?? null,
                    $data['payment_notes'] ?? null,
                    $data['payment_screenshot'] ?? null
                ]
            );

            // Update customer stats
            $customer = new Customer();
            $customer->updateStats($customerId, $totalAmount);

            $this->db->commit();

            // Send notification
            $this->sendNotification($orderId, 'order_confirmation');

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create a new order
     */
    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Get or create customer
            $customer = new Customer();
            $customerResult = $customer->getOrCreate(
                $data['customer_phone'],
                $data['customer_name']
            );

            if (!$customerResult['success']) {
                throw new Exception($customerResult['message']);
            }

            $customerId = $customerResult['customer']['id'];

            // Generate order number
            $orderNumber = generateOrderNumber();

            // Calculate totals
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += ($item['price'] * $item['quantity']);
            }
            $deliveryFee = $data['delivery_fee'] ?? DEFAULT_DELIVERY_FEE;
            $totalAmount = $subtotal + $deliveryFee;

            // Insert order
            $orderId = $this->db->insert(
                "INSERT INTO orders
                (order_number, customer_id, customer_name, customer_phone, delivery_address,
                 status, subtotal, delivery_fee, total_amount, payment_method, payment_status,
                 special_instructions)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $orderNumber,
                    $customerId,
                    $data['customer_name'],
                    $data['customer_phone'],
                    $data['delivery_address'],
                    $data['status'] ?? 'pending',
                    $subtotal,
                    $deliveryFee,
                    $totalAmount,
                    $data['payment_method'] ?? 'cash_on_delivery',
                    $data['payment_status'] ?? 'pending',
                    $data['special_instructions'] ?? null
                ]
            );

            if (!$orderId) {
                throw new Exception('Failed to create order');
            }

            // Insert order items
            foreach ($data['items'] as $item) {
                $this->db->insert(
                    "INSERT INTO order_items (order_id, item_name, quantity, unit_price, total_price, notes)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $orderId,
                        $item['name'],
                        $item['quantity'],
                        $item['price'],
                        ($item['price'] * $item['quantity']),
                        $item['notes'] ?? null
                    ]
                );
            }

            // Insert payment record
            $paymentStatus = $data['payment_status'] ?? 'pending';
            $this->db->insert(
                "INSERT INTO payments (order_id, amount, payment_method, payment_status, transaction_reference, notes, screenshot)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $orderId,
                    $totalAmount,
                    $data['payment_method'] ?? 'cash_on_delivery',
                    $paymentStatus,
                    $data['transaction_reference'] ?? null,
                    $data['payment_notes'] ?? null,
                    $data['payment_screenshot'] ?? null
                ]
            );

            // Update customer stats
            $customer->updateStats($customerId, $totalAmount);

            $this->db->commit();

            // Send notification if enabled
            if ($paymentStatus === 'paid') {
                $this->sendNotification($orderId, 'order_confirmation');
            }

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get order by ID
     */
    public function getById($id) {
        $order = $this->db->selectOne(
            "SELECT o.*,
                    c.name as customer_full_name, c.email as customer_email,
                    r.user_id as rider_user_id, u.name as rider_name, u.phone as rider_phone,
                    p.screenshot as payment_screenshot
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             LEFT JOIN riders r ON o.rider_id = r.id
             LEFT JOIN users u ON r.user_id = u.id
             LEFT JOIN payments p ON o.id = p.order_id
             WHERE o.id = ?",
            [$id]
        );

        if ($order) {
            $order['items'] = $this->getItems($id);
        }

        return $order;
    }

    /**
     * Get order by order number
     */
    public function getByOrderNumber($orderNumber) {
        $order = $this->db->selectOne(
            "SELECT o.*,
                    c.name as customer_full_name, c.email as customer_email,
                    r.user_id as rider_user_id, u.name as rider_name, u.phone as rider_phone
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             LEFT JOIN riders r ON o.rider_id = r.id
             LEFT JOIN users u ON r.user_id = u.id
             WHERE o.order_number = ?",
            [$orderNumber]
        );

        if ($order) {
            $order['items'] = $this->getItems($order['id']);
        }

        return $order;
    }

    /**
     * Get order items
     */
    public function getItems($orderId) {
        return $this->db->select(
            "SELECT * FROM order_items WHERE order_id = ?",
            [$orderId]
        );
    }

    /**
     * Get all orders with filters
     */
    public function getAll($filters = []) {
        $where = "WHERE 1=1";
        $params = [];

        // Status filter
        if (!empty($filters['status'])) {
            $where .= " AND o.status = ?";
            $params[] = $filters['status'];
        }

        // Payment status filter
        if (!empty($filters['payment_status'])) {
            $where .= " AND o.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        // Date filter
        if (!empty($filters['date'])) {
            $where .= " AND DATE(o.created_at) = ?";
            $params[] = $filters['date'];
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $where .= " AND DATE(o.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND DATE(o.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        // Today filter
        if (!empty($filters['today'])) {
            $where .= " AND DATE(o.created_at) = CURDATE()";
        }

        // Customer filter
        if (!empty($filters['customer_id'])) {
            $where .= " AND o.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        // Rider filter
        if (!empty($filters['rider_id'])) {
            $where .= " AND o.rider_id = ?";
            $params[] = $filters['rider_id'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        // Pagination
        $page = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 20;
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countResult = $this->db->selectOne("SELECT COUNT(*) as total FROM orders o $where", $params);
        $total = (int)$countResult['total'];

        // Get orders
        $params[] = $perPage;
        $params[] = $offset;

        $orders = $this->db->select(
            "SELECT o.*,
                    c.name as customer_full_name,
                    u.name as rider_name,
                    p.screenshot as payment_screenshot
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             LEFT JOIN riders r ON o.rider_id = r.id
             LEFT JOIN users u ON r.user_id = u.id
             LEFT JOIN payments p ON o.id = p.order_id
             $where
             ORDER BY o.created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );

        return [
            'orders' => $orders,
            'pagination' => getPaginationInfo($total, $perPage, $page)
        ];
    }

    /**
     * Get orders by status
     */
    public function getByStatus($status) {
        return $this->db->select(
            "SELECT o.*,
                    c.name as customer_full_name,
                    u.name as rider_name
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             LEFT JOIN riders r ON o.rider_id = r.id
             LEFT JOIN users u ON r.user_id = u.id
             WHERE o.status = ?
             ORDER BY o.created_at ASC",
            [$status]
        );
    }

    /**
     * Get today's orders
     */
    public function getTodayOrders() {
        return $this->db->select(
            "SELECT o.*,
                    c.name as customer_full_name,
                    u.name as rider_name
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             LEFT JOIN riders r ON o.rider_id = r.id
             LEFT JOIN users u ON r.user_id = u.id
             WHERE DATE(o.created_at) = CURDATE()
             ORDER BY o.created_at DESC",
            []
        );
    }

    /**
     * Update order status
     */
    public function updateStatus($orderId, $status) {
        $allowedStatuses = ['pending', 'preparing', 'out_for_delivery', 'delivered', 'failed', 'cancelled'];

        if (!in_array($status, $allowedStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }

        $order = $this->getById($orderId);

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        $affected = $this->db->update(
            "UPDATE orders SET status = ? WHERE id = ?",
            [$status, $orderId]
        );

        if ($affected > 0) {
            // Set delivered_at if status is delivered
            if ($status === 'delivered') {
                $this->db->update(
                    "UPDATE orders SET delivered_at = NOW() WHERE id = ?",
                    [$orderId]
                );
            }

            // Send notification based on status
            $this->sendNotification($orderId, $status);

            return ['success' => true, 'message' => 'Order status updated'];
        }

        return ['success' => false, 'message' => 'Failed to update status'];
    }

    /**
     * Assign rider to order
     */
    public function assignRider($orderId, $riderId) {
        $affected = $this->db->update(
            "UPDATE orders SET rider_id = ? WHERE id = ?",
            [$riderId, $orderId]
        );

        if ($affected > 0) {
            return ['success' => true, 'message' => 'Rider assigned successfully'];
        }

        return ['success' => false, 'message' => 'Failed to assign rider'];
    }

    /**
     * Update order
     */
    public function update($orderId, $data) {
        $fields = [];
        $params = [];

        if (isset($data['delivery_address'])) {
            $fields[] = "delivery_address = ?";
            $params[] = $data['delivery_address'];
        }
        if (isset($data['special_instructions'])) {
            $fields[] = "special_instructions = ?";
            $params[] = $data['special_instructions'];
        }
        if (isset($data['payment_method'])) {
            $fields[] = "payment_method = ?";
            $params[] = $data['payment_method'];
        }
        if (isset($data['payment_status'])) {
            $fields[] = "payment_status = ?";
            $params[] = $data['payment_status'];

            // Update payment record too
            $this->db->update(
                "UPDATE payments SET payment_status = ? WHERE order_id = ?",
                [$data['payment_status'], $orderId]
            );
        }
        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $params[] = $data['status'];
        }
        if (isset($data['rider_id'])) {
            $fields[] = "rider_id = ?";
            $params[] = $data['rider_id'];
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $params[] = $orderId;
        $query = "UPDATE orders SET " . implode(', ', $fields) . " WHERE id = ?";

        $affected = $this->db->update($query, $params);

        return [
            'success' => $affected > 0,
            'message' => $affected > 0 ? 'Order updated successfully' : 'No changes made'
        ];
    }

    /**
     * Get dashboard statistics
     */
    public function getStats() {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-01');

        // Today's stats
        $todayStats = $this->db->selectOne(
            "SELECT
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing,
                SUM(CASE WHEN status = 'out_for_delivery' THEN 1 ELSE 0 END) as out_for_delivery,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered
             FROM orders
             WHERE DATE(created_at) = ?",
            [$today]
        );

        // Week's stats
        $weekStats = $this->db->selectOne(
            "SELECT
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_revenue
             FROM orders
             WHERE DATE(created_at) >= ?",
            [$weekStart]
        );

        // Month's stats
        $monthStats = $this->db->selectOne(
            "SELECT
                COUNT(*) as total_orders,
                COALESCE(SUM(total_amount), 0) as total_revenue
             FROM orders
             WHERE DATE(created_at) >= ?",
            [$monthStart]
        );

        // Pending payments
        $pendingPayments = $this->db->selectOne(
            "SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount
             FROM orders
             WHERE payment_status = 'pending' AND status != 'cancelled'",
            []
        );

        // Status breakdown
        $statusBreakdown = $this->db->select(
            "SELECT status, COUNT(*) as count
             FROM orders
             WHERE DATE(created_at) = ?
             GROUP BY status",
            [$today]
        );

        return [
            'today' => $todayStats,
            'week' => $weekStats,
            'month' => $monthStats,
            'pending_payments' => $pendingPayments,
            'status_breakdown' => $statusBreakdown
        ];
    }

    /**
     * Get recent orders
     */
    public function getRecent($limit = 10) {
        return $this->db->select(
            "SELECT o.*,
                    c.name as customer_full_name,
                    u.name as rider_name
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             LEFT JOIN riders r ON o.rider_id = r.id
             LEFT JOIN users u ON r.user_id = u.id
             ORDER BY o.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Send notification for order status change
     */
    public function sendNotification($orderId, $status) {
        // Map statuses to notification types
        $typeMap = [
            'preparing' => 'order_preparing',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            'cancelled' => 'order_cancelled',
            'paid' => 'order_confirmation'
        ];

        $type = $typeMap[$status] ?? null;

        if (!$type) {
            return ['success' => false, 'message' => 'No notification for this status'];
        }

        $order = $this->getById($orderId);

        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        // Get notification template
        $template = $this->db->selectOne(
            "SELECT * FROM notification_templates WHERE type = ? AND is_active = 1",
            [$type]
        );

        if (!$template) {
            return ['success' => false, 'message' => 'Notification template not found'];
        }

        // Replace placeholders
        $placeholders = [
            '{customer_name}' => $order['customer_name'],
            '{order_number}' => $order['order_number'],
            '{total_amount}' => formatCurrency($order['total_amount']),
            '{rider_name}' => $order['rider_name'] ?? 'N/A',
            '{rider_phone}' => $order['rider_phone'] ?? 'N/A'
        ];

        $message = str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $template['sms_template']
        );

        // Log notification (in real implementation, send via API)
        $this->db->insert(
            "INSERT INTO notification_logs (order_id, recipient_phone, type, channel, message, status)
             VALUES (?, ?, ?, 'sms', ?, 'sent')",
            [$orderId, formatPhoneNumber($order['customer_phone']), $type, $message]
        );

        return ['success' => true, 'message' => 'Notification logged'];
    }
}
