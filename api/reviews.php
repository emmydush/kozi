<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Post a review
        if (!is_logged_in()) {
            json_response(['success' => false, 'message' => 'Authentication required'], 401);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['worker_id', 'rating', 'comment'];
        $errors = validate_required($required_fields, $data);
        
        if (!empty($errors)) {
            json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 400);
        }
        
        $worker_id = (int)$data['worker_id'];
        $rating = (int)$data['rating'];
        $comment = sanitize_input($data['comment']);
        $user_id = $_SESSION['user_id'];
        
        if ($rating < 1 || $rating > 5) {
            json_response(['success' => false, 'message' => 'Rating must be between 1 and 5'], 400);
        }
        
        $sql = "INSERT INTO reviews (worker_id, user_id, rating, comment, created_at) 
                VALUES (:worker_id, :user_id, :rating, :comment, NOW())";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            ':worker_id' => $worker_id,
            ':user_id' => $user_id,
            ':rating' => $rating,
            ':comment' => $comment
        ]);
        
        if ($result) {
            json_response(['success' => true, 'message' => 'Review posted successfully']);
        } else {
            json_response(['success' => false, 'message' => 'Failed to post review'], 500);
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get reviews for a worker
        if (!isset($_GET['worker_id']) || !is_numeric($_GET['worker_id'])) {
            json_response(['success' => false, 'message' => 'Worker ID is required'], 400);
        }
        
        $worker_id = (int)$_GET['worker_id'];
        
        $sql = "SELECT r.*, u.name as reviewer_name 
                FROM reviews r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.worker_id = :worker_id
                ORDER BY r.created_at DESC 
                LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([':worker_id' => $worker_id]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $reviews = [];
        if ($result && count($result) > 0) {
            $reviews = $result;
        }
        
        json_response(['success' => true, 'data' => $reviews]);
        
    } else {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Reviews API Error: " . $e->getMessage());
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
