<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT id, title, description, type, salary, location, work_hours, status, created_at 
            FROM jobs 
            WHERE status = 'active' 
            ORDER BY created_at DESC 
            LIMIT 20";
    $result = $conn->query($sql);
    
    $jobs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['formatted_salary'] = format_currency($row['salary']);
            $row['formatted_date'] = format_date($row['created_at']);
            $jobs[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $jobs]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>