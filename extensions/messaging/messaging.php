<?php
// Messaging/Chat System Extension
class MessagingSystem {
    private $db;
    private $settings;
    
    public function __construct() {
        global $conn;
        $this->db = $conn;
        $this->settings = $GLOBALS['extensions']['messaging']['settings'];
    }
    
    // Send message
    public function send_message($sender_id, $recipient_id, $subject, $message, $job_id = null, $file_path = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO messages (sender_id, recipient_id, subject, message, job_id, file_attachment)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$sender_id, $recipient_id, $subject, $message, $job_id, $file_path]);
            
            if ($result) {
                $message_id = $this->db->lastInsertId();
                
                // Trigger notification if notification system is available
                if (function_exists('auto_notify_message_received')) {
                    auto_notify_message_received($message_id);
                }
                
                return $message_id;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Failed to send message: " . $e->getMessage());
            return false;
        }
    }
    
    // Get conversation between two users
    public function get_conversation($user1_id, $user2_id, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, 
                       u.name as sender_name,
                       u.profile_image as sender_avatar,
                       CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as message_type
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE (m.sender_id = ? AND m.recipient_id = ?) 
                   OR (m.sender_id = ? AND m.recipient_id = ?)
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user1_id, $user1_id, $user2_id, $user2_id, $user1_id, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get conversation: " . $e->getMessage());
            return [];
        }
    }
    
    // Get user's message threads
    public function get_message_threads($user_id, $limit = 20) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT ON (GREATEST(sender_id, recipient_id), LEAST(sender_id, recipient_id))
                    m.id,
                    m.message,
                    m.created_at,
                    m.is_read,
                    CASE 
                        WHEN m.sender_id = ? THEN m.recipient_id 
                        ELSE m.sender_id 
                    END as other_user_id,
                    u.name as other_user_name,
                    u.profile_image as other_user_avatar,
                    (SELECT COUNT(*) FROM messages m2 
                     WHERE ((m2.sender_id = ? AND m2.recipient_id = GREATEST(m.sender_id, m.recipient_id)) 
                        OR (m2.sender_id = GREATEST(m.sender_id, m.recipient_id) AND m2.recipient_id = ?))
                        AND m2.is_read = FALSE) as unread_count
                FROM messages m
                JOIN users u ON CASE 
                    WHEN m.sender_id = ? THEN m.recipient_id 
                    ELSE m.sender_id 
                END = u.id
                WHERE m.sender_id = ? OR m.recipient_id = ?
                ORDER BY GREATEST(m.sender_id, m.recipient_id), LEAST(m.sender_id, m.recipient_id), m.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get message threads: " . $e->getMessage());
            return [];
        }
    }
    
    // Mark messages as read
    public function mark_messages_as_read($user_id, $other_user_id = null) {
        try {
            $sql = "UPDATE messages SET is_read = TRUE WHERE recipient_id = ? AND is_read = FALSE";
            $params = [$user_id];
            
            if ($other_user_id) {
                $sql .= " AND sender_id = ?";
                $params[] = $other_user_id;
            }
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Failed to mark messages as read: " . $e->getMessage());
            return false;
        }
    }
    
    // Delete message (soft delete)
    public function delete_message($message_id, $user_id, $delete_for = 'sender') {
        try {
            $field = $delete_for === 'sender' ? 'is_deleted_by_sender' : 'is_deleted_by_recipient';
            
            $stmt = $this->db->prepare("
                UPDATE messages 
                SET {$field} = TRUE 
                WHERE id = ? AND (sender_id = ? OR recipient_id = ?)
            ");
            return $stmt->execute([$message_id, $user_id, $user_id]);
        } catch (PDOException $e) {
            error_log("Failed to delete message: " . $e->getMessage());
            return false;
        }
    }
    
    // Upload file attachment
    public function upload_attachment($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']) {
        if (!$this->settings['file_upload_enabled']) {
            return ['success' => false, 'message' => 'File uploads are disabled'];
        }
        
        $max_size = $this->parse_file_size($this->settings['max_file_size']);
        
        if ($file['size'] > $max_size) {
            return ['success' => false, 'message' => 'File size exceeds maximum limit'];
        }
        
        $file_info = pathinfo($file['name']);
        $extension = strtolower($file_info['extension']);
        
        if (!in_array($extension, $allowed_types)) {
            return ['success' => false, 'message' => 'File type not allowed'];
        }
        
        $upload_dir = 'uploads/chat_attachments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = uniqid() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'original_name' => $file['name']
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
    
    // Search messages
    public function search_messages($user_id, $query, $limit = 20) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, u.name as sender_name, u.profile_image as sender_avatar
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE (m.sender_id = ? OR m.recipient_id = ?)
                AND (m.message ILIKE ? OR m.subject ILIKE ?)
                ORDER BY m.created_at DESC
                LIMIT ?
            ");
            $search_param = '%' . $query . '%';
            $stmt->execute([$user_id, $user_id, $search_param, $search_param, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to search messages: " . $e->getMessage());
            return [];
        }
    }
    
    // Get unread message count
    public function get_unread_count($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM messages 
                WHERE recipient_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Failed to get unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    // Get message statistics
    public function get_message_stats($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_messages,
                    COUNT(CASE WHEN sender_id = ? THEN 1 END) as sent_messages,
                    COUNT(CASE WHEN recipient_id = ? THEN 1 END) as received_messages,
                    COUNT(CASE WHEN recipient_id = ? AND is_read = FALSE THEN 1 END) as unread_messages,
                    COUNT(CASE WHEN file_attachment IS NOT NULL THEN 1 END) as messages_with_files
                FROM messages 
                WHERE sender_id = ? OR recipient_id = ?
            ");
            $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get message stats: " . $e->getMessage());
            return [];
        }
    }
    
    // Clean up old messages
    public function cleanup_old_messages() {
        try {
            $days = $this->settings['chat_history_days'];
            $cutoff_date = date('Y-m-d', strtotime("-$days days"));
            
            $stmt = $this->db->prepare("
                DELETE FROM messages 
                WHERE created_at < ? 
                AND is_deleted_by_sender = TRUE 
                AND is_deleted_by_recipient = TRUE
            ");
            return $stmt->execute([$cutoff_date]);
        } catch (PDOException $e) {
            error_log("Failed to cleanup old messages: " . $e->getMessage());
            return false;
        }
    }
    
    // Parse file size string to bytes
    private function parse_file_size($size) {
        $unit = strtolower(substr($size, -1));
        $value = (int)substr($size, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int)$size;
        }
    }
    
    // Get conversation with job context
    public function get_job_conversation($user_id, $job_id, $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.*, 
                       u.name as sender_name,
                       u.profile_image as sender_avatar,
                       CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as message_type
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE m.job_id = ?
                AND (m.sender_id = ? OR m.recipient_id = ?)
                ORDER BY m.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$user_id, $job_id, $user_id, $user_id, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get job conversation: " . $e->getMessage());
            return [];
        }
    }
    
    // Block/Unblock user
    public function block_user($user_id, $blocked_user_id) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_blocks (user_id, blocked_user_id)
                VALUES (?, ?)
                ON CONFLICT (user_id, blocked_user_id) DO NOTHING
            ");
            return $stmt->execute([$user_id, $blocked_user_id]);
        } catch (PDOException $e) {
            error_log("Failed to block user: " . $e->getMessage());
            return false;
        }
    }
    
    public function unblock_user($user_id, $blocked_user_id) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM user_blocks 
                WHERE user_id = ? AND blocked_user_id = ?
            ");
            return $stmt->execute([$user_id, $blocked_user_id]);
        } catch (PDOException $e) {
            error_log("Failed to unblock user: " . $e->getMessage());
            return false;
        }
    }
    
    // Check if user is blocked
    public function is_user_blocked($user_id, $other_user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM user_blocks 
                WHERE (user_id = ? AND blocked_user_id = ?) 
                   OR (user_id = ? AND blocked_user_id = ?)
            ");
            $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Failed to check block status: " . $e->getMessage());
            return false;
        }
    }
}

// Initialize messaging system
$messaging_system = new MessagingSystem();
?>
