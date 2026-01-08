-- Home-Cooked Meal Delivery Management System Database Schema
-- Created: 2026-01-06

CREATE DATABASE IF NOT EXISTS `foodsys` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `foodsys`;

-- =====================================================
-- USERS TABLE (Admin, Staff, Riders)
-- =====================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff', 'rider') NOT NULL DEFAULT 'staff',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CUSTOMERS TABLE (Mini CRM)
-- =====================================================
CREATE TABLE `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `preferences` TEXT DEFAULT NULL COMMENT 'JSON: dietary preferences, spice level, etc.',
  `total_orders` INT UNSIGNED DEFAULT 0,
  `total_spent` DECIMAL(10,2) DEFAULT 0.00,
  `last_order_date` DATE DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_phone` (`phone`),
  KEY `idx_name` (`name`),
  KEY `idx_total_orders` (`total_orders`),
  KEY `idx_total_spent` (`total_spent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- RIDERS TABLE (Extended rider information)
-- =====================================================
CREATE TABLE `riders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `vehicle_type` VARCHAR(50) DEFAULT NULL COMMENT 'motorcycle, bicycle, car',
  `plate_number` VARCHAR(20) DEFAULT NULL,
  `is_available` TINYINT(1) DEFAULT 1,
  `total_deliveries` INT UNSIGNED DEFAULT 0,
  `rating` DECIMAL(3,2) DEFAULT 5.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  KEY `idx_available` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ORDERS TABLE
-- =====================================================
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(20) NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `delivery_address` TEXT NOT NULL,
  `status` ENUM('pending', 'preparing', 'out_for_delivery', 'delivered', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
  `rider_id` INT UNSIGNED DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('bank_transfer', 'pos', 'cash_on_delivery') NOT NULL DEFAULT 'cash_on_delivery',
  `payment_status` ENUM('paid', 'pending', 'failed') NOT NULL DEFAULT 'pending',
  `special_instructions` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `delivered_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_number` (`order_number`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_rider` (`rider_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`rider_id`) REFERENCES `riders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ORDER ITEMS TABLE
-- =====================================================
CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `item_name` VARCHAR(200) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(10,2) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PAYMENTS TABLE
-- =====================================================
CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('bank_transfer', 'pos', 'cash_on_delivery') NOT NULL,
  `payment_status` ENUM('paid', 'pending', 'failed') NOT NULL DEFAULT 'pending',
  `transaction_reference` VARCHAR(100) DEFAULT NULL,
  `paid_at` TIMESTAMP NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_status` (`payment_status`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATION TEMPLATES TABLE
-- =====================================================
CREATE TABLE `notification_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('order_confirmation', 'order_preparing', 'out_for_delivery', 'delivered', 'order_cancelled') NOT NULL,
  `sms_template` TEXT NOT NULL,
  `whatsapp_template` TEXT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATION LOGS TABLE
-- =====================================================
CREATE TABLE `notification_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `recipient_phone` VARCHAR(20) NOT NULL,
  `type` ENUM('order_confirmation', 'order_preparing', 'out_for_delivery', 'delivered', 'order_cancelled') NOT NULL,
  `channel` ENUM('sms', 'whatsapp') NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('sent', 'pending', 'failed') NOT NULL DEFAULT 'pending',
  `response` TEXT DEFAULT NULL,
  `sent_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) VALUES
('System Admin', 'admin@foodsys.com', '08012345678', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert default notification templates
INSERT INTO `notification_templates` (`name`, `type`, `sms_template`, `whatsapp_template`) VALUES
('Order Confirmation', 'order_confirmation',
 'Hello {customer_name}, your order #{order_number} has been received! Total: ₦{total_amount}. We will notify you when preparation starts. Thank you!',
 '🍽️ *Order Confirmation*\n\nHello {customer_name},\n\nYour order #{order_number} has been received!\n\n*Total:* ₦{total_amount}\n\nWe will notify you when preparation starts.\n\nThank you for choosing us! 🙏'),

('Order Preparing', 'order_preparing',
 'Hello {customer_name}, your order #{order_number} is now being prepared. It will be ready soon!',
 '👨‍🍳 *Order In Preparation*\n\nHello {customer_name},\n\nYour order #{order_number} is now being prepared by our kitchen team.\n\nIt will be ready soon! 🔥\n\nThank you for your patience!'),

('Out for Delivery', 'out_for_delivery',
 'Hello {customer_name}, your order #{order_number} is out for delivery! Rider: {rider_name}. Contact: {rider_phone}',
 '🚴 *Out for Delivery*\n\nHello {customer_name},\n\nYour order #{order_number} is on its way!\n\n*Rider:* {rider_name}\n\nContact: {rider_phone}\n\nExpect it shortly! 📦'),

('Order Delivered', 'delivered',
 'Hello {customer_name}, your order #{order_number} has been delivered! Enjoy your meal. Thank you for ordering with us!',
 '✅ *Order Delivered*\n\nHello {customer_name},\n\nYour order #{order_number} has been successfully delivered!\n\nEnjoy your meal! 🍲\n\nThank you for ordering with us! ❤️'),

('Order Cancelled', 'order_cancelled',
 'Hello {customer_name}, your order #{order_number} has been cancelled. Contact us for more information.',
 '❌ *Order Cancelled*\n\nHello {customer_name},\n\nYour order #{order_number} has been cancelled.\n\nPlease contact us for more information.\n\nWe apologize for any inconvenience.'));
