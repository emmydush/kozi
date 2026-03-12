<?php
require_once 'config.php';

try {
    // Create database if not exists
    $conn->query("CREATE DATABASE IF NOT EXISTS household_connect");
    $conn->select_db("household_connect");
    
    // Read and execute SQL setup file
    $sql = file_get_contents('database/setup.sql');
    
    // Split SQL statements and execute them
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $conn->query($statement);
        }
    }
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Setup - Household Connect</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card'>
                    <div class='card-header bg-success text-white'>
                        <h4 class='mb-0'>✅ Database Setup Complete!</h4>
                    </div>
                    <div class='card-body'>
                        <h5>Household Connect Database</h5>
                        <p>The database has been successfully set up with the following:</p>
                        <ul>
                            <li>✅ Users table with sample accounts</li>
                            <li>✅ Jobs table with sample job postings</li>
                            <li>✅ Applications, Bookings, Messages tables</li>
                            <li>✅ Reviews and Earnings tables</li>
                            <li>✅ Sample data for testing</li>
                        </ul>
                        
                        <h6 class='mt-4'>Sample Accounts:</h6>
                        <div class='row'>
                            <div class='col-md-6'>
                                <strong>Employers:</strong>
                                <ul class='small'>
                                    <li>John Mukiza (john@example.com)</li>
                                    <li>Grace Kantengwa (grace@example.com)</li>
                                </ul>
                            </div>
                            <div class='col-md-6'>
                                <strong>Workers:</strong>
                                <ul class='small'>
                                    <li>Marie Uwimana (marie@example.com)</li>
                                    <li>Joseph Niyonzima (joseph@example.com)</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class='alert alert-info mt-3'>
                            <strong>Note:</strong> All sample accounts use the password: <code>password</code>
                        </div>
                        
                        <div class='d-grid gap-2 mt-4'>
                            <a href='index.php' class='btn btn-primary'>Go to Homepage</a>
                            <a href='login.php' class='btn btn-outline-primary'>Login to Test</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
    
} catch (Exception $e) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Setup Error</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card'>
                    <div class='card-header bg-danger text-white'>
                        <h4 class='mb-0'>❌ Database Setup Failed</h4>
                    </div>
                    <div class='card-body'>
                        <p>Error: " . $e->getMessage() . "</p>
                        <p>Please make sure:</p>
                        <ul>
                            <li>XAMPP MySQL service is running</li>
                            <li>Database user 'root' has no password</li>
                            <li>config.php database settings are correct</li>
                        </ul>
                        <a href='index.php' class='btn btn-primary'>Go to Homepage</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
}

$conn->close();
?>
