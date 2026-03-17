<?php
// Simulate being logged in as a worker
session_start();
$_SESSION['user_id'] = 1; // Try different worker IDs
$_SESSION['user_role'] = 'worker';
$_SESSION['user_name'] = 'Test Worker';
$_SESSION['logged_in'] = true;

echo "Testing worker dashboard API...\n";

// Test the API
require_once 'api/dashboard-data-simple.php';
?>
