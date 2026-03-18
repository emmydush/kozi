<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(current_language()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('login.heading')); ?> | Kigali</title>
    <meta name="description" content="<?php echo htmlspecialchars(t('login.subtitle')); ?>">
    
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

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .login-particles {
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

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 450px;
            width: 90%;
            position: relative;
            z-index: 10;
        }

        .login-left {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .login-left-content {
            position: relative;
            z-index: 1;
        }

        .login-right {
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-logo {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: var(--text-light);
            margin-bottom: 2rem;
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

        .form-floating label {
            color: var(--text-light);
        }

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
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

        .feature-list {
            list-style: none;
            padding: 0;
            margin-top: 2rem;
        }

        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feature-list i {
            color: #28a745;
            font-size: 1.2rem;
        }

        
        @media (max-width: 768px) {
            .login-card {
                max-width: 95%;
                margin: 1rem;
                border-radius: 15px;
            }

            .login-right {
                padding: 2rem 1.5rem;
            }

            .login-logo {
                font-size: 1.8rem;
                justify-content: center;
            }

            .login-title {
                font-size: 1.5rem;
            }

            .login-subtitle {
                font-size: 0.9rem;
            }

            .form-control {
                font-size:16px; /* Prevents zoom on iOS */
            }

            .btn-login {
                padding: 1.2rem;
                font-size: 1rem;
            }

            .particle {
                display: none; /* Remove particles on mobile for better performance */
            }

            .login-container {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                margin: 0.5rem;
                border-radius: 10px;
                width: 98%;
            }

            .login-right {
                padding: 1.5rem 1rem;
            }

            .login-logo {
                font-size: 1.5rem;
            }

            .login-title {
                font-size: 1.3rem;
            }

            .form-floating {
                margin-bottom: 1rem;
            }

            .btn-login {
                padding: 1rem;
                font-size: 0.95rem;
            }

            .login-subtitle {
                font-size: 0.85rem;
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

        .btn-login.loading .loading-spinner {
            display: inline-block;
        }

        .btn-login.loading .btn-text {
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 20;">
        <div class="dropdown" data-language-control="native">
            <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-globe me-1"></i><?php echo strtoupper(htmlspecialchars(current_language())); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach (supported_languages() as $code => $language): ?>
                    <li><a class="dropdown-item <?php echo current_language() === $code ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(language_switch_url($code)); ?>"><?php echo htmlspecialchars($language['label']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    
    <div class="login-container">
        <div class="login-particles">
            <div class="particle" style="width: 20px; height: 20px; left: 10%; top: 20%; animation-delay: 0s;"></div>
            <div class="particle" style="width: 15px; height: 15px; left: 80%; top: 60%; animation-delay: 2s;"></div>
            <div class="particle" style="width: 25px; height: 25px; left: 60%; top: 80%; animation-delay: 4s;"></div>
            <div class="particle" style="width: 18px; height: 18px; left: 30%; top: 40%; animation-delay: 1s;"></div>
            <div class="particle" style="width: 22px; height: 22px; left: 90%; top: 30%; animation-delay: 3s;"></div>
        </div>

        <div class="login-card">
            <div class="login-right">
                <div class="text-center mb-4">
                    <div class="login-logo justify-content-center">
                    </div>
                    <h1 class="login-title"><?php echo htmlspecialchars(t('login.heading')); ?></h1>
                    <p class="login-subtitle"><?php echo htmlspecialchars(t('login.subtitle')); ?></p>
                </div>

                <div id="alert-container"></div>

                <form id="login-form">
                    <div class="form-floating">
                        <input type="tel" class="form-control" id="phone" placeholder="+250 7XX XXX XXX" required>
                        <label for="phone"><?php echo htmlspecialchars(t('common.phone_number')); ?></label>
                    </div>

                    <div class="form-floating position-relative">
                        <input type="password" class="form-control" id="password" placeholder="Password" required>
                        <label for="password"><?php echo htmlspecialchars(t('common.password')); ?></label>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember">
                            <label class="form-check-label" for="remember">
                                <?php echo htmlspecialchars(t('common.remember_me')); ?>
                            </label>
                        </div>
                        <a href="forgot-password.php" class="text-decoration-none" style="color: #000000;"><?php echo htmlspecialchars(t('common.forgot_password')); ?></a>
                    </div>

                    <button type="submit" class="btn btn-login">
                        <span class="btn-text"><?php echo htmlspecialchars(t('login.submit')); ?></span>
                        <div class="loading-spinner"></div>
                    </button>
                </form>

                <div class="divider">
                    <?php echo htmlspecialchars(t('login.no_account')); ?> <a href="register.php" style="color: #000000; text-decoration: none; font-weight: 600;"><?php echo htmlspecialchars(t('common.sign_up')); ?></a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
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
            const button = document.querySelector('.btn-login');
            const buttonText = document.querySelector('.btn-text');
            
            if (loading) {
                button.classList.add('loading');
                button.disabled = true;
                buttonText.textContent = '<?php echo addslashes(t('login.logging_in')); ?>';
            } else {
                button.classList.remove('loading');
                button.disabled = false;
                buttonText.textContent = '<?php echo addslashes(t('login.submit')); ?>';
            }
        }

        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const phone = document.getElementById('phone').value;
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;
            
            // Clear previous alerts
            document.getElementById('alert-container').innerHTML = '';
            
            // Basic validation
            if (!phone || !password) {
                showAlert('<?php echo addslashes(t('login.fill_all')); ?>');
                return;
            }
            
            setLoading(true);
            
            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ phone, password, remember })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('<?php echo addslashes(t('login.success')); ?>', 'success');
                    
                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = result.redirect || 'dashboard.php';
                    }, 1500);
                } else {
                    showAlert(result.message || '<?php echo addslashes(t('login.failed')); ?>');
                }
            } catch (error) {
                console.error('Login error:', error);
                showAlert('<?php echo addslashes(t('login.network')); ?>');
            } finally {
                setLoading(false);
            }
        });

        // Add input animations
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });
    </script>
</body>
</html>
