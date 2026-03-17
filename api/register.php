<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['name', 'email', 'password', 'role'];
    $errors = validate_required($required_fields, $data);
    
    if (!empty($errors)) {
        json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 400);
    }
    
    $name = sanitize_input($data['name']);
    $email = sanitize_input($data['email']);
    $password = $data['password'];
    $role = sanitize_input($data['role']);
    
    // Enhanced email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Please enter a valid email address'], 400);
    }
    
    // Enhanced password validation
    if (strlen($password) < 8) {
        json_response(['success' => false, 'message' => 'Password must be at least 8 characters long'], 400);
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        json_response(['success' => false, 'message' => 'Password must contain at least one uppercase letter'], 400);
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        json_response(['success' => false, 'message' => 'Password must contain at least one lowercase letter'], 400);
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        json_response(['success' => false, 'message' => 'Password must contain at least one number'], 400);
    }
    
    // Name validation
    if (strlen($name) < 2) {
        json_response(['success' => false, 'message' => 'Name must be at least 2 characters long'], 400);
    }
    
    if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        json_response(['success' => false, 'message' => 'Name can only contain letters and spaces'], 400);
    }
    
    if (!in_array($role, ['employer', 'worker'])) {
        json_response(['success' => false, 'message' => 'Invalid role'], 400);
    }
    
    // Check if email already exists (PostgreSQL)
    $check_sql = "SELECT id FROM users WHERE email = :email";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->execute();
    $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_user) {
        json_response(['success' => false, 'message' => 'Email already registered'], 400);
    }
    
    // Hash password
    $hashed_password = password_hash($password, HASH_ALGO);
    
    // Insert user (PostgreSQL)
    $sql = "INSERT INTO users (name, email, password, role, created_at) VALUES (:name, :email, :password, :role, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':role', $role);
    
    if ($stmt->execute()) {
        // Get the last inserted ID (PostgreSQL)
        $user_id = $conn->lastInsertId();
        
        // Create session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        
        json_response(['success' => true, 'message' => 'Registration successful', 'user' => [
            'id' => $user_id,
            'name' => $name,
            'email' => $email,
            'role' => $role
        ]]);
    } else {
        json_response(['success' => false, 'message' => 'Registration failed'], 500);
    }
    
} catch (Exception $e) {
    // Removed the json_response call
}

$conn->close();
?>