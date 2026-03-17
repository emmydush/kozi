<?php
// Email Templates Extension
class EmailTemplates {
    private $db;
    private $settings;
    private $template_engine;
    
    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->settings = $GLOBALS['extensions']['email-templates']['settings'];
        
        // Initialize template engine
        $this->init_template_engine();
    }
    
    // Initialize template engine (simplified Twig-like implementation)
    private function init_template_engine() {
        // For this implementation, we'll use a simple string replacement
        // In production, you might want to integrate actual Twig
        $this->template_engine = new SimpleTemplateEngine();
    }
    
    // Send email using template
    public function send_template_email($template_name, $recipient_email, $data = [], $options = []) {
        try {
            // Get template
            $template = $this->get_template($template_name);
            if (!$template) {
                return ['success' => false, 'message' => 'Template not found'];
            }
            
            // Render subject and body
            $subject = $this->render_template($template['subject_template'], $data);
            $body = $this->render_template($template['message_template'], $data);
            
            // Add HTML wrapper if needed
            if ($options['html'] ?? true) {
                $body = $this->wrap_html_template($body, $data);
            }
            
            // Send email
            $headers = $this->build_email_headers($options);
            $sent = mail($recipient_email, $subject, $body, $headers);
            
            if ($sent) {
                // Log the email
                $this->log_email($template_name, $recipient_email, $subject, $data);
                
                return [
                    'success' => true,
                    'message' => 'Email sent successfully',
                    'template' => $template_name,
                    'recipient' => $recipient_email
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to send email'];
            }
        } catch (Exception $e) {
            error_log("Email template error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email sending failed'];
        }
    }
    
    // Get email template
    public function get_template($name) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM email_templates 
                WHERE name = ? AND is_active = TRUE
            ");
            $stmt->execute([$name]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get email template: " . $e->getMessage());
            return null;
        }
    }
    
    // Get all templates
    public function get_all_templates($category = null) {
        try {
            $sql = "SELECT * FROM email_templates WHERE is_active = TRUE";
            $params = [];
            
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            
            $sql .= " ORDER BY category, name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get email templates: " . $e->getMessage());
            return [];
        }
    }
    
    // Create/update template
    public function save_template($data) {
        try {
            $required = ['name', 'category', 'subject_template', 'message_template'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['success' => false, 'message' => "Field $field is required"];
                }
            }
            
            $variables = json_encode($this->extract_variables($data['subject_template'] . ' ' . $data['message_template']));
            
            $stmt = $this->db->prepare("
                INSERT INTO email_templates (name, category, subject_template, message_template, variables, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
                ON CONFLICT (name) 
                DO UPDATE SET
                    category = EXCLUDED.category,
                    subject_template = EXCLUDED.subject_template,
                    message_template = EXCLUDED.message_template,
                    variables = EXCLUDED.variables,
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $user_id = $_SESSION['user_id'] ?? 1;
            $stmt->execute([
                $data['name'],
                $data['category'],
                $data['subject_template'],
                $data['message_template'],
                $variables,
                $user_id
            ]);
            
            return ['success' => true, 'message' => 'Template saved successfully'];
        } catch (PDOException $e) {
            error_log("Failed to save email template: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to save template'];
        }
    }
    
    // Delete template
    public function delete_template($name) {
        try {
            $stmt = $this->db->prepare("
                UPDATE email_templates 
                SET is_active = FALSE 
                WHERE name = ?
            ");
            return $stmt->execute([$name]);
        } catch (PDOException $e) {
            error_log("Failed to delete email template: " . $e->getMessage());
            return false;
        }
    }
    
    // Render template with data
    private function render_template($template, $data) {
        return $this->template_engine->render($template, $data);
    }
    
    // Wrap template in HTML
    private function wrap_html_template($content, $data) {
        $base_template = $this->get_base_template();
        $template_data = array_merge($data, [
            'content' => $content,
            'app_name' => APP_NAME,
            'app_url' => APP_URL,
            'current_year' => date('Y')
        ]);
        
        return $this->render_template($base_template, $template_data);
    }
    
    // Get base HTML template
    private function get_base_template() {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{subject}}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
        .button { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
        .logo { font-size: 24px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">{{app_name}}</div>
    </div>
    <div class="content">
        {{content}}
    </div>
    <div class="footer">
        <p>&copy; {{current_year}} {{app_name}}. All rights reserved.</p>
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>
</body>
</html>';
    }
    
    // Build email headers
    private function build_email_headers($options) {
        $headers = [];
        
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . ($options['from'] ?? 'noreply@householdconnect.com');
        
        if (isset($options['reply_to'])) {
            $headers[] = 'Reply-To: ' . $options['reply_to'];
        }
        
        if (isset($options['cc'])) {
            $headers[] = 'Cc: ' . $options['cc'];
        }
        
        if (isset($options['bcc'])) {
            $headers[] = 'Bcc: ' . $options['bcc'];
        }
        
        return implode("\r\n", $headers);
    }
    
    // Extract template variables
    private function extract_variables($template) {
        preg_match_all('/\{\{(\w+)\}\}/', $template, $matches);
        return array_unique($matches[1]);
    }
    
    // Log email sending
    private function log_email($template_name, $recipient, $subject, $data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_logs (template_name, recipient_email, subject, data, sent_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$template_name, $recipient, $subject, json_encode($data)]);
        } catch (PDOException $e) {
            error_log("Failed to log email: " . $e->getMessage());
        }
    }
    
    // Send welcome email
    public function send_welcome_email($user_email, $user_name) {
        return $this->send_template_email('welcome', $user_email, [
            'user_name' => $user_name,
            'user_email' => $user_email,
            'login_url' => APP_URL . '/login'
        ]);
    }
    
    // Send booking confirmation
    public function send_booking_confirmation($booking_id, $user_email) {
        try {
            // Get booking details
            $stmt = $this->db->prepare("
                SELECT b.*, u.name as user_name, w.name as worker_name, w.type as service_type
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN workers w ON b.worker_id = w.id
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($booking) {
                return $this->send_template_email('booking_confirmation', $user_email, [
                    'user_name' => $booking['user_name'],
                    'worker_name' => $booking['worker_name'],
                    'service_type' => $booking['service_type'],
                    'start_date' => $booking['start_date'],
                    'end_date' => $booking['end_date'],
                    'total_amount' => number_format($booking['total_amount'], 0),
                    'booking_id' => $booking_id
                ]);
            }
        } catch (PDOException $e) {
            error_log("Failed to send booking confirmation: " . $e->getMessage());
        }
        
        return ['success' => false, 'message' => 'Failed to send booking confirmation'];
    }
    
    // Send payment receipt
    public function send_payment_receipt($transaction_id, $user_email) {
        try {
            // Get transaction details
            $stmt = $this->db->prepare("
                SELECT t.*, u.name as user_name, pm.type as payment_method
                FROM transactions t
                JOIN users u ON t.user_id = u.id
                LEFT JOIN payment_methods pm ON t.payment_method_id = pm.id
                WHERE t.id = ? AND t.status = 'completed'
            ");
            $stmt->execute([$transaction_id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($transaction) {
                return $this->send_template_email('payment_receipt', $user_email, [
                    'user_name' => $transaction['user_name'],
                    'amount' => number_format($transaction['amount'], 0),
                    'payment_method' => $transaction['payment_method'],
                    'transaction_id' => $transaction['transaction_id'],
                    'payment_date' => $transaction['payment_date']
                ]);
            }
        } catch (PDOException $e) {
            error_log("Failed to send payment receipt: " . $e->getMessage());
        }
        
        return ['success' => false, 'message' => 'Failed to send payment receipt'];
    }
    
    // Send password reset
    public function send_password_reset($user_email, $reset_token) {
        return $this->send_template_email('password_reset', $user_email, [
            'reset_url' => APP_URL . '/reset-password?token=' . $reset_token,
            'reset_token' => $reset_token,
            'expiry_hours' => 24
        ]);
    }
    
    // Send worker approval notification
    public function send_worker_approval($worker_email, $worker_name) {
        return $this->send_template_email('worker_approval', $worker_email, [
            'worker_name' => $worker_name,
            'profile_url' => APP_URL . '/worker-profile',
            'dashboard_url' => APP_URL . '/dashboard'
        ]);
    }
    
    // Send worker rejection notification
    public function send_worker_rejection($worker_email, $worker_name, $reason) {
        return $this->send_template_email('worker_rejection', $worker_email, [
            'worker_name' => $worker_name,
            'rejection_reason' => $reason,
            'contact_email' => 'support@householdconnect.com'
        ]);
    }
    
    // Get email statistics
    public function get_email_stats($days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    template_name,
                    COUNT(*) as total_sent,
                    COUNT(CASE WHEN sent_at >= CURRENT_DATE - INTERVAL '{$days} days' THEN 1 END) as recent_sent,
                    MAX(sent_at) as last_sent
                FROM email_logs
                GROUP BY template_name
                ORDER BY total_sent DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get email stats: " . $e->getMessage());
            return [];
        }
    }
}

// Simple template engine implementation
class SimpleTemplateEngine {
    public function render($template, $data) {
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }
}

// Initialize email templates system
$email_templates = new EmailTemplates();
?>
