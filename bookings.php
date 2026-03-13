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

// Only employers should access this page
if ($user_role !== 'employer') {
    redirect('dashboard.php');
}

// Fetch bookings from database
$bookings = [];
$sql = "SELECT b.*, u.name as worker_name, u.email as worker_email, u.phone as worker_phone,
               w.profile_image, w.hourly_rate, w.skills
        FROM bookings b
        JOIN users u ON b.worker_id = u.id
        LEFT JOIN workers w ON b.worker_id = w.user_id
        WHERE b.user_id = ?
        ORDER BY b.start_date ASC, b.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

// Calculate statistics
$total_bookings = count($bookings);
$active_bookings = count(array_filter($bookings, fn($b) => in_array($b['status'], ['confirmed', 'in_progress'])));
$pending_bookings = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
$completed_bookings = count(array_filter($bookings, fn($b) => $b['status'] === 'completed'));

// This month's bookings
$current_month = date('Y-m');
$this_month_bookings = count(array_filter($bookings, fn($b) => date('Y-m', strtotime($b['start_date'])) === $current_month));

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
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
            margin-right: 10px;
            width: 20px;
        }
        
        .main-content {
            margin-left: 0;
            padding: 15px;
            min-height: calc(100vh - 60px);
            margin-top: 60px;
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
        
        .booking-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            font-size: 0.8rem;
        }
        
        .calendar-container {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Standard button styles to match other pages */
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
        
        .btn-outline-success {
            border: 2px solid #198754;
            color: #198754;
            background: white;
        }
        
        .btn-outline-success:hover {
            background: #198754;
            color: white;
        }
        
        .btn-outline-warning {
            border: 2px solid #ffc107;
            color: #ffc107;
            background: white;
        }
        
        .btn-outline-warning:hover {
            background: #ffc107;
            color: white;
        }
        
        .btn-sm {
            min-height: 38px;
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        /* Standard card styles */
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: none;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
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
        
        .card.bg-primary h2,
        .card.bg-success h2 {
            font-size: 2rem;
            margin-bottom: 0;
            font-weight: 700;
            color: white !important;
        }
        
        .card.bg-primary .card-title,
        .card.bg-success .card-title {
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 600;
            opacity: 0.9;
            color: white !important;
        }
        
        /* Mobile responsive improvements */
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
            .card.bg-success h2 {
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
            .card.bg-success h2 {
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
            <a class="nav-link" href="post-job.php">
                <i class="fas fa-plus-circle"></i> Post Job
            </a>
            <a class="nav-link" href="workers.php">
                <i class="fas fa-users"></i> Find Workers
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> My Jobs
            </a>
            <a class="nav-link active" href="bookings.php">
                <i class="fas fa-calendar-check"></i> Bookings
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
                <h2>Bookings</h2>
                <p class="text-muted">Manage your worker bookings and schedules</p>
            </div>
        </div>

        <!-- Booking Statistics -->
        <div class="row mb-4">
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">Active Bookings</h5>
                        <h2><?php echo $active_bookings; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title">This Month</h5>
                        <h2><?php echo $this_month_bookings; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Calendar -->
            <div class="col-md-4 mb-4">
                <div class="calendar-container">
                    <h5 class="mb-3"><?php echo date('F Y'); ?></h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Sun</th>
                                    <th>Mon</th>
                                    <th>Tue</th>
                                    <th>Wed</th>
                                    <th>Thu</th>
                                    <th>Fri</th>
                                    <th>Sat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Generate calendar for current month
                                $current_month = date('n');
                                $current_year = date('Y');
                                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
                                $first_day_of_month = date('w', strtotime("$current_year-$current_month-01"));
                                
                                // Create array of booked dates for highlighting
                                $booked_dates = [];
                                foreach ($bookings as $booking) {
                                    if (date('n', strtotime($booking['start_date'])) == $current_month && 
                                        date('Y', strtotime($booking['start_date'])) == $current_year) {
                                        $booked_dates[] = date('j', strtotime($booking['start_date']));
                                    }
                                }
                                
                                $day_counter = 1;
                                $week_started = false;
                                
                                // Empty cells before month starts
                                for ($i = 0; $i < $first_day_of_month; $i++) {
                                    echo '<td></td>';
                                    $week_started = true;
                                }
                                
                                // Days of the month
                                for ($day = 1; $day <= $days_in_month; $day++) {
                                    if ($day > 1 && $day_counter == 1) {
                                        echo '<tr>';
                                    }
                                    
                                    $is_booked = in_array($day, $booked_dates);
                                    $cell_class = $is_booked ? 'bg-success text-white' : '';
                                    
                                    echo "<td class='$cell_class'>$day</td>";
                                    $day_counter++;
                                    
                                    if ($day_counter > 7) {
                                        echo '</tr>';
                                        $day_counter = 1;
                                    }
                                }
                                
                                // Empty cells after month ends
                                if ($day_counter > 1) {
                                    while ($day_counter <= 7) {
                                        echo '<td></td>';
                                        $day_counter++;
                                    }
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <span class="badge bg-success">Scheduled</span> - Worker booked
                        </small>
                    </div>
                </div>
            </div>

            <!-- Bookings List -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Upcoming Bookings</h5>
                        <button class="btn btn-primary btn-sm" onclick="window.location.href='post-job.php'">New Booking</button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Bookings Yet</h4>
                                <p class="text-muted">When you create bookings with workers, they will appear here.</p>
                                <a href="workers.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-search me-2"></i>Find Workers
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <div class="booking-card card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php 
                                                    $service_icons = [
                                                        'cleaning' => 'fa-broom',
                                                        'cooking' => 'fa-utensils',
                                                        'childcare' => 'fa-child',
                                                        'eldercare' => 'fa-user-nurse',
                                                        'gardening' => 'fa-seedling',
                                                        'other' => 'fa-briefcase'
                                                    ];
                                                    $icon = isset($service_icons[$booking['service_type']]) ? $service_icons[$booking['service_type']] : 'fa-briefcase';
                                                    $service_name = ucfirst(str_replace('_', ' ', $booking['service_type']));
                                                    ?>
                                                    <i class="fas <?php echo $icon; ?> me-2"></i>
                                                    <?php echo $service_name; ?> - <?php echo htmlspecialchars($booking['worker_name']); ?>
                                                </h6>
                                                <div class="text-muted small">
                                                    <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($booking['start_date'])); ?>
                                                    <?php if ($booking['end_date'] && $booking['end_date'] != $booking['start_date']): ?>
                                                        - <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                                                    <?php endif; ?>
                                                    <i class="fas fa-clock ms-2"></i> 
                                                    <?php 
                                                    $duration = $booking['end_date'] ? 
                                                        (strtotime($booking['end_date']) - strtotime($booking['start_date'])) / 86400 + 1 : 
                                                        1;
                                                    echo $duration . ' day' . ($duration > 1 ? 's' : '');
                                                    ?>
                                                    <?php if (!empty($booking['hourly_rate'])): ?>
                                                        <i class="fas fa-money-bill ms-2"></i> RWF <?php echo number_format($booking['hourly_rate']); ?>/hr
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <span class="badge bg-<?php echo $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'secondary'); ?> status-badge">
                                                <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="text-muted">
                                                    <?php if (!empty($booking['worker_email'])): ?>
                                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($booking['worker_email']); ?>
                                                    <?php endif; ?>
                                                    <?php if (!empty($booking['worker_phone'])): ?>
                                                        <i class="fas fa-phone ms-2"></i> <?php echo htmlspecialchars($booking['worker_phone']); ?>
                                                    <?php endif; ?>
                                                </span>
                                                <?php if (!empty($booking['notes'])): ?>
                                                    <div class="text-muted small mt-1">
                                                        <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars(substr($booking['notes'], 0, 100)) . (strlen($booking['notes']) > 100 ? '...' : ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <button class="btn btn-outline-secondary" onclick="messageWorker(<?php echo $booking['worker_id']; ?>, '<?php echo htmlspecialchars($booking['worker_name']); ?>')">
                                                    <i class="fas fa-envelope"></i> Message
                                                </button>
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <button class="btn btn-outline-success" onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'confirmed')">
                                                        <i class="fas fa-check"></i> Confirm
                                                    </button>
                                                <?php elseif ($booking['status'] === 'confirmed'): ?>
                                                    <button class="btn btn-outline-warning" onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'cancelled')">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // View booking details
        function viewBookingDetails(bookingId) {
            // You can implement a modal or redirect to details page
            alert('Booking details feature coming soon! Booking ID: ' + bookingId);
        }
        
        // Message worker
        function messageWorker(workerId, workerName) {
            if (confirm('Would you like to send a message to ' + workerName + '?')) {
                // Redirect to messages page with worker context
                window.location.href = 'messages.php?worker_id=' + workerId;
            }
        }
        
        // Update booking status
        function updateBookingStatus(bookingId, newStatus) {
            if (confirm('Are you sure you want to ' + newStatus + ' this booking?')) {
                // Create form data
                const formData = new FormData();
                formData.append('booking_id', bookingId);
                formData.append('status', newStatus);
                
                // Send AJAX request to update status
                fetch('api/update-booking-status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message and reload page
                        alert('Booking status updated successfully!');
                        window.location.reload();
                    } else {
                        alert('Error updating booking: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating booking. Please try again.');
                });
            }
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
    </script>
</body>
</html>
