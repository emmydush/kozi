<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Only employers should access this page
if ($user_role !== 'employer') {
    redirect('dashboard.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
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
            margin-right: 10px;
            width: 20px;
        }
        
        .main-content {
            margin-left: 0;
            padding: 15px;
            min-height: calc(100vh - 60px);
            margin-top: 60px;
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
        
        .booking-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            font-size: 0.8rem;
        }
        
        .calendar-container {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Standard button styles to match other pages */
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
        
        .btn-outline-success {
            border: 2px solid #198754;
            color: #198754;
            background: white;
        }
        
        .btn-outline-success:hover {
            background: #198754;
            color: white;
        }
        
        .btn-outline-warning {
            border: 2px solid #ffc107;
            color: #ffc107;
            background: white;
        }
        
        .btn-outline-warning:hover {
            background: #ffc107;
            color: white;
        }
        
        .btn-sm {
            min-height: 38px;
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        /* Standard card styles */
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
        
        .card.bg-success {
            background: #000000 !important;
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: none;
        }
        
        .card.bg-primary {
            background: linear-gradient(135deg, #000000, #333333) !important;
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: none;
        }
        
        .card.bg-primary h2,
        .card.bg-success h2 {
            font-size: 2rem;
            margin-bottom: 0;
            font-weight: 700;
            color: white !important;
        }
        
        .card.bg-primary .card-title,
        .card.bg-success .card-title {
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 600;
            opacity: 0.9;
            color: white !important;
        }
        
        /* Mobile responsive improvements */
        @media (max-width: 768px) {
            .row > * {
                padding-left: 8px;
                padding-right: 8px;
            }
            
            .row {
                margin-left: -8px;
                margin-right: -8px;
            }
            
            .card.bg-primary h2,
            .card.bg-success h2 {
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
            .card.bg-success h2 {
                font-size: 1.3rem;
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
            <a class="nav-link" href="post-job.php">
                <i class="fas fa-plus-circle"></i> Post Job
            </a>
            <a class="nav-link" href="workers.php">
                <i class="fas fa-users"></i> Find Workers
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> My Jobs
            </a>
            <a class="nav-link active" href="bookings.php">
                <i class="fas fa-calendar-check"></i> Bookings
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
                <h2>Bookings</h2>
                <p class="text-muted">Manage your worker bookings and schedules</p>
            </div>
        </div>

        <!-- Booking Statistics -->
        <div class="row mb-4">
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Active Bookings</h5>
                        <h2>5</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">This Month</h5>
                        <h2>12</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Calendar -->
            <div class="col-md-4 mb-4">
                <div class="calendar-container">
                    <h5 class="mb-3">December 2024</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Sun</th>
                                    <th>Mon</th>
                                    <th>Tue</th>
                                    <th>Wed</th>
                                    <th>Thu</th>
                                    <th>Fri</th>
                                    <th>Sat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>2</td>
                                    <td>3</td>
                                    <td>4</td>
                                    <td>5</td>
                                    <td>6</td>
                                    <td>7</td>
                                </tr>
                                <tr>
                                    <td>8</td>
                                    <td class="bg-success text-white">9</td>
                                    <td>10</td>
                                    <td>11</td>
                                    <td class="bg-success text-white">12</td>
                                    <td>13</td>
                                    <td>14</td>
                                </tr>
                                <tr>
                                    <td>15</td>
                                    <td class="bg-success text-white">16</td>
                                    <td>17</td>
                                    <td>18</td>
                                    <td class="bg-success text-white">19</td>
                                    <td>20</td>
                                    <td>21</td>
                                </tr>
                                <tr>
                                    <td>22</td>
                                    <td class="bg-success text-white">23</td>
                                    <td>24</td>
                                    <td>25</td>
                                    <td class="bg-success text-white">26</td>
                                    <td>27</td>
                                    <td>28</td>
                                </tr>
                                <tr>
                                    <td>29</td>
                                    <td class="bg-success text-white">30</td>
                                    <td>31</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <span class="badge bg-success">Scheduled</span> - Worker booked
                        </small>
                    </div>
                </div>
            </div>

            <!-- Bookings List -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Upcoming Bookings</h5>
                        <button class="btn btn-primary btn-sm">New Booking</button>
                    </div>
                    <div class="card-body">
                        <div class="booking-card card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">House Cleaning - Marie Uwimana</h6>
                                        <div class="text-muted small">
                                            <i class="fas fa-calendar"></i> Dec 9, 2024 - 9:00 AM
                                            <i class="fas fa-clock ms-2"></i> 4 hours
                                        </div>
                                    </div>
                                    <span class="badge bg-success status-badge">Confirmed</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Kigali - Home Address</span>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary">View</button>
                                        <button class="btn btn-outline-secondary">Message</button>
                                        <button class="btn btn-outline-warning">Reschedule</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="booking-card card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">Childcare - John Mukiza</h6>
                                        <div class="text-muted small">
                                            <i class="fas fa-calendar"></i> Dec 12, 2024 - 2:00 PM
                                            <i class="fas fa-clock ms-2"></i> 3 hours
                                        </div>
                                    </div>
                                    <span class="badge bg-success status-badge">Confirmed</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Kicukiro - Family Home</span>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary">View</button>
                                        <button class="btn btn-outline-secondary">Message</button>
                                        <button class="btn btn-outline-warning">Reschedule</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="booking-card card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">Gardening - Grace Kantengwa</h6>
                                        <div class="text-muted small">
                                            <i class="fas fa-calendar"></i> Dec 16, 2024 - 8:00 AM
                                            <i class="fas fa-clock ms-2"></i> 5 hours
                                        </div>
                                    </div>
                                    <span class="badge bg-warning status-badge">Pending</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Gasabo - Garden Maintenance</span>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary">View</button>
                                        <button class="btn btn-outline-secondary">Message</button>
                                        <button class="btn btn-outline-success">Confirm</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="booking-card card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">Eldercare - Joseph Niyonzima</h6>
                                        <div class="text-muted small">
                                            <i class="fas fa-calendar"></i> Dec 19, 2024 - 10:00 AM
                                            <i class="fas fa-clock ms-2"></i> 6 hours
                                        </div>
                                    </div>
                                    <span class="badge bg-success status-badge">Confirmed</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Nyarugenge - Elder Care</span>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary">View</button>
                                        <button class="btn btn-outline-secondary">Message</button>
                                        <button class="btn btn-outline-warning">Reschedule</button>
                                    </div>
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
