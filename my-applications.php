<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('index.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Only workers should access this page
if ($user_role !== 'worker') {
    redirect('dashboard.php');
}

// Fetch applications from database
$applications = [];
$sql = "SELECT ja.*, j.title, j.description, j.salary, j.location, j.work_hours, j.type,
               u.name as employer_name, u.email as employer_email
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        JOIN users u ON j.employer_id = u.id
        WHERE ja.worker_id = ?
        ORDER BY ja.applied_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_applications = count($applications);
$pending_count = 0;
$under_review_count = 0;
$accepted_count = 0;
$rejected_count = 0;

foreach ($applications as $app) {
    switch ($app['status']) {
        case 'pending':
            $pending_count++;
            break;
        case 'under_review':
            $under_review_count++;
            break;
        case 'accepted':
            $accepted_count++;
            break;
        case 'rejected':
            $rejected_count++;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Household Connect</title>
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
        
        /* Update main content background for consistency */
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
        
        /* Mobile-first responsive design */
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
        
        .application-card {
            transition: transform 0.2s;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background: white;
        }
        
        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            border-color: #000000;
        }
        
        .status-badge {
            font-size: 0.8rem;
        }
        
        /* Statistics cards styling */
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
            
            <a class="nav-link" href="jobs.php">
                <i class="fas fa-search"></i> Find Jobs
            </a>
            <a class="nav-link active" href="my-applications.php">
                <i class="fas fa-file-alt"></i> My Applications
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> Active Jobs
            </a>
            <a class="nav-link" href="earnings.php">
                <i class="fas fa-money-bill-wave"></i> Earnings
            </a>
            
            <a class="nav-link" href="messages.php">
                <i class="fas fa-envelope"></i> Messages
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
                <h2>My Applications</h2>
                <p class="text-muted">Track your job applications and their status</p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-file-alt me-2"></i>Total Applications</h5>
                        <h2><?php echo $total_applications; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-clock me-2"></i>Pending</h5>
                        <h2><?php echo $pending_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-search me-2"></i>Under Review</h5>
                        <h2><?php echo $under_review_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-check-circle me-2"></i>Accepted</h5>
                        <h2><?php echo $accepted_count; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-tabs" id="applicationTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">All Applications</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">Pending</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="review-tab" data-bs-toggle="tab" data-bs-target="#review" type="button" role="tab">Under Review</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="accepted-tab" data-bs-toggle="tab" data-bs-target="#accepted" type="button" role="tab">Accepted</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab">Rejected</button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Application Lists -->
        <div class="tab-content" id="applicationTabsContent">
            <!-- All Applications -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <div class="row">
                    <?php if (empty($applications)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                You haven't applied for any jobs yet. Start browsing jobs to apply!
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($applications as $application): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card application-card">
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
                                                $icon = isset($icons[$application['type']]) ? $icons[$application['type']] : 'fa-briefcase';
                                                ?>
                                                <i class="fas <?php echo $icon; ?> me-2"></i><?php echo htmlspecialchars($application['title']); ?>
                                            </h5>
                                            <span class="badge bg-dark status-badge"><?php echo ucfirst(htmlspecialchars($application['status'])); ?></span>
                                        </div>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($application['description'], 0, 120)) . '...'; ?></p>
                                        <div class="mb-2">
                                            <small class="text-muted"><i class="fas fa-user me-1"></i>Employer: <?php echo htmlspecialchars($application['employer_name']); ?></small><br>
                                            <small class="text-muted"><i class="fas fa-calendar me-1"></i>Applied: <?php echo format_date($application['applied_at']); ?></small><br>
                                            <small class="text-muted"><i class="fas fa-money-bill-wave me-1"></i>Salary: <?php echo format_currency($application['salary']); ?>/month</small>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-dark" onclick="viewApplicationDetails(<?php echo $application['id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="messageEmployer(<?php echo $application['job_id']; ?>, '<?php echo htmlspecialchars($application['employer_name']); ?>')">
                                                <i class="fas fa-envelope me-1"></i>Message Employer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pending Applications -->
            <div class="tab-pane fade" id="pending" role="tabpanel">
                <div id="pending-applications">
                    <p class="text-muted">Loading pending applications...</p>
                </div>
            </div>
            
            <!-- Under Review Applications -->
            <div class="tab-pane fade" id="review" role="tabpanel">
                <div id="review-applications">
                    <p class="text-muted">Loading applications under review...</p>
                </div>
            </div>
            
            <!-- Accepted Applications -->
            <div class="tab-pane fade" id="accepted" role="tabpanel">
                <div id="accepted-applications">
                    <p class="text-muted">Loading accepted applications...</p>
                </div>
            </div>
            
            <!-- Rejected Applications -->
            <div class="tab-pane fade" id="rejected" role="tabpanel">
                <div id="rejected-applications">
                    <p class="text-muted">Loading rejected applications...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // View application details
        function viewApplicationDetails(applicationId) {
            // You can implement a modal or redirect to details page
            alert('Application details feature coming soon! Application ID: ' + applicationId);
        }
        
        // Message employer
        function messageEmployer(jobId, employerName) {
            if (confirm('Would you like to send a message to ' + employerName + '?')) {
                // Redirect to messages page with job context
                window.location.href = 'messages.php?job_id=' + jobId;
            }
        }
        
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
        
        // Load accepted applications data
        document.addEventListener('DOMContentLoaded', function() {
            loadAcceptedApplications();
            loadPendingApplications();
            loadUnderReviewApplications();
            loadRejectedApplications();
        });
        
        function loadAcceptedApplications() {
            const acceptedContainer = document.getElementById('accepted-applications');
            if (acceptedContainer) {
                <?php
                $accepted_apps = array_filter($applications, fn($a) => $a['status'] === 'accepted');
                if (!empty($accepted_apps)):
                ?>
                    acceptedContainer.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Accepted Applications</h4>
                            <p class="text-muted">You haven't been accepted for any jobs yet.</p>
                        </div>
                    `;
                <?php else: ?>
                    acceptedContainer.innerHTML = `
                        <?php foreach ($accepted_apps as $app): ?>
                            <div class="application-card">
                                <div class="row align-items-start">
                                    <div class="col-md-8">
                                        <div class="application-header">
                                            <h5 class="application-title"><?php echo htmlspecialchars($app['title']); ?></h5>
                                            <div class="application-meta">
                                                <span class="employer"><?php echo htmlspecialchars($app['employer_name']); ?></span>
                                                <span class="date"><?php echo date('M j, Y', strtotime($app['applied_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="application-description">
                                            <p><?php echo htmlspecialchars($app['description'] ?? 'No description provided'); ?></p>
                                        </div>
                                        <div class="application-details">
                                            <div class="detail-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($app['location']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-money-bill"></i>
                                                <span><?php echo htmlspecialchars($app['salary']); ?></span>
                                            </div>
                                            <?php if (!empty($app['work_hours'])): ?>
                                            <div class="detail-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo htmlspecialchars($app['work_hours']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="application-status">
                                            <span class="status-badge status-accepted">Accepted</span>
                                            <div class="status-date">
                                                <small>Accepted on: <?php echo date('M j, Y', strtotime($app['updated_at'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="action-buttons">
                                            <button class="btn btn-primary btn-sm" onclick="viewApplicationDetails(<?php echo $app['id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </button>
                                            <button class="btn btn-success btn-sm" onclick="messageEmployer(<?php echo $app['job_id']; ?>, '<?php echo addslashes($app['employer_name']); ?>')">
                                                <i class="fas fa-envelope me-1"></i>Message Employer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    `;
                <?php endif; ?>
            }
        }
        
        function loadPendingApplications() {
            const pendingContainer = document.getElementById('pending-applications');
            if (pendingContainer) {
                <?php
                $pending_apps = array_filter($applications, fn($a) => $a['status'] === 'pending');
                if (!empty($pending_apps)):
                ?>
                    pendingContainer.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Pending Applications</h4>
                            <p class="text-muted">You don't have any pending applications.</p>
                        </div>
                    `;
                <?php else: ?>
                    pendingContainer.innerHTML = `
                        <?php foreach ($pending_apps as $app): ?>
                            <div class="application-card">
                                <div class="row align-items-start">
                                    <div class="col-md-8">
                                        <div class="application-header">
                                            <h5 class="application-title"><?php echo htmlspecialchars($app['title']); ?></h5>
                                            <div class="application-meta">
                                                <span class="employer"><?php echo htmlspecialchars($app['employer_name']); ?></span>
                                                <span class="date"><?php echo date('M j, Y', strtotime($app['applied_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="application-description">
                                            <p><?php echo htmlspecialchars($app['description'] ?? 'No description provided'); ?></p>
                                        </div>
                                        <div class="application-details">
                                            <div class="detail-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($app['location']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-money-bill"></i>
                                                <span><?php echo htmlspecialchars($app['salary']); ?></span>
                                            </div>
                                            <?php if (!empty($app['work_hours'])): ?>
                                            <div class="detail-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo htmlspecialchars($app['work_hours']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="application-status">
                                            <span class="status-badge status-pending">Pending</span>
                                            <div class="status-date">
                                                <small>Applied on: <?php echo date('M j, Y', strtotime($app['applied_at'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="action-buttons">
                                            <button class="btn btn-primary btn-sm" onclick="viewApplicationDetails(<?php echo $app['id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </button>
                                            <button class="btn btn-info btn-sm" onclick="messageEmployer(<?php echo $app['job_id']; ?>, '<?php echo addslashes($app['employer_name']); ?>')">
                                                <i class="fas fa-envelope me-1"></i>Message Employer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    `;
                <?php endif; ?>
            }
        }
        
        function loadUnderReviewApplications() {
            const reviewContainer = document.getElementById('review-applications');
            if (reviewContainer) {
                <?php
                $review_apps = array_filter($applications, fn($a) => $a['status'] === 'under_review');
                if (!empty($review_apps)):
                ?>
                    reviewContainer.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Applications Under Review</h4>
                            <p class="text-muted">You don't have any applications under review.</p>
                        </div>
                    `;
                <?php else: ?>
                    reviewContainer.innerHTML = `
                        <?php foreach ($review_apps as $app): ?>
                            <div class="application-card">
                                <div class="row align-items-start">
                                    <div class="col-md-8">
                                        <div class="application-header">
                                            <h5 class="application-title"><?php echo htmlspecialchars($app['title']); ?></h5>
                                            <div class="application-meta">
                                                <span class="employer"><?php echo htmlspecialchars($app['employer_name']); ?></span>
                                                <span class="date"><?php echo date('M j, Y', strtotime($app['applied_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="application-description">
                                            <p><?php echo htmlspecialchars($app['description'] ?? 'No description provided'); ?></p>
                                        </div>
                                        <div class="application-details">
                                            <div class="detail-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($app['location']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-money-bill"></i>
                                                <span><?php echo htmlspecialchars($app['salary']); ?></span>
                                            </div>
                                            <?php if (!empty($app['work_hours'])): ?>
                                            <div class="detail-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo htmlspecialchars($app['work_hours']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="application-status">
                                            <span class="status-badge status-under_review">Under Review</span>
                                            <div class="status-date">
                                                <small>Under Review Since: <?php echo date('M j, Y', strtotime($app['updated_at'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="action-buttons">
                                            <button class="btn btn-primary btn-sm" onclick="viewApplicationDetails(<?php echo $app['id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </button>
                                            <button class="btn btn-info btn-sm" onclick="messageEmployer(<?php echo $app['job_id']; ?>, '<?php echo addslashes($app['employer_name']); ?>')">
                                                <i class="fas fa-envelope me-1"></i>Message Employer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    `;
                <?php endif; ?>
            }
        }
        
        function loadRejectedApplications() {
            const rejectedContainer = document.getElementById('rejected-applications');
            if (rejectedContainer) {
                <?php
                $rejected_apps = array_filter($applications, fn($a) => $a['status'] === 'rejected');
                if (!empty($rejected_apps)):
                ?>
                    rejectedContainer.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-times-circle fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Rejected Applications</h4>
                            <p class="text-muted">You don't have any rejected applications.</p>
                        </div>
                    `;
                <?php else: ?>
                    rejectedContainer.innerHTML = `
                        <?php foreach ($rejected_apps as $app): ?>
                            <div class="application-card">
                                <div class="row align-items-start">
                                    <div class="col-md-8">
                                        <div class="application-header">
                                            <h5 class="application-title"><?php echo htmlspecialchars($app['title']); ?></h5>
                                            <div class="application-meta">
                                                <span class="employer"><?php echo htmlspecialchars($app['employer_name']); ?></span>
                                                <span class="date"><?php echo date('M j, Y', strtotime($app['applied_at'])); ?></span>
                                            </div>
                                        </div>
                                        <div class="application-description">
                                            <p><?php echo htmlspecialchars($app['description'] ?? 'No description provided'); ?></p>
                                        </div>
                                        <div class="application-details">
                                            <div class="detail-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($app['location']); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <i class="fas fa-money-bill"></i>
                                                <span><?php echo htmlspecialchars($app['salary']); ?></span>
                                            </div>
                                            <?php if (!empty($app['work_hours'])): ?>
                                            <div class="detail-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo htmlspecialchars($app['work_hours']); ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="application-status">
                                            <span class="status-badge status-rejected">Rejected</span>
                                            <div class="status-date">
                                                <small>Rejected on: <?php echo date('M j, Y', strtotime($app['updated_at'])); ?></small>
                                            </div>
                                        </div>
                                        <div class="action-buttons">
                                            <button class="btn btn-primary btn-sm" onclick="viewApplicationDetails(<?php echo $app['id']; ?>)">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </button>
                                            <button class="btn btn-info btn-sm" onclick="messageEmployer(<?php echo $app['job_id']; ?>, '<?php echo addslashes($app['employer_name']); ?>')">
                                                <i class="fas fa-envelope me-1"></i>Message Employer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    `;
                <?php endif; ?>
            }
        }
        
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
