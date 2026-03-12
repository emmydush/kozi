<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Only workers should access this page
if ($user_role !== 'worker') {
    redirect('dashboard.php');
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
        
        .application-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .status-badge {
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
                <h2>My Applications</h2>
                <p class="text-muted">Track your job applications and their status</p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Applications</h5>
                        <h2>12</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pending</h5>
                        <h2>3</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Under Review</h5>
                        <h2>5</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Accepted</h5>
                        <h2>4</h2>
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
                    <div class="col-md-6 mb-4">
                        <div class="card application-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">House Cleaner Needed</h5>
                                    <span class="badge bg-success status-badge">Accepted</span>
                                </div>
                                <p class="card-text">Applied for a full-time house cleaning position in Kigali.</p>
                                <div class="mb-2">
                                    <small class="text-muted">Employer: John Mukiza</small><br>
                                    <small class="text-muted">Applied: 2 days ago</small><br>
                                    <small class="text-muted">Salary: RWF 50,000/month</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-secondary">Message Employer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card application-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">Childcare Provider</h5>
                                    <span class="badge bg-warning status-badge">Pending</span>
                                </div>
                                <p class="card-text">Applied for part-time childcare position for 2 children.</p>
                                <div class="mb-2">
                                    <small class="text-muted">Employer: Marie Uwimana</small><br>
                                    <small class="text-muted">Applied: 5 days ago</small><br>
                                    <small class="text-muted">Salary: RWF 35,000/month</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-secondary">Message Employer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card application-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">Weekend Gardener</h5>
                                    <span class="badge bg-info status-badge">Under Review</span>
                                </div>
                                <p class="card-text">Applied for weekend gardening and landscaping work.</p>
                                <div class="mb-2">
                                    <small class="text-muted">Employer: Grace Kantengwa</small><br>
                                    <small class="text-muted">Applied: 1 week ago</small><br>
                                    <small class="text-muted">Salary: RWF 20,000/weekend</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-secondary">Message Employer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card application-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">Elderly Care Assistant</h5>
                                    <span class="badge bg-danger status-badge">Rejected</span>
                                </div>
                                <p class="card-text">Application for elderly care position was not successful.</p>
                                <div class="mb-2">
                                    <small class="text-muted">Employer: Joseph Niyonzima</small><br>
                                    <small class="text-muted">Applied: 2 weeks ago</small><br>
                                    <small class="text-muted">Salary: RWF 80,000/month</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-secondary">Withdraw Application</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Applications -->
            <div class="tab-pane fade" id="pending" role="tabpanel">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card application-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">Childcare Provider</h5>
                                    <span class="badge bg-warning status-badge">Pending</span>
                                </div>
                                <p class="card-text">Applied for part-time childcare position for 2 children.</p>
                                <div class="mb-2">
                                    <small class="text-muted">Employer: Marie Uwimana</small><br>
                                    <small class="text-muted">Applied: 5 days ago</small><br>
                                    <small class="text-muted">Salary: RWF 35,000/month</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-secondary">Message Employer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Under Review Applications -->
            <div class="tab-pane fade" id="review" role="tabpanel">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card application-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">Weekend Gardener</h5>
                                    <span class="badge bg-info status-badge">Under Review</span>
                                </div>
                                <p class="card-text">Applied for weekend gardening and landscaping work.</p>
                                <div class="mb-2">
                                    <small class="text-muted">Employer: Grace Kantengwa</small><br>
                                    <small class="text-muted">Applied: 1 week ago</small><br>
                                    <small class="text-muted">Salary: RWF 20,000/weekend</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-secondary">Message Employer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Accepted Applications -->
            <div class="tab-pane fade" id="accepted" role="tabpanel">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card application-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">House Cleaner Needed</h5>
                                    <span class="badge bg-success status-badge">Accepted</span>
                                </div>
                                <p class="card-text">Applied for a full-time house cleaning position in Kigali.</p>
                                <div class="mb-2">
                                    <small class="text-muted">Employer: John Mukiza</small><br>
                                    <small class="text-muted">Applied: 2 days ago</small><br>
                                    <small class="text-muted">Salary: RWF 50,000/month</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-secondary">Message Employer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rejected Applications -->
            <div class="tab-pane fade" id="rejected" role="tabpanel">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card application-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">Elderly Care Assistant</h5>
                                    <span class="badge bg-danger status-badge">Rejected</span>
                                </div>
                                <p class="card-text">Application for elderly care position was not successful.</p>
                                <div class="mb-2">
                                    <small class="text-muted">Employer: Joseph Niyonzima</small><br>
                                    <small class="text-muted">Applied: 2 weeks ago</small><br>
                                    <small class="text-muted">Salary: RWF 80,000/month</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-primary">View Details</button>
                                    <button class="btn btn-sm btn-outline-secondary">Withdraw Application</button>
                                </div>
                            </div>
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
    </script>
</body>
</html>
