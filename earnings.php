<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role and ID
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Only workers should access this page
if ($user_role !== 'worker') {
    redirect('dashboard.php');
}

// Calculate real earnings data
$current_month = date('Y-m');
$last_month = date('Y-m', strtotime('-1 month'));

// This month's earnings (from accepted applications)
$this_month_sql = "SELECT COALESCE(SUM(j.salary), 0) as total 
                   FROM job_applications ja 
                   JOIN jobs j ON ja.job_id = j.id 
                   WHERE ja.worker_id = ? AND ja.status = 'accepted' 
                   AND TO_CHAR(ja.updated_at, 'YYYY-MM') = ?";
$stmt = $conn->prepare($this_month_sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->bindParam(2, $current_month, PDO::PARAM_STR);
$stmt->execute();
$this_month_result = $stmt->fetch(PDO::FETCH_ASSOC);
$this_month_earnings = $this_month_result['total'];

// Last month's earnings (for comparison)
$last_month_sql = "SELECT COALESCE(SUM(j.salary), 0) as total 
                   FROM job_applications ja 
                   JOIN jobs j ON ja.job_id = j.id 
                   WHERE ja.worker_id = ? AND ja.status = 'accepted' 
                   AND TO_CHAR(ja.updated_at, 'YYYY-MM') = ?";
$stmt = $conn->prepare($last_month_sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->bindParam(2, $last_month, PDO::PARAM_STR);
$stmt->execute();
$last_month_result = $stmt->fetch(PDO::FETCH_ASSOC);
$last_month_earnings = $last_month_result['total'];

// Total earnings all time
$total_earnings_sql = "SELECT COALESCE(SUM(j.salary), 0) as total 
                       FROM job_applications ja 
                       JOIN jobs j ON ja.job_id = j.id 
                       WHERE ja.worker_id = ? AND ja.status = 'accepted'";
$stmt = $conn->prepare($total_earnings_sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->execute();
$total_result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_earnings = $total_result['total'];

// Pending earnings (applications accepted but not paid)
$pending_earnings_sql = "SELECT COALESCE(SUM(j.salary), 0) as total 
                         FROM job_applications ja 
                         JOIN jobs j ON ja.job_id = j.id 
                         WHERE ja.worker_id = ? AND ja.status = 'accepted' 
                         AND ja.id NOT IN (
                             SELECT DISTINCT job_id FROM earnings WHERE worker_id = ? AND payment_status = 'paid'
                         )";
$stmt = $conn->prepare($pending_earnings_sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->bindParam(2, $user_id, PDO::PARAM_INT);
$stmt->execute();
$pending_result = $stmt->fetch(PDO::FETCH_ASSOC);
$pending_earnings = $pending_result['total'];

// Calculate monthly average (last 6 months)
$avg_monthly_sql = "SELECT AVG(monthly_total) as avg_total 
                   FROM (
                       SELECT COALESCE(SUM(j.salary), 0) as monthly_total
                       FROM job_applications ja 
                       JOIN jobs j ON ja.job_id = j.id 
                       WHERE ja.worker_id = ? AND ja.status = 'accepted' 
                       AND ja.updated_at >= CURRENT_DATE - INTERVAL '6 months'
                       GROUP BY TO_CHAR(ja.updated_at, 'YYYY-MM')
                   ) as monthly_data";
$stmt = $conn->prepare($avg_monthly_sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->execute();
$avg_result = $stmt->fetch(PDO::FETCH_ASSOC);
$avg_monthly = $avg_result['avg_total'] ?? 0;

// Job statistics
$jobs_completed_sql = "SELECT COUNT(*) as count 
                        FROM job_applications ja 
                        WHERE ja.worker_id = ? AND ja.status = 'accepted'";
$stmt = $conn->prepare($jobs_completed_sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->execute();
$jobs_completed_result = $stmt->fetch(PDO::FETCH_ASSOC);
$jobs_completed = $jobs_completed_result['count'];

$active_jobs_sql = "SELECT COUNT(*) as count 
                    FROM job_applications ja 
                    WHERE ja.worker_id = ? AND ja.status = 'accepted'";
$stmt = $conn->prepare($active_jobs_sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->execute();
$active_jobs_result = $stmt->fetch(PDO::FETCH_ASSOC);
$active_jobs = $active_jobs_result['count'];

// Best month earnings
$best_month_sql = "SELECT TO_CHAR(ja.updated_at, 'YYYY-MM') as month, SUM(j.salary) as total
                   FROM job_applications ja 
                   JOIN jobs j ON ja.job_id = j.id 
                   WHERE ja.worker_id = ? AND ja.status = 'accepted'
                   GROUP BY TO_CHAR(ja.updated_at, 'YYYY-MM')
                   ORDER BY total DESC
                   LIMIT 1";
$stmt = $conn->prepare($best_month_sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->execute();
$best_month_result = $stmt->fetch(PDO::FETCH_ASSOC);
$best_month_earnings = $best_month_result['total'] ?? 0;

// Get monthly data for chart (last 6 months)
$chart_data_sql = "SELECT TO_CHAR(ja.updated_at, 'YYYY-MM') as month, SUM(j.salary) as total
                   FROM job_applications ja 
                   JOIN jobs j ON ja.job_id = j.id 
                   WHERE ja.worker_id = ? AND ja.status = 'accepted' 
                   AND ja.updated_at >= CURRENT_DATE - INTERVAL '6 months'
                   GROUP BY TO_CHAR(ja.updated_at, 'YYYY-MM')
                   ORDER BY month";
$stmt = $conn->prepare($chart_data_sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->execute();
$chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$chart_labels = [];
$chart_values = [];
foreach ($chart_data as $row) {
    $chart_labels[] = date('M Y', strtotime($row['month'] . '-01'));
    $chart_values[] = floatval($row['total']);
}

// Get payment history
$payment_history_sql = "SELECT ja.updated_at as date, u.name as employer_name, j.type, 
                               j.work_hours, j.salary, 
                               CASE 
                                   WHEN e.payment_status = 'paid' THEN 'Paid'
                                   WHEN e.payment_status = 'pending' THEN 'Pending'
                                   ELSE 'Pending'
                               END as payment_status,
                               ja.id as application_id
                        FROM job_applications ja 
                        JOIN jobs j ON ja.job_id = j.id 
                        JOIN users u ON j.employer_id = u.id 
                        LEFT JOIN earnings e ON ja.job_id = e.job_id AND ja.worker_id = e.worker_id
                        WHERE ja.worker_id = ? AND ja.status = 'accepted'
                        ORDER BY ja.updated_at DESC";
$stmt = $conn->prepare($payment_history_sql);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->execute();
$payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - Household Connect</title>
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

        .earnings-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 15px;
        }

        .earnings-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .payment-status {
            font-size: 0.8rem;
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

        .card.bg-warning {
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
                        <h2><?php echo format_currency($this_month_earnings); ?></h2>
                        <small>
                            <?php 
                            if ($last_month_earnings > 0) {
                                $change = (($this_month_earnings - $last_month_earnings) / $last_month_earnings) * 100;
                                $arrow = $change >= 0 ? 'up' : 'down';
                                echo "<i class='fas fa-arrow-$arrow'></i> " . abs(round($change)) . "% from last month";
                            } else {
                                echo "<i class='fas fa-info-circle'></i> No previous data";
                            }
                            ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card earnings-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Earnings</h5>
                        <h2><?php echo format_currency($total_earnings); ?></h2>
                        <small>All time earnings</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card earnings-card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pending</h5>
                        <h2><?php echo format_currency($pending_earnings); ?></h2>
                        <small>Awaiting payment</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card earnings-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Avg. Monthly</h5>
                        <h2><?php echo format_currency($avg_monthly); ?></h2>
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
                                <strong><?php echo $jobs_completed; ?></strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Active Jobs</span>
                                <strong><?php echo $active_jobs; ?></strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Best Month</span>
                                <strong><?php echo format_currency($best_month_earnings); ?></strong>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Total Hours</span>
                                <strong><?php 
                                    // Estimate hours based on work patterns (this could be enhanced with actual hours data)
                                    $estimated_hours = $jobs_completed * 8; // Assuming 8 hours per job average
                                    echo $estimated_hours;
                                ?></strong>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm w-100" onclick="downloadReport()">Download Report</button>
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
                                <tbody id="earnings-tbody">
                                    <?php if (!empty($payment_history)): ?>
                                        <?php foreach ($payment_history as $row): ?>
                                            <tr>
                                                <td><?php echo format_date($row['date']); ?></td>
                                                <td><?php echo htmlspecialchars($row['employer_name']); ?></td>
                                                <td><?php echo ucfirst(htmlspecialchars($row['type'])); ?></td>
                                                <td><?php echo htmlspecialchars($row['work_hours']); ?></td>
                                                <td><strong><?php echo format_currency($row['salary']); ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $row['payment_status'] === 'Paid' ? 'success' : 'warning'; ?> payment-status">
                                                        <?php echo $row['payment_status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewDetails(<?php echo $row['application_id']; ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                No payment history found. Start accepting jobs to see your earnings here.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
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

        // Earnings Chart
        const ctx = document.getElementById('earningsChart').getContext('2d');
        const chartLabels = <?php echo json_encode($chart_labels); ?>;
        const chartValues = <?php echo json_encode($chart_values); ?>;
        
        const earningsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels.length > 0 ? chartLabels : ['No Data'],
                datasets: [{
                    label: 'Monthly Earnings',
                    data: chartValues.length > 0 ? chartValues : [0],
                    borderColor: 'rgb(0, 0, 0)',
                    backgroundColor: 'rgba(0, 0, 0, 0.05)',
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

        // Filter payment history
        function filterPaymentHistory(status) {
            const rows = document.querySelectorAll('#earnings-tbody tr');
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    const statusBadge = row.querySelector('.badge');
                    const rowStatus = statusBadge ? statusBadge.textContent.trim() : '';
                    row.style.display = rowStatus === status ? '' : 'none';
                }
            });
            
            // Update active button
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // View job details
        function viewDetails(applicationId) {
            // You can implement a modal or redirect to job details
            alert('View details for application ID: ' + applicationId);
        }

        // Download report
        function downloadReport() {
            // Simple CSV download for now
            const data = [
                ['Date', 'Employer', 'Job Type', 'Hours', 'Amount', 'Status'],
                // Add real data here
            ];
            
            // Create CSV content
            let csvContent = data.map(row => row.join(',')).join('\n');
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', 'earnings_report.csv');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        // Add event listeners for filter buttons
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.btn-group .btn');
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    filterPaymentHistory(this.textContent.trim());
                });
            });
        });
    </script>
</body>
</html>
