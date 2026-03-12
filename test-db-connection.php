<?php
// Test database connection and data
require_once 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Test - Household Connect</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <h2>Database Connection Test</h2>";

try {
    echo "<div class='alert alert-success'>✅ Database connection successful!</div>";
    
    // Test database selection
    $conn->select_db("household_connect");
    echo "<div class='alert alert-success'>✅ Database 'household_connect' selected!</div>";
    
    // Test users table
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "<div class='alert alert-info'>📊 Users table: {$row['count']} records</div>";
    
    // Test jobs table
    $result = $conn->query("SELECT COUNT(*) as count FROM jobs");
    $row = $result->fetch_assoc();
    echo "<div class='alert alert-info'>📊 Jobs table: {$row['count']} records</div>";
    
    // Show sample data
    echo "<h3 class='mt-4'>Sample Users:</h3>";
    $result = $conn->query("SELECT id, name, email, role FROM users LIMIT 4");
    echo "<table class='table table-striped'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['email']}</td><td>{$row['role']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h3 class='mt-4'>Sample Jobs:</h3>";
    $result = $conn->query("SELECT id, title, job_type, salary, location FROM jobs LIMIT 4");
    echo "<table class='table table-striped'>";
    echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Salary</th><th>Location</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['id']}</td><td>{$row['title']}</td><td>{$row['job_type']}</td><td>RWF {$row['salary']}</td><td>{$row['location']}</td></tr>";
    }
    echo "</table>";
    
    echo "<div class='mt-4'>
        <a href='login.php' class='btn btn-primary'>Test Login</a>
        <a href='dashboard.php' class='btn btn-success ms-2'>Test Dashboard</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<p>Please check:</p>
    <ul>
        <li>XAMPP MySQL service is running</li>
        <li>Database exists</li>
        <li>config.php settings are correct</li>
    </ul>";
}

$conn->close();

echo "</div>
</body>
</html>";
?>
