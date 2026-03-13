-- Admin Role Setup for Household Connect
-- This script adds admin role support to the existing database

-- Update users table to include admin role
ALTER TABLE users 
MODIFY COLUMN role ENUM('employer', 'worker', 'admin') NOT NULL DEFAULT 'employer';

-- Create admin user (password: admin123)
-- In production, you should change this password immediately
INSERT INTO users (name, email, password, role, is_verified, status) 
VALUES (
    'System Administrator', 
    'admin@householdconnect.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'admin', 
    TRUE, 
    'active'
);

-- Add admin-specific indexes for better performance
CREATE INDEX idx_users_admin_role ON users(role) WHERE role = 'admin';

-- Create admin_logs table for tracking admin activities
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create admin_settings table for system configuration
CREATE TABLE admin_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin settings
INSERT INTO admin_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'Household Connect', 'string', 'Site name displayed in header', TRUE),
('site_description', 'Kigali Household Worker Platform', 'string', 'Site description for SEO', TRUE),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode', FALSE),
('max_workers_per_employer', '10', 'number', 'Maximum workers an employer can hire', FALSE),
('auto_approve_workers', 'false', 'boolean', 'Automatically approve new worker registrations', FALSE),
('platform_fee_percentage', '5', 'number', 'Platform fee percentage charged on transactions', FALSE),
('min_booking_amount', '1000', 'number', 'Minimum booking amount in RWF', FALSE),
('max_booking_amount', '1000000', 'number', 'Maximum booking amount in RWF', FALSE),
('enable_notifications', 'true', 'boolean', 'Enable email notifications', FALSE),
('contact_email', 'support@householdconnect.com', 'string', 'Contact email for support', TRUE);

-- Create system_announcements table for admin announcements
CREATE TABLE system_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_active BOOLEAN DEFAULT TRUE,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    target_audience ENUM('all', 'employers', 'workers', 'admins') DEFAULT 'all',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for admin tables
CREATE INDEX idx_admin_logs_admin_id ON admin_logs(admin_id);
CREATE INDEX idx_admin_logs_action ON admin_logs(action);
CREATE INDEX idx_admin_logs_created_at ON admin_logs(created_at);
CREATE INDEX idx_admin_settings_key ON admin_settings(setting_key);
CREATE INDEX idx_system_announcements_active ON system_announcements(is_active);
CREATE INDEX idx_system_announcements_dates ON system_announcements(start_date, end_date);

-- Create a view for admin dashboard statistics
CREATE VIEW admin_stats AS
SELECT 
    'total_users' as stat_name,
    COUNT(*) as stat_value,
    (SELECT COUNT(*) FROM users WHERE role = 'worker') as workers_count,
    (SELECT COUNT(*) FROM users WHERE role = 'employer') as employers_count,
    (SELECT COUNT(*) FROM jobs WHERE status = 'active') as active_jobs_count,
    (SELECT COUNT(*) FROM bookings WHERE status = 'pending') as pending_bookings_count,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'completed') as total_revenue,
    (SELECT COUNT(*) FROM workers WHERE status = 'pending_verification') as pending_verifications,
    (SELECT COUNT(*) FROM reviews) as total_reviews
FROM users;

-- Grant necessary permissions (if using MySQL with specific users)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON household_connect.* TO 'admin_user'@'localhost';
