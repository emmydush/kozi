<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    if (!is_logged_in()) {
        json_response(['success' => false, 'message' => 'Authentication required'], 401);
    }
    
    $user_id = $_SESSION['user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create a booking
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['worker_id', 'start_date', 'end_date', 'service_type'];
        $errors = validate_required($required_fields, $data);
        
        if (!empty($errors)) {
            json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 400);
        }
        
        $worker_id = (int)$data['worker_id'];
        $start_date = sanitize_input($data['start_date']);
        $end_date = sanitize_input($data['end_date']);
        $service_type = sanitize_input($data['service_type']);
        $status = 'pending';
        
        // Check if worker exists
        $check_sql = "SELECT id FROM workers WHERE id = ? AND status = 'active'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $worker_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            json_response(['success' => false, 'message' => 'Worker not found or inactive'], 404);
        }
        
        // Check date format
        if (!strtotime($start_date) || !strtotime($end_date)) {
            json_response(['success' => false, 'message' => 'Invalid date format'], 400);
        }
        
        $sql = "INSERT INTO bookings (worker_id, user_id, start_date, end_date, service_type, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissss", $worker_id, $user_id, $start_date, $end_date, $service_type, $status);
        
        if ($stmt->execute()) {
            json_response(['success' => true, 'message' => 'Booking created successfully', 'booking_id' => $stmt->insert_id]);
        } else {
            json_response(['success' => false, 'message' => 'Failed to create booking'], 500);
        }
        
        $stmt->close();
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get bookings
        $type = $_GET['type'] ?? 'all';
        
        if ($type === 'all') {
            $sql = "SELECT b.*, w.name as worker_name, w.type as worker_type 
                    FROM bookings b 
                    LEFT JOIN workers w ON b.worker_id = w.id 
                    WHERE b.user_id = ? 
                    ORDER BY b.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            
        } elseif ($type === 'pending') {
            $sql = "SELECT b.*, w.name as worker_name, w.type as worker_type 
                    FROM bookings b 
                    LEFT JOIN workers w ON b.worker_id = w.id 
                    WHERE b.user_id = ? AND b.status = 'pending' 
                    ORDER BY b.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            
        } elseif ($type === 'confirmed') {
            $sql = "SELECT b.*, w.name as worker_name, w.type as worker_type 
                    FROM bookings b 
                    LEFT JOIN workers w ON b.worker_id = w.id 
                    WHERE b.user_id = ? AND b.status = 'confirmed' 
                    ORDER BY b.created_at DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            
        } else {
            json_response(['success' => false, 'message' => 'Invalid booking type'], 400);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        json_response(['success' => true, 'data' => $bookings]);
        
    } else {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>