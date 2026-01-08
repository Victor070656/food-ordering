<?php
/**
 * Customer Class
 * Handles customer management (Mini CRM)
 */

class Customer {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new customer
     */
    public function create($data) {
        // Check if phone already exists
        $existing = $this->getByPhone($data['phone']);

        if ($existing) {
            return ['success' => false, 'message' => 'Customer with this phone already exists', 'customer' => $existing];
        }

        $customerId = $this->db->insert(
            "INSERT INTO customers (name, phone, email, address, preferences, notes) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['phone'],
                $data['email'] ?? null,
                $data['address'] ?? null,
                isset($data['preferences']) ? json_encode($data['preferences']) : null,
                $data['notes'] ?? null
            ]
        );

        if ($customerId) {
            return ['success' => true, 'customer_id' => $customerId];
        }

        return ['success' => false, 'message' => 'Failed to create customer'];
    }

    /**
     * Get or create customer by phone
     */
    public function getOrCreate($phone, $name = null) {
        $customer = $this->getByPhone($phone);

        if ($customer) {
            return ['success' => true, 'customer' => $customer, 'created' => false];
        }

        if ($name) {
            $customerId = $this->db->insert(
                "INSERT INTO customers (name, phone) VALUES (?, ?)",
                [$name, $phone]
            );

            if ($customerId) {
                return [
                    'success' => true,
                    'customer' => $this->getById($customerId),
                    'created' => true
                ];
            }
        }

        return ['success' => false, 'message' => 'Customer not found'];
    }

    /**
     * Get customer by ID
     */
    public function getById($id) {
        $customer = $this->db->selectOne(
            "SELECT * FROM customers WHERE id = ?",
            [$id]
        );

        if ($customer && $customer['preferences']) {
            $customer['preferences'] = json_decode($customer['preferences'], true);
        }

        return $customer;
    }

    /**
     * Get customer by phone
     */
    public function getByPhone($phone) {
        $customer = $this->db->selectOne(
            "SELECT * FROM customers WHERE phone = ?",
            [$phone]
        );

        if ($customer && $customer['preferences']) {
            $customer['preferences'] = json_decode($customer['preferences'], true);
        }

        return $customer;
    }

    /**
     * Search customers by name or phone
     */
    public function search($search) {
        return $this->db->select(
            "SELECT id, name, phone, email, address, total_orders, total_spent, last_order_date
             FROM customers
             WHERE name LIKE ? OR phone LIKE ?
             ORDER BY total_orders DESC, total_spent DESC
             LIMIT 20",
            ["%$search%", "%$search%"]
        );
    }

    /**
     * Get all customers with pagination
     */
    public function getAll($page = 1, $perPage = 20, $search = null) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = '';

        if ($search) {
            $where = " WHERE name LIKE ? OR phone LIKE ? OR email LIKE ?";
            $searchTerm = "%$search%";
            $params = [$searchTerm, $searchTerm, $searchTerm];
        }

        // Get total count
        $countResult = $this->db->selectOne("SELECT COUNT(*) as total FROM customers$where", $params);
        $total = (int)$countResult['total'];

        // Get customers
        $params[] = $perPage;
        $params[] = $offset;
        $customers = $this->db->select(
            "SELECT id, name, phone, email, address, total_orders, total_spent, last_order_date, created_at
             FROM customers
             $where
             ORDER BY total_orders DESC, created_at DESC
             LIMIT ? OFFSET ?",
            $params
        );

        return [
            'customers' => $customers,
            'pagination' => getPaginationInfo($total, $perPage, $page)
        ];
    }

    /**
     * Get top customers
     */
    public function getTopCustomers($limit = 10) {
        return $this->db->select(
            "SELECT id, name, phone, email, total_orders, total_spent, last_order_date
             FROM customers
             WHERE total_orders > 0
             ORDER BY total_orders DESC, total_spent DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Update customer
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = $data['name'];
        }
        if (isset($data['phone'])) {
            $fields[] = "phone = ?";
            $params[] = $data['phone'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        if (isset($data['address'])) {
            $fields[] = "address = ?";
            $params[] = $data['address'];
        }
        if (isset($data['preferences'])) {
            $fields[] = "preferences = ?";
            $params[] = json_encode($data['preferences']);
        }
        if (isset($data['notes'])) {
            $fields[] = "notes = ?";
            $params[] = $data['notes'];
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $params[] = $id;
        $query = "UPDATE customers SET " . implode(', ', $fields) . " WHERE id = ?";

        $affected = $this->db->update($query, $params);

        return [
            'success' => $affected > 0,
            'message' => $affected > 0 ? 'Customer updated successfully' : 'No changes made'
        ];
    }

    /**
     * Delete customer
     */
    public function delete($id) {
        // Check if customer has orders
        $orderCount = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM orders WHERE customer_id = ?",
            [$id]
        );

        if ($orderCount['count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete customer with existing orders'];
        }

        $affected = $this->db->delete("DELETE FROM customers WHERE id = ?", [$id]);

        return [
            'success' => $affected > 0,
            'message' => $affected > 0 ? 'Customer deleted successfully' : 'Customer not found'
        ];
    }

    /**
     * Update customer stats after order
     */
    public function updateStats($customerId, $orderAmount) {
        $this->db->execute(
            "UPDATE customers
             SET total_orders = total_orders + 1,
                 total_spent = total_spent + ?,
                 last_order_date = CURDATE()
             WHERE id = ?",
            [$orderAmount, $customerId]
        );
    }

    /**
     * Get customer order history
     */
    public function getOrderHistory($customerId, $limit = 10) {
        return $this->db->select(
            "SELECT o.id, o.order_number, o.status, o.total_amount, o.payment_status,
                    o.created_at, o.delivered_at
             FROM orders o
             WHERE o.customer_id = ?
             ORDER BY o.created_at DESC
             LIMIT ?",
            [$customerId, $limit]
        );
    }
}
