<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('index.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get current user profile data
$user_id = $_SESSION['user_id'];

// Get basic user info from users table
$sql = "SELECT phone, profile_image FROM users WHERE id = :user_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$phone = $user_data['phone'] ?? '';
$profile_image = $user_data['profile_image'] ?? '';

// Initialize worker-specific variables
$location = '';
$bio = '';
$skills = [];
$experience = '';
$expected_salary = '';
$availability = '';

// If user is a worker, get additional data from workers table
if ($user_role === 'worker') {
    // Check if national_id columns exist
    $check_columns = "SELECT column_name FROM information_schema.columns 
                     WHERE table_name = 'workers' AND column_name IN ('national_id', 'national_id_photo')";
    $column_check = $conn->query($check_columns);
    $columns = [];
    while ($row = $column_check->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['column_name'];
    }
    
    // Build dynamic query based on available columns
    $worker_sql = "SELECT experience_years, skills, availability";
    if (in_array('national_id', $columns)) {
        $worker_sql .= ", national_id";
    }
    if (in_array('national_id_photo', $columns)) {
        $worker_sql .= ", national_id_photo";
    }
    $worker_sql .= " FROM workers WHERE user_id = :user_id";
    
    $worker_stmt = $conn->prepare($worker_sql);
    $worker_stmt->bindParam(':user_id', $user_id);
    $worker_stmt->execute();
    $worker_data = $worker_stmt->fetch(PDO::FETCH_ASSOC);
    
    $experience = $worker_data['experience_years'] ?? '';
    $skills = $worker_data['skills'] ? json_decode($worker_data['skills'], true) : [];
    $availability = $worker_data['availability'] ?? '';
    $national_id = $worker_data['national_id'] ?? '';
    $national_id_photo = $worker_data['national_id_photo'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('nav.profile_settings'); ?> - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            background: #f8f9fa;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-size: 1.2rem;
        }
        
        .user-profile {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 24px;
            color: #000000;
        }
        
        .user-name {
            color: white;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .user-role {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
        
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #000000;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 60px;
            color: white;
        }
        
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            border-color: #000000;
            color: white;
        }
        
        .profile-avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto;
            cursor: pointer;
            overflow: hidden;
            border: 4px solid #f8f9fa;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            overflow: hidden;
            border: 4px solid #f8f9fa;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .upload-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .profile-avatar-container:hover .upload-overlay {
            opacity: 1;
        }
        
        .profile-avatar-container:hover .profile-avatar {
            transform: scale(1.05);
        }
        
        .upload-loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            
            <?php if ($user_role === 'employer'): ?>
            <a class="nav-link" href="post-job.php">
                <i class="fas fa-plus-circle"></i> <?php echo t('common.post_job'); ?>
            </a>
            <a class="nav-link" href="workers.php">
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
            <a class="nav-link" href="api/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row">
            <div class="col-12">
                <h2><?php echo t('nav.profile_settings'); ?></h2>
                <p class="text-muted">Manage your account information and preferences</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="profile-avatar-container mb-3">
                            <div class="profile-avatar" id="profile-avatar">
                                <?php if ($profile_image): ?>
                                    <img src="<?php echo htmlspecialchars($profile_image); ?>?t=<?php echo time(); ?>" alt="Profile picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <div class="upload-overlay" id="upload-overlay">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        <h5><?php echo htmlspecialchars($user_name); ?></h5>
                        <p class="text-muted"><?php echo ucfirst(htmlspecialchars($user_role)); ?></p>
                        <input type="file" id="profile-image-input" accept="image/*" style="display: none;">
                        <button class="btn btn-primary btn-sm" id="change-photo-btn">Change Photo</button>
                        <div class="mt-2" id="upload-status"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">Personal Information</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">Security</button>
                            </li>
                            <?php if ($user_role === 'worker'): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="professional-tab" data-bs-toggle="tab" data-bs-target="#professional" type="button" role="tab">Professional Info</button>
                            </li>
                            <?php endif; ?>
                        </ul>
                        
                        <div class="tab-content mt-3" id="profileTabsContent">
                            <!-- Personal Information Tab -->
                            <div class="tab-pane fade show active" id="personal" role="tabpanel">
                                <form id="personal-form">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="+250 7XX XXX XXX">
                                        </div>
                                        <?php if ($user_role === 'worker'): ?>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">National ID Number</label>
                                            <input type="text" class="form-control" id="national_id" name="national_id" value="<?php echo htmlspecialchars($national_id); ?>" placeholder="Enter your national ID number">
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($user_role === 'worker'): ?>
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">National ID Photo</label>
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <input type="file" id="national-id-photo-input" accept="image/*" style="display: none;">
                                                    <button type="button" class="btn btn-outline-secondary" id="upload-id-btn">
                                                        <i class="fas fa-camera me-2"></i>Upload ID Photo
                                                    </button>
                                                </div>
                                                <div id="id-photo-preview">
                                                    <?php if ($national_id_photo): ?>
                                                        <img src="uploads/<?php echo htmlspecialchars($national_id_photo); ?>" alt="National ID" style="max-height: 100px; border-radius: 8px; border: 1px solid #ddd;">
                                                        <small class="text-success ms-2"><i class="fas fa-check-circle"></i> ID photo uploaded</small>
                                                    <?php else: ?>
                                                        <small class="text-muted"><i class="fas fa-info-circle"></i> No ID photo uploaded</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="mt-2" id="id-upload-status"></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </form>
                            </div>
                            
                            <!-- Security Tab -->
                            <div class="tab-pane fade" id="security" role="tabpanel">
                                <form id="security-form">
                                    <div class="mb-3">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current-password">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new-password">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm-password">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Password</button>
                                </form>
                            </div>
                            
                            <?php if ($user_role === 'worker'): ?>
                            <!-- Professional Info Tab -->
                            <div class="tab-pane fade" id="professional" role="tabpanel">
                                <form id="professional-form">
                                    <div class="mb-3">
                                        <label class="form-label">Skills</label>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="skills[]" id="skill-cleaning" value="cleaning" <?php echo in_array('cleaning', $skills) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="skill-cleaning">Cleaning</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="skills[]" id="skill-cooking" value="cooking" <?php echo in_array('cooking', $skills) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="skill-cooking">Cooking</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="skills[]" id="skill-childcare" value="childcare" <?php echo in_array('childcare', $skills) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="skill-childcare">Childcare</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="skills[]" id="skill-eldercare" value="eldercare" <?php echo in_array('eldercare', $skills) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="skill-eldercare">Eldercare</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="skills[]" id="skill-gardening" value="gardening" <?php echo in_array('gardening', $skills) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="skill-gardening">Gardening</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Experience (years)</label>
                                        <input type="number" class="form-control" id="experience" name="experience" min="0" max="50" value="<?php echo htmlspecialchars($experience); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Availability</label>
                                        <select class="form-select" id="availability" name="availability">
                                            <option value="full-time" <?php echo $availability === 'full-time' ? 'selected' : ''; ?>>Full-time</option>
                                            <option value="part-time" <?php echo $availability === 'part-time' ? 'selected' : ''; ?>>Part-time</option>
                                            <option value="weekend" <?php echo $availability === 'weekend' ? 'selected' : ''; ?>>Weekend Only</option>
                                            <option value="flexible" <?php echo $availability === 'flexible' ? 'selected' : ''; ?>>Flexible</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Save Professional Info</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 11">
        <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas fa-bell me-2"></i>
                <strong class="me-auto" id="toastTitle">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastMessage">
                Message here
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <script>
        // Toast notification function
        function showNotification(title, message, type = 'success') {
            const toastElement = document.getElementById('notificationToast');
            const toastTitle = document.getElementById('toastTitle');
            const toastMessage = document.getElementById('toastMessage');
            
            // Update toast content
            toastTitle.textContent = title;
            toastMessage.textContent = message;
            
            // Update toast styling based on type
            toastElement.className = 'toast';
            toastElement.classList.add(type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info');
            toastElement.classList.add('text-white');
            
            // Show toast
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 3000
            });
            toast.show();
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Profile page DOM loaded');
            
            // Load current profile picture
            loadCurrentProfilePicture();
            
            // National ID photo upload functionality (only for workers)
            const uploadIdBtn = document.getElementById('upload-id-btn');
            console.log('Upload ID button found:', !!uploadIdBtn);
            
            if (uploadIdBtn) {
                const nationalIdPhotoInput = document.getElementById('national-id-photo-input');
                const idPhotoPreview = document.getElementById('id-photo-preview');
                const idUploadStatus = document.getElementById('id-upload-status');
                
                // Click handlers
                uploadIdBtn.addEventListener('click', () => {
                    console.log('Upload ID button clicked');
                    nationalIdPhotoInput.click();
                });
                
                // File change handler
                nationalIdPhotoInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Validate file
                        if (!file.type.startsWith('image/')) {
                            showIdUploadStatus('Please select an image file', 'error');
                            return;
                        }
                        
                        if (file.size > 5 * 1024 * 1024) {
                            showIdUploadStatus('File size must be less than 5MB', 'error');
                            return;
                        }
                        
                        // Show preview
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            idPhotoPreview.innerHTML = `<img src="${e.target.result}" alt="ID preview" style="max-height: 100px; border-radius: 8px; border: 1px solid #ddd;">`;
                        };
                        reader.readAsDataURL(file);
                        
                        // Upload file
                        uploadNationalIdPhoto(file);
                    }
                });
                
                function uploadNationalIdPhoto(file) {
                    const formData = new FormData();
                    formData.append('national_id_photo', file);
                    
                    showIdUploadStatus('<span class="upload-loading"></span>Uploading...', 'loading');
                    
                    fetch('api/upload-national-id.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showIdUploadStatus('✅ ID photo uploaded successfully!', 'success');
                            // Update preview with uploaded file
                            setTimeout(() => {
                                idPhotoPreview.innerHTML = `<img src="uploads/${result.filename}" alt="National ID" style="max-height: 100px; border-radius: 8px; border: 1px solid #ddd;">`;
                            }, 1000);
                        } else {
                            showIdUploadStatus('❌ ' + result.message, 'error');
                            // Reset preview on error
                            <?php if ($national_id_photo): ?>
                            idPhotoPreview.innerHTML = `<img src="uploads/<?php echo htmlspecialchars($national_id_photo); ?>" alt="National ID" style="max-height: 100px; border-radius: 8px; border: 1px solid #ddd;">`;
                            <?php else: ?>
                            idPhotoPreview.innerHTML = '<small class="text-muted"><i class="fas fa-info-circle"></i> No ID photo uploaded</small>';
                            <?php endif; ?>
                        }
                    })
                    .catch(error => {
                        console.error('Upload error:', error);
                        showIdUploadStatus('❌ Upload failed. Please try again.', 'error');
                        // Reset preview on error
                        <?php if ($national_id_photo): ?>
                        idPhotoPreview.innerHTML = `<img src="uploads/<?php echo htmlspecialchars($national_id_photo); ?>" alt="National ID" style="max-height: 100px; border-radius: 8px; border: 1px solid #ddd;">`;
                        <?php else: ?>
                        idPhotoPreview.innerHTML = '<small class="text-muted"><i class="fas fa-info-circle"></i> No ID photo uploaded</small>';
                        <?php endif; ?>
                    });
                }
                
                function showIdUploadStatus(message, type) {
                    idUploadStatus.innerHTML = message;
                    idUploadStatus.className = `mt-2 text-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'muted'}`;
                    
                    if (type === 'success') {
                        setTimeout(() => {
                            idUploadStatus.innerHTML = '';
                        }, 3000);
                    }
                }
            }
            
            // Profile picture upload functionality
            const changePhotoBtn = document.getElementById('change-photo-btn');
            const profileImageInput = document.getElementById('profile-image-input');
            const profileAvatar = document.getElementById('profile-avatar');
            const uploadStatus = document.getElementById('upload-status');
            
            console.log('Change photo button found:', !!changePhotoBtn);
            console.log('Profile image input found:', !!profileImageInput);
            
            // Only add event listeners if elements exist
            if (changePhotoBtn && profileImageInput) {
                console.log('Adding event listeners to profile photo upload');
                // Click handlers
                changePhotoBtn.addEventListener('click', () => {
                    console.log('Change photo button clicked');
                    profileImageInput.click();
                });
                profileAvatar.addEventListener('click', () => profileImageInput.click());
                
                // File change handler
                profileImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file
                    if (!file.type.startsWith('image/')) {
                        showUploadStatus('Please select an image file', 'error');
                        return;
                    }
                    
                    if (file.size > 5 * 1024 * 1024) {
                        showUploadStatus('File size must be less than 5MB', 'error');
                        return;
                    }
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profileAvatar.innerHTML = `<img src="${e.target.result}" alt="Profile preview">`;
                    };
                    reader.readAsDataURL(file);
                    
                    // Upload file
                    uploadProfilePicture(file);
                }
            });
            
            function uploadProfilePicture(file) {
                const formData = new FormData();
                formData.append('profile_image', file);
                
                showUploadStatus('<span class="upload-loading"></span>Uploading...', 'loading');
                
                fetch('api/upload-profile-picture.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showUploadStatus('✅ Profile picture updated successfully!', 'success');
                        // Add timestamp to prevent caching
                        const timestamp = Date.now();
                        const imageUrl = result.profile_image + '?t=' + timestamp;
                        // Update profile page avatar
                        profileAvatar.innerHTML = `<img src="${imageUrl}" alt="Profile picture" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                        // Update navbar profile picture
                        updateNavbarProfilePicture(imageUrl);
                    } else {
                        showUploadStatus('❌ ' + result.message, 'error');
                        // Reset to default on error
                        profileAvatar.innerHTML = '<i class="fas fa-user"></i>';
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    showUploadStatus('❌ Upload failed. Please try again.', 'error');
                    profileAvatar.innerHTML = '<i class="fas fa-user"></i>';
                });
            }
            
            function showUploadStatus(message, type) {
                uploadStatus.innerHTML = message;
                uploadStatus.className = `mt-2 text-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'muted'}`;
                
                if (type === 'success') {
                    setTimeout(() => {
                        uploadStatus.innerHTML = '';
                    }, 3000);
                }
            }
            
            function loadCurrentProfilePicture() {
                // Fetch current user profile picture
                fetch('api/get-user-profile.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data.profile_image) {
                        profileAvatar.innerHTML = `<img src="${result.data.profile_image}" alt="Profile picture">`;
                        updateNavbarProfilePicture(result.data.profile_image);
                    }
                })
                .catch(error => {
                    console.log('No profile picture found or error loading');
                });
            }
            
            function updateNavbarProfilePicture(imagePath) {
                // Update navbar profile picture if it exists
                const navbarAvatar = document.querySelector('.rounded-circle.bg-white.text-primary');
                if (navbarAvatar) {
                    navbarAvatar.innerHTML = `<img src="${imagePath}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                }
            }
            }
        });
        
        // Form submissions
        const personalForm = document.getElementById('personal-form');
        console.log('Personal form found:', !!personalForm);
        
        if (personalForm) {
            personalForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                console.log('Personal form submitted');
                
                // Check if all required fields exist
                const nameField = document.getElementById('name');
                const emailField = document.getElementById('email');
                const phoneField = document.getElementById('phone');
                
                console.log('Name field found:', !!nameField, 'Value:', nameField?.value);
                console.log('Email field found:', !!emailField, 'Value:', emailField?.value);
                console.log('Phone field found:', !!phoneField, 'Value:', phoneField?.value);
                
                const formData = {
                    type: 'personal',
                    name: nameField?.value || '',
                    email: emailField?.value || '',
                    phone: phoneField?.value || ''
                };
                
                // Add national ID if worker
                <?php if ($user_role === 'worker'): ?>
                const nationalIdField = document.getElementById('national_id');
                if (nationalIdField) {
                    formData.national_id = nationalIdField.value;
                }
                <?php endif; ?>
                
                try {
                    console.log('Submitting form data:', formData);
                    
                    const response = await fetch('api/update-profile.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'include',
                        body: JSON.stringify(formData)
                    });
                    
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    const result = await response.json();
                    console.log('API Response:', result);
                    
                    if (result.success) {
                        showNotification('Success', result.message, 'success');
                        // Update navbar if name changed
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Error', result.message || 'Update failed', 'error');
                    }
                } catch (error) {
                    console.error('Form submission error:', error);
                    showNotification('Error', 'An error occurred. Please try again.', 'error');
                }
            });
        }
        
        const securityForm = document.getElementById('security-form');
        console.log('Security form found:', !!securityForm);
        
        if (securityForm) {
            securityForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                console.log('Security form submitted');
                
                const currentPassword = document.getElementById('current-password').value;
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword !== confirmPassword) {
                showNotification('Error', 'Passwords do not match!', 'error');
                return;
            }
            
            const formData = {
                type: 'security',
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            };
            
            try {
                const response = await fetch('api/update-profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Success', result.message, 'success');
                    this.reset();
                } else {
                    showNotification('Error', result.message || 'Password update failed', 'error');
                }
            } catch (error) {
                showNotification('Error', 'An error occurred. Please try again.', 'error');
            }
        });
        }
        
        const professionalForm = document.getElementById('professional-form');
        console.log('Professional form found:', !!professionalForm);
        
        if (professionalForm) {
            professionalForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                console.log('Professional form submitted');
                
                const skills = [];
                document.querySelectorAll('input[name="skills[]"]:checked').forEach(checkbox => {
                    skills.push(checkbox.value);
                });
            
            const formData = {
                type: 'professional',
                skills: skills,
                experience: document.getElementById('experience').value,
                availability: document.getElementById('availability').value
            };
            
            try {
                const response = await fetch('api/update-profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Success', result.message, 'success');
                } else {
                    showNotification('Error', result.message || 'Update failed', 'error');
                }
            } catch (error) {
                showNotification('Error', 'An error occurred. Please try again.', 'error');
            }
        });
        }
    </script>
</body>
</html>
