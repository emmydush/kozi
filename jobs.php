<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch jobs from database
$jobs = [];
$sql = "SELECT j.*, u.name as employer_name, u.email as employer_email 
        FROM jobs j 
        JOIN users u ON j.employer_id = u.id 
        WHERE j.status = 'active' 
        ORDER BY j.created_at DESC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
}

// Get job statistics for filters
$total_jobs = count($jobs);
$job_types = [];
$locations = [];

foreach ($jobs as $job) {
    $job_types[] = $job['job_type'];
    $locations[] = $job['location'];
}
$unique_job_types = array_unique($job_types);
$unique_locations = array_unique($locations);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Jobs - Household Connect</title>
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
        
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #000000;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
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
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
        }
        
        .job-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            font-size: 0.8rem;
        }
        
        .job-card {
            transition: transform 0.2s;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background: white;
        }
        
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            border-color: #000000;
        }
        
        .job-badge {
            font-size: 0.8rem;
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
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> My Jobs
            </a>
            <a class="nav-link" href="bookings.php">
                <i class="fas fa-calendar-check"></i> Bookings
            </a>
            <?php else: ?>
            <a class="nav-link active" href="jobs.php">
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
                <h2>Find Jobs</h2>
                <p class="text-muted">Discover available job opportunities in your area</p>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for jobs, keywords, or companies...">
                    <button class="btn btn-dark" type="button">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle w-100" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-sort me-2"></i>Sort By
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-clock me-2"></i>Latest Posted</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-money-bill-wave me-2"></i>Salary: High to Low</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-map-marker-alt me-2"></i>Nearest First</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Filter Pills -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex flex-wrap gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-dark dropdown-toggle" type="button" id="jobTypeDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-briefcase me-2"></i>Job Type
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="selectJobType('all')">All Types</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectJobType('cleaning')">Cleaning</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectJobType('cooking')">Cooking</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectJobType('childcare')">Childcare</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectJobType('eldercare')">Eldercare</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectJobType('gardening')">Gardening</a></li>
                        </ul>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-dark dropdown-toggle" type="button" id="locationDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-map-marker-alt me-2"></i>Location
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="selectLocation('all')">All Locations</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectLocation('kigali')">Kigali</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectLocation('nyabugogo')">Nyabugogo</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectLocation('kicukiro')">Kicukiro</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectLocation('gasabo')">Gasabo</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectLocation('nyarugenge')">Nyarugenge</a></li>
                        </ul>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-dark dropdown-toggle" type="button" id="salaryDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-money-bill-wave me-2"></i>Salary Range
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="selectSalary('all')">All Salaries</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectSalary('0-50000')">Below RWF 50,000</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectSalary('50000-100000')">RWF 50,000 - 100,000</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectSalary('100000-150000')">RWF 100,000 - 150,000</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectSalary('150000+')">Above RWF 150,000</a></li>
                        </ul>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-dark dropdown-toggle" type="button" id="hoursDropdown" data-bs-toggle="dropdown">
                            <i class="fas fa-clock me-2"></i>Work Hours
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="selectHours('all')">All Hours</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectHours('full-time')">Full-time</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectHours('part-time')">Part-time</a></li>
                            <li><a class="dropdown-item" href="#" onclick="selectHours('weekend')">Weekend Only</a></li>
                        </ul>
                    </div>
                    
                    <button class="btn btn-outline-secondary" onclick="clearFilters()">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </button>
                </div>
                
                <!-- Active Filters Display -->
                <div class="mt-3" id="active-filters">
                    <small class="text-muted">No filters selected</small>
                </div>
            </div>
        </div>

        <!-- Job Listings -->
        <div class="row" id="jobs-container">
            <?php if (empty($jobs)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        No active jobs found at the moment. Please check back later.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
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
                                        $icon = isset($icons[$job['job_type']]) ? $icons[$job['job_type']] : 'fa-briefcase';
                                        ?>
                                        <i class="fas <?php echo $icon; ?> me-2"></i><?php echo htmlspecialchars($job['title']); ?>
                                    </h5>
                                    <span class="badge bg-dark job-badge"><?php echo htmlspecialchars($job['work_hours']); ?></span>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars(substr($job['description'], 0, 150)) . '...'; ?></p>
                                <div class="mb-2">
                                    <span class="badge bg-dark">
                                        <i class="fas fa-briefcase me-1"></i><?php echo ucfirst(htmlspecialchars($job['job_type'])); ?>
                                    </span>
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($job['location']); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-dark">
                                        <i class="fas fa-money-bill-wave me-1"></i><?php echo format_currency($job['salary']); ?>
                                        <?php echo strpos($job['work_hours'], 'weekend') !== false ? '/weekend' : '/month'; ?>
                                    </strong>
                                    <button class="btn btn-dark btn-sm" onclick="applyForJob(<?php echo $job['id']; ?>)">
                                        <i class="fas fa-paper-plane me-1"></i>Apply Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <script>
        // Filter state
        let currentFilters = {
            jobType: 'all',
            location: 'all',
            salary: 'all',
            hours: 'all'
        };

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
        
        // Filter selection functions
        function selectJobType(type) {
            currentFilters.jobType = type;
            updateButtonText('jobTypeDropdown', getJobTypeText(type));
            updateActiveFilters();
        }
        
        function selectLocation(location) {
            currentFilters.location = location;
            updateButtonText('locationDropdown', getLocationText(location));
            updateActiveFilters();
        }
        
        function selectSalary(salary) {
            currentFilters.salary = salary;
            updateButtonText('salaryDropdown', getSalaryText(salary));
            updateActiveFilters();
        }
        
        function selectHours(hours) {
            currentFilters.hours = hours;
            updateButtonText('hoursDropdown', getHoursText(hours));
            updateActiveFilters();
        }
        
        // Helper functions to get display text
        function getJobTypeText(type) {
            const types = {
                'all': 'Job Type',
                'cleaning': 'Cleaning',
                'cooking': 'Cooking',
                'childcare': 'Childcare',
                'eldercare': 'Eldercare',
                'gardening': 'Gardening'
            };
            return types[type] || 'Job Type';
        }
        
        function getLocationText(location) {
            const locations = {
                'all': 'Location',
                'kigali': 'Kigali',
                'nyabugogo': 'Nyabugogo',
                'kicukiro': 'Kicukiro',
                'gasabo': 'Gasabo',
                'nyarugenge': 'Nyarugenge'
            };
            return locations[location] || 'Location';
        }
        
        function getSalaryText(salary) {
            const salaries = {
                'all': 'Salary Range',
                '0-50000': 'Below RWF 50,000',
                '50000-100000': 'RWF 50,000 - 100,000',
                '100000-150000': 'RWF 100,000 - 150,000',
                '150000+': 'Above RWF 150,000'
            };
            return salaries[salary] || 'Salary Range';
        }
        
        function getHoursText(hours) {
            const hoursMap = {
                'all': 'Work Hours',
                'full-time': 'Full-time',
                'part-time': 'Part-time',
                'weekend': 'Weekend Only'
            };
            return hoursMap[hours] || 'Work Hours';
        }
        
        // Update dropdown button text
        function updateButtonText(dropdownId, text) {
            const button = document.getElementById(dropdownId);
            if (button) {
                // Keep the icon and update the text
                const icon = button.querySelector('i').outerHTML;
                button.innerHTML = icon + ' ' + text + ' <span class="dropdown-toggle"></span>';
            }
        }
        
        // Update active filters display
        function updateActiveFilters() {
            const container = document.getElementById('active-filters');
            const filters = [];
            
            if (currentFilters.jobType !== 'all') {
                filters.push(`<span class="badge bg-dark me-2"><i class="fas fa-briefcase me-1"></i>${getJobTypeText(currentFilters.jobType)}</span>`);
            }
            
            if (currentFilters.location !== 'all') {
                filters.push(`<span class="badge bg-dark me-2"><i class="fas fa-map-marker-alt me-1"></i>${getLocationText(currentFilters.location)}</span>`);
            }
            
            if (currentFilters.salary !== 'all') {
                filters.push(`<span class="badge bg-dark me-2"><i class="fas fa-money-bill-wave me-1"></i>${getSalaryText(currentFilters.salary)}</span>`);
            }
            
            if (currentFilters.hours !== 'all') {
                filters.push(`<span class="badge bg-dark me-2"><i class="fas fa-clock me-1"></i>${getHoursText(currentFilters.hours)}</span>`);
            }
            
            container.innerHTML = filters.length > 0 ? filters.join('') : '<small class="text-muted">No filters selected</small>';
        }
        
        // Apply filters
        function applyFilters() {
            console.log('Applying filters:', currentFilters);
            
            // Show loading state
            const container = document.getElementById('jobs-container');
            container.innerHTML = '<div class="col-12"><p class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Filtering jobs...</p></div>';
            
            // Simulate API call
            setTimeout(() => {
                // For demo purposes, just reload the page
                // In a real application, you would make an API call here
                location.reload();
            }, 1000);
        }
        
        // Clear all filters
        function clearFilters() {
            currentFilters = {
                jobType: 'all',
                location: 'all',
                salary: 'all',
                hours: 'all'
            };
            
            // Reset button texts
            updateButtonText('jobTypeDropdown', 'Job Type');
            updateButtonText('locationDropdown', 'Location');
            updateButtonText('salaryDropdown', 'Salary Range');
            updateButtonText('hoursDropdown', 'Work Hours');
            
            updateActiveFilters();
        }
        
        // Apply for job function
        function applyForJob(jobId) {
            <?php if ($user_role === 'worker'): ?>
            if (confirm('Are you sure you want to apply for this job?')) {
                fetch('api/apply-job.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ job_id: jobId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Application submitted successfully!');
                        location.reload();
                    } else {
                        alert(data.message || 'Application failed. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Network error. Please try again.');
                });
            }
            <?php else: ?>
            alert('Only workers can apply for jobs. Please switch to worker account.');
            <?php endif; ?>
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateActiveFilters();
        });
    </script>
</body>
</html>
