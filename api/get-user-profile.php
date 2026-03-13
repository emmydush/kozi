<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user_id = $_SESSION['user_id'];

if (!$user_id) {
    json_response(['success' => false, 'message' => 'User not logged in'], 401);
}

try {
    $conn->select_db("household_connect");
    
    // Get user profile data from users table
    $sql = "SELECT name, email, phone, profile_image FROM users WHERE id = $user_id";
    $result = $conn->query($sql);
    
    if (!$result || !$row = $result->fetch_assoc()) {
        json_response(['success' => false, 'message' => 'User not found'], 404);
    }
    
    $data = [
        'name' => $row['name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'profile_image' => $row['profile_image'],
        'location' => '',
        'bio' => '',
        'skills' => [],
        'experience' => '',
        'expected_salary' => '',
        'availability' => ''
    ];
    
    // If user is a worker, get additional data from workers table
    if ($_SESSION['user_role'] === 'worker') {
        $worker_sql = "SELECT experience_years, skills, availability FROM workers WHERE user_id = $user_id";
        $worker_result = $conn->query($worker_sql);
        
        if ($worker_result && $worker_row = $worker_result->fetch_assoc()) {
            $data['experience'] = $worker_row['experience_years'];
            $data['skills'] = $worker_row['skills'] ? json_decode($worker_row['skills'], true) : [];
            $data['availability'] = $worker_row['availability'];
        }
    }
    
    json_response(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>
