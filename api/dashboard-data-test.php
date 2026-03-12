<?php
// Test version without authentication for debugging
require_once '../config.php';

header('Content-Type: application/json');

try {
    // Use a fixed user ID for testing
    $user_id = 1; // John Mukiza (employer)
    $user_role = 'employer';
    
    $data = [
        'posted_jobs' => getEmployerJobStats($user_id),
        'active_bookings' => getEmployerBookingStats($user_id),
        'total_spent' => getEmployerSpending($user_id),
        'workers_hired' => getEmployerHiredWorkers($user_id),
        'recent_jobs' => getEmployerRecentJobs($user_id)
    ];
    
    json_response(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();

// Helper functions (copied from dashboard-data.php)
function getEmployerJobStats($user_id) {
    global $conn;
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as filled
            FROM jobs WHERE employer_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function getEmployerBookingStats($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as active
            FROM bookings 
            WHERE employer_id = ? AND status = 'confirmed'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = $result->fetch_assoc();
    return $row['active'] ?? 0;
}

function getEmployerSpending($user_id) {
    global $conn;
    
    $sql = "SELECT SUM(total_amount) as total
            FROM bookings 
            WHERE employer_id = ? AND status = 'completed'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function getEmployerHiredWorkers($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(DISTINCT worker_id) as workers
            FROM bookings 
            WHERE employer_id = ? AND status IN ('confirmed', 'completed')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = $result->fetch_assoc();
    return $row['workers'] ?? 0;
}

function getEmployerRecentJobs($user_id) {
    global $conn;
    
    $sql = "SELECT j.*, COUNT(ja.id) as application_count
            FROM jobs j
            LEFT JOIN job_applications ja ON j.id = ja.job_id
            WHERE j.employer_id = ?
            ORDER BY j.created_at DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'job_type' => $row['job_type'],
            'salary' => $row['salary'],
            'location' => $row['location'],
            'status' => $row['status'],
            'applications' => $row['application_count'],
            'created_at' => $row['created_at']
        ];
    }
    
    return $jobs;
}
?>
