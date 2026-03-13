<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Only employers should access this page
if ($user_role !== 'employer') {
    redirect('dashboard.php');
}

// Handle search and filters
$search_query = sanitize_input($_GET['search'] ?? '');
$service_type = sanitize_input($_GET['service_type'] ?? '');
$location = sanitize_input($_GET['location'] ?? '');
$min_rating = intval($_GET['rating'] ?? 0);
$min_experience = intval($_GET['experience'] ?? 0);
$sort_by = sanitize_input($_GET['sort'] ?? 'newest');

// Build base query
$where_conditions = ["u.role = 'worker'"];
$params = [];
$param_types = "";

// Add search conditions
if (!empty($search_query)) {
    $where_conditions[] = "(u.name LIKE ? OR w.description LIKE ? OR w.skills LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "sss";
}

if (!empty($service_type)) {
    $where_conditions[] = "w.type = ?";
    $params[] = $service_type;
    $param_types .= "s";
}

if (!empty($location)) {
    $where_conditions[] = "(u.location LIKE ? OR w.availability LIKE ?)";
    $location_term = "%$location%";
    $params[] = $location_term;
    $params[] = $location_term;
    $param_types .= "ss";
}

if ($min_experience > 0) {
    $where_conditions[] = "w.experience_years >= ?";
    $params[] = $min_experience;
    $param_types .= "i";
}

// Add sorting
$order_by = "ORDER BY ";
switch ($sort_by) {
    case 'rating':
        $order_by = "ORDER BY COALESCE(avg_rating, 0) DESC, u.created_at DESC";
        break;
    case 'experience':
        $order_by = "ORDER BY w.experience_years DESC, u.created_at DESC";
        break;
    case 'name':
        $order_by = "ORDER BY u.name ASC";
        break;
    case 'newest':
    default:
        $order_by = "ORDER BY u.created_at DESC";
        break;
}

// Fetch workers with filters
$workers = [];
$sql = "SELECT u.*, w.profile_image, w.national_id, w.national_id_photo, w.skills, 
               w.experience_years, w.education, w.languages, w.certifications, 
               w.description as worker_description, w.type as worker_type, 
               w.hourly_rate, w.availability, w.status as worker_status,
               COALESCE(avg_rating, 0) as avg_rating,
               total_reviews
        FROM users u
        LEFT JOIN workers w ON u.id = w.user_id
        LEFT JOIN (
            SELECT r.reviewee_id, 
                   AVG(rating) as avg_rating, 
                   COUNT(*) as total_reviews
            FROM reviews 
            GROUP BY reviewee_id
        ) r ON u.id = r.reviewee_id
        WHERE " . implode(' AND ', $where_conditions) . "
        AND (w.status IS NULL OR w.status = 'active')
        $order_by";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $workers[] = $row;
}

$stmt->close();

// Calculate statistics
$total_workers = count($workers);
$active_workers = count(array_filter($workers, fn($w) => $w['worker_status'] === 'active'));

// Get service types for filter
$service_types = ['cleaning', 'cooking', 'childcare', 'eldercare', 'gardening', 'other'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Workers - KOZI CONNECT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--light-bg);
            color: var(--dark-color);
            line-height: 1.6;
        }

        /* Modern Header */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
            color: white;
            padding: 4rem 0 3rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        /* Search Section */
        .search-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: -3rem;
            position: relative;
            z-index: 10;
        }

        .search-input {
            border: 2px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem 1.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Filter Pills */
        .filter-pills {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .filter-pill {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            border: 2px solid var(--border-color);
            background: white;
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-pill:hover,
        .filter-pill.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        /* Worker Cards */
        .worker-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .worker-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
            border-color: var(--primary-color);
        }

        .worker-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
        }

        .worker-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--success-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .worker-info {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .worker-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .worker-type {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stars {
            color: var(--warning-color);
        }

        .worker-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .meta-badge {
            background: var(--light-bg);
            color: var(--secondary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .worker-description {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .worker-actions {
            margin-top: auto;
            display: flex;
            gap: 0.75rem;
        }

        .btn-primary-custom {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary-custom:hover {
            background: #1e40af;
            transform: translateY(-2px);
            color: white;
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-outline-custom:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        /* Stats Section */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 3rem 0;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--border-color);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--secondary-color);
            font-weight: 500;
        }

        /* Sidebar Filters */
        .filter-sidebar {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--border-color);
            position: sticky;
            top: 2rem;
        }

        .filter-group {
            margin-bottom: 1.5rem;
        }

        .filter-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.75rem;
            display: block;
        }

        .form-control-custom {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-section {
                padding: 3rem 0 2rem;
            }
            
            .search-section {
                margin-top: -2rem;
                padding: 1.5rem;
            }
            
            .worker-actions {
                flex-direction: column;
            }
            
            .btn-primary-custom,
            .btn-outline-custom {
                width: 100%;
                justify-content: center;
            }
        }

        /* Loading Animation */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="display-4 fw-bold mb-3">Find Trusted Household Workers</h1>
                        <p class="lead mb-4">Connect with verified and experienced household workers in Kigali through KOZI CONNECT. Browse profiles, read reviews, and find the perfect match for your needs.</p>
                        
                        <!-- Search Bar -->
                        <div class="search-section">
                            <form method="GET" class="mb-3">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <input type="text" name="search" class="form-control search-input" 
                                               placeholder="Search by name, skills, or description..." 
                                               value="<?php echo htmlspecialchars($search_query); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary-custom w-100">
                                            <i class="fas fa-search"></i> Search Workers
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Hidden fields for filters -->
                                <input type="hidden" name="service_type" value="<?php echo htmlspecialchars($service_type); ?>">
                                <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                                <input type="hidden" name="rating" value="<?php echo $min_rating; ?>">
                                <input type="hidden" name="experience" value="<?php echo $min_experience; ?>">
                                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                            </form>
                            
                            <!-- Quick Filter Pills -->
                            <div class="filter-pills">
                                <a href="find-workers.php" class="filter-pill <?php echo empty($service_type) && empty($location) && empty($search_query) ? 'active' : ''; ?>">
                                    <i class="fas fa-th"></i> All Workers
                                </a>
                                <?php foreach ($service_types as $type): ?>
                                    <a href="?service_type=<?php echo $type; ?>" class="filter-pill <?php echo $service_type === $type ? 'active' : ''; ?>">
                                        <?php
                                        $icons = [
                                            'cleaning' => 'fa-broom',
                                            'cooking' => 'fa-utensils',
                                            'childcare' => 'fa-child',
                                            'eldercare' => 'fa-user-nurse',
                                            'gardening' => 'fa-seedling',
                                            'other' => 'fa-briefcase'
                                        ];
                                        ?>
                                        <i class="fas <?php echo $icons[$type]; ?>"></i> 
                                        <?php echo ucfirst($type); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="text-center">
                            <i class="fas fa-users fa-4x mb-3 opacity-75"></i>
                            <div class="h5">Verified Workers</div>
                            <div class="h3 fw-bold"><?php echo number_format($active_workers); ?>+ Available</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <h5 class="mb-4">
                        <i class="fas fa-filter me-2"></i>Filters
                    </h5>
                    
                    <form method="GET">
                        <!-- Service Type -->
                        <div class="filter-group">
                            <label class="filter-label">Service Type</label>
                            <select name="service_type" class="form-control form-control-custom">
                                <option value="">All Services</option>
                                <?php foreach ($service_types as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo $service_type === $type ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Location -->
                        <div class="filter-group">
                            <label class="filter-label">Location</label>
                            <input type="text" name="location" class="form-control form-control-custom" 
                                   placeholder="Enter location..." value="<?php echo htmlspecialchars($location); ?>">
                        </div>

                        <!-- Rating -->
                        <div class="filter-group">
                            <label class="filter-label">Minimum Rating</label>
                            <select name="rating" class="form-control form-control-custom">
                                <option value="">Any Rating</option>
                                <option value="5" <?php echo $min_rating === 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ 5 stars</option>
                                <option value="4" <?php echo $min_rating === 4 ? 'selected' : ''; ?>>⭐⭐⭐⭐ 4+ stars</option>
                                <option value="3" <?php echo $min_rating === 3 ? 'selected' : ''; ?>>⭐⭐⭐ 3+ stars</option>
                            </select>
                        </div>

                        <!-- Experience -->
                        <div class="filter-group">
                            <label class="filter-label">Experience</label>
                            <select name="experience" class="form-control form-control-custom">
                                <option value="">Any Experience</option>
                                <option value="1" <?php echo $min_experience === 1 ? 'selected' : ''; ?>>1+ year</option>
                                <option value="3" <?php echo $min_experience === 3 ? 'selected' : ''; ?>>3+ years</option>
                                <option value="5" <?php echo $min_experience === 5 ? 'selected' : ''; ?>>5+ years</option>
                            </select>
                        </div>

                        <!-- Sort By -->
                        <div class="filter-group">
                            <label class="filter-label">Sort By</label>
                            <select name="sort" class="form-control form-control-custom">
                                <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>🆕 Newest First</option>
                                <option value="rating" <?php echo $sort_by === 'rating' ? 'selected' : ''; ?>>⭐ Top Rated</option>
                                <option value="experience" <?php echo $sort_by === 'experience' ? 'selected' : ''; ?>>📊 Most Experienced</option>
                                <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>🔤 Name A-Z</option>
                            </select>
                        </div>

                        <!-- Hidden search field -->
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-search me-2"></i>Apply Filters
                            </button>
                            <a href="find-workers.php" class="btn btn-outline-custom d-block text-center">
                                <i class="fas fa-times me-2"></i>Clear All
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Workers Grid -->
            <div class="col-lg-9">
                <!-- Results Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Available Workers</h2>
                        <p class="text-muted mb-0">
                            <?php if ($total_workers > 0): ?>
                                Showing <?php echo number_format($total_workers); ?> worker<?php echo $total_workers !== 1 ? 's' : ''; ?>
                                <?php if (!empty($search_query) || !empty($service_type) || !empty($location)): ?>
                                    matching your criteria
                                <?php endif; ?>
                            <?php else: ?>
                                No workers found
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-custom btn-sm" onclick="toggleView()">
                            <i class="fas fa-th-list"></i> List View
                        </button>
                    </div>
                </div>

                <!-- Workers Container -->
                <div id="workers-container" class="row g-4">
                    <?php if (empty($workers)): ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h3>No Workers Found</h3>
                                <p class="text-muted">Try adjusting your filters or search terms to find more workers.</p>
                                <a href="find-workers.php" class="btn btn-primary-custom">
                                    <i class="fas fa-redo me-2"></i>Clear Filters
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($workers as $worker): ?>
                            <div class="col-lg-4 col-md-6">
                                <div class="worker-card">
                                    <div class="position-relative">
                                        <?php 
                                        $profile_image = '';
                                        if (!empty($worker['profile_image']) && file_exists('uploads/profiles/' . $worker['profile_image'])) {
                                            $profile_image = 'uploads/profiles/' . htmlspecialchars($worker['profile_image']);
                                        }
                                        ?>
                                        <div class="worker-image">
                                            <?php if ($profile_image): ?>
                                                <img src="<?php echo $profile_image; ?>" alt="<?php echo htmlspecialchars($worker['name']); ?>" 
                                                     style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($worker['name'], 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="worker-badge">Available</div>
                                    </div>
                                    
                                    <div class="worker-info">
                                        <h3 class="worker-name"><?php echo htmlspecialchars($worker['name']); ?></h3>
                                        <div class="worker-type"><?php echo ucfirst(htmlspecialchars($worker['worker_type'] ?? 'General Worker')); ?></div>
                                        
                                        <div class="rating">
                                            <div class="stars">
                                                <?php
                                                $rating = round($worker['avg_rating']);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $rating ? '⭐' : '☆';
                                                }
                                                ?>
                                            </div>
                                            <span class="text-muted">(<?php echo number_format($worker['avg_rating'], 1); ?>)</span>
                                            <?php if ($worker['total_reviews'] > 0): ?>
                                                <span class="text-muted">(<?php echo $worker['total_reviews']; ?> reviews)</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="worker-meta">
                                            <?php if (!empty($worker['experience_years'])): ?>
                                                <span class="meta-badge">
                                                    <i class="fas fa-clock"></i> <?php echo $worker['experience_years']; ?>+ years
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($worker['hourly_rate'])): ?>
                                                <span class="meta-badge">
                                                    <i class="fas fa-money-bill"></i> RWF <?php echo number_format($worker['hourly_rate']); ?>/hr
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($worker['location'])): ?>
                                                <span class="meta-badge">
                                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($worker['location']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($worker['skills'])): ?>
                                            <div class="mb-3">
                                                <?php 
                                                $skills = explode(',', $worker['skills']);
                                                foreach (array_slice($skills, 0, 3) as $skill): 
                                                ?>
                                                    <span class="meta-badge"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($worker['worker_description'])): ?>
                                            <p class="worker-description">
                                                <?php echo htmlspecialchars(substr($worker['worker_description'], 0, 120)) . (strlen($worker['worker_description']) > 120 ? '...' : ''); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="worker-actions">
                                            <a href="worker-details.php?id=<?php echo $worker['id']; ?>" class="btn btn-primary-custom">
                                                <i class="fas fa-eye"></i> View Profile
                                            </a>
                                            <a href="messages.php?worker_id=<?php echo $worker['id']; ?>" class="btn btn-outline-custom">
                                                <i class="fas fa-envelope"></i> Message
                                            </a>
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

    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2024 KOZI CONNECT. Connecting Kigali families with trusted workers.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('show');
            }
        }
        
        function toggleView() {
            // Toggle between grid and list view
            const container = document.getElementById('workers-container');
            if (container.classList.contains('row')) {
                container.classList.remove('row', 'g-4');
                container.classList.add('list-view');
            } else {
                container.classList.add('row', 'g-4');
                container.classList.remove('list-view');
            }
        }
        
        // Auto-submit form when filter pills are clicked
        document.querySelectorAll('.filter-pill').forEach(pill => {
            pill.addEventListener('click', function(e) {
                if (!this.classList.contains('active')) {
                    window.location.href = this.href;
                }
            });
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('mobile-menu-toggle');
            
            if (window.innerWidth < 992 && sidebar && toggle && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    </script>
</body>
</html>
