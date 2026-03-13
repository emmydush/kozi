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
            case 'create_user':
                $name = sanitize_input($_POST['name']);
                $email = sanitize_input($_POST['email']);
                $password = sanitize_input($_POST['password']);
                $role = sanitize_input($_POST['role']);
                $phone = sanitize_input($_POST['phone']);
                $address = sanitize_input($_POST['address']);
                
                // Validate required fields
                $required_fields = ['name', 'email', 'password', 'role'];
                $errors = validate_required($required_fields, $_POST);
                
                if (empty($errors)) {
                    // Check if email already exists
                    $check_sql = "SELECT id FROM users WHERE email = ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param("s", $email);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $message = 'Email already exists!';
                        $message_type = 'danger';
                    } else {
                        // Create user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "INSERT INTO users (name, email, password, role, phone, address, is_verified, status) 
                                VALUES (?, ?, ?, ?, ?, ?, TRUE, 'active')";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sssssss", $name, $email, $hashed_password, $role, $phone, $address);
                        
                        if ($stmt->execute()) {
                            // Log admin action
                            $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, new_values) 
                                       VALUES (?, 'CREATE', 'users', ?, ?)";
                            $log_stmt = $conn->prepare($log_sql);
                            $new_values = json_encode(['name' => $name, 'email' => $email, 'role' => $role]);
                            $log_stmt->bind_param("iis", $user_id, $conn->insert_id, $new_values);
                            $log_stmt->execute();
                            
                            redirect('admin-users.php?success=' . urlencode('User created successfully!'));
                        } else {
                            redirect('admin-users.php?error=' . urlencode('Error creating user: ' . $conn->error));
                        }
                    }
                } else {
                    $message = 'Please fill in all required fields.';
                    $message_type = 'danger';
                }
                break;
                
            case 'update_user':
                $user_id_to_update = (int)$_POST['user_id'];
                $name = sanitize_input($_POST['name']);
                $email = sanitize_input($_POST['email']);
                $role = sanitize_input($_POST['role']);
                $phone = sanitize_input($_POST['phone']);
                $address = sanitize_input($_POST['address']);
                $status = sanitize_input($_POST['status']);
                $is_verified = isset($_POST['is_verified']) ? 1 : 0;
                
                // Get old values for logging
                $old_sql = "SELECT * FROM users WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->bind_param("i", $user_id_to_update);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result();
                $old_data = $old_result->fetch_assoc();
                
                // Update user
                $sql = "UPDATE users SET name = ?, email = ?, role = ?, phone = ?, address = ?, status = ?, is_verified = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssii", $name, $email, $role, $phone, $address, $status, $is_verified, $user_id_to_update);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'UPDATE', 'users', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(['name' => $name, 'email' => $email, 'role' => $role, 'status' => $status]);
                    $old_values = json_encode($old_data);
                    $log_stmt->bind_param("iiss", $user_id, $user_id_to_update, $old_values, $new_values);
                    $log_stmt->execute();
                    
                    redirect('admin-users.php?success=' . urlencode('User updated successfully!'));
                } else {
                    redirect('admin-users.php?error=' . urlencode('Error updating user: ' . $conn->error));
                }
                break;
                
            case 'delete_user':
                $user_id_to_delete = (int)$_POST['user_id'];
                
                // Get user data for logging
                $get_sql = "SELECT * FROM users WHERE id = ?";
                $get_stmt = $conn->prepare($get_sql);
                $get_stmt->bind_param("i", $user_id_to_delete);
                $get_stmt->execute();
                $get_result = $get_stmt->get_result();
                $user_data = $get_result->fetch_assoc();
                
                // Don't allow deleting admins
                if ($user_data['role'] === 'admin') {
                    $message = 'Cannot delete admin users!';
                    $message_type = 'danger';
                } else {
                    // Delete user (cascade will handle related records)
                    $sql = "DELETE FROM users WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id_to_delete);
                    
                    if ($stmt->execute()) {
                        // Log admin action
                        $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values) 
                                   VALUES (?, 'DELETE', 'users', ?, ?)";
                        $log_stmt = $conn->prepare($log_sql);
                        $old_values = json_encode($user_data);
                        $log_stmt->bind_param("iis", $user_id, $user_id_to_delete, $old_values);
                        $log_stmt->execute();
                        
                        redirect('admin-users.php?success=' . urlencode('User deleted successfully!'));
                    } else {
                        redirect('admin-users.php?error=' . urlencode('Error deleting user: ' . $conn->error));
                    }
                }
                break;
                
            case 'toggle_status':
                $user_id_to_toggle = (int)$_POST['user_id'];
                $new_status = sanitize_input($_POST['status']);
                
                // Get old values
                $old_sql = "SELECT * FROM users WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->bind_param("i", $user_id_to_toggle);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result();
                $old_data = $old_result->fetch_assoc();
                
                // Update status
                $sql = "UPDATE users SET status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_status, $user_id_to_toggle);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'TOGGLE_STATUS', 'users', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(['status' => $new_status]);
                    $old_values = json_encode($old_data);
                    $log_stmt->bind_param("iiss", $user_id, $user_id_to_toggle, $old_values, $new_values);
                    $log_stmt->execute();
                    
                    redirect('admin-users.php?success=' . urlencode('User status updated successfully!'));
                } else {
                    redirect('admin-users.php?error=' . urlencode('Error updating user status: ' . $conn->error));
                }
                break;
        }
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize_input($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

// Build query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_users = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);

// Get users
$sql = "SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
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

        .status-suspended {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .role-badge {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-admin {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .role-employer {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .role-worker {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .search-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
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
                        <h1 class="page-title h3 mb-0">User Management</h1>
                        <p class="page-subtitle">Manage all platform users</p>
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
                            <a class="nav-link active" href="admin-users.php">
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
                    <!-- Alert Message -->
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Search and Filter Section -->
                    <div class="search-section">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search Users</label>
                                <input type="text" class="form-control" id="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role">
                                    <option value="">All Roles</option>
                                    <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="employer" <?php echo $role_filter === 'employer' ? 'selected' : ''; ?>>Employer</option>
                                    <option value="worker" <?php echo $role_filter === 'worker' ? 'selected' : ''; ?>>Worker</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                    <i class="fas fa-plus me-2"></i>Add User
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">All Users (<?php echo $total_users; ?>)</h5>
                            <div class="text-muted small">
                                Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_users); ?> of <?php echo $total_users; ?>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Role</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th>Verified</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($users->num_rows > 0): ?>
                                            <?php while ($user = $users->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="user-avatar me-3">
                                                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                                                                <div class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <?php if (!empty($user['phone'])): ?>
                                                                <div><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($user['phone']); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($user['address'])): ?>
                                                                <div><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($user['address']); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $user['status']; ?>">
                                                            <?php echo ucfirst($user['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($user['is_verified']): ?>
                                                            <i class="fas fa-check-circle text-success"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-times-circle text-danger"></i>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <?php echo format_date($user['created_at']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-action btn-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($user['role'] !== 'admin'): ?>
                                                                <button type="button" class="btn btn-sm btn-action btn-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?>" onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>')">
                                                                    <i class="fas fa-<?php echo $user['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-action btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                    <p class="text-muted">No users found matching your criteria.</p>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php if ($total_pages > 1): ?>
                            <div class="card-footer">
                                <nav>
                                    <ul class="pagination justify-content-center mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>&status=<?php echo urlencode($status_filter); ?>">
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

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Create New User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="create_user">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="employer">Employer</option>
                                <option value="worker">Worker</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Edit User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editUserForm">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role *</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="employer">Employer</option>
                                <option value="worker">Worker</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="edit_address" class="form-label">Address</label>
                            <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status</label>
                                    <select class="form-select" id="edit_status" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_verified" class="form-label">Verified</label>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="edit_verified" name="is_verified" value="1">
                                        <label class="form-check-label" for="edit_verified">
                                            Mark as verified
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update User
                        </button>
                    </div>
                </form>
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

        // Apply filters
        function applyFilters() {
            const search = document.getElementById('search').value;
            const role = document.getElementById('role').value;
            const status = document.getElementById('status').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (role) params.append('role', role);
            if (status) params.append('status', status);
            
            window.location.href = '?' + params.toString();
        }

        // Edit user
        function editUser(userId) {
            // Fetch user data via AJAX (for demo, using a simple approach)
            fetch(`admin-users.php?action=get_user&id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_user_id').value = data.id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_email').value = data.email;
                    document.getElementById('edit_role').value = data.role;
                    document.getElementById('edit_phone').value = data.phone || '';
                    document.getElementById('edit_address').value = data.address || '';
                    document.getElementById('edit_status').value = data.status;
                    document.getElementById('edit_verified').checked = data.is_verified;
                    
                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                })
                .catch(error => {
                    console.error('Error fetching user data:', error);
                    // Fallback: reload page with edit parameter
                    window.location.href = `?edit_user=${userId}`;
                });
        }

        // Toggle user status
        function toggleStatus(userId, newStatus) {
            const actionMessage = newStatus === 'active' ? 'activate' : 'deactivate';
            showCustomConfirm(`Are you sure you want to ${actionMessage} this user?`, () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }

        // Delete user
        function deleteUser(userId) {
            showCustomConfirm('Are you sure you want to delete this user? This action cannot be undone.', () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            });
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

        // Initialize: Check for URL parameters on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkUrlParameters();
        });
    </script>
</body>
</html>
