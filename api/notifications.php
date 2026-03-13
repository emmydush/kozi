<?php
require_once '../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

try {
    $notifications = [];
    $unread_count = 0;
    
    if ($user_role === 'employer') {
        // Employer notifications
        $sql = "SELECT 'job_application' as type, j.title as message, ja.created_at, ja.id as reference_id
                FROM job_applications ja
                JOIN jobs j ON ja.job_id = j.id
                WHERE j.employer_id = ? AND ja.status = 'pending'
                ORDER BY ja.created_at DESC
                LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'type' => $row['type'],
                'message' => 'New application for: ' . $row['message'],
                'time' => format_time_ago($row['created_at']),
                'link' => 'my-applications.php',
                'icon' => 'fas fa-briefcase'
            ];
            $unread_count++;
        }
        
        // Payment confirmations
        $paymentSql = "SELECT 'payment' as type, 'Payment confirmed' as message, b.created_at, b.id as reference_id
                      FROM bookings b
                      WHERE b.user_id = ? AND b.payment_status = 'confirmed'
                      AND b.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                      ORDER BY b.created_at DESC
                      LIMIT 3";
        $stmt = $conn->prepare($paymentSql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'type' => $row['type'],
                'message' => $row['message'],
                'time' => format_time_ago($row['created_at']),
                'link' => 'bookings.php',
                'icon' => 'fas fa-credit-card'
            ];
            $unread_count++;
        }
        
    } else {
        // Worker notifications
        $sql = "SELECT 'application_status' as type, 
                       CASE 
                           WHEN ja.status = 'accepted' THEN 'Application accepted!'
                           WHEN ja.status = 'under_review' THEN 'Application under review'
                           ELSE 'Application status updated'
                       END as message,
                       ja.updated_at as created_at, ja.id as reference_id
                FROM job_applications ja
                WHERE ja.worker_id = ? AND ja.status IN ('accepted', 'under_review')
                ORDER BY ja.updated_at DESC
                LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'type' => $row['type'],
                'message' => $row['message'],
                'time' => format_time_ago($row['created_at']),
                'link' => 'my-applications.php',
                'icon' => 'fas fa-check-circle'
            ];
            $unread_count++;
        }
    }
    
    json_response([
        'success' => true,
        'data' => [
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Notifications API Error: " . $e->getMessage());
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
