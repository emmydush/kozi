<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('index.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Fetch all workers from database
$workers = [];
$sql = "SELECT u.*, u.profile_image as user_profile_image, w.profile_image, w.skills, 
               w.experience_years, w.education, w.languages, w.certifications, 
               w.description as worker_description, w.type as worker_type, 
               w.hourly_rate, w.availability, w.status as worker_status
        FROM users u
        LEFT JOIN workers w ON u.id = w.user_id
        WHERE u.role = 'worker' 
        AND (w.status IS NULL OR w.status = 'active')
        ORDER BY w.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_workers = count($workers);
$active_workers = count(array_filter($workers, fn($w) => $w['worker_status'] === 'active'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('common.find_workers'); ?> - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            margin-right: 12px;
            width: 20px;
            font-size: 1rem;
        }
        
        .main-content {
            margin-left: 0;
            padding: 15px;
            min-height: calc(100vh - 60px);
            margin-top: 60px;
            background: #f8f9fa;
        }
        
        /* Update main content background for consistency */
        body {
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
        
        /* Mobile-first responsive design */
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

        /* Modern Worker Cards */
        .modern-worker-card {
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            background: white;
        }

        .modern-worker-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: #007bff;
        }

        .modern-worker-card .rounded-circle {
            transition: transform 0.3s ease;
        }

        .modern-worker-card:hover .rounded-circle {
            transform: scale(1.05);
        }

        .modern-worker-card .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .modern-worker-card .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-1px);
        }

        .modern-worker-card .card-title {
            color: #212529;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .modern-worker-card .text-muted {
            color: #6c757d !important;
            font-size: 0.9rem;
        }

        .modern-worker-card .card-text {
            color: #6c757d;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        /* Enhanced Worker Cards */
        .worker-card {
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            overflow: hidden;
        }

        .worker-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: #000000;
        }

        .worker-card .card-img-top {
            transition: transform 0.3s ease;
        }

        .worker-card:hover .card-img-top {
            transform: scale(1.05);
        }

        .worker-card .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 0.9rem;
        }

        .skills-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        /* Page Header Gradient */
        .page-header {
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .page-header .container {
            position: relative;
            z-index: 1;
        }

        /* Filter Card */
        .sticky-top {
            z-index: 100;
        }

        /* View Toggle Buttons */
        .btn-group .btn {
            border-color: #dee2e6;
        }

        .btn-group .btn.active {
            background-color: #000000;
            border-color: #000000;
        }

        /* Loading State */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .page-header {
                padding: 2rem 0;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .modern-worker-card {
                margin-bottom: 1rem;
            }
        }

        @media (min-width: 992px) {
            .modern-worker-card {
                margin-bottom: 1.5rem;
            }
        }

        /* List View Styles */
        .list-view {
            display: flex !important;
            flex-direction: column;
            gap: 1rem;
        }

        .list-view .worker-card {
            display: flex;
            flex-direction: row;
            max-width: 100%;
            height: auto;
        }

        .list-view .worker-card .card-img-top {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 0.375rem 0 0 0.375rem;
        }

        .list-view .worker-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        @media (max-width: 768px) {
            .list-view .worker-card {
                flex-direction: column;
            }
            
            .list-view .worker-card .card-img-top {
                width: 100%;
                height: 200px;
            }
        }

        /* Smartphone layout for worker cards */
        @media (max-width: 576px) {
            .row .col-6 {
                padding-left: 5px !important;
                padding-right: 5px !important;
            }
            
            .row .col-6 .worker-card {
                margin-bottom: 10px;
            }
            
            .row .col-6 .card.worker-card {
                margin-bottom: 10px;
            }
            
            .row .col-6 .worker-card .card-img-top {
                height: 150px;
            }
            
            .row .col-6 .worker-card .card-title {
                font-size: 1rem;
            }
            
            .row .col-6 .worker-card .text-muted {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <nav class="nav flex-column p-3">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            
            <?php if ($user_role === 'employer'): ?>
            <a class="nav-link" href="post-job.php">
                <i class="fas fa-plus-circle"></i> <?php echo t('common.post_job'); ?>
            </a>
            <a class="nav-link active" href="workers.php">
                <i class="fas fa-users"></i> <?php echo t('common.find_workers'); ?>
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> <?php echo t('common.my_jobs'); ?>
            </a>
            <a class="nav-link" href="bookings.php">
                <i class="fas fa-calendar-check"></i> <?php echo t('common.bookings'); ?>
            </a>
            <?php else: ?>
            <a class="nav-link" href="jobs.php">
                <i class="fas fa-search"></i> Find Jobs
            </a>
            <a class="nav-link" href="my-applications.php">
                <i class="fas fa-file-alt"></i> My Applications
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> Active Jobs
            </a>
            <a class="nav-link" href="earnings.php">
                <i class="fas fa-money-bill-wave"></i> Earnings
            </a>
            <?php endif; ?>
            
            <a class="nav-link" href="messages.php">
                <i class="fas fa-envelope"></i> <?php echo t('common.messages'); ?>
            </a>
            
            <hr class="text-white-50">
            
            <a class="nav-link" href="help.php">
                <i class="fas fa-question-circle"></i> Help & Support
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <header class="page-header bg-gradient text-white py-5 mb-4">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h1 class="display-4 fw-bold mb-3">Find Trusted Household Workers</h1>
                            <p class="lead">Discover skilled and reliable household workers in Kigali. Filter by service type, location, and ratings to find the perfect match for your needs.</p>
                        </div>
                        <div class="col-lg-4">
                            <div class="text-center">
                                <i class="fas fa-users fa-4x mb-3 opacity-75"></i>
                                <div class="h5">Verified Workers</div>
                                <div class="h3 fw-bold"><?php echo $active_workers; ?>+ Available</div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Filters and Content -->
            <div class="container">
                <div class="row">
                    <!-- Filters Sidebar -->
                    <div class="col-lg-3 mb-4">
                        <div class="card shadow-sm border-0 sticky-top" style="top: 80px;">
                            <div class="card-header bg-white border-bottom">
                                <h5 class="mb-0">
                                    <i class="fas fa-filter me-2"></i>Filters
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="filter-form">
                                    <div class="mb-3">
                                        <label for="service-type" class="form-label fw-semibold">Service Type</label>
                                        <select class="form-select" id="service-type">
                                            <option value="">All Services</option>
                                            <option value="cleaning">🧹 Cleaning</option>
                                            <option value="cooking">👨‍🍳 Cooking</option>
                                            <option value="childcare">👶 Childcare</option>
                                            <option value="eldercare">👴 Eldercare</option>
                                            <option value="gardening">🌿 Gardening</option>
                                            <option value="other">📦 Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="location" class="form-label fw-semibold">Location</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </span>
                                            <input type="text" class="form-control" id="location" placeholder="Enter location">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="rating" class="form-label fw-semibold">Minimum Rating</label>
                                        <select class="form-select" id="rating">
                                            <option value="">Any Rating</option>
                                            <option value="5">⭐⭐⭐⭐⭐ 5 stars</option>
                                            <option value="4">⭐⭐⭐⭐ 4 stars & above</option>
                                            <option value="3">⭐⭐⭐ 3 stars & above</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="experience" class="form-label fw-semibold">Experience</label>
                                        <select class="form-select" id="experience">
                                            <option value="">Any Experience</option>
                                            <option value="1">1+ year</option>
                                            <option value="3">3+ years</option>
                                            <option value="5">5+ years</option>
                                        </select>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-2"></i>Apply Filters
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                            <i class="fas fa-times me-2"></i>Clear
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Workers Content -->
                    <div class="col-lg-9">
                        <!-- Header with sorting -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="mb-0">Available Workers</h2>
                                <small class="text-muted" id="results-count">Showing all workers</small>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-secondary btn-sm active" data-view="grid">
                                        <i class="fas fa-th"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-view="list">
                                        <i class="fas fa-list"></i>
                                    </button>
                                </div>
                                <select class="form-select form-select-sm" id="sort-by" style="width: auto;">
                                    <option value="relevance">🎯 Relevance</option>
                                    <option value="rating">⭐ Top Rated</option>
                                    <option value="experience">📊 Most Experienced</option>
                                    <option value="newest">🆕 Newest</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Workers Container -->
                        <div id="workers-container" class="row g-4">
                            <?php if (empty($workers)): ?>
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h4 class="text-muted">No Workers Available</h4>
                                        <p class="text-muted">There are currently no workers available. Please check back later.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($workers as $worker): ?>
                                    <div class="col-lg-4 col-md-6 col-sm-6 col-6">
                                        <div class="card worker-card h-100">
                                            <div class="position-relative">
                                                <?php 
                                                $profile_image = '';
                                                // Check user profile image first, then worker profile image
                                                $image_to_check = '';
                                                if (!empty($worker['user_profile_image'])) {
                                                    $image_to_check = $worker['user_profile_image'];
                                                } elseif (!empty($worker['profile_image'])) {
                                                    $image_to_check = $worker['profile_image'];
                                                }
                                                
                                                if (!empty($image_to_check) && file_exists($image_to_check)) {
                                                    $profile_image = htmlspecialchars($image_to_check);
                                                }
                                                ?>
                                                <img src="<?php echo $profile_image ?: 'https://picsum.photos/seed/worker-' . $worker['id'] . '/400/300.jpg'; ?>" 
                                                     class="card-img-top" 
                                                     alt="<?php echo htmlspecialchars($worker['name']); ?>"
                                                     style="height: 200px; object-fit: cover;">
                                                <div class="position-absolute top-0 end-0 m-2">
                                                    <span class="badge bg-success">Available</span>
                                                </div>
                                            </div>
                                            <div class="card-body d-flex flex-column">
                                                <div class="mb-2">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($worker['name']); ?></h5>
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="rating-stars me-2">
                                                            <?php
                                                            // Generate random rating for demo (in real app, this would come from reviews table)
                                                            $rating = rand(4, 5);
                                                            for ($i = 1; $i <= 5; $i++) {
                                                                echo $i <= $rating ? '⭐' : '☆';
                                                            }
                                                            ?>
                                                        </div>
                                                        <small class="text-muted">(<?php echo $rating; ?>.0)</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <span class="badge bg-primary me-1"><?php echo ucfirst(htmlspecialchars($worker['worker_type'] ?? 'General')); ?></span>
                                                    <?php if (!empty($worker['experience_years'])): ?>
                                                        <span class="badge bg-info me-1"><?php echo $worker['experience_years']; ?>+ years</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($worker['hourly_rate'])): ?>
                                                        <span class="badge bg-success">RWF <?php echo number_format($worker['hourly_rate']); ?>/hr</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if (!empty($worker['skills'])): ?>
                                                    <div class="mb-2">
                                                        <?php 
                                                        $skills = explode(',', $worker['skills']);
                                                        foreach (array_slice($skills, 0, 3) as $skill): 
                                                        ?>
                                                            <span class="skills-badge me-1"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($worker['worker_description'])): ?>
                                                    <p class="card-text text-muted small">
                                                        <?php echo htmlspecialchars(substr($worker['worker_description'], 0, 100)) . (strlen($worker['worker_description']) > 100 ? '...' : ''); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($worker['location'])): ?>
                                                    <div class="mb-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?php echo htmlspecialchars($worker['location']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="mt-auto">
                                                    <div class="d-grid gap-2">
                                                        <button class="btn btn-primary" onclick="viewWorkerProfile(<?php echo $worker['id']; ?>)">
                                                            <i class="fas fa-eye me-2"></i>View Profile
                                                        </button>
                                                        <button class="btn btn-outline-secondary" onclick="messageWorker(<?php echo $worker['id']; ?>, '<?php echo htmlspecialchars($worker['name']); ?>')">
                                                            <i class="fas fa-envelope me-2"></i>Message
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Load More Button -->
                        <div class="d-flex justify-content-center mt-5">
                            <button id="load-more" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-plus-circle me-2"></i>Load More Workers
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2024 Household Connect. Connecting Kigali families with trusted workers.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
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

        // Clear filters function
        function clearFilters() {
            document.getElementById('service-type').value = '';
            document.getElementById('location').value = '';
            document.getElementById('rating').value = '';
            document.getElementById('experience').value = '';
            
            // Trigger form submission to reload with cleared filters
            document.getElementById('filter-form').dispatchEvent(new Event('submit'));
        }

        // View toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('[data-view]');
            const workersContainer = document.getElementById('workers-container');
            
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Update active state
                    viewButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Toggle view class
                    const view = this.dataset.view;
                    if (view === 'list') {
                        workersContainer.classList.remove('row');
                        workersContainer.classList.add('list-view');
                    } else {
                        workersContainer.classList.add('row');
                        workersContainer.classList.remove('list-view');
                    }
                });
            });
        });

        // Update results count
        function updateResultsCount(count, total) {
            const countElement = document.getElementById('results-count');
            if (countElement) {
                if (total > count) {
                    countElement.textContent = `Showing ${count} of ${total} workers`;
                } else {
                    countElement.textContent = `Showing ${count} workers`;
                }
            }
        }
        
        // View worker profile
        function viewWorkerProfile(workerId) {
            window.location.href = 'worker-details.php?id=' + workerId;
        }
        
        // Message worker
        function messageWorker(workerId, workerName) {
            if (confirm('Would you like to send a message to ' + workerName + '?')) {
                window.location.href = 'messages.php?worker_id=' + workerId;
            }
        }
        
        // Initialize results count
        document.addEventListener('DOMContentLoaded', function() {
            updateResultsCount(<?php echo count($workers); ?>, <?php echo count($workers); ?>);
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="script.js"></script>
    <script src="workers.js"></script>
</body>
</html>
