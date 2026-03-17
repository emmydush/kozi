<?php
require_once '../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Please login to apply for jobs'], 401);
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Only workers can apply for jobs
if ($user_role !== 'worker') {
    json_response(['success' => false, 'message' => 'Only workers can apply for jobs'], 403);
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['job_id']) || empty($data['job_id'])) {
    json_response(['success' => false, 'message' => 'Job ID is required'], 400);
}

$job_id = intval($data['job_id']);

try {
    // Check if job exists and is active
    $job_check_sql = "SELECT id, employer_id FROM jobs WHERE id = :job_id AND status = 'active'";
    $stmt = $conn->prepare($job_check_sql);
    $stmt->execute([':job_id' => $job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        json_response(['success' => false, 'message' => 'Job not found or not active'], 404);
    }
    
    // Check if user already applied
    $application_check_sql = "SELECT id FROM job_applications WHERE job_id = :job_id AND worker_id = :worker_id";
    $stmt = $conn->prepare($application_check_sql);
    $stmt->execute([
        ':job_id' => $job_id,
        ':worker_id' => $user_id
    ]);
    $existing_app = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_app) {
        json_response(['success' => false, 'message' => 'You have already applied for this job'], 409);
    }
    
    // Create application
    $application_sql = "INSERT INTO job_applications (job_id, worker_id, status, applied_at) 
                       VALUES (:job_id, :worker_id, 'pending', NOW())";
    $stmt = $conn->prepare($application_sql);
    $result = $stmt->execute([
        ':job_id' => $job_id,
        ':worker_id' => $user_id
    ]);
    
    if ($result) {
        json_response(['success' => true, 'message' => 'Application submitted successfully!']);
    } else {
        json_response(['success' => false, 'message' => 'Failed to submit application. Please try again.'], 500);
    }
    
} catch (Exception $e) {
    error_log("Apply Job Error: " . $e->getMessage());
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
