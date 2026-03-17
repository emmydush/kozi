<?php
// Test API response structure
session_start();
$_SESSION['user_id'] = 1; // Test user ID
$_SESSION['user_role'] = 'worker'; // Test role

require_once 'api/dashboard-data-simple.php';

// Read the output
ob_start();
include 'api/dashboard-data-simple.php';
$output = ob_get_clean();

echo "API Response: " . $output;
?>
