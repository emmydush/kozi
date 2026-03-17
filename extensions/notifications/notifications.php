<?php
// Notification System Extension
class NotificationSystem {
    private $db;
    private $settings;
    
    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->settings = $GLOBALS['extensions']['notifications']['settings'];
    }
    
    // Create notification
    public function create_notification($user_id, $title, $message, $type = 'info', $related_id = null, $related_type = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id, related_type) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $title, $message, $type, $related_id, $related_type]);
            
            $notification_id = $this->db->lastInsertId();
            
            // Send email if enabled
            if ($this->settings['email_enabled']) {
                $this->send_email_notification($user_id, $title, $message);
            }
            
            // Send SMS if enabled
            if ($this->settings['sms_enabled']) {
                $this->send_sms_notification($user_id, $message);
            }
            
            // Send push notification if enabled
            if ($this->settings['push_enabled']) {
                $this->send_push_notification($user_id, $title, $message);
            }
            
            return $notification_id;
        } catch (PDOException $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    // Get user notifications
    public function get_user_notifications($user_id, $limit = 50, $unread_only = false) {
        try {
            $sql = "
                SELECT * FROM notifications 
                WHERE user_id = ?
            ";
            $params = [$user_id];
            
            if ($unread_only) {
                $sql .= " AND is_read = FALSE";
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get notifications: " . $e->getMessage());
            return [];
        }
    }
    
    // Mark notification as read
    public function mark_as_read($notification_id, $user_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$notification_id, $user_id]);
        } catch (PDOException $e) {
            error_log("Failed to mark notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    // Mark all notifications as read
    public function mark_all_as_read($user_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE user_id = ? AND is_read = FALSE
            ");
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Failed to mark all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    // Get unread count
    public function get_unread_count($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Failed to get unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    // Send email notification
    private function send_email_notification($user_id, $title, $message) {
        try {
            $stmt = $this->db->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['email'])) {
                $to = $user['email'];
                $subject = APP_NAME . " - " . $title;
                $headers = "From: noreply@householdconnect.com\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                
                $email_body = "
                    <html>
                    <head>
                        <title>$title</title>
                    </head>
                    <body>
                        <h2>$title</h2>
                        <p>$message</p>
                        <hr>
                        <p><small>This is an automated message from " . APP_NAME . "</small></p>
                    </body>
                    </html>
                ";
                
                mail($to, $subject, $email_body, $headers);
            }
        } catch (Exception $e) {
            error_log("Failed to send email notification: " . $e->getMessage());
        }
    }
    
    // Send SMS notification
    private function send_sms_notification($user_id, $message) {
        try {
            $stmt = $this->db->prepare("SELECT phone FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['phone'])) {
                // Integration with SMS gateway would go here
                // For now, just log the SMS
                error_log("SMS to {$user['phone']}: $message");
            }
        } catch (Exception $e) {
            error_log("Failed to send SMS notification: " . $e->getMessage());
        }
    }
    
    // Send push notification
    private function send_push_notification($user_id, $title, $message) {
        try {
            // Integration with push notification service would go here
            // For now, just log the push notification
            error_log("Push notification to user $user_id: $title - $message");
        } catch (Exception $e) {
            error_log("Failed to send push notification: " . $e->getMessage());
        }
    }
    
    // Create booking notification
    public function create_booking_notification($booking_id, $type) {
        try {
            $stmt = $this->db->prepare("
                SELECT b.*, u.name as user_name, w.name as worker_name 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                JOIN workers w ON b.worker_id = w.id 
                WHERE b.id = ?
            ");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($booking) {
                $title = "Booking " . ucfirst($type);
                $message = "Your booking has been $type.";
                
                // Notify employer
                $this->create_notification(
                    $booking['user_id'], 
                    $title, 
                    $message, 
                    'booking', 
                    $booking_id, 
                    'booking'
                );
                
                // Notify worker
                $this->create_notification(
                    $booking['worker_id'], 
                    $title, 
                    $message, 
                    'booking', 
                    $booking_id, 
                    'booking'
                );
            }
        } catch (Exception $e) {
            error_log("Failed to create booking notification: " . $e->getMessage());
        }
    }
    
    // Create message notification
    public function create_message_notification($message_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, s.name as sender_name, r.email as recipient_email 
                FROM messages m 
                JOIN users s ON m.sender_id = s.id 
                JOIN users r ON m.recipient_id = r.id 
                WHERE m.id = ?
            ");
            $stmt->execute([$message_id]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($message) {
                $title = "New Message";
                $message_text = "You have a new message from {$message['sender_name']}: {$message['message']}";
                
                $this->create_notification(
                    $message['recipient_id'], 
                    $title, 
                    $message_text, 
                    'message', 
                    $message_id, 
                    'message'
                );
            }
        } catch (Exception $e) {
            error_log("Failed to create message notification: " . $e->getMessage());
        }
    }
}

// Initialize notification system
$notification_system = new NotificationSystem();

// Auto-create notifications for events
function auto_notify_booking_created($booking_id) {
    global $notification_system;
    $notification_system->create_booking_notification($booking_id, 'created');
}

function auto_notify_booking_confirmed($booking_id) {
    global $notification_system;
    $notification_system->create_booking_notification($booking_id, 'confirmed');
}

function auto_notify_booking_completed($booking_id) {
    global $notification_system;
    $notification_system->create_booking_notification($booking_id, 'completed');
}

function auto_notify_message_received($message_id) {
    global $notification_system;
    $notification_system->create_message_notification($message_id);
}
?>
