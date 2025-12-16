-- Fix orders table to match the admin orders.php page expectations
ALTER TABLE `orders` 
ADD COLUMN `id` INT AUTO_INCREMENT UNIQUE NOT NULL AFTER `order_id`,
ADD COLUMN `customer_name` VARCHAR(255) NOT NULL DEFAULT 'Guest' AFTER `customer_id`,
ADD COLUMN `customer_email` VARCHAR(255) NOT NULL DEFAULT '' AFTER `customer_name`,
ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending' AFTER `order_status`,
ADD COLUMN `payment_status` VARCHAR(50) NOT NULL DEFAULT 'unpaid' AFTER `status`;

-- Show updated table structure
SHOW COLUMNS FROM orders;
