-- Payment Gateway Database Schema
-- This extends the existing database schema

-- Additional payment method types
ALTER TYPE payment_method_type ADD VALUE 'mtn_money';
ALTER TYPE payment_method_type ADD VALUE 'airtel_money';

-- Additional transaction types
ALTER TYPE transaction_type ADD VALUE 'refund';

-- Payment gateway settings table
CREATE TABLE IF NOT EXISTS payment_gateway_settings (
    id SERIAL PRIMARY KEY,
    gateway_name VARCHAR(50) UNIQUE NOT NULL,
    is_enabled BOOLEAN DEFAULT FALSE,
    settings JSONB,
    test_mode BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create trigger for updated_at
CREATE TRIGGER update_payment_gateway_settings_updated_at BEFORE UPDATE ON payment_gateway_settings
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Payment method verification table
CREATE TABLE IF NOT EXISTS payment_method_verification (
    id SERIAL PRIMARY KEY,
    payment_method_id INT NOT NULL,
    verification_token VARCHAR(255) UNIQUE NOT NULL,
    verification_code VARCHAR(10),
    is_verified BOOLEAN DEFAULT FALSE,
    verified_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE CASCADE
);

-- Transaction webhook logs
CREATE TABLE IF NOT EXISTS transaction_webhook_logs (
    id SERIAL PRIMARY KEY,
    transaction_id INT NOT NULL,
    gateway_name VARCHAR(50) NOT NULL,
    webhook_data JSONB,
    processed BOOLEAN DEFAULT FALSE,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
);

-- Payment disputes table
CREATE TABLE IF NOT EXISTS payment_disputes (
    id SERIAL PRIMARY KEY,
    transaction_id INT NOT NULL,
    user_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(20) DEFAULT 'open', -- 'open', 'investigating', 'resolved', 'rejected'
    resolution TEXT,
    resolved_by INT,
    resolved_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create trigger for updated_at
CREATE TRIGGER update_payment_disputes_updated_at BEFORE UPDATE ON payment_disputes
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Payment analytics table
CREATE TABLE IF NOT EXISTS payment_analytics (
    id SERIAL PRIMARY KEY,
    date DATE NOT NULL,
    gateway_name VARCHAR(50) NOT NULL,
    total_transactions INT DEFAULT 0,
    successful_transactions INT DEFAULT 0,
    failed_transactions INT DEFAULT 0,
    total_amount DECIMAL(15,2) DEFAULT 0,
    success_rate DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (date, gateway_name)
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_payment_gateway_settings_name ON payment_gateway_settings(gateway_name);
CREATE INDEX IF NOT EXISTS idx_payment_method_verification_token ON payment_method_verification(verification_token);
CREATE INDEX IF NOT EXISTS idx_payment_method_verification_payment_method_id ON payment_method_verification(payment_method_id);
CREATE INDEX IF NOT EXISTS idx_transaction_webhook_logs_transaction_id ON transaction_webhook_logs(transaction_id);
CREATE INDEX IF NOT EXISTS idx_transaction_webhook_logs_processed ON transaction_webhook_logs(processed);
CREATE INDEX IF NOT EXISTS idx_payment_disputes_transaction_id ON payment_disputes(transaction_id);
CREATE INDEX IF NOT EXISTS idx_payment_disputes_user_id ON payment_disputes(user_id);
CREATE INDEX IF NOT EXISTS idx_payment_disputes_status ON payment_disputes(status);
CREATE INDEX IF NOT EXISTS idx_payment_analytics_date ON payment_analytics(date);
CREATE INDEX IF NOT EXISTS idx_payment_analytics_gateway ON payment_analytics(gateway_name);

-- Insert default payment gateway settings
INSERT INTO payment_gateway_settings (gateway_name, is_enabled, settings, test_mode) VALUES
('mtn_money', true, '{"api_key": "test_key", "api_secret": "test_secret", "merchant_id": "TEST_MERCHANT"}', true),
('airtel_money', true, '{"api_key": "test_key", "api_secret": "test_secret", "merchant_id": "TEST_MERCHANT"}', true),
('card', true, '{"stripe_public_key": "pk_test_...", "stripe_secret_key": "sk_test_..."}', true),
('paypal', false, '{"client_id": "test_client_id", "client_secret": "test_client_secret", "sandbox": true}', true)
ON CONFLICT (gateway_name) DO NOTHING;

-- Create view for payment statistics
CREATE OR REPLACE VIEW payment_statistics AS
SELECT 
    gateway_name,
    COUNT(*) as total_transactions,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_transactions,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount END), 0) as total_revenue,
    ROUND(
        COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0), 
        2
    ) as success_rate
FROM transactions t
JOIN payment_methods pm ON t.payment_method_id = pm.id
GROUP BY gateway_name;

-- Function to update payment analytics
CREATE OR REPLACE FUNCTION update_payment_analytics()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO payment_analytics (date, gateway_name, total_transactions, successful_transactions, failed_transactions, total_amount, success_rate)
    VALUES (
        CURRENT_DATE,
        (SELECT type FROM payment_methods WHERE id = NEW.payment_method_id),
        1,
        CASE WHEN NEW.status = 'completed' THEN 1 ELSE 0 END,
        CASE WHEN NEW.status = 'failed' THEN 1 ELSE 0 END,
        CASE WHEN NEW.status = 'completed' THEN NEW.amount ELSE 0 END,
        CASE WHEN NEW.status = 'completed' THEN 100 ELSE 0 END
    )
    ON CONFLICT (date, gateway_name)
    DO UPDATE SET
        total_transactions = payment_analytics.total_transactions + 1,
        successful_transactions = payment_analytics.successful_transactions + CASE WHEN NEW.status = 'completed' THEN 1 ELSE 0 END,
        failed_transactions = payment_analytics.failed_transactions + CASE WHEN NEW.status = 'failed' THEN 1 ELSE 0 END,
        total_amount = payment_analytics.total_amount + CASE WHEN NEW.status = 'completed' THEN NEW.amount ELSE 0 END,
        success_rate = ROUND(
            (payment_analytics.successful_transactions + CASE WHEN NEW.status = 'completed' THEN 1 ELSE 0 END) * 100.0 / 
            NULLIF((payment_analytics.total_transactions + 1), 0), 
            2
        );
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger to automatically update analytics
DROP TRIGGER IF EXISTS trigger_update_payment_analytics ON transactions;
CREATE TRIGGER trigger_update_payment_analytics
    AFTER INSERT ON transactions
    FOR EACH ROW
    EXECUTE FUNCTION update_payment_analytics();
