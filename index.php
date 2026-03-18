<?php
require_once 'config.php';
$language_options = supported_languages();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(current_language()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('index.hero_title')); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(t('index.hero_subtitle')); ?>">
    
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

        /* Modern Footer */
        .site-footer {
            position: relative;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,0.08), transparent 30%),
                linear-gradient(135deg, #050505 0%, #111111 48%, #1c1c1c 100%);
            color: rgba(255, 255, 255, 0.86);
            padding: 5rem 0 1.5rem;
            overflow: hidden;
        }

        .site-footer::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: 0.25;
            pointer-events: none;
        }

        .site-footer > .container {
            position: relative;
            z-index: 1;
        }

        .footer-top-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 28px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(14px);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.25);
        }

        .footer-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.85rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            color: #f4f4f4;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            margin-bottom: 1rem;
        }

        .footer-lead {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1.15;
            color: #ffffff;
            margin-bottom: 0.9rem;
            max-width: 10ch;
        }

        .footer-copy {
            color: rgba(255, 255, 255, 0.72);
            margin-bottom: 0;
            max-width: 560px;
        }

        .footer-stat-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .footer-stat {
            padding: 1.1rem 1.2rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .footer-stat strong {
            display: block;
            font-size: 1.4rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.25rem;
        }

        .footer-stat span {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.92rem;
        }

        .footer-brand-block {
            padding-right: 1.5rem;
        }

        .footer-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
            color: #ffffff;
            text-decoration: none;
            font-size: 1.25rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .footer-brand-mark {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #ffffff 0%, #cfcfcf 100%);
            color: #111111;
            box-shadow: 0 12px 30px rgba(255,255,255,0.15);
        }

        .footer-description {
            color: rgba(255, 255, 255, 0.68);
            max-width: 360px;
            line-height: 1.75;
            margin-bottom: 1.5rem;
        }

        .footer-socials {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .footer-socials a {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }

        .footer-socials a:hover {
            transform: translateY(-3px);
            background: #ffffff;
            color: #111111;
        }

        .footer-heading {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 1.1rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li + li {
            margin-top: 0.75rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.74);
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .footer-links a:hover {
            color: #ffffff;
            padding-left: 0.25rem;
        }

        .footer-contact-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 1.4rem;
            margin-top: 1rem;
        }

        .footer-contact-item {
            display: flex;
            gap: 0.9rem;
            align-items: flex-start;
        }

        .footer-contact-item + .footer-contact-item {
            margin-top: 1rem;
        }

        .footer-contact-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            flex-shrink: 0;
        }

        .footer-contact-item span {
            display: block;
            color: rgba(255, 255, 255, 0.55);
            font-size: 0.82rem;
            margin-bottom: 0.15rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .footer-contact-item strong,
        .footer-contact-item a {
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-contact-item a:hover {
            color: #d8d8d8;
        }

        .footer-bottom {
            margin-top: 2.5rem;
            padding-top: 1.4rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-bottom p,
        .footer-bottom a {
            color: rgba(255, 255, 255, 0.68);
            text-decoration: none;
            margin-bottom: 0;
        }

        .footer-bottom a:hover {
            color: #ffffff;
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
                flex-direction: row;
                gap: 1rem;
                justify-content: center;
            }
            
            .hero-buttons .btn {
                padding: 15px 25px;
                font-size: 1rem;
                width: auto;
                justify-content: center;
                min-width: 150px;
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

            .homepage-nav-actions {
                gap: 0.5rem;
            }

            .homepage-language-switcher {
                margin: 0;
            }

            .sidebar {
                width: 280px;
            }

            .footer-top-card {
                padding: 1.5rem;
            }

            .footer-lead {
                font-size: 1.8rem;
                max-width: none;
            }

            .footer-brand-block {
                padding-right: 0;
                margin-bottom: 2rem;
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

            .footer-stat-grid {
                grid-template-columns: 1fr;
            }

            .footer-lead {
                font-size: 1.55rem;
            }

            /* Hide less important elements on very small screens */
            .hero-section::before {
                opacity: 0.1;
            }
        }

        /* Loading Animation */

        .homepage-language-switcher .dropdown-toggle {
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.08);
            color: #fff;
            border-radius: 999px;
            padding: 0.55rem 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-height: 44px;
        }

        .homepage-language-switcher .dropdown-toggle:hover,
        .homepage-language-switcher .dropdown-toggle:focus {
            background: rgba(255,255,255,0.16);
            color: #fff;
            border-color: rgba(255,255,255,0.35);
        }

        .homepage-language-switcher .dropdown-menu {
            border: none;
            border-radius: 16px;
            padding: 0.5rem;
            min-width: 190px;
            box-shadow: 0 18px 40px rgba(0,0,0,0.18);
        }

        .homepage-language-switcher .dropdown-item {
            border-radius: 12px;
            min-height: 42px;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .homepage-language-switcher .dropdown-item.active,
        .homepage-language-switcher .dropdown-item:active {
            background: #111;
            color: #fff;
        }

        .homepage-nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-left: auto;
        }

        .homepage-nav-actions .homepage-language-switcher {
            margin: 0;
        }
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
                    <h5 class="text-white"><?php echo htmlspecialchars(t('common.menu')); ?></h5>
                    <button class="btn btn-sm btn-light" onclick="toggleSidebar()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> <?php echo htmlspecialchars(t('common.dashboard')); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="profile.php">
                            <i class="fas fa-user me-2"></i> <?php echo htmlspecialchars(t('common.profile')); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="workers.php">
                            <i class="fas fa-users me-2"></i> <?php echo htmlspecialchars(t('common.workers')); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="jobs.php">
                            <i class="fas fa-briefcase me-2"></i> <?php echo htmlspecialchars(t('common.jobs')); ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="messages.php">
                            <i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars(t('common.messages')); ?>
                        </a>
                    </li>
                    <?php if ($_SESSION['user_role'] === 'worker'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="earnings.php">
                            <i class="fas fa-money-bill-wave me-2"></i> <?php echo htmlspecialchars(t('nav.earnings')); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="help.php">
                            <i class="fas fa-question-circle me-2"></i> <?php echo htmlspecialchars(t('common.help')); ?>
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
                <div class="homepage-nav-actions">
                    <div class="nav-item dropdown homepage-language-switcher" id="homepage-language-dropdown" data-language-control="native">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-globe"></i>
                            <span><?php echo strtoupper(htmlspecialchars(current_language())); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($language_options as $code => $language): ?>
                                <li>
                                    <a class="dropdown-item <?php echo current_language() === $code ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(language_switch_url($code)); ?>">
                                        <?php echo htmlspecialchars($language['label']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#services"><?php echo htmlspecialchars(t('index.nav_services')); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#workers"><?php echo htmlspecialchars(t('index.nav_workers')); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#how-it-works"><?php echo htmlspecialchars(t('index.nav_how')); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><?php echo htmlspecialchars(t('common.login')); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-success text-white px-3 ms-2" href="register.php"><?php echo htmlspecialchars(t('common.register')); ?></a>
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
                    <h1><?php echo htmlspecialchars(t('index.hero_title')); ?></h1>
                    <p class="lead"><?php echo htmlspecialchars(t('index.hero_subtitle')); ?></p>
                    <div class="hero-buttons d-flex flex-wrap">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="jobs.php" class="btn btn-hero-primary">
                                <i class="fas fa-search"></i> <?php echo htmlspecialchars(t('index.hero_find_workers')); ?>
                            </a>
                            <a href="post-job.php" class="btn btn-hero-outline">
                                <i class="fas fa-plus"></i> <?php echo htmlspecialchars(t('index.hero_post_job')); ?>
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-hero-primary">
                                <i class="fas fa-user-plus"></i> <?php echo htmlspecialchars(t('index.hero_get_started')); ?>
                            </a>
                            <a href="#workers" class="btn btn-hero-outline">
                                <i class="fas fa-search"></i> <?php echo htmlspecialchars(t('index.hero_browse_workers')); ?>
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
                        <div class="stat-label"><?php echo htmlspecialchars(t('index.stats_workers')); ?></div>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <div class="stat-number" data-count="1200">0</div>
                        <div class="stat-label"><?php echo htmlspecialchars(t('index.stats_families')); ?></div>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-card">
                        <div class="stat-number" data-count="3500">0</div>
                        <div class="stat-label"><?php echo htmlspecialchars(t('index.stats_jobs')); ?></div>
                    </div>
                </div>
                <div class="col-md-3 col-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-card">
                        <div class="stat-number" data-count="98">0</div>
                        <div class="stat-label"><?php echo htmlspecialchars(t('index.stats_satisfaction')); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5" data-aos="fade-up">
                <h2 class="display-4 fw-bold"><?php echo htmlspecialchars(t('index.services_title')); ?></h2>
                <p class="lead text-muted"><?php echo htmlspecialchars(t('index.services_subtitle')); ?></p>
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
                            <h4><?php echo htmlspecialchars(t('index.service_cleaning_title')); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars(t('index.service_cleaning_text')); ?></p>
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
                            <h4><?php echo htmlspecialchars(t('index.service_cooking_title')); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars(t('index.service_cooking_text')); ?></p>
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
                            <h4><?php echo htmlspecialchars(t('index.service_childcare_title')); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars(t('index.service_childcare_text')); ?></p>
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
                            <h4><?php echo htmlspecialchars(t('index.service_elder_title')); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars(t('index.service_elder_text')); ?></p>
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
                            <h4><?php echo htmlspecialchars(t('index.service_garden_title')); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars(t('index.service_garden_text')); ?></p>
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
                            <h4><?php echo htmlspecialchars(t('index.service_driver_title')); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars(t('index.service_driver_text')); ?></p>
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
                    <h2 class="display-4 fw-bold"><?php echo htmlspecialchars(t('index.featured_title')); ?></h2>
                    <p class="lead text-muted"><?php echo htmlspecialchars(t('index.featured_subtitle')); ?></p>
                    <a href="workers.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-arrow-right me-2"></i><?php echo htmlspecialchars(t('index.view_all_workers')); ?>
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
                <h2 class="display-4 fw-bold"><?php echo htmlspecialchars(t('index.how_title')); ?></h2>
                <p class="lead text-muted"><?php echo htmlspecialchars(t('index.how_subtitle')); ?></p>
            </div>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="text-center">
                        <div class="feature-icon mx-auto mb-3">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h4><?php echo htmlspecialchars(t('index.how_step1_title')); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars(t('index.how_step1_text')); ?></p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-center">
                        <div class="feature-icon mx-auto mb-3">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4><?php echo htmlspecialchars(t('index.how_step2_title')); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars(t('index.how_step2_text')); ?></p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="text-center">
                        <div class="feature-icon mx-auto mb-3">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h4><?php echo htmlspecialchars(t('index.how_step3_title')); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars(t('index.how_step3_text')); ?></p>
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
                    <h2><?php echo htmlspecialchars(t('index.cta_title')); ?></h2>
                    <p><?php echo htmlspecialchars(t('index.cta_text')); ?></p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="jobs.php" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-search me-2"></i><?php echo htmlspecialchars(t('index.cta_browse_workers')); ?>
                        </a>
                        <a href="post-job.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-plus me-2"></i><?php echo htmlspecialchars(t('index.cta_post_job')); ?>
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-user-plus me-2"></i><?php echo htmlspecialchars(t('index.cta_get_started')); ?>
                        </a>
                        <a href="login.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i><?php echo htmlspecialchars(t('common.login')); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-top-card" data-aos="fade-up">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <div class="footer-kicker">
                            <i class="fas fa-shield-heart"></i>
                            <?php echo htmlspecialchars(t('index.footer_kicker')); ?>
                        </div>
                        <h2 class="footer-lead"><?php echo htmlspecialchars(t('index.footer_lead')); ?></h2>
                        <p class="footer-copy">
                            <?php echo htmlspecialchars(t('index.footer_copy')); ?>
                        </p>
                    </div>
                    <div class="col-lg-5">
                        <div class="footer-stat-grid">
                            <div class="footer-stat">
                                <strong><?php echo htmlspecialchars(t('index.footer_stat_1_title')); ?></strong>
                                <span><?php echo htmlspecialchars(t('index.footer_stat_1_text')); ?></span>
                            </div>
                            <div class="footer-stat">
                                <strong><?php echo htmlspecialchars(t('index.footer_stat_2_title')); ?></strong>
                                <span><?php echo htmlspecialchars(t('index.footer_stat_2_text')); ?></span>
                            </div>
                            <div class="footer-stat">
                                <strong><?php echo htmlspecialchars(t('index.footer_stat_3_title')); ?></strong>
                                <span><?php echo htmlspecialchars(t('index.footer_stat_3_text')); ?></span>
                            </div>
                            <div class="footer-stat">
                                <strong><?php echo htmlspecialchars(t('index.footer_stat_4_title')); ?></strong>
                                <span><?php echo htmlspecialchars(t('index.footer_stat_4_text')); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="footer-brand-block">
                        <a href="index.php" class="footer-brand">
                            <span class="footer-brand-mark">
                                <i class="fas fa-house-user"></i>
                            </span>
                            KOZI CONNECT
                        </a>
                        <p class="footer-description">
                            <?php echo htmlspecialchars(t('index.footer_description')); ?>
                        </p>
                        <div class="footer-socials">
                            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fab fa-x-twitter"></i></a>
                            <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-2">
                    <h6 class="footer-heading"><?php echo htmlspecialchars(t('index.footer_explore')); ?></h6>
                    <ul class="footer-links">
                        <li><a href="index.php"><?php echo htmlspecialchars(t('common.home')); ?></a></li>
                        <li><a href="workers.php"><?php echo htmlspecialchars(t('index.hero_find_workers')); ?></a></li>
                        <li><a href="jobs.php"><?php echo htmlspecialchars(t('common.jobs')); ?></a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="dashboard.php"><?php echo htmlspecialchars(t('common.dashboard')); ?></a></li>
                    </ul>
                </div>

                <div class="col-sm-6 col-lg-2">
                    <h6 class="footer-heading"><?php echo htmlspecialchars(t('index.footer_services')); ?></h6>
                    <ul class="footer-links">
                        <li><a href="#services">House Cleaning</a></li>
                        <li><a href="#services">Cooking</a></li>
                        <li><a href="#services">Childcare</a></li>
                        <li><a href="#services">Elder Care</a></li>
                        <li><a href="#services">Gardening</a></li>
                    </ul>
                </div>

                <div class="col-sm-6 col-lg-2">
                    <h6 class="footer-heading"><?php echo htmlspecialchars(t('index.footer_support')); ?></h6>
                    <ul class="footer-links">
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>

                <div class="col-lg-4">
                    <h6 class="footer-heading"><?php echo htmlspecialchars(t('index.footer_contact')); ?></h6>
                    <div class="footer-contact-card">
                        <div class="footer-contact-item">
                            <div class="footer-contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <span><?php echo htmlspecialchars(t('index.footer_call')); ?></span>
                                <a href="tel:+250788123456">+250 788 123 456</a>
                            </div>
                        </div>
                        <div class="footer-contact-item">
                            <div class="footer-contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <span><?php echo htmlspecialchars(t('index.footer_email')); ?></span>
                                <a href="mailto:info@household.rw">info@household.rw</a>
                            </div>
                        </div>
                        <div class="footer-contact-item">
                            <div class="footer-contact-icon">
                                <i class="fas fa-location-dot"></i>
                            </div>
                            <div>
                                <span><?php echo htmlspecialchars(t('index.footer_location')); ?></span>
                                <strong>Kigali, Rwanda</strong>
                            </div>
                        </div>
                        <div class="footer-contact-item">
                            <div class="footer-contact-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <span><?php echo htmlspecialchars(t('index.footer_hours')); ?></span>
                                <strong><?php echo htmlspecialchars(t('index.footer_hours_value')); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p>
                            &copy; 2024 <?php echo htmlspecialchars(t('index.footer_copyright')); ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p>
                            <a href="privacy.php"><?php echo htmlspecialchars(t('common.privacy')); ?></a> / <a href="terms.php"><?php echo htmlspecialchars(t('common.terms')); ?></a> / <?php echo htmlspecialchars(t('index.footer_bottom_tag')); ?>
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
                        <h4 class="text-muted"><?php echo addslashes(t('index.no_workers_title')); ?></h4>
                        <p class="text-muted"><?php echo addslashes(t('index.no_workers_text')); ?></p>
                        <a href="register.php" class="btn btn-primary mt-2"><?php echo addslashes(t('index.register_worker')); ?></a>
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
