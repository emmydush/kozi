<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Kigali</title>
    <meta name="description" content="Create your account and find trusted household workers in Kigali">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles.css">
    
    <style>
        :root {
            --primary-color: #000000;
            --secondary-color: #333333;
            --success-color: #000000;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --text-dark: #000000;
            --text-light: #666666;
            --bg-light: #f8f9fa;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            position: relative;
            overflow: hidden;
        }

        .register-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .register-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
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

        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 600px;
            width: 90%;
            margin: 2rem auto;
            position: relative;
            z-index: 10;
        }

        .register-header {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
        }

        .register-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .register-header-content {
            position: relative;
            z-index: 1;
        }

        .register-logo {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .register-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .register-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .register-body {
            padding: 2.5rem 2rem;
        }

        /* Multi-Step Form Styles */
        .progress-indicator {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
            position: relative;
        }

        .progress-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 25%;
            right: 25%;
            height: 2px;
            background: rgba(255, 255, 255, 0.3);
            z-index: 1;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }

        .step.active .step-number {
            background: white;
            color: #000000;
            transform: scale(1.1);
        }

        .step.completed .step-number {
            background: #28a745;
            color: white;
        }

        .step-title {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .step.active .step-title {
            color: white;
            font-weight: 600;
        }

        .form-step {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .form-step.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .step-heading {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .step-description {
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .step-buttons {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-next, .btn-prev {
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            min-height: 44px;
        }

        .btn-next {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
        }

        .btn-next:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .btn-prev {
            background: #f8f9fa;
            color: var(--text-dark);
            border: 2px solid #e9ecef;
        }

        .btn-prev:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #000000;
            box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.25);
            transform: translateY(-2px);
        }

        .form-control.is-valid {
            border-color: var(--success-color);
            background-image: none;
        }

        .form-control.is-invalid {
            border-color: var(--danger-color);
            background-image: none;
        }

        .form-floating label {
            color: var(--text-light);
        }

        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: #000000;
            box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.25);
            transform: translateY(-2px);
        }

        .btn-register {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .social-login {
            margin-top: 2rem;
        }

        .social-login-title {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 1rem;
            position: relative;
        }

        .social-login-title::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }

        .social-login-title span {
            background: white;
            padding: 0 1rem;
            position: relative;
        }

        .social-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn-social {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 500;
        }

        .btn-social:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-google:hover {
            border-color: #ea4335;
            color: #ea4335;
        }

        .btn-facebook:hover {
            border-color: #1877f2;
            color: #1877f2;
        }

        .divider {
            text-align: center;
            margin: 2rem 0;
            color: var(--text-light);
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .benefits-list {
            list-style: none;
            padding: 0;
            margin-top: 2rem;
        }

        .benefits-list li {
            padding: 0.75rem 0;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .benefits-list i {
            color: white;
            font-size: 1.2rem;
            margin-top: 0.2rem;
            flex-shrink: 0;
        }

        .back-to-home {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            z-index: 100;
        }

        .back-to-home:hover {
            transform: translateX(-5px);
            color: white;
        }

        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }

        .password-strength.weak {
            background: var(--danger-color);
            width: 33%;
        }

        .password-strength.medium {
            background: var(--warning-color);
            width: 66%;
        }

        .password-strength.strong {
            background: var(--success-color);
            width: 100%;
        }

        .password-requirements {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-top: 0.5rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .requirement i {
            font-size: 0.75rem;
            color: var(--text-light);
        }

        .requirement.met i {
            color: var(--success-color);
        }

        .role-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .role-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .role-card:hover {
            border-color: #000000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .role-card.selected {
            border-color: #000000;
            background: rgba(0, 0, 0, 0.05);
        }

        .role-card input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .role-icon {
            font-size: 2rem;
            color: #000000;
            margin-bottom: 0.5rem;
        }

        .role-card.selected .role-icon {
            color: #000000;
        }

        .role-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .role-description {
            font-size: 0.875rem;
            color: var(--text-light);
        }

        @media (max-width: 768px) {
            .register-container {
                padding: 0.5rem;
            }

            .register-card {
                max-width: 100%;
                margin: 1rem auto;
                border-radius: 15px;
            }

            .register-header {
                padding: 2rem 1.5rem;
            }

            .register-body {
                padding: 2rem 1.5rem;
            }

            .register-logo {
                font-size: 1.8rem;
            }

            .register-title {
                font-size: 1.5rem;
            }

            .register-subtitle {
                font-size: 0.9rem;
            }

            .progress-indicator {
                gap: 1rem;
                margin-top: 1rem;
            }

            .step-number {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }

            .step-title {
                font-size: 0.7rem;
            }

            .step-heading {
                font-size: 1.3rem;
            }

            .step-description {
                font-size: 0.9rem;
                margin-bottom: 1.5rem;
            }

            .form-control {
                font-size: 16px; /* Prevents zoom on iOS */
                min-height: 44px;
            }

            .btn-register {
                padding: 1rem;
                font-size: 1rem;
                min-height: 48px;
            }

            .btn-next, .btn-prev {
                padding: 0.625rem 1.25rem;
                font-size: 0.9rem;
            }

            .particle {
                display: none; /* Remove particles on mobile for better performance */
            }

            .password-requirements {
                font-size: 0.75rem;
            }

            .role-card {
                padding: 1rem;
                margin-bottom: 0.5rem;
            }

            .role-icon {
                font-size: 1.5rem;
            }

            .form-floating {
                margin-bottom: 1rem;
            }

            .form-check {
                margin-bottom: 0.8rem;
            }

            .divider {
                margin: 1.5rem 0;
            }
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 0;
            }

            .register-card {
                margin: 0;
                border-radius: 0;
                min-height: 100vh;
                box-shadow: none;
            }

            .register-header {
                padding: 1.5rem 1rem;
            }

            .register-body {
                padding: 1.5rem 1rem;
            }

            .register-logo {
                font-size: 1.5rem;
            }

            .register-title {
                font-size: 1.3rem;
            }

            .register-subtitle {
                font-size: 0.85rem;
            }

            .progress-indicator {
                gap: 0.5rem;
                margin-top: 0.75rem;
            }

            .step-number {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }

            .step-title {
                font-size: 0.65rem;
            }

            .step-heading {
                font-size: 1.2rem;
            }

            .step-description {
                font-size: 0.85rem;
                margin-bottom: 1.25rem;
            }

            .form-floating {
                margin-bottom: 0.8rem;
            }

            .form-control {
                font-size: 16px;
                min-height: 44px;
                padding: 0.75rem;
            }

            .btn-register {
                padding: 0.875rem;
                font-size: 0.95rem;
                min-height: 44px;
            }

            .btn-next, .btn-prev {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }

            .step-buttons {
                flex-direction: column;
                gap: 0.75rem;
            }

            .role-card {
                padding: 0.75rem;
            }

            .role-icon {
                font-size: 1.3rem;
            }

            .role-title {
                font-size: 0.9rem;
            }

            .role-description {
                font-size: 0.75rem;
            }

            .form-check {
                margin-bottom: 0.6rem;
            }

            .form-check-label {
                font-size: 0.85rem;
            }

            .password-requirements {
                font-size: 0.7rem;
            }

            .divider {
                margin: 1rem 0;
                font-size: 0.85rem;
            }

            /* Ensure full viewport height on mobile */
            body, html {
                height: 100%;
            }

            .register-container {
                min-height: 100vh;
                display: flex;
                align-items: center;
            }

            .register-card {
                flex: 1;
                display: flex;
                flex-direction: column;
            }
        }

        @media (max-width: 360px) {
            .register-header {
                padding: 1.25rem 0.75rem;
            }

            .register-body {
                padding: 1.25rem 0.75rem;
            }

            .register-title {
                font-size: 1.2rem;
            }

            .register-subtitle {
                font-size: 0.8rem;
            }

            .progress-indicator {
                gap: 0.25rem;
            }

            .step-number {
                width: 25px;
                height: 25px;
                font-size: 0.7rem;
            }

            .step-title {
                font-size: 0.6rem;
            }

            .step-heading {
                font-size: 1.1rem;
            }

            .step-description {
                font-size: 0.8rem;
            }

            .form-control {
                padding: 0.625rem;
            }

            .btn-register {
                padding: 0.75rem;
                font-size: 0.9rem;
            }

            .btn-next, .btn-prev {
                padding: 0.4rem 0.75rem;
                font-size: 0.8rem;
            }

            .role-card {
                padding: 0.5rem;
            }

            .form-check-label {
                font-size: 0.8rem;
            }
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
            z-index: 10;
            background: white;
            padding: 0.5rem;
        }

        .password-toggle:hover {
            color: #000000;
        }

        .loading-spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #000000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 0.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn-register.loading .loading-spinner {
            display: inline-block;
        }

        .btn-register.loading .btn-text {
            opacity: 0.7;
        }

        .form-check {
            margin-bottom: 1rem;
        }

        .form-check-input:checked {
            background-color: #000000;
            border-color: #000000;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: var(--danger-color);
        }

        .valid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: var(--success-color);
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-to-home">
        <i class="fas fa-arrow-left"></i>
        Back to Home
    </a>

    <div class="register-container">
        <div class="register-particles">
            <div class="particle" style="width: 20px; height: 20px; left: 10%; top: 20%; animation-delay: 0s;"></div>
            <div class="particle" style="width: 15px; height: 15px; left: 80%; top: 60%; animation-delay: 2s;"></div>
            <div class="particle" style="width: 25px; height: 25px; left: 60%; top: 80%; animation-delay: 4s;"></div>
            <div class="particle" style="width: 18px; height: 18px; left: 30%; top: 40%; animation-delay: 1s;"></div>
            <div class="particle" style="width: 22px; height: 22px; left: 90%; top: 30%; animation-delay: 3s;"></div>
        </div>

        <div class="register-card">
            <div class="register-header">
                <div class="register-logo">
                </div>
                <h2 class="register-title">Create Account</h2>
                <p class="register-subtitle">Join thousands of families and workers in Kigali</p>
                
                <!-- Progress Indicator -->
                <div class="progress-indicator">
                    <div class="step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-title">Personal Info</div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-title">Account</div>
                    </div>
                    <div class="step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-title">Role</div>
                    </div>
                </div>
            </div>

            <div class="register-body">
                <div id="alert-container"></div>

                <form id="register-form" novalidate>
                    <!-- Step 1: Personal Information -->
                    <div class="form-step active" data-step="1">
                        <h3 class="step-heading">Personal Information</h3>
                        <p class="step-description">Tell us about yourself</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="firstName" placeholder="First Name" required>
                                    <label for="firstName">First Name</label>
                                    <div class="invalid-feedback">Please enter your first name</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="lastName" placeholder="Last Name" required>
                                    <label for="lastName">Last Name</label>
                                    <div class="invalid-feedback">Please enter your last name</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" placeholder="name@example.com" required>
                            <label for="email">Email Address</label>
                            <div class="invalid-feedback">Please enter a valid email address</div>
                        </div>

                        <div class="form-floating">
                            <input type="tel" class="form-control" id="phone" placeholder="+250 7XX XXX XXX" required>
                            <label for="phone">Phone Number</label>
                            <div class="invalid-feedback">Please enter your phone number</div>
                        </div>


                        <div class="step-buttons">
                            <button type="button" class="btn btn-next" onclick="nextStep(1)">
                                Next Step <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Account Setup -->
                    <div class="form-step" data-step="2">
                        <h3 class="step-heading">Create Your Account</h3>
                        <p class="step-description">Set up your login credentials</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating position-relative">
                                    <input type="password" class="form-control" id="password" placeholder="Password" required>
                                    <label for="password">Password</label>
                                    <span class="password-toggle" onclick="togglePassword('password')">
                                        <i class="fas fa-eye" id="password-icon"></i>
                                    </span>
                                </div>
                                <div class="password-strength" id="password-strength"></div>
                                <div class="password-requirements">
                                    <div class="requirement" id="req-length">
                                        <i class="fas fa-circle"></i> At least 8 characters
                                    </div>
                                    <div class="requirement" id="req-uppercase">
                                        <i class="fas fa-circle"></i> One uppercase letter
                                    </div>
                                    <div class="requirement" id="req-lowercase">
                                        <i class="fas fa-circle"></i> One lowercase letter
                                    </div>
                                    <div class="requirement" id="req-number">
                                        <i class="fas fa-circle"></i> One number
                                    </div>
                                </div>
                                <div class="invalid-feedback">Password does not meet requirements</div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating position-relative">
                                    <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm Password" required>
                                    <label for="confirmPassword">Confirm Password</label>
                                    <span class="password-toggle" onclick="togglePassword('confirmPassword')">
                                        <i class="fas fa-eye" id="confirm-password-icon"></i>
                                    </span>
                                </div>
                                <div class="invalid-feedback">Passwords do not match</div>
                            </div>
                        </div>

                        <div class="step-buttons">
                            <button type="button" class="btn btn-prev" onclick="prevStep(2)">
                                <i class="fas fa-arrow-left me-2"></i> Previous
                            </button>
                            <button type="button" class="btn btn-next" onclick="nextStep(2)">
                                Next Step <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Role Selection -->
                    <div class="form-step" data-step="3">
                        <h3 class="step-heading">Choose Your Role</h3>
                        <p class="step-description">How will you use our platform?</p>
                        
                        <div class="role-cards">
                            <label class="role-card">
                                <input type="radio" name="role" value="employer" required>
                                <div class="role-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="role-title">Employer</div>
                                <div class="role-description">Looking for household workers</div>
                            </label>
                            <label class="role-card">
                                <input type="radio" name="role" value="worker" required>
                                <div class="role-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="role-title">Worker</div>
                                <div class="role-description">Looking for job opportunities</div>
                            </label>
                        </div>
                        <div class="invalid-feedback">Please select your role</div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" style="color: #000000;">Terms of Service</a> and <a href="#" style="color: #000000;">Privacy Policy</a>
                            </label>
                            <div class="invalid-feedback">You must agree to the terms and conditions</div>
                        </div>

                        <div class="step-buttons">
                            <button type="button" class="btn btn-prev" onclick="prevStep(3)">
                                <i class="fas fa-arrow-left me-2"></i> Previous
                            </button>
                            <button type="submit" class="btn btn-register">
                                <span class="btn-text">Create Account</span>
                                <div class="loading-spinner"></div>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="divider">
                    Already have an account? <a href="login.php" style="color: #000000; text-decoration: none; font-weight: 600;">Sign in</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const iconId = fieldId === 'password' ? 'password-icon' : 'confirm-password-icon';
            const passwordIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        function showAlert(message, type = 'danger') {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            
            alertContainer.innerHTML = `
                <div class="alert ${alertClass} d-flex align-items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
                    ${message}
                </div>
            `;
        }

        function setLoading(loading) {
            const button = document.querySelector('.btn-register');
            const buttonText = document.querySelector('.btn-text');
            
            if (loading) {
                button.classList.add('loading');
                button.disabled = true;
                buttonText.textContent = 'Creating Account...';
            } else {
                button.classList.remove('loading');
                button.disabled = false;
                buttonText.textContent = 'Create Your Account';
            }
        }

        function validatePassword(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password)
            };

            // Update requirement indicators
            document.getElementById('req-length').classList.toggle('met', requirements.length);
            document.getElementById('req-uppercase').classList.toggle('met', requirements.uppercase);
            document.getElementById('req-lowercase').classList.toggle('met', requirements.lowercase);
            document.getElementById('req-number').classList.toggle('met', requirements.number);

            // Update password strength indicator
            const strengthBar = document.getElementById('password-strength');
            const metCount = Object.values(requirements).filter(Boolean).length;
            
            strengthBar.className = 'password-strength';
            if (metCount <= 1) {
                strengthBar.classList.add('weak');
            } else if (metCount <= 3) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }

            return metCount === 4;
        }

        // Role card selection
        document.querySelectorAll('.role-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Password validation on input
        document.getElementById('password').addEventListener('input', function() {
            validatePassword(this.value);
        });

        // Form validation and submission
        document.getElementById('register-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Clear previous validation states
            this.classList.remove('was-validated');
            document.querySelectorAll('.is-invalid, .is-valid').forEach(el => {
                el.classList.remove('is-invalid', 'is-valid');
            });

            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (!firstName || !lastName || !email) {
                showAlert('Please fill in all required fields');
                return;
            }
            
            const submitBtn = e.target.querySelector('.btn-register');
            const btnText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.loading-spinner');
            
            // Show loading state
            submitBtn.classList.add('loading');
            btnText.textContent = 'Creating Account...';
            spinner.style.display = 'inline-block';
            
            // Collect form data
            const formData = {
                name: document.getElementById('firstName').value + ' ' + document.getElementById('lastName').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                password: document.getElementById('password').value,
                role: document.querySelector('input[name="role"]:checked').value
            };
            
            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Account created successfully! Redirecting to login...', 'success');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    showAlert(result.message || 'Registration failed. Please try again.', 'danger');
                }
            } catch (error) {
                showAlert('Network error. Please check your connection and try again.', 'danger');
            } finally {
                // Reset loading state
                submitBtn.classList.remove('loading');
                btnText.textContent = 'Create Account';
                spinner.style.display = 'none';
            }
        });

        // Real-time password strength checking
        document.getElementById('password').addEventListener('input', function() {
            validatePassword(this.value);
        });

        // Clear validation on input
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });

        // Initialize
        showStep(1);

        // Multi-step form functions
        function showStep(stepNumber) {
            // Hide all steps
            document.querySelectorAll('.form-step').forEach(step => {
                step.classList.remove('active');
            });
            
            // Show current step
            document.querySelector(`.form-step[data-step="${stepNumber}"]`).classList.add('active');
            
            // Update progress indicator
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('active');
                if (parseInt(step.dataset.step) <= stepNumber) {
                    step.classList.add('completed');
                }
            });
            document.querySelector(`.step[data-step="${stepNumber}"]`).classList.add('active');
        }

        function nextStep(currentStep) {
            // Validate current step
            const stepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
            const requiredFields = stepElement.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                }
            });
            
            if (isValid) {
                showStep(currentStep + 1);
            }
        }

        function prevStep(currentStep) {
            showStep(currentStep - 1);
        }
    </script>
</body>
</html>
