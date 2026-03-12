<?php
require_once 'config.php';

try {
    $conn->select_db("household_connect");
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Check Tables - Household Connect</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <h2>Database Table Structure</h2>";
    
    // Check users table
    echo "<h3>Users Table:</h3>";
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        echo "<table class='table table-striped'>
            <tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
        }
        echo "</table>";
    }
    
    // Check jobs table
    echo "<h3>Jobs Table:</h3>";
    $result = $conn->query("DESCRIBE jobs");
    if ($result) {
        echo "<table class='table table-striped'>
            <tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
        }
        echo "</table>";
    }
    
    // Add missing columns if needed
    echo "<h3>Adding Missing Columns...</h3>";
    
    // Add location column to users if missing
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'location'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN location VARCHAR(255) AFTER role");
        echo "<div class='alert alert-success'>✅ Added location column to users</div>";
    }
    
    // Add other missing columns to users
    $missingUserColumns = [
        'phone' => 'VARCHAR(20) AFTER location',
        'bio' => 'TEXT AFTER phone',
        'skills' => 'JSON AFTER bio',
        'experience' => 'INT DEFAULT 0 AFTER skills',
        'expected_salary' => 'DECIMAL(10,2) AFTER experience',
        'availability' => 'VARCHAR(100) AFTER expected_salary',
        'profile_image' => 'VARCHAR(255) AFTER availability'
    ];
    
    foreach ($missingUserColumns as $column => $definition) {
        $result = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
        if ($result->num_rows == 0) {
            $conn->query("ALTER TABLE users ADD COLUMN $column $definition");
            echo "<div class='alert alert-success'>✅ Added $column column to users</div>";
        }
    }
    
    // Add job_type column to jobs if missing
    $result = $conn->query("SHOW COLUMNS FROM jobs LIKE 'job_type'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE jobs ADD COLUMN job_type ENUM('cleaning', 'cooking', 'childcare', 'eldercare', 'gardening', 'other') AFTER description");
        echo "<div class='alert alert-success'>✅ Added job_type column to jobs</div>";
    }
    
    // Add other missing columns to jobs
    $missingJobColumns = [
        'work_hours' => 'VARCHAR(100) AFTER salary',
        'status' => "ENUM('active', 'filled', 'closed') DEFAULT 'active' AFTER work_hours"
    ];
    
    foreach ($missingJobColumns as $column => $definition) {
        $result = $conn->query("SHOW COLUMNS FROM jobs LIKE '$column'");
        if ($result->num_rows == 0) {
            $conn->query("ALTER TABLE jobs ADD COLUMN $column $definition");
            echo "<div class='alert alert-success'>✅ Added $column column to jobs</div>";
        }
    }
    
    echo "<div class='alert alert-info mt-4'>
        <h4>Next Steps:</h4>
        <ol>
            <li>Run <code>add-data-simple.php</code> to add sample data</li>
            <li>Test the application with real data</li>
            <li>Check the dashboard for live statistics</li>
        </ol>
    </div>";
    
    echo "<div class='mt-3'>
        <a href='add-data-simple.php' class='btn btn-primary'>📊 Add Sample Data</a>
        <a href='login.php' class='btn btn-success ms-2'>🔑 Test Login</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();

echo "</div>
</body>
</html>";
?>
