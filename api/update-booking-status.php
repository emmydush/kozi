<?php
require_once '../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Only employers can update booking status
if ($user_role !== 'employer') {
    json_response(['success' => false, 'message' => 'Only employers can update booking status'], 403);
}

header('Content-Type: application/json');

try {
    // Get POST data
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
    $new_status = isset($_POST['status']) ? sanitize_input($_POST['status']) : null;
    
    if (!$booking_id || !$new_status) {
        json_response(['success' => false, 'message' => 'Missing required fields'], 400);
    }
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        json_response(['success' => false, 'message' => 'Invalid status'], 400);
    }
    
    // Check if booking exists and belongs to this employer
    $check_sql = "SELECT id, worker_id FROM bookings WHERE id = :booking_id AND user_id = :user_id";
    $stmt = $conn->prepare($check_sql);
    $stmt->execute([
        ':booking_id' => $booking_id,
        ':user_id' => $user_id
    ]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        json_response(['success' => false, 'message' => 'Booking not found or access denied'], 404);
    }
    
    // Update booking status
    $update_sql = "UPDATE bookings SET status = :status, updated_at = NOW() WHERE id = :id";
    $stmt = $conn->prepare($update_sql);
    $result = $stmt->execute([
        ':status' => $new_status,
        ':id' => $booking_id
    ]);
    
    if ($result) {
        // Create notification for worker about status change
        $notification_sql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                            VALUES (:worker_id, :title, :message, 'booking', NOW())";
        $notification_stmt = $conn->prepare($notification_sql);
        
        $title = 'Booking Status Updated';
        $message = "Your booking status has been updated to: " . ucfirst($new_status);
        
        try {
            $notification_stmt->execute([
                ':worker_id' => $booking['worker_id'],
                ':title' => $title,
                ':message' => $message
            ]);
        } catch (Exception $e) {
            // Notification creation failure is not critical
            error_log("Notification creation failed: " . $e->getMessage());
        }
        
        json_response(['success' => true, 'message' => 'Booking status updated successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to update booking status'], 500);
    }
    
} catch (Exception $e) {
    error_log("Update Booking Status Error: " . $e->getMessage());
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
