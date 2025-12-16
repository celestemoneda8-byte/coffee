-- Add missing app_settings table to existing database
-- Run this if you already have the database imported but missing the app_settings table

CREATE TABLE IF NOT EXISTS `app_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(255) UNIQUE NOT NULL,
  `setting_value` LONGTEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample settings
INSERT IGNORE INTO `app_settings` (`setting_key`, `setting_value`) VALUES
('website_name', 'EXpresso Caffe'),
('website_description', 'Premium Coffee Delivery Service'),
('currency_symbol', '₱'),
('admin_theme', 'brown'),
('customer_theme', 'light'),
('rider_theme', 'modern');
