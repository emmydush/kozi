<?php
// Extension Pack Configuration
define('EXTENSIONS_PATH', __DIR__);
define('EXTENSIONS_URL', APP_URL . '/extensions');

// Extension settings
$extensions = [
    'notifications' => [
        'enabled' => true,
        'version' => '1.0.0',
        'dependencies' => ['database'],
        'settings' => [
            'email_enabled' => true,
            'sms_enabled' => false,
            'push_enabled' => true
        ]
    ],
    'payment-gateway' => [
        'enabled' => true,
        'version' => '1.0.0',
        'dependencies' => ['database'],
        'settings' => [
            'mtn_money_enabled' => true,
            'airtel_money_enabled' => true,
            'paypal_enabled' => false,
            'card_enabled' => true
        ]
    ],
    'messaging' => [
        'enabled' => true,
        'version' => '1.0.0',
        'dependencies' => ['database'],
        'settings' => [
            'file_upload_enabled' => true,
            'max_file_size' => '10MB',
            'chat_history_days' => 365
        ]
    ],
    'analytics' => [
        'enabled' => true,
        'version' => '1.0.0',
        'dependencies' => ['database'],
        'settings' => [
            'real_time_stats' => true,
            'export_enabled' => true,
            'report_retention_days' => 730
        ]
    ],
    'mobile-api' => [
        'enabled' => true,
        'version' => '1.0.0',
        'dependencies' => ['database'],
        'settings' => [
            'rate_limiting' => true,
            'api_version' => 'v1',
            'token_expiry' => 86400
        ]
    ],
    'email-templates' => [
        'enabled' => true,
        'version' => '1.0.0',
        'dependencies' => ['database'],
        'settings' => [
            'template_engine' => 'twig',
            'cache_enabled' => true,
            'auto_send' => true
        ]
    ]
];

// Load enabled extensions
function load_extensions() {
    global $extensions;
    
    foreach ($extensions as $name => $config) {
        if ($config['enabled']) {
            $extension_file = EXTENSIONS_PATH . "/{$name}/{$name}.php";
            if (file_exists($extension_file)) {
                require_once $extension_file;
            }
        }
    }
}

// Check extension dependencies
function check_extension_dependencies($extension_name) {
    global $extensions;
    
    if (!isset($extensions[$extension_name])) {
        return false;
    }
    
    $deps = $extensions[$extension_name]['dependencies'] ?? [];
    foreach ($deps as $dep) {
        if (!isset($extensions[$dep]) || !$extensions[$dep]['enabled']) {
            return false;
        }
    }
    
    return true;
}
?>
