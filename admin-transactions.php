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
            case 'update_transaction_status':
                $transaction_id = (int)$_POST['transaction_id'];
                $new_status = sanitize_input($_POST['status']);
                $admin_notes = sanitize_input($_POST['admin_notes']);
                
                // Get current transaction data
                $old_sql = "SELECT * FROM transactions WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->bind_param("i", $transaction_id);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result();
                $old_data = $old_result->fetch_assoc();
                
                // Update transaction status
                $sql = "UPDATE transactions SET status = ?, admin_notes = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $new_status, $admin_notes, $transaction_id);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'UPDATE_TRANSACTION_STATUS', 'transactions', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(['status' => $new_status, 'admin_notes' => $admin_notes]);
                    $old_values = json_encode($old_data);
                    $log_stmt->bind_param("iiss", $user_id, $transaction_id, $old_values, $new_values);
                    $log_stmt->execute();
                    
                    redirect('admin-transactions.php?success=' . urlencode('Transaction status updated successfully!'));
                } else {
                    redirect('admin-transactions.php?error=' . urlencode('Error updating transaction status: ' . $conn->error));
                }
                break;
                
            case 'delete_transaction':
                $transaction_id = (int)$_POST['transaction_id'];
                
                // Get transaction data for logging
                $get_sql = "SELECT t.*, u.name as user_name, u.email as user_email 
                           FROM transactions t 
                           JOIN users u ON t.user_id = u.id 
                           WHERE t.id = ?";
                $get_stmt = $conn->prepare($get_sql);
                $get_stmt->bind_param("i", $transaction_id);
                $get_stmt->execute();
                $get_result = $get_stmt->get_result();
                $transaction_data = $get_result->fetch_assoc();
                
                // Delete transaction
                $sql = "DELETE FROM transactions WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $transaction_id);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values) 
                               VALUES (?, 'DELETE_TRANSACTION', 'transactions', ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $old_values = json_encode($transaction_data);
                    $log_stmt->bind_param("iis", $user_id, $transaction_id, $old_values);
                    $log_stmt->execute();
                    
                    redirect('admin-transactions.php?success=' . urlencode('Transaction deleted successfully!'));
                } else {
                    redirect('admin-transactions.php?error=' . urlencode('Error deleting transaction: ' . $conn->error));
                }
                break;
        }
    }
}

// Get transactions with pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$amount_min = isset($_GET['amount_min']) ? (float)$_GET['amount_min'] : '';
$amount_max = isset($_GET['amount_max']) ? (float)$_GET['amount_max'] : '';

// Build query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(t.transaction_id LIKE ? OR t.notes LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($type_filter)) {
    $where_conditions[] = "t.type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

if (!empty($amount_min)) {
    $where_conditions[] = "t.amount >= ?";
    $params[] = $amount_min;
    $types .= 'd';
}

if (!empty($amount_max)) {
    $where_conditions[] = "t.amount <= ?";
    $params[] = $amount_max;
    $types .= 'd';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM transactions t 
             JOIN users u ON t.user_id = u.id 
             $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_transactions = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_transactions / $per_page);

// Get transactions
$sql = "SELECT t.*, u.name as user_name, u.email as user_email,
               b.id as booking_id, b.total_amount as booking_amount
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        LEFT JOIN bookings b ON t.booking_id = b.id
        $where_clause 
        ORDER BY t.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$transactions = $stmt->get_result();

// Get transaction statistics
$stats = [
    'total_transactions' => $total_transactions,
    'pending_transactions' => 0,
    'completed_transactions' => 0,
    'failed_transactions' => 0,
    'cancelled_transactions' => 0,
    'total_revenue' => 0,
    'total_payments' => 0,
    'total_refunds' => 0,
    'total_withdrawals' => 0,
    'average_transaction' => 0
];

$stats_sql = "SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_transactions,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_transactions,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_transactions,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_revenue,
    COUNT(CASE WHEN type = 'payment' THEN 1 END) as total_payments,
    COUNT(CASE WHEN type = 'refund' THEN 1 END) as total_refunds,
    COUNT(CASE WHEN type = 'withdrawal' THEN 1 END) as total_withdrawals,
    COALESCE(AVG(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as average_transaction
    FROM transactions";
$stats_result = $conn->query($stats_sql);
if ($stats_row = $stats_result->fetch_assoc()) {
    $stats = array_merge($stats, $stats_row);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management - Admin Dashboard</title>
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

        .transaction-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            background: white;
        }

        .transaction-card:hover {
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

        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-failed {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .status-cancelled {
            background: rgba(107, 114, 128, 0.1);
            color: var(--secondary-color);
        }

        .type-badge {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-payment {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .type-refund {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .type-withdrawal {
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

        .amount-display.negative {
            color: var(--danger-color);
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
                        <h1 class="page-title h3 mb-0">Transaction Management</h1>
                        <p class="page-subtitle">Monitor and manage all financial transactions</p>
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
                            <a class="nav-link active" href="admin-transactions.php">
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
                            <div class="stat-value"><?php echo $stats['total_transactions']; ?></div>
                            <div class="stat-label">Total Transactions</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['completed_transactions']; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo format_currency($stats['total_revenue']); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['pending_transactions']; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['total_payments']; ?></div>
                            <div class="stat-label">Payments</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['total_refunds']; ?></div>
                            <div class="stat-label">Refunds</div>
                        </div>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="search-section">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label for="search" class="form-label">Search Transactions</label>
                                <input type="text" class="form-control" id="search" placeholder="Search by ID, name, email..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="type" class="form-label">Type</label>
                                <select class="form-select" id="type">
                                    <option value="">All Types</option>
                                    <option value="payment" <?php echo $type_filter === 'payment' ? 'selected' : ''; ?>>Payment</option>
                                    <option value="refund" <?php echo $type_filter === 'refund' ? 'selected' : ''; ?>>Refund</option>
                                    <option value="withdrawal" <?php echo $type_filter === 'withdrawal' ? 'selected' : ''; ?>>Withdrawal</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label for="amount_min" class="form-label">Min Amount</label>
                                <input type="number" class="form-control" id="amount_min" placeholder="0" value="<?php echo htmlspecialchars($amount_min); ?>">
                            </div>
                            <div class="col-md-1">
                                <label for="amount_max" class="form-label">Max Amount</label>
                                <input type="number" class="form-control" id="amount_max" placeholder="0" value="<?php echo htmlspecialchars($amount_max); ?>">
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

                    <!-- Transactions List -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Transactions (<?php echo $total_transactions; ?>)</h5>
                            <div class="text-muted small">
                                Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_transactions); ?> of <?php echo $total_transactions; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($transactions->num_rows > 0): ?>
                                <?php while ($transaction = $transactions->fetch_assoc()): ?>
                                    <div class="transaction-card">
                                        <div class="row align-items-start">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="mb-0 me-3">Transaction #<?php echo $transaction['id']; ?></h6>
                                                    <span class="type-badge type-<?php echo $transaction['type']; ?>">
                                                        <?php echo ucfirst($transaction['type']); ?>
                                                    </span>
                                                    <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                                        <?php echo ucfirst($transaction['status']); ?>
                                                    </span>
                                                </div>
                                                
                                                <div class="row small text-muted mb-2">
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <i class="fas fa-user text-primary me-2"></i>
                                                            <strong>User:</strong> <?php echo htmlspecialchars($transaction['user_name']); ?>
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-envelope text-primary me-2"></i>
                                                            <strong>Email:</strong> <?php echo htmlspecialchars($transaction['user_email']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <i class="fas fa-hashtag text-primary me-2"></i>
                                                            <strong>Transaction ID:</strong> <?php echo htmlspecialchars($transaction['transaction_id'] ?: 'N/A'); ?>
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-clock text-primary me-2"></i>
                                                            <strong>Created:</strong> <?php echo format_date($transaction['created_at']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row small text-muted">
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <i class="fas fa-money-bill-wave text-primary me-2"></i>
                                                            <strong>Amount:</strong> 
                                                            <?php if ($transaction['amount'] > 0): ?>
                                                                <span class="amount-display <?php echo $transaction['type'] === 'refund' ? 'negative' : ''; ?>">
                                                                    <?php echo ($transaction['type'] === 'refund' ? '-' : '+') . format_currency($transaction['amount']); ?>
                                                                </span>
                                                            <?php else: ?>
                                                                Not specified
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <i class="fas fa-calendar-check text-primary me-2"></i>
                                                            <strong>Booking ID:</strong> 
                                                            <?php echo $transaction['booking_id'] ? '#' . $transaction['booking_id'] : 'N/A'; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <i class="fas fa-credit-card text-primary me-2"></i>
                                                            <strong>Payment Method:</strong> 
                                                            <?php echo $transaction['payment_method_id'] ? '#' . $transaction['payment_method_id'] : 'N/A'; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-1">
                                                            <i class="fas fa-file-invoice text-primary me-2"></i>
                                                            <strong>Booking Amount:</strong> 
                                                            <?php echo $transaction['booking_amount'] ? format_currency($transaction['booking_amount']) : 'N/A'; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($transaction['notes'])): ?>
                                                    <div class="mt-2 p-2 bg-light rounded">
                                                        <small class="text-muted">
                                                            <strong>Notes:</strong> <?php echo htmlspecialchars($transaction['notes']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($transaction['admin_notes']) && !empty($transaction['admin_notes'])): ?>
                                                    <div class="mt-2 p-2 bg-warning bg-opacity-10 rounded">
                                                        <small class="text-muted">
                                                            <strong>Admin Notes:</strong> <?php echo htmlspecialchars($transaction['admin_notes']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-grid gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="updateTransactionStatus(<?php echo $transaction['id']; ?>)">
                                                        <i class="fas fa-sync me-1"></i>Update Status
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteTransaction(<?php echo $transaction['id']; ?>)">
                                                        <i class="fas fa-trash me-1"></i>Delete Transaction
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No transactions found matching your criteria.</p>
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
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>&amount_min=<?php echo urlencode($amount_min); ?>&amount_max=<?php echo urlencode($amount_max); ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>&amount_min=<?php echo urlencode($amount_min); ?>&amount_max=<?php echo urlencode($amount_max); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&type=<?php echo urlencode($type_filter); ?>&amount_min=<?php echo urlencode($amount_min); ?>&amount_max=<?php echo urlencode($amount_max); ?>">
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

    <!-- Update Transaction Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sync me-2"></i>Update Transaction Status
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="statusForm">
                    <input type="hidden" name="action" value="update_transaction_status">
                    <input type="hidden" name="transaction_id" id="status_transaction_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" placeholder="Add notes about this status change..."></textarea>
                            <small class="text-muted">These notes are for internal admin use only</small>
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
            const type = document.getElementById('type').value;
            const amountMin = document.getElementById('amount_min').value;
            const amountMax = document.getElementById('amount_max').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (type) params.append('type', type);
            if (amountMin) params.append('amount_min', amountMin);
            if (amountMax) params.append('amount_max', amountMax);
            
            window.location.href = '?' + params.toString();
        }

        // Show pending transactions only
        function showPendingOnly() {
            window.location.href = '?status=pending';
        }

        // Update transaction status
        function updateTransactionStatus(transactionId) {
            document.getElementById('status_transaction_id').value = transactionId;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }

        // Delete transaction
        function deleteTransaction(transactionId) {
            showCustomConfirm('Are you sure you want to delete this transaction? This action cannot be undone.', () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_transaction">
                    <input type="hidden" name="transaction_id" value="${transactionId}">
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
