<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['title', 'description', 'type', 'salary', 'location', 'work_hours'];
    $errors = validate_required($required_fields, $data);
    
    if (!empty($errors)) {
        json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 400);
    }
    
    $title = sanitize_input($data['title']);
    $description = sanitize_input($data['description']);
    $type = sanitize_input($data['type']);
    $salary = (int)$data['salary'];
    $location = sanitize_input($data['location']);
    $work_hours = sanitize_input($data['work_hours']);
    $employer_id = is_logged_in() ? $_SESSION['user_id'] : null;
    $status = 'active';
    
    $sql = "INSERT INTO jobs (title, description, type, salary, location, work_hours, employer_id, status, created_at) 
            VALUES (:title, :description, :type, :salary, :location, :work_hours, :employer_id, :status, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':title' => $title,
        ':description' => $description,
        ':type' => $type,
        ':salary' => $salary,
        ':location' => $location,
        ':work_hours' => $work_hours,
        ':employer_id' => $employer_id,
        ':status' => $status
    ]);
    
    // Get the last inserted ID
    $last_id = $conn->lastInsertId();
    
    json_response(['success' => true, 'message' => 'Job posted successfully', 'job_id' => $last_id]);
    
} catch (Exception $e) {
    error_log("Jobs API Error: " . $e->getMessage());
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
