<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
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
            <a class="nav-link" href="#post-job">
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

        <?php if ($user_role === 'employer'): ?>
        <!-- Employer Dashboard -->
        <div class="row mt-4">
            <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Posted Jobs</h5>
                        <h2 id="posted-jobs-count">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Active Bookings</h5>
                        <h2 id="active-bookings-count">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total Spent</h5>
                        <h2 id="total-spent">RWF 0</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 mb-4">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Workers Hired</h5>
                        <h2 id="workers-hired-count">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-8 col-md-12 mb-4">
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
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="#post-job" class="btn btn-primary">Post New Job</a>
                            <a href="workers.php" class="btn btn-outline-primary">Find Workers</a>
                            <a href="messages.php" class="btn btn-outline-secondary">Messages</a>
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
            
            // Show loading state
            const loadingElements = ['posted-jobs-count', 'active-bookings-count', 'total-spent', 'workers-hired-count', 'jobs-applied-count', 'active-jobs-count'];
            loadingElements.forEach(id => {
                const element = document.getElementById(id);
                if (element) element.textContent = 'Loading...';
            });
            
            // Fetch real data from API
            fetch('api/dashboard-data-simple.php', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                console.log('API Response Status:', response.status);
                return response.json();
            })
            .then(result => {
                console.log('API Response:', result);
                if (result.success) {
                    if (userRole === 'employer') {
                        loadEmployerData(result.data);
                    } else {
                        loadWorkerData(result.data);
                    }
                } else {
                    console.error('API Error:', result.message);
                    showErrorMessage('Failed to load dashboard data: ' + result.message);
                    // Fallback to mock data
                    if (userRole === 'employer') {
                        loadEmployerData();
                    } else {
                        loadWorkerData();
                    }
                }
            })
            .catch(error => {
                console.error('Network Error:', error);
                showErrorMessage('Network error. Using demo data.');
                // Fallback to mock data
                if (userRole === 'employer') {
                    loadEmployerData();
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
            if (!data) {
                // Fallback mock data
                data = {
                    posted_jobs: { total: 5, active: 3, filled: 2 },
                    active_bookings: { active: 2 },
                    total_spent: { total: 150000 },
                    workers_hired: { workers: 3 },
                    recent_jobs: [
                        { title: 'House Cleaner Needed', applications: 12, salary: 50000, created_at: '2024-12-01' },
                        { title: 'Childcare Provider', applications: 8, salary: 35000, created_at: '2024-12-05' }
                    ]
                };
            }
            
            // Update statistics
            document.getElementById('posted-jobs-count').textContent = data.posted_jobs?.total || 0;
            document.getElementById('active-bookings-count').textContent = data.active_bookings?.active || 0;
            document.getElementById('total-spent').textContent = formatCurrency(data.total_spent?.total || 0);
            document.getElementById('workers-hired-count').textContent = data.workers_hired?.workers || 0;
            
            // Update recent jobs
            const recentJobsContainer = document.getElementById('recent-jobs');
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
        
        function loadWorkerData(data) {
            if (!data) {
                // Fallback mock data
                data = {
                    jobs_applied: { total: 12, pending: 3, under_review: 5, accepted: 4 },
                    active_jobs: { active: 2 },
                    available_jobs: [
                        { title: 'Part-time House Cleaner', salary: 40000, location: 'Kigali', employer_name: 'John Mukiza' },
                        { title: 'Weekend Childcare', salary: 25000, location: 'Kicukiro', employer_name: 'Marie Uwimana' }
                    ]
                };
            }
            
            // Update statistics
            document.getElementById('jobs-applied-count').textContent = data.jobs_applied?.total || 0;
            document.getElementById('active-jobs-count').textContent = data.active_jobs?.active || 0;
            
            // Update available jobs
            const availableJobsContainer = document.getElementById('available-jobs');
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
    </script>
</body>
</html>
