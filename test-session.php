<?php
require_once 'config.php';

// Start session and check current user
session_start();

echo "Current session data:\n";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
echo "User Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'NOT SET') . "\n";
echo "User Name: " . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'NOT SET') . "\n";
echo "Logged In: " . (isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : 'NOT SET') . "\n";

if (is_logged_in()) {
    echo "User is logged in\n";
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    // Test the same query as the dashboard API
    if ($user_role === 'employer') {
        $postedJobsSql = "SELECT COUNT(*) as total FROM jobs WHERE employer_id = :user_id";
        $stmt = $conn->prepare($postedJobsSql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $postedJobsResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $postedJobs = $postedJobsResult ? $postedJobsResult['total'] : 0;
        
        echo "Posted jobs count for current user: $postedJobs\n";
        
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
        
        echo "Recent jobs found: " . count($recentJobs) . "\n";
        foreach ($recentJobs as $job) {
            echo "  - {$job['title']} (ID: {$job['id']}, Status: {$job['status']})\n";
        }
    }
} else {
    echo "User is NOT logged in\n";
}
?>
