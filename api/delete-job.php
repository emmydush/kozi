<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role and ID
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Only employers should access this API
if ($user_role !== 'employer') {
    json_response(['success' => false, 'message' => 'Unauthorized'], 403);
    exit();
}

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['job_id'])) {
        json_response(['success' => false, 'message' => 'Job ID is required'], 400);
        exit();
    }
    
    $job_id = (int)$input['job_id'];
    
    try {
        // Check if job exists and belongs to this employer
        $check_sql = "SELECT id, employer_id FROM jobs WHERE id = :job_id";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $check_stmt->execute();
        $job = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$job || $job['employer_id'] != $user_id) {
            json_response(['success' => false, 'message' => 'Job not found or access denied'], 404);
            exit();
        }
        
        // Check if job has applications
        $apps_sql = "SELECT COUNT(*) as count FROM job_applications WHERE job_id = :job_id AND status != 'rejected'";
        $apps_stmt = $conn->prepare($apps_sql);
        $apps_stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $apps_stmt->execute();
        $applications = $apps_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($applications['count'] > 0) {
            json_response(['success' => false, 'message' => 'Cannot delete job with active applications'], 400);
            exit();
        }
        
        // Delete the job
        $delete_sql = "DELETE FROM jobs WHERE id = :job_id AND employer_id = :user_id";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $delete_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if ($delete_stmt->execute()) {
            json_response(['success' => true, 'message' => 'Job deleted successfully']);
        } else {
            json_response(['success' => false, 'message' => 'Failed to delete job'], 500);
        }
        
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
    }
} else {
    json_response(['success' => false, 'message' => 'Method not allowed'], 405);
}
?>
