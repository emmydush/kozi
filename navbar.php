<?php
// Get user session data
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get user profile image from database
$profile_image = null;
if ($user_id) {
    $query = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $profile_image = $row['profile_image'];
    }
    $stmt->close();
}

// Determine profile image source
$image_src = 'uploads/profiles/user_1_1773319465.png'; // Default fallback
if (!empty($profile_image)) {
    if (strpos($profile_image, 'http') === 0) {
        // Full URL provided
        $image_src = $profile_image;
    } elseif (file_exists('uploads/profiles/' . $profile_image)) {
        // Local file exists
        $image_src = 'uploads/profiles/' . $profile_image;
    }
}

// Get user initial for fallback
$user_initial = strtoupper(substr($user_name, 0, 1));
?>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, #000000 0%, #333333 100%); box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" id="mobile-menu-toggle" style="padding: 8px 12px; font-size: 1.2rem;">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php" style="font-size: 1.1rem;">
            <img src="Logo.png" alt="KOZI CONNECT" style="height: 35px; margin-right: 10px;">
            <span>KOZI CONNECT</span>
        </a>
        
        <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
            <!-- Notifications - Larger on mobile -->
            <div class="nav-item dropdown me-2 me-sm-3">
                <a class="nav-link text-white p-2 p-sm-1" href="#" role="button" data-bs-toggle="dropdown" style="font-size: 1.1rem;">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-count" style="font-size: 0.6rem;">
                        0
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" id="notifications-dropdown">
                    <li><h6 class="dropdown-header">Notifications</h6></li>
                    <li><div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Loading...</div></li>
                </ul>
            </div>
            
            <!-- Messages - Larger on mobile -->
            <div class="nav-item me-2 me-sm-3">
                <a class="nav-link text-white p-2 p-sm-1" href="messages.php" style="font-size: 1.1rem;">
                    <i class="fas fa-envelope"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info messages-count" style="font-size: 0.6rem;">
                        0
                    </span>
                </a>
            </div>
            
            <!-- User Profile Dropdown - Mobile Optimized -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center text-white p-2 p-sm-1" href="#" role="button" data-bs-toggle="dropdown" style="font-size: 1.1rem;">
                    <div class="rounded-circle overflow-hidden d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px; background: white;">
                        <?php if (!empty($profile_image) && file_exists($image_src)): ?>
                            <img src="<?php echo htmlspecialchars($image_src); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-dark text-white">
                                <?php echo htmlspecialchars($user_initial); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="d-none d-md-block ms-2">
                        <div class="small"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="small text-white-50"><?php echo ucfirst(htmlspecialchars($user_role)); ?></div>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="px-3 py-2">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle overflow-hidden d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background: white;">
                                <?php if (!empty($profile_image) && file_exists($image_src)): ?>
                                    <img src="<?php echo htmlspecialchars($image_src); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-dark text-white">
                                        <?php echo htmlspecialchars($user_initial); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($user_name); ?></div>
                                <div class="small text-muted"><?php echo ucfirst(htmlspecialchars($user_role)); ?></div>
                            </div>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="profile.php">
                        <i class="fas fa-user-cog me-2"></i> Profile Settings
                    </a></li>
                    <li><a class="dropdown-item" href="reviews.php">
                        <i class="fas fa-star me-2"></i> My Reviews
                    </a></li>
                    <?php if ($user_role === 'worker'): ?>
                    <li><a class="dropdown-item" href="earnings.php">
                        <i class="fas fa-money-bill-wave me-2"></i> Earnings
                    </a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="help.php">
                        <i class="fas fa-question-circle me-2"></i> Help & Support
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="confirmLogout(event)">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
    .navbar {
        z-index: 1030;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        min-height: 60px;
        padding: 0.5rem 0;
        background: linear-gradient(135deg, #000000 0%, #333333 100%) !important;
    }
    
    .main-content {
        margin-top: 60px !important;
    }
    
    .sidebar {
        top: 60px !important;
        height: calc(100vh - 60px) !important;
        width: 280px !important;
        transform: translateX(-100%);
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0 !important;
        padding: 15px;
        min-height: calc(100vh - 60px);
        margin-top: 60px;
        background: #f8f9fa;
    }
    
    /* Desktop styles */
    @media (min-width: 992px) {
        .sidebar {
            transform: translateX(0);
            width: 250px !important;
        }
        
        .main-content {
            margin-left: 250px !important;
        }
    }
    
    /* Tablet styles */
    @media (min-width: 768px) and (max-width: 991px) {
        .sidebar {
            width: 260px !important;
        }
    }
    
    .mobile-menu-toggle {
        display: block !important;
        background: none !important;
        border: none !important;
        color: white !important;
    }
    
    /* Larger touch targets */
    .nav-link {
        min-height: 44px;
        display: flex;
        align-items: center;
    }
    
    .dropdown-item {
        padding: 12px 20px;
        min-height: 44px;
        display: flex;
        align-items: center;
    }
    
    /* Better mobile spacing */
    .container-fluid {
        padding: 0 10px;
    }
</style>

<script>
document.getElementById('mobile-menu-toggle')?.addEventListener('click', function() {
    toggleSidebar();
});

// Logout confirmation function
function confirmLogout(event) {
    event.preventDefault();
    
    // Create confirmation modal
    const modalHtml = `
        <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="logoutModalLabel">
                            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Logout
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-sign-out-alt fa-3x text-danger mb-3"></i>
                        </div>
                        <h6 class="text-center">Are you sure you want to logout?</h6>
                        <p class="text-muted text-center mb-0">You will be redirected to the homepage and will need to login again to access your account.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="performLogout()">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if present
    const existingModal = document.getElementById('logoutModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('logoutModal'));
    modal.show();
}

// Perform logout function
function performLogout() {
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('logoutModal'));
    modal.hide();
    
    // Show loading indicator
    const loadingHtml = `
        <div class="modal fade" id="logoutLoadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center py-4">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h6>Logging out...</h6>
                        <p class="text-muted mb-0">Please wait while we secure your session.</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add loading modal
    document.body.insertAdjacentHTML('beforeend', loadingHtml);
    const loadingModal = new bootstrap.Modal(document.getElementById('logoutLoadingModal'));
    loadingModal.show();
    
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
            // Show success message briefly
            setTimeout(() => {
                loadingModal.hide();
                // Redirect to homepage
                window.location.href = './index.php';
            }, 1000);
        } else {
            throw new Error('Logout failed');
        }
    })
    .catch(error => {
        console.error('Logout error:', error);
        loadingModal.hide();
        // Fallback: redirect anyway
        window.location.href = './index.php';
    });
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('mobile-menu-toggle');
    
    if (window.innerWidth < 992 && 
        !sidebar.contains(event.target) && 
        !toggle.contains(event.target) && 
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

// Load notifications and messages dynamically
function loadNotifications() {
    fetch('api/notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const notificationCount = document.querySelector('.notification-count');
                const dropdown = document.getElementById('notifications-dropdown');
                
                // Update count
                notificationCount.textContent = data.data.unread_count;
                notificationCount.style.display = data.data.unread_count > 0 ? 'inline-block' : 'none';
                
                // Update dropdown content
                let html = '<li><h6 class="dropdown-header">Notifications</h6></li>';
                
                if (data.data.notifications.length === 0) {
                    html += '<li><div class="dropdown-item text-muted">No new notifications</div></li>';
                } else {
                    data.data.notifications.forEach(notification => {
                        html += `
                            <li>
                                <a class="dropdown-item" href="${notification.link}">
                                    <div class="d-flex">
                                        <i class="${notification.icon} me-2 text-muted"></i>
                                        <div>
                                            <div>${notification.message}</div>
                                            <small class="text-muted">${notification.time}</small>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        `;
                    });
                }
                
                dropdown.innerHTML = html;
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function loadMessagesCount() {
    fetch('api/messages-count.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const messagesCount = document.querySelector('.messages-count');
                messagesCount.textContent = data.data.unread_count;
                messagesCount.style.display = data.data.unread_count > 0 ? 'inline-block' : 'none';
            }
        })
        .catch(error => console.error('Error loading messages count:', error));
}

// Load notifications and messages when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    loadMessagesCount();
    
    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
    
    // Refresh messages count every 30 seconds
    setInterval(loadMessagesCount, 30000);
});
</script>
