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
            case 'update_booking_status':
                $booking_id = (int)$_POST['booking_id'];
                $new_status = sanitize_input($_POST['status']);
                $admin_notes = sanitize_input($_POST['admin_notes']);
                
                // Get current booking data
                $old_sql = "SELECT * FROM bookings WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->bind_param("i", $booking_id);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result();
                $old_data = $old_result->fetch_assoc();
                
                // Update booking status
                $sql = "UPDATE bookings SET status = ?, admin_notes = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $new_status, $admin_notes, $booking_id);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'UPDATE_BOOKING_STATUS', 'bookings', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(['status' => $new_status, 'admin_notes' => $admin_notes]);
                    $old_values = json_encode($old_data);
                    $log_stmt->bind_param("iiss", $user_id, $booking_id, $old_values, $new_values);
                    $log_stmt->execute();
                    
                    // Send notifications to user and worker
                    $notification_sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'booking')";
                    $notification_stmt = $conn->prepare($notification_sql);
                    $title = 'Booking Status Updated';
                    $message_text = "Your booking status has been updated to: " . ucfirst($new_status);
                    if (!empty($admin_notes)) {
                        $message_text .= ' Admin notes: ' . $admin_notes;
                    }
                    
                    // Notify user
                    $notification_stmt->bind_param("iss", $old_data['user_id'], $title, $message_text);
                    $notification_stmt->execute();
                    
                    // Notify worker
                    $worker_sql = "SELECT user_id FROM workers WHERE id = ?";
                    $worker_stmt = $conn->prepare($worker_sql);
                    $worker_stmt->bind_param("i", $old_data['worker_id']);
                    $worker_stmt->execute();
                    $worker_result = $worker_stmt->get_result();
                    $worker_data = $worker_result->fetch_assoc();
                    
                    if ($worker_data) {
                        $notification_stmt->bind_param("iss", $worker_data['user_id'], $title, $message_text);
                        $notification_stmt->execute();
                    }
                    
                    redirect('admin-bookings.php?success=' . urlencode('Booking status updated successfully!'));
                } else {
                    redirect('admin-bookings.php?error=' . urlencode('Error updating booking status: ' . $conn->error));
                }
                break;
                
            case 'update_payment_status':
                $booking_id = (int)$_POST['booking_id'];
                $new_payment_status = sanitize_input($_POST['payment_status']);
                
                // Get current booking data
                $old_sql = "SELECT * FROM bookings WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->bind_param("i", $booking_id);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result();
                $old_data = $old_result->fetch_assoc();
                
                // Update payment status
                $sql = "UPDATE bookings SET payment_status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_payment_status, $booking_id);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'UPDATE_PAYMENT_STATUS', 'bookings', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(['payment_status' => $new_payment_status]);
                    $old_values = json_encode($old_data);
                    $log_stmt->bind_param("iiss", $user_id, $booking_id, $old_values, $new_values);
                    $log_stmt->execute();
                    
                    redirect('admin-bookings.php?success=' . urlencode('Payment status updated successfully!'));
                } else {
                    redirect('admin-bookings.php?error=' . urlencode('Error updating payment status: ' . $conn->error));
                }
                break;
                
            case 'delete_booking':
                $booking_id = (int)$_POST['booking_id'];
                
                // Get booking data for logging
                $get_sql = "SELECT b.*, u.name as user_name, w.name as worker_name 
                           FROM bookings b 
                           JOIN users u ON b.user_id = u.id 
                           JOIN workers w ON b.worker_id = w.id 
                           WHERE b.id = ?";
                $get_stmt = $conn->prepare($get_sql);
                $get_stmt->bind_param("i", $booking_id);
                $get_stmt->execute();
                $get_result = $get_stmt->get_result();
                $booking_data = $get_result->fetch_assoc();
                
                // Delete booking
                $sql = "DELETE FROM bookings WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $booking_id);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values) 
                               VALUES (?, 'DELETE_BOOKING', 'bookings', ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $old_values = json_encode($booking_data);
                    $log_stmt->bind_param("iis", $user_id, $booking_id, $old_values);
                    $log_stmt->execute();
                    
                    redirect('admin-bookings.php?success=' . urlencode('Booking deleted successfully!'));
                } else {
                    redirect('admin-bookings.php?error=' . urlencode('Error deleting booking: ' . $conn->error));
                }
                break;
        }
    }
}

// Get bookings with pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$payment_status_filter = isset($_GET['payment_status']) ? sanitize_input($_GET['payment_status']) : '';
$service_type_filter = isset($_GET['service_type']) ? sanitize_input($_GET['service_type']) : '';

// Build query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(b.notes LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR w.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($payment_status_filter)) {
    $where_conditions[] = "b.payment_status = ?";
    $params[] = $payment_status_filter;
    $types .= 's';
}

if (!empty($service_type_filter)) {
    $where_conditions[] = "b.service_type = ?";
    $params[] = $service_type_filter;
    $types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM bookings b 
             JOIN users u ON b.user_id = u.id 
             JOIN workers w ON b.worker_id = w.id 
             $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_bookings = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_bookings / $per_page);

// Get bookings
$sql = "SELECT b.*, u.name as user_name, u.email as user_email, 
               w.name as worker_name, w.user_id as worker_user_id,
               wu.email as worker_email
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN workers w ON b.worker_id = w.id
        JOIN users wu ON w.user_id = wu.id
        $where_clause 
        ORDER BY b.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$bookings = $stmt->get_result();

// Get booking statistics
$stats = [
    'total_bookings' => $total_bookings,
    'pending_bookings' => 0,
    'confirmed_bookings' => 0,
    'in_progress_bookings' => 0,
    'completed_bookings' => 0,
    'cancelled_bookings' => 0,
    'pending_payments' => 0,
    'completed_payments' => 0,
    'total_revenue' => 0
];

$stats_sql = "SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_bookings,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
    COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_payments,
    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as completed_payments,
    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as total_revenue
    FROM bookings";
$stats_result = $conn->query($stats_sql);
if ($stats_row = $stats_result->fetch_assoc()) {
    $stats = array_merge($stats, $stats_row);
}

// Get service types for filter
$service_types_sql = "SELECT DISTINCT service_type FROM bookings ORDER BY service_type";
$service_types_result = $conn->query($service_types_sql);
$service_types = [];
while ($row = $service_types_result->fetch_assoc()) {
    $service_types[] = $row['service_type'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Admin Dashboard</title>
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

        .booking-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            background: white;
        }

        .booking-card:hover {
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

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-confirmed {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }

        .status-in_progress {
            background: rgba(6, 182, 212, 0.1);
            color: var(--info-color);
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .payment-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .payment-paid {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .payment-refunded {
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

        .amount-display {
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
                        <h1 class="page-title h3 mb-0">Booking Management</h1>
                        <p class="page-subtitle">Monitor and manage all platform bookings</p>
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
                            <a class="nav-link active" href="admin-bookings.php">
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
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['total_bookings']; ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['pending_bookings']; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['confirmed_bookings']; ?></div>
                            <div class="stat-label">Confirmed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['completed_bookings']; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['pending_payments']; ?></div>
                            <div class="stat-label">Pending Payments</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo format_currency($stats['total_revenue']); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="search-section">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search Bookings</label>
                                <input type="text" class="form-control" id="search" placeholder="Search by name, email..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="payment_status" class="form-label">Payment Status</label>
                                <select class="form-select" id="payment_status">
                                    <option value="">All Payment Status</option>
                                    <option value="pending" <?php echo $payment_status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $payment_status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="refunded" <?php echo $payment_status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="service_type" class="form-label">Service Type</label>
                                <select class="form-select" id="service_type">
                                    <option value="">All Types</option>
                                    <?php foreach ($service_types as $type): ?>
                                        <option value="<?php echo $type; ?>" <?php echo $service_type_filter === $type ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($type); ?>
                                        </option>
                                    <?php endforeach; ?>
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

                    <!-- Bookings List -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Bookings (<?php echo $total_bookings; ?>)</h5>
                            <div class="text-muted small">
                                Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_bookings); ?> of <?php echo $total_bookings; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($bookings->num_rows > 0): ?>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <div class="booking-card">
                                        <div class="row align-items-start">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="mb-0 me-3">Booking #<?php echo $booking['id']; ?></h6>
                                                    <span class="type-badge"><?php echo ucfirst($booking['service_type']); ?></span>
                                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                        <?php echo str_replace('_', ' ', ucfirst($booking['status'])); ?>
                                                    </span>
                                                    <span class="payment-badge payment-<?php echo $booking['payment_status']; ?>">
                                                        <?php echo ucfirst($booking['payment_status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="row small text-muted mb-2">
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <i class="fas fa-user text-primary me-2"></i>
                                                            <strong>Client:</strong> <?php echo htmlspecialchars($booking['user_name']); ?>
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-envelope text-primary me-2"></i>
                                                            <strong>Client Email:</strong> <?php echo htmlspecialchars($booking['user_email']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <i class="fas fa-hard-hat text-primary me-2"></i>
                                                            <strong>Worker:</strong> <?php echo htmlspecialchars($booking['worker_name']); ?>
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-envelope text-primary me-2"></i>
                                                            <strong>Worker Email:</strong> <?php echo htmlspecialchars($booking['worker_email']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row small text-muted">
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <i class="fas fa-calendar text-primary me-2"></i>
                                                            <strong>Start:</strong> <?php echo format_date($booking['start_date']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <i class="fas fa-calendar-check text-primary me-2"></i>
                                                            <strong>End:</strong> <?php echo format_date($booking['end_date']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <i class="fas fa-money-bill-wave text-primary me-2"></i>
                                                            <strong>Amount:</strong> 
                                                            <?php if ($booking['total_amount'] > 0): ?>
                                                                <span class="amount-display"><?php echo format_currency($booking['total_amount']); ?></span>
                                                            <?php else: ?>
                                                                Not specified
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <i class="fas fa-clock text-primary me-2"></i>
                                                            <strong>Created:</strong> <?php echo format_date($booking['created_at']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($booking['notes'])): ?>
                                                    <div class="mt-2 p-2 bg-light rounded">
                                                        <small class="text-muted">
                                                            <strong>Notes:</strong> <?php echo htmlspecialchars($booking['notes']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($booking['admin_notes']) && !empty($booking['admin_notes'])): ?>
                                                    <div class="mt-2 p-2 bg-warning bg-opacity-10 rounded">
                                                        <small class="text-muted">
                                                            <strong>Admin Notes:</strong> <?php echo htmlspecialchars($booking['admin_notes']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-grid gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="updateBookingStatus(<?php echo $booking['id']; ?>)">
                                                        <i class="fas fa-sync me-1"></i>Update Status
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning" onclick="updatePaymentStatus(<?php echo $booking['id']; ?>)">
                                                        <i class="fas fa-credit-card me-1"></i>Update Payment
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteBooking(<?php echo $booking['id']; ?>)">
                                                        <i class="fas fa-trash me-1"></i>Delete Booking
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No bookings found matching your criteria.</p>
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
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment_status=<?php echo urlencode($payment_status_filter); ?>&service_type=<?php echo urlencode($service_type_filter); ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment_status=<?php echo urlencode($payment_status_filter); ?>&service_type=<?php echo urlencode($service_type_filter); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment_status=<?php echo urlencode($payment_status_filter); ?>&service_type=<?php echo urlencode($service_type_filter); ?>">
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

    <!-- Update Booking Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sync me-2"></i>Update Booking Status
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="statusForm">
                    <input type="hidden" name="action" value="update_booking_status">
                    <input type="hidden" name="booking_id" id="status_booking_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" placeholder="Add notes about this status change..."></textarea>
                            <small class="text-muted">These notes will be visible to both client and worker</small>
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

    <!-- Update Payment Status Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-credit-card me-2"></i>Update Payment Status
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="paymentForm">
                    <input type="hidden" name="action" value="update_payment_status">
                    <input type="hidden" name="booking_id" id="payment_booking_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="payment_status" class="form-label">Payment Status *</label>
                            <select class="form-select" id="payment_status" name="payment_status" required>
                                <option value="">Select Payment Status</option>
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Payment
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
            const status = document.getElementById('status').value;
            const paymentStatus = document.getElementById('payment_status').value;
            const serviceType = document.getElementById('service_type').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (paymentStatus) params.append('payment_status', paymentStatus);
            if (serviceType) params.append('service_type', serviceType);
            
            window.location.href = '?' + params.toString();
        }

        // Show pending bookings only
        function showPendingOnly() {
            window.location.href = '?status=pending';
        }

        // Update booking status
        function updateBookingStatus(bookingId) {
            document.getElementById('status_booking_id').value = bookingId;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }

        // Update payment status
        function updatePaymentStatus(bookingId) {
            document.getElementById('payment_booking_id').value = bookingId;
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }

        // Delete booking
        function deleteBooking(bookingId) {
            showCustomConfirm('Are you sure you want to delete this booking? This action cannot be undone.', () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_booking">
                    <input type="hidden" name="booking_id" value="${bookingId}">
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

        // Initialize: Check for URL parameters on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkUrlParameters();
        });
    </script>
</body>
</html>
