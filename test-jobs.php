<?php
require_once 'config.php';

// Test with different user IDs to find your jobs
$user_ids = [1, 2, 3, 4, 5, 6];

foreach ($user_ids as $user_id) {
    echo "Testing user ID: $user_id\n";
    
    // Check if user exists and is employer
    $userSql = "SELECT id, name, role FROM users WHERE id = :user_id AND role = 'employer'";
    $stmt = $conn->prepare($userSql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Found employer: " . $user['name'] . " (ID: " . $user['id'] . ")\n";
        
        // Count jobs
        $countSql = "SELECT COUNT(*) as total FROM jobs WHERE employer_id = :user_id";
        $stmt = $conn->prepare($countSql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Posted jobs count: " . $count['total'] . "\n";
        
        // Show actual jobs
        if ($count['total'] > 0) {
            $jobsSql = "SELECT id, title, status, created_at FROM jobs WHERE employer_id = :user_id ORDER BY created_at DESC";
            $stmt = $conn->prepare($jobsSql);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "Jobs:\n";
            foreach ($jobs as $job) {
                echo "  - ID: {$job['id']}, Title: {$job['title']}, Status: {$job['status']}, Created: {$job['created_at']}\n";
            }
        }
        echo "\n";
    }
}

// Also show all jobs in the system
echo "All jobs in system:\n";
$allJobsSql = "SELECT j.id, j.title, j.status, j.created_at, u.name as employer_name, u.id as employer_id 
               FROM jobs j 
               JOIN users u ON j.employer_id = u.id 
               ORDER BY j.created_at DESC";
$stmt = $conn->prepare($allJobsSql);
$stmt->execute();
$allJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($allJobs as $job) {
    echo "- ID: {$job['id']}, Title: {$job['title']}, Employer: {$job['employer_name']} (ID: {$job['employer_id']}), Status: {$job['status']}\n";
}
?>
