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
    
    $sql = "INSERT INTO jobs (title, description, type, salary, location, work_hours, employer_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisssi", $title, $description, $type, $salary, $location, $work_hours, $employer_id, $status);
    
    if ($stmt->execute()) {
        json_response(['success' => true, 'message' => 'Job posted successfully', 'job_id' => $stmt->insert_id]);
    } else {
        json_response(['success' => false, 'message' => 'Failed to post job'], 500);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>