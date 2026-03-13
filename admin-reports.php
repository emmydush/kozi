<?php
require_once 'config.php';

// Check if user is logged in and is admin
require_admin();

// Get admin user info
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Handle date range filtering
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-t');

// Handle report type
$report_type = isset($_GET['report_type']) ? sanitize_input($_GET['report_type']) : 'overview';

// Get financial statistics
$financial_stats = [
    'total_revenue' => 0,
    'total_transactions' => 0,
    'completed_transactions' => 0,
    'pending_transactions' => 0,
    'failed_transactions' => 0,
    'platform_fees' => 0,
    'avg_transaction_amount' => 0,
    'monthly_growth' => 0
];

// Get transactions statistics
$trans_sql = "SELECT 
    COUNT(*) as total_transactions,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_transactions,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_transactions,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_revenue,
    COALESCE(AVG(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as avg_transaction_amount
    FROM transactions 
    WHERE created_at BETWEEN ? AND ?";
$trans_stmt = $conn->prepare($trans_sql);
$trans_stmt->bind_param("ss", $start_date, $end_date);
$trans_stmt->execute();
$trans_result = $trans_stmt->get_result();
if ($trans_row = $trans_result->fetch_assoc()) {
    $financial_stats = array_merge($financial_stats, $trans_row);
}

// Calculate platform fees (assuming 5% fee)
$financial_stats['platform_fees'] = $financial_stats['total_revenue'] * 0.05;

// Get monthly revenue data
$monthly_revenue = [];
$monthly_sql = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue,
    COUNT(*) as transactions
    FROM transactions 
    WHERE created_at BETWEEN DATE_SUB(?, INTERVAL 12 MONTH) AND ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month";
$monthly_stmt = $conn->prepare($monthly_sql);
$monthly_stmt->bind_param("ss", $end_date, $end_date);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_revenue[] = $row;
}

// Get top earners (workers)
$top_earners_sql = "SELECT 
    w.name,
    w.user_id,
    COALESCE(SUM(t.amount), 0) as total_earned,
    COUNT(t.id) as transaction_count
    FROM workers w
    LEFT JOIN transactions t ON w.user_id = t.user_id AND t.status = 'completed'
    WHERE t.created_at BETWEEN ? AND ?
    GROUP BY w.id, w.name, w.user_id
    HAVING total_earned > 0
    ORDER BY total_earned DESC
    LIMIT 10";
$top_earners_stmt = $conn->prepare($top_earners_sql);
$top_earners_stmt->bind_param("ss", $start_date, $end_date);
$top_earners_stmt->execute();
$top_earners_result = $top_earners_stmt->get_result();

// Get job statistics by type
$job_stats_sql = "SELECT 
    j.type,
    COUNT(j.id) as total_jobs,
    COUNT(CASE WHEN j.status = 'filled' THEN 1 END) as filled_jobs,
    COUNT(CASE WHEN j.status = 'active' THEN 1 END) as active_jobs
    FROM jobs j
    WHERE j.created_at BETWEEN ? AND ?
    GROUP BY j.type
    ORDER BY total_jobs DESC";
$job_stats_stmt = $conn->prepare($job_stats_sql);
$job_stats_stmt->bind_param("ss", $start_date, $end_date);
$job_stats_stmt->execute();
$job_stats_result = $job_stats_stmt->get_result();

// Get booking statistics
$booking_stats_sql = "SELECT 
    COUNT(*) as total_bookings,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_booking_value
    FROM bookings 
    WHERE created_at BETWEEN ? AND ?";
$booking_stats_stmt = $conn->prepare($booking_stats_sql);
$booking_stats_stmt->bind_param("ss", $start_date, $end_date);
$booking_stats_stmt->execute();
$booking_stats_result = $booking_stats_stmt->get_result();
$booking_stats = $booking_stats_result->fetch_assoc();

// Get user growth data
$user_growth_sql = "SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as new_users,
    SUM(CASE WHEN role = 'worker' THEN 1 ELSE 0 END) as new_workers,
    SUM(CASE WHEN role = 'employer' THEN 1 ELSE 0 END) as new_employers
    FROM users 
    WHERE created_at BETWEEN DATE_SUB(?, INTERVAL 12 MONTH) AND ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month";
$user_growth_stmt = $conn->prepare($user_growth_sql);
$user_growth_stmt->bind_param("ss", $end_date, $end_date);
$user_growth_stmt->execute();
$user_growth_result = $user_growth_stmt->get_result();
$user_growth_data = [];
while ($row = $user_growth_result->fetch_assoc()) {
    $user_growth_data[] = $row;
}

// Handle export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="financial_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV header
    fputcsv($output, ['Financial Report - ' . date('Y-m-d')]);
    fputcsv($output, []);
    fputcsv($output, ['Period', $start_date . ' to ' . $end_date]);
    fputcsv($output, []);
    
    // Financial Statistics
    fputcsv($output, ['Financial Statistics']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Revenue', format_currency($financial_stats['total_revenue'])]);
    fputcsv($output, ['Total Transactions', $financial_stats['total_transactions']]);
    fputcsv($output, ['Completed Transactions', $financial_stats['completed_transactions']]);
    fputcsv($output, ['Pending Transactions', $financial_stats['pending_transactions']]);
    fputcsv($output, ['Failed Transactions', $financial_stats['failed_transactions']]);
    fputcsv($output, ['Platform Fees', format_currency($financial_stats['platform_fees'])]);
    fputcsv($output, ['Average Transaction Amount', format_currency($financial_stats['avg_transaction_amount'])]);
    fputcsv($output, []);
    
    // Top Earners
    fputcsv($output, ['Top Earners']);
    fputcsv($output, ['Worker Name', 'Total Earned', 'Transaction Count']);
    while ($row = $top_earners_result->fetch_assoc()) {
        fputcsv($output, [
            $row['name'],
            format_currency($row['total_earned']),
            $row['transaction_count']
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --dark-color: #1f2937;
            --light-bg: #f8fafc;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .admin-sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            min-height: calc(100vh - 70px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 4px 0 6px rgba(0, 0, 0, 0.1);
        }

        .admin-sidebar .nav-link {
            color: var(--dark-color);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 12px;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
            transform: translateX(4px);
        }

        .admin-sidebar .nav-link i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            padding: 30px;
            background: rgba(248, 250, 252, 0.8);
            min-height: calc(100vh - 70px);
        }

        .page-title {
            color: white;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0;
        }

        .admin-badge {
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .card {
            background: white;
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--info-color));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card.primary::before { background: linear-gradient(90deg, var(--primary-color), #3b82f6); }
        .stat-card.success::before { background: linear-gradient(90deg, var(--success-color), #10b981); }
        .stat-card.warning::before { background: linear-gradient(90deg, var(--warning-color), #f59e0b); }
        .stat-card.danger::before { background: linear-gradient(90deg, var(--danger-color), #ef4444); }
        .stat-card.info::before { background: linear-gradient(90deg, var(--info-color), #06b6d4); }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .stat-card.primary .stat-icon { background: rgba(37, 99, 235, 0.1); color: var(--primary-color); }
        .stat-card.success .stat-icon { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
        .stat-card.warning .stat-icon { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
        .stat-card.danger .stat-icon { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
        .stat-card.info .stat-icon { background: rgba(6, 182, 212, 0.1); color: var(--info-color); }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--secondary-color);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
        }

        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }

        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--light-bg);
            border-bottom: 2px solid #e5e7eb;
            font-weight: 600;
            color: var(--dark-color);
            padding: 16px 12px;
        }

        .table tbody td {
            padding: 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f3f4f6;
        }

        .table tbody tr:hover {
            background: var(--light-bg);
        }

        .btn-export {
            background: linear-gradient(135deg, var(--success-color), #10b981);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .report-tabs {
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 24px;
        }

        .report-tabs .nav-link {
            border: none;
            color: var(--secondary-color);
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 8px 8px 0 0;
            transition: all 0.3s ease;
        }

        .report-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        .report-tabs .nav-link:hover {
            color: var(--primary-color);
        }

        .report-tabs .nav-link.active:hover {
            color: white;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                position: fixed;
                left: -280px;
                top: 70px;
                z-index: 1000;
                width: 280px;
            }

            .admin-sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .mobile-menu-toggle {
                display: block;
                background: var(--primary-color);
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 8px;
                margin-right: 15px;
            }

            .stat-value {
                font-size: 2rem;
            }
        }

        .mobile-menu-toggle {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header py-3">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1 class="page-title h3 mb-0">Financial Reports</h1>
                        <p class="page-subtitle">Analytics and insights for platform performance</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end">
                        <div class="fw-bold text-dark">Welcome, <?php echo htmlspecialchars($user_name); ?></div>
                        <div class="text-muted small">Administrator</div>
                    </div>
                    <div class="admin-badge">
                        <i class="fas fa-shield-alt me-2"></i>ADMIN
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <div class="col-md-3 col-lg-2 p-0">
                <div class="admin-sidebar" id="adminSidebar">
                    <div class="p-3">
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">Navigation</h6>
                        <nav class="nav flex-column">
                            <a class="nav-link" href="admin-dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a class="nav-link" href="admin-users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                            <a class="nav-link" href="admin-workers.php">
                                <i class="fas fa-hard-hat"></i> Workers
                            </a>
                            <a class="nav-link" href="admin-jobs.php">
                                <i class="fas fa-briefcase"></i> Jobs
                            </a>
                            <a class="nav-link" href="admin-bookings.php">
                                <i class="fas fa-calendar-check"></i> Bookings
                            </a>
                            <a class="nav-link" href="admin-reviews.php">
                                <i class="fas fa-star"></i> Reviews
                            </a>
                            <a class="nav-link" href="admin-transactions.php">
                                <i class="fas fa-money-bill-wave"></i> Transactions
                            </a>
                            <a class="nav-link active" href="admin-reports.php">
                                <i class="fas fa-chart-line"></i> Reports
                            </a>
                            <a class="nav-link" href="admin-settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <hr class="my-3">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-arrow-left"></i> Back to App
                            </a>
                            <a class="nav-link text-danger" href="#" onclick="confirmLogout(event)">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="report_type" class="form-label">Report Type</label>
                                <select class="form-select" id="report_type">
                                    <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                                    <option value="financial" <?php echo $report_type === 'financial' ? 'selected' : ''; ?>>Financial</option>
                                    <option value="users" <?php echo $report_type === 'users' ? 'selected' : ''; ?>>Users</option>
                                    <option value="jobs" <?php echo $report_type === 'jobs' ? 'selected' : ''; ?>>Jobs</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
                                    <i class="fas fa-filter me-2"></i>Apply
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-export w-100" onclick="exportReport()">
                                    <i class="fas fa-download me-2"></i>Export CSV
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="stat-card primary">
                                <div class="stat-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="stat-value"><?php echo format_currency($financial_stats['total_revenue']); ?></div>
                                <div class="stat-label">Total Revenue</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="stat-card success">
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-value"><?php echo $financial_stats['completed_transactions']; ?></div>
                                <div class="stat-label">Completed Transactions</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="stat-card warning">
                                <div class="stat-icon">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="stat-value"><?php echo format_currency($financial_stats['platform_fees']); ?></div>
                                <div class="stat-label">Platform Fees</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="stat-card info">
                                <div class="stat-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="stat-value"><?php echo format_currency($financial_stats['avg_transaction_amount']); ?></div>
                                <div class="stat-label">Avg Transaction</div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="row">
                        <!-- Revenue Chart -->
                        <div class="col-lg-8 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-4">Monthly Revenue Trend</h5>
                                <canvas id="revenueChart" height="100"></canvas>
                            </div>
                        </div>

                        <!-- Transaction Status Chart -->
                        <div class="col-lg-4 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-4">Transaction Status</h5>
                                <canvas id="transactionChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- User Growth Chart -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="chart-container">
                                <h5 class="mb-4">User Growth Trend</h5>
                                <canvas id="userGrowthChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Top Tables Section -->
                    <div class="row">
                        <!-- Top Earners -->
                        <div class="col-lg-6 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-4">Top Earners</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Worker Name</th>
                                                <th>Total Earned</th>
                                                <th>Transactions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($top_earners_result->num_rows > 0): ?>
                                                <?php while ($row = $top_earners_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 12px;">
                                                                    <?php echo strtoupper(substr($row['name'], 0, 1)); ?>
                                                                </div>
                                                                <?php echo htmlspecialchars($row['name']); ?>
                                                            </div>
                                                        </td>
                                                        <td class="fw-bold text-success"><?php echo format_currency($row['total_earned']); ?></td>
                                                        <td><?php echo $row['transaction_count']; ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No earnings data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Job Statistics -->
                        <div class="col-lg-6 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-4">Job Statistics by Type</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Job Type</th>
                                                <th>Total</th>
                                                <th>Active</th>
                                                <th>Filled</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($job_stats_result->num_rows > 0): ?>
                                                <?php while ($row = $job_stats_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo ucfirst($row['type']); ?></span>
                                                        </td>
                                                        <td><?php echo $row['total_jobs']; ?></td>
                                                        <td>
                                                            <span class="badge bg-success"><?php echo $row['active_jobs']; ?></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-info"><?php echo $row['filled_jobs']; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No job data available</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Statistics -->
                    <div class="row">
                        <div class="col-12">
                            <div class="chart-container">
                                <h5 class="mb-4">Booking Statistics</h5>
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <div class="stat-value text-primary"><?php echo $booking_stats['total_bookings']; ?></div>
                                            <div class="stat-label">Total Bookings</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <div class="stat-value text-success"><?php echo $booking_stats['completed_bookings']; ?></div>
                                            <div class="stat-label">Completed</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <div class="stat-value text-info"><?php echo $booking_stats['confirmed_bookings']; ?></div>
                                            <div class="stat-label">Confirmed</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <div class="stat-value text-warning"><?php echo $booking_stats['pending_bookings']; ?></div>
                                            <div class="stat-label">Pending</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <div class="stat-value text-danger"><?php echo $booking_stats['cancelled_bookings']; ?></div>
                                            <div class="stat-label">Cancelled</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="text-center">
                                            <div class="stat-value text-success"><?php echo format_currency($booking_stats['total_booking_value']); ?></div>
                                            <div class="stat-label">Total Value</div>
                                        </div>
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
    <script>
        // Toggle mobile sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('adminSidebar');
            sidebar.classList.toggle('show');
        }

        // Logout confirmation
        function confirmLogout(e) {
            e.preventDefault();
            
            // Show a simple confirmation without the delete modal
            if (confirm('Are you sure you want to logout?')) {
                // Show logout toast
                showToast('info', 'Logging out... Goodbye!');
                
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
                        // Delay logout to show toast
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 1500);
                    } else {
                        throw new Error('Logout failed');
                    }
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    // Fallback: redirect anyway
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                });
            }
        }

        // Apply filters
        function applyFilters() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const reportType = document.getElementById('report_type').value;
            
            const params = new URLSearchParams();
            params.append('start_date', startDate);
            params.append('end_date', endDate);
            params.append('report_type', reportType);
            
            window.location.href = '?' + params.toString();
        }

        // Export report
        function exportReport() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            const params = new URLSearchParams();
            params.append('start_date', startDate);
            params.append('end_date', endDate);
            params.append('export', 'csv');
            
            window.location.href = '?' + params.toString();
        }

        // Initialize Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const monthlyRevenueData = <?php echo json_encode($monthly_revenue); ?>;
        
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: monthlyRevenueData.map(item => item.month),
                datasets: [{
                    label: 'Monthly Revenue',
                    data: monthlyRevenueData.map(item => item.revenue),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        borderRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: RWF ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'RWF ' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Initialize Transaction Status Chart
        const transactionCtx = document.getElementById('transactionChart').getContext('2d');
        const transactionChart = new Chart(transactionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending', 'Failed'],
                datasets: [{
                    data: [
                        <?php echo $financial_stats['completed_transactions']; ?>,
                        <?php echo $financial_stats['pending_transactions']; ?>,
                        <?php echo $financial_stats['failed_transactions']; ?>
                    ],
                    backgroundColor: [
                        '#10b981',
                        '#f59e0b',
                        '#ef4444'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Initialize User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        const userGrowthData = <?php echo json_encode($user_growth_data); ?>;
        
        const userGrowthChart = new Chart(userGrowthCtx, {
            type: 'bar',
            data: {
                labels: userGrowthData.map(item => item.month),
                datasets: [
                    {
                        label: 'Total Users',
                        data: userGrowthData.map(item => item.new_users),
                        backgroundColor: '#2563eb',
                        borderRadius: 8
                    },
                    {
                        label: 'Workers',
                        data: userGrowthData.map(item => item.new_workers),
                        backgroundColor: '#10b981',
                        borderRadius: 8
                    },
                    {
                        label: 'Employers',
                        data: userGrowthData.map(item => item.new_employers),
                        backgroundColor: '#f59e0b',
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Auto-refresh data every 5 minutes
        setInterval(() => {
            console.log('Dashboard data refresh check...');
            // You can implement AJAX refresh here if needed
        }, 300000);
    </script>
</body>
</html>
