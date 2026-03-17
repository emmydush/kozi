<?php
require_once '../config.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit();
    }
    
    // Get JSON input
    $json_input = file_get_contents('php://input');
    error_log("Raw JSON input: " . $json_input);
    
    if ($json_input === false || empty($json_input)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid or empty JSON input']);
        exit();
    }
    
    $data = json_decode($json_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'JSON decode error: ' . json_last_error_msg()]);
        exit();
    }
    
    // Debug: Log received data
    error_log("Login attempt data: " . print_r($data, true));
    
    $required_fields = ['email', 'password'];
    $errors = validate_required($required_fields, $data);
    
    if (!empty($errors)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
        exit();
    }
    
    $email = sanitize_input($data['email']);
    $password = $data['password'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }
    
    // Check database connection (PostgreSQL)
    if (!$conn) {
        error_log("Database connection error");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = :email";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        error_log("Statement preparation failed");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database query preparation failed']);
        exit();
    }
    
    $stmt->bindParam(':email', $email);
    $result = $stmt->execute();
    
    if ($result === false) {
        error_log("Statement execution failed");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database query execution failed']);
        exit();
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit();
    }
    
    // Debug: Log user data (without password)
    error_log("Found user: " . print_r(['id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']], true));
    
    if (!password_verify($password, $user['password'])) {
        error_log("Password verification failed for email: " . $email);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit();
    }
    
    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    // Debug: Log session creation
    error_log("Session created for user ID: " . $user['id']);
    
    header('Content-Type: application/json');
    echo json_encode([
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
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
