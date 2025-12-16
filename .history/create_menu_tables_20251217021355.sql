-- Step 1: Drop foreign key constraint from cart_items
ALTER TABLE `cart_items` DROP FOREIGN KEY `cart_items_ibfk_2`;

-- Step 2: Change cart_items to reference 'id' instead of 'product_id'
ALTER TABLE `cart_items` CHANGE COLUMN `product_id` `product_id` INT NOT NULL;

-- Step 3: Drop old tables
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;

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

-- Step 6: Recreate foreign key in cart_items (now pointing to products.id)
ALTER TABLE `cart_items` 
ADD CONSTRAINT `cart_items_ibfk_2` 
FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

-- Step 7: Create indexes
CREATE INDEX `idx_product_category` ON `products`(`category_id`);
CREATE INDEX `idx_category_slug` ON `categories`(`slug`);
CREATE INDEX `idx_product_slug` ON `products`(`slug`);

-- Verify
SHOW TABLES LIKE 'categories';
SHOW TABLES LIKE 'products';
DESC products;
DESC categories;
