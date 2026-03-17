-- Mobile API Database Schema
-- This extends the existing database schema

-- API tokens table
CREATE TABLE IF NOT EXISTS api_tokens (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    device_info JSONB,
    app_version VARCHAR(20),
    expires_at TIMESTAMP NOT NULL,
    last_used TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Rate limiting table
CREATE TABLE IF NOT EXISTS rate_limits (
    id SERIAL PRIMARY KEY,
    ip_address INET NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Push notification tokens table
CREATE TABLE IF NOT EXISTS push_tokens (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    platform VARCHAR(20) NOT NULL, -- 'ios', 'android'
    device_info JSONB,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, token)
);

-- Create trigger for updated_at
CREATE TRIGGER update_push_tokens_updated_at BEFORE UPDATE ON push_tokens
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Mobile app sessions table
CREATE TABLE IF NOT EXISTS mobile_sessions (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    device_info JSONB,
    app_version VARCHAR(20),
    platform VARCHAR(20),
    ip_address INET,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- API usage logs table
CREATE TABLE IF NOT EXISTS api_usage_logs (
    id SERIAL PRIMARY KEY,
    user_id INT,
    token_id INT,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address INET,
    user_agent TEXT,
    response_code INT,
    response_time INT, -- in milliseconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (token_id) REFERENCES api_tokens(id) ON DELETE SET NULL
);

-- Mobile app settings table
CREATE TABLE IF NOT EXISTS mobile_app_settings (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'string', -- 'string', 'number', 'boolean', 'json'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, setting_key)
);

-- Create trigger for updated_at
CREATE TRIGGER update_mobile_app_settings_updated_at BEFORE UPDATE ON mobile_app_settings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Offline sync table
CREATE TABLE IF NOT EXISTS offline_sync (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    action VARCHAR(20) NOT NULL, -- 'create', 'update', 'delete'
    data JSONB,
    sync_status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'synced', 'failed'
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    synced_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Mobile app crash reports table
CREATE TABLE IF NOT EXISTS crash_reports (
    id SERIAL PRIMARY KEY,
    user_id INT,
    app_version VARCHAR(20),
    platform VARCHAR(20),
    device_info JSONB,
    error_message TEXT NOT NULL,
    stack_trace TEXT,
    user_action TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Feature usage tracking table
CREATE TABLE IF NOT EXISTS feature_usage (
    id SERIAL PRIMARY KEY,
    user_id INT,
    feature_name VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    metadata JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_api_tokens_user_id ON api_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_api_tokens_token ON api_tokens(token);
CREATE INDEX IF NOT EXISTS idx_api_tokens_expires_at ON api_tokens(expires_at);
CREATE INDEX IF NOT EXISTS idx_rate_limits_ip_address ON rate_limits(ip_address);
CREATE INDEX IF NOT EXISTS idx_rate_limits_created_at ON rate_limits(created_at);
CREATE INDEX IF NOT EXISTS idx_push_tokens_user_id ON push_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_push_tokens_token ON push_tokens(token);
CREATE INDEX IF NOT EXISTS idx_mobile_sessions_user_id ON mobile_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_mobile_sessions_token ON mobile_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_mobile_sessions_expires_at ON mobile_sessions(expires_at);
CREATE INDEX IF NOT EXISTS idx_api_usage_logs_user_id ON api_usage_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_api_usage_logs_token_id ON api_usage_logs(token_id);
CREATE INDEX IF NOT EXISTS idx_api_usage_logs_created_at ON api_usage_logs(created_at);
CREATE INDEX IF NOT EXISTS idx_mobile_app_settings_user_id ON mobile_app_settings(user_id);
CREATE INDEX IF NOT EXISTS idx_offline_sync_user_id ON offline_sync(user_id);
CREATE INDEX IF NOT EXISTS idx_offline_sync_sync_status ON offline_sync(sync_status);
CREATE INDEX IF NOT EXISTS idx_crash_reports_user_id ON crash_reports(user_id);
CREATE INDEX IF NOT EXISTS idx_crash_reports_created_at ON crash_reports(created_at);
CREATE INDEX IF NOT EXISTS idx_feature_usage_user_id ON feature_usage(user_id);
CREATE INDEX IF NOT EXISTS idx_feature_usage_feature_name ON feature_usage(feature_name);

-- Insert default mobile app settings for existing users
INSERT INTO mobile_app_settings (user_id, setting_key, setting_value, setting_type)
SELECT 
    id,
    unnest(ARRAY['notifications_enabled', 'auto_sync', 'dark_mode', 'language']),
    unnest(ARRAY['true', 'true', 'false', 'en']),
    unnest(ARRAY['boolean', 'boolean', 'boolean', 'string'])
FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM mobile_app_settings mas 
    WHERE mas.user_id = users.id AND mas.setting_key IN ('notifications_enabled', 'auto_sync', 'dark_mode', 'language')
    LIMIT 1
);

-- Function to clean up expired tokens
CREATE OR REPLACE FUNCTION cleanup_expired_tokens()
RETURNS void AS $$
BEGIN
    DELETE FROM api_tokens WHERE expires_at < NOW();
    DELETE FROM mobile_sessions WHERE expires_at < NOW();
END;
$$ LANGUAGE plpgsql;

-- Function to clean up old rate limit records
CREATE OR REPLACE FUNCTION cleanup_rate_limits()
RETURNS void AS $$
BEGIN
    DELETE FROM rate_limits WHERE created_at < NOW() - INTERVAL '24 hours';
END;
$$ LANGUAGE plpgsql;

-- Function to log API usage
CREATE OR REPLACE FUNCTION log_api_usage(
    p_user_id INT,
    p_token_id INT,
    p_endpoint VARCHAR(255),
    p_method VARCHAR(10),
    p_ip_address INET,
    p_user_agent TEXT,
    p_response_code INT,
    p_response_time INT
)
RETURNS void AS $$
BEGIN
    INSERT INTO api_usage_logs (user_id, token_id, endpoint, method, ip_address, user_agent, response_code, response_time)
    VALUES (p_user_id, p_token_id, p_endpoint, p_method, p_ip_address, p_user_agent, p_response_code, p_response_time);
END;
$$ LANGUAGE plpgsql;

-- View for API statistics
CREATE OR REPLACE VIEW api_statistics AS
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_requests,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT ip_address) as unique_ips,
    AVG(response_time) as avg_response_time,
    COUNT(CASE WHEN response_code >= 400 THEN 1 END) as error_requests,
    ROUND(COUNT(CASE WHEN response_code >= 400 THEN 1 END) * 100.0 / COUNT(*), 2) as error_rate
FROM api_usage_logs
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- View for mobile app usage statistics
CREATE OR REPLACE VIEW mobile_app_stats AS
SELECT 
    COUNT(DISTINCT user_id) as active_users,
    COUNT(DISTINCT token_id) as active_tokens,
    COUNT(DISTINCT ip_address) as unique_ips,
    COUNT(*) as total_requests,
    DATE(created_at) as date
FROM api_usage_logs
WHERE created_at >= CURRENT_DATE - INTERVAL '7 days'
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Scheduled jobs (requires pg_cron extension)
-- SELECT cron.schedule('cleanup-expired-tokens', '0 */6 * * *', 'SELECT cleanup_expired_tokens();');
-- SELECT cron.schedule('cleanup-rate-limits', '0 2 * * *', 'SELECT cleanup_rate_limits();');

-- API version tracking
CREATE TABLE IF NOT EXISTS api_versions (
    id SERIAL PRIMARY KEY,
    version VARCHAR(20) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    deprecated_at TIMESTAMP,
    sunset_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert current API version
INSERT INTO api_versions (version, description) VALUES
('v1', 'Initial mobile API version')
ON CONFLICT (version) DO NOTHING;
