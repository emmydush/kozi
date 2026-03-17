<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

try {
    // PostgreSQL doesn't need select_db - connection is already to the correct database
    
    // Get user profile data from users table
    $sql = "SELECT name, email, phone, profile_image FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
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
        $worker_sql = "SELECT experience_years, skills, availability FROM workers WHERE user_id = :user_id";
        $worker_stmt = $conn->prepare($worker_sql);
        $worker_stmt->bindParam(':user_id', $user_id);
        $worker_stmt->execute();
        $worker_row = $worker_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($worker_row) {
            $data['experience'] = $worker_row['experience_years'];
            $data['skills'] = $worker_row['skills'] ? json_decode($worker_row['skills'], true) : [];
            $data['availability'] = $worker_row['availability'];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
