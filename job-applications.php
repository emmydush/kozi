<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('index.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Only employers should access this page
if ($user_role !== 'employer') {
    redirect('dashboard.php');
}

// Fetch job applications from database
$applications = [];
$sql = "SELECT ja.*, j.title, j.description, j.salary, j.location, j.work_hours, j.type as job_type,
               u.name as worker_name, u.email as worker_email, u.phone as worker_phone,
               w.profile_image, w.skills, w.experience_years,
               w.education, w.languages, w.certifications, w.description as worker_description,
               w.type as worker_type, w.hourly_rate, w.availability
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        JOIN users u ON ja.worker_id = u.id
        LEFT JOIN workers w ON ja.worker_id = w.user_id
        WHERE j.employer_id = :user_id
        ORDER BY ja.applied_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Don't close the connection here - navbar needs it
// $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applications - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dialog.css">
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
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #fff;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-size: 1.2rem;
        }
        
        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 60px);
            padding-top: 20px;
        }
        
        .main-content.sidebar-open {
            margin-left: 250px;
        }
        
        .menu-toggle {
            position: fixed;
            top: 80px;
            left: 20px;
            z-index: 1001;
            background: #000;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        .menu-toggle:hover {
            background: #333;
            transform: scale(1.1);
        }
        
        .application-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .application-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .status-badge {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-accepted {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .status-under_review {
            background-color: #cff4fc;
            color: #055160;
        }
        
        .worker-info {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .worker-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .worker-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .skill-tag {
            background-color: #f8f9fa;
            color: #495057;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-right: 0.3rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 280px;
            }
            
            .main-content.sidebar-open {
                margin-left: 280px;
            }
            
            .menu-toggle {
                top: 70px;
                left: 10px;
                width: 45px;
                height: 45px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
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
                <i class="fas fa-plus-circle"></i> Post Job
            </a>
            <a class="nav-link active" href="job-applications.php">
                <i class="fas fa-users"></i> Job Applications
            </a>
            <a class="nav-link" href="workers.php">
                <i class="fas fa-search"></i> Find Workers
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
            
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user"></i> Profile
            </a>
            <a class="nav-link" href="help.php">
                <i class="fas fa-question-circle"></i> Help
            </a>
        </nav>
    </div>

    <!-- Menu Toggle Button -->
    <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>Job Applications</h2>
                            <p class="text-muted">Manage applications to your posted jobs</p>
                        </div>
                        <a href="post-job.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Post New Job
                        </a>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="row mb-4">
                <div class="col-12">
                    <ul class="nav nav-tabs" id="applicationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                                All Applications (<?php echo count($applications); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab">
                                Pending (<?php echo count(array_filter($applications, fn($a) => $a['status'] === 'pending')); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="accepted-tab" data-bs-toggle="tab" data-bs-target="#accepted" type="button" role="tab">
                                Accepted (<?php echo count(array_filter($applications, fn($a) => $a['status'] === 'accepted')); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button" role="tab">
                                Rejected (<?php echo count(array_filter($applications, fn($a) => $a['status'] === 'rejected')); ?>)
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="applicationTabContent">
                <!-- All Applications -->
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    <?php if (empty($applications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Applications Yet</h4>
                            <p class="text-muted">When workers apply to your jobs, they will appear here.</p>
                            <a href="post-job.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-2"></i>Post a Job
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <div class="application-card">
                                <div class="row align-items-start">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($app['title']); ?></h5>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($app['location']); ?> • 
                                                    <i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($app['work_hours']); ?> • 
                                                    <i class="fas fa-money-bill me-1"></i><?php echo htmlspecialchars($app['salary']); ?>
                                                </p>
                                            </div>
                                            <span class="status-badge status-<?php echo $app['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="worker-info mb-3">
                                            <div class="worker-avatar">
                                                <?php 
                                                if (!empty($app['profile_image']) && file_exists('uploads/profiles/' . $app['profile_image'])) {
                                                    echo '<img src="uploads/profiles/' . htmlspecialchars($app['profile_image']) . '" alt="' . htmlspecialchars($app['worker_name']) . '">';
                                                } else {
                                                    echo strtoupper(substr($app['worker_name'], 0, 1));
                                                }
                                                ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($app['worker_name']); ?></h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($app['worker_email']); ?>
                                                    <?php if (!empty($app['worker_phone'])): ?>
                                                        • <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($app['worker_phone']); ?>
                                                    <?php endif; ?>
                                                </p>
                                                
                                                <!-- Worker Type and Hourly Rate -->
                                                <div class="mb-2">
                                                    <span class="badge bg-primary me-2"><?php echo ucfirst(htmlspecialchars($app['worker_type'] ?? 'N/A')); ?></span>
                                                    <?php if (!empty($app['hourly_rate'])): ?>
                                                        <span class="badge bg-success me-2">RWF <?php echo number_format($app['hourly_rate']); ?>/hr</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($app['experience_years'])): ?>
                                                        <span class="badge bg-info me-2"><?php echo $app['experience_years']; ?>+ years</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Skills -->
                                                <?php if (!empty($app['skills'])): ?>
                                                    <div class="mb-2">
                                                        <strong class="text-muted small">Skills:</strong>
                                                        <?php $skills = explode(',', $app['skills']); ?>
                                                        <?php foreach (array_slice($skills, 0, 5) as $skill): ?>
                                                            <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- National ID Information -->
                                                <?php if (!empty($app['national_id'])): ?>
                                                    <div class="mb-2">
                                                        <strong class="text-muted small">National ID:</strong>
                                                        <span class="text-success fw-bold"><?php echo htmlspecialchars($app['national_id']); ?></span>
                                                        <?php if (!empty($app['national_id_photo']) && file_exists('uploads/' . $app['national_id_photo'])): ?>
                                                            <button class="btn btn-sm btn-outline-primary ms-2" onclick="viewNationalId('<?php echo htmlspecialchars($app['national_id_photo']); ?>', '<?php echo htmlspecialchars($app['worker_name']); ?>')">
                                                                <i class="fas fa-id-card me-1"></i>View ID
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Education -->
                                                <?php if (!empty($app['education'])): ?>
                                                    <div class="mb-2">
                                                        <strong class="text-muted small">Education:</strong>
                                                        <span><?php echo htmlspecialchars($app['education']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Languages -->
                                                <?php if (!empty($app['languages'])): ?>
                                                    <div class="mb-2">
                                                        <strong class="text-muted small">Languages:</strong>
                                                        <span><?php echo htmlspecialchars($app['languages']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Availability -->
                                                <?php if (!empty($app['availability'])): ?>
                                                    <div class="mb-2">
                                                        <strong class="text-muted small">Availability:</strong>
                                                        <span><?php echo htmlspecialchars($app['availability']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Worker Description -->
                                                <?php if (!empty($app['worker_description'])): ?>
                                                    <div class="mb-2">
                                                        <strong class="text-muted small">About:</strong>
                                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($app['worker_description'], 0, 200)) . (strlen($app['worker_description']) > 200 ? '...' : ''); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($app['cover_letter'])): ?>
                                            <div class="mb-3">
                                                <h6>Cover Letter:</h6>
                                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($app['cover_letter'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-muted small">
                                            <i class="fas fa-calendar me-1"></i>
                                            Applied on <?php echo date('M j, Y', strtotime($app['applied_at'])); ?> at <?php echo date('g:i A', strtotime($app['applied_at'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="action-buttons">
                                            <?php if ($app['status'] === 'pending'): ?>
                                                <button class="btn btn-success btn-sm" onclick="updateApplicationStatus(<?php echo $app['id']; ?>, 'accepted')">
                                                    <i class="fas fa-check me-1"></i>Accept
                                                </button>
                                                <button class="btn btn-warning btn-sm" onclick="updateApplicationStatus(<?php echo $app['id']; ?>, 'under_review')">
                                                    <i class="fas fa-eye me-1"></i>Review
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="updateApplicationStatus(<?php echo $app['id']; ?>, 'rejected')">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                            <?php elseif ($app['status'] === 'under_review'): ?>
                                                <button class="btn btn-success btn-sm" onclick="updateApplicationStatus(<?php echo $app['id']; ?>, 'accepted')">
                                                    <i class="fas fa-check me-1"></i>Accept
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="updateApplicationStatus(<?php echo $app['id']; ?>, 'rejected')">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary btn-sm" disabled>
                                                    <i class="fas fa-check me-1"></i><?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <a href="mailto:<?php echo htmlspecialchars($app['worker_email']); ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-envelope me-1"></i>Contact
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Pending Applications -->
                <div class="tab-pane fade" id="pending" role="tabpanel">
                    <?php
                    $pending_apps = array_filter($applications, fn($a) => $a['status'] === 'pending');
                    if (empty($pending_apps)):
                    ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Pending Applications</h4>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_apps as $app): ?>
                            <div class="application-card">
                                <div class="row align-items-start">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($app['title']); ?></h5>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($app['location']); ?> • 
                                                    <i class="fas fa-clock me-1"></i><?php echo htmlspecialchars($app['work_hours']); ?> • 
                                                    <i class="fas fa-money-bill me-1"></i><?php echo htmlspecialchars($app['salary']); ?>
                                                </p>
                                            </div>
                                            <span class="status-badge status-pending">Pending</span>
                                        </div>
                                        
                                        <div class="worker-info mb-3">
                                            <div class="worker-avatar">
                                                <?php 
                                                if (!empty($app['profile_image']) && file_exists('uploads/profiles/' . $app['profile_image'])) {
                                                    echo '<img src="uploads/profiles/' . htmlspecialchars($app['profile_image']) . '" alt="' . htmlspecialchars($app['worker_name']) . '">';
                                                } else {
                                                    echo strtoupper(substr($app['worker_name'], 0, 1));
                                                }
                                                ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($app['worker_name']); ?></h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($app['worker_email']); ?>
                                                    <?php if (!empty($app['worker_phone'])): ?>
                                                        • <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($app['worker_phone']); ?>
                                                    <?php endif; ?>
                                                </p>
                                                
                                                <!-- Worker Type and Hourly Rate -->
                                                <div class="mb-2">
                                                    <span class="badge bg-primary me-2"><?php echo ucfirst(htmlspecialchars($app['worker_type'] ?? 'N/A')); ?></span>
                                                    <?php if (!empty($app['hourly_rate'])): ?>
                                                        <span class="badge bg-success me-2">RWF <?php echo number_format($app['hourly_rate']); ?>/hr</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($app['experience_years'])): ?>
                                                        <span class="badge bg-info me-2"><?php echo $app['experience_years']; ?>+ years</span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- National ID Information -->
                                                <?php if (!empty($app['national_id'])): ?>
                                                    <div class="mb-2">
                                                        <strong class="text-muted small">National ID:</strong>
                                                        <span class="text-success fw-bold"><?php echo htmlspecialchars($app['national_id']); ?></span>
                                                        <?php if (!empty($app['national_id_photo']) && file_exists('uploads/' . $app['national_id_photo'])): ?>
                                                            <button class="btn btn-sm btn-outline-primary ms-2" onclick="viewNationalId('<?php echo htmlspecialchars($app['national_id_photo']); ?>', '<?php echo htmlspecialchars($app['worker_name']); ?>')">
                                                                <i class="fas fa-id-card me-1"></i>View ID
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Skills -->
                                                <?php if (!empty($app['skills'])): ?>
                                                    <div class="mb-2">
                                                        <strong class="text-muted small">Skills:</strong>
                                                        <?php $skills = explode(',', $app['skills']); ?>
                                                        <?php foreach (array_slice($skills, 0, 5) as $skill): ?>
                                                            <span class="skill-tag"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Education -->
                                                <?php if (!empty($app['education'])): ?>
                                                    <div class="mb-2">
                                                        <strong class="text-muted small">Education:</strong>
                                                        <span><?php echo htmlspecialchars($app['education']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($app['cover_letter'])): ?>
                                            <div class="mb-3">
                                                <h6>Cover Letter:</h6>
                                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($app['cover_letter'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="action-buttons">
                                            <button class="btn btn-success btn-sm" onclick="updateApplicationStatus(<?php echo $app['id']; ?>, 'accepted')">
                                                <i class="fas fa-check me-1"></i>Accept
                                            </button>
                                            <button class="btn btn-warning btn-sm" onclick="updateApplicationStatus(<?php echo $app['id']; ?>, 'under_review')">
                                                <i class="fas fa-eye me-1"></i>Review
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="updateApplicationStatus(<?php echo $app['id']; ?>, 'rejected')">
                                                <i class="fas fa-times me-1"></i>Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Accepted Applications -->
                <div class="tab-pane fade" id="accepted" role="tabpanel">
                    <?php
                    $accepted_apps = array_filter($applications, fn($a) => $a['status'] === 'accepted');
                    if (empty($accepted_apps)):
                    ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Accepted Applications</h4>
                        </div>
                    <?php else: ?>
                        <?php foreach ($accepted_apps as $app): ?>
                            <!-- Same application card structure -->
                            <div class="application-card">
                                <div class="row align-items-start">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($app['title']); ?></h5>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($app['location']); ?> • 
                                                    <i class="fas fa-money-bill me-1"></i><?php echo htmlspecialchars($app['salary']); ?>
                                                </p>
                                            </div>
                                            <span class="status-badge status-accepted">Accepted</span>
                                        </div>
                                        
                                        <div class="worker-info mb-3">
                                            <div class="worker-avatar">
                                                <?php 
                                                if (!empty($app['profile_image']) && file_exists('uploads/profiles/' . $app['profile_image'])) {
                                                    echo '<img src="uploads/profiles/' . htmlspecialchars($app['profile_image']) . '" alt="' . htmlspecialchars($app['worker_name']) . '">';
                                                } else {
                                                    echo strtoupper(substr($app['worker_name'], 0, 1));
                                                }
                                                ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($app['worker_name']); ?></h6>
                                                <p class="text-muted mb-1"><?php echo htmlspecialchars($app['worker_email']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="action-buttons">
                                            <a href="mailto:<?php echo htmlspecialchars($app['worker_email']); ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-envelope me-1"></i>Contact Worker
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Rejected Applications -->
                <div class="tab-pane fade" id="rejected" role="tabpanel">
                    <?php
                    $rejected_apps = array_filter($applications, fn($a) => $a['status'] === 'rejected');
                    if (empty($rejected_apps)):
                    ?>
                        <div class="text-center py-5">
                            <i class="fas fa-times-circle fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Rejected Applications</h4>
                        </div>
                    <?php else: ?>
                        <?php foreach ($rejected_apps as $app): ?>
                            <!-- Same application card structure -->
                            <div class="application-card opacity-75">
                                <div class="row align-items-start">
                                    <div class="col-md-8">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="mb-1"><?php echo htmlspecialchars($app['title']); ?></h5>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($app['location']); ?> • 
                                                    <i class="fas fa-money-bill me-1"></i><?php echo htmlspecialchars($app['salary']); ?>
                                                </p>
                                            </div>
                                            <span class="status-badge status-rejected">Rejected</span>
                                        </div>
                                        
                                        <div class="worker-info mb-3">
                                            <div class="worker-avatar">
                                                <?php 
                                                if (!empty($app['profile_image']) && file_exists('uploads/profiles/' . $app['profile_image'])) {
                                                    echo '<img src="uploads/profiles/' . htmlspecialchars($app['profile_image']) . '" alt="' . htmlspecialchars($app['worker_name']) . '">';
                                                } else {
                                                    echo strtoupper(substr($app['worker_name'], 0, 1));
                                                }
                                                ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($app['worker_name']); ?></h6>
                                                <p class="text-muted mb-1"><?php echo htmlspecialchars($app['worker_email']); ?></p>
                                            </div>
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

    <script src="assets/js/dialog.js"></script>
    <script>
        function updateApplicationStatus(applicationId, newStatus) {
            showConfirm(
                'Are you sure you want to ' + newStatus + ' this application?',
                () => {
                    // User confirmed, proceed with update
                    fetch('./api/update-application-status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            application_id: applicationId,
                            status: newStatus
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('Application status updated successfully!', 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert('Error: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('An error occurred. Please try again.', 'error');
                    });
                }
            );
        }
        
        function viewNationalId(photoFilename, workerName) {
            const modal = new bootstrap.Modal(document.getElementById('nationalIdModal'));
            const imageElement = document.getElementById('nationalIdImage');
            const nameElement = document.getElementById('nationalIdWorkerName');
            
            imageElement.src = 'uploads/' + photoFilename;
            nameElement.textContent = workerName;
            modal.show();
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const menuToggle = document.getElementById('menuToggle');
            
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('sidebar-open');
            
            // Toggle icon
            const icon = menuToggle.querySelector('i');
            if (sidebar.classList.contains('show')) {
                icon.classList.remove('fas fa-bars');
                icon.classList.add('fas fa-times');
            } else {
                icon.classList.remove('fas fa-times');
                icon.classList.add('fas fa-bars');
            }
        }
        
        // Auto-open sidebar on desktop
        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth > 768) {
                toggleSidebar();
            }
        });
    </script>
    
    <!-- National ID Modal -->
    <div class="modal fade" id="nationalIdModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">National ID Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <h6 class="mb-3">Worker: <span id="nationalIdWorkerName"></span></h6>
                    <img id="nationalIdImage" src="" alt="National ID" class="img-fluid" style="max-height: 500px; border-radius: 8px;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Close database connection at the end
$conn->close();
?>
