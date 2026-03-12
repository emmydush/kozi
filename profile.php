<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Household Connect</title>
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
            <a class="nav-link" href="#post-job">
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
                <i class="fas fa-envelope"></i> Messages
            </a>
            
            <hr class="text-white-50">
            
            <a class="nav-link" href="help.php">
                <i class="fas fa-question-circle"></i> Help & Support
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row">
            <div class="col-12">
                <h2>Profile Settings</h2>
                <p class="text-muted">Manage your account information and preferences</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="profile-avatar-container mb-3">
                            <div class="profile-avatar" id="profile-avatar">
                                <i class="fas fa-user"></i>
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
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" required>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" placeholder="+250 7XX XXX XXX">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Location</label>
                                            <select class="form-select">
                                                <option value="">Select District</option>
                                                <option value="kigali">Kigali</option>
                                                <option value="gasabo">Gasabo</option>
                                                <option value="kicukiro">Kicukiro</option>
                                                <option value="nyarugenge">Nyarugenge</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Bio</label>
                                        <textarea class="form-control" rows="3" placeholder="Tell us about yourself..."></textarea>
                                    </div>
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
                                                    <input class="form-check-input" type="checkbox" id="skill-cleaning">
                                                    <label class="form-check-label" for="skill-cleaning">Cleaning</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="skill-cooking">
                                                    <label class="form-check-label" for="skill-cooking">Cooking</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="skill-childcare">
                                                    <label class="form-check-label" for="skill-childcare">Childcare</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="skill-eldercare">
                                                    <label class="form-check-label" for="skill-eldercare">Eldercare</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="skill-gardening">
                                                    <label class="form-check-label" for="skill-gardening">Gardening</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Experience (years)</label>
                                        <input type="number" class="form-control" min="0" max="50">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Expected Salary (RWF/month)</label>
                                        <input type="number" class="form-control" min="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Availability</label>
                                        <select class="form-select">
                                            <option value="full-time">Full-time</option>
                                            <option value="part-time">Part-time</option>
                                            <option value="weekend">Weekend Only</option>
                                            <option value="flexible">Flexible</option>
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
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !toggle.contains(event.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
        
        document.addEventListener('DOMContentLoaded', function() {
            // Load current profile picture
            loadCurrentProfilePicture();
            
            // Profile picture upload functionality
            const changePhotoBtn = document.getElementById('change-photo-btn');
            const profileImageInput = document.getElementById('profile-image-input');
            const profileAvatar = document.getElementById('profile-avatar');
            const uploadStatus = document.getElementById('upload-status');
            
            // Click handlers
            changePhotoBtn.addEventListener('click', () => profileImageInput.click());
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
                        // Update navbar profile picture
                        updateNavbarProfilePicture(result.profile_image);
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
        });
        
        // Form submissions
        document.getElementById('personal-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                type: 'personal',
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                location: document.getElementById('location').value,
                bio: document.getElementById('bio').value
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
                    alert(result.message);
                    // Update navbar if name changed
                    location.reload();
                } else {
                    alert(result.message || 'Update failed');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        });
        
        document.getElementById('security-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Passwords do not match!');
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
                    alert(result.message);
                    this.reset();
                } else {
                    alert(result.message || 'Password update failed');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        });
        
        document.getElementById('professional-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const skills = [];
            document.querySelectorAll('input[name="skills[]"]:checked').forEach(checkbox => {
                skills.push(checkbox.value);
            });
            
            const formData = {
                type: 'professional',
                skills: skills,
                experience: document.getElementById('experience').value,
                expected_salary: document.getElementById('expected-salary').value,
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
                    alert(result.message);
                } else {
                    alert(result.message || 'Update failed');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        });
    </script>
</body>
</html>
