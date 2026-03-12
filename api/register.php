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
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => 'Invalid email format'], 400);
    }
    
    if (strlen($password) < 6) {
        json_response(['success' => false, 'message' => 'Password must be at least 6 characters'], 400);
    }
    
    if (!in_array($role, ['employer', 'worker'])) {
        json_response(['success' => false, 'message' => 'Invalid role'], 400);
    }
    
    // Check if email already exists
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        json_response(['success' => false, 'message' => 'Email already registered'], 400);
    }
    
    // Hash password
    $hashed_password = password_hash($password, HASH_ALGO);
    
    // Insert user
    $sql = "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
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
    
    $stmt->close();
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>