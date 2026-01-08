<?php
/**
 * Menu Item Class
 * Handles food menu management
 */

class MenuItem {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all menu items with filters
     */
    public function getAll($filters = []) {
        $where = "WHERE 1=1";
        $params = [];

        if (isset($filters['available'])) {
            $where .= " AND is_available = ?";
            $params[] = $filters['available'] ? 1 : 0;
        }

        if (isset($filters['featured'])) {
            $where .= " AND is_featured = ?";
            $params[] = $filters['featured'] ? 1 : 0;
        }

        if (!empty($filters['category'])) {
            $where .= " AND category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $where .= " AND (name LIKE ? OR description LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['spice_level'])) {
            $where .= " AND spice_level = ?";
            $params[] = $filters['spice_level'];
        }

        $orderBy = $filters['order_by'] ?? 'category ASC, name ASC';

        return $this->db->select(
            "SELECT * FROM menu_items $where ORDER BY $orderBy",
            $params
        );
    }

    /**
     * Get menu item by ID
     */
    public function getById($id) {
        return $this->db->selectOne(
            "SELECT * FROM menu_items WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get categories
     */
    public function getCategories() {
        $result = $this->db->select(
            "SELECT DISTINCT category FROM menu_items WHERE is_available = 1 ORDER BY category ASC"
        );

        return array_column($result, 'category');
    }

    /**
     * Get featured items
     */
    public function getFeatured($limit = 8) {
        return $this->db->select(
            "SELECT * FROM menu_items WHERE is_available = 1 AND is_featured = 1 ORDER BY RAND() LIMIT ?",
            [$limit]
        );
    }

    /**
     * Create new menu item
     */
    public function create($data) {
        $itemId = $this->db->insert(
            "INSERT INTO menu_items (name, description, price, category, image_url, is_available, is_featured, preparation_time, spice_level, tags)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['description'] ?? null,
                $data['price'],
                $data['category'] ?? 'Main',
                $data['image_url'] ?? null,
                $data['is_available'] ?? 1,
                $data['is_featured'] ?? 0,
                $data['preparation_time'] ?? 30,
                $data['spice_level'] ?? 'medium',
                $data['tags'] ?? null
            ]
        );

        if ($itemId) {
            return ['success' => true, 'item_id' => $itemId];
        }

        return ['success' => false, 'message' => 'Failed to create menu item'];
    }

    /**
     * Update menu item
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];

        $editableFields = ['name', 'description', 'price', 'category', 'image_url', 'is_available', 'is_featured', 'preparation_time', 'spice_level', 'tags'];

        foreach ($editableFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                if ($field === 'is_available' || $field === 'is_featured') {
                    $params[] = $data[$field] ? 1 : 0;
                } else {
                    $params[] = $data[$field];
                }
            }
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => 'No fields to update'];
        }

        $params[] = $id;
        $query = "UPDATE menu_items SET " . implode(', ', $fields) . " WHERE id = ?";

        $affected = $this->db->update($query, $params);

        return [
            'success' => $affected > 0,
            'message' => $affected > 0 ? 'Menu item updated successfully' : 'No changes made'
        ];
    }

    /**
     * Delete menu item
     */
    public function delete($id) {
        $affected = $this->db->delete("DELETE FROM menu_items WHERE id = ?", [$id]);

        return [
            'success' => $affected > 0,
            'message' => $affected > 0 ? 'Menu item deleted successfully' : 'Item not found'
        ];
    }

    /**
     * Toggle availability
     */
    public function toggleAvailability($id) {
        $item = $this->getById($id);
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found'];
        }

        $newStatus = !$item['is_available'];
        return $this->update($id, ['is_available' => $newStatus]);
    }

    /**
     * Toggle featured
     */
    public function toggleFeatured($id) {
        $item = $this->getById($id);
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found'];
        }

        $newStatus = !$item['is_featured'];
        return $this->update($id, ['is_featured' => $newStatus]);
    }

    /**
     * Get spice level label
     */
    public function getSpiceLabel($level) {
        $labels = [
            'none' => ['label' => 'Not Spicy', 'color' => 'bg-gray-100 text-gray-700', 'icon' => '🌶️🚫'],
            'mild' => ['label' => 'Mild', 'color' => 'bg-green-100 text-green-700', 'icon' => '🌶️'],
            'medium' => ['label' => 'Medium', 'color' => 'bg-yellow-100 text-yellow-700', 'icon' => '🌶️🌶️'],
            'hot' => ['label' => 'Hot', 'color' => 'bg-red-100 text-red-700', 'icon' => '🌶️🌶️🌶️'],
        ];

        return $labels[$level] ?? $labels['medium'];
    }
}
