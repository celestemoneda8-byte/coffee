-- Create app_settings table for storing application configuration
CREATE TABLE IF NOT EXISTS app_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings if they don't exist
INSERT INTO app_settings (setting_key, setting_value) VALUES 
('website_name', 'EXpresso Caffe'),
('admin_primary_color', '#7f5539'),
('admin_secondary_color', '#7b6a58'),
('admin_accent_color', '#dec0ad'),
('customer_primary_color', '#7f5539'),
('customer_secondary_color', '#7b6a58'),
('customer_accent_color', '#dec0ad'),
('rider_primary_color', '#7f5539'),
('rider_secondary_color', '#7b6a58'),
('rider_accent_color', '#dec0ad')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
