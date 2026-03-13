<?php
require_once '../config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    // Get JSON input
    $json_input = file_get_contents('php://input');
    if ($json_input === false) {
        json_response(['success' => false, 'message' => 'Invalid JSON input'], 400);
    }
    
    $data = json_decode($json_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_response(['success' => false, 'message' => 'JSON decode error: ' . json_last_error_msg()], 400);
    }
    
    // Debug: Log received data
    error_log("Login attempt data: " . print_r($data, true));
    
    $required_fields = ['email', 'password'];
    $errors = validate_required($required_fields, $data);
    
    if (!empty($errors)) {
        json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 400);
    }
    
    $email = sanitize_input($data['email']);
    $password = $data['password'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Invalid email format'], 400);
    }
    
    // Check database connection
    if ($conn->connect_error) {
        error_log("Database connection error: " . $conn->connect_error);
        json_response(['success' => false, 'message' => 'Database connection failed'], 500);
    }
    
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        error_log("Statement preparation failed: " . $conn->error);
        json_response(['success' => false, 'message' => 'Database query preparation failed'], 500);
    }
    
    $stmt->bind_param("s", $email);
    $result = $stmt->execute();
    
    if ($result === false) {
        error_log("Statement execution failed: " . $stmt->error);
        json_response(['success' => false, 'message' => 'Database query execution failed'], 500);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        json_response(['success' => false, 'message' => 'Invalid email or password'], 401);
    }
    
    $user = $result->fetch_assoc();
    
    // Debug: Log user data (without password)
    error_log("Found user: " . print_r(['id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']], true));
    
    if (!password_verify($password, $user['password'])) {
        error_log("Password verification failed for email: " . $email);
        json_response(['success' => false, 'message' => 'Invalid email or password'], 401);
    }
    
    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    // Debug: Log session creation
    error_log("Session created for user ID: " . $user['id']);
    
    json_response([
        'success' => true, 
        'message' => 'Login successful', 
        'redirect' => 'dashboard.php',
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Login exception: " . $e->getMessage());
    json_response(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}

$conn->close();
?>
