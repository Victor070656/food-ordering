<?php
/**
 * Rider Class
 * Handles rider management
 */

class Rider {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all riders
     */
    public function getAll($status = null, $availableOnly = false) {
        $query = "SELECT r.*, u.name, u.email, u.phone, u.status as user_status
                  FROM riders r
                  INNER JOIN users u ON r.user_id = u.id
                  WHERE 1=1";
        $params = [];

        if ($status) {
            $query .= " AND u.status = ?";
            $params[] = $status;
        }

        if ($availableOnly) {
            $query .= " AND r.is_available = 1 AND u.status = 'active'";
        }

        $query .= " ORDER BY u.name ASC";

        return $this->db->select($query, $params);
    }

    /**
     * Get rider by user ID
     */
    public function getByUserId($userId) {
        return $this->db->selectOne(
            "SELECT r.*, u.name, u.email, u.phone, u.status as user_status
             FROM riders r
             INNER JOIN users u ON r.user_id = u.id
             WHERE r.user_id = ?",
            [$userId]
        );
    }

    /**
     * Get rider by ID
     */
    public function getById($id) {
        return $this->db->selectOne(
            "SELECT r.*, u.name, u.email, u.phone, u.status as user_status
             FROM riders r
             INNER JOIN users u ON r.user_id = u.id
             WHERE r.id = ?",
            [$id]
        );
    }

    /**
     * Get rider's assigned orders
     */
    public function getOrders($riderId, $status = null) {
        $query = "SELECT o.*,
                         c.name as customer_full_name,
                         c.phone as customer_phone
                  FROM orders o
                  INNER JOIN customers c ON o.customer_id = c.id
                  WHERE o.rider_id = ?";
        $params = [$riderId];

        if ($status) {
            $query .= " AND o.status = ?";
            $params[] = $status;
        }

        $query .= " ORDER BY o.created_at ASC";

        return $this->db->select($query, $params);
    }

    /**
     * Get rider's today deliveries
     */
    public function getTodayDeliveries($riderId) {
        return $this->db->select(
            "SELECT o.*,
                    c.name as customer_full_name,
                    c.phone as customer_phone,
                    c.address as customer_address
             FROM orders o
             INNER JOIN customers c ON o.customer_id = c.id
             WHERE o.rider_id = ? AND DATE(o.created_at) = CURDATE()
             ORDER BY o.created_at ASC",
            [$riderId]
        );
    }

    /**
     * Get rider's active deliveries
     */
    public function getActiveDeliveries($riderId) {
        return $this->db->select(
            "SELECT o.*,
                    c.name as customer_full_name,
                    c.phone as customer_phone,
                    c.address as customer_address
             FROM orders o
             INNER JOIN customers c ON o.customer_id = c.id
             WHERE o.rider_id = ? AND o.status IN ('out_for_delivery')
             ORDER BY o.created_at ASC",
            [$riderId]
        );
    }

    /**
     * Get rider statistics
     */
    public function getStats($riderId) {
        $today = date('Y-m-d');

        // Today's deliveries
        $todayStats = $this->db->selectOne(
            "SELECT
                COUNT(*) as total_deliveries,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'out_for_delivery' THEN 1 ELSE 0 END) as in_progress
             FROM orders
             WHERE rider_id = ? AND DATE(created_at) = ?",
            [$riderId, $today]
        );

        // All time stats
        $allTimeStats = $this->db->selectOne(
            "SELECT
                COUNT(*) as total_deliveries,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed
             FROM orders
             WHERE rider_id = ?",
            [$riderId]
        );

        // This week's completed deliveries
        $weekStats = $this->db->selectOne(
            "SELECT COUNT(*) as count
             FROM orders
             WHERE rider_id = ? AND status = 'delivered'
             AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)",
            [$riderId]
        );

        return [
            'today' => $todayStats,
            'all_time' => $allTimeStats,
            'week_completed' => $weekStats['count'] ?? 0
        ];
    }

    /**
     * Update rider availability
     */
    public function setAvailability($riderId, $isAvailable) {
        $affected = $this->db->update(
            "UPDATE riders SET is_available = ? WHERE id = ?",
            [$isAvailable ? 1 : 0, $riderId]
        );

        return ['success' => $affected > 0];
    }

    /**
     * Update rider profile
     */
    public function update($riderId, $data) {
        $fields = [];
        $params = [];

        if (isset($data['vehicle_type'])) {
            $fields[] = "vehicle_type = ?";
            $params[] = $data['vehicle_type'];
        }
        if (isset($data['plate_number'])) {
            $fields[] = "plate_number = ?";
            $params[] = $data['plate_number'];
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $params[] = $riderId;
        $query = "UPDATE riders SET " . implode(', ', $fields) . " WHERE id = ?";

        $affected = $this->db->update($query, $params);

        return [
            'success' => $affected > 0,
            'message' => $affected > 0 ? 'Rider updated successfully' : 'No changes made'
        ];
    }

    /**
     * Update delivery count after successful delivery
     */
    public function incrementDeliveryCount($riderId) {
        $this->db->execute(
            "UPDATE riders SET total_deliveries = total_deliveries + 1 WHERE id = ?",
            [$riderId]
        );
    }

    /**
     * Get available riders
     */
    public function getAvailableRiders() {
        return $this->db->select(
            "SELECT r.id, r.user_id, u.name, u.phone, r.vehicle_type, r.plate_number
             FROM riders r
             INNER JOIN users u ON r.user_id = u.id
             WHERE r.is_available = 1 AND u.status = 'active'
             ORDER BY r.total_deliveries ASC",
            []
        );
    }

    /**
     * Get top riders
     */
    public function getTopRiders($limit = 5) {
        return $this->db->select(
            "SELECT r.id, r.user_id, u.name, u.phone, r.total_deliveries, r.rating
             FROM riders r
             INNER JOIN users u ON r.user_id = u.id
             WHERE u.status = 'active'
             ORDER BY r.total_deliveries DESC
             LIMIT ?",
            [$limit]
        );
    }
}
