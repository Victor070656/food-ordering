-- Home-Cooked Meal Delivery Management System - Complete Database Schema
-- Created: 2026-01-06
-- This file contains all tables and default data

-- Drop existing database if needed (uncomment to reset)
-- DROP DATABASE IF EXISTS `foodsys`;

CREATE DATABASE IF NOT EXISTS `foodsys` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `foodsys`;

-- =====================================================
-- USERS TABLE (Admin, Staff, Riders, Customer)
-- =====================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff', 'rider', 'customer') NOT NULL DEFAULT 'staff',
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
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
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
  UNIQUE KEY `unique_user_id` (`user_id`),
  KEY `idx_name` (`name`),
  KEY `idx_total_orders` (`total_orders`),
  KEY `idx_total_spent` (`total_spent`),
  CONSTRAINT `fk_customer_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- RIDERS TABLE (Extended rider information)
-- =====================================================
CREATE TABLE IF NOT EXISTS `riders` (
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
  KEY `idx_available` (`is_available`),
  CONSTRAINT `fk_rider_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- MENU ITEMS / FOOD ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `category` VARCHAR(100) DEFAULT 'Main',
  `image_url` VARCHAR(500) DEFAULT NULL,
  `is_available` TINYINT(1) DEFAULT 1,
  `is_featured` TINYINT(1) DEFAULT 0,
  `preparation_time` INT UNSIGNED DEFAULT 30 COMMENT 'in minutes',
  `spice_level` ENUM('none', 'mild', 'medium', 'hot') DEFAULT 'medium',
  `tags` VARCHAR(500) DEFAULT NULL COMMENT 'comma-separated tags',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_available` (`is_available`),
  KEY `idx_featured` (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ORDERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `orders` (
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
  CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_order_rider` FOREIGN KEY (`rider_id`) REFERENCES `riders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ORDER ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `order_items` (
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
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PAYMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `payments` (
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
  CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATION TEMPLATES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `notification_templates` (
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
CREATE TABLE IF NOT EXISTS `notification_logs` (
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
  CONSTRAINT `fk_notification_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) VALUES
('System Admin', 'admin@foodsys.com', '08012345678', 'admin123', 'admin', 'active')
ON DUPLICATE KEY UPDATE `email`=`email`;

-- Insert sample customer user (password: customer123)
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) VALUES
('Test Customer', 'customer@test.com', '08099999999', 'customer123', 'customer', 'active')
ON DUPLICATE KEY UPDATE `email`=`email`;

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
 '❌ *Order Cancelled*\n\nHello {customer_name},\n\nYour order #{order_number} has been cancelled.\n\nPlease contact us for more information.\n\nWe apologize for any inconvenience.')
ON DUPLICATE KEY UPDATE `type`=`type`;

-- Insert sample menu items (Nigerian dishes)
INSERT INTO `menu_items` (`name`, `description`, `price`, `category`, `is_available`, `is_featured`, `preparation_time`, `spice_level`, `tags`) VALUES
('Jollof Rice with Chicken', 'Classic Nigerian jollof rice served with grilled chicken thigh', 2500.00, 'Rice', 1, 1, 25, 'medium', 'popular, nigerian'),
('Fried Rice with Chicken', 'Colorful vegetable fried rice served with grilled chicken', 2500.00, 'Rice', 1, 0, 25, 'mild', 'popular'),
('Coconut Fried Rice', 'Fried rice cooked in coconut milk with vegetables and chicken', 2800.00, 'Rice', 1, 1, 30, 'mild', 'special'),
('Jollof Spaghetti', 'Jollof rice style spaghetti with beef or chicken', 2200.00, 'Pasta', 1, 0, 20, 'medium', 'popular'),
('Egusi Soup with Eba', 'Rich melon seed soup with assorted meat and pounded yam or eba', 3000.00, 'Soups', 1, 1, 35, 'medium', 'traditional, nigerian'),
('Efo Riro with Semo', 'Nigerian vegetable soup with stockfish, beef, and semolina', 2800.00, 'Soups', 1, 0, 30, 'medium', 'traditional'),
('Groundnut Soup with Fufu', 'Groundnut soup with beef and fufu', 2800.00, 'Soups', 1, 0, 35, 'mild', 'traditional'),
('Beans and Plantain (Dodo)', 'Porridge beans with ripe fried plantain', 2000.00, 'Beans', 1, 1, 25, 'mild', 'popular'),
('Moi Moi', 'Steamed bean pudding with fish, egg, or crayfish', 800.00, 'Sides', 1, 0, 20, 'mild', 'snack'),
('Yam and Egg Sauce', 'Boiled yam served with savory egg sauce', 1800.00, 'Yam', 1, 0, 15, 'mild', 'breakfast'),
('Bole and Fish', 'Roasted plantain with pepper sauce and grilled fish', 2500.00, 'Grills', 1, 1, 20, 'hot', 'port-harcourt'),
('Suya (Spiced Meat)', 'Thinly sliced grilled beef coated with spicy peanut mix', 1500.00, 'Grills', 1, 0, 15, 'hot', 'snack, spicy'),
('Chicken Wings (6pcs)', 'Crispy fried chicken wings with sauce', 2000.00, 'Chicken', 1, 0, 20, 'medium', 'snack'),
('Grilled Chicken Quarter', 'Marinated and grilled chicken quarter with spices', 2500.00, 'Chicken', 1, 1, 25, 'medium', 'protein'),
('Beef Suya Wrap', 'Suya beef wrapped in warm tortilla with vegetables', 1800.00, 'Wraps', 1, 0, 15, 'hot', 'fast-food'),
('Plantain Chips (Small)', 'Crispy fried plantain chips', 500.00, 'Sides', 1, 0, 10, 'none', 'snack'),
('Fresh Fruit Juice', 'Freshly squeezed fruit juice (choice of flavor)', 800.00, 'Drinks', 1, 0, 5, 'none', 'drink'),
('Bottled Water', '500ml bottled water', 200.00, 'Drinks', 1, 0, 1, 'none', 'drink'),
('Soft Drink', 'Choice of Coke, Fanta, Sprite, or Schweppes', 400.00, 'Drinks', 1, 0, 1, 'none', 'drink'),
('Pap (Akamu) with Akara', 'Corn pap with bean cake', 1000.00, 'Breakfast', 1, 0, 20, 'none', 'traditional'),
('Toast Bread and Egg', 'French toast with scrambled eggs and sausage', 1200.00, 'Breakfast', 1, 0, 10, 'mild', 'breakfast')
ON DUPLICATE KEY UPDATE `name`=`name`;
