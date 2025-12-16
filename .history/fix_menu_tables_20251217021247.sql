-- Fix categories and products tables to match the application code expectations

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist with the old schema
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create categories table with correct structure
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create products table with correct structure
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `description` LONGTEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `category_id` INT,
  `image` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better performance
CREATE INDEX `idx_product_category` ON `products`(`category_id`);
CREATE INDEX `idx_category_slug` ON `categories`(`slug`);
CREATE INDEX `idx_product_slug` ON `products`(`slug`);
