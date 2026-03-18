<?php
// Database configuration (override via environment for containers)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_USER', getenv('DB_USER') ?: 'household_app');
define('DB_PASS', getenv('DB_PASS') ?: 'Jesuslove@12');
define('DB_NAME', getenv('DB_NAME') ?: 'household_connect');

// Create database connection
try {
    $conn = new PDO("pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Application settings
define('APP_NAME', 'Household Connect');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');
define('UPLOAD_PATH', 'uploads/');

// Security settings
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_LIFETIME', 86400); // 24 hours

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Africa/Kigali');

// Start session
session_start();

require_once __DIR__ . '/lang.php';
initialize_language();

function should_inject_global_i18n() {
    if (PHP_SAPI === 'cli') {
        return false;
    }

    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($request_uri, '/api/') !== false) {
        return false;
    }

    return true;
}

function render_global_i18n_assets() {
    $payload = [
        'lang' => current_language(),
        'languages' => supported_languages(),
        'urls' => language_switch_urls(),
        'translations' => runtime_translation_dictionary(),
        'labels' => [
            'language' => t('common.language'),
        ],
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return <<<HTML
<script id="global-i18n-payload" type="application/json">{$json}</script>
<script>
(function () {
    if (window.__globalI18nInitialized) return;
    window.__globalI18nInitialized = true;

    const payloadEl = document.getElementById('global-i18n-payload');
    if (!payloadEl) return;
    const payload = JSON.parse(payloadEl.textContent || '{}');
    const lang = payload.lang || 'en';
    const dictionary = (payload.translations && payload.translations[lang]) || {};
    const urls = payload.urls || {};
    const labels = payload.labels || {};

    function translateText(text) {
        if (!text) return text;
        const trimmed = text.trim();
        if (!trimmed) return text;
        if (dictionary[trimmed]) {
            return text.replace(trimmed, dictionary[trimmed]);
        }
        return text;
    }

    function translateNode(node) {
        if (!node || node.nodeType !== Node.TEXT_NODE) return;
        if (!node.parentElement) return;
        const tag = node.parentElement.tagName;
        if (['SCRIPT', 'STYLE'].includes(tag)) return;
        node.textContent = translateText(node.textContent);
    }

    function translateAttributes(root) {
        root.querySelectorAll('input[placeholder], textarea[placeholder]').forEach(function (el) {
            const placeholder = el.getAttribute('placeholder');
            if (dictionary[placeholder]) {
                el.setAttribute('placeholder', dictionary[placeholder]);
            }
        });

        root.querySelectorAll('option').forEach(function (el) {
            const text = el.textContent.trim();
            if (dictionary[text]) {
                el.textContent = dictionary[text];
            }
        });

        root.querySelectorAll('[title]').forEach(function (el) {
            const title = el.getAttribute('title');
            if (dictionary[title]) {
                el.setAttribute('title', dictionary[title]);
            }
        });
    }

    function walk(root) {
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
        let current;
        while ((current = walker.nextNode())) {
            translateNode(current);
        }
        translateAttributes(root);
    }

    function injectSwitcher() {
        if (document.querySelector('[data-language-control="native"]')) return;
        if (document.getElementById('global-language-switcher')) return;

        const wrap = document.createElement('div');
        wrap.id = 'global-language-switcher';
        wrap.style.position = 'fixed';
        wrap.style.top = '14px';
        wrap.style.right = '14px';
        wrap.style.zIndex = '9999';
        wrap.style.fontFamily = 'system-ui, sans-serif';

        const select = document.createElement('select');
        select.setAttribute('aria-label', labels.language || 'Language');
        select.style.background = 'rgba(255,255,255,0.96)';
        select.style.color = '#111';
        select.style.border = '1px solid rgba(0,0,0,0.12)';
        select.style.borderRadius = '999px';
        select.style.padding = '8px 14px';
        select.style.boxShadow = '0 8px 20px rgba(0,0,0,0.12)';
        select.style.fontSize = '14px';

        Object.entries(payload.languages || {}).forEach(function(entry) {
            const code = entry[0];
            const info = entry[1];
            const option = document.createElement('option');
            option.value = urls[code] || '#';
            option.textContent = info.label || code.toUpperCase();
            option.selected = code === lang;
            select.appendChild(option);
        });

        select.addEventListener('change', function () {
            if (this.value) {
                window.location.href = this.value;
            }
        });

        wrap.appendChild(select);
        document.body.appendChild(wrap);
    }

    document.documentElement.setAttribute('lang', lang);
    walk(document.body);
    injectSwitcher();

    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    walk(node);
                } else if (node.nodeType === Node.TEXT_NODE) {
                    translateNode(node);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
})();
</script>
HTML;
}

if (should_inject_global_i18n()) {
    ob_start(function ($buffer) {
        if (stripos($buffer, '<html') !== false && stripos($buffer, '</body>') !== false) {
            $buffer = preg_replace('/<html([^>]*)lang="[^"]*"([^>]*)>/i', '<html$1lang="' . current_language() . '"$2>', $buffer, 1, $count);
            if (!$count) {
                $buffer = preg_replace('/<html([^>]*)>/i', '<html$1 lang="' . current_language() . '">', $buffer, 1);
            }
            $buffer = str_ireplace('</body>', render_global_i18n_assets() . '</body>', $buffer);
        }
        return $buffer;
    });
}

// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_token() {
    return bin2hex(random_bytes(32));
}

function verify_token($token, $stored_token) {
    return hash_equals($token, $stored_token);
}

function format_currency($amount) {
    return 'RWF ' . number_format($amount, 0, '.', ',');
}

function format_date($date) {
    return date('d M Y', strtotime($date));
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function require_admin() {
    if (!is_logged_in()) {
        redirect('index.php');
    }

    if (!is_admin()) {
        redirect('dashboard.php');
    }
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function json_response($data, $status = 200) {
    header_remove();
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit();
}

function app_base_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? parse_url(APP_URL, PHP_URL_HOST) ?? 'localhost';
    $port = parse_url(APP_URL, PHP_URL_PORT);
    $path = parse_url(APP_URL, PHP_URL_PATH) ?? '';

    if (!empty($_SERVER['HTTP_HOST'])) {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $root = trim(dirname(dirname($scriptName)), '/.');
        $path = $root ? '/' . $root : '';
    } elseif ($port && strpos($host, ':') === false) {
        $host .= ':' . $port;
    }

    return rtrim($scheme . '://' . $host . $path, '/');
}

function ensure_password_reset_tokens_table() {
    static $ensured = false;

    if ($ensured) {
        return;
    }

    global $conn;

    $conn->exec("
        CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            token_hash VARCHAR(255) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            used_at TIMESTAMP NULL,
            requested_ip VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $conn->exec("CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_hash ON password_reset_tokens(token_hash)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_user_id ON password_reset_tokens(user_id)");

    $ensured = true;
}

function password_reset_token_store_path() {
    $directory = __DIR__ . '/storage';
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    return $directory . '/password_reset_tokens.json';
}

function read_password_reset_tokens() {
    $path = password_reset_token_store_path();
    if (!file_exists($path)) {
        return [];
    }

    $tokens = json_decode((string) file_get_contents($path), true);
    if (!is_array($tokens)) {
        return [];
    }

    $now = time();
    return array_values(array_filter($tokens, function ($item) use ($now) {
        $expiresAt = isset($item['expires_at']) ? strtotime((string) $item['expires_at']) : 0;
        $usedAt = $item['used_at'] ?? null;
        return $usedAt || $expiresAt > ($now - 86400);
    }));
}

function write_password_reset_tokens(array $tokens) {
    file_put_contents(
        password_reset_token_store_path(),
        json_encode(array_values($tokens), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
}

function create_password_reset_token($userId) {
    $plainToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $plainToken);
    $tokens = read_password_reset_tokens();
    $nowIso = gmdate('c');

    foreach ($tokens as &$token) {
        if ((int) ($token['user_id'] ?? 0) === (int) $userId && empty($token['used_at'])) {
            $token['used_at'] = $nowIso;
        }
    }
    unset($token);

    $tokens[] = [
        'user_id' => (int) $userId,
        'token_hash' => $tokenHash,
        'expires_at' => gmdate('c', time() + 3600),
        'used_at' => null,
        'created_at' => $nowIso,
    ];

    write_password_reset_tokens($tokens);

    return $plainToken;
}

function find_password_reset_token($plainToken) {
    $tokenHash = hash('sha256', $plainToken);
    $tokens = array_reverse(read_password_reset_tokens());
    $now = time();

    foreach ($tokens as $token) {
        if (($token['token_hash'] ?? '') !== $tokenHash) {
            continue;
        }

        if (!empty($token['used_at'])) {
            return null;
        }

        $expiresAt = isset($token['expires_at']) ? strtotime((string) $token['expires_at']) : 0;
        if ($expiresAt <= $now) {
            return null;
        }

        return $token;
    }

    return null;
}

function consume_password_reset_tokens($userId) {
    $tokens = read_password_reset_tokens();
    $nowIso = gmdate('c');

    foreach ($tokens as &$token) {
        if ((int) ($token['user_id'] ?? 0) === (int) $userId && empty($token['used_at'])) {
            $token['used_at'] = $nowIso;
        }
    }
    unset($token);

    write_password_reset_tokens($tokens);
}

function load_email_templates_extension() {
    static $loaded = false;

    if ($loaded) {
        return isset($GLOBALS['email_templates']);
    }

    $extensionConfig = __DIR__ . '/extensions/config.php';
    if (!file_exists($extensionConfig)) {
        $loaded = true;
        return false;
    }

    require_once $extensionConfig;
    $emailExtension = __DIR__ . '/extensions/email-templates/email-templates.php';
    if (file_exists($emailExtension)) {
        require_once $emailExtension;
    }

    if (class_exists('EmailTemplates') && !isset($GLOBALS['email_templates'])) {
        $GLOBALS['email_templates'] = new EmailTemplates();
    }

    $loaded = true;
    return isset($GLOBALS['email_templates']);
}

function send_password_reset_email($email, $userName, $resetUrl) {
    if (load_email_templates_extension() && isset($GLOBALS['email_templates'])) {
        $result = $GLOBALS['email_templates']->send_template_email('password_reset', $email, [
            'user_name' => $userName,
            'app_name' => APP_NAME,
            'reset_url' => $resetUrl,
            'expiry_hours' => 1,
        ]);

        if (!empty($result['success'])) {
            return true;
        }
    }

    $subject = APP_NAME . ' Password Reset';
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($userName ?: 'User', ENT_QUOTES, 'UTF-8');
    $message = "
        <html>
        <body style=\"font-family: Arial, sans-serif; color: #222;\">
            <h2 style=\"margin-bottom: 12px;\">" . APP_NAME . "</h2>
            <p>Hello {$safeName},</p>
            <p>We received a request to reset your password.</p>
            <p><a href=\"{$safeUrl}\" style=\"display:inline-block;padding:12px 18px;background:#111;color:#fff;text-decoration:none;border-radius:8px;\">Reset Password</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you did not request this, you can ignore this email.</p>
        </body>
        </html>
    ";

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: noreply@koziconnect.local',
    ];

    return @mail($email, $subject, $message, implode("\r\n", $headers));
}

function validate_required($fields, $data) {
    $errors = [];
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    return $errors;
}

function upload_file($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file parameters'];
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'File too large'];
        default:
            return ['success' => false, 'message' => 'Unknown upload error'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mime = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    $extension = array_search($mime_type, $allowed_mime, true);
    
    if ($extension === false || !in_array($extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $filename = uniqid() . '.' . $extension;
    $upload_path = UPLOAD_PATH . $filename;
    
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0777, true);
    }
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
    
    return ['success' => true, 'filename' => $filename];
}
?>
