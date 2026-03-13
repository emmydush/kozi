<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Only workers should access this page
if ($user_role !== 'worker') {
    redirect('dashboard.php');
}

// Check if worker profile already exists
$check_sql = "SELECT id FROM workers WHERE user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    // Profile already exists, redirect to profile view/edit
    redirect('profile.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $type = sanitize_input($_POST['type']);
    $experience_years = intval($_POST['experience_years']);
    $hourly_rate = floatval($_POST['hourly_rate']);
    $location = sanitize_input($_POST['location']);
    $availability = sanitize_input($_POST['availability']);
    $skills = sanitize_input($_POST['skills']);
    $education = sanitize_input($_POST['education']);
    $languages = sanitize_input($_POST['languages']);
    $certifications = sanitize_input($_POST['certifications']);
    
    // Validate required fields
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($description)) $errors[] = 'Description is required';
    if (empty($type)) $errors[] = 'Service type is required';
    if (empty($hourly_rate) || $hourly_rate <= 0) $errors[] = 'Valid hourly rate is required';
    if (empty($location)) $errors[] = 'Location is required';
    
    if (empty($errors)) {
        // Insert worker profile
        $sql = "INSERT INTO workers (user_id, name, description, type, experience_years, hourly_rate, location, availability, skills, education, languages, certifications, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issidsssssss', $user_id, $name, $description, $type, $experience_years, $hourly_rate, $location, $availability, $skills, $education, $languages, $certifications);
        
        if ($stmt->execute()) {
            $success_message = "Worker profile created successfully! You can now find jobs.";
            // Redirect to dashboard after 3 seconds
            header('refresh:3;url=dashboard.php');
        } else {
            $error_message = "Failed to create profile. Please try again.";
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
    <title>Create Worker Profile - Household Connect</title>
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
            <a class="nav-link active" href="create-worker-profile.php">
                <i class="fas fa-user-plus"></i> Create Profile
            </a>
            <a class="nav-link" href="jobs.php">
                <i class="fas fa-search"></i> Find Jobs
            </a>
            <a class="nav-link" href="my-applications.php">
                <i class="fas fa-file-alt"></i> My Applications
            </a>
            <a class="nav-link" href="earnings.php">
                <i class="fas fa-money-bill-wave"></i> Earnings
            </a>
            
            <a class="nav-link" href="messages.php">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user-cog"></i> Profile Settings
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
                <h2>Create Your Worker Profile</h2>
                <p class="text-muted">Tell employers about your skills and experience</p>
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
                <h5><i class="fas fa-user me-2"></i>Basic Information</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo isset($name) ? htmlspecialchars($name) : htmlspecialchars($user_name); ?>" 
                               placeholder="Enter your full name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">Primary Service Type *</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select your primary service</option>
                            <option value="cleaning" <?php echo (isset($type) && $type === 'cleaning') ? 'selected' : ''; ?>>House Cleaning</option>
                            <option value="cooking" <?php echo (isset($type) && $type === 'cooking') ? 'selected' : ''; ?>>Cooking</option>
                            <option value="childcare" <?php echo (isset($type) && $type === 'childcare') ? 'selected' : ''; ?>>Childcare</option>
                            <option value="eldercare" <?php echo (isset($type) && $type === 'eldercare') ? 'selected' : ''; ?>>Elderly Care</option>
                            <option value="gardening" <?php echo (isset($type) && $type === 'gardening') ? 'selected' : ''; ?>>Gardening</option>
                            <option value="other" <?php echo (isset($type) && $type === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Professional Description *</label>
                    <textarea class="form-control" id="description" name="description" rows="4" 
                              placeholder="Describe your experience, skills, and what makes you a great worker" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>
            </div>

            <!-- Experience & Pricing -->
            <div class="form-section">
                <h5><i class="fas fa-briefcase me-2"></i>Experience & Pricing</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="experience_years" class="form-label">Years of Experience</label>
                        <input type="number" class="form-control" id="experience_years" name="experience_years" 
                               value="<?php echo isset($experience_years) ? htmlspecialchars($experience_years) : ''; ?>" 
                               placeholder="e.g., 3" min="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="hourly_rate" class="form-label">Hourly Rate (RWF) *</label>
                        <input type="number" class="form-control" id="hourly_rate" name="hourly_rate" 
                               value="<?php echo isset($hourly_rate) ? htmlspecialchars($hourly_rate) : ''; ?>" 
                               placeholder="e.g., 2500" min="0" step="100" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="location" class="form-label">Service Area *</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" 
                               placeholder="e.g., Kigali, Kicukiro" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="availability" class="form-label">Availability</label>
                    <textarea class="form-control" id="availability" name="availability" rows="2" 
                              placeholder="e.g., Monday-Friday, 8AM-6PM, Weekends available"><?php echo isset($availability) ? htmlspecialchars($availability) : ''; ?></textarea>
                </div>
            </div>

            <!-- Skills & Qualifications -->
            <div class="form-section">
                <h5><i class="fas fa-graduation-cap me-2"></i>Skills & Qualifications</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="skills" class="form-label">Skills</label>
                        <textarea class="form-control" id="skills" name="skills" rows="3" 
                                  placeholder="List your key skills (e.g., House Cleaning, Laundry, Cooking, Childcare)"><?php echo isset($skills) ? htmlspecialchars($skills) : ''; ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="languages" class="form-label">Languages</label>
                        <input type="text" class="form-control" id="languages" name="languages" 
                               value="<?php echo isset($languages) ? htmlspecialchars($languages) : ''; ?>" 
                               placeholder="e.g., Kinyarwanda, English, French">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="education" class="form-label">Education</label>
                        <textarea class="form-control" id="education" name="education" rows="2" 
                                  placeholder="e.g., Secondary School Certificate, Vocational Training"><?php echo isset($education) ? htmlspecialchars($education) : ''; ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="certifications" class="form-label">Certifications</label>
                        <textarea class="form-control" id="certifications" name="certifications" rows="2" 
                                  placeholder="e.g., First Aid Certificate, Housekeeping Certification"><?php echo isset($certifications) ? htmlspecialchars($certifications) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Profile
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
