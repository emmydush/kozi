<?php
// Payment Gateway Extension
class PaymentGateway {
    private $db;
    private $settings;
    
    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->settings = $GLOBALS['extensions']['payment-gateway']['settings'];
    }
    
    // Process payment
    public function process_payment($booking_id, $amount, $payment_method, $payment_details = []) {
        try {
            // Create transaction record
            $transaction_id = $this->create_transaction($booking_id, $amount, $payment_method, $payment_details);
            
            if (!$transaction_id) {
                return ['success' => false, 'message' => 'Failed to create transaction'];
            }
            
            // Process payment based on method
            $result = [];
            switch ($payment_method) {
                case 'mtn_money':
                    $result = $this->process_mtn_money($transaction_id, $amount, $payment_details);
                    break;
                case 'airtel_money':
                    $result = $this->process_airtel_money($transaction_id, $amount, $payment_details);
                    break;
                case 'card':
                    $result = $this->process_card_payment($transaction_id, $amount, $payment_details);
                    break;
                case 'paypal':
                    $result = $this->process_paypal($transaction_id, $amount, $payment_details);
                    break;
                default:
                    return ['success' => false, 'message' => 'Invalid payment method'];
            }
            
            // Update transaction status
            if ($result['success']) {
                $this->update_transaction_status($transaction_id, 'completed', $result['transaction_ref']);
                $this->update_booking_payment_status($booking_id, 'paid');
            } else {
                $this->update_transaction_status($transaction_id, 'failed');
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Payment processing failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Payment processing failed'];
        }
    }
    
    // Create transaction record
    private function create_transaction($booking_id, $amount, $payment_method, $payment_details) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO transactions (booking_id, user_id, amount, type, status, payment_method_id, transaction_id, notes)
                SELECT ?, user_id, ?, 'payment', 'pending', ?, ?, ?
                FROM bookings WHERE id = ?
            ");
            
            $payment_method_id = $this->get_or_create_payment_method($payment_method, $payment_details);
            $transaction_ref = $this->generate_transaction_ref();
            $notes = json_encode($payment_details);
            
            $stmt->execute([$booking_id, $amount, $payment_method_id, $transaction_ref, $notes, $booking_id]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Failed to create transaction: " . $e->getMessage());
            return false;
        }
    }
    
    // Update transaction status
    private function update_transaction_status($transaction_id, $status, $transaction_ref = null) {
        try {
            $sql = "UPDATE transactions SET status = ?";
            $params = [$status];
            
            if ($transaction_ref) {
                $sql .= ", transaction_id = ?";
                $params[] = $transaction_ref;
            }
            
            if ($status === 'completed') {
                $sql .= ", payment_date = CURRENT_DATE";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $transaction_id;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Failed to update transaction status: " . $e->getMessage());
            return false;
        }
    }
    
    // Update booking payment status
    private function update_booking_payment_status($booking_id, $status) {
        try {
            $stmt = $this->db->prepare("
                UPDATE bookings SET payment_status = ? WHERE id = ?
            ");
            return $stmt->execute([$status, $booking_id]);
        } catch (PDOException $e) {
            error_log("Failed to update booking payment status: " . $e->getMessage());
            return false;
        }
    }
    
    // Process MTN Mobile Money
    private function process_mtn_money($transaction_id, $amount, $payment_details) {
        if (!$this->settings['mtn_money_enabled']) {
            return ['success' => false, 'message' => 'MTN Mobile Money is disabled'];
        }
        
        // Validate required fields
        $required_fields = ['phone_number', 'reference'];
        foreach ($required_fields as $field) {
            if (!isset($payment_details[$field]) || empty($payment_details[$field])) {
                return ['success' => false, 'message' => "Missing required field: $field"];
            }
        }
        
        // Simulate MTN Mobile Money API call
        $mtn_response = $this->simulate_mtn_money_payment($payment_details['phone_number'], $amount, $payment_details['reference']);
        
        if ($mtn_response['success']) {
            return [
                'success' => true,
                'message' => 'MTN Mobile Money payment successful',
                'transaction_ref' => $mtn_response['transaction_id']
            ];
        } else {
            return [
                'success' => false,
                'message' => $mtn_response['message']
            ];
        }
    }
    
    // Process Airtel Money
    private function process_airtel_money($transaction_id, $amount, $payment_details) {
        if (!$this->settings['airtel_money_enabled']) {
            return ['success' => false, 'message' => 'Airtel Money is disabled'];
        }
        
        // Validate required fields
        $required_fields = ['phone_number', 'reference'];
        foreach ($required_fields as $field) {
            if (!isset($payment_details[$field]) || empty($payment_details[$field])) {
                return ['success' => false, 'message' => "Missing required field: $field"];
            }
        }
        
        // Simulate Airtel Money API call
        $airtel_response = $this->simulate_airtel_money_payment($payment_details['phone_number'], $amount, $payment_details['reference']);
        
        if ($airtel_response['success']) {
            return [
                'success' => true,
                'message' => 'Airtel Money payment successful',
                'transaction_ref' => $airtel_response['transaction_id']
            ];
        } else {
            return [
                'success' => false,
                'message' => $airtel_response['message']
            ];
        }
    }
    
    // Process card payment
    private function process_card_payment($transaction_id, $amount, $payment_details) {
        if (!$this->settings['card_enabled']) {
            return ['success' => false, 'message' => 'Card payments are disabled'];
        }
        
        // Validate required fields
        $required_fields = ['card_number', 'expiry_month', 'expiry_year', 'cvv', 'cardholder_name'];
        foreach ($required_fields as $field) {
            if (!isset($payment_details[$field]) || empty($payment_details[$field])) {
                return ['success' => false, 'message' => "Missing required field: $field"];
            }
        }
        
        // Simulate card payment processing
        $card_response = $this->simulate_card_payment($payment_details, $amount);
        
        if ($card_response['success']) {
            return [
                'success' => true,
                'message' => 'Card payment successful',
                'transaction_ref' => $card_response['transaction_id']
            ];
        } else {
            return [
                'success' => false,
                'message' => $card_response['message']
            ];
        }
    }
    
    // Process PayPal payment
    private function process_paypal($transaction_id, $amount, $payment_details) {
        if (!$this->settings['paypal_enabled']) {
            return ['success' => false, 'message' => 'PayPal is disabled'];
        }
        
        // Simulate PayPal payment
        $paypal_response = $this->simulate_paypal_payment($amount, $payment_details);
        
        if ($paypal_response['success']) {
            return [
                'success' => true,
                'message' => 'PayPal payment successful',
                'transaction_ref' => $paypal_response['transaction_id']
            ];
        } else {
            return [
                'success' => false,
                'message' => $paypal_response['message']
            ];
        }
    }
    
    // Get or create payment method
    private function get_or_create_payment_method($payment_method, $payment_details) {
        try {
            // Get user_id from session or context
            $user_id = $_SESSION['user_id'] ?? null;
            if (!$user_id) {
                return null;
            }
            
            // Check if payment method already exists
            $stmt = $this->db->prepare("
                SELECT id FROM payment_methods 
                WHERE user_id = ? AND type = ? AND details = ?
            ");
            $stmt->execute([$user_id, $payment_method, json_encode($payment_details)]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['id'];
            }
            
            // Create new payment method
            $stmt = $this->db->prepare("
                INSERT INTO payment_methods (user_id, type, details, is_default, status)
                VALUES (?, ?, ?, FALSE, 'active')
            ");
            $stmt->execute([$user_id, $payment_method, json_encode($payment_details)]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Failed to get/create payment method: " . $e->getMessage());
            return null;
        }
    }
    
    // Generate transaction reference
    private function generate_transaction_ref() {
        return 'TXN' . date('YmdHis') . rand(1000, 9999);
    }
    
    // Simulate MTN Mobile Money payment
    private function simulate_mtn_money_payment($phone, $amount, $reference) {
        // Simulate API delay
        usleep(100000); // 100ms
        
        // Simulate success
        return [
            'success' => true,
            'transaction_id' => 'MTN' . date('YmdHis') . rand(1000, 9999),
            'message' => 'Payment processed successfully'
        ];
    }
    
    // Simulate Airtel Money payment
    private function simulate_airtel_money_payment($phone, $amount, $reference) {
        // Simulate API delay
        usleep(100000); // 100ms
        
        // Simulate success
        return [
            'success' => true,
            'transaction_id' => 'AIR' . date('YmdHis') . rand(1000, 9999),
            'message' => 'Payment processed successfully'
        ];
    }
    
    // Simulate card payment
    private function simulate_card_payment($card_details, $amount) {
        // Simulate API delay
        usleep(150000); // 150ms
        
        // Basic card validation
        $card_number = str_replace([' ', '-'], '', $card_details['card_number']);
        if (strlen($card_number) < 13 || strlen($card_number) > 19) {
            return ['success' => false, 'message' => 'Invalid card number'];
        }
        
        // Simulate success
        return [
            'success' => true,
            'transaction_id' => 'CARD' . date('YmdHis') . rand(1000, 9999),
            'message' => 'Payment processed successfully'
        ];
    }
    
    // Simulate PayPal payment
    private function simulate_paypal_payment($amount, $payment_details) {
        // Simulate API delay
        usleep(200000); // 200ms
        
        // Simulate success
        return [
            'success' => true,
            'transaction_id' => 'PP' . date('YmdHis') . rand(1000, 9999),
            'message' => 'Payment processed successfully'
        ];
    }
    
    // Get payment methods for user
    public function get_user_payment_methods($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM payment_methods 
                WHERE user_id = ? AND status = 'active'
                ORDER BY is_default DESC, created_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get payment methods: " . $e->getMessage());
            return [];
        }
    }
    
    // Get transaction history
    public function get_transaction_history($user_id, $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, b.start_date, b.end_date, w.name as worker_name, u.name as user_name
                FROM transactions t
                LEFT JOIN bookings b ON t.booking_id = b.id
                LEFT JOIN workers w ON b.worker_id = w.id
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.user_id = ?
                ORDER BY t.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get transaction history: " . $e->getMessage());
            return [];
        }
    }
    
    // Refund payment
    public function refund_payment($transaction_id, $reason = '') {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM transactions WHERE id = ? AND status = 'completed'
            ");
            $stmt->execute([$transaction_id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                return ['success' => false, 'message' => 'Transaction not found or not completed'];
            }
            
            // Create refund transaction
            $refund_id = $this->create_transaction(
                $transaction['booking_id'], 
                $transaction['amount'], 
                'refund', 
                ['original_transaction_id' => $transaction_id, 'reason' => $reason]
            );
            
            if ($refund_id) {
                $this->update_transaction_status($refund_id, 'completed', 'REF' . date('YmdHis') . rand(1000, 9999));
                $this->update_transaction_status($transaction_id, 'refunded');
                
                return ['success' => true, 'message' => 'Refund processed successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to process refund'];
        } catch (Exception $e) {
            error_log("Refund processing failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Refund processing failed'];
        }
    }
}

// Initialize payment gateway
$payment_gateway = new PaymentGateway();
?>
