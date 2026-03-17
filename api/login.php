<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
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
    
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        json_response(['success' => false, 'message' => 'Invalid credentials'], 401);
    }
    
    if (!password_verify($password, $user['password'])) {
        json_response(['success' => false, 'message' => 'Invalid credentials'], 401);
    }
    
    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    json_response(['success' => true, 'message' => 'Login successful', 'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ]]);
    
} catch (Exception $e) {
    // Removed the json_response call
}

$conn->close();
?>