<?php
// Simple working API without authentication for testing
require_once '../config.php';

header('Content-Type: application/json');

try {
    $conn->select_db("household_connect");
    
    // Get employer data (John Mukiza - ID 2)
    $employerId = 2;
    
    // Get posted jobs count
    $postedJobsResult = $conn->query("SELECT COUNT(*) as total FROM jobs WHERE employer_id = $employerId");
    $postedJobs = $postedJobsResult ? $postedJobsResult->fetch_assoc()['total'] : 0;
    
    // Get active bookings count
    $bookingsResult = $conn->query("SELECT COUNT(*) as active FROM bookings WHERE employer_id = $employerId AND status = 'confirmed'");
    $activeBookings = $bookingsResult ? $bookingsResult->fetch_assoc()['active'] : 0;
    
    // Get total spent
    $spentResult = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE employer_id = $employerId AND status = 'completed'");
    $totalSpent = $spentResult ? ($spentResult->fetch_assoc()['total'] ?: 0) : 0;
    
    // Get workers hired
    $workersResult = $conn->query("SELECT COUNT(DISTINCT worker_id) as workers FROM bookings WHERE employer_id = $employerId AND status IN ('confirmed', 'completed')");
    $workersHired = $workersResult ? $workersResult->fetch_assoc()['workers'] : 0;
    
    // Get recent jobs
    $recentJobsResult = $conn->query("SELECT j.*, COUNT(ja.id) as application_count FROM jobs j LEFT JOIN job_applications ja ON j.id = ja.job_id WHERE j.employer_id = $employerId ORDER BY j.created_at DESC LIMIT 5");
    $recentJobs = [];
    
    if ($recentJobsResult) {
        while ($row = $recentJobsResult->fetch_assoc()) {
            $recentJobs[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'type' => $row['type'],
                'salary' => $row['salary'],
                'location' => $row['location'],
                'status' => $row['status'],
                'applications' => $row['application_count'],
                'created_at' => $row['created_at']
            ];
        }
    }
    
    $data = [
        'posted_jobs' => ['total' => $postedJobs],
        'active_bookings' => ['active' => $activeBookings],
        'total_spent' => ['total' => $totalSpent],
        'workers_hired' => ['workers' => $workersHired],
        'recent_jobs' => $recentJobs
    ];
    
    json_response(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>
