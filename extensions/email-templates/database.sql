-- Email Templates Database Schema
-- This extends the existing database schema

-- Email templates table
CREATE TABLE IF NOT EXISTS email_templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    category VARCHAR(100) NOT NULL,
    subject_template TEXT NOT NULL,
    message_template TEXT NOT NULL,
    html_template TEXT,
    variables JSONB,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create trigger for updated_at
CREATE TRIGGER update_email_templates_updated_at BEFORE UPDATE ON email_templates
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Email logs table
CREATE TABLE IF NOT EXISTS email_logs (
    id SERIAL PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500),
    data JSONB,
    status VARCHAR(20) DEFAULT 'sent', -- 'sent', 'failed', 'bounced'
    error_message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP,
    opened_at TIMESTAMP,
    clicked_at TIMESTAMP
);

-- Email campaigns table
CREATE TABLE IF NOT EXISTS email_campaigns (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    template_name VARCHAR(255) NOT NULL,
    description TEXT,
    target_audience JSONB, -- criteria for selecting recipients
    filters JSONB, -- additional filters
    scheduled_at TIMESTAMP,
    sent_at TIMESTAMP,
    status VARCHAR(20) DEFAULT 'draft', -- 'draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled'
    total_recipients INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    opened_count INT DEFAULT 0,
    clicked_count INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_name) REFERENCES email_templates(name) ON DELETE RESTRICT
);

-- Create trigger for updated_at
CREATE TRIGGER update_email_campaigns_updated_at BEFORE UPDATE ON email_campaigns
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Email campaign recipients table
CREATE TABLE IF NOT EXISTS email_campaign_recipients (
    id SERIAL PRIMARY KEY,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed'
    sent_at TIMESTAMP,
    delivered_at TIMESTAMP,
    opened_at TIMESTAMP,
    clicked_at TIMESTAMP,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES email_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Email subscriptions table
CREATE TABLE IF NOT EXISTS email_subscriptions (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    is_subscribed BOOLEAN DEFAULT TRUE,
    preferred_time TIME, -- preferred time to receive emails
    timezone VARCHAR(50) DEFAULT 'UTC',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, category)
);

-- Create trigger for updated_at
CREATE TRIGGER update_email_subscriptions_updated_at BEFORE UPDATE ON email_subscriptions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Email templates categories table
CREATE TABLE IF NOT EXISTS email_template_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Email tracking pixels table
CREATE TABLE IF NOT EXISTS email_tracking_pixels (
    id SERIAL PRIMARY KEY,
    email_log_id INT NOT NULL,
    user_id INT NOT NULL,
    user_agent TEXT,
    ip_address INET,
    tracked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_log_id) REFERENCES email_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Email link tracking table
CREATE TABLE IF NOT EXISTS email_link_tracking (
    id SERIAL PRIMARY KEY,
    email_log_id INT NOT NULL,
    user_id INT NOT NULL,
    link_url VARCHAR(1000) NOT NULL,
    link_text VARCHAR(500),
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_agent TEXT,
    ip_address INET,
    FOREIGN KEY (email_log_id) REFERENCES email_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_email_templates_name ON email_templates(name);
CREATE INDEX IF NOT EXISTS idx_email_templates_category ON email_templates(category);
CREATE INDEX IF NOT EXISTS idx_email_logs_template_name ON email_logs(template_name);
CREATE INDEX IF NOT EXISTS idx_email_logs_recipient_email ON email_logs(recipient_email);
CREATE INDEX IF NOT EXISTS idx_email_logs_sent_at ON email_logs(sent_at);
CREATE INDEX IF NOT EXISTS idx_email_campaigns_status ON email_campaigns(status);
CREATE INDEX IF NOT EXISTS idx_email_campaigns_created_by ON email_campaigns(created_by);
CREATE INDEX IF NOT EXISTS idx_email_campaign_recipients_campaign_id ON email_campaign_recipients(campaign_id);
CREATE INDEX IF NOT EXISTS idx_email_campaign_recipients_user_id ON email_campaign_recipients(user_id);
CREATE INDEX IF NOT EXISTS idx_email_campaign_recipients_status ON email_campaign_recipients(status);
CREATE INDEX IF NOT EXISTS idx_email_subscriptions_user_id ON email_subscriptions(user_id);
CREATE INDEX IF NOT EXISTS idx_email_subscriptions_category ON email_subscriptions(category);
CREATE INDEX IF NOT EXISTS idx_email_tracking_pixels_email_log_id ON email_tracking_pixels(email_log_id);
CREATE INDEX IF NOT EXISTS idx_email_link_tracking_email_log_id ON email_link_tracking(email_log_id);
CREATE INDEX IF NOT EXISTS idx_email_link_tracking_user_id ON email_link_tracking(user_id);

-- Insert default email template categories
INSERT INTO email_template_categories (name, description) VALUES
('welcome', 'Welcome and onboarding emails'),
('booking', 'Booking related emails'),
('payment', 'Payment and transaction emails'),
('notification', 'General notification emails'),
('marketing', 'Marketing and promotional emails'),
('system', 'System and administrative emails'),
('security', 'Security related emails')
ON CONFLICT (name) DO NOTHING;

-- Insert default email templates
INSERT INTO email_templates (name, category, subject_template, message_template, variables, created_by) VALUES
('welcome', 'welcome', 'Welcome to {{app_name}}!', 'Dear {{user_name}},\n\nWelcome to {{app_name}}! We\'re excited to have you join our community of household service providers and seekers.\n\nYour account has been successfully created with the email {{user_email}}. You can now start using our platform to:\n\n- Find reliable household workers\n- Offer your services to those in need\n- Manage bookings and payments\n- Build your reputation through reviews\n\nTo get started, please log in to your account at {{login_url}}.\n\nIf you have any questions or need assistance, please don\'t hesitate to contact our support team.\n\nBest regards,\nThe {{app_name}} Team', '{"app_name", "user_name", "user_email", "login_url"}', 1),

('booking_confirmation', 'booking', 'Booking Confirmed - {{service_type}} on {{start_date}}', 'Dear {{user_name}},\n\nYour booking has been confirmed! Here are the details:\n\nService: {{service_type}}\nWorker: {{worker_name}}\nDate: {{start_date}}\nDuration: {{start_date}} to {{end_date}}\nTotal Amount: {{total_amount}} RWF\nBooking ID: {{booking_id}}\n\nPlease ensure you are available at the scheduled time. If you need to make any changes, please contact us or the worker directly.\n\nPayment will be processed after the service is completed.\n\nThank you for using {{app_name}}!\n\nBest regards,\nThe {{app_name}} Team', '{"user_name", "service_type", "worker_name", "start_date", "end_date", "total_amount", "booking_id", "app_name"}', 1),

('payment_receipt', 'payment', 'Payment Receipt - {{transaction_id}}', 'Dear {{user_name}},\n\nThank you for your payment! Here are your transaction details:\n\nTransaction ID: {{transaction_id}}\nAmount: {{amount}} RWF\nPayment Method: {{payment_method}}\nPayment Date: {{payment_date}}\n\nYour payment has been successfully processed and confirmed. You can view your transaction history in your dashboard.\n\nIf you have any questions about this transaction, please contact our support team.\n\nBest regards,\nThe {{app_name}} Team', '{"user_name", "transaction_id", "amount", "payment_method", "payment_date"}', 1),

('password_reset', 'security', 'Reset Your Password', 'Dear {{user_name}},\n\nWe received a request to reset your password for your {{app_name}} account.\n\nTo reset your password, please click on the link below:\n\n{{reset_url}}\n\nThis link will expire in {{expiry_hours}} hours for security reasons.\n\nIf you didn\'t request this password reset, please ignore this email or contact our support team if you have concerns.\n\nBest regards,\nThe {{app_name}} Team', '{"user_name", "app_name", "reset_url", "expiry_hours"}', 1),

('worker_approval', 'notification', 'Your Profile Has Been Approved!', 'Dear {{worker_name}},\n\nGreat news! Your profile has been approved and you are now ready to start receiving bookings through {{app_name}}.\n\nYour profile is now visible to potential employers who are looking for your services. Make sure to:\n\n- Keep your availability up to date\n- Respond promptly to booking requests\n- Provide excellent service to build your reputation\n- Encourage clients to leave reviews\n\nYou can access your dashboard and manage your profile here: {{dashboard_url}}\n\nView your public profile: {{profile_url}}\n\nWe\'re excited to see you succeed on our platform!\n\nBest regards,\nThe {{app_name}} Team', '{"worker_name", "app_name", "dashboard_url", "profile_url"}', 1),

('worker_rejection', 'notification', 'Regarding Your Profile Application', 'Dear {{worker_name}},\n\nThank you for your interest in joining {{app_name}} as a service provider.\n\nAfter careful review of your application, we regret to inform you that your profile could not be approved at this time.\n\nReason: {{rejection_reason}}\n\nYou may reapply after addressing the issues mentioned above. If you believe this decision was made in error or need clarification, please don\'t hesitate to contact us at {{contact_email}}.\n\nWe appreciate your understanding and wish you the best in your future endeavors.\n\nBest regards,\nThe {{app_name}} Team', '{"worker_name", "app_name", "rejection_reason", "contact_email"}', 1),

('booking_reminder', 'booking', 'Reminder: Upcoming Booking Tomorrow', 'Dear {{user_name}},\n\nThis is a friendly reminder about your upcoming booking tomorrow:\n\nService: {{service_type}}\nWorker: {{worker_name}}\nDate: {{start_date}}\nTime: {{start_time}}\n\nPlease ensure you are prepared and available at the scheduled time. If you need to make any changes, please contact us as soon as possible.\n\nWe look forward to providing you with excellent service!\n\nBest regards,\nThe {{app_name}} Team', '{"user_name", "service_type", "worker_name", "start_date", "start_time", "app_name"}', 1),

('review_request', 'booking', 'Please Share Your Experience', 'Dear {{user_name}},\n\nHow was your experience with {{worker_name}} for your {{service_type}} service on {{service_date}}?\n\nYour feedback helps us improve our service and helps other users make informed decisions. Please take a moment to share your experience by leaving a review.\n\nRate your experience and write a review here: {{review_url}}\n\nYour honest feedback is greatly appreciated!\n\nBest regards,\nThe {{app_name}} Team', '{"user_name", "worker_name", "service_type", "service_date", "review_url", "app_name"}', 1)
ON CONFLICT (name) DO NOTHING;

-- Insert default email subscriptions for all users
INSERT INTO email_subscriptions (user_id, category, is_subscribed)
SELECT 
    u.id,
    unnest(ARRAY['booking', 'payment', 'notification', 'system']),
    unnest(ARRAY[true, true, true, true])
FROM users u
WHERE NOT EXISTS (
    SELECT 1 FROM email_subscriptions es 
    WHERE es.user_id = u.id AND es.category IN ('booking', 'payment', 'notification', 'system')
    LIMIT 1
);

-- Create view for email statistics
CREATE OR REPLACE VIEW email_statistics AS
SELECT 
    template_name,
    COUNT(*) as total_sent,
    COUNT(CASE WHEN sent_at >= CURRENT_DATE - INTERVAL '30 days' THEN 1 END) as last_30_days,
    COUNT(CASE WHEN sent_at >= CURRENT_DATE - INTERVAL '7 days' THEN 1 END) as last_7_days,
    COUNT(CASE WHEN sent_at >= CURRENT_DATE THEN 1 END) as today,
    COUNT(CASE WHEN status = 'bounced' THEN 1 END) as bounced,
    COUNT(CASE WHEN opened_at IS NOT NULL THEN 1 END) as opened,
    COUNT(CASE WHEN clicked_at IS NOT NULL THEN 1 END) as clicked,
    ROUND(COUNT(CASE WHEN opened_at IS NOT NULL THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as open_rate,
    ROUND(COUNT(CASE WHEN clicked_at IS NOT NULL THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as click_rate
FROM email_logs
GROUP BY template_name
ORDER BY total_sent DESC;

-- Function to clean up old email logs
CREATE OR REPLACE FUNCTION cleanup_old_email_logs()
RETURNS void AS $$
BEGIN
    DELETE FROM email_logs WHERE sent_at < CURRENT_DATE - INTERVAL '1 year';
    DELETE FROM email_tracking_pixels WHERE tracked_at < CURRENT_DATE - INTERVAL '1 year';
    DELETE FROM email_link_tracking WHERE clicked_at < CURRENT_DATE - INTERVAL '1 year';
END;
$$ LANGUAGE plpgsql;

-- Scheduled job (requires pg_cron extension)
-- SELECT cron.schedule('cleanup-email-logs', '0 3 * * *', 'SELECT cleanup_old_email_logs();');
