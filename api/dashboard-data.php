<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

try {
    $data = [];
    
    if ($user_role === 'employer') {
        // Employer dashboard data
        $data = [
            'posted_jobs' => getEmployerJobStats($user_id),
            'active_bookings' => getEmployerBookingStats($user_id),
            'total_spent' => getEmployerSpending($user_id),
            'workers_hired' => getEmployerHiredWorkers($user_id),
            'recent_jobs' => getEmployerRecentJobs($user_id)
        ];
    } else {
        // Worker dashboard data
        $data = [
            'jobs_applied' => getWorkerApplicationStats($user_id),
            'active_jobs' => getWorkerActiveJobs($user_id),
            'total_earned' => getWorkerEarnings($user_id),
            'reviews' => getWorkerReviewStats($user_id),
            'available_jobs' => getAvailableJobs()
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Helper functions
function getEmployerJobStats($user_id) {
    global $conn;
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as filled
            FROM jobs WHERE employer_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getEmployerBookingStats($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as active
            FROM bookings 
            WHERE employer_id = :user_id AND status = 'confirmed'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['active'] ?? 0;
}

function getEmployerSpending($user_id) {
    global $conn;
    
    $sql = "SELECT COALESCE(SUM(amount), 0) as total
            FROM bookings 
            WHERE employer_id = :user_id AND status = 'completed'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['total'] ?? 0;
}

function getEmployerHiredWorkers($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(DISTINCT worker_id) as workers
            FROM bookings 
            WHERE employer_id = :user_id AND status IN ('confirmed', 'completed')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['workers'] ?? 0;
}

function getEmployerRecentJobs($user_id) {
    global $conn;
    
    $sql = "SELECT j.*, COUNT(ja.id) as application_count
            FROM jobs j
            LEFT JOIN job_applications ja ON j.id = ja.job_id
            WHERE j.employer_id = :user_id
            ORDER BY j.created_at DESC
            LIMIT 5";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $jobs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $jobs[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'job_type' => $row['job_type'],
            'salary' => $row['salary'],
            'location' => $row['location'],
            'status' => $row['status'],
            'applications' => $row['application_count'],
            'created_at' => $row['created_at']
        ];
    }
    
    return $jobs;
}

function getWorkerApplicationStats($user_id) {
    global $conn;
    
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted
            FROM job_applications WHERE worker_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getWorkerActiveJobs($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as active
            FROM bookings 
            WHERE worker_id = :user_id AND status = 'confirmed'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['active'] ?? 0;
}

function getWorkerEarnings($user_id) {
    global $conn;
    
    $sql = "SELECT 
                SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as total_earned,
                SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as pending,
                SUM(CASE WHEN payment_status = 'paid' AND MONTH(work_date) = MONTH(CURRENT_DATE) THEN amount ELSE 0 END) as this_month
            FROM earnings WHERE worker_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getWorkerReviewStats($user_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as total, AVG(rating) as average_rating
            FROM reviews 
            WHERE reviewee_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAvailableJobs() {
    global $conn;
    
    $sql = "SELECT j.*, u.name as employer_name
            FROM jobs j
            JOIN users u ON j.employer_id = u.id
            WHERE j.status = 'active'
            ORDER BY j.created_at DESC
            LIMIT 10";
    
    $stmt = $conn->query($sql);
    
    $jobs = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $jobs[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => substr($row['description'], 0, 150) . '...',
            'job_type' => $row['job_type'],
            'salary' => $row['salary'],
            'location' => $row['location'],
            'work_hours' => $row['work_hours'],
            'employer_name' => $row['employer_name'],
            'created_at' => $row['created_at']
        ];
    }
    
    return $jobs;
}
