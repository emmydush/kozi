<?php
require_once '../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['application_id']) || !isset($data['status'])) {
        json_response(['success' => false, 'message' => 'Missing required fields'], 400);
    }
    
    $application_id = (int)$data['application_id'];
    $status = sanitize_input($data['status']);
    $user_id = $_SESSION['user_id'];
    
    // Validate status
    $valid_statuses = ['pending', 'under_review', 'accepted', 'rejected'];
    if (!in_array($status, $valid_statuses)) {
        json_response(['success' => false, 'message' => 'Invalid status'], 400);
    }
    
    // Check if user owns this job (employer check)
    $check_sql = "SELECT ja.id, j.employer_id 
                  FROM job_applications ja
                  JOIN jobs j ON ja.job_id = j.id
                  WHERE ja.id = :application_id AND j.employer_id = :user_id";
    $stmt = $conn->prepare($check_sql);
    $stmt->execute([
        ':application_id' => $application_id,
        ':user_id' => $user_id
    ]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        json_response(['success' => false, 'message' => 'Application not found or unauthorized'], 404);
    }
    
    // Update application status
    $update_sql = "UPDATE job_applications SET status = :status, updated_at = NOW() WHERE id = :id";
    $stmt = $conn->prepare($update_sql);
    $result = $stmt->execute([
        ':status' => $status,
        ':id' => $application_id
    ]);
    
    if ($result) {
        // If application is accepted, update job status to 'filled'
        if ($status === 'accepted') {
            // Get job_id from the application
            $job_sql = "SELECT job_id FROM job_applications WHERE id = :id";
            $job_stmt = $conn->prepare($job_sql);
            $job_stmt->execute([':id' => $application_id]);
            $job_data = $job_stmt->fetch(PDO::FETCH_ASSOC);
            $job_id = $job_data['job_id'];
            
            // Update job status to filled
            $job_update_sql = "UPDATE jobs SET status = 'filled', updated_at = NOW() WHERE id = :id";
            $job_update_stmt = $conn->prepare($job_update_sql);
            $job_update_stmt->execute([':id' => $job_id]);
        }
        
        json_response(['success' => true, 'message' => 'Application status updated successfully']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to update application status'], 500);
    }
    
} catch (Exception $e) {
    error_log("Update Application Status Error: " . $e->getMessage());
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
