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

// Check if worker has a profile (only for workers)
$has_worker_profile = false;
if ($user_role === 'worker') {
    $check_sql = "SELECT id FROM workers WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $has_worker_profile = $check_result->num_rows > 0;
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Household Connect</title>
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
        
        /* Mobile optimizations */
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
        
        .btn-sm {
            min-height: 38px;
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        /* Better mobile spacing */
        .row > * {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .row {
            margin-left: -10px;
            margin-right: -10px;
        }
        
        /* Larger touch targets for mobile */
        .nav-tabs .nav-link {
            min-height: 44px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
        }
        
        /* Mobile-friendly cards */
        .card-body {
            padding: 15px;
        }
        
        .card-header {
            padding: 12px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        /* Mobile table responsiveness */
        .table-responsive {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Form optimizations for mobile */
        .form-control, .form-select {
            min-height: 44px;
            padding: 10px 15px;
            font-size: 16px; /* Prevents zoom on iOS */
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }
        
        /* Mobile-friendly badges */
        .badge {
            font-size: 0.75rem;
            padding: 6px 10px;
        }
        
        /* Better mobile typography */
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
        
        /* Welcome section styling */
        .welcome-section {
            padding: 1rem 0 2rem 0;
            margin-bottom: 2rem;
        }
        
        .welcome-title {
            color: #000000;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .welcome-subtitle {
            color: #666666;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .welcome-stats {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666666;
            font-size: 0.95rem;
        }
        
        .stat-item i {
            color: #000000 !important;
            font-size: 1rem;
        }
        
        .stat-text {
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .welcome-section {
                padding: 0.5rem 0 1.5rem 0;
                margin-bottom: 1.5rem;
            }
            
            .welcome-title {
                font-size: 1.5rem;
            }
            
            .welcome-subtitle {
                font-size: 1rem;
            }
            
            .welcome-stats {
                gap: 1rem;
            }
            
            .stat-item {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .welcome-section {
                padding: 0 0 1rem 0;
                margin-bottom: 1rem;
            }
            
            .welcome-title {
                font-size: 1.3rem;
            }
            
            .welcome-stats {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
        
        /* Mobile-friendly statistics cards */
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
            background: #000000 !important;
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
        }
        
        /* General card improvements */
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: none;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .card-header {
            border-radius: 15px 15px 0 0;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.25rem;
            background: #f8f9fa;
            font-weight: 600;
        }
        
        /* Better responsive grid */
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
        
        /* Mobile-friendly dropdowns */
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: none;
        }
        
        /* Better mobile spacing for lists */
        .list-group-item {
            padding: 15px;
            border: none;
            border-bottom: 1px solid #dee2e6;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        /* Ensure card numbers are visible */
        .card.bg-primary h2,
        .card.bg-success h2,
        .card.bg-info h2,
        .card.bg-warning h2 {
            font-size: 2rem;
            margin-bottom: 0;
            font-weight: 700;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            line-height: 1.2;
        }
        
        /* Fix any potential overflow issues */
        .card-body {
            overflow: hidden;
        }
        
        /* Ensure proper spacing for numbers */
        .card-body h2 {
            min-height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Modern Worker Cards */
        .modern-worker-card {
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            background: white;
        }

        .modern-worker-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: #007bff;
        }

        .modern-worker-card .rounded-circle {
            transition: transform 0.3s ease;
        }

        .modern-worker-card:hover .rounded-circle {
            transform: scale(1.05);
        }

        .modern-worker-card .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .modern-worker-card .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-1px);
        }

        .modern-worker-card .card-title {
            color: #212529;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .modern-worker-card .text-muted {
            color: #6c757d !important;
            font-size: 0.9rem;
        }

        .modern-worker-card .card-text {
            color: #6c757d;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        /* Minimal worker cards styling */
        .worker-card {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            height: 100%;
            background: white;
        }
        
        .worker-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        
        .worker-card .card-img-top {
            height: 180px;
            object-fit: cover;
            position: relative;
            width: 100%;
        }
        
        .worker-card .card-img-placeholder {
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .worker-card .card-img-placeholder span {
            color: white;
            font-size: 3rem;
            font-weight: bold;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .worker-card .profile-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.1) 100%);
            pointer-events: none;
        }
        
        .worker-card .card-body {
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
        }
        
        .worker-card .worker-info {
            text-align: center;
        }
        
        .worker-card .worker-name {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #333;
        }
        
        .worker-card .worker-type {
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .worker-card .worker-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
        }
        
        .worker-card .worker-rating {
            color: #ffc107;
        }
        
        .worker-card .worker-rate {
            font-weight: 600;
            color: #007bff;
        }
        
        .worker-card .worker-actions {
            margin-top: auto;
        }
        
        .worker-card .btn-view-profile {
            width: 100%;
            background: #007bff;
            border: none;
            color: white;
            padding: 0.4rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .worker-card .btn-view-profile:hover {
            background: #0056b3;
        }
        
        .worker-card .availability-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 0.2rem 0.4rem;
            border-radius: 10px;
            font-size: 0.6rem;
            font-weight: 600;
        }
        
        .worker-card .availability-badge.busy {
            background: rgba(255, 193, 7, 0.9);
        }
        
        /* Search and filter styling */
        .form-control-sm, .form-select-sm {
            border-radius: 8px;
        }
        
        .pagination .page-link {
            border-radius: 6px;
            margin: 0 2px;
            border: 1px solid #dee2e6;
            color: #000000;
        }
        
        .pagination .page-link:hover {
            background-color: #f8f9fa;
            border-color: #000000;
            color: #000000;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #000000;
            border-color: #000000;
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
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            
            <?php if ($user_role === 'employer'): ?>
            <a class="nav-link" href="post-job.php">
                <i class="fas fa-plus-circle"></i> Post Job
            </a>
            <a class="nav-link" href="workers.php">
                <i class="fas fa-users"></i> Find Workers
            </a>
            <a class="nav-link" href="my-jobs.php">
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
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> Active Jobs
            </a>
            <a class="nav-link" href="earnings.php">
                <i class="fas fa-money-bill-wave"></i> Earnings
            </a>
            <?php endif; ?>
            
            <a class="nav-link" href="messages.php">
                <i class="fas fa-envelope"></i> Messages
            </a>
            
            <hr class="text-white-50">
            
            <a class="nav-link" href="help.php">
                <i class="fas fa-question-circle"></i> Help & Support
            </a>
            <a class="nav-link" href="#" onclick="confirmLogout(event)">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row">
            <div class="col-12">
                <div class="welcome-section">
                    <h2 class="welcome-title">Welcome back, <?php echo htmlspecialchars($user_name); ?>! 👋</h2>
                    <p class="welcome-subtitle text-muted">We're glad to see you again! Here's what's happening with your <?php echo htmlspecialchars($user_role); ?> dashboard today.</p>
                    <div class="welcome-stats d-flex gap-4 mt-3">
                        <div class="stat-item">
                            <i class="fas fa-calendar-day text-primary"></i>
                            <span class="stat-text"><?php echo date('l, F j, Y'); ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-clock text-primary"></i>
                            <span class="stat-text"><?php echo date('g:i A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($user_role === 'worker' && !$has_worker_profile): ?>
        <!-- Worker Profile Creation Prompt -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-plus fa-2x me-3"></i>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1">Create Your Worker Profile</h5>
                            <p class="mb-2">To start finding jobs, you need to create your worker profile. This will help employers learn about your skills and experience.</p>
                            <a href="create-worker-profile.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Create Profile Now
                            </a>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user_role === 'employer'): ?>
        <!-- Employer Dashboard -->
        <div class="row mt-4">
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Posted Jobs</h5>
                        <h2 id="posted-jobs-count">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Active Bookings</h5>
                        <h2 id="active-bookings-count">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-8 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Available Workers</h5>
                        <div class="d-flex gap-2">
                            <input type="text" id="worker-search" class="form-control form-control-sm" placeholder="Search workers..." style="width: 200px;">
                            <select id="worker-type-filter" class="form-select form-select-sm" style="width: 150px;">
                                <option value="">All Types</option>
                                <option value="cleaning">Cleaning</option>
                                <option value="childcare">Childcare</option>
                                <option value="gardening">Gardening</option>
                                <option value="eldercare">Elder Care</option>
                                <option value="cooking">Cooking</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="workers-container" class="row">
                            <p class="text-muted col-12">Loading available workers...</p>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            <nav id="workers-pagination"></nav>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5>Recent Job Postings</h5>
                    </div>
                    <div class="card-body">
                        <div id="recent-jobs">
                            <p class="text-muted">Loading recent jobs...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Worker Dashboard -->
        <div class="row mt-4">
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Jobs Applied</h5>
                        <h2 id="jobs-applied-count">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Active Jobs</h5>
                        <h2 id="active-jobs-count">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-8 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5>Available Jobs</h5>
                    </div>
                    <div class="card-body">
                        <div id="available-jobs">
                            <p class="text-muted">Loading available jobs...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="profile.php" class="btn btn-primary">Update Profile</a>
                            <a href="jobs.php" class="btn btn-outline-primary">Browse Jobs</a>
                            <a href="messages.php" class="btn btn-outline-secondary">Messages</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Worker Profile Modal -->
    <div class="modal fade" id="workerProfileModal" tabindex="-1" aria-labelledby="workerProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="workerProfileModalLabel">
                        <i class="fas fa-user me-2"></i>Worker Profile
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="worker-profile-content">
                        <!-- Worker profile content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                    <button type="button" class="btn btn-warning" id="modal-contact-btn">
                        <i class="fas fa-envelope me-2"></i>Contact
                    </button>
                    <button type="button" class="btn btn-success" id="modal-book-btn">
                        <i class="fas fa-calendar-check me-2"></i>Book Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contact Worker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="contact-form">
                        <div class="mb-3">
                            <label for="message-subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="message-subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message-body" class="form-label">Message</label>
                            <textarea class="form-control" id="message-body" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="send-message-btn">Send Message</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book Worker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="booking-form">
                        <div class="mb-3">
                            <label for="booking-start" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="booking-start" required>
                        </div>
                        <div class="mb-3">
                            <label for="booking-end" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="booking-end" required>
                        </div>
                        <div class="mb-3">
                            <label for="booking-service" class="form-label">Service Type</label>
                            <select class="form-select" id="booking-service" required>
                                <option value="cleaning">Cleaning</option>
                                <option value="cooking">Cooking</option>
                                <option value="childcare">Childcare</option>
                                <option value="eldercare">Eldercare</option>
                                <option value="gardening">Gardening</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="booking-notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="booking-notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="create-booking-btn">Create Booking</button>
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
        
        // Load dashboard data
        document.addEventListener('DOMContentLoaded', function() {
            const userRole = '<?php echo $user_role; ?>';
            console.log('Dashboard loaded for user role:', userRole);
            
            // Show loading state
            const loadingElements = ['posted-jobs-count', 'active-bookings-count', 'jobs-applied-count', 'active-jobs-count'];
            loadingElements.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = 'Loading...';
                    console.log('Set loading for element:', id);
                }
            });
            
            // Set fallback values immediately to ensure something is visible
            setTimeout(() => {
                console.log('Setting fallback values after timeout...');
                if (userRole === 'employer') {
                    loadEmployerData(null); // Force empty state
                    loadWorkers(); // Load workers for employers
                } else {
                    loadWorkerData(null); // Force empty state
                }
            }, 500); // Reduced timeout for faster display
            
            // Fetch real data from API
            fetch('./api/dashboard-data-simple.php', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('API Response Status:', response.status);
                if (response.status === 401) {
                    throw new Error('Session expired. Please log in again.');
                } else if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('API Response:', result);
                if (result.success) {
                    if (userRole === 'employer') {
                        loadEmployerData(result.data);
                        loadWorkers(); // Load workers for employers
                    } else {
                        loadWorkerData(result.data);
                    }
                } else {
                    console.error('API Error:', result.message);
                    if (result.message.includes('Unauthorized') || result.message.includes('Session')) {
                        showErrorMessage('Session expired. <a href="login.php" class="alert-link">Please log in again</a>.');
                    } else {
                        showErrorMessage('Failed to load dashboard data: ' + result.message);
                    }
                    // Fallback to empty state data
                    if (userRole === 'employer') {
                        loadEmployerData();
                        loadWorkers(); // Load workers for employers
                    } else {
                        loadWorkerData();
                    }
                }
            })
            .catch(error => {
                console.error('Network Error:', error);
                if (error.message.includes('Session expired')) {
                    showErrorMessage('Session expired. <a href="login.php" class="alert-link">Please log in again</a>.');
                } else {
                    showErrorMessage('Network error. Please check your connection and try again.');
                }
                // Fallback to empty state data
                if (userRole === 'employer') {
                    loadEmployerData();
                    loadWorkers(); // Load workers for employers
                } else {
                    loadWorkerData();
                }
            });
        });
        
        function showErrorMessage(message) {
            // Show error message in console and optionally on page
            console.error(message);
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning alert-dismissible fade show';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            const container = document.querySelector('.main-content .row .col-12');
            if (container) {
                container.insertBefore(alertDiv, container.firstChild);
            }
        }
        
        function loadEmployerData(data) {
            console.log('Loading employer data:', data);
            if (!data) {
                // No data available - show empty state
                data = {
                    posted_jobs: { total: 0, active: 0, filled: 0 },
                    active_bookings: { active: 0 },
                    recent_jobs: []
                };
                console.log('Using empty employer data:', data);
            }
            
            // Update statistics with fallback values - ensure numbers are visible
            const postedJobsEl = document.getElementById('posted-jobs-count');
            if (postedJobsEl) {
                const count = data.posted_jobs?.total || 0;
                postedJobsEl.textContent = count;
                postedJobsEl.style.display = 'block';
                postedJobsEl.style.visibility = 'visible';
                console.log('Set posted jobs count:', count);
            }
            
            const activeBookingsEl = document.getElementById('active-bookings-count');
            if (activeBookingsEl) {
                const count = data.active_bookings?.active || 0;
                activeBookingsEl.textContent = count;
                activeBookingsEl.style.display = 'block';
                activeBookingsEl.style.visibility = 'visible';
                console.log('Set active bookings count:', count);
            }
            
            // Update recent jobs
            const recentJobsContainer = document.getElementById('recent-jobs');
            if (recentJobsContainer) {
                if (data.recent_jobs && data.recent_jobs.length > 0) {
                    recentJobsContainer.innerHTML = data.recent_jobs.map(job => `
                        <div class="list-group-item">
                            <h6>${job.title}</h6>
                            <small class="text-muted">
                                ${job.applications || 0} applications - 
                                ${formatCurrency(job.salary)} - 
                                ${formatDate(job.created_at)}
                            </small>
                        </div>
                    `).join('');
                } else {
                    recentJobsContainer.innerHTML = '<p class="text-muted">No recent job postings</p>';
                }
            }
        }
        
        function loadWorkerData(data) {
            console.log('Loading worker data:', data);
            if (!data) {
                // No data available - show empty state
                data = {
                    jobs_applied: { total: 0, pending: 0, under_review: 0, accepted: 0 },
                    active_jobs: { active: 0 },
                    available_jobs: []
                };
                console.log('Using empty worker data:', data);
            }
            
            // Update statistics with fallback values - ensure numbers are visible
            const jobsAppliedEl = document.getElementById('jobs-applied-count');
            if (jobsAppliedEl) {
                const count = data.jobs_applied?.total || 0;
                jobsAppliedEl.textContent = count;
                jobsAppliedEl.style.display = 'block';
                jobsAppliedEl.style.visibility = 'visible';
                console.log('Set jobs applied count:', count);
            }
            
            const activeJobsEl = document.getElementById('active-jobs-count');
            if (activeJobsEl) {
                const count = data.active_jobs?.active || 0;
                activeJobsEl.textContent = count;
                activeJobsEl.style.display = 'block';
                activeJobsEl.style.visibility = 'visible';
                console.log('Set active jobs count:', count);
            }
            
            // Update available jobs
            const availableJobsContainer = document.getElementById('available-jobs');
            if (availableJobsContainer) {
                if (data.available_jobs && data.available_jobs.length > 0) {
                    availableJobsContainer.innerHTML = data.available_jobs.map(job => `
                        <div class="list-group-item">
                            <h6>${job.title}</h6>
                            <small class="text-muted">
                                ${job.employer_name} - ${job.location} - 
                                ${formatCurrency(job.salary)}
                            </small>
                            <button class="btn btn-sm btn-primary mt-2" onclick="applyForJob(${job.id})">Apply Now</button>
                        </div>
                    `).join('');
                } else {
                    availableJobsContainer.innerHTML = '<p class="text-muted">No available jobs at the moment</p>';
                }
            }
        }
        
        function formatCurrency(amount) {
            return 'RWF ' + Number(amount || 0).toLocaleString();
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-RW', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }
        
        function applyForJob(jobId) {
            // Implement job application logic
            alert('Application functionality will be implemented soon!');
        }
        
        // Logout confirmation function (same as in navbar)
        function confirmLogout(event) {
            event.preventDefault();
            
            // Create confirmation modal
            const modalHtml = `
                <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="logoutModalLabel">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Logout
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    <i class="fas fa-sign-out-alt fa-3x text-danger mb-3"></i>
                                </div>
                                <h6 class="text-center">Are you sure you want to logout?</h6>
                                <p class="text-muted text-center mb-0">You will be redirected to the homepage and will need to login again to access your account.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                                <button type="button" class="btn btn-danger" onclick="performLogout()">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if present
            const existingModal = document.getElementById('logoutModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('logoutModal'));
            modal.show();
        }
        
        // Perform logout function (same as in navbar)
        function performLogout() {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('logoutModal'));
            modal.hide();
            
            // Show loading indicator
            const loadingHtml = `
                <div class="modal fade" id="logoutLoadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center py-4">
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <h6>Logging out...</h6>
                                <p class="text-muted mb-0">Please wait while we secure your session.</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add loading modal
            document.body.insertAdjacentHTML('beforeend', loadingHtml);
            const loadingModal = new bootstrap.Modal(document.getElementById('logoutLoadingModal'));
            loadingModal.show();
            
            // Perform logout via AJAX
            fetch('./api/logout.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    // Show success message briefly
                    setTimeout(() => {
                        loadingModal.hide();
                        // Redirect to homepage
                        window.location.href = './index.php';
                    }, 1000);
                } else {
                    throw new Error('Logout failed');
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                loadingModal.hide();
                // Fallback: redirect anyway
                window.location.href = './index.php';
            });
        }
        
        // Workers loading functions for employers
        let currentPage = 1;
        let currentFilters = {};
        
        function loadWorkers(page = 1, filters = {}) {
            currentPage = page;
            currentFilters = filters;
            
            console.log('Loading workers page:', page, 'with filters:', filters);
            
            const container = document.getElementById('workers-container');
            if (container) {
                container.innerHTML = '<p class="text-muted col-12">Loading workers...</p>';
            }
            
            // Build query parameters
            const params = new URLSearchParams({
                page: page,
                ...filters
            });
            
            fetch(`./api/all-workers.php?${params}`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('Workers API Response:', result);
                if (result.success) {
                    displayWorkers(result.data);
                    displayPagination(result.pagination);
                } else {
                    console.error('Workers API Error:', result.message);
                    displayFallbackWorkers();
                }
            })
            .catch(error => {
                console.error('Error loading workers:', error);
                displayFallbackWorkers();
            });
        }
        
        function displayWorkers(workers) {
            const container = document.getElementById('workers-container');
            if (!container) return;
            
            if (workers.length === 0) {
                container.innerHTML = '<p class="text-muted col-12">No workers found matching your criteria.</p>';
                return;
            }
            
            container.innerHTML = workers.map(worker => {
                const skills = worker.skills ? worker.skills.split(',').slice(0, 3) : [];
                
                return `
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card h-100 shadow-sm border-0 worker-card modern-worker-card">
                            <div class="text-center p-3">
                                <img src="${worker.profile_image}" class="rounded-circle mb-3" alt="${worker.name}" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #f8f9fa;">
                                <h5 class="card-title mb-1 fw-bold">${worker.name}</h5>
                                <p class="text-muted mb-2">${worker.type || 'General Worker'}</p>
                                <p class="card-text text-muted small mb-3">${worker.description ? worker.description.substring(0, 80) + (worker.description.length > 80 ? '...' : '') : 'No description available'}</p>
                                <button class="btn btn-primary w-100" onclick="viewWorkerProfile(${worker.id})">View Profile</button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function displayFallbackWorkers() {
            const container = document.getElementById('workers-container');
            if (!container) return;
            
            // Show no workers available message with enhanced design
            container.innerHTML = `
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <div style="width: 100px; height: 100px; margin: 0 auto; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-users fa-3x text-muted"></i>
                                </div>
                            </div>
                            <h3 class="text-muted mb-3">No Workers Available Yet</h3>
                            <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">
                                Be the first to register as a worker and start connecting with employers looking for household services!
                            </p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="register.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Register as Worker
                                </a>
                                <a href="post-job.php" class="btn btn-outline-primary">
                                    <i class="fas fa-bullhorn me-2"></i>Post a Job
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function displayPagination(pagination) {
            const paginationContainer = document.getElementById('workers-pagination');
            if (!paginationContainer) return;
            
            if (pagination.total_pages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }
            
            let paginationHTML = '<ul class="pagination pagination-sm">';
            
            // Previous button
            if (pagination.current_page > 1) {
                paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadWorkers(${pagination.current_page - 1}); return false;">Previous</a></li>`;
            }
            
            // Page numbers
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    paginationHTML += `<li class="page-item active"><a class="page-link" href="#">${i}</a></li>`;
                } else {
                    paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadWorkers(${i}); return false;">${i}</a></li>`;
                }
            }
            
            // Next button
            if (pagination.current_page < pagination.total_pages) {
                paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadWorkers(${pagination.current_page + 1}); return false;">Next</a></li>`;
            }
            
            paginationHTML += '</ul>';
            paginationContainer.innerHTML = paginationHTML;
        }
        
        function contactWorker(workerId) {
            // Implement contact worker functionality
            alert('Contact functionality will be implemented soon!');
        }
        
        // Worker Profile Modal Functions
        let currentWorkerId = null;
        
        function viewWorkerProfile(workerId) {
            currentWorkerId = workerId;
            
            // Show loading state in modal
            const modalContent = document.getElementById('worker-profile-content');
            modalContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5>Loading worker profile...</h5>
                </div>
            `;
            
            // Show the modal
            const workerProfileModal = new bootstrap.Modal(document.getElementById('workerProfileModal'));
            workerProfileModal.show();
            
            // Fetch worker data
            fetch(`./api/worker-details.php?id=${workerId}`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    displayWorkerInModal(result.data);
                } else {
                    throw new Error(result.message || 'Failed to load worker profile');
                }
            })
            .catch(error => {
                console.error('Error loading worker profile:', error);
                modalContent.innerHTML = `
                    <div class="alert alert-danger m-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load worker profile. Please try again.
                    </div>
                `;
            });
        }
        
        function displayWorkerInModal(worker) {
            const modalContent = document.getElementById('worker-profile-content');
            
            // Generate initials for avatar if no profile image
            const initials = worker.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
            const profileImage = worker.profile_image || '';
            
            modalContent.innerHTML = `
                <div class="worker-profile-modal">
                    <!-- Modern Header Section -->
                    <div class="profile-header" style="background: linear-gradient(135deg, #000000 0%, #333333 100%); position: relative;">
                        <div class="container-fluid">
                            <div class="row align-items-center py-4">
                                <div class="col-md-4 text-center">
                                    <div class="profile-avatar-container position-relative">
                                        <img src="${profileImage}" class="profile-avatar" alt="${worker.name}" 
                                             style="width: 150px; height: 150px; object-fit: cover; border: 4px solid rgba(255,255,255,0.9); border-radius: 50%; box-shadow: 0 8px 24px rgba(0,0,0,0.3); display: block; margin: 0 auto;"
                                             onerror="this.style.display='none'; this.parentElement.querySelector('.profile-avatar-fallback').style.display='flex';">
                                        <div class="profile-avatar-fallback rounded-circle d-flex align-items-center justify-content-center position-absolute top-0 start-50 translate-middle-x" 
                                             style="width: 150px; height: 150px; background: rgba(255,255,255,0.2); display: none; border: 4px solid rgba(255,255,255,0.9); box-shadow: 0 8px 24px rgba(0,0,0,0.3);">
                                            <span style="color: white; font-size: 2.5rem; font-weight: bold;">${initials}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="profile-info text-white">
                                        <h2 class="mb-3 fw-bold">${worker.name || 'Name not available'}</h2>
                                        <div class="profile-rating mb-3">
                                            <div class="stars mb-2">
                                                ${getRatingStars(worker.avg_rating || 0)}
                                            </div>
                                            <small class="opacity-75">(${worker.review_count || 0} reviews)</small>
                                        </div>
                                        <p class="profile-description mb-3 opacity-90">${worker.description || 'No description available'}</p>
                                        <div class="profile-badges">
                                            <span class="badge bg-white text-dark me-2 mb-2 px-3 py-2">${worker.type ? worker.type.charAt(0).toUpperCase() + worker.type.slice(1) : 'General Worker'}</span>
                                            <span class="badge bg-white text-dark me-2 mb-2 px-3 py-2">
                                                <i class="fas fa-map-marker-alt me-1"></i>${worker.location || 'Location not specified'}
                                            </span>
                                            <span class="badge bg-white text-dark me-2 mb-2 px-3 py-2">
                                                <i class="fas fa-clock me-1"></i>${worker.experience_years ? worker.experience_years + '+ years' : 'Experience not specified'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="rate-card bg-white rounded-3 shadow-lg p-4 text-center">
                                        <div class="rate-icon mb-2">
                                            <i class="fas fa-money-bill-wave fa-2x text-dark"></i>
                                        </div>
                                        <h6 class="text-muted mb-2">Hourly Rate</h6>
                                        <h3 class="text-dark fw-bold mb-1">${worker.formatted_rate || 'RWF 0'}</h3>
                                        <small class="text-muted">per hour</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modern Content Section -->
                    <div class="profile-content bg-white">
                        <div class="container-fluid py-4">
                            <div class="row g-4">
                                <div class="col-lg-8">
                                    <!-- Services Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4 mb-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-tools text-dark me-2"></i>Services Offered
                                            </h5>
                                        </div>
                                        <div class="services-grid">
                                            ${getWorkerServices(worker.type)}
                                        </div>
                                    </div>
                                    
                                    <!-- Availability Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4 mb-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-calendar-check text-dark me-2"></i>Availability
                                            </h5>
                                        </div>
                                        <div class="availability-info text-center">
                                            <div class="availability-status mb-3">
                                                <div class="status-indicator bg-dark rounded-circle d-inline-block me-2" style="width: 12px; height: 12px;"></div>
                                                <span class="fw-semibold text-dark">Available for work</span>
                                            </div>
                                            <div class="availability-badges d-flex justify-content-center gap-2 flex-wrap">
                                                <span class="badge bg-dark text-white px-3 py-2">Mon-Fri</span>
                                                <span class="badge bg-secondary text-white px-3 py-2">Weekends</span>
                                                <span class="badge bg-dark text-white px-3 py-2">Flexible</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Reviews Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-star text-dark me-2"></i>Reviews
                                            </h5>
                                        </div>
                                        ${getWorkerReviews(worker.reviews)}
                                    </div>
                                </div>
                                
                                <div class="col-lg-4">
                                    <!-- Contact Info Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4 mb-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-phone text-dark me-2"></i>Contact Information
                                            </h5>
                                        </div>
                                        <div class="contact-details">
                                            <div class="contact-item mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-phone text-dark me-3" style="width: 20px;"></i>
                                                    <div>
                                                        <small class="text-muted d-block">Phone</small>
                                                        <span class="fw-semibold text-dark">${worker.phone || 'Not provided'}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="contact-item">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-envelope text-dark me-3" style="width: 20px;"></i>
                                                    <div>
                                                        <small class="text-muted d-block">Email</small>
                                                        <span class="fw-semibold text-dark">${worker.email || 'Not provided'}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Skills Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-check-circle text-dark me-2"></i>Skills
                                            </h5>
                                        </div>
                                        ${getWorkerSkills(worker.skills)}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <style>
                    .profile-avatar-container {
                        position: relative;
                        display: inline-block;
                        width: 150px;
                        height: 150px;
                    }
                    
                    .profile-avatar {
                        transition: transform 0.3s ease;
                        display: block;
                    }
                    
                    .profile-avatar-fallback {
                        transition: transform 0.3s ease;
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                    }
                    
                    .profile-avatar:hover, .profile-avatar-fallback:hover {
                        transform: scale(1.05);
                    }
                    
                    .stars {
                        font-size: 1.2rem;
                    }
                    
                    .profile-badges .badge {
                        font-weight: 500;
                        border-radius: 20px;
                    }
                    
                    .content-card {
                        border: 1px solid #e9ecef;
                        transition: transform 0.2s ease, box-shadow 0.2s ease;
                    }
                    
                    .content-card:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
                    }
                    
                    .card-header-modern h5 {
                        color: #000000;
                    }
                    
                    .services-grid .row .col-md-6 {
                        margin-bottom: 0.5rem;
                    }
                    
                    .services-grid .d-flex {
                        padding: 0.5rem;
                        border-radius: 8px;
                        transition: background-color 0.2s ease;
                    }
                    
                    .services-grid .d-flex:hover {
                        background-color: #f8f9fa;
                    }
                    
                    .rate-card {
                        border: none;
                        transition: transform 0.2s ease;
                    }
                    
                    .rate-card:hover {
                        transform: translateY(-2px);
                    }
                    
                    .availability-badges .badge {
                        font-weight: 500;
                        border-radius: 20px;
                    }
                    
                    .contact-item {
                        padding: 0.75rem;
                        border-radius: 8px;
                        transition: background-color 0.2s ease;
                    }
                    
                    .contact-item:hover {
                        background-color: #f8f9fa;
                    }
                </style>
            `;
            
            // Setup modal action buttons
            setupModalActions(worker);
        }
        
        function getRatingStars(rating) {
            const fullStars = Math.floor(rating);
            const halfStar = rating % 1 >= 0.5 ? 1 : 0;
            const emptyStars = 5 - fullStars - halfStar;
            
            let stars = '';
            for (let i = 0; i < fullStars; i++) {
                stars += '<i class="fas fa-star text-warning"></i>';
            }
            if (halfStar) {
                stars += '<i class="fas fa-star-half-alt text-warning"></i>';
            }
            for (let i = 0; i < emptyStars; i++) {
                stars += '<i class="far fa-star text-warning"></i>';
            }
            
            return stars;
        }
        
        function getWorkerServices(type) {
            const services = {
                'cleaning': ['House Cleaning', 'Deep Cleaning', 'Window Cleaning', 'Laundry', 'Organizing'],
                'cooking': ['Meal Preparation', 'Special Dietary Cooking', 'Event Catering', 'Meal Planning'],
                'childcare': ['Child Supervision', 'Homework Help', 'Activity Planning', 'Light Housekeeping'],
                'eldercare': ['Companionship', 'Medication Reminders', 'Meal Assistance', 'Light Housekeeping'],
                'gardening': ['Lawn Maintenance', 'Plant Care', 'Landscape Design', 'Weed Control'],
                'other': ['General Household Support']
            };
            
            const workerServices = services[type] || services['other'];
            
            return `
                <div class="row g-3">
                    ${workerServices.map(service => `
                        <div class="col-md-6">
                            <div class="service-item d-flex align-items-center p-3 bg-white border rounded-2">
                                <div class="service-icon me-3">
                                    <i class="fas fa-check-circle text-dark"></i>
                                </div>
                                <span class="fw-medium text-dark">${service}</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        function getWorkerSkills(skills) {
            if (!skills || skills.trim() === '') {
                return '<p class="text-muted opacity-75">No specific skills listed</p>';
            }
            
            const skillsArray = skills.split(',').map(skill => skill.trim()).filter(skill => skill);
            
            return `
                <div class="skills-container d-flex flex-wrap gap-2">
                    ${skillsArray.map(skill => `
                        <span class="skill-badge bg-dark text-white px-3 py-2 rounded-pill fw-medium">
                            <i class="fas fa-check-circle me-1"></i>${skill}
                        </span>
                    `).join('')}
                </div>
            `;
        }
        
        function getWorkerReviews(reviews) {
            if (!reviews || reviews.length === 0) {
                return `
                    <div class="text-center py-4">
                        <div class="no-reviews-icon mb-3">
                            <i class="fas fa-star fa-3x text-muted opacity-50"></i>
                        </div>
                        <h6 class="text-muted mb-2">No reviews yet</h6>
                        <p class="text-muted opacity-75 small mb-0">Be the first to review this worker!</p>
                    </div>
                `;
            }
            
            return reviews.slice(0, 3).map(review => `
                <div class="review-item bg-white border rounded-3 p-3 mb-3">
                    <div class="review-header d-flex justify-content-between align-items-start mb-2">
                        <div class="reviewer-info">
                            <h6 class="reviewer-name mb-1 fw-semibold text-dark">${review.reviewer_name || 'Anonymous'}</h6>
                            <div class="review-rating text-warning mb-1">
                                ${getRatingStars(review.rating)}
                            </div>
                        </div>
                        <div class="review-date">
                            <small class="text-muted">${new Date(review.created_at).toLocaleDateString('en-US', { 
                                year: 'numeric', 
                                month: 'short', 
                                day: 'numeric' 
                            })}</small>
                        </div>
                    </div>
                    <div class="review-comment">
                        <p class="mb-0 text-secondary">${review.comment || 'No comment provided'}</p>
                    </div>
                </div>
            `).join('');
        }
        
        function setupModalActions(worker) {
            const contactBtn = document.getElementById('modal-contact-btn');
            const bookBtn = document.getElementById('modal-book-btn');
            
            // Remove existing event listeners
            const newContactBtn = contactBtn.cloneNode(true);
            const newBookBtn = bookBtn.cloneNode(true);
            contactBtn.parentNode.replaceChild(newContactBtn, contactBtn);
            bookBtn.parentNode.replaceChild(newBookBtn, bookBtn);
            
            // Add new event listeners
            newContactBtn.addEventListener('click', () => {
                // Close profile modal and open contact modal
                bootstrap.Modal.getInstance(document.getElementById('workerProfileModal')).hide();
                setTimeout(() => {
                    const contactModal = new bootstrap.Modal(document.getElementById('contactModal'));
                    contactModal.show();
                }, 300);
            });
            
            newBookBtn.addEventListener('click', () => {
                // Close profile modal and open booking modal
                bootstrap.Modal.getInstance(document.getElementById('workerProfileModal')).hide();
                setTimeout(() => {
                    const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
                    bookingModal.show();
                }, 300);
            });
        }
        
        // Setup search and filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Worker search
            const searchInput = document.getElementById('worker-search');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function(e) {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        const filters = {...currentFilters};
                        if (e.target.value.trim()) {
                            filters.search = e.target.value.trim();
                        } else {
                            delete filters.search;
                        }
                        loadWorkers(1, filters);
                    }, 500);
                });
            }
            
            // Worker type filter
            const typeFilter = document.getElementById('worker-type-filter');
            if (typeFilter) {
                typeFilter.addEventListener('change', function(e) {
                    const filters = {...currentFilters};
                    if (e.target.value) {
                        filters.type = e.target.value;
                    } else {
                        delete filters.type;
                    }
                    loadWorkers(1, filters);
                });
            }
        });
    </script>
</body>
</html>
