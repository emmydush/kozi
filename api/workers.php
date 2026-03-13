<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    // Query workers from users table where role is 'worker'
    $sql = "SELECT id, name, email, phone, location, bio, skills, experience, expected_salary, availability, profile_image, created_at 
            FROM users 
            WHERE role = 'worker' 
            ORDER BY created_at DESC 
            LIMIT 6";
    $result = $conn->query($sql);
    
    $workers = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Format the worker data for frontend
            $worker = [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['bio'] ?: 'Experienced and reliable worker',
                'type' => json_decode($row['skills'] ?: '[]', true),
                'location' => $row['location'] ?: 'Kigali',
                'experience' => $row['experience'],
                'expected_salary' => $row['expected_salary'],
                'availability' => $row['availability'],
                'phone' => $row['phone'],
                'profile_image' => $row['profile_image'],
                'created_at' => $row['created_at']
            ];
            $workers[] = $worker;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $workers]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>