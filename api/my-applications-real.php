<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user_id = $_SESSION['user_id'];

// Only workers can access this
if ($_SESSION['user_role'] !== 'worker') {
    json_response(['success' => false, 'message' => 'Access denied'], 403);
}

try {
    $sql = "SELECT ja.*, j.title, j.salary, j.location, j.type as job_type, j.work_hours,
                   u.name as employer_name, u.phone as employer_phone
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            JOIN users u ON j.employer_id = u.id
            WHERE ja.worker_id = :user_id
            ORDER BY ja.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $applications = [];
    if ($result && count($result) > 0) {
        foreach ($result as $row) {
            $applications[] = [
                'id' => $row['id'],
                'job_id' => $row['job_id'],
                'title' => $row['title'],
                'salary' => $row['salary'],
                'location' => $row['location'],
                'job_type' => $row['job_type'],
                'work_hours' => $row['work_hours'],
                'employer_name' => $row['employer_name'],
                'employer_phone' => $row['employer_phone'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'] ?? null
            ];
        }
    }
    
    // Get statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                  FROM job_applications WHERE worker_id = :user_id";
    
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->execute([':user_id' => $user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    json_response([
        'success' => true, 
        'applications' => $applications,
        'statistics' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("My Applications Error: " . $e->getMessage());
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
