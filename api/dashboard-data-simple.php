<?php
require_once '../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

header('Content-Type: application/json');

try {
    $conn->select_db("household_connect");
    
    if ($user_role === 'employer') {
        // Employer dashboard data
        $data = [];
        
        // Get posted jobs count
        $postedJobsSql = "SELECT COUNT(*) as total FROM jobs WHERE employer_id = ?";
        $stmt = $conn->prepare($postedJobsSql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $postedJobsResult = $stmt->get_result();
        $postedJobs = $postedJobsResult ? $postedJobsResult->fetch_assoc()['total'] : 0;
        
        // Get active bookings count
        $bookingsSql = "SELECT COUNT(*) as active FROM bookings WHERE user_id = ? AND status = 'confirmed'";
        $stmt = $conn->prepare($bookingsSql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $bookingsResult = $stmt->get_result();
        $activeBookings = $bookingsResult ? $bookingsResult->fetch_assoc()['active'] : 0;
        
        // Get total spent
        $spentSql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE user_id = ? AND status = 'completed'";
        $stmt = $conn->prepare($spentSql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $spentResult = $stmt->get_result();
        $totalSpent = $spentResult ? ($spentResult->fetch_assoc()['total'] ?: 0) : 0;
        
        // Get workers hired
        $workersSql = "SELECT COUNT(DISTINCT worker_id) as workers FROM bookings WHERE user_id = ? AND status IN ('confirmed', 'completed')";
        $stmt = $conn->prepare($workersSql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $workersResult = $stmt->get_result();
        $workersHired = $workersResult ? $workersResult->fetch_assoc()['workers'] : 0;
        
        // Get recent jobs
        $recentJobsSql = "SELECT j.*, COUNT(ja.id) as application_count 
                          FROM jobs j 
                          LEFT JOIN job_applications ja ON j.id = ja.job_id 
                          WHERE j.employer_id = ? 
                          GROUP BY j.id 
                          ORDER BY j.created_at DESC 
                          LIMIT 5";
        $stmt = $conn->prepare($recentJobsSql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $recentJobsResult = $stmt->get_result();
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
        
    } else {
        // Worker dashboard data
        $data = [];
        
        // Get jobs applied statistics
        $appsSql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted
                    FROM job_applications WHERE worker_id = ?";
        $stmt = $conn->prepare($appsSql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $appsResult = $stmt->get_result();
        $jobsApplied = $appsResult ? $appsResult->fetch_assoc() : ['total' => 0, 'pending' => 0, 'under_review' => 0, 'accepted' => 0];
        
        // Get active jobs count
        $activeSql = "SELECT COUNT(*) as active FROM job_applications WHERE worker_id = ? AND status = 'accepted'";
        $stmt = $conn->prepare($activeSql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $activeResult = $stmt->get_result();
        $activeJobs = $activeResult ? $activeResult->fetch_assoc()['active'] : 0;
        
        // Get available jobs (active jobs user hasn't applied to)
        $availableSql = "SELECT j.id, j.title, j.salary, j.location, u.name as employer_name
                           FROM jobs j 
                           JOIN users u ON j.employer_id = u.id 
                           WHERE j.status = 'active' 
                           AND j.id NOT IN (
                               SELECT job_id FROM job_applications WHERE worker_id = ?
                           )
                           ORDER BY j.created_at DESC 
                           LIMIT 5";
        $stmt = $conn->prepare($availableSql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $availableResult = $stmt->get_result();
        $availableJobs = [];
        
        if ($availableResult) {
            while ($row = $availableResult->fetch_assoc()) {
                $availableJobs[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'salary' => $row['salary'],
                    'location' => $row['location'],
                    'employer_name' => $row['employer_name']
                ];
            }
        }
        
        $data = [
            'jobs_applied' => $jobsApplied,
            'active_jobs' => ['active' => $activeJobs],
            'available_jobs' => $availableJobs
        ];
    }
    
    json_response(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$stmt->close();
$conn->close();
?>
