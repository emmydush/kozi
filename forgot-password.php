<?php
require_once 'config.php';

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
    <title><?php echo htmlspecialchars(t('forgot.heading')); ?> | KOZI</title>
    <meta name="description" content="<?php echo htmlspecialchars(t('forgot.subtitle')); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }

        .auth-card {
            width: min(100%, 460px);
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 22px 60px rgba(0, 0, 0, 0.28);
            padding: 2rem;
        }

        .auth-icon {
            width: 68px;
            height: 68px;
            border-radius: 20px;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #111111, #404040);
            color: #fff;
            font-size: 1.5rem;
        }

        .form-control {
            min-height: 50px;
            border-radius: 14px;
            border: 1px solid #d9dee5;
            padding: 0.9rem 1rem;
        }

        .form-control:focus {
            border-color: #111;
            box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.12);
        }

        .btn-dark {
            min-height: 50px;
            border-radius: 14px;
            font-weight: 600;
        }

        .alert {
            border-radius: 14px;
        }

        .top-language {
            position: fixed;
            top: 14px;
            right: 14px;
            z-index: 20;
        }
    </style>
</head>
<body>
    <div class="dropdown top-language" data-language-control="native">
        <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="fas fa-globe me-1"></i><?php echo strtoupper(htmlspecialchars(current_language())); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <?php foreach (supported_languages() as $code => $language): ?>
                <li><a class="dropdown-item <?php echo current_language() === $code ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(language_switch_url($code)); ?>"><?php echo htmlspecialchars($language['label']); ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="auth-card">
        <div class="text-center mb-4">
            <div class="auth-icon"><i class="fas fa-key"></i></div>
            <h1 class="h3 mb-2"><?php echo htmlspecialchars(t('forgot.heading')); ?></h1>
            <p class="text-muted mb-0"><?php echo htmlspecialchars(t('forgot.subtitle')); ?></p>
        </div>

        <div id="alert-container"></div>

        <form id="forgot-password-form">
            <div class="mb-3">
                <label for="identifier" class="form-label"><?php echo htmlspecialchars(t('forgot.identifier')); ?></label>
                <input type="text" class="form-control" id="identifier" placeholder="<?php echo htmlspecialchars(t('forgot.identifier_placeholder')); ?>" required>
            </div>

            <button type="submit" class="btn btn-dark w-100" id="submit-btn">
                <span class="btn-label"><?php echo htmlspecialchars(t('forgot.submit')); ?></span>
            </button>

            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none text-dark fw-semibold"><?php echo htmlspecialchars(t('forgot.back_login')); ?></a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showAlert(message, type) {
            document.getElementById('alert-container').innerHTML = `
                <div class="alert alert-${type === 'success' ? 'success' : 'danger'}">
                    ${message}
                </div>
            `;
        }

        function setLoading(loading) {
            const button = document.getElementById('submit-btn');
            const label = button.querySelector('.btn-label');
            button.disabled = loading;
            label.textContent = loading
                ? '<?php echo addslashes(t('forgot.submitting')); ?>'
                : '<?php echo addslashes(t('forgot.submit')); ?>';
        }

        document.getElementById('forgot-password-form').addEventListener('submit', async function (event) {
            event.preventDefault();

            const identifier = document.getElementById('identifier').value.trim();
            if (!identifier) {
                showAlert('<?php echo addslashes(t('forgot.invalid_identifier')); ?>', 'danger');
                return;
            }

            setLoading(true);

            try {
                const response = await fetch('api/forgot-password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier })
                });

                const result = await response.json();
                showAlert(result.message || '<?php echo addslashes(t('forgot.success')); ?>', result.success ? 'success' : 'danger');
                if (result.success) {
                    this.reset();
                }
            } catch (error) {
                showAlert('<?php echo addslashes(t('auth.reset_request_failed')); ?>', 'danger');
            } finally {
                setLoading(false);
            }
        });
    </script>
</body>
</html>
