<?php
require_once 'config.php';
header('Content-Type: application/json');

// Simulate the exact request from login form
$data = [
    'email' => 'test@example.com',
    'password' => 'Test1234',
    'remember' => false
];

$json_input = json_encode($data);

// Simulate POST data
$_SERVER['REQUEST_METHOD'] = 'POST';

// Create a temporary file to simulate php://input
$temp_file = tempnam(sys_get_temp_dir(), 'json_input');
file_put_contents($temp_file, $json_input);

// Override php://input for testing
$_POST = $data;

echo json_encode([
    'success' => true, 
    'message' => 'Test request simulated successfully',
    'data' => $data
]);
?>
