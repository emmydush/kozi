<?php
require_once 'config.php';

try {
    $conn->select_db("household_connect");
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Insert Sample Data - Household Connect</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <h2>Inserting Sample Data</h2>";
    
    // Insert sample users
    $users = [
        ['John Mukiza', 'john@example.com', 'employer', '+250788123456', 'Kigali', 'Looking for reliable household workers'],
        ['Marie Uwimana', 'marie@example.com', 'worker', '+250788234567', 'Kicukiro', 'Experienced house cleaner and childcare provider'],
        ['Grace Kantengwa', 'grace@example.com', 'employer', '+250788345678', 'Gasabo', 'Need gardening and childcare help'],
        ['Joseph Niyonzima', 'joseph@example.com', 'worker', '+250788456789', 'Nyarugenge', 'Specialized in eldercare and cooking']
    ];
    
    foreach ($users as $userData) {
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password, role, phone, location, bio) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $userData[0], $userData[1], $hashed_password, $userData[2], $userData[3], $userData[4], $userData[5]);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>✅ User inserted: {$userData[0]}</div>";
        } else {
            echo "<div class='alert alert-warning'>⚠️ User already exists: {$userData[0]}</div>";
        }
    }
    
    // Insert sample jobs
    $jobs = [
        [1, 'House Cleaner Needed', 'Looking for an experienced house cleaner for a family home in Kigali. Responsibilities include cleaning, laundry, and occasional cooking.', 'cleaning', 50000, 'Kigali', 'Full-time'],
        [3, 'Childcare Provider', 'Need a reliable childcare provider for 2 children (ages 3 and 5). Must have experience with toddlers and be patient.', 'childcare', 35000, 'Kicukiro', 'Part-time'],
        [1, 'Weekend Gardener', 'Looking for someone to maintain garden and lawn on weekends. Knowledge of plants and basic landscaping required.', 'gardening', 20000, 'Gasabo', 'Weekend Only'],
        [3, 'Elderly Care Assistant', 'Seeking a compassionate caregiver for an elderly person. Duties include companionship, medication reminders, and light housekeeping.', 'eldercare', 80000, 'Nyarugenge', 'Full-time']
    ];
    
    foreach ($jobs as $jobData) {
        $sql = "INSERT INTO jobs (employer_id, title, description, job_type, salary, location, work_hours) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssdss", $jobData[0], $jobData[1], $jobData[2], $jobData[3], $jobData[4], $jobData[5], $jobData[6]);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>✅ Job inserted: {$jobData[1]}</div>";
        } else {
            echo "<div class='alert alert-warning'>⚠️ Job already exists: {$jobData[1]}</div>";
        }
    }
    
    // Insert sample applications
    $applications = [
        [1, 2, 'accepted'], // Marie applies to House Cleaner job
        [2, 2, 'pending'], // Marie applies to Childcare job
        [3, 4, 'under_review'], // Joseph applies to Gardener job
        [4, 4, 'accepted']  // Joseph applies to Elderly Care job
    ];
    
    foreach ($applications as $appData) {
        $sql = "INSERT IGNORE INTO job_applications (job_id, worker_id, status) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $appData[0], $appData[1], $appData[2]);
        $stmt->execute();
    }
    
    // Insert sample bookings
    $bookings = [
        [1, 2, 1, '2024-12-01', '2024-12-31', 'confirmed', 50000],
        [4, 4, 3, '2024-12-15', '2024-12-31', 'confirmed', 80000]
    ];
    
    foreach ($bookings as $bookingData) {
        $sql = "INSERT IGNORE INTO bookings (job_id, worker_id, employer_id, start_date, end_date, status, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiisssd", $bookingData[0], $bookingData[1], $bookingData[2], $bookingData[3], $bookingData[4], $bookingData[5], $bookingData[6]);
        $stmt->execute();
    }
    
    // Show final counts
    $userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $jobCount = $conn->query("SELECT COUNT(*) as count FROM jobs")->fetch_assoc()['count'];
    $appCount = $conn->query("SELECT COUNT(*) as count FROM job_applications")->fetch_assoc()['count'];
    
    echo "<div class='alert alert-info mt-4'>
        <h5>Database Summary:</h5>
        <ul>
            <li>👥 Users: {$userCount}</li>
            <li>💼 Jobs: {$jobCount}</li>
            <li>📝 Applications: {$appCount}</li>
        </ul>
    </div>";
    
    echo "<div class='mt-4'>
        <h3>Test Accounts:</h3>
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
        <p class='text-muted'><strong>All passwords: password</strong></p>
        
        <div class='mt-3'>
            <a href='login.php' class='btn btn-primary'>Test Login</a>
            <a href='dashboard.php' class='btn btn-success ms-2'>Test Dashboard</a>
            <a href='test-db-connection.php' class='btn btn-info ms-2'>Verify Data</a>
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
