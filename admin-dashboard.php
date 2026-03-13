<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    redirect('login.php');
}

// Get admin user info
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Initialize stats with default values
$stats = [
    'total_users' => 0,
    'total_workers' => 0,
    'total_employers' => 0,
    'total_jobs' => 0,
    'active_jobs' => 0,
    'total_bookings' => 0,
    'pending_bookings' => 0,
    'total_revenue' => 0,
    'pending_verifications' => 0,
    'total_reviews' => 0
];

// Fetch statistics
$sql = "SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'worker') as total_workers,
    (SELECT COUNT(*) FROM users WHERE role = 'employer') as total_employers,
    (SELECT COUNT(*) FROM jobs) as total_jobs,
    (SELECT COUNT(*) FROM jobs WHERE status = 'active') as active_jobs,
    (SELECT COUNT(*) FROM bookings) as total_bookings,
    (SELECT COUNT(*) FROM bookings WHERE status = 'pending') as pending_bookings,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE status = 'completed') as total_revenue,
    (SELECT COUNT(*) FROM workers WHERE status = 'pending_verification') as pending_verifications,
    (SELECT COUNT(*) FROM reviews) as total_reviews";

$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $stats = array_merge($stats, $row);
}

// Get recent activities
$recent_activities = [];
$activities_sql = "
    (SELECT 'New User' as activity, CONCAT(name, ' (', role, ')') as details, created_at as activity_date FROM users ORDER BY created_at DESC LIMIT 3)
    UNION ALL
    (SELECT 'New Job' as activity, title as details, created_at as activity_date FROM jobs ORDER BY created_at DESC LIMIT 3)
    UNION ALL
    (SELECT 'New Booking' as activity, CONCAT('Booking #', id) as details, created_at as activity_date FROM bookings ORDER BY created_at DESC LIMIT 3)
    ORDER BY activity_date DESC LIMIT 5
";

$activities_result = $conn->query($activities_sql);
if ($activities_result) {
    while ($row = $activities_result->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}

// Get top workers by rating
$top_workers_sql = "
    SELECT w.name, w.type, w.average_rating as rating, w.total_jobs as review_count, u.email 
    FROM workers w 
    JOIN users u ON w.user_id = u.id 
    WHERE w.average_rating > 0 
    ORDER BY w.average_rating DESC, w.total_jobs DESC 
    LIMIT 5
";

$top_workers = [];
$top_workers_result = $conn->query($top_workers_sql);
if ($top_workers_result) {
    while ($row = $top_workers_result->fetch_assoc()) {
        $top_workers[] = $row;
    }
}

// Get recent jobs needing attention
$pending_jobs_sql = "
    SELECT j.id, j.title, j.type, j.created_at, u.name as employer_name 
    FROM jobs j 
    JOIN users u ON j.employer_id = u.id 
    WHERE j.status = 'active' 
    ORDER BY j.created_at DESC 
    LIMIT 5
";

$recent_jobs = [];
$recent_jobs_result = $conn->query($pending_jobs_sql);
if ($recent_jobs_result) {
    while ($row = $recent_jobs_result->fetch_assoc()) {
        $recent_jobs[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --dark-color: #1e293b;
            --light-bg: #f8fafc;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --card-hover-shadow: 0 20px 40px rgba(0,0,0,0.15);
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-warning: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--gradient-primary);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            pointer-events: none;
            z-index: 1;
        }

        .admin-header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1000;
            padding: 1.5rem 0;
        }

        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1), rgba(124, 58, 237, 0.1));
            opacity: 0.5;
            z-index: -1;
        }

        .admin-header .container-fluid {
            position: relative;
            z-index: 10;
        }

        .admin-sidebar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            min-height: calc(100vh - 90px);
            border-right: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 8px 0 32px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 999;
        }

        .admin-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.05), rgba(124, 58, 237, 0.05));
            opacity: 0.7;
        }

        .admin-sidebar .nav-link {
            color: var(--dark-color);
            padding: 14px 24px;
            border-radius: 12px;
            margin: 6px 16px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .admin-sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            transition: left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: -1;
        }

        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: white;
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
        }

        .admin-sidebar .nav-link:hover::before,
        .admin-sidebar .nav-link.active::before {
            left: 0;
        }

        .admin-sidebar .nav-link i {
            margin-right: 16px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
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
            padding: 40px 30px;
            background: rgba(248, 250, 252, 0.9);
            min-height: calc(100vh - 90px);
            position: relative;
            z-index: 2;
        }

        .page-title {
            color: #1e293b !important;
            font-weight: 800;
            margin-bottom: 8px;
            font-size: 2.5rem;
            text-shadow: none !important;
            position: relative;
            z-index: 10;
            opacity: 1 !important;
            display: block !important;
        }

        .page-subtitle {
            color: #64748b !important;
            margin-bottom: 0;
            font-size: 1.1rem;
            font-weight: 400;
            position: relative;
            z-index: 10;
            opacity: 1 !important;
            display: block !important;
        }

        /* Force override any conflicting styles */
        h1.page-title {
            color: #1e293b !important;
        }

        p.page-subtitle {
            color: #64748b !important;
        }

        .admin-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.875rem;
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
            position: relative;
            z-index: 1;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 12px;
            padding: 16px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.3);
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
            border-radius: 12px 12px 0 0;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--card-hover-shadow);
            border-color: rgba(79, 70, 229, 0.3);
        }

        .stat-card:hover::after {
            opacity: 1;
        }

        .stat-card.primary::before { background: linear-gradient(90deg, var(--primary-color), #3b82f6); }
        .stat-card.success::before { background: linear-gradient(90deg, var(--success-color), #10b981); }
        .stat-card.warning::before { background: linear-gradient(90deg, var(--warning-color), #f59e0b); }
        .stat-card.danger::before { background: linear-gradient(90deg, var(--danger-color), #ef4444); }
        .stat-card.info::before { background: linear-gradient(90deg, var(--info-color), #06b6d4); }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .stat-icon.primary { background: linear-gradient(135deg, var(--primary-color), #3b82f6); color: white; }
        .stat-icon.success { background: linear-gradient(135deg, var(--success-color), #10b981); color: white; }
        .stat-icon.warning { background: linear-gradient(135deg, var(--warning-color), #f59e0b); color: white; }
        .stat-icon.danger { background: linear-gradient(135deg, var(--danger-color), #ef4444); color: white; }
        .stat-icon.info { background: linear-gradient(135deg, var(--info-color), #06b6d4); color: white; }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(3deg);
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 4px;
            line-height: 1;
            position: relative;
            z-index: 1;
        }

        .stat-label {
            color: var(--secondary-color);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            z-index: 1;
        }

        .stat-change {
            position: absolute;
            top: 16px;
            right: 16px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 12px;
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .stat-change.negative {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
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
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 12px;
            padding: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .chart-container h5 {
            color: var(--dark-color);
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }

        .activities-list {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .activities-list::-webkit-scrollbar {
            width: 4px;
        }

        .activities-list::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 2px;
        }

        .activities-list::-webkit-scrollbar-thumb {
            background: rgba(79, 70, 229, 0.3);
            border-radius: 2px;
        }

        .activities-list::-webkit-scrollbar-thumb:hover {
            background: rgba(79, 70, 229, 0.5);
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-radius: 8px;
            margin-bottom: 4px;
        }

        .activity-item:hover {
            background: rgba(79, 70, 229, 0.05);
            transform: translateX(4px);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 14px;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .activity-icon.user { background: linear-gradient(135deg, var(--primary-color), #3b82f6); color: white; }
        .activity-icon.job { background: linear-gradient(135deg, var(--success-color), #10b981); color: white; }
        .activity-icon.booking { background: linear-gradient(135deg, var(--warning-color), #f59e0b); color: white; }

        .activity-item:hover .activity-icon {
            transform: scale(1.1) rotate(3deg);
        }

        .activity-content {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 2px;
            font-size: 0.85rem;
        }

        .activity-time {
            color: var(--secondary-color);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            font-size: 16px;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 4px;
        }

        .activity-time {
            color: var(--secondary-color);
            font-size: 0.875rem;
        }

        .admin-badge {
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
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

        .quick-action-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .quick-action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.05), rgba(124, 58, 237, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .quick-action-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--card-hover-shadow);
            border-color: rgba(79, 70, 229, 0.3);
        }

        .quick-action-card:hover::before {
            opacity: 1;
        }

        .quick-action-card i {
            font-size: 1.8rem;
            margin-bottom: 8px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .quick-action-card:hover i {
            transform: scale(1.1) rotate(3deg);
        }

        .quick-action-card h6 {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 4px;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }

        .quick-action-card p {
            color: var(--secondary-color);
            font-size: 0.875rem;
            margin-bottom: 0;
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

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 12px;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .stat-icon {
                width: 35px;
                height: 35px;
                font-size: 16px;
            }

            .quick-action-card {
                padding: 12px;
            }

            .quick-action-card i {
                font-size: 1.5rem;
            }

            .chart-container {
                padding: 12px;
            }

            .activity-icon {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }
        }

        .mobile-menu-toggle {
            display: none;
        }

        /* Final override for header text colors */
        .admin-header .page-title,
        .admin-header h1.page-title {
            color: #1e293b !important;
            text-shadow: none !important;
        }

        .admin-header .page-subtitle,
        .admin-header p.page-subtitle {
            color: #64748b !important;
            text-shadow: none !important;
        }

        /* Custom Confirmation Modal Styles */
        #confirmationModal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        #confirmationModal .modal-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
        }

        #confirmationModal .modal-body {
            padding: 2rem 1.5rem;
        }

        #confirmationModal .modal-footer {
            padding: 1.5rem;
            background: #f8f9fa;
        }

        #confirmationModal .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 0.75rem 2rem;
            transition: all 0.3s ease;
            border: none;
        }

        #confirmationModal .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        #confirmationModal .btn-secondary {
            background: #6c757d;
            color: white;
        }

        #confirmationModal .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        #confirmationModal .fa-question-circle {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
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
                        <h1 class="page-title h3 mb-0" style="color: #1e293b !important;">Admin Dashboard</h1>
                        <p class="page-subtitle" style="color: #64748b !important;">Manage your household worker platform</p>
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
                            <a class="nav-link active" href="admin-dashboard.php">
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
                            <a class="nav-link" href="admin-reports.php">
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
                    <!-- Statistics Cards -->
                    <div class="stats-grid">
                        <div class="stat-card primary">
                            <div class="stat-icon primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-value"><?php echo isset($stats['total_users']) ? $stats['total_users'] : 0; ?></div>
                            <div class="stat-label">Total Users</div>
                            <div class="stat-change">+12%</div>
                        </div>
                        <div class="stat-card success">
                            <div class="stat-icon success">
                                <i class="fas fa-hard-hat"></i>
                            </div>
                            <div class="stat-value"><?php echo isset($stats['total_workers']) ? $stats['total_workers'] : 0; ?></div>
                            <div class="stat-label">Workers</div>
                            <div class="stat-change">+8%</div>
                        </div>
                        <div class="stat-card warning">
                            <div class="stat-icon warning">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="stat-value"><?php echo isset($stats['total_jobs']) ? $stats['total_jobs'] : 0; ?></div>
                            <div class="stat-label">Jobs Posted</div>
                            <div class="stat-change">+15%</div>
                        </div>
                        <div class="stat-card info">
                            <div class="stat-icon info">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-value"><?php echo isset($stats['total_bookings']) ? $stats['total_bookings'] : 0; ?></div>
                            <div class="stat-label">Bookings</div>
                            <div class="stat-change">+22%</div>
                        </div>
                        <div class="stat-card primary">
                            <div class="stat-icon primary">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-value"><?php echo isset($stats['total_revenue']) ? format_currency($stats['total_revenue']) : format_currency(0); ?></div>
                            <div class="stat-label">Revenue</div>
                            <div class="stat-change">+18%</div>
                        </div>
                        <div class="stat-card success">
                            <div class="stat-icon success">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-value"><?php echo isset($stats['total_reviews']) ? $stats['total_reviews'] : 0; ?></div>
                            <div class="stat-label">Reviews</div>
                            <div class="stat-change negative">-2%</div>
                        </div>
                    </div>

                    <!-- Additional Statistics Row -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="stat-card danger">
                                <div class="stat-icon">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($stats['pending_verifications']); ?></div>
                                <div class="stat-label">Pending Verifications</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="stat-card warning">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($stats['pending_bookings']); ?></div>
                                <div class="stat-label">Pending Bookings</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="stat-card success">
                                <div class="stat-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($stats['total_employers']); ?></div>
                                <div class="stat-label">Employers</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
                            <div class="stat-card info">
                                <div class="stat-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="stat-value"><?php echo number_format($stats['total_reviews']); ?></div>
                                <div class="stat-label">Total Reviews</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-white mb-3">Quick Actions</h5>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="quick-action-card" onclick="window.location.href='admin-users.php'">
                                <i class="fas fa-user-plus"></i>
                                <h6>Add User</h6>
                                <p>Create new account</p>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="quick-action-card" onclick="window.location.href='admin-workers.php'">
                                <i class="fas fa-user-check"></i>
                                <h6>Verify Worker</h6>
                                <p>Approve applications</p>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="quick-action-card" onclick="window.location.href='admin-jobs.php'">
                                <i class="fas fa-eye"></i>
                                <h6>Review Jobs</h6>
                                <p>Moderate postings</p>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="quick-action-card" onclick="window.location.href='admin-bookings.php'">
                                <i class="fas fa-handshake"></i>
                                <h6>Manage Bookings</h6>
                                <p>Handle requests</p>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="quick-action-card" onclick="window.location.href='admin-reports.php'">
                                <i class="fas fa-download"></i>
                                <h6>Generate Report</h6>
                                <p>Export analytics</p>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="quick-action-card" onclick="window.location.href='admin-settings.php'">
                                <i class="fas fa-cog"></i>
                                <h6>Settings</h6>
                                <p>System config</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="row">
                        <div class="col-12 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-4">Recent Activities</h5>
                                <div class="row">
                                    <?php if (empty($recent_activities)): ?>
                                        <div class="col-12">
                                            <p class="text-muted">No recent activities found.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <div class="col-lg-6 col-md-12 mb-3">
                                                <div class="activity-item">
                                                    <div class="activity-icon bg-primary bg-opacity-10 text-primary">
                                                        <i class="fas fa-<?php echo $activity['activity'] === 'New User' ? 'user-plus' : ($activity['activity'] === 'New Job' ? 'briefcase' : 'calendar-check'); ?>"></i>
                                                    </div>
                                                    <div class="activity-content">
                                                        <div class="activity-title"><?php echo htmlspecialchars($activity['activity']); ?></div>
                                                        <div class="activity-time"><?php echo htmlspecialchars($activity['details']); ?> • <?php echo format_date($activity['activity_date']); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Workers and Recent Jobs -->
                    <div class="row">
                        <!-- Top Workers -->
                        <div class="col-lg-6 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-4">Top Rated Workers</h5>
                                <?php if (empty($top_workers)): ?>
                                    <p class="text-muted">No workers found.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Rating</th>
                                                    <th>Reviews</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($top_workers as $worker): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 12px;">
                                                                    <?php echo strtoupper(substr($worker['name'], 0, 1)); ?>
                                                                </div>
                                                                <?php echo htmlspecialchars($worker['name']); ?>
                                                            </div>
                                                        </td>
                                                        <td><span class="badge bg-info"><?php echo ucfirst($worker['type']); ?></span></td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-star text-warning me-1"></i>
                                                                <?php echo number_format($worker['rating'], 1); ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $worker['review_count']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Jobs -->
                        <div class="col-lg-6 mb-4">
                            <div class="chart-container">
                                <h5 class="mb-4">Recent Job Postings</h5>
                                <?php if (empty($recent_jobs)): ?>
                                    <p class="text-muted">No recent jobs found.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Employer</th>
                                                    <th>Type</th>
                                                    <th>Posted</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_jobs as $job): ?>
                                                    <tr>
                                                        <td>
                                                            <a href="#" class="text-decoration-none fw-bold"><?php echo htmlspecialchars($job['title']); ?></a>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($job['employer_name']); ?></td>
                                                        <td><span class="badge bg-success"><?php echo ucfirst($job['type']); ?></span></td>
                                                        <td><?php echo format_date($job['created_at']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Toast Container -->
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
            <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" id="successToastMessage">
                    Action completed successfully!
                </div>
            </div>
            
            <div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-danger text-white">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" id="errorToastMessage">
                    Something went wrong!
                </div>
            </div>
            
            <div id="infoToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-info text-white">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong class="me-auto">Information</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" id="infoToastMessage">
                    Information message
                </div>
            </div>
        </div>

        <!-- Custom Confirmation Modal -->
        <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="confirmationModalLabel">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Confirm Action
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-question-circle fa-3x text-primary"></i>
                        </div>
                        <p class="text-center mb-0" id="confirmationMessage">
                            Are you sure you want to perform this action?
                        </p>
                    </div>
                    <div class="modal-footer border-0 justify-content-center">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">
                            <i class="fas fa-trash me-2"></i>Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>

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

        // Toast notification functions
        function showToast(type, message) {
            const toastElement = document.getElementById(type + 'Toast');
            const messageElement = document.getElementById(type + 'ToastMessage');
            
            // Set message
            messageElement.textContent = message;
            
            // Create and show toast
            const toast = new bootstrap.Toast(toastElement, {
                delay: 3000,
                autohide: true
            });
            
            toast.show();
        }

        // Success toast shortcut
        function showSuccessToast(message) {
            showToast('success', message);
        }

        // Error toast shortcut
        function showErrorToast(message) {
            showToast('error', message);
        }

        // Info toast shortcut
        function showInfoToast(message) {
            showToast('info', message);
        }

        // Check for URL parameters and show appropriate toasts
        function checkUrlParameters() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Check for success message
            if (urlParams.get('success')) {
                const successMessage = urlParams.get('success');
                showSuccessToast(successMessage);
                
                // Remove parameter from URL
                const url = new URL(window.location);
                url.searchParams.delete('success');
                window.history.replaceState({}, '', url);
            }
            
            // Check for error message
            if (urlParams.get('error')) {
                const errorMessage = urlParams.get('error');
                showErrorToast(errorMessage);
                
                // Remove parameter from URL
                const url = new URL(window.location);
                url.searchParams.delete('error');
                window.history.replaceState({}, '', url);
            }
            
            // Check for info message
            if (urlParams.get('info')) {
                const infoMessage = urlParams.get('info');
                showInfoToast(infoMessage);
                
                // Remove parameter from URL
                const url = new URL(window.location);
                url.searchParams.delete('info');
                window.history.replaceState({}, '', url);
            }
        }

        // Custom confirmation dialog function
        function showCustomConfirm(message, onConfirm, onCancel = null) {
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            const messageElement = document.getElementById('confirmationMessage');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            // Set the message
            messageElement.textContent = message;
            
            // Remove previous event listeners
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            
            // Add click event listener
            newConfirmBtn.addEventListener('click', () => {
                modal.hide();
                if (onConfirm) onConfirm();
            });
            
            // Handle modal hidden event for cancel
            const modalElement = document.getElementById('confirmationModal');
            modalElement.addEventListener('hidden.bs.modal', function () {
                if (onCancel) onCancel();
            }, { once: true });
            
            // Show the modal
            modal.show();
        }

        
        // Auto-refresh dashboard data every 30 seconds
        setInterval(() => {
            // You can implement AJAX refresh here if needed
            console.log('Dashboard refresh check...');
        }, 30000);

        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Initialize: Check for URL parameters on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkUrlParameters();
        });
    </script>
</body>
</html>
