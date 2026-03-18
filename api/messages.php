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
        $check_stmt->bindParam(1, $recipient_id, PDO::PARAM_INT);
        $check_stmt->execute();
        $check_result = $check_stmt->fetchAll();
        
        if (empty($check_result)) {
            json_response(['success' => false, 'message' => 'Recipient not found'], 404);
        }
        
        $sql = "INSERT INTO messages (sender_id, recipient_id, subject, message, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $recipient_id, PDO::PARAM_INT);
        $stmt->bindParam(3, $subject, PDO::PARAM_STR);
        $stmt->bindParam(4, $message, PDO::PARAM_STR);
        
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
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            
        } elseif ($type === 'sent') {
            $sql = "SELECT m.*, u.name as recipient_name 
                    FROM messages m 
                    LEFT JOIN users u ON m.recipient_id = u.id 
                    WHERE m.sender_id = ? 
                    ORDER BY m.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
            
        } else {
            json_response(['success' => false, 'message' => 'Invalid message type'], 400);
        }
        
        $stmt->execute();
        
        $messages = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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