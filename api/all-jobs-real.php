<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT j.*, u.name as employer_name, u.phone as employer_phone
            FROM jobs j
            JOIN users u ON j.employer_id = u.id
            WHERE j.status = 'active'
            ORDER BY j.created_at DESC";
    
    $result = $conn->query($sql);
    $jobs = [];
    
    while ($row = $result->fetch_assoc()) {
        $jobs[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'job_type' => $row['job_type'],
            'salary' => $row['salary'],
            'location' => $row['location'],
            'work_hours' => $row['work_hours'],
            'employer_name' => $row['employer_name'],
            'employer_phone' => $row['employer_phone'],
            'created_at' => $row['created_at']
        ];
    }
    
    json_response(['success' => true, 'jobs' => $jobs]);
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>
