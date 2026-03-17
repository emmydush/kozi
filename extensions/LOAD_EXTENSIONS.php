<?php
// Extension Loader - Include this in your main application
require_once 'config.php';

// Load extension configuration
require_once EXTENSIONS_PATH . '/config.php';

// Auto-load enabled extensions
load_extensions();

// Make extension systems globally available
if (class_exists('NotificationSystem')) {
    $GLOBALS['notification_system'] = new NotificationSystem();
}

if (class_exists('PaymentGateway')) {
    $GLOBALS['payment_gateway'] = new PaymentGateway();
}

if (class_exists('MessagingSystem')) {
    $GLOBALS['messaging_system'] = new MessagingSystem();
}

if (class_exists('AnalyticsSystem')) {
    $GLOBALS['analytics_system'] = new AnalyticsSystem();
}

if (class_exists('EmailTemplates')) {
    $GLOBALS['email_templates'] = new EmailTemplates();
}

// Helper function to check if extension is loaded
function is_extension_enabled($name) {
    global $extensions;
    return isset($extensions[$name]) && $extensions[$name]['enabled'];
}

// Helper function to get extension setting
function get_extension_setting($extension_name, $setting_key, $default = null) {
    global $extensions;
    
    if (isset($extensions[$extension_name]['settings'][$setting_key])) {
        return $extensions[$extension_name]['settings'][$setting_key];
    }
    
    return $default;
}
?>
