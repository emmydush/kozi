<?php
require_once __DIR__ . '/config.php';

// Check if user is logged in and is admin
require_admin();

// Get admin user info
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_settings':
                $settings = [
                    'site_name' => sanitize_input($_POST['site_name']),
                    'site_description' => sanitize_input($_POST['site_description']),
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? 'true' : 'false',
                    'max_workers_per_employer' => (int)$_POST['max_workers_per_employer'],
                    'auto_approve_workers' => isset($_POST['auto_approve_workers']) ? 'true' : 'false',
                    'platform_fee_percentage' => (float)$_POST['platform_fee_percentage'],
                    'min_booking_amount' => (int)$_POST['min_booking_amount'],
                    'max_booking_amount' => (int)$_POST['max_booking_amount'],
                    'enable_notifications' => isset($_POST['enable_notifications']) ? 'true' : 'false',
                    'contact_email' => sanitize_input($_POST['contact_email'])
                ];

                foreach ($settings as $key => $value) {
                    $sql = "UPDATE admin_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$value, $key]);
                }

                // Log admin action
                $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, new_values) 
                           VALUES (?, 'UPDATE_SETTINGS', 'admin_settings', ?)";
                $log_stmt = $conn->prepare($log_sql);
                $settings_json = json_encode($settings);
                $log_stmt->execute([$user_id, $settings_json]);

                $message = 'Settings updated successfully!';
                $message_type = 'success';
                break;

            case 'add_announcement':
                $title = sanitize_input($_POST['title']);
                $announcement_message = sanitize_input($_POST['message']);
                $type = sanitize_input($_POST['type']);
                $target_audience = sanitize_input($_POST['target_audience']);
                $start_date = sanitize_input($_POST['start_date']);
                $end_date = !empty($_POST['end_date']) ? sanitize_input($_POST['end_date']) : null;

                $sql = "INSERT INTO system_announcements (title, message, type, target_audience, start_date, end_date, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$title, $announcement_message, $type, $target_audience, $start_date, $end_date, $user_id]);

                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, new_values) 
                               VALUES (?, 'CREATE_ANNOUNCEMENT', 'system_announcements', ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $announcement_data = json_encode(compact('title', 'type', 'target_audience'));
                    $log_stmt->execute([$user_id, $announcement_data]);

                    $message = 'Announcement created successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error creating announcement: ' . $conn->error;
                    $message_type = 'danger';
                }
                break;

            case 'delete_announcement':
                $announcement_id = (int)$_POST['announcement_id'];

                // Get announcement data for logging
                $get_sql = "SELECT * FROM system_announcements WHERE id = ?";
                $get_stmt = $conn->prepare($get_sql);
                $get_stmt->execute([$announcement_id]);
                $announcement_data = $get_stmt->fetch(PDO::FETCH_ASSOC);

                // Delete announcement
                $sql = "DELETE FROM system_announcements WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$announcement_id]);

                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, old_values) 
                               VALUES (?, 'DELETE_ANNOUNCEMENT', 'system_announcements', ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $announcement_json = json_encode($announcement_data);
                    $log_stmt->execute([$user_id, $announcement_json]);

                    $message = 'Announcement deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting announcement: ' . $conn->error;
                    $message_type = 'danger';
                }
                break;

            case 'clear_logs':
                $days = (int)$_POST['days'];
                $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));

                $sql = "DELETE FROM admin_logs WHERE created_at < ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$cutoff_date]);

                if ($stmt->execute()) {
                    $deleted_count = $stmt->affected_rows;
                    
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, new_values) 
                               VALUES (?, 'CLEAR_LOGS', ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $log_data = json_encode(['deleted_count' => $deleted_count, 'cutoff_days' => $days]);
                    $log_stmt->execute([$user_id, $log_data]);

                    $message = "Successfully deleted $deleted_count log entries older than $days days!";
                    $message_type = 'success';
                } else {
                    $message = 'Error clearing logs: ' . $conn->error;
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get current settings
$settings_sql = "SELECT setting_key, setting_value FROM admin_settings";
$settings_result = $conn->query($settings_sql);
$settings = [];
while ($row = $settings_result->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get system announcements
$announcements_sql = "SELECT * FROM system_announcements ORDER BY created_at DESC";
$announcements_result = $conn->query($announcements_sql);

// Get recent admin logs
$logs_sql = "SELECT al.*, u.name as admin_name FROM admin_logs al 
             JOIN users u ON al.admin_id = u.id 
             ORDER BY al.created_at DESC LIMIT 50";
$logs_result = $conn->query($logs_sql);

// Get system information
$system_info = [
    'php_version' => PHP_VERSION,
    'postgres_version' => $conn->getAttribute(PDO::ATTR_SERVER_VERSION),
    'app_version' => '1.0.0',
    'database_size' => 0,
    'total_users' => 0,
    'total_workers' => 0,
    'total_jobs' => 0
];

// Get database size (PostgreSQL)
$db_size_sql = "SELECT pg_size_pretty(pg_database_size(?)) AS size_mb";
$db_size_stmt = $conn->prepare($db_size_sql);
$db_name = DB_NAME;
$db_size_stmt->execute([$db_name]);
if ($row = $db_size_stmt->fetch(PDO::FETCH_ASSOC)) {
    $system_info['database_size'] = $row['size_mb'];
}

// Get counts
$system_info['total_users'] = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$system_info['total_workers'] = $conn->query("SELECT COUNT(*) FROM workers")->fetchColumn();
$system_info['total_jobs'] = $conn->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .card-header {
            background: var(--light-bg);
            border-bottom: 1px solid #e5e7eb;
            border-radius: 16px 16px 0 0;
            font-weight: 600;
            color: var(--dark-color);
        }

        .settings-tabs {
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 24px;
        }

        .settings-tabs .nav-link {
            border: none;
            color: var(--secondary-color);
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 8px 8px 0 0;
            transition: all 0.3s ease;
        }

        .settings-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        .settings-tabs .nav-link:hover {
            color: var(--primary-color);
        }

        .settings-tabs .nav-link.active:hover {
            color: white;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-switch .form-check-input {
            width: 50px;
            height: 25px;
            background-color: #e5e7eb;
            border: none;
            cursor: pointer;
        }

        .form-switch .form-check-input:checked {
            background-color: var(--primary-color);
        }

        .announcement-item {
            border-left: 4px solid var(--primary-color);
            padding: 16px;
            margin-bottom: 16px;
            background: var(--light-bg);
            border-radius: 0 8px 8px 0;
        }

        .announcement-item.warning {
            border-left-color: var(--warning-color);
        }

        .announcement-item.success {
            border-left-color: var(--success-color);
        }

        .announcement-item.danger {
            border-left-color: var(--danger-color);
        }

        .announcement-item.info {
            border-left-color: var(--info-color);
        }

        .log-item {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            transition: background 0.3s ease;
        }

        .log-item:hover {
            background: var(--light-bg);
        }

        .log-item:last-child {
            border-bottom: none;
        }

        .log-action {
            font-weight: 600;
            color: var(--primary-color);
        }

        .system-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .system-info-item:last-child {
            border-bottom: none;
        }

        .system-info-label {
            font-weight: 600;
            color: var(--dark-color);
        }

        .system-info-value {
            color: var(--secondary-color);
            font-family: 'Courier New', monospace;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            transition: all 0.3s ease;
            margin: 0 2px;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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
                        <h1 class="page-title h3 mb-0">System Settings</h1>
                        <p class="page-subtitle">Configure platform settings and preferences</p>
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
                            <a class="nav-link" href="admin-reports.php">
                                <i class="fas fa-chart-line"></i> Reports
                            </a>
                            <a class="nav-link active" href="admin-settings.php">
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
                    <!-- Alert Message -->
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Settings Tabs -->
                    <ul class="nav nav-tabs settings-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                <i class="fas fa-cog me-2"></i>General Settings
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="announcements-tab" data-bs-toggle="tab" data-bs-target="#announcements" type="button" role="tab">
                                <i class="fas fa-bullhorn me-2"></i>Announcements
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button" role="tab">
                                <i class="fas fa-history me-2"></i>Activity Logs
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
                                <i class="fas fa-info-circle me-2"></i>System Info
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="settingsTabContent">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Platform Configuration</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_settings">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="site_name" class="form-label">Site Name</label>
                                                    <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="contact_email" class="form-label">Contact Email</label>
                                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email']); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="site_description" class="form-label">Site Description</label>
                                            <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="platform_fee_percentage" class="form-label">Platform Fee (%)</label>
                                                    <input type="number" class="form-control" id="platform_fee_percentage" name="platform_fee_percentage" value="<?php echo htmlspecialchars($settings['platform_fee_percentage']); ?>" min="0" max="100" step="0.1">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="min_booking_amount" class="form-label">Min Booking Amount (RWF)</label>
                                                    <input type="number" class="form-control" id="min_booking_amount" name="min_booking_amount" value="<?php echo htmlspecialchars($settings['min_booking_amount']); ?>" min="0">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="max_booking_amount" class="form-label">Max Booking Amount (RWF)</label>
                                                    <input type="number" class="form-control" id="max_booking_amount" name="max_booking_amount" value="<?php echo htmlspecialchars($settings['max_booking_amount']); ?>" min="0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="max_workers_per_employer" class="form-label">Max Workers per Employer</label>
                                                    <input type="number" class="form-control" id="max_workers_per_employer" name="max_workers_per_employer" value="<?php echo htmlspecialchars($settings['max_workers_per_employer']); ?>" min="1">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h6 class="mb-3">Toggle Settings</h6>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo $settings['maintenance_mode'] === 'true' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="maintenance_mode">
                                                        Maintenance Mode
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="auto_approve_workers" name="auto_approve_workers" <?php echo $settings['auto_approve_workers'] === 'true' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="auto_approve_workers">
                                                        Auto-approve Workers
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="enable_notifications" name="enable_notifications" <?php echo $settings['enable_notifications'] === 'true' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="enable_notifications">
                                                        Enable Notifications
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Save Settings
                                            </button>
                                            <button type="button" class="btn btn-secondary" onclick="resetSettings()">
                                                <i class="fas fa-undo me-2"></i>Reset
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Announcements -->
                        <div class="tab-pane fade" id="announcements" role="tabpanel">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">System Announcements</h5>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                                        <i class="fas fa-plus me-2"></i>Add Announcement
                                    </button>
                                </div>
                                <div class="card-body">
                                    <?php if ($announcements_result->rowCount() > 0):
                                        foreach ($announcements_result->fetchAll(PDO::FETCH_ASSOC) as $announcement): ?>
                                            <div class="announcement-item <?php echo $announcement['type']; ?>">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-sm btn-action btn-primary" onclick="editAnnouncement(<?php echo $announcement['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-action btn-danger" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <p class="mb-2"><?php echo htmlspecialchars($announcement['message']); ?></p>
                                                <div class="d-flex gap-3 small text-muted">
                                                    <span><i class="fas fa-tag me-1"></i><?php echo ucfirst($announcement['type']); ?></span>
                                                    <span><i class="fas fa-users me-1"></i><?php echo ucfirst($announcement['target_audience']); ?></span>
                                                    <span><i class="fas fa-calendar me-1"></i><?php echo format_date($announcement['start_date']); ?></span>
                                                    <?php if ($announcement['end_date']): ?>
                                                        <span><i class="fas fa-calendar-check me-1"></i>Ends: <?php echo format_date($announcement['end_date']); ?></span>
                                                    <?php endif; ?>
                                                    <span><i class="fas fa-circle me-1"></i><?php echo $announcement['is_active'] ? 'Active' : 'Inactive'; ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No announcements found.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Logs -->
                        <div class="tab-pane fade" id="logs" role="tabpanel">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Admin Activity Logs</h5>
                                    <div>
                                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                                            <i class="fas fa-trash me-2"></i>Clear Old Logs
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="refreshLogs()">
                                            <i class="fas fa-sync me-2"></i>Refresh
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Date/Time</th>
                                                    <th>Admin</th>
                                                    <th>Action</th>
                                                    <th>Table</th>
                                                    <th>Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                    <?php if ($logs_result->rowCount() > 0):
                                                        foreach ($logs_result->fetchAll(PDO::FETCH_ASSOC) as $log): ?>
                                                        <tr>
                                                            <td>
                                                                <div><?php echo format_date($log['created_at']); ?></div>
                                                                <small class="text-muted"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($log['admin_name']); ?></td>
                                                            <td>
                                                                <span class="log-action"><?php echo htmlspecialchars(str_replace('_', ' ', strtoupper($log['action']))); ?></span>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($log['table_name'] ?: 'N/A'); ?></td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    <?php if ($log['record_id']): ?>
                                                                        Record ID: <?php echo $log['record_id']; ?>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-4">No activity logs found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Info -->
                        <div class="tab-pane fade" id="system" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">System Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="mb-3">Environment</h6>
                                            <div class="system-info-item">
                                                <span class="system-info-label">PHP Version</span>
                                                <span class="system-info-value"><?php echo $system_info['php_version']; ?></span>
                                            </div>
                                            <div class="system-info-item">
                                                <span class="system-info-label">MySQL Version</span>
                                                <span class="system-info-value"><?php echo $system_info['mysql_version']; ?></span>
                                            </div>
                                            <div class="system-info-item">
                                                <span class="system-info-label">App Version</span>
                                                <span class="system-info-value"><?php echo $system_info['app_version']; ?></span>
                                            </div>
                                            <div class="system-info-item">
                                                <span class="system-info-label">Database Size</span>
                                                <span class="system-info-value"><?php echo $system_info['database_size']; ?> MB</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="mb-3">Statistics</h6>
                                            <div class="system-info-item">
                                                <span class="system-info-label">Total Users</span>
                                                <span class="system-info-value"><?php echo number_format($system_info['total_users']); ?></span>
                                            </div>
                                            <div class="system-info-item">
                                                <span class="system-info-label">Total Workers</span>
                                                <span class="system-info-value"><?php echo number_format($system_info['total_workers']); ?></span>
                                            </div>
                                            <div class="system-info-item">
                                                <span class="system-info-label">Total Jobs</span>
                                                <span class="system-info-value"><?php echo number_format($system_info['total_jobs']); ?></span>
                                            </div>
                                            <div class="system-info-item">
                                                <span class="system-info-label">Server Time</span>
                                                <span class="system-info-value"><?php echo date('Y-m-d H:i:s'); ?></span>
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
    </div>

    <!-- Add Announcement Modal -->
    <div class="modal fade" id="addAnnouncementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bullhorn me-2"></i>Add Announcement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_announcement">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Type *</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="info">Info</option>
                                        <option value="warning">Warning</option>
                                        <option value="success">Success</option>
                                        <option value="error">Error</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="target_audience" class="form-label">Target Audience *</label>
                                    <select class="form-select" id="target_audience" name="target_audience" required>
                                        <option value="all">All Users</option>
                                        <option value="employers">Employers Only</option>
                                        <option value="workers">Workers Only</option>
                                        <option value="admins">Admins Only</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_date" class="form-label">Start Date *</label>
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_date" class="form-label">End Date (Optional)</label>
                                    <input type="datetime-local" class="form-control" id="end_date" name="end_date">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Clear Logs Modal -->
    <div class="modal fade" id="clearLogsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>Clear Old Logs
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="clear_logs">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="days" class="form-label">Delete logs older than (days)</label>
                            <select class="form-select" id="days" name="days">
                                <option value="7">7 days</option>
                                <option value="30">30 days</option>
                                <option value="90">90 days</option>
                                <option value="180">180 days</option>
                                <option value="365">1 year</option>
                            </select>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This action cannot be undone. Please be careful when deleting logs.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Clear Logs
                        </button>
                    </div>
                </form>
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

        // Reset settings
        function resetSettings() {
            if (confirm('Are you sure you want to reset all settings to default values?')) {
                location.reload();
            }
        }

        // Edit announcement (placeholder function)
        function editAnnouncement(id) {
            alert('Edit announcement functionality would be implemented here');
        }

        // Delete announcement
        function deleteAnnouncement(id) {
            showCustomConfirm('Are you sure you want to delete this announcement?', () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_announcement">
                    <input type="hidden" name="announcement_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }

        // Refresh logs
        function refreshLogs() {
            location.reload();
        }

        // Set default datetime for announcement start date
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000)
                .toISOString()
                .slice(0, 16);
            
            const startDateInput = document.getElementById('start_date');
            if (startDateInput) {
                startDateInput.value = localDateTime;
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Custom confirmation dialog function
        function showCustomConfirm(message, onConfirm, onCancel = null) {
            const modalElement = document.getElementById('customConfirmModal');
            const messageElement = document.getElementById('customConfirmModalBody');
            const confirmBtn = document.getElementById('customConfirmOKButton');
            
            // Set the message
            messageElement.textContent = message;
            
            // Remove previous event listeners
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            
            // Add click event listener
            newConfirmBtn.addEventListener('click', () => {
                const modal = bootstrap.Modal.getInstance(modalElement);
                modal.hide();
                if (onConfirm) onConfirm();
            });
            
            // Handle modal hidden event for cancel
            modalElement.addEventListener('hidden.bs.modal', function () {
                if (onCancel) onCancel();
            }, { once: true });
            
            // Show the modal
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    </script>

    <!-- Custom Confirmation Modal -->
    <div class="modal fade" id="customConfirmModal" tabindex="-1" aria-labelledby="customConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customConfirmModalLabel">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>Confirm Action
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="customConfirmModalBody">
                    <!-- Message will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="customConfirmOKButton">
                        <i class="fas fa-check me-2"></i>Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
