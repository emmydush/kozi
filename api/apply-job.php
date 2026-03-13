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

// Check if job exists and is active
$job_check_sql = "SELECT id, employer_id FROM jobs WHERE id = ? AND status = 'active'";
$stmt = $conn->prepare($job_check_sql);
$stmt->bind_param('i', $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    json_response(['success' => false, 'message' => 'Job not found or not active'], 404);
}

$job = $result->fetch_assoc();

// Check if user already applied
$application_check_sql = "SELECT id FROM job_applications WHERE job_id = ? AND worker_id = ?";
$stmt = $conn->prepare($application_check_sql);
$stmt->bind_param('ii', $job_id, $user_id);
$stmt->execute();
$app_result = $stmt->get_result();

if ($app_result->num_rows > 0) {
    json_response(['success' => false, 'message' => 'You have already applied for this job'], 409);
}

// Create application
$application_sql = "INSERT INTO job_applications (job_id, worker_id, status) VALUES (?, ?, 'pending')";
$stmt = $conn->prepare($application_sql);
$stmt->bind_param('ii', $job_id, $user_id);

if ($stmt->execute()) {
    // Create notification for employer (optional - you can implement this later)
    json_response(['success' => true, 'message' => 'Application submitted successfully!']);
} else {
    json_response(['success' => false, 'message' => 'Failed to submit application. Please try again.'], 500);
}

$stmt->close();
$conn->close();
?>
