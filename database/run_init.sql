-- Complete PostgreSQL Database Initialization
-- This script contains all necessary setup without external file references

-- Create database
CREATE DATABASE household_connect;

-- Connect to the new database
\c household_connect;

-- Create enum types
CREATE TYPE user_role AS ENUM ('employer', 'worker', 'admin');
CREATE TYPE user_status AS ENUM ('active', 'inactive', 'suspended');
CREATE TYPE worker_type AS ENUM ('cleaning', 'cooking', 'childcare', 'eldercare', 'gardening', 'other');
CREATE TYPE worker_status AS ENUM ('active', 'inactive', 'pending_verification');
CREATE TYPE job_status AS ENUM ('active', 'filled', 'closed');
CREATE TYPE booking_status AS ENUM ('pending', 'confirmed', 'in_progress', 'completed', 'cancelled');
CREATE TYPE payment_status AS ENUM ('pending', 'paid', 'refunded');
CREATE TYPE review_status AS ENUM ('approved', 'pending', 'rejected');
CREATE TYPE day_of_week AS ENUM ('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
CREATE TYPE payment_method_type AS ENUM ('mobile_money', 'bank_transfer', 'card');
CREATE TYPE payment_method_status AS ENUM ('active', 'inactive');
CREATE TYPE transaction_type AS ENUM ('payment', 'refund', 'withdrawal');
CREATE TYPE transaction_status AS ENUM ('pending', 'completed', 'failed', 'cancelled');
CREATE TYPE notification_type AS ENUM ('booking', 'message', 'review', 'system');
CREATE TYPE setting_type_enum AS ENUM ('string', 'number', 'boolean', 'json');
CREATE TYPE announcement_type AS ENUM ('info', 'warning', 'success', 'error');
CREATE TYPE target_audience_enum AS ENUM ('all', 'employers', 'workers', 'admins');

-- Create trigger function for updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role user_role NOT NULL DEFAULT 'employer',
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    status user_status DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Workers table
CREATE TABLE workers (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type worker_type NOT NULL,
    experience_years INT DEFAULT 0,
    hourly_rate DECIMAL(10,2),
    location VARCHAR(100),
    availability TEXT,
    skills TEXT,
    education TEXT,
    languages VARCHAR(255),
    certifications TEXT,
    rating DECIMAL(3,2) DEFAULT 0,
    review_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    profile_image VARCHAR(255),
    status worker_status DEFAULT 'pending_verification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TRIGGER update_workers_updated_at BEFORE UPDATE ON workers
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Jobs table
CREATE TABLE jobs (
    id SERIAL PRIMARY KEY,
    employer_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    type worker_type NOT NULL,
    salary DECIMAL(10,2),
    location VARCHAR(100),
    work_hours VARCHAR(100),
    requirements TEXT,
    status job_status DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TRIGGER update_jobs_updated_at BEFORE UPDATE ON jobs
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Bookings table
CREATE TABLE bookings (
    id SERIAL PRIMARY KEY,
    worker_id INT NOT NULL,
    user_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    service_type worker_type NOT NULL,
    status booking_status DEFAULT 'pending',
    payment_status payment_status DEFAULT 'pending',
    total_amount DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TRIGGER update_bookings_updated_at BEFORE UPDATE ON bookings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Reviews table
CREATE TABLE reviews (
    id SERIAL PRIMARY KEY,
    worker_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    status review_status DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (worker_id, user_id)
);

CREATE TRIGGER update_reviews_updated_at BEFORE UPDATE ON reviews
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Messages table
CREATE TABLE messages (
    id SERIAL PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_deleted_by_sender BOOLEAN DEFAULT FALSE,
    is_deleted_by_recipient BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Categories table
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Worker categories junction table
CREATE TABLE worker_categories (
    id SERIAL PRIMARY KEY,
    worker_id INT NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE (worker_id, category_id)
);

-- Availability table
CREATE TABLE availability (
    id SERIAL PRIMARY KEY,
    worker_id INT NOT NULL,
    day_of_week day_of_week NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    notes TEXT,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
);

-- Payment methods table
CREATE TABLE payment_methods (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    type payment_method_type NOT NULL,
    details TEXT NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    status payment_method_status DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Transactions table
CREATE TABLE transactions (
    id SERIAL PRIMARY KEY,
    booking_id INT,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type transaction_type NOT NULL,
    status transaction_status DEFAULT 'pending',
    payment_method_id INT,
    transaction_id VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL
);

-- Notifications table
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type notification_type NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT,
    related_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Admin tables
CREATE TABLE admin_logs (
    id SERIAL PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSONB,
    new_values JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE admin_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type setting_type_enum DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER update_admin_settings_updated_at BEFORE UPDATE ON admin_settings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TABLE system_announcements (
    id SERIAL PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type announcement_type DEFAULT 'info',
    is_active BOOLEAN DEFAULT TRUE,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    target_audience target_audience_enum DEFAULT 'all',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TRIGGER update_system_announcements_updated_at BEFORE UPDATE ON system_announcements
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Create indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_workers_user_id ON workers(user_id);
CREATE INDEX idx_workers_type ON workers(type);
CREATE INDEX idx_workers_location ON workers(location);
CREATE INDEX idx_jobs_employer_id ON jobs(employer_id);
CREATE INDEX idx_jobs_status ON jobs(status);
CREATE INDEX idx_bookings_worker_id ON bookings(worker_id);
CREATE INDEX idx_bookings_user_id ON bookings(user_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_reviews_worker_id ON reviews(worker_id);
CREATE INDEX idx_reviews_user_id ON reviews(user_id);
CREATE INDEX idx_messages_sender_id ON messages(sender_id);
CREATE INDEX idx_messages_recipient_id ON messages(recipient_id);
CREATE INDEX idx_messages_is_read ON messages(is_read);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
CREATE INDEX idx_availability_worker_id ON availability(worker_id);
CREATE INDEX idx_payment_methods_user_id ON payment_methods(user_id);
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_booking_id ON transactions(booking_id);
CREATE INDEX idx_users_admin_role ON users(role) WHERE role = 'admin';
CREATE INDEX idx_admin_logs_admin_id ON admin_logs(admin_id);
CREATE INDEX idx_admin_logs_action ON admin_logs(action);
CREATE INDEX idx_admin_logs_created_at ON admin_logs(created_at);
CREATE INDEX idx_admin_settings_key ON admin_settings(setting_key);
CREATE INDEX idx_system_announcements_active ON system_announcements(is_active);
CREATE INDEX idx_system_announcements_dates ON system_announcements(start_date, end_date);

-- Insert default data
INSERT INTO categories (name, description, icon) VALUES
('Cleaning', 'General house cleaning services', 'broom'),
('Cooking', 'Meal preparation and cooking', 'utensils'),
('Childcare', 'Child care and babysitting', 'child'),
('Eldercare', 'Care for elderly family members', 'heart'),
('Gardening', 'Garden maintenance and landscaping', 'leaf'),
('Laundry', 'Washing and ironing clothes', 'tint'),
('Pet Care', 'Pet sitting and care', 'paw');

-- Create admin user
INSERT INTO users (name, email, password, role, is_verified, status) 
VALUES (
    'System Administrator', 
    'admin@householdconnect.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'admin', 
    TRUE, 
    'active'
) ON CONFLICT (email) DO NOTHING;

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
('contact_email', 'support@householdconnect.com', 'string', 'Contact email for support', TRUE)
ON CONFLICT (setting_key) DO NOTHING;

-- Create admin stats view
CREATE OR REPLACE VIEW admin_stats AS
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

-- Create application user
CREATE USER household_app WITH PASSWORD 'Jesuslove@12';
GRANT CONNECT ON DATABASE household_connect TO household_app;
GRANT USAGE ON SCHEMA public TO household_app;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO household_app;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO household_app;

-- Set default permissions for future tables
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO household_app;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO household_app;
