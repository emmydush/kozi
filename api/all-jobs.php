<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT id, title, description, type, salary, location, work_hours, status, created_at 
            FROM jobs 
            WHERE status = 'active' 
            ORDER BY created_at DESC 
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $jobs = [];
    if ($result && count($result) > 0) {
        foreach ($result as $row) {
            $row['formatted_salary'] = format_currency($row['salary']);
            $row['formatted_date'] = format_date($row['created_at']);
            $jobs[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $jobs]);
    
} catch (Exception $e) {
    error_log("All jobs API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
