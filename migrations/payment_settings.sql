-- Migration: Create payment_settings table
-- This table stores payment configuration like bank details, POS instructions, etc.

CREATE TABLE IF NOT EXISTS payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'json') DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default payment settings
INSERT INTO payment_settings (setting_key, setting_value, setting_type) VALUES
('bank_transfer_enabled', '1', 'text'),
('bank_name', '', 'text'),
('account_name', '', 'text'),
('account_number', '', 'text'),
('bank_instructions', 'Please transfer to the account above and send your receipt.', 'text'),
('pos_enabled', '1', 'text'),
('pos_instructions', 'Pay with POS at our location.', 'text')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
