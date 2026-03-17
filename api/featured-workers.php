<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT w.id, w.name, w.description, w.type, w.location, w.experience_years as experience, 
                   w.hourly_rate, w.average_rating as rating, 
                   u.phone, u.email, u.profile_image
            FROM workers w
            JOIN users u ON w.user_id = u.id
            WHERE w.status = 'active' 
            ORDER BY w.average_rating DESC, w.created_at DESC 
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $workers = [];
    if ($result && count($result) > 0) {
        foreach ($result as $row) {
            $row['formatted_rate'] = format_currency($row['hourly_rate'] ?? 0);
            $row['profile_image'] = $row['profile_image'] ?: 'https://picsum.photos/seed/' . $row['id'] . '/400/300.jpg';
            $workers[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $workers]);
    
} catch (Exception $e) {
    error_log("Featured Workers Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
