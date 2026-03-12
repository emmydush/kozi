<?php
require_once 'config.php';

try {
    $conn->select_db("household_connect");
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Check Users - Household Connect</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <h2>Current Users in Database</h2>";
    
    $result = $conn->query("SELECT id, name, email, role FROM users ORDER BY id");
    
    if ($result && $result->num_rows > 0) {
        echo "<table class='table table-striped'>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['email']}</td><td>{$row['role']}</td></tr>";
        }
        
        echo "</table>";
        
        // Now insert the missing users with correct IDs
        echo "<h3 class='mt-4'>Adding Missing Users...</h3>";
        
        $missingUsers = [
            ['John Mukiza', 'john@example.com', 'employer', '+250788123456', 'Kigali', 'Looking for reliable household workers'],
            ['Grace Kantengwa', 'grace@example.com', 'employer', '+250788345678', 'Gasabo', 'Need gardening and childcare help'],
            ['Marie Uwimana', 'marie@example.com', 'worker', '+250788234567', 'Kicukiro', 'Experienced house cleaner and childcare provider'],
            ['Joseph Niyonzima', 'joseph@example.com', 'worker', '+250788456789', 'Nyarugenge', 'Specialized in eldercare and cooking']
        ];
        
        foreach ($missingUsers as $userData) {
            // Check if user already exists
            $checkSql = "SELECT id FROM users WHERE email = '{$userData[1]}'";
            $checkResult = $conn->query($checkSql);
            
            if ($checkResult->num_rows == 0) {
                $hashed_password = password_hash('password', PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (name, email, password, role, phone, location, bio) VALUES ('{$userData[0]}', '{$userData[1]}', '{$hashed_password}', '{$userData[2]}', '{$userData[3]}', '{$userData[4]}', '{$userData[5]}')";
                
                if ($conn->query($sql)) {
                    $newId = $conn->insert_id;
                    echo "<div class='alert alert-success'>✅ Added user: {$userData[0]} (ID: {$newId})</div>";
                }
            } else {
                $existingUser = $checkResult->fetch_assoc();
                echo "<div class='alert alert-info'>ℹ️ User already exists: {$userData[0]} (ID: {$existingUser['id']})</div>";
            }
        }
        
        // Add jobs
        echo "<h3 class='mt-4'>Adding Jobs...</h3>";
        
        $jobs = [
            [1, 'House Cleaner Needed', 'Looking for an experienced house cleaner for a family home in Kigali.', 'cleaning', 50000, 'Kigali', 'Full-time'],
            [3, 'Childcare Provider', 'Need a reliable childcare provider for 2 children.', 'childcare', 35000, 'Kicukiro', 'Part-time'],
            [1, 'Weekend Gardener', 'Looking for someone to maintain garden on weekends.', 'gardening', 20000, 'Gasabo', 'Weekend Only'],
            [3, 'Elderly Care Assistant', 'Seeking a compassionate caregiver for an elderly person.', 'eldercare', 80000, 'Nyarugenge', 'Full-time']
        ];
        
        foreach ($jobs as $jobData) {
            $escaped_desc = $conn->real_escape_string($jobData[1]);
            $sql = "INSERT IGNORE INTO jobs (employer_id, title, description, job_type, salary, location, work_hours) VALUES ({$jobData[0]}, '{$jobData[2]}', '{$escaped_desc}', '{$jobData[3]}', {$jobData[4]}, '{$jobData[5]}', '{$jobData[6]}')";
            
            if ($conn->query($sql)) {
                echo "<div class='alert alert-success'>✅ Added job: {$jobData[2]}</div>";
            }
        }
        
        // Final count
        $userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
        $jobCount = $conn->query("SELECT COUNT(*) as count FROM jobs")->fetch_assoc()['count'];
        
        echo "<div class='alert alert-success mt-4'>
            <h5>Final Status:</h5>
            <ul>
                <li>👥 Total Users: {$userCount}</li>
                <li>💼 Total Jobs: {$jobCount}</li>
            </ul>
        </div>";
        
    } else {
        echo "<div class='alert alert-warning'>No users found in database</div>";
    }
    
    echo "<div class='mt-4'>
        <a href='login.php' class='btn btn-primary'>Test Login</a>
        <a href='dashboard.php' class='btn btn-success ms-2'>Test Dashboard</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
}

$conn->close();

echo "</div>
</body>
</html>";
?>
