<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

try {
    // Query workers from workers table
    $sql = "SELECT w.id, w.name, w.description, w.type, w.location, w.experience_years as experience, 
                   w.hourly_rate as expected_salary, w.status, w.profile_image,
                   u.availability, u.created_at
            FROM workers w
            LEFT JOIN users u ON w.user_id = u.id
            WHERE w.status = 'active' 
            ORDER BY w.created_at DESC 
            LIMIT 6";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $workers = [];
    if ($result && count($result) > 0) {
        foreach ($result as $row) {
            // Format the worker data for frontend
            $worker = [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description'] ?: 'Experienced and reliable worker',
                'type' => $row['type'],
                'location' => $row['location'] ?: 'Kigali',
                'experience' => $row['experience'],
                'expected_salary' => $row['expected_salary'],
                'availability' => $row['availability'],
                'profile_image' => $row['profile_image'] ?: 'https://picsum.photos/seed/' . $row['id'] . '/400/300.jpg',
                'created_at' => $row['created_at']
            ];
            $workers[] = $worker;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $workers]);
    
} catch (Exception $e) {
    error_log("Workers API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
