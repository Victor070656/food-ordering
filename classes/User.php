<?php
/**
 * User Class
 * Handles user authentication and management
 */

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Authenticate user login
     */
    public function login($email, $password) {
        $user = $this->db->selectOne(
            "SELECT id, name, email, phone, password, role, status FROM users WHERE email = ?",
            [$email]
        );

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        if ($user['status'] === 'inactive') {
            return ['success' => false, 'message' => 'Your account has been deactivated. Please contact the admin.'];
        }

        // Simple password comparison (no hashing)
        if ($password === $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Update last login
            $this->db->execute(
                "UPDATE users SET updated_at = NOW() WHERE id = ?",
                [$user['id']]
            );

            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
        }

        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    /**
     * Logout user
     */
    public function logout() {
        session_unset();
        session_destroy();
    }

    /**
     * Create a new user
     */
    public function create($data) {
        // Check if email already exists
        $existing = $this->db->selectOne(
            "SELECT id FROM users WHERE email = ?",
            [$data['email']]
        );

        if ($existing) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $userId = $this->db->insert(
            "INSERT INTO users (name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['password'], // Plain text password (no hashing)
                $data['role'] ?? 'staff',
                $data['status'] ?? 'active'
            ]
        );

        if ($userId) {
            // If rider, create rider record
            if ($data['role'] === 'rider') {
                $this->db->insert(
                    "INSERT INTO riders (user_id, vehicle_type, plate_number) VALUES (?, ?, ?)",
                    [$userId, $data['vehicle_type'] ?? null, $data['plate_number'] ?? null]
                );
            }

            return ['success' => true, 'user_id' => $userId];
        }

        return ['success' => false, 'message' => 'Failed to create user'];
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        return $this->db->selectOne(
            "SELECT id, name, email, phone, role, status, created_at FROM users WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get all users with optional role filter
     */
    public function getAll($role = null, $status = null, $search = null) {
        $query = "SELECT id, name, email, phone, role, status, created_at FROM users WHERE 1=1";
        $params = [];

        if ($role) {
            $query .= " AND role = ?";
            $params[] = $role;
        }

        if ($status) {
            $query .= " AND status = ?";
            $params[] = $status;
        }

        if ($search) {
            $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $query .= " ORDER BY created_at DESC";

        return $this->db->select($query, $params);
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        if (isset($data['phone'])) {
            $fields[] = "phone = ?";
            $params[] = $data['phone'];
        }
        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $params[] = $data['status'];
        }
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $params[] = $data['role'];
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password = ?";
            $params[] = $data['password']; // Plain text password
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $params[] = $id;
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";

        $affected = $this->db->update($query, $params);

        return [
            'success' => $affected > 0,
            'message' => $affected > 0 ? 'User updated successfully' : 'No changes made'
        ];
    }

    /**
     * Delete user
     */
    public function delete($id) {
        // Prevent deleting yourself
        if (isset($_SESSION['user_id']) && $id == $_SESSION['user_id']) {
            return ['success' => false, 'message' => 'You cannot delete your own account'];
        }

        $affected = $this->db->delete("DELETE FROM users WHERE id = ?", [$id]);

        return [
            'success' => $affected > 0,
            'message' => $affected > 0 ? 'User deleted successfully' : 'User not found'
        ];
    }

    /**
     * Get user count by role
     */
    public function getCountByRole($role = null) {
        if ($role) {
            $result = $this->db->selectOne("SELECT COUNT(*) as count FROM users WHERE role = ?", [$role]);
        } else {
            $result = $this->db->selectOne("SELECT COUNT(*) as count FROM users");
        }

        return (int)($result['count'] ?? 0);
    }
}
