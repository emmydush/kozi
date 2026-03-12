<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user_id = $_SESSION['user_id'];

try {
    $conn->select_db("household_connect");
    
    // Get user profile data
    $sql = "SELECT name, email, phone, location, bio, profile_image, skills, experience, expected_salary, availability, role 
            FROM users WHERE id = $user_id";
    
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        // Decode JSON fields
        $data = [
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'location' => $row['location'],
            'bio' => $row['bio'],
            'profile_image' => $row['profile_image'],
            'skills' => $row['skills'] ? json_decode($row['skills'], true) : [],
            'experience' => $row['experience'],
            'expected_salary' => $row['expected_salary'],
            'availability' => $row['availability'],
            'role' => $row['role']
        ];
        
        json_response(['success' => true, 'data' => $data]);
    } else {
        json_response(['success' => false, 'message' => 'User not found'], 404);
    }
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>
