-- Analytics System Database Schema
-- This extends the existing database schema

-- User sessions table for real-time analytics
CREATE TABLE IF NOT EXISTS user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    ip_address INET,
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Page views table
CREATE TABLE IF NOT EXISTS page_views (
    id SERIAL PRIMARY KEY,
    session_id VARCHAR(255),
    user_id INT,
    page_url VARCHAR(500) NOT NULL,
    page_title VARCHAR(255),
    referrer VARCHAR(500),
    load_time INT, -- in milliseconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES user_sessions(session_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- User activity logs
CREATE TABLE IF NOT EXISTS user_activity_logs (
    id SERIAL PRIMARY KEY,
    user_id INT,
    activity_type VARCHAR(50) NOT NULL, -- 'login', 'logout', 'booking_created', 'payment_made', etc.
    activity_data JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Daily statistics table (aggregated data)
CREATE TABLE IF NOT EXISTS daily_statistics (
    id SERIAL PRIMARY KEY,
    stat_date DATE UNIQUE NOT NULL,
    total_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    total_bookings INT DEFAULT 0,
    completed_bookings INT DEFAULT 0,
    cancelled_bookings INT DEFAULT 0,
    total_revenue DECIMAL(15,2) DEFAULT 0,
    total_transactions INT DEFAULT 0,
    successful_transactions INT DEFAULT 0,
    failed_transactions INT DEFAULT 0,
    page_views INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create trigger for updated_at
CREATE TRIGGER update_daily_statistics_updated_at BEFORE UPDATE ON daily_statistics
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Monthly statistics table
CREATE TABLE IF NOT EXISTS monthly_statistics (
    id SERIAL PRIMARY KEY,
    year_month VARCHAR(7) UNIQUE NOT NULL, -- YYYY-MM format
    total_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    total_bookings INT DEFAULT 0,
    completed_bookings INT DEFAULT 0,
    cancelled_bookings INT DEFAULT 0,
    total_revenue DECIMAL(15,2) DEFAULT 0,
    total_transactions INT DEFAULT 0,
    successful_transactions INT DEFAULT 0,
    failed_transactions INT DEFAULT 0,
    page_views INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    avg_session_duration DECIMAL(10,2) DEFAULT 0, -- in seconds
    bounce_rate DECIMAL(5,2) DEFAULT 0, -- in percentage
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create trigger for updated_at
CREATE TRIGGER update_monthly_statistics_updated_at BEFORE UPDATE ON monthly_statistics
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Report schedules table
CREATE TABLE IF NOT EXISTS report_schedules (
    id SERIAL PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type VARCHAR(50) NOT NULL, -- 'revenue', 'bookings', 'users', 'workers'
    frequency VARCHAR(20) NOT NULL, -- 'daily', 'weekly', 'monthly'
    recipients JSONB NOT NULL, -- array of email addresses
    filters JSONB, -- report filters
    is_active BOOLEAN DEFAULT TRUE,
    last_sent TIMESTAMP,
    next_send TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create trigger for updated_at
CREATE TRIGGER update_report_schedules_updated_at BEFORE UPDATE ON report_schedules
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Generated reports table
CREATE TABLE IF NOT EXISTS generated_reports (
    id SERIAL PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type VARCHAR(50) NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    filters JSONB,
    generated_by INT,
    status VARCHAR(20) DEFAULT 'generating', -- 'generating', 'completed', 'failed'
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Analytics settings table
CREATE TABLE IF NOT EXISTS analytics_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'string', -- 'string', 'number', 'boolean', 'json'
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create trigger for updated_at
CREATE TRIGGER update_analytics_settings_updated_at BEFORE UPDATE ON analytics_settings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_session_id ON user_sessions(session_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_last_activity ON user_sessions(last_activity);
CREATE INDEX IF NOT EXISTS idx_page_views_session_id ON page_views(session_id);
CREATE INDEX IF NOT EXISTS idx_page_views_user_id ON page_views(user_id);
CREATE INDEX IF NOT EXISTS idx_page_views_created_at ON page_views(created_at);
CREATE INDEX IF NOT EXISTS idx_user_activity_logs_user_id ON user_activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_user_activity_logs_activity_type ON user_activity_logs(activity_type);
CREATE INDEX IF NOT EXISTS idx_user_activity_logs_created_at ON user_activity_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_daily_statistics_stat_date ON daily_statistics(stat_date);
CREATE INDEX IF NOT EXISTS idx_monthly_statistics_year_month ON monthly_statistics(year_month);
CREATE INDEX IF NOT EXISTS idx_report_schedules_next_send ON report_schedules(next_send);
CREATE INDEX IF NOT EXISTS idx_generated_reports_status ON generated_reports(status);
CREATE INDEX IF NOT EXISTS idx_analytics_settings_key ON analytics_settings(setting_key);

-- Insert default analytics settings
INSERT INTO analytics_settings (setting_key, setting_value, setting_type, description) VALUES
('real_time_stats_enabled', 'true', 'boolean', 'Enable real-time statistics collection'),
('page_view_tracking', 'true', 'boolean', 'Enable page view tracking'),
('session_timeout_minutes', '30', 'number', 'Session timeout in minutes'),
('report_retention_days', '730', 'number', 'Number of days to retain generated reports'),
('auto_daily_reports', 'true', 'boolean', 'Enable automatic daily report generation'),
('auto_monthly_reports', 'true', 'boolean', 'Enable automatic monthly report generation'),
('export_enabled', 'true', 'boolean', 'Enable report export functionality'),
('max_export_rows', '100000', 'number', 'Maximum number of rows in exported reports'),
('analytics_email', 'analytics@householdconnect.com', 'string', 'Email for sending analytics reports')
ON CONFLICT (setting_key) DO NOTHING;

-- Function to update daily statistics
CREATE OR REPLACE FUNCTION update_daily_statistics(target_date DATE DEFAULT CURRENT_DATE)
RETURNS void AS $$
BEGIN
    INSERT INTO daily_statistics (stat_date, total_users, new_users, active_users, total_bookings, completed_bookings, cancelled_bookings, total_revenue, total_transactions, successful_transactions, failed_transactions, page_views, unique_visitors)
    SELECT 
        target_date,
        (SELECT COUNT(*) FROM users),
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) = target_date),
        (SELECT COUNT(DISTINCT user_id) FROM user_sessions WHERE DATE(last_activity) = target_date),
        (SELECT COUNT(*) FROM bookings),
        (SELECT COUNT(*) FROM bookings WHERE status = 'completed'),
        (SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'),
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'completed' AND DATE(created_at) = target_date),
        (SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = target_date),
        (SELECT COUNT(*) FROM transactions WHERE status = 'completed' AND DATE(created_at) = target_date),
        (SELECT COUNT(*) FROM transactions WHERE status = 'failed' AND DATE(created_at) = target_date),
        (SELECT COUNT(*) FROM page_views WHERE DATE(created_at) = target_date),
        (SELECT COUNT(DISTINCT session_id) FROM page_views WHERE DATE(created_at) = target_date)
    ON CONFLICT (stat_date) 
    DO UPDATE SET
        total_users = EXCLUDED.total_users,
        new_users = EXCLUDED.new_users,
        active_users = EXCLUDED.active_users,
        total_bookings = EXCLUDED.total_bookings,
        completed_bookings = EXCLUDED.completed_bookings,
        cancelled_bookings = EXCLUDED.cancelled_bookings,
        total_revenue = EXCLUDED.total_revenue,
        total_transactions = EXCLUDED.total_transactions,
        successful_transactions = EXCLUDED.successful_transactions,
        failed_transactions = EXCLUDED.failed_transactions,
        page_views = EXCLUDED.page_views,
        unique_visitors = EXCLUDED.unique_visitors,
        updated_at = CURRENT_TIMESTAMP;
END;
$$ LANGUAGE plpgsql;

-- Function to update monthly statistics
CREATE OR REPLACE FUNCTION update_monthly_statistics(target_year_month VARCHAR DEFAULT TO_CHAR(CURRENT_DATE, 'YYYY-MM'))
RETURNS void AS $$
BEGIN
    INSERT INTO monthly_statistics (year_month, total_users, new_users, active_users, total_bookings, completed_bookings, cancelled_bookings, total_revenue, total_transactions, successful_transactions, failed_transactions, page_views, unique_visitors, avg_session_duration, bounce_rate)
    SELECT 
        target_year_month,
        COALESCE(SUM(total_users), 0),
        COALESCE(SUM(new_users), 0),
        COALESCE(SUM(active_users), 0),
        COALESCE(SUM(total_bookings), 0),
        COALESCE(SUM(completed_bookings), 0),
        COALESCE(SUM(cancelled_bookings), 0),
        COALESCE(SUM(total_revenue), 0),
        COALESCE(SUM(total_transactions), 0),
        COALESCE(SUM(successful_transactions), 0),
        COALESCE(SUM(failed_transactions), 0),
        COALESCE(SUM(page_views), 0),
        COALESCE(SUM(unique_visitors), 0),
        0, -- avg_session_duration (calculated separately)
        0  -- bounce_rate (calculated separately)
    FROM daily_statistics
    WHERE TO_CHAR(stat_date, 'YYYY-MM') = target_year_month
    ON CONFLICT (year_month) 
    DO UPDATE SET
        total_users = EXCLUDED.total_users,
        new_users = EXCLUDED.new_users,
        active_users = EXCLUDED.active_users,
        total_bookings = EXCLUDED.total_bookings,
        completed_bookings = EXCLUDED.completed_bookings,
        cancelled_bookings = EXCLUDED.cancelled_bookings,
        total_revenue = EXCLUDED.total_revenue,
        total_transactions = EXCLUDED.total_transactions,
        successful_transactions = EXCLUDED.successful_transactions,
        failed_transactions = EXCLUDED.failed_transactions,
        page_views = EXCLUDED.page_views,
        unique_visitors = EXCLUDED.unique_visitors,
        updated_at = CURRENT_TIMESTAMP;
END;
$$ LANGUAGE plpgsql;

-- Create views for common analytics queries
CREATE OR REPLACE VIEW analytics_summary AS
SELECT 
    'users' as metric,
    COUNT(*) as total,
    COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as last_30_days,
    COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '7 days' THEN 1 END) as last_7_days,
    COUNT(CASE WHEN created_at >= CURRENT_DATE THEN 1 END) as today
FROM users
UNION ALL
SELECT 
    'bookings' as metric,
    COUNT(*) as total,
    COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as last_30_days,
    COUNT(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '7 days' THEN 1 END) as last_7_days,
    COUNT(CASE WHEN created_at >= CURRENT_DATE THEN 1 END) as today
FROM bookings
UNION ALL
SELECT 
    'revenue' as metric,
    COALESCE(SUM(amount), 0) as total,
    COALESCE(SUM(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '30 days' THEN amount END), 0) as last_30_days,
    COALESCE(SUM(CASE WHEN created_at >= CURRENT_DATE - INTERVAL '7 days' THEN amount END), 0) as last_7_days,
    COALESCE(SUM(CASE WHEN created_at >= CURRENT_DATE THEN amount END), 0) as today
FROM transactions
WHERE status = 'completed';

-- Scheduled jobs (requires pg_cron extension)
-- SELECT cron.schedule('update-daily-stats', '0 1 * * *', 'SELECT update_daily_statistics(CURRENT_DATE - INTERVAL ''1 day'');');
-- SELECT cron.schedule('update-monthly-stats', '0 2 1 * *', 'SELECT update_monthly_statistics(TO_CHAR(CURRENT_DATE - INTERVAL ''1 month'', ''YYYY-MM''));');
