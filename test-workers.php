<?php
require_once 'config.php';

echo "Checking workers in database...\n";

// Get all workers
$workersSql = "SELECT u.id, u.name, u.email, u.role FROM users u WHERE u.role = 'worker'";
$stmt = $conn->prepare($workersSql);
$stmt->execute();
$workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found workers:\n";
foreach ($workers as $worker) {
    echo "- ID: {$worker['id']}, Name: {$worker['name']}, Email: {$worker['email']}\n";
}

if (empty($workers)) {
    echo "No workers found in database!\n";
    
    // Check all users
    $allUsersSql = "SELECT id, name, email, role FROM users";
    $stmt = $conn->prepare($allUsersSql);
    $stmt->execute();
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nAll users in database:\n";
    foreach ($allUsers as $user) {
        echo "- ID: {$user['id']}, Name: {$user['name']}, Role: {$user['role']}\n";
    }
} else {
    // Test available jobs query for first worker
    $testWorkerId = $workers[0]['id'];
    echo "\nTesting available jobs query for worker ID: $testWorkerId\n";
    
    $availableSql = "SELECT j.id, j.title, j.salary, j.location, u.name as employer_name,
                       CASE 
                           WHEN ja.id IS NOT NULL THEN 'applied'
                           ELSE 'active'
                       END as application_status
                       FROM jobs j 
                       JOIN users u ON j.employer_id = u.id 
                       LEFT JOIN job_applications ja ON j.id = ja.job_id AND ja.worker_id = :user_id
                       WHERE j.status = 'active'
                       ORDER BY j.created_at DESC 
                       LIMIT 5";
    
    $stmt = $conn->prepare($availableSql);
    $stmt->bindParam(':user_id', $testWorkerId);
    $stmt->execute();
    $availableJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Available jobs found: " . count($availableJobs) . "\n";
    foreach ($availableJobs as $job) {
        echo "- {$job['title']} ({$job['employer_name']}) - Status: {$job['application_status']}\n";
    }
}
?>
