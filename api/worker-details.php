<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $worker_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$worker_id) {
        json_response(['success' => false, 'message' => 'Worker ID is required'], 400);
    }
    
    $sql = "SELECT w.*, u.name, u.email, u.phone, u.profile_image as user_profile_image,
                   COUNT(r.id) as review_count, AVG(r.rating) as avg_rating 
            FROM workers w 
            LEFT JOIN users u ON w.user_id = u.id 
            LEFT JOIN reviews r ON w.id = r.worker_id 
            WHERE w.id = :worker_id AND w.status = 'active' 
            GROUP BY w.id, u.name, u.email, u.phone, u.profile_image";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':worker_id', $worker_id, PDO::PARAM_INT);
    $stmt->execute();
    $worker = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$worker) {
        json_response(['success' => false, 'message' => 'Worker not found'], 404);
    }
    
    $worker['formatted_rate'] = isset($worker['hourly_rate']) ? format_currency($worker['hourly_rate']) : 'RWF 0';
    $worker['avg_rating'] = $worker['avg_rating'] ?: 0;
    $worker['review_count'] = $worker['review_count'] ?: 0;
    
    $worker['profile_image'] = $worker['user_profile_image'] ?: 'https://picsum.photos/seed/' . $worker['id'] . '/400/300.jpg';
    
    $reviews_sql = "SELECT r.*, u.name as reviewer_name 
                    FROM reviews r 
                    LEFT JOIN users u ON r.user_id = u.id 
                    WHERE r.worker_id = :worker_id 
                    ORDER BY r.created_at DESC 
                    LIMIT 10";
    
    $reviews_stmt = $conn->prepare($reviews_sql);
    $reviews_stmt->bindParam(':worker_id', $worker_id, PDO::PARAM_INT);
    $reviews_stmt->execute();
    $reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $worker['reviews'] = $reviews;
    
    json_response(['success' => true, 'data' => $worker]);
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

?>