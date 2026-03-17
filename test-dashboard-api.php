<?php
// Simulate being logged in as user ID 4
session_start();
$_SESSION['user_id'] = 4;
$_SESSION['user_role'] = 'employer';
$_SESSION['user_name'] = 'ishimwe adeline';
$_SESSION['logged_in'] = true;

echo "Testing dashboard API with user ID 4...\n";

// Call the dashboard API
require_once 'api/dashboard-data-simple.php';
?>
