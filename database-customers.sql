-- Customer User Table Addition
-- Run this to add customer login functionality

USE `foodsys`;

-- Add customer role to users table
ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'staff', 'rider', 'customer') NOT NULL DEFAULT 'staff';

-- Add customer-specific fields to users (optional - using customers table as main source)
ALTER TABLE `customers` ADD COLUMN `user_id` INT UNSIGNED DEFAULT NULL AFTER `id`;
ALTER TABLE `customers` ADD UNIQUE KEY `unique_user_id` (`user_id`);
ALTER TABLE `customers` ADD CONSTRAINT `fk_customer_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Update password hash for a sample customer (password: customer123)
-- This is just for testing
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `status`) VALUES
('Test Customer', 'customer@test.com', '08099999999', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'active');
