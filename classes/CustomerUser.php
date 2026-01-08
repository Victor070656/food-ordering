<?php
/**
 * Customer User Class
 * Handles customer registration and login
 */

class CustomerUser {
    private $db;
    private $user;
    private $customer;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->user = new User();
        $this->customer = new Customer();
    }

    /**
     * Register a new customer
     */
    public function register($data) {
        // Check if email already exists
        $existing = $this->db->selectOne(
            "SELECT id FROM users WHERE email = ?",
            [$data['email']]
        );

        if ($existing) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        // Create user account (plain text password)
        $userId = $this->db->insert(
            "INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, 'customer', 'active')",
            [
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['password']
            ]
        );

        if (!$userId) {
            return ['success' => false, 'message' => 'Failed to create account'];
        }

        // Create customer record
        $customerId = $this->db->insert(
            "INSERT INTO customers (user_id, name, phone, email, address) VALUES (?, ?, ?, ?, ?)",
            [
                $userId,
                $data['name'],
                $data['phone'],
                $data['email'],
                $data['address'] ?? null
            ]
        );

        // Auto-login
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $data['name'];
        $_SESSION['user_email'] = $data['email'];
        $_SESSION['user_role'] = 'customer';
        $_SESSION['customer_id'] = $customerId;

        return [
            'success' => true,
            'user_id' => $userId,
            'customer_id' => $customerId,
            'message' => 'Account created successfully'
        ];
    }

    /**
     * Customer login
     */
    public function login($email, $password) {
        $userData = $this->db->selectOne(
            "SELECT u.*, c.id as customer_id FROM users u
             LEFT JOIN customers c ON u.id = c.user_id
             WHERE u.email = ? AND u.role = 'customer'",
            [$email]
        );

        if (!$userData) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        if ($userData['status'] === 'inactive') {
            return ['success' => false, 'message' => 'Your account has been deactivated'];
        }

        // Simple password comparison (no hashing)
        if ($password === $userData['password']) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['user_name'] = $userData['name'];
            $_SESSION['user_email'] = $userData['email'];
            $_SESSION['user_role'] = 'customer';
            $_SESSION['customer_id'] = $userData['customer_id'];

            return [
                'success' => true,
                'user' => [
                    'id' => $userData['id'],
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'customer_id' => $userData['customer_id']
                ]
            ];
        }

        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    /**
     * Get customer profile
     */
    public function getProfile($userId) {
        return $this->db->selectOne(
            "SELECT u.*, c.id as customer_id, c.address, c.preferences, c.total_orders, c.total_spent
             FROM users u
             LEFT JOIN customers c ON u.id = c.user_id
             WHERE u.id = ?",
            [$userId]
        );
    }

    /**
     * Update customer profile
     */
    public function updateProfile($userId, $data) {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = "u.name = ?";
            $params[] = $data['name'];
            $params[] = $data['name']; // For customer table too
        }
        if (isset($data['phone'])) {
            $fields[] = "u.phone = ?";
            $params[] = $data['phone'];
            $params[] = $data['phone']; // For customer table too
        }
        if (isset($data['address'])) {
            $fields[] = "c.address = ?";
            $params[] = $data['address'];
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $params[] = $userId;
        $params[] = $userId;

        // Update both users and customers tables
        $query = "UPDATE users u JOIN customers c ON u.id = c.user_id SET " . implode(', ', $fields) . " WHERE u.id = ? AND c.user_id = ?";

        $affected = $this->db->update($query, $params);

        return [
            'success' => $affected > 0,
            'message' => $affected > 0 ? 'Profile updated successfully' : 'No changes made'
        ];
    }

    /**
     * Get customer's orders
     */
    public function getOrders($customerId, $limit = 20) {
        return $this->db->select(
            "SELECT o.*,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
             FROM orders o
             WHERE o.customer_id = ?
             ORDER BY o.created_at DESC
             LIMIT ?",
            [$customerId, $limit]
        );
    }

    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->selectOne(
            "SELECT password FROM users WHERE id = ?",
            [$userId]
        );

        if (!$user || $currentPassword !== $user['password']) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        $this->db->update(
            "UPDATE users SET password = ? WHERE id = ?",
            [$newPassword, $userId]
        );

        return ['success' => true, 'message' => 'Password changed successfully'];
    }
}
