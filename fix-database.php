<?php
require_once 'config.php';

try {
    $conn->select_db("household_connect");
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Database - Household Connect</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <h2>Fixing Database Issues</h2>";
    
    // Check if users table exists and has correct structure
    $result = $conn->query("DESCRIBE users");
    if (!$result) {
        echo "<div class='alert alert-warning'>⚠️ Users table doesn't exist. Creating it...</div>";
        
        // Create users table
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('employer', 'worker') NOT NULL,
            phone VARCHAR(20),
            location VARCHAR(255),
            bio TEXT,
            skills JSON,
            experience INT DEFAULT 0,
            expected_salary DECIMAL(10, 2),
            availability VARCHAR(100),
            profile_image VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Users table created</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Failed to create users table: " . $conn->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-success'>✅ Users table exists</div>";
    }
    
    // Check if jobs table exists
    $result = $conn->query("DESCRIBE jobs");
    if (!$result) {
        echo "<div class='alert alert-warning'>⚠️ Jobs table doesn't exist. Creating it...</div>";
        
        $sql = "CREATE TABLE IF NOT EXISTS jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employer_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            job_type ENUM('cleaning', 'cooking', 'childcare', 'eldercare', 'gardening', 'other') NOT NULL,
            salary DECIMAL(10, 2) NOT NULL,
            location VARCHAR(255) NOT NULL,
            work_hours VARCHAR(100) NOT NULL,
            status ENUM('active', 'filled', 'closed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Jobs table created</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Failed to create jobs table: " . $conn->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-success'>✅ Jobs table exists</div>";
    }
    
    // Now insert sample data using simple queries
    echo "<h3 class='mt-4'>Inserting Sample Data...</h3>";
    
    // Insert users with simple query
    $users = [
        ['John Mukiza', 'john@example.com', 'employer', '+250788123456', 'Kigali', 'Looking for reliable household workers'],
        ['Marie Uwimana', 'marie@example.com', 'worker', '+250788234567', 'Kicukiro', 'Experienced house cleaner and childcare provider'],
        ['Grace Kantengwa', 'grace@example.com', 'employer', '+250788345678', 'Gasabo', 'Need gardening and childcare help'],
        ['Joseph Niyonzima', 'joseph@example.com', 'worker', '+250788456789', 'Nyarugenge', 'Specialized in eldercare and cooking']
    ];
    
    foreach ($users as $userData) {
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $sql = "INSERT IGNORE INTO users (name, email, password, role, phone, location, bio) VALUES ('{$userData[0]}', '{$userData[1]}', '{$hashed_password}', '{$userData[2]}', '{$userData[3]}', '{$userData[4]}', '{$userData[5]}')";
        
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ User inserted: {$userData[0]}</div>";
        }
    }
    
    // Insert jobs
    $jobs = [
        [1, 'House Cleaner Needed', 'Looking for an experienced house cleaner for a family home in Kigali. Responsibilities include cleaning, laundry, and occasional cooking.', 'cleaning', 50000, 'Kigali', 'Full-time'],
        [3, 'Childcare Provider', 'Need a reliable childcare provider for 2 children (ages 3 and 5). Must have experience with toddlers and be patient.', 'childcare', 35000, 'Kicukiro', 'Part-time'],
        [1, 'Weekend Gardener', 'Looking for someone to maintain garden and lawn on weekends. Knowledge of plants and basic landscaping required.', 'gardening', 20000, 'Gasabo', 'Weekend Only'],
        [3, 'Elderly Care Assistant', 'Seeking a compassionate caregiver for an elderly person. Duties include companionship, medication reminders, and light housekeeping.', 'eldercare', 80000, 'Nyarugenge', 'Full-time']
    ];
    
    foreach ($jobs as $jobData) {
        $escaped_desc = $conn->real_escape_string($jobData[1]);
        $sql = "INSERT IGNORE INTO jobs (employer_id, title, description, job_type, salary, location, work_hours) VALUES ({$jobData[0]}, '{$jobData[2]}', '{$escaped_desc}', '{$jobData[3]}', {$jobData[4]}, '{$jobData[5]}', '{$jobData[6]}')";
        
        if ($conn->query($sql)) {
            echo "<div class='alert alert-success'>✅ Job inserted: {$jobData[2]}</div>";
        }
    }
    
    // Show final status
    $userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $jobCount = $conn->query("SELECT COUNT(*) as count FROM jobs")->fetch_assoc()['count'];
    
    echo "<div class='alert alert-info mt-4'>
        <h5>Database Status:</h5>
        <ul>
            <li>👥 Users: {$userCount}</li>
            <li>💼 Jobs: {$jobCount}</li>
        </ul>
    </div>";
    
    echo "<div class='mt-4'>
        <h3>Test Accounts:</h3>
        <p><strong>All passwords: password</strong></p>
        <div class='row'>
            <div class='col-md-6'>
                <h5>Employers:</h5>
                <ul>
                    <li>John Mukiza - john@example.com</li>
                    <li>Grace Kantengwa - grace@example.com</li>
                </ul>
            </div>
            <div class='col-md-6'>
                <h5>Workers:</h5>
                <ul>
                    <li>Marie Uwimana - marie@example.com</li>
                    <li>Joseph Niyonzima - joseph@example.com</li>
                </ul>
            </div>
        </div>
        
        <div class='mt-3'>
            <a href='login.php' class='btn btn-primary'>Test Login</a>
            <a href='dashboard.php' class='btn btn-success ms-2'>Test Dashboard</a>
        </div>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();

echo "</div>
</body>
</html>";
?>
