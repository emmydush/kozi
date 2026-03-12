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
    <title>Earnings - Household Connect</title>
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
        
        .earnings-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .earnings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .payment-status {
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
            <a class="nav-link" href="my-applications.php">
                <i class="fas fa-file-alt"></i> My Applications
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> Active Jobs
            </a>
            <a class="nav-link active" href="earnings.php">
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
                <h2>Earnings</h2>
                <p class="text-muted">Track your income and payment history</p>
            </div>
        </div>

        <!-- Earnings Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card earnings-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">This Month</h5>
                        <h2>RWF 280,000</h2>
                        <small><i class="fas fa-arrow-up"></i> 15% from last month</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card earnings-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Earnings</h5>
                        <h2>RWF 1,850,000</h2>
                        <small>All time earnings</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card earnings-card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pending</h5>
                        <h2>RWF 75,000</h2>
                        <small>Awaiting payment</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card earnings-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Avg. Monthly</h5>
                        <h2>RWF 246,667</h2>
                        <small>Last 6 months</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart and Recent Transactions -->
        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Earnings Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="earningsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Stats</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Jobs Completed</span>
                                <strong>28</strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Active Jobs</span>
                                <strong>3</strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Best Month</span>
                                <strong>RWF 320,000</strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Total Hours</span>
                                <strong>624</strong>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm w-100">Download Report</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Payment History</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary active">All</button>
                            <button class="btn btn-outline-primary">Paid</button>
                            <button class="btn btn-outline-primary">Pending</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Employer</th>
                                        <th>Job Type</th>
                                        <th>Hours</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Dec 1, 2024</td>
                                        <td>John Mukiza</td>
                                        <td>House Cleaning</td>
                                        <td>40</td>
                                        <td><strong>RWF 50,000</strong></td>
                                        <td><span class="badge bg-success payment-status">Paid</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Dec 1, 2024</td>
                                        <td>Grace Kantengwa</td>
                                        <td>Gardening</td>
                                        <td>8</td>
                                        <td><strong>RWF 20,000</strong></td>
                                        <td><span class="badge bg-success payment-status">Paid</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Nov 28, 2024</td>
                                        <td>Marie Uwimana</td>
                                        <td>Childcare</td>
                                        <td>20</td>
                                        <td><strong>RWF 35,000</strong></td>
                                        <td><span class="badge bg-warning payment-status">Pending</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Nov 25, 2024</td>
                                        <td>Joseph Niyonzima</td>
                                        <td>Eldercare</td>
                                        <td>35</td>
                                        <td><strong>RWF 45,000</strong></td>
                                        <td><span class="badge bg-success payment-status">Paid</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Nov 20, 2024</td>
                                        <td>John Mukiza</td>
                                        <td>House Cleaning</td>
                                        <td>40</td>
                                        <td><strong>RWF 50,000</strong></td>
                                        <td><span class="badge bg-success payment-status">Paid</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Nov 15, 2024</td>
                                        <td>Grace Kantengwa</td>
                                        <td>Gardening</td>
                                        <td>8</td>
                                        <td><strong>RWF 20,000</strong></td>
                                        <td><span class="badge bg-warning payment-status">Pending</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">View</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        // Earnings Chart
        const ctx = document.getElementById('earningsChart').getContext('2d');
        const earningsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['July', 'August', 'September', 'October', 'November', 'December'],
                datasets: [{
                    label: 'Monthly Earnings',
                    data: [220000, 245000, 280000, 265000, 290000, 280000],
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'RWF ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
