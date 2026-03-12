<?php
require_once 'config.php';

try {
    $conn->select_db("household_connect");
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Add Sample Data - Household Connect</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <h2>Adding Sample Data</h2>";
    
    // Clear existing data first
    $conn->query("DELETE FROM job_applications");
    $conn->query("DELETE FROM bookings");
    $conn->query("DELETE FROM jobs");
    $conn->query("DELETE FROM users WHERE id > 1");
    
    echo "<div class='alert alert-warning'>⚠️ Cleared existing sample data</div>";
    
    // Add sample users
    $users = [
        ['John Mukiza', 'john@example.com', 'employer', '+250788123456', 'Kigali', 'Looking for reliable household workers'],
        ['Grace Kantengwa', 'grace@example.com', 'employer', '+250788345678', 'Gasabo', 'Need gardening and childcare help'],
        ['Marie Uwimana', 'marie@example.com', 'worker', '+250788234567', 'Kicukiro', 'Experienced house cleaner and childcare provider'],
        ['Joseph Niyonzima', 'joseph@example.com', 'worker', '+250788456789', 'Nyarugenge', 'Specialized in eldercare and cooking']
    ];
    
    $userIds = [];
    foreach ($users as $userData) {
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password, role, phone, location, bio) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $userData[0], $userData[1], $hashed_password, $userData[2], $userData[3], $userData[4], $userData[5]);
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            $userIds[$userData[1]] = $userId;
            echo "<div class='alert alert-success'>✅ Added user: {$userData[0]} (ID: {$userId})</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Failed to add user: {$userData[0]} - " . $conn->error . "</div>";
        }
    }
    
    // Add sample jobs
    $jobs = [
        ['john@example.com', 'House Cleaner Needed', 'Looking for an experienced house cleaner for a family home in Kigali. Responsibilities include cleaning, laundry, and occasional cooking.', 'cleaning', 50000, 'Kigali', 'Full-time'],
        ['grace@example.com', 'Childcare Provider', 'Need a reliable childcare provider for 2 children (ages 3 and 5). Must have experience with toddlers and be patient.', 'childcare', 35000, 'Kicukiro', 'Part-time'],
        ['john@example.com', 'Weekend Gardener', 'Looking for someone to maintain garden and lawn on weekends. Knowledge of plants and basic landscaping required.', 'gardening', 20000, 'Gasabo', 'Weekend Only'],
        ['grace@example.com', 'Elderly Care Assistant', 'Seeking a compassionate caregiver for an elderly person. Duties include companionship, medication reminders, and light housekeeping.', 'eldercare', 80000, 'Nyarugenge', 'Full-time']
    ];
    
    foreach ($jobs as $jobData) {
        $employerId = $userIds[$jobData[0]];
        $escaped_desc = $conn->real_escape_string($jobData[1]);
        
        $sql = "INSERT INTO jobs (employer_id, title, description, job_type, salary, location, work_hours) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssdss", $employerId, $jobData[2], $jobData[3], $jobData[4], $jobData[5], $jobData[6], $jobData[7]);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>✅ Added job: {$jobData[2]}</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Failed to add job: {$jobData[2]} - " . $conn->error . "</div>";
        }
    }
    
    // Add some applications and bookings
    echo "<h3 class='mt-4'>Adding Applications and Bookings...</h3>";
    
    // Get the IDs we just created
    $marieId = $userIds['marie@example.com'];
    $josephId = $userIds['joseph@example.com'];
    $johnId = $userIds['john@example.com'];
    
    // Add applications
    $applications = [
        [1, $marieId, 'accepted'], // House Cleaner job
        [2, $marieId, 'pending'], // Childcare job
        [3, $josephId, 'under_review'], // Gardener job
        [4, $josephId, 'accepted'] // Elderly Care job
    ];
    
    foreach ($applications as $app) {
        $sql = "INSERT INTO job_applications (job_id, worker_id, status) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $app[0], $app[1], $app[2]);
        $stmt->execute();
    }
    
    // Add bookings
    $bookings = [
        [1, $marieId, $johnId, '2024-12-01', '2024-12-31', 'confirmed', 50000],
        [4, $josephId, 3, '2024-12-15', '2024-12-31', 'confirmed', 80000]
    ];
    
    foreach ($bookings as $booking) {
        $sql = "INSERT INTO bookings (job_id, worker_id, employer_id, start_date, end_date, status, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiisssd", $booking[0], $booking[1], $booking[2], $booking[3], $booking[4], $booking[5], $booking[6]);
        $stmt->execute();
    }
    
    echo "<div class='alert alert-success'>✅ Added applications and bookings</div>";
    
    // Show final counts
    $userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $jobCount = $conn->query("SELECT COUNT(*) as count FROM jobs")->fetch_assoc()['count'];
    $appCount = $conn->query("SELECT COUNT(*) as count FROM job_applications")->fetch_assoc()['count'];
    
    echo "<div class='alert alert-info mt-4'>
        <h4>Database Summary:</h4>
        <ul>
            <li>👥 Users: {$userCount}</li>
            <li>💼 Jobs: {$jobCount}</li>
            <li>📝 Applications: {$appCount}</li>
        </ul>
    </div>";
    
    echo "<div class='mt-4'>
        <h4>Test Accounts (password: password):</h4>
        <div class='row'>
            <div class='col-md-6'>
                <h5>Employers:</h5>
                <ul>
                    <li><strong>John Mukiza</strong> - john@example.com</li>
                    <li><strong>Grace Kantengwa</strong> - grace@example.com</li>
                </ul>
            </div>
            <div class='col-md-6'>
                <h5>Workers:</h5>
                <ul>
                    <li><strong>Marie Uwimana</strong> - marie@example.com</li>
                    <li><strong>Joseph Niyonzima</strong> - joseph@example.com</li>
                </ul>
            </div>
        </div>
        
        <div class='mt-3'>
            <a href='login.php' class='btn btn-primary'>🔑 Test Login</a>
            <a href='dashboard.php' class='btn btn-success ms-2'>📊 Test Dashboard</a>
            <a href='api/dashboard-data-test.php' class='btn btn-info ms-2'>🔧 Test API</a>
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
