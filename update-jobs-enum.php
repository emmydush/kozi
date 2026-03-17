<?php
require_once 'config.php';

echo "Updating job_status enum to include all needed values...\n";

// Update the enum to include all needed status values
$sql = "ALTER TABLE jobs ALTER COLUMN status TYPE VARCHAR(20)";
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "Changed status column to VARCHAR(20)\n";
} catch (Exception $e) {
    echo "Error changing column type: " . $e->getMessage() . "\n";
}

// Add a check constraint to ensure valid values
$sql = "ALTER TABLE jobs ADD CONSTRAINT chk_job_status CHECK (status IN ('active', 'filled', 'closed', 'new'))";
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "Added check constraint for job status\n";
} catch (Exception $e) {
    echo "Error adding constraint (may already exist): " . $e->getMessage() . "\n";
}

// Verify the changes
echo "\nCurrent jobs table structure:\n";
$sql = "SELECT column_name, data_type, column_default 
        FROM information_schema.columns 
        WHERE table_name = 'jobs' AND column_name = 'status'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($result);

echo "\nTesting insert with 'new' status...\n";
$sql = "INSERT INTO jobs (title, description, salary, location, type, employer_id, status) VALUES (?, ?, ?, ?, ?, ?, 'new')";
$stmt = $conn->prepare($sql);
$stmt->execute(['Test Job', 'Test Description', 50000, 'Kigali', 'cleaning', 1]);
echo "Successfully inserted test job with 'new' status\n";

// Clean up test job
$sql = "DELETE FROM jobs WHERE title = 'Test Job' AND description = 'Test Description'";
$stmt = $conn->prepare($sql);
$stmt->execute();
echo "Cleaned up test job\n";

echo "\nDone! The job status column now supports: active, filled, closed, new\n";
?>
