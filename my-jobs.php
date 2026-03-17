<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

if ($user_role === 'employer') {
    // Fetch jobs posted by employer
    $jobs = [];
    $sql = "SELECT j.*, 
                   COUNT(ja.id) as application_count,
                   SUM(CASE WHEN ja.status = 'accepted' THEN 1 ELSE 0 END) as accepted_count
            FROM jobs j 
            LEFT JOIN job_applications ja ON j.id = ja.job_id 
            WHERE j.employer_id = :user_id 
            GROUP BY j.id 
            ORDER BY j.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $posted_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $total_posted = count($posted_jobs);
    $active_count = 0;
    $filled_count = 0;
    $total_applications = 0;
    
    foreach ($posted_jobs as $job) {
        if ($job['status'] === 'active') {
            $active_count++;
        } else if ($job['status'] === 'filled') {
            $filled_count++;
        }
        $total_applications += $job['application_count'];
    }
    
} else {
    // Fetch active jobs for worker (accepted applications)
    $active_jobs = [];
    $sql = "SELECT ja.*, j.title, j.description, j.salary, j.location, j.work_hours, j.type,
                   u.name as employer_name, u.email as employer_email,
                   b.start_date, b.end_date, b.status as booking_status
            FROM job_applications ja
            JOIN jobs j ON ja.job_id = j.id
            JOIN users u ON j.employer_id = u.id
            LEFT JOIN bookings b ON ja.worker_id = b.worker_id AND ja.job_id = (SELECT id FROM jobs WHERE employer_id = b.user_id LIMIT 1)
            WHERE ja.worker_id = :user_id AND ja.status = 'accepted'
            ORDER BY ja.applied_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $active_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate worker statistics
    $active_count = count($active_jobs);
    
    // Fetch completed jobs count
    $completed_sql = "SELECT COUNT(*) as completed FROM earnings WHERE worker_id = :user_id AND payment_status = 'paid'";
    $stmt = $conn->prepare($completed_sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $completed_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $completed_count = $completed_result['completed'];
    
    // Calculate earnings
    $this_month_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM earnings 
                      WHERE worker_id = :user_id AND payment_status = 'paid' 
                      AND EXTRACT(MONTH FROM work_date) = EXTRACT(MONTH FROM CURRENT_DATE) 
                      AND EXTRACT(YEAR FROM work_date) = EXTRACT(YEAR FROM CURRENT_DATE)";
    $stmt = $conn->prepare($this_month_sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $month_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $this_month_earnings = $month_result['total'];
    
    // Calculate pending payments
    $pending_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM earnings 
                   WHERE worker_id = :user_id AND payment_status = 'pending'";
    $stmt = $conn->prepare($pending_sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $pending_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pending_payments = $pending_result['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 60px);
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            position: fixed;
            top: 60px;
            left: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s;
            transform: translateX(-100%);
            border-radius: 0 20px 20px 0;
            box-shadow: 4px 0 12px rgba(0,0,0,0.15);
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 20px;
            border-radius: 12px;
            margin: 5px 10px;
            transition: all 0.3s;
            min-height: 50px;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            margin-right: 12px;
            width: 20px;
            font-size: 1rem;
        }

        .main-content {
            margin-left: 0;
            padding: 15px;
            min-height: calc(100vh - 60px);
            margin-top: 60px;
            background: #f8f9fa;
        }

        body {
            background: #f8f9fa;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0 20px 0 0;
        }

        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-size: 1.2rem;
        }

        .user-profile {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 24px;
            color: #000000;
        }

        .user-name {
            color: white;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .user-role {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }

        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 250px;
                padding: 20px;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            .sidebar {
                width: 260px;
            }
        }

        .card {
            margin-bottom: 1rem;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 15px;
        }

        .btn {
            min-height: 44px;
            padding: 12px 20px;
            font-size: 0.95rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
        }

        .btn-outline-primary {
            border: 2px solid #000000;
            color: #000000;
            background: white;
        }

        .btn-outline-primary:hover {
            background: #000000;
            color: white;
        }

        .btn-outline-secondary {
            border: 2px solid #e9ecef;
            color: var(--text-dark);
            background: white;
        }

        .btn-outline-secondary:hover {
            background: #f8f9fa;
            border-color: #e9ecef;
        }

        .btn-outline-danger {
            border: 2px solid #dc3545;
            color: #dc3545;
            background: white;
        }

        .btn-outline-danger:hover {
            background: #dc3545;
            color: white;
        }

        .btn-sm {
            min-height: 38px;
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .row > * {
            padding-left: 10px;
            padding-right: 10px;
        }

        .row {
            margin-left: -10px;
            margin-right: -10px;
        }

        .nav-tabs .nav-link {
            min-height: 44px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
        }

        .card-body {
            padding: 15px;
        }

        .card-header {
            padding: 12px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .table-responsive {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .form-control, .form-select {
            min-height: 44px;
            padding: 10px 15px;
            font-size: 16px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }

        .badge {
            font-size: 0.75rem;
            padding: 6px 10px;
        }

        h2 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #000000;
            font-weight: 700;
        }

        h5 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #000000;
            font-weight: 600;
        }

        .job-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 15px;
        }

        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .status-badge {
            font-size: 0.8rem;
        }

        .card.bg-primary {
            background: linear-gradient(135deg, #000000, #333333) !important;
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: none;
        }

        .card.bg-success {
            background: #000000 !important;
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: none;
        }

        .card.bg-info {
            background: #333333 !important;
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: none;
        }

        .card.bg-warning {
            background: #000000 !important;
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: none;
        }

        .card.bg-primary:hover, .card.bg-success:hover, .card.bg-info:hover, .card.bg-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }

        .card.bg-primary .card-title,
        .card.bg-success .card-title,
        .card.bg-info .card-title,
        .card.bg-warning .card-title {
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 600;
            opacity: 0.9;
        }

        .card.bg-primary h2,
        .card.bg-success h2,
        .card.bg-info h2,
        .card.bg-warning h2 {
            font-size: 2rem;
            margin-bottom: 0;
            font-weight: 700;
            color: white !important;
        }

        .card.bg-primary h5,
        .card.bg-success h5,
        .card.bg-info h5,
        .card.bg-warning h5 {
            color: white !important;
        }

        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: none;
        }

        .list-group-item {
            padding: 15px;
            border: none;
            border-bottom: 1px solid #dee2e6;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .row > * {
                padding-left: 8px;
                padding-right: 8px;
            }

            .row {
                margin-left: -8px;
                margin-right: -8px;
            }

            .col-lg-3.col-md-6.col-sm-12 {
                margin-bottom: 1rem;
            }

            .card.bg-primary h2,
            .card.bg-success h2,
            .card.bg-info h2,
            .card.bg-warning h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .row > * {
                padding-left: 5px;
                padding-right: 5px;
            }

            .row {
                margin-left: -5px;
                margin-right: -5px;
            }

            .card-body {
                padding: 1rem;
            }

            .card-header {
                padding: 0.75rem 1rem;
            }

            .card.bg-primary h2,
            .card.bg-success h2,
            .card.bg-info h2,
            .card.bg-warning h2 {
                font-size: 1.3rem;
            }

            .card.bg-primary .card-title,
            .card.bg-success .card-title,
            .card.bg-info .card-title,
            .card.bg-warning .card-title {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-home"></i> Household Connect</h3>
        </div>
        
        <nav class="nav flex-column p-3">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            
            <?php if ($user_role === 'employer'): ?>
            <a class="nav-link" href="post-job.php">
                <i class="fas fa-plus-circle"></i> Post Job
            </a>
            <a class="nav-link" href="workers.php">
                <i class="fas fa-users"></i> Find Workers
            </a>
            <a class="nav-link active" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> My Jobs
            </a>
            <a class="nav-link" href="bookings.php">
                <i class="fas fa-calendar-check"></i> Bookings
            </a>
            <?php else: ?>
            <a class="nav-link" href="jobs.php">
                <i class="fas fa-search"></i> Find Jobs
            </a>
            <a class="nav-link" href="my-applications.php">
                <i class="fas fa-file-alt"></i> My Applications
            </a>
            <a class="nav-link active" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> Active Jobs
            </a>
            <a class="nav-link" href="earnings.php">
                <i class="fas fa-money-bill-wave"></i> Earnings
            </a>
            <?php endif; ?>
            
            <a class="nav-link" href="messages.php">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user-cog"></i> Profile Settings
            </a>
            <a class="nav-link" href="reviews.php">
                <i class="fas fa-star"></i> Reviews
            </a>
            
            <hr class="text-white-50">
            
            <a class="nav-link" href="help.php">
                <i class="fas fa-question-circle"></i> Help & Support
            </a>
            <a class="nav-link" href="api/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row">
            <div class="col-12">
                <h2><?php echo $user_role === 'employer' ? 'My Posted Jobs' : 'My Active Jobs'; ?></h2>
                <p class="text-muted"><?php echo $user_role === 'employer' ? 'Manage your job postings and applications' : 'Track your current work assignments'; ?></p>
            </div>
        </div>

        <?php if ($user_role === 'employer'): ?>
        <!-- Employer View - Posted Jobs -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Posted</h5>
                        <h2><?php echo $total_posted; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active</h5>
                        <h2><?php echo $active_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Applications</h5>
                        <h2><?php echo $total_applications; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Filled</h5>
                        <h2><?php echo $filled_count; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <?php if (empty($posted_jobs)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        You haven't posted any jobs yet. Click "Post Job" to get started!
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($posted_jobs as $job): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card job-card" id="job-<?php echo $job['id']; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                    <span class="badge bg-dark status-badge"><?php echo ucfirst(htmlspecialchars($job['status'])); ?></span>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars(substr($job['description'], 0, 120)) . '...'; ?></p>
                                <div class="mb-2">
                                    <small class="text-muted">Posted: <?php echo format_date($job['created_at']); ?></small><br>
                                    <small class="text-muted">Applications: <?php echo $job['application_count']; ?></small><br>
                                    <small class="text-muted">Salary: <?php echo format_currency($job['salary']); ?>/month</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-dark" onclick="viewApplications(<?php echo $job['id']; ?>)">
                                        <i class="fas fa-eye me-1"></i>View Applications
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="editJob(<?php echo $job['id']; ?>)">
                                        <i class="fas fa-edit me-1"></i>Edit Job
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteJob(<?php echo $job['id']; ?>)">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <!-- Worker View - Active Jobs -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active Jobs</h5>
                        <h2><?php echo $active_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Completed</h5>
                        <h2><?php echo $completed_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">This Month</h5>
                        <h2><?php echo format_currency($this_month_earnings); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pending Payment</h5>
                        <h2><?php echo format_currency($pending_payments); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <?php if (empty($active_jobs)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        You don't have any active jobs yet. Start applying for jobs to get work!
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($active_jobs as $job): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card job-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">
                                        <?php 
                                        $icons = [
                                            'cleaning' => 'fa-broom',
                                            'cooking' => 'fa-utensils',
                                            'childcare' => 'fa-child',
                                            'eldercare' => 'fa-user-nurse',
                                            'gardening' => 'fa-seedling',
                                            'other' => 'fa-briefcase'
                                        ];
                                        $icon = isset($icons[$job['type']]) ? $icons[$job['type']] : 'fa-briefcase';
                                        ?>
                                        <?php echo htmlspecialchars($job['title']); ?> - <?php echo htmlspecialchars($job['employer_name']); ?>
                                    </h5>
                                    <span class="badge bg-dark status-badge">
                                        <?php echo isset($job['booking_status']) && $job['booking_status'] === 'confirmed' ? 'Confirmed' : 'Active'; ?>
                                    </span>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars(substr($job['description'], 0, 120)) . '...'; ?></p>
                                <div class="mb-2">
                                    <small class="text-muted">Started: <?php echo isset($job['start_date']) ? format_date($job['start_date']) : 'Recently'; ?></small><br>
                                    <small class="text-muted">Salary: <?php echo format_currency($job['salary']); ?>/month</small><br>
                                    <small class="text-muted">Next Payment: <?php echo isset($job['end_date']) ? format_date($job['end_date']) : 'TBD'; ?></small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-dark" onclick="viewJobDetails(<?php echo $job['job_id']; ?>)">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="messageEmployer(<?php echo $job['job_id']; ?>, '<?php echo htmlspecialchars($job['employer_name']); ?>')">
                                        <i class="fas fa-envelope me-1"></i>Message Employer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        <?php if ($user_role === 'employer'): ?>
        // View applications for a job
        function viewApplications(jobId) {
            window.location.href = 'job-applications.php?job_id=' + jobId;
        }
        
        // Edit job
        function editJob(jobId) {
            window.location.href = 'edit-job.php?id=' + jobId;
        }
        
        // Delete job
        function deleteJob(jobId) {
            if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
                fetch('./api/delete-job.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        job_id: jobId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove job from page
                        const jobElement = document.getElementById('job-' + jobId);
                        if (jobElement) {
                            jobElement.remove();
                        }
                        // Show success message
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
                        alertDiv.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px;';
                        alertDiv.innerHTML = `
                            <button type="button" class="btn-close" data-bs-dismiss="alert">&times;</button>
                            <strong>Success!</strong> ${data.message}
                        `;
                        document.body.appendChild(alertDiv);
                        
                        // Auto-remove after 3 seconds
                        setTimeout(() => {
                            if (alertDiv.parentNode) {
                                alertDiv.parentNode.removeChild(alertDiv);
                            }
                        }, 3000);
                        
                        // Reload page after short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete job. Please try again.');
                });
            }
        }
        <?php else: ?>
        // View job details
        function viewJobDetails(jobId) {
            window.location.href = 'job-details.php?id=' + jobId;
        }
        
        // Message employer
        function messageEmployer(jobId, employerName) {
            if (confirm('Would you like to send a message to ' + employerName + '?')) {
                window.location.href = 'messages.php?job_id=' + jobId;
            }
        }
        <?php endif; ?>

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('mobile-menu-toggle');

            if (window.innerWidth < 992 &&
                !sidebar.contains(event.target) &&
                !toggle?.contains(event.target) &&
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('show');
            }
        });
    </script>
</body>
</html>
