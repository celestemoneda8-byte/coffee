-- Add missing columns to orders table if they don't exist
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `customer_name` VARCHAR(255) NOT NULL DEFAULT 'Guest' AFTER `customer_id`,
ADD COLUMN IF NOT EXISTS `customer_email` VARCHAR(255) NOT NULL DEFAULT '' AFTER `customer_name`,
ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) NOT NULL DEFAULT 'pending' AFTER `order_status`,
ADD COLUMN IF NOT EXISTS `payment_status` VARCHAR(50) NOT NULL DEFAULT 'unpaid' AFTER `status`;
