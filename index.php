<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Household Connect - Kigali | Find Trusted Household Workers</title>
    <meta name="description" content="Connect with trusted household workers in Kigali. Find reliable domestic workers for cleaning, cooking, childcare, and more.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #000000;
            --secondary-color: #333333;
            --success-color: #000000;
            --accent-color: #000000;
            --text-dark: #000000;
            --text-light: #666666;
            --bg-light: #f8f9fa;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow-x: hidden;
        }

        /* Enhanced Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
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
            background: url('https://picsum.photos/seed/household-workers/1920/1080.jpg') center/cover;
            opacity: 0.2;
            z-index: 0;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
            z-index: 1;
        }

        .hero-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 2;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .hero-content {
            position: relative;
            z-index: 3;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-content .lead {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .hero-buttons {
            gap: 1rem;
        }

        .hero-buttons .btn {
            padding: 15px 35px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-hero-primary {
            background: var(--success-color);
            color: white;
            border: none;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }

        .btn-hero-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(40, 167, 69, 0.4);
            background: #218838;
        }

        .btn-hero-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
            backdrop-filter: blur(10px);
        }

        .btn-hero-outline:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        /* Enhanced Feature Cards */
        .feature-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            height: 100%;
            position: relative;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            transform: scaleX(0);
            transition: transform 0.3s ease;
            z-index: 1;
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.2);
        }

        .feature-image {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .feature-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .feature-card:hover .feature-image img {
            transform: scale(1.1);
        }

        .feature-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(0,0,0,0.1), rgba(0,0,0,0.3));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .feature-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .feature-content {
            padding: 1.5rem;
            text-align: center;
        }

        .feature-content h4 {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-dark);
        }

        .feature-content p {
            color: var(--text-light);
            margin-bottom: 0;
        }

        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, var(--bg-light), white);
            padding: 4rem 0;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            color: var(--text-light);
            font-weight: 500;
        }

        /* Enhanced Workers Section */
        .workers-section {
            padding: 5rem 0;
            background: white;
        }

        .worker-preview-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .worker-preview-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .worker-preview-img {
            height: 150px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .worker-preview-info {
            padding: 1.5rem;
        }

        .worker-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .worker-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
            margin-top: 0.5rem;
        }

        .skill-badge {
            background: var(--bg-light);
            color: var(--text-dark);
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--success-color), var(--accent-color));
            padding: 5rem 0;
            color: white;
        }

        .cta-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
                line-height: 1.2;
            }
            
            .hero-content .lead {
                font-size: 1rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                gap: 1rem;
            }
            
            .hero-buttons .btn {
                padding: 15px 25px;
                font-size: 1rem;
                width: 100%;
                justify-content: center;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .stat-label {
                font-size: 0.9rem;
            }
            
            .cta-content h2 {
                font-size: 1.5rem;
            }
            
            .feature-card {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
            }
            
            .feature-icon i {
                font-size: 1.5rem;
            }
            
            .worker-preview-card {
                margin-bottom: 1rem;
            }
            
            .worker-preview-img {
                height: 120px;
            }
            
            .worker-name {
                font-size: 0.9rem;
            }
            
            .skill-badge {
                font-size: 0.7rem;
                padding: 2px 6px;
            }
            
            .particle {
                display: none; /* Remove particles on mobile for better performance */
            }
            
            .hero-section {
                padding: 2rem 0;
            }
            
            .hero-image img {
                max-height: 300px !important;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .section-subtitle {
                font-size: 0.9rem;
            }
            
            /* Mobile navigation improvements */
            .navbar {
                padding: 0.5rem 1rem;
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .sidebar {
                width: 280px;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 1.8rem;
                line-height: 1.1;
            }
            
            .hero-content .lead {
                font-size: 0.95rem;
            }
            
            .hero-buttons .btn {
                padding: 12px 20px;
                font-size: 0.95rem;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
            
            .stat-label {
                font-size: 0.8rem;
            }
            
            .feature-card {
                padding: 1rem;
            }
            
            .feature-icon {
                width: 50px;
                height: 50px;
            }
            
            .feature-icon i {
                font-size: 1.2rem;
            }
            
            .worker-preview-card {
                margin-bottom: 0.8rem;
            }
            
            .worker-preview-img {
                height: 100px;
            }
            
            .worker-name {
                font-size: 0.85rem;
            }
            
            .skill-badge {
                font-size: 0.65rem;
                padding: 1px 4px;
            }
            
            .cta-content h2 {
                font-size: 1.3rem;
            }
            
            .cta-content .lead {
                font-size: 0.9rem;
            }
            
            .section-title {
                font-size: 1.3rem;
            }
            
            .section-subtitle {
                font-size: 0.85rem;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .row {
                margin: 0;
            }
            
            .col-md-3, .col-md-4, .col-md-6, .col-md-8, .col-md-12 {
                padding: 0.5rem;
            }
            
            /* Touch-friendly improvements */
            .btn {
                min-height: 44px; /* iOS touch target minimum */
            }
            
            .nav-link {
                padding: 0.75rem 1rem;
            }
            
            .form-control {
                font-size: 16px; /* Prevents zoom on iOS */
                min-height: 44px;
            }
            
            /* Hide less important elements on very small screens */
            .hero-section::before {
                opacity: 0.1;
            }
        }

        /* Loading Animation */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php include 'navbar.php'; ?>
        <!-- Sidebar for logged-in users -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-content">
                <div class="sidebar-header">
                    <h5 class="text-white">Menu</h5>
                    <button class="btn btn-sm btn-light" onclick="toggleSidebar()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="profile.php">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="workers.php">
                            <i class="fas fa-users me-2"></i> Workers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="jobs.php">
                            <i class="fas fa-briefcase me-2"></i> Jobs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="messages.php">
                            <i class="fas fa-envelope me-2"></i> Messages
                        </a>
                    </li>
                    <?php if ($_SESSION['user_role'] === 'worker'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="earnings.php">
                            <i class="fas fa-money-bill-wave me-2"></i> Earnings
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="help.php">
                            <i class="fas fa-question-circle me-2"></i> Help
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    <?php else: ?>
        <!-- Public Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: rgba(0,0,0,0.1); backdrop-filter: blur(10px);">
            <div class="container">
                <a class="navbar-brand fw-bold" href="index.php">
                    <img src="Logo.png" alt="KOZI CONNECT" style="height: 30px; margin-right: 8px;">
                    KOZI CONNECT
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#services">Services</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#workers">Workers</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#how-it-works">How It Works</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-success text-white px-3 ms-2" href="register.php">Register</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-particles">
            <div class="particle" style="width: 20px; height: 20px; left: 10%; top: 20%; animation-delay: 0s;"></div>
            <div class="particle" style="width: 15px; height: 15px; left: 80%; top: 60%; animation-delay: 2s;"></div>
            <div class="particle" style="width: 25px; height: 25px; left: 60%; top: 80%; animation-delay: 4s;"></div>
            <div class="particle" style="width: 18px; height: 18px; left: 30%; top: 40%; animation-delay: 1s;"></div>
            <div class="particle" style="width: 22px; height: 22px; left: 90%; top: 30%; animation-delay: 3s;"></div>
        </div>
        
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6 hero-content" data-aos="fade-right">
                    <h1>Connect with Trusted Household Workers in Kigali</h1>
                    <p class="lead">Find reliable domestic workers for cleaning, cooking, childcare, and more. All workers are verified and reviewed.</p>
                    <div class="hero-buttons d-flex flex-wrap">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="jobs.php" class="btn btn-hero-primary">
                                <i class="fas fa-search"></i> Find Workers
                            </a>
                            <a href="post-job.php" class="btn btn-hero-outline">
                                <i class="fas fa-plus"></i> Post Job
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-hero-primary">
                                <i class="fas fa-user-plus"></i> Get Started
                            </a>
                            <a href="#workers" class="btn btn-hero-outline">
                                <i class="fas fa-search"></i> Browse Workers
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="hero-image text-center position-relative">
                        <img src="https://picsum.photos/seed/household-workers-team/600/500.jpg" 
                             alt="Household Workers Team" 
                             class="img-fluid rounded-3 shadow-lg"
                             style="max-height: 500px; object-fit: cover;">
                        <div class="hero-image-overlay position-absolute top-0 start-0 w-100 h-100 rounded-3" 
                             style="background: linear-gradient(45deg, rgba(0,0,0,0.1), rgba(0,0,0,0.3)); pointer-events: none;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-card">
                        <div class="stat-number" data-count="500">0</div>
                        <div class="stat-label">Verified Workers</div>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <div class="stat-number" data-count="1200">0</div>
                        <div class="stat-label">Happy Families</div>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-card">
                        <div class="stat-number" data-count="3500">0</div>
                        <div class="stat-label">Jobs Completed</div>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-card">
                        <div class="stat-number" data-count="98">0</div>
                        <div class="stat-label">Satisfaction %</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-4 fw-bold">Our Services</h2>
                <p class="lead text-muted">Professional household services tailored to your needs</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-image">
                            <img src="https://picsum.photos/seed/professional-house-cleaning-maid-service/400/300.jpg" alt="Professional House Cleaning Service">
                            <div class="feature-image-overlay">
                                <div class="feature-icon">
                                    <i class="fas fa-broom"></i>
                                </div>
                            </div>
                        </div>
                        <div class="feature-content">
                            <h4>House Cleaning</h4>
                            <p class="text-muted">Professional cleaning services for homes and apartments with eco-friendly products.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-image">
                            <img src="https://picsum.photos/seed/domestic-cook-kitchen-meal-preparation/400/300.jpg" alt="Domestic Cooking and Meal Preparation">
                            <div class="feature-image-overlay">
                                <div class="feature-icon">
                                    <i class="fas fa-utensils"></i>
                                </div>
                            </div>
                        </div>
                        <div class="feature-content">
                            <h4>Cooking & Meal Prep</h4>
                            <p class="text-muted">Experienced cooks for daily meals, meal planning, and special occasions.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-image">
                            <img src="https://picsum.photos/seed/nanny-childcare-provider-babysitter/400/300.jpg" alt="Nanny Childcare Provider Service">
                            <div class="feature-image-overlay">
                                <div class="feature-icon">
                                    <i class="fas fa-baby"></i>
                                </div>
                            </div>
                        </div>
                        <div class="feature-content">
                            <h4>Childcare</h4>
                            <p class="text-muted">Trusted caregivers for your children's safety, development, and well-being.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card">
                        <div class="feature-image">
                            <img src="https://picsum.photos/seed/elderly-caregiver-senior-care-assistant/400/300.jpg" alt="Elderly Caregiver Senior Care Service">
                            <div class="feature-image-overlay">
                                <div class="feature-icon">
                                    <i class="fas fa-user-friends"></i>
                                </div>
                            </div>
                        </div>
                        <div class="feature-content">
                            <h4>Elder Care</h4>
                            <p class="text-muted">Compassionate caregivers providing support and companionship for elderly family members.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="feature-card">
                        <div class="feature-image">
                            <img src="https://picsum.photos/seed/gardener-landscaping-garden-maintenance/400/300.jpg" alt="Professional Gardener Landscaping Service">
                            <div class="feature-image-overlay">
                                <div class="feature-icon">
                                    <i class="fas fa-seedling"></i>
                                </div>
                            </div>
                        </div>
                        <div class="feature-content">
                            <h4>Gardening</h4>
                            <p class="text-muted">Expert gardeners for lawn maintenance, landscaping, and plant care.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="600">
                    <div class="feature-card">
                        <div class="feature-image">
                            <img src="https://picsum.photos/seed/personal-driver-family-chauffeur-service/400/300.jpg" alt="Personal Driver Family Chauffeur Service">
                            <div class="feature-image-overlay">
                                <div class="feature-icon">
                                    <i class="fas fa-car"></i>
                                </div>
                            </div>
                        </div>
                        <div class="feature-content">
                            <h4>Driving</h4>
                            <p class="text-muted">Professional drivers for family transportation and errands.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Workers Section -->
    <section id="workers" class="workers-section">
        <div class="container">
            <div class="row align-items-center mb-5">
                <div class="col-lg-6" data-aos="fade-right">
                    <h2 class="display-4 fw-bold">Featured Workers</h2>
                    <p class="lead text-muted">Meet some of our top-rated household workers in Kigali</p>
                    <a href="workers.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-arrow-right me-2"></i>View All Workers
                    </a>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="row g-3" id="featured-workers">
                        <!-- Workers will be loaded here -->
                        <div class="col-6">
                            <div class="worker-preview-card loading-skeleton">
                                <div class="worker-preview-img"></div>
                                <div class="worker-preview-info">
                                    <div class="worker-name loading-skeleton" style="height: 20px; width: 80%; margin-bottom: 10px;"></div>
                                    <div class="worker-skills">
                                        <span class="skill-badge loading-skeleton" style="width: 50px; height: 20px;"></span>
                                        <span class="skill-badge loading-skeleton" style="width: 40px; height: 20px;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="worker-preview-card loading-skeleton">
                                <div class="worker-preview-img"></div>
                                <div class="worker-preview-info">
                                    <div class="worker-name loading-skeleton" style="height: 20px; width: 80%; margin-bottom: 10px;"></div>
                                    <div class="worker-skills">
                                        <span class="skill-badge loading-skeleton" style="width: 50px; height: 20px;"></span>
                                        <span class="skill-badge loading-skeleton" style="width: 40px; height: 20px;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="worker-preview-card loading-skeleton">
                                <div class="worker-preview-img"></div>
                                <div class="worker-preview-info">
                                    <div class="worker-name loading-skeleton" style="height: 20px; width: 80%; margin-bottom: 10px;"></div>
                                    <div class="worker-skills">
                                        <span class="skill-badge loading-skeleton" style="width: 50px; height: 20px;"></span>
                                        <span class="skill-badge loading-skeleton" style="width: 40px; height: 20px;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="worker-preview-card loading-skeleton">
                                <div class="worker-preview-img"></div>
                                <div class="worker-preview-info">
                                    <div class="worker-name loading-skeleton" style="height: 20px; width: 80%; margin-bottom: 10px;"></div>
                                    <div class="worker-skills">
                                        <span class="skill-badge loading-skeleton" style="width: 50px; height: 20px;"></span>
                                        <span class="skill-badge loading-skeleton" style="width: 40px; height: 20px;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-4 fw-bold">How It Works</h2>
                <p class="lead text-muted">Get connected with household workers in 3 simple steps</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="text-center">
                        <div class="feature-icon mx-auto mb-3">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h4>1. Sign Up</h4>
                        <p class="text-muted">Create your account and tell us about your household needs</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-center">
                        <div class="feature-icon mx-auto mb-3">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4>2. Find Workers</h4>
                        <p class="text-muted">Browse through verified workers and read reviews from other families</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="text-center">
                        <div class="feature-icon mx-auto mb-3">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4>3. Connect & Hire</h4>
                        <p class="text-muted">Contact workers directly and hire the perfect match for your family</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center cta-content" data-aos="fade-up">
                    <h2>Ready to Find Your Perfect Household Worker?</h2>
                    <p>Join hundreds of families in Kigali who trust Household Connect for their domestic help needs</p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="jobs.php" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-search me-2"></i>Browse Workers
                        </a>
                        <a href="post-job.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-plus me-2"></i>Post a Job
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-user-plus me-2"></i>Get Started Now
                        </a>
                        <a href="login.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <!-- Brand Section -->
                <div class="col-lg-4 col-md-6 footer-section">
                    <a href="index.php" class="footer-brand">
                        <i class="fas fa-home"></i>
                        Household Connect
                    </a>
                    <p class="footer-description">
                        Connecting Kigali families with trusted household workers since 2024. Your reliable partner for all domestic help needs.
                    </p>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 footer-section">
                    <h6 class="footer-title">Quick Links</h6>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-chevron-right me-1"></i>Home</a></li>
                        <li><a href="workers.php"><i class="fas fa-chevron-right me-1"></i>Find Workers</a></li>
                        <li><a href="jobs.php"><i class="fas fa-chevron-right me-1"></i>Browse Jobs</a></li>
                        <li><a href="about.php"><i class="fas fa-chevron-right me-1"></i>About Us</a></li>
                        <li><a href="dashboard.php"><i class="fas fa-chevron-right me-1"></i>Dashboard</a></li>
                    </ul>
                </div>
                
                <!-- Services -->
                <div class="col-lg-2 col-md-6 footer-section">
                    <h6 class="footer-title">Services</h6>
                    <ul class="footer-links">
                        <li><a href="#services"><i class="fas fa-chevron-right me-1"></i>House Cleaning</a></li>
                        <li><a href="#services"><i class="fas fa-chevron-right me-1"></i>Cooking</a></li>
                        <li><a href="#services"><i class="fas fa-chevron-right me-1"></i>Childcare</a></li>
                        <li><a href="#services"><i class="fas fa-chevron-right me-1"></i>Elder Care</a></li>
                        <li><a href="#services"><i class="fas fa-chevron-right me-1"></i>Gardening</a></li>
                    </ul>
                </div>
                
                <!-- Support -->
                <div class="col-lg-2 col-md-6 footer-section">
                    <h6 class="footer-title">Support</h6>
                    <ul class="footer-links">
                        <li><a href="help.php"><i class="fas fa-chevron-right me-1"></i>Help Center</a></li>
                        <li><a href="contact.php"><i class="fas fa-chevron-right me-1"></i>Contact Us</a></li>
                        <li><a href="privacy.php"><i class="fas fa-chevron-right me-1"></i>Privacy Policy</a></li>
                        <li><a href="terms.php"><i class="fas fa-chevron-right me-1"></i>Terms of Service</a></li>
                        <li><a href="faq.php"><i class="fas fa-chevron-right me-1"></i>FAQ</a></li>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div class="col-lg-2 col-md-6 footer-section">
                    <h6 class="footer-title">Get in Touch</h6>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>+250 788 123 456</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>info@household.rw</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Kigali, Rwanda</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <span>Mon-Fri: 8AM-6PM</span>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Section -->
            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-0 mb-md-0">
                            &copy; 2024 Household Connect. All rights reserved.
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-0">
                            Made with <span class="heart">&hearts;</span> in Kigali
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="script.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });

        // Counter Animation
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-count'));
                const increment = target / 100;
                let current = 0;
                
                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        counter.textContent = Math.ceil(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                updateCounter();
            });
        }

        // Trigger counter animation when in viewport
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.disconnect();
                }
            });
        });

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            observer.observe(statsSection);
        }

        // Load featured workers
        async function loadFeaturedWorkers() {
            try {
                const response = await fetch('api/workers.php?featured=1&limit=4');
                if (response.ok) {
                    const workers = await response.json();
                    displayFeaturedWorkers(workers);
                }
            } catch (error) {
                console.error('Error loading workers:', error);
                // Display placeholder workers if API fails
                displayPlaceholderWorkers();
            }
        }

        function displayFeaturedWorkers(workers) {
            const container = document.getElementById('featured-workers');
            if (!container) return;

            container.innerHTML = workers.map(worker => `
                <div class="col-6">
                    <div class="worker-preview-card" onclick="window.location.href='worker-details.php?id=${worker.id}'">
                        <div class="worker-preview-img">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="worker-preview-info">
                            <div class="worker-name">${worker.name}</div>
                            <div class="worker-skills">
                                ${worker.skills ? worker.skills.split(',').slice(0, 2).map(skill => 
                                    `<span class="skill-badge">${skill.trim()}</span>`
                                ).join('') : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function displayPlaceholderWorkers() {
            // Show no workers available message when database is empty
            const container = document.getElementById('featured-workers');
            if (container) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Workers Available Yet</h4>
                        <p class="text-muted">Be the first to register as a worker and start finding jobs!</p>
                        <a href="register.php" class="btn btn-primary mt-2">Register as Worker</a>
                    </div>
                `;
            }
        }

        // Load workers when page loads
        document.addEventListener('DOMContentLoaded', loadFeaturedWorkers);

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (navbar) {
                if (window.scrollY > 50) {
                    navbar.style.background = 'rgba(0,0,0,0.9)';
                } else {
                    navbar.style.background = 'rgba(0,0,0,0.1)';
                }
            }
        });
    </script>
</body>
</html>