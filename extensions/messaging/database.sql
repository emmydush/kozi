-- Messaging System Database Schema
-- This extends the existing database schema

-- Add file_attachment column to messages table
ALTER TABLE messages ADD COLUMN IF NOT EXISTS file_attachment VARCHAR(255);

-- User blocks table
CREATE TABLE IF NOT EXISTS user_blocks (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    blocked_user_id INT NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, blocked_user_id)
);

-- Message status tracking table
CREATE TABLE IF NOT EXISTS message_status (
    id SERIAL PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    status VARCHAR(20) NOT NULL, -- 'delivered', 'read', 'deleted'
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (message_id, user_id, status)
);

-- Chat rooms table (for group messaging)
CREATE TABLE IF NOT EXISTS chat_rooms (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,
    type VARCHAR(20) DEFAULT 'private', -- 'private', 'group', 'public'
    created_by INT NOT NULL,
    job_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
);

-- Create trigger for updated_at
CREATE TRIGGER update_chat_rooms_updated_at BEFORE UPDATE ON chat_rooms
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Chat room members table
CREATE TABLE IF NOT EXISTS chat_room_members (
    id SERIAL PRIMARY KEY,
    room_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(20) DEFAULT 'member', -- 'admin', 'member'
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_read_at TIMESTAMP,
    is_muted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (room_id, user_id)
);

-- Group messages table
CREATE TABLE IF NOT EXISTS group_messages (
    id SERIAL PRIMARY KEY,
    room_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type VARCHAR(20) DEFAULT 'text', -- 'text', 'image', 'file', 'system'
    file_attachment VARCHAR(255),
    reply_to_id INT,
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_id) REFERENCES group_messages(id) ON DELETE SET NULL
);

-- Message reactions table
CREATE TABLE IF NOT EXISTS message_reactions (
    id SERIAL PRIMARY KEY,
    message_id INT NOT NULL,
    message_type VARCHAR(20) NOT NULL, -- 'direct', 'group'
    user_id INT NOT NULL,
    reaction VARCHAR(10) NOT NULL, -- emoji
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (message_id, message_type, user_id, reaction)
);

-- Typing indicators table
CREATE TABLE IF NOT EXISTS typing_indicators (
    id SERIAL PRIMARY KEY,
    room_id INT,
    user_id INT NOT NULL,
    recipient_id INT,
    is_typing BOOLEAN DEFAULT TRUE,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Message templates table
CREATE TABLE IF NOT EXISTS message_templates (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    template_text TEXT NOT NULL,
    variables JSONB,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create trigger for updated_at
CREATE TRIGGER update_message_templates_updated_at BEFORE UPDATE ON message_templates
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_user_blocks_user_id ON user_blocks(user_id);
CREATE INDEX IF NOT EXISTS idx_user_blocks_blocked_user_id ON user_blocks(blocked_user_id);
CREATE INDEX IF NOT EXISTS idx_message_status_message_id ON message_status(message_id);
CREATE INDEX IF NOT EXISTS idx_message_status_user_id ON message_status(user_id);
CREATE INDEX IF NOT EXISTS idx_chat_rooms_created_by ON chat_rooms(created_by);
CREATE INDEX IF NOT EXISTS idx_chat_rooms_job_id ON chat_rooms(job_id);
CREATE INDEX IF NOT EXISTS idx_chat_rooms_type ON chat_rooms(type);
CREATE INDEX IF NOT EXISTS idx_chat_room_members_room_id ON chat_room_members(room_id);
CREATE INDEX IF NOT EXISTS idx_chat_room_members_user_id ON chat_room_members(user_id);
CREATE INDEX IF NOT EXISTS idx_group_messages_room_id ON group_messages(room_id);
CREATE INDEX IF NOT EXISTS idx_group_messages_sender_id ON group_messages(sender_id);
CREATE INDEX IF NOT EXISTS idx_group_messages_created_at ON group_messages(created_at);
CREATE INDEX IF NOT EXISTS idx_message_reactions_message_id ON message_reactions(message_id);
CREATE INDEX IF NOT EXISTS idx_typing_indicators_room_id ON typing_indicators(room_id);
CREATE INDEX IF NOT EXISTS idx_typing_indicators_user_id ON typing_indicators(user_id);
CREATE INDEX IF NOT EXISTS idx_typing_indicators_recipient_id ON typing_indicators(recipient_id);
CREATE INDEX IF NOT EXISTS idx_message_templates_category ON message_templates(category);

-- Insert default message templates
INSERT INTO message_templates (name, category, template_text, variables, created_by) VALUES
('Booking Inquiry', 'booking', 'Hello, I''m interested in your {service_type} services. Are you available on {date}?', '{"service_type", "date"}', 1),
('Booking Confirmation', 'booking', 'Your booking for {service_type} on {date} has been confirmed. Please arrive on time.', '{"service_type", "date"}', 1),
('Booking Cancellation', 'booking', 'Unfortunately, I need to cancel our booking for {date} due to {reason}. I apologize for any inconvenience.', '{"date", "reason"}', 1),
('Payment Request', 'payment', 'Please proceed with the payment of {amount} RWF for your booking on {date}. Payment methods available: {payment_methods}.', '{"amount", "date", "payment_methods"}', 1),
('Service Completion', 'service', 'The {service_type} service has been completed. Please review the work and provide feedback.', '{"service_type"}', 1),
('Greeting', 'general', 'Hello! How can I help you today?', '{}', 1),
('Thank You', 'general', 'Thank you for your business! I appreciate the opportunity to work with you.', '{}', 1),
('Follow Up', 'general', 'Just checking in to see if you need any additional assistance with {service_type}.', '{"service_type"}', 1)
ON CONFLICT DO NOTHING;

-- Create view for user message statistics
CREATE OR REPLACE VIEW user_message_stats AS
SELECT 
    u.id as user_id,
    u.name,
    COUNT(CASE WHEN m.sender_id = u.id THEN 1 END) as messages_sent,
    COUNT(CASE WHEN m.recipient_id = u.id THEN 1 END) as messages_received,
    COUNT(CASE WHEN m.recipient_id = u.id AND m.is_read = FALSE THEN 1 END) as unread_messages,
    COUNT(CASE WHEN m.file_attachment IS NOT NULL THEN 1 END) as messages_with_files,
    MAX(m.created_at) as last_message_at
FROM users u
LEFT JOIN messages m ON (m.sender_id = u.id OR m.recipient_id = u.id)
GROUP BY u.id, u.name;

-- Function to clean up old typing indicators
CREATE OR REPLACE FUNCTION cleanup_typing_indicators()
RETURNS void AS $$
BEGIN
    DELETE FROM typing_indicators 
    WHERE last_seen < CURRENT_TIMESTAMP - INTERVAL '5 minutes';
END;
$$ LANGUAGE plpgsql;

-- Clean up old messages function
CREATE OR REPLACE FUNCTION cleanup_old_messages()
RETURNS void AS $$
BEGIN
    -- This would be called by a scheduled job
    DELETE FROM messages 
    WHERE created_at < CURRENT_DATE - INTERVAL '1 year'
    AND is_deleted_by_sender = TRUE 
    AND is_deleted_by_recipient = TRUE;
END;
$$ LANGUAGE plpgsql;

-- Create scheduled job cleanup (requires pg_cron extension)
-- SELECT cron.schedule('cleanup-typing-indicators', '*/1 * * * *', 'SELECT cleanup_typing_indicators();');
-- SELECT cron.schedule('cleanup-old-messages', '0 2 * * *', 'SELECT cleanup_old_messages();');
