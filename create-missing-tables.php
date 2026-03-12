<?php
require_once 'config.php';

try {
    $conn->select_db("household_connect");
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Create Missing Tables - Household Connect</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-5'>
        <h2>Creating Missing Tables</h2>";
    
    // Create job_applications table
    $sql = "CREATE TABLE IF NOT EXISTS job_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        worker_id INT NOT NULL,
        status ENUM('pending', 'under_review', 'accepted', 'rejected') DEFAULT 'pending',
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
        FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_application (job_id, worker_id)
    )";
    
    if ($conn->query($sql)) {
        echo "<div class='alert alert-success'>✅ job_applications table created/exists</div>";
    }
    
    // Create bookings table
    $sql = "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        worker_id INT NOT NULL,
        employer_id INT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE,
        status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
        total_amount DECIMAL(10, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
        FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "<div class='alert alert-success'>✅ bookings table created/exists</div>";
    }
    
    // Create messages table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        job_id INT NULL,
        message TEXT NOT NULL,
        status ENUM('sent', 'delivered', 'read') DEFAULT 'sent',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($sql)) {
        echo "<div class='alert alert-success'>✅ messages table created/exists</div>";
    }
    
    // Create reviews table
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reviewer_id INT NOT NULL,
        reviewee_id INT NOT NULL,
        job_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
        UNIQUE KEY unique_review (reviewer_id, reviewee_id, job_id)
    )";
    
    if ($conn->query($sql)) {
        echo "<div class='alert alert-success'>✅ reviews table created/exists</div>";
    }
    
    // Create earnings table
    $sql = "CREATE TABLE IF NOT EXISTS earnings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        worker_id INT NOT NULL,
        job_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
        payment_date DATE,
        work_date DATE NOT NULL,
        hours_worked DECIMAL(4, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (worker_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql)) {
        echo "<div class='alert alert-success'>✅ earnings table created/exists</div>";
    }
    
    // Add sample applications and bookings
    echo "<h3 class='mt-4'>Adding Sample Applications and Bookings...</h3>";
    
    // Add applications
    $applications = [
        [1, 4, 'accepted'], // House Cleaner job - Marie
        [2, 4, 'pending'], // Childcare job - Marie
        [3, 5, 'under_review'], // Gardener job - Joseph
        [4, 5, 'accepted'] // Elderly Care job - Joseph
    ];
    
    foreach ($applications as $app) {
        $sql = "INSERT IGNORE INTO job_applications (job_id, worker_id, status) VALUES ({$app[0]}, {$app[1]}, '{$app[2]}')";
        $conn->query($sql);
    }
    
    // Add bookings
    $bookings = [
        [1, 4, 2, '2024-12-01', '2024-12-31', 'confirmed', 50000],
        [4, 5, 3, '2024-12-15', '2024-12-31', 'confirmed', 80000]
    ];
    
    foreach ($bookings as $booking) {
        $sql = "INSERT IGNORE INTO bookings (job_id, worker_id, employer_id, start_date, end_date, status, total_amount) VALUES ({$booking[0]}, {$booking[1]}, {$booking[2]}, '{$booking[3]}', '{$booking[4]}', '{$booking[5]}', {$booking[6]})";
        $conn->query($sql);
    }
    
    // Add earnings
    $earnings = [
        [4, 1, 50000, 'paid', '2024-12-01', '2024-12-01', 40.00],
        [5, 4, 80000, 'pending', NULL, '2024-12-15', 35.00]
    ];
    
    foreach ($earnings as $earning) {
        $sql = "INSERT IGNORE INTO earnings (worker_id, job_id, amount, payment_status, payment_date, work_date, hours_worked) VALUES ({$earning[0]}, {$earning[1]}, {$earning[2]}, '{$earning[3]}', " . ($earning[4] ? "'{$earning[4]}'" : "NULL") . ", '{$earning[5]}', {$earning[6]})";
        $conn->query($sql);
    }
    
    echo "<div class='alert alert-success'>✅ Added sample applications, bookings, and earnings</div>";
    
    // Show final counts
    $userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $jobCount = $conn->query("SELECT COUNT(*) as count FROM jobs")->fetch_assoc()['count'];
    $appCount = $conn->query("SELECT COUNT(*) as count FROM job_applications")->fetch_assoc()['count'];
    $bookingCount = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
    $earningCount = $conn->query("SELECT COUNT(*) as count FROM earnings")->fetch_assoc()['count'];
    
    echo "<div class='alert alert-info mt-4'>
        <h4>Database Summary:</h4>
        <ul>
            <li>👥 Users: {$userCount}</li>
            <li>💼 Jobs: {$jobCount}</li>
            <li>📝 Applications: {$appCount}</li>
            <li>📅 Bookings: {$bookingCount}</li>
            <li>💰 Earnings: {$earningCount}</li>
        </ul>
    </div>";
    
    echo "<div class='mt-4'>
        <h4>Test the Application:</h4>
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
