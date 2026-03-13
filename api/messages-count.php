<?php
require_once '../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

try {
    // Get unread message count
    $sql = "SELECT COUNT(*) as unread_count 
            FROM messages m
            WHERE m.receiver_id = ? AND m.is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_count = $result ? $result->fetch_assoc()['unread_count'] : 0;
    
    // Get recent messages
    $recentSql = "SELECT m.sender_id, u.name as sender_name, m.message, m.created_at
                  FROM messages m
                  JOIN users u ON m.sender_id = u.id
                  WHERE m.receiver_id = ? 
                  ORDER BY m.created_at DESC
                  LIMIT 5";
    $stmt = $conn->prepare($recentSql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recent_messages = [];
    while ($row = $result->fetch_assoc()) {
        $recent_messages[] = [
            'sender_name' => $row['sender_name'],
            'message' => substr($row['message'], 0, 50) . (strlen($row['message']) > 50 ? '...' : ''),
            'time' => format_time_ago($row['created_at'])
        ];
    }
    
    json_response([
        'success' => true,
        'data' => [
            'unread_count' => $unread_count,
            'recent_messages' => $recent_messages
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Messages API Error: " . $e->getMessage());
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

function format_time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('M j, Y', $time);
    }
}

$stmt->close();
$conn->close();
?>
