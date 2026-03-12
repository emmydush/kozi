<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    if (!is_logged_in()) {
        json_response(['success' => false, 'message' => 'Authentication required'], 401);
    }
    
    $user_id = $_SESSION['user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Send a message
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['recipient_id', 'subject', 'message'];
        $errors = validate_required($required_fields, $data);
        
        if (!empty($errors)) {
            json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 400);
        }
        
        $recipient_id = (int)$data['recipient_id'];
        $subject = sanitize_input($data['subject']);
        $message = sanitize_input($data['message']);
        
        // Check if recipient exists
        $check_sql = "SELECT id FROM users WHERE id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $recipient_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            json_response(['success' => false, 'message' => 'Recipient not found'], 404);
        }
        
        $sql = "INSERT INTO messages (sender_id, recipient_id, subject, message, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $user_id, $recipient_id, $subject, $message);
        
        if ($stmt->execute()) {
            json_response(['success' => true, 'message' => 'Message sent successfully']);
        } else {
            json_response(['success' => false, 'message' => 'Failed to send message'], 500);
        }
        
        $stmt->close();
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get messages
        $type = $_GET['type'] ?? 'inbox';
        
        if ($type === 'inbox') {
            $sql = "SELECT m.*, u.name as sender_name 
                    FROM messages m 
                    LEFT JOIN users u ON m.sender_id = u.id 
                    WHERE m.recipient_id = ? 
                    ORDER BY m.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            
        } elseif ($type === 'sent') {
            $sql = "SELECT m.*, u.name as recipient_name 
                    FROM messages m 
                    LEFT JOIN users u ON m.recipient_id = u.id 
                    WHERE m.sender_id = ? 
                    ORDER BY m.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            
        } else {
            json_response(['success' => false, 'message' => 'Invalid message type'], 400);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        json_response(['success' => true, 'data' => $messages]);
        
    } else {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>