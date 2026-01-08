<?php
/**
 * PaymentSettings Class
 * Manages payment configuration settings (bank transfer, POS, etc.)
 */

class PaymentSettings {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get a setting value by key
     */
    public function get($key, $default = null) {
        $result = $this->db->select(
            "SELECT setting_value, setting_type FROM payment_settings WHERE setting_key = ?",
            [$key]
        );

        if (!empty($result)) {
            $value = $result[0]['setting_value'];
            if ($result[0]['setting_type'] === 'json' && $value) {
                return json_decode($value, true);
            }
            return $value;
        }

        return $default;
    }

    /**
     * Get all payment settings
     */
    public function getAll() {
        $results = $this->db->select("SELECT * FROM payment_settings ORDER BY setting_key");

        $settings = [];
        foreach ($results as $row) {
            $value = $row['setting_value'];
            if ($row['setting_type'] === 'json' && $value) {
                $value = json_decode($value, true);
            }
            $settings[$row['setting_key']] = [
                'value' => $value,
                'type' => $row['setting_type']
            ];
        }

        return $settings;
    }

    /**
     * Set a setting value
     */
    public function set($key, $value, $type = 'text') {
        if ($type === 'json' && is_array($value)) {
            $value = json_encode($value);
        }

        $existing = $this->db->select(
            "SELECT id FROM payment_settings WHERE setting_key = ?",
            [$key]
        );

        if (!empty($existing)) {
            return $this->db->update(
                "UPDATE payment_settings SET setting_value = ?, setting_type = ? WHERE setting_key = ?",
                [$value, $type, $key]
            );
        } else {
            return $this->db->insert(
                "INSERT INTO payment_settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)",
                [$key, $value, $type]
            );
        }
    }

    /**
     * Set multiple settings at once
     */
    public function setMultiple($data) {
        $this->db->beginTransaction();

        try {
            foreach ($data as $key => $value) {
                $type = is_array($value) ? 'json' : 'text';
                $this->set($key, $value, $type);
            }

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get payment method details for display
     */
    public function getPaymentMethodDetails($method) {
        if ($method === 'bank_transfer') {
            return [
                'enabled' => $this->get('bank_transfer_enabled', '1') === '1',
                'bank_name' => $this->get('bank_name', ''),
                'account_name' => $this->get('account_name', ''),
                'account_number' => $this->get('account_number', ''),
                'instructions' => $this->get('bank_instructions', 'Please transfer to complete your order.')
            ];
        }

        if ($method === 'pos') {
            return [
                'enabled' => $this->get('pos_enabled', '1') === '1',
                'bank_name' => $this->get('bank_name', ''),
                'account_name' => $this->get('account_name', ''),
                'account_number' => $this->get('account_number', ''),
                'instructions' => $this->get('pos_instructions', 'Pay via POS terminal to our bank account.')
            ];
        }

        return null;
    }

    /**
     * Check if a payment method is enabled
     */
    public function isEnabled($method) {
        $key = $method . '_enabled';
        return $this->get($key, '1') === '1';
    }
}
