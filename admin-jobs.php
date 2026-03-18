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
            case 'update_job_status':
                $job_id = (int)$_POST['job_id'];
                $new_status = sanitize_input($_POST['status']);
                
                // Get current job data
                $old_sql = "SELECT * FROM jobs WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->execute([$job_id]);
                $old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update job status (only update status column since admin_notes doesn't exist)
                $sql = "UPDATE jobs SET status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([$new_status, $job_id])) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'UPDATE_JOB_STATUS', 'jobs', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(['status' => $new_status]);
                    $old_values = json_encode($old_data);
                    $log_stmt->execute([$user_id, $job_id, $old_values, $new_values]);
                    
                    // Send notification to employer
                    $notification_sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'system')";
                    $notification_stmt = $conn->prepare($notification_sql);
                    $title = 'Job Status Updated';
                    $message_text = "Your job posting status has been updated to: " . ucfirst($new_status);
                    $notification_stmt->execute([$old_data['employer_id'], $title, $message_text]);
                    
                    $message = 'Job status updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating job status: ' . $conn->error;
                    $message_type = 'danger';
                }
                break;
                
            case 'edit_job':
                $job_id = (int)$_POST['job_id'];
                $title = sanitize_input($_POST['title']);
                $description = sanitize_input($_POST['description']);
                $type = sanitize_input($_POST['type']);
                $salary = (float)$_POST['salary'];
                $location = sanitize_input($_POST['location']);
                $work_hours = sanitize_input($_POST['work_hours']);
                $requirements = sanitize_input($_POST['requirements']);
                
                // Get current job data
                $old_sql = "SELECT * FROM jobs WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->execute([$job_id]);
                $old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update job
                $sql = "UPDATE jobs SET title = ?, description = ?, type = ?, salary = ?, 
                        location = ?, work_hours = ?, requirements = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([$title, $description, $type, $salary, 
                                  $location, $work_hours, $requirements, $job_id])) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'EDIT_JOB', 'jobs', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(compact('title', 'description', 'type', 'salary', 
                                                      'location', 'work_hours', 'requirements'));
                    $old_values = json_encode($old_data);
                    $log_stmt->execute([$user_id, $job_id, $old_values, $new_values]);
                    
                    $message = 'Job updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating job: ' . $conn->error;
                    $message_type = 'danger';
                }
                break;
                
            case 'delete_job':
                $job_id = (int)$_POST['job_id'];
                
                // Get job data for logging
                $get_sql = "SELECT j.*, u.email as employer_email FROM jobs j JOIN users u ON j.employer_id = u.id WHERE j.id = ?";
                $get_stmt = $conn->prepare($get_sql);
                $get_stmt->execute([$job_id]);
                $job_data = $get_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Delete job
                $sql = "DELETE FROM jobs WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([$job_id])) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values) 
                               VALUES (?, 'DELETE_JOB', 'jobs', ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $old_values = json_encode($job_data);
                    $log_stmt->execute([$user_id, $job_id, $old_values]);
                    
                    // Send notification to employer
                    $notification_sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'system')";
                    $notification_stmt = $conn->prepare($notification_sql);
                    $title = 'Job Removed';
                    $message_text = 'Your job posting "' . $job_data['title'] . '" has been removed by an administrator.';
                    $notification_stmt->execute([$job_data['employer_id'], $title, $message_text]);
                    
                    $message = 'Job deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting job: ' . $conn->error;
                    $message_type = 'danger';
                }
                break;
                
            case 'feature_job':
                $job_id = (int)$_POST['job_id'];
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $boolean_featured = $is_featured ? 'true' : 'false';
                
                // Get current job data
                $old_sql = "SELECT * FROM jobs WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->execute([$job_id]);
                $old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update featured status (add to jobs table if not exists)
                $check_column_sql = "SELECT column_name FROM information_schema.columns WHERE table_name = 'jobs' AND column_name = 'is_featured'";
                $column_result = $conn->query($check_column_sql);
                
                if ($column_result->rowCount() == 0) {
                    // Add is_featured column if it doesn't exist
                    $alter_sql = "ALTER TABLE jobs ADD COLUMN is_featured BOOLEAN DEFAULT FALSE";
                    $conn->query($alter_sql);
                }
                
                $sql = "UPDATE jobs SET is_featured = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                
                if ($stmt->execute([$boolean_featured, $job_id])) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'FEATURE_JOB', 'jobs', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(['is_featured' => $is_featured]);
                    $old_values = json_encode($old_data);
                    $log_stmt->execute([$user_id, $job_id, $old_values, $new_values]);
                    
                    $message = 'Job featured status updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating featured status: ' . $conn->error;
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get jobs with pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$featured_filter = isset($_GET['featured']) ? sanitize_input($_GET['featured']) : '';

// Build query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(j.title LIKE ? OR j.description LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($type_filter)) {
    $where_conditions[] = "j.type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $where_conditions[] = "j.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($featured_filter)) {
    $check_column_sql = "SHOW COLUMNS FROM jobs LIKE 'is_featured'";
    $column_result = $conn->query($check_column_sql);
    
    if ($column_result->num_rows > 0) {
        $where_conditions[] = "j.is_featured = ?";
        $params[] = $featured_filter === 'yes' ? 1 : 0;
        $types .= 'i';
    }
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM jobs j JOIN users u ON j.employer_id = u.id $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_jobs = $count_stmt->fetchColumn();
$total_pages = ceil($total_jobs / $per_page);

// Get jobs
$sql = "SELECT j.*, u.name as employer_name, u.email as employer_email 
        FROM jobs j 
        JOIN users u ON j.employer_id = u.id 
        $where_clause 
        ORDER BY j.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get job types for filter
$types_sql = "SELECT DISTINCT type FROM jobs ORDER BY type";
$types_result = $conn->query($types_sql);
$job_types = [];
while ($row = $types_result->fetch(PDO::FETCH_ASSOC)) {
    $job_types[] = $row['type'];
}

// Get statistics
$stats = [
    'total_jobs' => $total_jobs,
    'active_jobs' => 0,
    'filled_jobs' => 0,
    'closed_jobs' => 0,
    'featured_jobs' => 0
];

$stats_sql = "SELECT 
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_jobs,
    COUNT(CASE WHEN status = 'filled' THEN 1 END) as filled_jobs,
    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_jobs
    FROM jobs";
$stats_result = $conn->query($stats_sql);
if ($stats_row = $stats_result->fetch(PDO::FETCH_ASSOC)) {
    $stats = array_merge($stats, $stats_row);
}

// Check if featured column exists and get featured count
$check_column_sql = "SELECT column_name FROM information_schema.columns WHERE table_name = 'jobs' AND column_name = 'is_featured'";
$column_result = $conn->query($check_column_sql);
if ($column_result->fetchColumn()) {
    $featured_sql = "SELECT COUNT(*) as featured_jobs FROM jobs WHERE is_featured = 1";
    $featured_result = $conn->query($featured_sql);
    if ($featured_row = $featured_result->fetch(PDO::FETCH_ASSOC)) {
        $stats['featured_jobs'] = $featured_row['featured_jobs'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Management - Admin Dashboard</title>
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

        .job-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            background: white;
        }

        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-filled {
            background: rgba(6, 182, 212, 0.1);
            color: var(--info-color);
        }

        .status-closed {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .type-badge {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .featured-badge {
            background: linear-gradient(135deg, #f59e0b, #f97316);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
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

        .search-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .stat-label {
            color: var(--secondary-color);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .modal-header {
            background: var(--primary-color);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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

        .pagination .page-link {
            border: none;
            color: var(--primary-color);
            padding: 8px 16px;
            margin: 0 4px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            background: var(--primary-color);
            color: white;
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-color);
            color: white;
        }

        .salary-display {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--success-color);
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

            .stats-row {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
                        <h1 class="page-title h3 mb-0">Job Management</h1>
                        <p class="page-subtitle">Monitor and moderate job postings</p>
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
                            <a class="nav-link active" href="admin-jobs.php">
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
                    <!-- Alert Message -->
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['total_jobs']; ?></div>
                            <div class="stat-label">Total Jobs</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['active_jobs']; ?></div>
                            <div class="stat-label">Active Jobs</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['filled_jobs']; ?></div>
                            <div class="stat-label">Filled Jobs</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['featured_jobs']; ?></div>
                            <div class="stat-label">Featured Jobs</div>
                        </div>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="search-section">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search Jobs</label>
                                <input type="text" class="form-control" id="search" placeholder="Search by title, description..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="type" class="form-label">Job Type</label>
                                <select class="form-select" id="type">
                                    <option value="">All Types</option>
                                    <?php foreach ($job_types as $type): ?>
                                        <option value="<?php echo $type; ?>" <?php echo $type_filter === $type ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="filled" <?php echo $status_filter === 'filled' ? 'selected' : ''; ?>>Filled</option>
                                    <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="featured" class="form-label">Featured</label>
                                <select class="form-select" id="featured">
                                    <option value="">All</option>
                                    <option value="yes" <?php echo $featured_filter === 'yes' ? 'selected' : ''; ?>>Featured</option>
                                    <option value="no" <?php echo $featured_filter === 'no' ? 'selected' : ''; ?>>Not Featured</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-warning w-100" onclick="showActiveOnly()">
                                    <i class="fas fa-play me-2"></i>Active Only
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Jobs List -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Job Postings (<?php echo $total_jobs; ?>)</h5>
                            <div class="text-muted small">
                                Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_jobs); ?> of <?php echo $total_jobs; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($jobs)): ?>
                                <?php foreach ($jobs as $job): ?>
                                    <div class="job-card">
                                        <div class="row align-items-start">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="mb-0 me-3"><?php echo htmlspecialchars($job['title']); ?></h6>
                                                    <?php if (isset($job['is_featured']) && $job['is_featured']): ?>
                                                        <span class="featured-badge">
                                                            <i class="fas fa-star me-1"></i>Featured
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex gap-2 mb-2">
                                                    <span class="type-badge"><?php echo ucfirst($job['type']); ?></span>
                                                    <span class="status-badge status-<?php echo $job['status']; ?>">
                                                        <?php echo ucfirst($job['status']); ?>
                                                    </span>
                                                </div>
                                                <p class="text-muted mb-2"><?php echo htmlspecialchars(substr($job['description'], 0, 200)); ?>...</p>
                                                <div class="row small text-muted">
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <i class="fas fa-user-tie text-primary me-2"></i>
                                                            <strong>Employer:</strong> <?php echo htmlspecialchars($job['employer_name']); ?>
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-envelope text-primary me-2"></i>
                                                            <strong>Email:</strong> <?php echo htmlspecialchars($job['employer_email']); ?>
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                                            <strong>Location:</strong> <?php echo htmlspecialchars($job['location'] ?: 'Not specified'); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <i class="fas fa-clock text-primary me-2"></i>
                                                            <strong>Work Hours:</strong> <?php echo htmlspecialchars($job['work_hours'] ?: 'Not specified'); ?>
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-money-bill-wave text-primary me-2"></i>
                                                            <strong>Salary:</strong> 
                                                            <?php if ($job['salary'] > 0): ?>
                                                                <span class="salary-display"><?php echo format_currency($job['salary']); ?></span>
                                                            <?php else: ?>
                                                                Not specified
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-calendar text-primary me-2"></i>
                                                            <strong>Posted:</strong> <?php echo format_date($job['created_at']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Admin notes section removed as column doesn't exist in database -->
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-grid gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="editJob(<?php echo $job['id']; ?>)">
                                                        <i class="fas fa-edit me-1"></i>Edit Job
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $job['id']; ?>)">
                                                        <i class="fas fa-sync me-1"></i>Update Status
                                                    </button>
                                                    <?php if (isset($job['is_featured']) && $job['is_featured']): ?>
                                                        <button type="button" class="btn btn-sm btn-secondary" onclick="toggleFeatured(<?php echo $job['id']; ?>, 0)">
                                                            <i class="fas fa-star me-1"></i>Remove Featured
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-warning" onclick="toggleFeatured(<?php echo $job['id']; ?>, 1)">
                                                            <i class="fas fa-star me-1"></i>Make Featured
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteJob(<?php echo $job['id']; ?>)">
                                                        <i class="fas fa-trash me-1"></i>Delete Job
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No jobs found matching your criteria.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="card-footer">
                                <nav>
                                    <ul class="pagination justify-content-center mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>&featured=<?php echo urlencode($featured_filter); ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>&featured=<?php echo urlencode($featured_filter); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&status=<?php echo urlencode($status_filter); ?>&featured=<?php echo urlencode($featured_filter); ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sync me-2"></i>Update Job Status
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="statusForm">
                    <input type="hidden" name="action" value="update_job_status">
                    <input type="hidden" name="job_id" id="status_job_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="active">Active</option>
                                <option value="filled">Filled</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Job Modal -->
    <div class="modal fade" id="editJobModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Job
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editJobForm">
                    <input type="hidden" name="action" value="edit_job">
                    <input type="hidden" name="job_id" id="edit_job_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Job Title *</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_type" class="form-label">Job Type *</label>
                                    <select class="form-select" id="edit_type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="cleaning">Cleaning</option>
                                        <option value="cooking">Cooking</option>
                                        <option value="childcare">Childcare</option>
                                        <option value="eldercare">Elder Care</option>
                                        <option value="gardening">Gardening</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_salary" class="form-label">Salary (RWF)</label>
                                    <input type="number" class="form-control" id="edit_salary" name="salary" min="0" step="100">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="edit_location" name="location">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_work_hours" class="form-label">Work Hours</label>
                                    <input type="text" class="form-control" id="edit_work_hours" name="work_hours" placeholder="e.g., 9 AM - 5 PM, Full-time">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Job Description *</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_requirements" class="form-label">Requirements</label>
                            <textarea class="form-control" id="edit_requirements" name="requirements" rows="3" placeholder="List job requirements..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Job
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

        // Apply filters
        function applyFilters() {
            const search = document.getElementById('search').value;
            const type = document.getElementById('type').value;
            const status = document.getElementById('status').value;
            const featured = document.getElementById('featured').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (type) params.append('type', type);
            if (status) params.append('status', status);
            if (featured) params.append('featured', featured);
            
            window.location.href = '?' + params.toString();
        }

        // Show active jobs only
        function showActiveOnly() {
            window.location.href = '?status=active';
        }

        // Update job status
        function updateStatus(jobId) {
            document.getElementById('status_job_id').value = jobId;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }

        // Edit job
        function editJob(jobId) {
            // Fetch job data via AJAX
            fetch(`admin-jobs.php?action=get_job&id=${jobId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_job_id').value = data.id;
                    document.getElementById('edit_title').value = data.title;
                    document.getElementById('edit_type').value = data.type;
                    document.getElementById('edit_salary').value = data.salary || '';
                    document.getElementById('edit_location').value = data.location || '';
                    document.getElementById('edit_work_hours').value = data.work_hours || '';
                    document.getElementById('edit_description').value = data.description;
                    document.getElementById('edit_requirements').value = data.requirements || '';
                    
                    new bootstrap.Modal(document.getElementById('editJobModal')).show();
                })
                .catch(error => {
                    console.error('Error fetching job data:', error);
                    alert('Error loading job data. Please try again.');
                });
        }

        // Toggle featured status
        function toggleFeatured(jobId, isFeatured) {
            const action = isFeatured ? 'feature' : 'unfeature';
            if (confirm(`Are you sure you want to ${action} this job?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="feature_job">
                    <input type="hidden" name="job_id" value="${jobId}">
                    <input type="hidden" name="is_featured" value="${isFeatured}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Delete job
        let jobToDelete = null;
        
        function deleteJob(jobId) {
            jobToDelete = jobId;
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
            modal.show();
        }
        
        // Handle confirm delete button click
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (jobToDelete) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmationModal'));
                modal.hide();
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_job">
                    <input type="hidden" name="job_id" value="${jobToDelete}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });

        // Handle search on Enter key
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
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
    </script>
<!-- Custom Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmationModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    </div>
                    <h6 class="text-center">Are you sure you want to delete this job?</h6>
                    <p class="text-muted text-center mb-0">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-2"></i>Delete Job
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
