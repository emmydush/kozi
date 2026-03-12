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
    <title>Find Jobs - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            color: #667eea;
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
        
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
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
        
        .job-badge {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-home"></i> Household Connect</h3>
        </div>
        
        <div class="user-profile">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
            <div class="user-role"><?php echo ucfirst(htmlspecialchars($user_role)); ?></div>
        </div>
        
        <nav class="nav flex-column p-3">
            <a class="nav-link" href="dashboard.php">
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
                <p class="text-muted">Browse available job opportunities</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Filter Jobs</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Job Type</label>
                                <select class="form-select" id="job-type-filter">
                                    <option value="">All Types</option>
                                    <option value="cleaning">Cleaning</option>
                                    <option value="cooking">Cooking</option>
                                    <option value="childcare">Childcare</option>
                                    <option value="eldercare">Eldercare</option>
                                    <option value="gardening">Gardening</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Location</label>
                                <select class="form-select" id="location-filter">
                                    <option value="">All Locations</option>
                                    <option value="kigali">Kigali</option>
                                    <option value="nyabugogo">Nyabugogo</option>
                                    <option value="kicukiro">Kicukiro</option>
                                    <option value="gasabo">Gasabo</option>
                                    <option value="nyarugenge">Nyarugenge</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Salary Range</label>
                                <select class="form-select" id="salary-filter">
                                    <option value="">Any Salary</option>
                                    <option value="0-50000">Below RWF 50,000</option>
                                    <option value="50000-100000">RWF 50,000 - 100,000</option>
                                    <option value="100000-150000">RWF 100,000 - 150,000</option>
                                    <option value="150000+">Above RWF 150,000</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Work Hours</label>
                                <select class="form-select" id="hours-filter">
                                    <option value="">Any Hours</option>
                                    <option value="full-time">Full-time</option>
                                    <option value="part-time">Part-time</option>
                                    <option value="weekend">Weekend Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Job Listings -->
        <div class="row" id="jobs-container">
            <div class="col-md-6 mb-4">
                <div class="card job-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title">House Cleaner Needed</h5>
                            <span class="badge bg-success job-badge">Full-time</span>
                        </div>
                        <p class="card-text">Looking for an experienced house cleaner for a family home in Kigali. Responsibilities include cleaning, laundry, and occasional cooking.</p>
                        <div class="mb-2">
                            <span class="badge bg-primary">Cleaning</span>
                            <span class="badge bg-info">Kigali</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-success">RWF 50,000/month</strong>
                            <button class="btn btn-primary btn-sm">Apply Now</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card job-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title">Childcare Provider</h5>
                            <span class="badge bg-warning job-badge">Part-time</span>
                        </div>
                        <p class="card-text">Need a reliable childcare provider for 2 children (ages 3 and 5). Must have experience with toddlers and be patient.</p>
                        <div class="mb-2">
                            <span class="badge bg-primary">Childcare</span>
                            <span class="badge bg-info">Kicukiro</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-success">RWF 35,000/month</strong>
                            <button class="btn btn-primary btn-sm">Apply Now</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card job-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title">Weekend Gardener</h5>
                            <span class="badge bg-info job-badge">Weekend</span>
                        </div>
                        <p class="card-text">Looking for someone to maintain garden and lawn on weekends. Knowledge of plants and basic landscaping required.</p>
                        <div class="mb-2">
                            <span class="badge bg-primary">Gardening</span>
                            <span class="badge bg-info">Gasabo</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-success">RWF 20,000/weekend</strong>
                            <button class="btn btn-primary btn-sm">Apply Now</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card job-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title">Elderly Care Assistant</h5>
                            <span class="badge bg-success job-badge">Full-time</span>
                        </div>
                        <p class="card-text">Seeking a compassionate caregiver for an elderly person. Duties include companionship, medication reminders, and light housekeeping.</p>
                        <div class="mb-2">
                            <span class="badge bg-primary">Eldercare</span>
                            <span class="badge bg-info">Nyarugenge</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-success">RWF 80,000/month</strong>
                            <button class="btn btn-primary btn-sm">Apply Now</button>
                        </div>
                    </div>
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
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
        
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filters = ['job-type-filter', 'location-filter', 'salary-filter', 'hours-filter'];
            
            filters.forEach(filterId => {
                document.getElementById(filterId).addEventListener('change', filterJobs);
            });
        });
        
        function filterJobs() {
            // This would typically make an API call to filter jobs
            console.log('Filtering jobs...');
            // For demo purposes, we'll just show a message
            const container = document.getElementById('jobs-container');
            container.innerHTML = '<div class="col-12"><p class="text-center">Loading filtered jobs...</p></div>';
            
            setTimeout(() => {
                location.reload(); // Reload to show all jobs for demo
            }, 1000);
        }
    </script>
</body>
</html>
