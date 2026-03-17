<?php
require_once 'config.php';

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
            case 'verify_worker':
                $worker_id = (int)$_POST['worker_id'];
                $verification_status = sanitize_input($_POST['verification_status']);
                $admin_notes = sanitize_input($_POST['admin_notes']);
                
                // Get current worker data
                $old_sql = "SELECT * FROM workers WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->bind_param("i", $worker_id);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result();
                $old_data = $old_result->fetch_assoc();
                
                // Update worker verification status
                $sql = "UPDATE workers SET status = ?, admin_notes = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $verification_status, $admin_notes, $worker_id);
                
                if ($stmt->execute()) {
                    // Update user verification status if worker is approved
                    if ($verification_status === 'active') {
                        $user_sql = "UPDATE users SET is_verified = TRUE WHERE id = ?";
                        $user_stmt = $conn->prepare($user_sql);
                        $user_stmt->bind_param("i", $old_data['user_id']);
                        $user_stmt->execute();
                    }
                    
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'VERIFY_WORKER', 'workers', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(['status' => $verification_status, 'admin_notes' => $admin_notes]);
                    $old_values = json_encode($old_data);
                    $log_stmt->bind_param("iiss", $user_id, $worker_id, $old_values, $new_values);
                    $log_stmt->execute();
                    
                    // Send notification to worker
                    $notification_sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'system')";
                    $notification_stmt = $conn->prepare($notification_sql);
                    $title = $verification_status === 'active' ? 'Worker Profile Approved' : 'Worker Profile Update';
                    $message_text = $verification_status === 'active' 
                        ? 'Congratulations! Your worker profile has been approved and is now active.' 
                        : 'Your worker profile status has been updated: ' . ucfirst($verification_status);
                    if (!empty($admin_notes)) {
                        $message_text .= ' Admin notes: ' . $admin_notes;
                    }
                    $notification_stmt->bind_param("iss", $old_data['user_id'], $title, $message_text);
                    $notification_stmt->execute();
                    
                    $message = 'Worker verification status updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating worker verification: ' . $conn->error;
                    $message_type = 'danger';
                }
                break;
                
            case 'toggle_featured':
                $worker_id = (int)$_POST['worker_id'];
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                
                // Get current worker data
                $old_sql = "SELECT * FROM workers WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->bind_param("i", $worker_id);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result();
                $old_data = $old_result->fetch_assoc();
                
                // Update featured status
                $sql = "UPDATE workers SET is_featured = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $is_featured, $worker_id);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'TOGGLE_FEATURED', 'workers', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(['is_featured' => $is_featured]);
                    $old_values = json_encode($old_data);
                    $log_stmt->bind_param("iiss", $user_id, $worker_id, $old_values, $new_values);
                    $log_stmt->execute();
                    
                    $message = 'Worker featured status updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating featured status: ' . $conn->error;
                    $message_type = 'danger';
                }
                break;
                
            case 'update_worker_profile':
                $worker_id = (int)$_POST['worker_id'];
                $name = sanitize_input($_POST['name']);
                $description = sanitize_input($_POST['description']);
                $type = sanitize_input($_POST['type']);
                $experience_years = (int)$_POST['experience_years'];
                $hourly_rate = (float)$_POST['hourly_rate'];
                $location = sanitize_input($_POST['location']);
                $availability = sanitize_input($_POST['availability']);
                $skills = sanitize_input($_POST['skills']);
                $education = sanitize_input($_POST['education']);
                $languages = sanitize_input($_POST['languages']);
                $certifications = sanitize_input($_POST['certifications']);
                
                // Get current worker data
                $old_sql = "SELECT * FROM workers WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->bind_param("i", $worker_id);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result();
                $old_data = $old_result->fetch_assoc();
                
                // Update worker profile
                $sql = "UPDATE workers SET name = ?, description = ?, type = ?, experience_years = ?, 
                        hourly_rate = ?, location = ?, availability = ?, skills = ?, education = ?, 
                        languages = ?, certifications = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssiidsssssi", $name, $description, $type, $experience_years, 
                                  $hourly_rate, $location, $availability, $skills, $education, 
                                  $languages, $certifications, $worker_id);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'UPDATE_PROFILE', 'workers', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(compact('name', 'description', 'type', 'experience_years', 
                                                      'hourly_rate', 'location', 'availability', 'skills', 
                                                      'education', 'languages', 'certifications'));
                    $old_values = json_encode($old_data);
                    $log_stmt->bind_param("iiss", $user_id, $worker_id, $old_values, $new_values);
                    $log_stmt->execute();
                    
                    $message = 'Worker profile updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating worker profile: ' . $conn->error;
                    $message_type = 'danger';
                }
                break;
                
            case 'delete_worker':
                $worker_id = (int)$_POST['worker_id'];
                
                // Get worker data for logging
                $get_sql = "SELECT w.*, u.email FROM workers w JOIN users u ON w.user_id = u.id WHERE w.id = ?";
                $get_stmt = $conn->prepare($get_sql);
                $get_stmt->bind_param("i", $worker_id);
                $get_stmt->execute();
                $get_result = $get_stmt->get_result();
                $worker_data = $get_result->fetch_assoc();
                
                // Delete worker (cascade will handle related records)
                $sql = "DELETE FROM workers WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $worker_id);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values) 
                               VALUES (?, 'DELETE_WORKER', 'workers', ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $old_values = json_encode($worker_data);
                    $log_stmt->bind_param("iis", $user_id, $worker_id, $old_values);
                    $log_stmt->execute();
                    
                    $message = 'Worker deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error deleting worker: ' . $conn->error;
                    $message_type = 'danger';
                }
                break;
        }
    }
}

// Get workers with pagination and filtering
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
    $where_conditions[] = "(w.name LIKE ? OR w.description LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($type_filter)) {
    $where_conditions[] = "w.type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $where_conditions[] = "w.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($featured_filter)) {
    $where_conditions[] = "w.is_featured = ?";
    $params[] = $featured_filter === 'yes' ? 1 : 0;
    $types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM workers w JOIN users u ON w.user_id = u.id $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_workers = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_workers / $per_page);

// Get workers
$sql = "SELECT w.*, u.email, u.phone, u.address, u.created_at as user_created_at, u.profile_image as user_profile_image
        FROM workers w 
        JOIN users u ON w.user_id = u.id 
        $where_clause 
        ORDER BY w.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$workers = $stmt->get_result();

// Get worker types for filter
$types_sql = "SELECT DISTINCT type FROM workers ORDER BY type";
$types_result = $conn->query($types_sql);
$worker_types = [];
while ($row = $types_result->fetch_assoc()) {
    $worker_types[] = $row['type'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Management - Admin Dashboard</title>
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

        .worker-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            background: white;
        }

        .worker-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .worker-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 20px;
        }

        .worker-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
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

        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .status-pending_verification {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
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

        .rating {
            color: #f59e0b;
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
                        <h1 class="page-title h3 mb-0">Worker Management</h1>
                        <p class="page-subtitle">Verify and manage worker profiles</p>
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
                            <a class="nav-link active" href="admin-workers.php">
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
                            <div class="stat-value"><?php echo $total_workers; ?></div>
                            <div class="stat-label">Total Workers</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">
                                <?php 
                                $pending_sql = "SELECT COUNT(*) as count FROM workers WHERE status = 'pending_verification'";
                                $pending_result = $conn->query($pending_sql);
                                echo $pending_result->fetch_assoc()['count'];
                                ?>
                            </div>
                            <div class="stat-label">Pending Verification</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">
                                <?php 
                                $active_sql = "SELECT COUNT(*) as count FROM workers WHERE status = 'active'";
                                $active_result = $conn->query($active_sql);
                                echo $active_result->fetch_assoc()['count'];
                                ?>
                            </div>
                            <div class="stat-label">Active Workers</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">
                                <?php 
                                $featured_sql = "SELECT COUNT(*) as count FROM workers WHERE is_featured = 1";
                                $featured_result = $conn->query($featured_sql);
                                echo $featured_result->fetch_assoc()['count'];
                                ?>
                            </div>
                            <div class="stat-label">Featured Workers</div>
                        </div>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="search-section">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search Workers</label>
                                <input type="text" class="form-control" id="search" placeholder="Search by name, email..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="type" class="form-label">Worker Type</label>
                                <select class="form-select" id="type">
                                    <option value="">All Types</option>
                                    <?php foreach ($worker_types as $type): ?>
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
                                    <option value="pending_verification" <?php echo $status_filter === 'pending_verification' ? 'selected' : ''; ?>>Pending Verification</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
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
                                <button type="button" class="btn btn-warning w-100" onclick="showPendingOnly()">
                                    <i class="fas fa-clock me-2"></i>Pending Only
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Workers List -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Workers (<?php echo $total_workers; ?>)</h5>
                            <div class="text-muted small">
                                Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_workers); ?> of <?php echo $total_workers; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($workers->num_rows > 0): ?>
                                <?php while ($worker = $workers->fetch_assoc()): ?>
                                    <div class="worker-card">
                                        <div class="row align-items-center">
                                            <div class="col-md-2 text-center">
                                                <div class="worker-avatar mx-auto mb-2">
                                                    <?php 
                                                    $profile_image = '';
                                                    // Check user profile image first, then worker profile image
                                                    $image_to_check = '';
                                                    if (!empty($worker['user_profile_image'])) {
                                                        $image_to_check = $worker['user_profile_image'];
                                                    } elseif (!empty($worker['profile_image'])) {
                                                        $image_to_check = 'uploads/' . $worker['profile_image'];
                                                    }
                                                    
                                                    if (!empty($image_to_check) && file_exists($image_to_check)) {
                                                        $profile_image = htmlspecialchars($image_to_check);
                                                    }
                                                    ?>
                                                    <?php if ($profile_image): ?>
                                                        <img src="<?php echo $profile_image; ?>" alt="<?php echo htmlspecialchars($worker['name']); ?>">
                                                    <?php else: ?>
                                                        <?php echo strtoupper(substr($worker['name'], 0, 1)); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($worker['is_featured']): ?>
                                                    <span class="featured-badge">
                                                        <i class="fas fa-star me-1"></i>Featured
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($worker['name']); ?></h6>
                                                <div class="text-muted small mb-2"><?php echo htmlspecialchars($worker['email']); ?></div>
                                                <div class="d-flex gap-2 mb-2">
                                                    <span class="type-badge"><?php echo ucfirst($worker['type']); ?></span>
                                                    <span class="status-badge status-<?php echo $worker['status']; ?>">
                                                        <?php echo str_replace('_', ' ', ucfirst($worker['status'])); ?>
                                                    </span>
                                                </div>
                                                <?php if ($worker['rating'] > 0): ?>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $worker['rating'] ? '' : 'text-muted'; ?>"></i>
                                                        <?php endfor; ?>
                                                        <small class="text-muted">(<?php echo $worker['review_count']; ?> reviews)</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="small">
                                                    <div class="mb-1">
                                                        <i class="fas fa-money-bill-wave text-primary me-2"></i>
                                                        <?php if ($worker['hourly_rate'] > 0): ?>
                                                            <?php echo format_currency($worker['hourly_rate']); ?>/hour
                                                        <?php else: ?>
                                                            Rate not set
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="mb-1">
                                                        <i class="fas fa-briefcase text-primary me-2"></i>
                                                        <?php echo $worker['experience_years']; ?> years experience
                                                    </div>
                                                    <div class="mb-1">
                                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                                        <?php echo htmlspecialchars($worker['location'] ?: 'Not specified'); ?>
                                                    </div>
                                                    <div>
                                                        <i class="fas fa-calendar text-primary me-2"></i>
                                                        Joined <?php echo format_date($worker['user_created_at']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="d-grid gap-2">
                                                    <?php if ($worker['status'] === 'pending_verification'): ?>
                                                        <button type="button" class="btn btn-sm btn-success" onclick="verifyWorker(<?php echo $worker['id']; ?>)">
                                                            <i class="fas fa-check me-1"></i>Verify Worker
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="editWorker(<?php echo $worker['id']; ?>)">
                                                        <i class="fas fa-edit me-1"></i>Edit Profile
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-<?php echo $worker['is_featured'] ? 'secondary' : 'warning'; ?>" onclick="toggleFeatured(<?php echo $worker['id']; ?>, <?php echo $worker['is_featured'] ? '0' : '1'; ?>)">
                                                        <i class="fas fa-star me-1"></i><?php echo $worker['is_featured'] ? 'Remove Featured' : 'Make Featured'; ?>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteWorker(<?php echo $worker['id']; ?>)">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if (!empty($worker['admin_notes'])): ?>
                                            <div class="mt-3 p-2 bg-light rounded">
                                                <small class="text-muted">
                                                    <strong>Admin Notes:</strong> <?php echo htmlspecialchars($worker['admin_notes']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-hard-hat fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No workers found matching your criteria.</p>
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

    <!-- Verify Worker Modal -->
    <div class="modal fade" id="verifyWorkerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-check me-2"></i>Verify Worker
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="verifyWorkerForm">
                    <input type="hidden" name="action" value="verify_worker">
                    <input type="hidden" name="worker_id" id="verify_worker_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="verification_status" class="form-label">Verification Status *</label>
                            <select class="form-select" id="verification_status" name="verification_status" required>
                                <option value="">Select Status</option>
                                <option value="active">Approve - Make Active</option>
                                <option value="inactive">Reject - Mark Inactive</option>
                                <option value="pending_verification">Keep Pending</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" placeholder="Add notes about this verification decision..."></textarea>
                            <small class="text-muted">These notes will be visible to the worker</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i>Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Worker Modal -->
    <div class="modal fade" id="editWorkerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Edit Worker Profile
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editWorkerForm">
                    <input type="hidden" name="action" value="update_worker_profile">
                    <input type="hidden" name="worker_id" id="edit_worker_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_type" class="form-label">Worker Type *</label>
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
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_experience" class="form-label">Experience (Years)</label>
                                    <input type="number" class="form-control" id="edit_experience" name="experience_years" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_hourly_rate" class="form-label">Hourly Rate (RWF)</label>
                                    <input type="number" class="form-control" id="edit_hourly_rate" name="hourly_rate" min="0" step="100">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="edit_location" name="location">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_skills" class="form-label">Skills</label>
                            <textarea class="form-control" id="edit_skills" name="skills" rows="2" placeholder="List worker skills..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_languages" class="form-label">Languages</label>
                            <input type="text" class="form-control" id="edit_languages" name="languages" placeholder="e.g., English, Kinyarwanda, French">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_education" class="form-label">Education</label>
                                    <textarea class="form-control" id="edit_education" name="education" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_certifications" class="form-label">Certifications</label>
                                    <textarea class="form-control" id="edit_certifications" name="certifications" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_availability" class="form-label">Availability</label>
                            <textarea class="form-control" id="edit_availability" name="availability" rows="2" placeholder="Available days and times..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Profile
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

        // Show pending verification only
        function showPendingOnly() {
            window.location.href = '?status=pending_verification';
        }

        // Verify worker
        function verifyWorker(workerId) {
            document.getElementById('verify_worker_id').value = workerId;
            new bootstrap.Modal(document.getElementById('verifyWorkerModal')).show();
        }

        // Edit worker
        function editWorker(workerId) {
            // Fetch worker data via AJAX
            fetch(`admin-workers.php?action=get_worker&id=${workerId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_worker_id').value = data.id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_type').value = data.type;
                    document.getElementById('edit_description').value = data.description || '';
                    document.getElementById('edit_experience').value = data.experience_years || 0;
                    document.getElementById('edit_hourly_rate').value = data.hourly_rate || 0;
                    document.getElementById('edit_location').value = data.location || '';
                    document.getElementById('edit_skills').value = data.skills || '';
                    document.getElementById('edit_languages').value = data.languages || '';
                    document.getElementById('edit_education').value = data.education || '';
                    document.getElementById('edit_certifications').value = data.certifications || '';
                    document.getElementById('edit_availability').value = data.availability || '';
                    
                    new bootstrap.Modal(document.getElementById('editWorkerModal')).show();
                })
                .catch(error => {
                    console.error('Error fetching worker data:', error);
                    alert('Error loading worker data. Please try again.');
                });
        }

        // Toggle featured status
        function toggleFeatured(workerId, isFeatured) {
            const action = isFeatured ? 'feature' : 'unfeature';
            if (confirm(`Are you sure you want to ${action} this worker?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_featured">
                    <input type="hidden" name="worker_id" value="${workerId}">
                    <input type="hidden" name="is_featured" value="${isFeatured}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Delete worker
        function deleteWorker(workerId) {
            if (confirm('Are you sure you want to delete this worker? This action cannot be undone and will remove all associated data.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_worker">
                    <input type="hidden" name="worker_id" value="${workerId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

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
</body>
</html>
