-- Step 1: Check what constraints exist
-- (informational only)

-- Step 2: Drop foreign key constraint from cart_items if it exists
SET FOREIGN_KEY_CHECKS = 0;
ALTER TABLE `cart_items` DROP FOREIGN KEY IF EXISTS `cart_items_ibfk_2`;

-- Step 3: Drop old tables
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Step 4: Create new categories table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 5: Create new products table
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `description` LONGTEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `category_id` INT NULL,
  `image` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 6: Create indexes
CREATE INDEX `idx_product_category` ON `products`(`category_id`);
CREATE INDEX `idx_category_slug` ON `categories`(`slug`);
CREATE INDEX `idx_product_slug` ON `products`(`slug`);

-- Verify
DESC products;
DESC categories;
