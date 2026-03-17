-- Notification System Database Schema
-- This extends the existing database schema

-- Additional notification types
ALTER TYPE notification_type ADD VALUE 'payment';
ALTER TYPE notification_type ADD VALUE 'booking_reminder';
ALTER TYPE notification_type ADD VALUE 'review_received';
ALTER TYPE notification_type ADD VALUE 'profile_approved';
ALTER TYPE notification_type ADD VALUE 'profile_rejected';

-- Notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    email_enabled BOOLEAN DEFAULT TRUE,
    sms_enabled BOOLEAN DEFAULT FALSE,
    push_enabled BOOLEAN DEFAULT TRUE,
    in_app_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, notification_type)
);

-- Create trigger for updated_at
CREATE TRIGGER update_notification_preferences_updated_at BEFORE UPDATE ON notification_preferences
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Notification templates table
CREATE TABLE IF NOT EXISTS notification_templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    type VARCHAR(50) NOT NULL,
    subject_template TEXT,
    message_template TEXT NOT NULL,
    email_template TEXT,
    sms_template TEXT,
    variables JSONB,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create trigger for updated_at
CREATE TRIGGER update_notification_templates_updated_at BEFORE UPDATE ON notification_templates
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Notification delivery log
CREATE TABLE IF NOT EXISTS notification_delivery_log (
    id SERIAL PRIMARY KEY,
    notification_id INT NOT NULL,
    delivery_type VARCHAR(20) NOT NULL, -- 'email', 'sms', 'push'
    status VARCHAR(20) NOT NULL, -- 'sent', 'failed', 'pending'
    recipient VARCHAR(255),
    error_message TEXT,
    sent_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_notification_preferences_user_id ON notification_preferences(user_id);
CREATE INDEX IF NOT EXISTS idx_notification_preferences_type ON notification_preferences(notification_type);
CREATE INDEX IF NOT EXISTS idx_notification_templates_type ON notification_templates(type);
CREATE INDEX IF NOT EXISTS idx_notification_delivery_log_notification_id ON notification_delivery_log(notification_id);
CREATE INDEX IF NOT EXISTS idx_notification_delivery_log_status ON notification_delivery_log(status);

-- Insert default notification templates
INSERT INTO notification_templates (name, type, subject_template, message_template, email_template, sms_template, variables) VALUES
('booking_created', 'booking', 'New Booking Created', 'A new booking has been created for {service_type} on {start_date}.', '<h3>New Booking</h3><p>A new booking has been created for {service_type} on {start_date}.</p><p>Worker: {worker_name}</p><p>Employer: {employer_name}</p>', 'New booking for {service_type} on {start_date}', '{"service_type", "start_date", "worker_name", "employer_name"}'),
('booking_confirmed', 'booking', 'Booking Confirmed', 'Your booking for {service_type} on {start_date} has been confirmed.', '<h3>Booking Confirmed</h3><p>Your booking for {service_type} on {start_date} has been confirmed.</p><p>Worker: {worker_name}</p>', 'Your booking for {service_type} on {start_date} is confirmed', '{"service_type", "start_date", "worker_name"}'),
('booking_completed', 'booking', 'Booking Completed', 'Your booking for {service_type} on {start_date} has been completed. Please leave a review.', '<h3>Booking Completed</h3><p>Your booking for {service_type} on {start_date} has been completed.</p><p>Please take a moment to leave a review for {worker_name}.</p>', 'Your booking for {service_type} is completed. Please review {worker_name}', '{"service_type", "start_date", "worker_name"}'),
('message_received', 'message', 'New Message', 'You have a new message from {sender_name}: {message_preview}', '<h3>New Message</h3><p>You have received a new message from {sender_name}.</p><blockquote>{message_preview}</blockquote><p>Check your messages to reply.</p>', 'New message from {sender_name}: {message_preview}', '{"sender_name", "message_preview"}'),
('payment_received', 'payment', 'Payment Received', 'Payment of {amount} RWF has been received for booking #{booking_id}.', '<h3>Payment Received</h3><p>A payment of {amount} RWF has been received for booking #{booking_id}.</p><p>Payment method: {payment_method}</p>', 'Payment of {amount} RWF received for booking #{booking_id}', '{"amount", "booking_id", "payment_method"}'),
('review_received', 'review', 'New Review Received', 'You have received a {rating}-star review from {reviewer_name}.', '<h3>New Review</h3><p>You have received a {rating}-star review from {reviewer_name}.</p><blockquote>{review_comment}</blockquote>', 'You received a {rating}-star review from {reviewer_name}', '{"rating", "reviewer_name", "review_comment"}')
ON CONFLICT (name) DO NOTHING;

-- Insert default notification preferences for all users
INSERT INTO notification_preferences (user_id, notification_type, email_enabled, sms_enabled, push_enabled, in_app_enabled)
SELECT 
    u.id,
    nt.type,
    TRUE,  -- email enabled
    FALSE, -- sms disabled by default
    TRUE,  -- push enabled
    TRUE   -- in-app enabled
FROM users u
CROSS JOIN (VALUES ('booking'), ('message'), ('payment'), ('review'), ('system')) AS nt(type)
WHERE NOT EXISTS (
    SELECT 1 FROM notification_preferences np 
    WHERE np.user_id = u.id AND np.notification_type = nt.type
);
