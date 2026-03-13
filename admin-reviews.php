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
            case 'update_review_status':
                $review_id = (int)$_POST['review_id'];
                $new_status = sanitize_input($_POST['status']);
                $admin_notes = sanitize_input($_POST['admin_notes']);
                
                // Get current review data
                $old_sql = "SELECT * FROM reviews WHERE id = ?";
                $old_stmt = $conn->prepare($old_sql);
                $old_stmt->bind_param("i", $review_id);
                $old_stmt->execute();
                $old_result = $old_stmt->get_result();
                $old_data = $old_result->fetch_assoc();
                
                // Update review status
                $sql = "UPDATE reviews SET status = ?, admin_notes = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $new_status, $admin_notes, $review_id);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values) 
                               VALUES (?, 'UPDATE_REVIEW_STATUS', 'reviews', ?, ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $new_values = json_encode(['status' => $new_status, 'admin_notes' => $admin_notes]);
                    $old_values = json_encode($old_data);
                    $log_stmt->bind_param("iiss", $user_id, $review_id, $old_values, $new_values);
                    $log_stmt->execute();
                    
                    redirect('admin-reviews.php?success=' . urlencode('Review status updated successfully!'));
                } else {
                    redirect('admin-reviews.php?error=' . urlencode('Error updating review status: ' . $conn->error));
                }
                break;
                
            case 'delete_review':
                $review_id = (int)$_POST['review_id'];
                
                // Get review data for logging
                $get_sql = "SELECT r.*, u.name as user_name, w.name as worker_name 
                           FROM reviews r 
                           JOIN users u ON r.user_id = u.id 
                           JOIN workers w ON r.worker_id = w.id 
                           WHERE r.id = ?";
                $get_stmt = $conn->prepare($get_sql);
                $get_stmt->bind_param("i", $review_id);
                $get_stmt->execute();
                $get_result = $get_stmt->get_result();
                $review_data = $get_result->fetch_assoc();
                
                // Delete review
                $sql = "DELETE FROM reviews WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $review_id);
                
                if ($stmt->execute()) {
                    // Log admin action
                    $log_sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values) 
                               VALUES (?, 'DELETE_REVIEW', 'reviews', ?, ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $old_values = json_encode($review_data);
                    $log_stmt->bind_param("iis", $user_id, $review_id, $old_values);
                    $log_stmt->execute();
                    
                    redirect('admin-reviews.php?success=' . urlencode('Review deleted successfully!'));
                } else {
                    redirect('admin-reviews.php?error=' . urlencode('Error deleting review: ' . $conn->error));
                }
                break;
        }
    }
}

// Get reviews with pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$rating_filter = isset($_GET['rating']) ? sanitize_input($_GET['rating']) : '';

// Build query
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(r.comment LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR w.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($rating_filter)) {
    $where_conditions[] = "r.rating = ?";
    $params[] = $rating_filter;
    $types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM reviews r 
             JOIN users u ON r.user_id = u.id 
             JOIN workers w ON r.worker_id = w.id 
             $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_reviews = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_reviews / $per_page);

// Get reviews
$sql = "SELECT r.*, u.name as user_name, u.email as user_email, 
               w.name as worker_name, w.user_id as worker_user_id,
               wu.email as worker_email
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        JOIN workers w ON r.worker_id = w.id
        JOIN users wu ON w.user_id = wu.id
        $where_clause 
        ORDER BY r.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reviews = $stmt->get_result();

// Get review statistics
$stats = [
    'total_reviews' => $total_reviews,
    'pending_reviews' => 0,
    'approved_reviews' => 0,
    'rejected_reviews' => 0,
    'average_rating' => 0,
    'five_star_reviews' => 0,
    'four_star_reviews' => 0,
    'three_star_reviews' => 0,
    'two_star_reviews' => 0,
    'one_star_reviews' => 0
];

$stats_sql = "SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reviews,
    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reviews,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_reviews,
    COALESCE(AVG(rating), 0) as average_rating,
    COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star_reviews,
    COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star_reviews,
    COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star_reviews,
    COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star_reviews,
    COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star_reviews
    FROM reviews";
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
    <title>Review Management - Admin Dashboard</title>
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

        .review-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            background: white;
        }

        .review-card:hover {
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

        .status-approved {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .rating-stars {
            color: #fbbf24;
            font-size: 1rem;
        }

        .rating-stars .empty {
            color: #e5e7eb;
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

        .comment-text {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            font-style: italic;
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
                        <h1 class="page-title h3 mb-0">Review Management</h1>
                        <p class="page-subtitle">Moderate and manage user reviews and ratings</p>
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
                            <a class="nav-link active" href="admin-reviews.php">
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
                            <div class="stat-value"><?php echo $stats['total_reviews']; ?></div>
                            <div class="stat-label">Total Reviews</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['pending_reviews']; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['approved_reviews']; ?></div>
                            <div class="stat-label">Approved</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo number_format($stats['average_rating'], 1); ?></div>
                            <div class="stat-label">Avg Rating</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['five_star_reviews']; ?></div>
                            <div class="stat-label">5 Star Reviews</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['rejected_reviews']; ?></div>
                            <div class="stat-label">Rejected</div>
                        </div>
                    </div>

                    <!-- Search and Filter Section -->
                    <div class="search-section">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search Reviews</label>
                                <input type="text" class="form-control" id="search" placeholder="Search by name, email..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="rating" class="form-label">Rating</label>
                                <select class="form-select" id="rating">
                                    <option value="">All Ratings</option>
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $rating_filter == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> Stars
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
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

                    <!-- Reviews List -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Reviews (<?php echo $total_reviews; ?>)</h5>
                            <div class="text-muted small">
                                Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_reviews); ?> of <?php echo $total_reviews; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($reviews->num_rows > 0): ?>
                                <?php while ($review = $reviews->fetch_assoc()): ?>
                                    <div class="review-card">
                                        <div class="row align-items-start">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="mb-0 me-3">Review #<?php echo $review['id']; ?></h6>
                                                    <span class="status-badge status-<?php echo $review['status']; ?>">
                                                        <?php echo ucfirst($review['status']); ?>
                                                    </span>
                                                    <?php if ($review['is_anonymous']): ?>
                                                        <span class="badge bg-secondary ms-2">Anonymous</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="rating-stars mb-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= $review['rating']): ?>
                                                            <i class="fas fa-star"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-star empty"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                    <span class="ms-2 text-muted">(<?php echo $review['rating']; ?>/5)</span>
                                                </div>
                                                
                                                <div class="row small text-muted mb-2">
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <i class="fas fa-user text-primary me-2"></i>
                                                            <strong>Reviewer:</strong> <?php echo htmlspecialchars($review['user_name']); ?>
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-envelope text-primary me-2"></i>
                                                            <strong>Email:</strong> <?php echo htmlspecialchars($review['user_email']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-1">
                                                            <i class="fas fa-hard-hat text-primary me-2"></i>
                                                            <strong>Worker:</strong> <?php echo htmlspecialchars($review['worker_name']); ?>
                                                        </div>
                                                        <div class="mb-1">
                                                            <i class="fas fa-clock text-primary me-2"></i>
                                                            <strong>Reviewed:</strong> <?php echo format_date($review['created_at']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($review['comment'])): ?>
                                                    <div class="comment-text">
                                                        <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($review['admin_notes']) && !empty($review['admin_notes'])): ?>
                                                    <div class="mt-2 p-2 bg-warning bg-opacity-10 rounded">
                                                        <small class="text-muted">
                                                            <strong>Admin Notes:</strong> <?php echo htmlspecialchars($review['admin_notes']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-grid gap-2">
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="updateReviewStatus(<?php echo $review['id']; ?>)">
                                                        <i class="fas fa-sync me-1"></i>Update Status
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteReview(<?php echo $review['id']; ?>)">
                                                        <i class="fas fa-trash me-1"></i>Delete Review
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No reviews found matching your criteria.</p>
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
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&rating=<?php echo urlencode($rating_filter); ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&rating=<?php echo urlencode($rating_filter); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&rating=<?php echo urlencode($rating_filter); ?>">
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

    <!-- Update Review Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sync me-2"></i>Update Review Status
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="statusForm">
                    <input type="hidden" name="action" value="update_review_status">
                    <input type="hidden" name="review_id" id="status_review_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status *</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
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
            const rating = document.getElementById('rating').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (rating) params.append('rating', rating);
            
            window.location.href = '?' + params.toString();
        }

        // Show pending reviews only
        function showPendingOnly() {
            window.location.href = '?status=pending';
        }

        // Update review status
        function updateReviewStatus(reviewId) {
            document.getElementById('status_review_id').value = reviewId;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }

        // Delete review
        function deleteReview(reviewId) {
            showCustomConfirm('Are you sure you want to delete this review? This action cannot be undone.', () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_review">
                    <input type="hidden" name="review_id" value="${reviewId}">
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
