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

// Get POST data
$booking_id = intval($_POST['booking_id']);
$new_status = sanitize_input($_POST['status']);

// Validate status
$valid_statuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    json_response(['success' => false, 'message' => 'Invalid status'], 400);
}

// Check if booking exists and belongs to this employer
$check_sql = "SELECT id FROM bookings WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param('ii', $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    json_response(['success' => false, 'message' => 'Booking not found or access denied'], 404);
}

// Update booking status
$update_sql = "UPDATE bookings SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param('si', $new_status, $booking_id);

if ($stmt->execute()) {
    // Create notification for worker about status change
    $notification_sql = "INSERT INTO notifications (user_id, title, message, type) 
                        SELECT worker_id, ?, ?, 'booking' 
                        FROM bookings WHERE id = ?";
    $notification_stmt = $conn->prepare($notification_sql);
    
    $title = 'Booking Status Updated';
    $message = "Your booking status has been updated to: " . ucfirst($new_status);
    $notification_stmt->bind_param('ssi', $title, $message, $booking_id);
    $notification_stmt->execute();
    
    json_response(['success' => true, 'message' => 'Booking status updated successfully']);
} else {
    json_response(['success' => false, 'message' => 'Failed to update booking status'], 500);
}

$stmt->close();
$conn->close();
?>
