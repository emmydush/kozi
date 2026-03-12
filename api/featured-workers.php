<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT id, name, description, type, location, experience, hourly_rate, rating, 
                   phone, email, profile_image 
            FROM workers 
            WHERE status = 'active' 
            ORDER BY rating DESC, created_at DESC 
            LIMIT 20";
    $result = $conn->query($sql);
    
    $workers = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['formatted_rate'] = format_currency($row['hourly_rate']);
            $row['profile_image'] = $row['profile_image'] ?: 'https://picsum.photos/seed/' . $row['id'] . '/400/300.jpg';
            $workers[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $workers]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>