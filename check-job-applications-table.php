<?php
require_once 'config.php';

echo "Checking job_applications table structure...\n";

// Check table structure
$sql = "SELECT column_name, data_type, column_default 
        FROM information_schema.columns 
        WHERE table_name = 'job_applications' 
        ORDER BY ordinal_position";
$stmt = $conn->prepare($sql);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Columns in job_applications table:\n";
foreach ($columns as $column) {
    echo "- {$column['column_name']} ({$column['data_type']})\n";
}

// Check if created_at column exists
$created_at_exists = false;
foreach ($columns as $column) {
    if ($column['column_name'] === 'created_at') {
        $created_at_exists = true;
        break;
    }
}

echo "\ncreated_at column exists: " . ($created_at_exists ? 'YES' : 'NO') . "\n";

// Show current data
echo "\nCurrent data in job_applications:\n";
$sql = "SELECT * FROM job_applications LIMIT 3";
$stmt = $conn->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($data as $row) {
    echo "ID: {$row['id']}, Job ID: {$row['job_id']}, Worker ID: {$row['worker_id']}, Status: {$row['status']}\n";
}
?>
