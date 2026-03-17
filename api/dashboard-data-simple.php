<?php
require_once '../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Prevent caching
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // PostgreSQL doesn't need select_db - connection is already to the correct database
    
    if ($user_role === 'employer') {
        // Employer dashboard data
        $data = [];
        
        // Get posted jobs count
        $postedJobsSql = "SELECT COUNT(*) as total FROM jobs WHERE employer_id = :user_id";
        $stmt = $conn->prepare($postedJobsSql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $postedJobsResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $postedJobs = $postedJobsResult ? $postedJobsResult['total'] : 0;
        
        // Get active bookings count
        $bookingsSql = "SELECT COUNT(*) as active FROM bookings WHERE user_id = :user_id AND status = 'confirmed'";
        $stmt = $conn->prepare($bookingsSql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $bookingsResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $activeBookings = $bookingsResult ? $bookingsResult['active'] : 0;
        
        // Get total spent
        $spentSql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM bookings WHERE user_id = :user_id AND status = 'completed'";
        $stmt = $conn->prepare($spentSql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $spentResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalSpent = $spentResult ? ($spentResult['total'] ?: 0) : 0;
        
        // Get workers hired
        $workersSql = "SELECT COUNT(DISTINCT worker_id) as workers FROM bookings WHERE user_id = :user_id AND status IN ('confirmed', 'completed')";
        $stmt = $conn->prepare($workersSql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $workersResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $workersHired = $workersResult ? $workersResult['workers'] : 0;
        
        // Get recent jobs
        $recentJobsSql = "SELECT j.*, COUNT(ja.id) as application_count 
                          FROM jobs j 
                          LEFT JOIN job_applications ja ON j.id = ja.job_id 
                          WHERE j.employer_id = :user_id 
                          GROUP BY j.id 
                          ORDER BY j.created_at DESC 
                          LIMIT 5";
        $stmt = $conn->prepare($recentJobsSql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $recentJobs = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
                    FROM job_applications WHERE worker_id = :user_id";
        $stmt = $conn->prepare($appsSql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $appsResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $jobsApplied = $appsResult ? $appsResult : ['total' => 0, 'pending' => 0, 'under_review' => 0, 'accepted' => 0];
        
        // Get active jobs count
        $activeSql = "SELECT COUNT(*) as active FROM job_applications WHERE worker_id = :user_id AND status = 'accepted'";
        $stmt = $conn->prepare($activeSql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $activeResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $activeJobs = $activeResult ? $activeResult['active'] : 0;
        
        // Get all available jobs with application status
        $availableSql = "SELECT j.id, j.title, j.salary, j.location, u.name as employer_name,
                            CASE 
                                WHEN ja.id IS NOT NULL THEN 'applied'
                                ELSE 'new'
                            END as application_status
                            FROM jobs j 
                            JOIN users u ON j.employer_id = u.id 
                            LEFT JOIN job_applications ja ON j.id = ja.job_id AND ja.worker_id = :user_id
                            WHERE j.status = 'active'
                            ORDER BY j.created_at DESC 
                            LIMIT 5";
        
        error_log("Available jobs SQL for worker ID $user_id: " . $availableSql);
        
        $stmt = $conn->prepare($availableSql);
        if (!$stmt) {
            error_log("Failed to prepare available jobs statement");
            throw new Exception("Database query preparation failed");
        }
        
        $stmt->bindParam(':user_id', $user_id);
        if (!$stmt->execute()) {
            error_log("Failed to execute available jobs statement");
            throw new Exception("Database query execution failed");
        }
        
        $availableJobs = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $availableJobs[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'salary' => $row['salary'],
                    'location' => $row['location'],
                    'employer_name' => $row['employer_name'],
                    'status' => $row['application_status']
                ];
        }
        
        error_log("Found " . count($availableJobs) . " available jobs for worker ID $user_id");
        
        $data = [
            'jobs_applied' => $jobsApplied,
            'active_jobs' => ['active' => $activeJobs],
            'available_jobs' => $availableJobs
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    
    // Return fallback data to prevent empty display
    $fallbackData = [
        'jobs_applied' => ['total' => 0, 'pending' => 0, 'under_review' => 0, 'accepted' => 0],
        'active_jobs' => ['active' => 0],
        'available_jobs' => []
    ];
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => $fallbackData]);
}

?>
