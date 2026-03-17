<?php
require_once 'config.php';

echo "Checking jobs table structure...\n";

// Check the enum values for status column
$sql = "SELECT column_name, data_type, column_default 
        FROM information_schema.columns 
        WHERE table_name = 'jobs' AND column_name = 'status'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Status column info:\n";
print_r($result);

// Get all distinct status values currently in jobs table
echo "\nCurrent status values in jobs table:\n";
$sql = "SELECT DISTINCT status FROM jobs";
$stmt = $conn->prepare($sql);
$stmt->execute();
$statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($statuses as $status) {
    echo "- {$status['status']}\n";
}

// Show all jobs
echo "\nAll jobs in database:\n";
$sql = "SELECT id, title, status, employer_id FROM jobs ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($jobs as $job) {
    echo "- ID: {$job['id']}, Title: {$job['title']}, Status: {$job['status']}, Employer: {$job['employer_id']}\n";
}
?>
