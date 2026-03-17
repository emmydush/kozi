<?php
require_once 'config.php';

// Simulate being logged in as a worker
session_start();
$_SESSION['user_id'] = 3; // Worker ID we found earlier
$_SESSION['user_role'] = 'worker';
$_SESSION['user_name'] = 'Dushimirimana Emmanu';
$_SESSION['logged_in'] = true;

echo "Testing available jobs API...\n";

// Test the exact query from dashboard API
$user_id = 3;
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

echo "SQL Query: $availableSql\n\n";

$stmt = $conn->prepare($availableSql);
if (!$stmt) {
    echo "Failed to prepare statement\n";
    exit();
}

$stmt->bindParam(':user_id', $user_id);
if (!$stmt->execute()) {
    echo "Failed to execute statement\n";
    print_r($stmt->errorInfo());
    exit();
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

echo "Found " . count($availableJobs) . " available jobs:\n";
foreach ($availableJobs as $job) {
    echo "- {$job['title']} ({$job['employer_name']}) - Status: {$job['status']}\n";
}

// Test full API response format
$data = [
    'jobs_applied' => ['total' => 0, 'pending' => 0, 'under_review' => 0, 'accepted' => 0],
    'active_jobs' => ['active' => 0],
    'available_jobs' => $availableJobs
];

echo "\nFull API response:\n";
echo json_encode(['success' => true, 'data' => $data], JSON_PRETTY_PRINT);
?>
