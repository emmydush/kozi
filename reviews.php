<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
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
        
        .review-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .star-rating {
            color: #ffc107;
        }
        
        .rating-input {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
        }
        
        .rating-input:hover,
        .rating-input.active {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-home"></i> Household Connect</h3>
        </div>
        
        <div class="user-profile">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
            <div class="user-role"><?php echo ucfirst(htmlspecialchars($user_role)); ?></div>
        </div>
        
        <nav class="nav flex-column p-3">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            
            <?php if ($user_role === 'employer'): ?>
            <a class="nav-link" href="post-job.php">
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
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user-cog"></i> Profile Settings
            </a>
            <a class="nav-link active" href="reviews.php">
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
                <h2>Reviews</h2>
                <p class="text-muted"><?php echo $user_role === 'employer' ? 'Manage reviews for workers you have hired' : 'View reviews from employers and manage your reputation'; ?></p>
            </div>
        </div>

        <!-- Rating Overview -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="star-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </h3>
                        <h4>4.5</h4>
                        <p class="text-muted">Average Rating</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3>12</h3>
                        <p class="text-muted">Total Reviews</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3>95%</h3>
                        <p class="text-muted">Positive Reviews</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Write Review Section -->
        <?php if ($user_role === 'employer'): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Write a Review</h5>
                    </div>
                    <div class="card-body">
                        <form id="review-form">
                            <div class="mb-3">
                                <label class="form-label">Select Worker</label>
                                <select class="form-select" id="worker-select" required>
                                    <option value="">Choose a worker to review</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rating</label>
                                <div class="rating-input-container">
                                    <span class="rating-input" data-rating="1"><i class="fas fa-star"></i></span>
                                    <span class="rating-input" data-rating="2"><i class="fas fa-star"></i></span>
                                    <span class="rating-input" data-rating="3"><i class="fas fa-star"></i></span>
                                    <span class="rating-input" data-rating="4"><i class="fas fa-star"></i></span>
                                    <span class="rating-input" data-rating="5"><i class="fas fa-star"></i></span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Review</label>
                                <textarea class="form-control" rows="3" placeholder="Share your experience..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reviews List -->
        <div class="row">
            <div class="col-12">
                <h5 class="mb-3">Recent Reviews</h5>
                
                <div id="reviews-container">
                    <p class="text-muted">Loading reviews...</p>
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
        
        // Rating system
        let selectedRating = 0;
        const ratingInputs = document.querySelectorAll('.rating-input');
        
        ratingInputs.forEach(input => {
            input.addEventListener('click', function() {
                selectedRating = parseInt(this.dataset.rating);
                updateRatingDisplay(selectedRating);
            });
            
            input.addEventListener('mouseenter', function() {
                const hoverRating = parseInt(this.dataset.rating);
                updateRatingDisplay(hoverRating);
            });
        });
        
        document.querySelector('.rating-input-container').addEventListener('mouseleave', function() {
            updateRatingDisplay(selectedRating);
        });
        
        function updateRatingDisplay(rating) {
            ratingInputs.forEach((input, index) => {
                if (index < rating) {
                    input.classList.add('active');
                } else {
                    input.classList.remove('active');
                }
            });
        }
        
        // Form submission
        document.getElementById('review-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (selectedRating === 0) {
                alert('Please select a rating');
                return;
            }
            
            alert('Review submitted successfully!');
            this.reset();
            selectedRating = 0;
            updateRatingDisplay(0);
        });
    </script>
</body>
</html>
