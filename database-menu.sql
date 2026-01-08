-- Menu Items Table Addition
-- Run this to add the menu functionality

USE `foodsys`;

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

-- Insert sample menu items (Nigerian dishes)
INSERT INTO `menu_items` (`name`, `description`, `price`, `category`, `is_available`, `is_featured`, `preparation_time`, `spice_level`, `tags`) VALUES
('Jollof Rice with Chicken', 'Classic Nigerian jollof rice served with grilled chicken thigh', 2500.00, 'Rice', 1, 1, 25, 'medium', 'popular, nigerian'),
('Fried Rice with Chicken', 'Colorful vegetable fried rice served with grilled chicken', 2500.00, 'Rice', 1, 0, 25, 'mild', 'popular'),
('Coconut Fried Rice', 'Fried rice cooked in coconut milk with vegetables and chicken', 2800.00, 'Rice', 1, 1, 30, 'mild', 'special'),
('Jollof Spaghetti', 'Jollof rice style spaghetti with beef or chicken', 2200.00, 'Pasta', 1, 0, 20, 'medium', 'popular'),
('Egusi Soup with Eba', 'Rich melon seed soup with assorted meat and pounded yam or eba', 3000.00, 'Soups', 1, 1, 35, 'medium', 'traditional, nigerian'),
('Efo Riro with Semo', 'Nigerian vegetable soup with stockfish, beef, and semolina', 2800.00, 'Soups', 1, 0, 30, 'medium', 'traditional'),
('Bangun Soup with Fufu', 'Groundnut soup with beef and fufu', 2800.00, 'Soups', 1, 0, 35, 'mild', 'traditional'),
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
('Toast Bread and Egg', 'French toast with scrambled eggs and sausage', 1200.00, 'Breakfast', 1, 0, 10, 'mild', 'breakfast');
