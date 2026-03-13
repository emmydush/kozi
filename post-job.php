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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $job_type = sanitize_input($_POST['job_type']);
    $salary = floatval($_POST['salary']);
    $location = sanitize_input($_POST['location']);
    $work_hours = sanitize_input($_POST['work_hours']);
    $requirements = sanitize_input($_POST['requirements']);
    $experience_required = sanitize_input($_POST['experience_required']);
    
    // Validate required fields
    $errors = [];
    if (empty($title)) $errors[] = 'Job title is required';
    if (empty($description)) $errors[] = 'Job description is required';
    if (empty($job_type)) $errors[] = 'Job type is required';
    if (empty($salary) || $salary <= 0) $errors[] = 'Valid salary is required';
    if (empty($location)) $errors[] = 'Location is required';
    if (empty($work_hours)) $errors[] = 'Work hours are required';
    
    if (empty($errors)) {
        // Insert job into database
        $sql = "INSERT INTO jobs (employer_id, title, description, job_type, salary, location, work_hours, requirements, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isssdsss', $user_id, $title, $description, $job_type, $salary, $location, $work_hours, $requirements);
        
        if ($stmt->execute()) {
            $success_message = "Job posted successfully! Workers can now apply for your position.";
            // Clear form
            $title = $description = $job_type = $salary = $location = $work_hours = $requirements = $experience_required = '';
        } else {
            $error_message = "Failed to post job. Please try again.";
        }
        $stmt->close();
    } else {
        $error_message = implode(', ', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Job - Household Connect</title>
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
        
        .form-control, .form-select {
            min-height: 44px;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }
        
        h2 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #000000;
            font-weight: 700;
        }
        
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .form-section h5 {
            color: #000000;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
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
            <a class="nav-link active" href="post-job.php">
                <i class="fas fa-plus-circle"></i> Post Job
            </a>
            <a class="nav-link" href="workers.php">
                <i class="fas fa-users"></i> Find Workers
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> My Jobs
            </a>
            <a class="nav-link" href="bookings.php">
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
                <h2>Post a New Job</h2>
                <p class="text-muted">Find the perfect worker for your household needs</p>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Basic Information -->
            <div class="form-section">
                <h5><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Job Title *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" 
                               placeholder="e.g., House Cleaner, Childcare Provider" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="job_type" class="form-label">Job Type *</label>
                        <select class="form-select" id="job_type" name="job_type" required>
                            <option value="">Select job type</option>
                            <option value="cleaning" <?php echo (isset($job_type) && $job_type === 'cleaning') ? 'selected' : ''; ?>>House Cleaning</option>
                            <option value="cooking" <?php echo (isset($job_type) && $job_type === 'cooking') ? 'selected' : ''; ?>>Cooking</option>
                            <option value="childcare" <?php echo (isset($job_type) && $job_type === 'childcare') ? 'selected' : ''; ?>>Childcare</option>
                            <option value="eldercare" <?php echo (isset($job_type) && $job_type === 'eldercare') ? 'selected' : ''; ?>>Elderly Care</option>
                            <option value="gardening" <?php echo (isset($job_type) && $job_type === 'gardening') ? 'selected' : ''; ?>>Gardening</option>
                            <option value="driving" <?php echo (isset($job_type) && $job_type === 'driving') ? 'selected' : ''; ?>>Driving</option>
                            <option value="other" <?php echo (isset($job_type) && $job_type === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Job Description *</label>
                    <textarea class="form-control" id="description" name="description" rows="4" 
                              placeholder="Describe the job responsibilities, tasks, and what you're looking for in a worker" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>
            </div>

            <!-- Job Details -->
            <div class="form-section">
                <h5><i class="fas fa-briefcase me-2"></i>Job Details</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="salary" class="form-label">Monthly Salary (RWF) *</label>
                        <input type="number" class="form-control" id="salary" name="salary" 
                               value="<?php echo isset($salary) ? htmlspecialchars($salary) : ''; ?>" 
                               placeholder="e.g., 50000" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" 
                               placeholder="e.g., Kigali, Kicukiro" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="work_hours" class="form-label">Work Hours *</label>
                        <input type="text" class="form-control" id="work_hours" name="work_hours" 
                               value="<?php echo isset($work_hours) ? htmlspecialchars($work_hours) : ''; ?>" 
                               placeholder="e.g., 8 hours/day, Monday-Friday" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="requirements" class="form-label">Requirements</label>
                        <textarea class="form-control" id="requirements" name="requirements" rows="3" 
                                  placeholder="List any specific requirements or qualifications needed"><?php echo isset($requirements) ? htmlspecialchars($requirements) : ''; ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="experience_required" class="form-label">Experience Required</label>
                        <textarea class="form-control" id="experience_required" name="experience_required" rows="3" 
                                  placeholder="e.g., 2+ years of experience, references required"><?php echo isset($experience_required) ? htmlspecialchars($experience_required) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i>Post Job
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

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
    </script>
</body>
</html>
