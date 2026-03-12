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
    <title>Help & Support - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #667eea;
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
            background: #667eea;
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
        
        .help-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .help-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .faq-item {
            border-bottom: 1px solid #dee2e6;
            padding: 15px 0;
        }
        
        .faq-item:last-child {
            border-bottom: none;
        }
        
        .faq-question {
            cursor: pointer;
            font-weight: 600;
            color: #495057;
        }
        
        .faq-question:hover {
            color: #667eea;
        }
        
        .faq-answer {
            margin-top: 10px;
            color: #6c757d;
            display: none;
        }
        
        .faq-answer.show {
            display: block;
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
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user-cog"></i> Profile Settings
            </a>
            <a class="nav-link" href="reviews.php">
                <i class="fas fa-star"></i> Reviews
            </a>
            
            <hr class="text-white-50">
            
            <a class="nav-link active" href="help.php">
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
                <h2>Help & Support</h2>
                <p class="text-muted">Find answers to common questions and get support</p>
            </div>
        </div>

        <!-- Quick Help Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="help-card card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-book fa-3x text-primary mb-3"></i>
                        <h5>User Guide</h5>
                        <p class="text-muted">Learn how to use Household Connect effectively</p>
                        <button class="btn btn-primary">View Guide</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="help-card card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-video fa-3x text-success mb-3"></i>
                        <h5>Video Tutorials</h5>
                        <p class="text-muted">Watch step-by-step video guides</p>
                        <button class="btn btn-success">Watch Videos</button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="help-card card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-headset fa-3x text-info mb-3"></i>
                        <h5>Contact Support</h5>
                        <p class="text-muted">Get help from our support team</p>
                        <button class="btn btn-info">Contact Us</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- FAQ Section -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Frequently Asked Questions</h5>
                    </div>
                    <div class="card-body">
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <i class="fas fa-chevron-right me-2"></i>
                                How do I post a job as an employer?
                            </div>
                            <div class="faq-answer">
                                To post a job, click on "Post Job" from the sidebar menu. Fill in the job details including title, description, job type, salary, location, and work hours. Submit the form and your job will be posted for workers to see and apply.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <i class="fas fa-chevron-right me-2"></i>
                                How do I apply for jobs as a worker?
                            </div>
                            <div class="faq-answer">
                                Browse available jobs from the "Find Jobs" page. Use filters to narrow down your search. Click on any job to view details and click "Apply Now" to submit your application. Make sure your profile is complete with your skills and experience.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <i class="fas fa-chevron-right me-2"></i>
                                How do payments work?
                            </div>
                            <div class="faq-answer">
                                Payments are handled securely through our platform. Employers can pay workers directly through the system after work is completed. Workers can track their earnings and payment status in the "Earnings" section.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <i class="fas fa-chevron-right me-2"></i>
                                How do I communicate with workers/employers?
                            </div>
                            <div class="faq-answer">
                                Use the "Messages" section to communicate with other users. You can send messages, share files, and coordinate work details. All communications are secure and monitored for safety.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <i class="fas fa-chevron-right me-2"></i>
                                What if I have a dispute with a worker/employer?
                            </div>
                            <div class="faq-answer">
                                If you have a dispute, first try to resolve it through direct communication. If that doesn't work, contact our support team with details of the issue. We mediate disputes and help find fair solutions for both parties.
                            </div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFAQ(this)">
                                <i class="fas fa-chevron-right me-2"></i>
                                How do ratings and reviews work?
                            </div>
                            <div class="faq-answer">
                                After completing a job, both employers and workers can rate and review each other. Reviews help build trust in the community. Be honest and constructive in your reviews to help others make informed decisions.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Contact Support</h5>
                    </div>
                    <div class="card-body">
                        <form id="support-form">
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <select class="form-select" required>
                                    <option value="">Select issue type</option>
                                    <option value="technical">Technical Issue</option>
                                    <option value="payment">Payment Problem</option>
                                    <option value="account">Account Issue</option>
                                    <option value="dispute">Dispute Resolution</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" rows="4" placeholder="Describe your issue..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Send Message</button>
                        </form>

                        <hr>

                        <div class="text-center">
                            <h6>Other Ways to Reach Us</h6>
                            <div class="mt-3">
                                <p class="mb-2">
                                    <i class="fas fa-phone me-2"></i>
                                    +250 788 123 456
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-envelope me-2"></i>
                                    support@householdconnect.rw
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    Mon-Fri: 8AM-6PM
                                </p>
                            </div>
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
        
        // Toggle FAQ answers
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            answer.classList.toggle('show');
            
            if (answer.classList.contains('show')) {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            } else {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
            }
        }
        
        // Support form submission
        document.getElementById('support-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Support request submitted successfully! We will get back to you within 24 hours.');
            this.reset();
        });
    </script>
</body>
</html>
