<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT j.*, u.name as employer_name, u.phone as employer_phone
            FROM jobs j
            JOIN users u ON j.employer_id = u.id
            WHERE j.status = 'active'
            ORDER BY j.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $jobs = [];
    
    if ($result && count($result) > 0) {
        foreach ($result as $row) {
            $jobs[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'job_type' => $row['type'],
                'salary' => $row['salary'],
                'location' => $row['location'],
                'work_hours' => $row['work_hours'],
                'employer_name' => $row['employer_name'],
                'employer_phone' => $row['employer_phone'],
                'created_at' => $row['created_at']
            ];
        }
    }
    
    json_response(['success' => true, 'jobs' => $jobs]);
    
} catch (Exception $e) {
    error_log("All jobs real API Error: " . $e->getMessage());
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
