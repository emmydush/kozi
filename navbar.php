<?php
// Get user session data
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
?>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(135deg, #000000 0%, #333333 100%); box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" id="mobile-menu-toggle" style="padding: 8px 12px; font-size: 1.2rem;">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php" style="font-size: 1.1rem;">
        </a>
        
        <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
            <!-- Notifications - Larger on mobile -->
            <div class="nav-item dropdown me-2 me-sm-3">
                <a class="nav-link text-white p-2 p-sm-1" href="#" role="button" data-bs-toggle="dropdown" style="font-size: 1.1rem;">
                    <i class="fas fa-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                        3
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Notifications</h6></li>
                    <li><a class="dropdown-item" href="#">New job application received</a></li>
                    <li><a class="dropdown-item" href="#">Payment confirmed</a></li>
                    <li><a class="dropdown-item" href="#">New message from Marie</a></li>
                </ul>
            </div>
            
            <!-- Messages - Larger on mobile -->
            <div class="nav-item me-2 me-sm-3">
                <a class="nav-link text-white p-2 p-sm-1" href="messages.php" style="font-size: 1.1rem;">
                    <i class="fas fa-envelope"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info" style="font-size: 0.6rem;">
                        5
                    </span>
                </a>
            </div>
            
            <!-- User Profile Dropdown - Mobile Optimized -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center text-white p-2 p-sm-1" href="#" role="button" data-bs-toggle="dropdown" style="font-size: 1.1rem;">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="d-none d-md-block ms-2">
                        <div class="small"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="small text-white-50"><?php echo ucfirst(htmlspecialchars($user_role)); ?></div>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header"><?php echo htmlspecialchars($user_name); ?></h6></li>
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
                    <li><a class="dropdown-item text-danger" href="api/logout.php">
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
</script>
