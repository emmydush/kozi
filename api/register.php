<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => t('auth.method_not_allowed')], 405);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['name', 'email', 'phone', 'password', 'role'];
    $errors = validate_required($required_fields, $data);
    
    if (!empty($errors)) {
        json_response(['success' => false, 'message' => t('auth.validation_failed'), 'errors' => $errors], 400);
    }
    
    $name = sanitize_input($data['name']);
    $email = sanitize_input($data['email']);
    $phone = trim((string) ($data['phone'] ?? ''));
    $normalized_phone = preg_replace('/\D+/', '', $phone);
    $password = $data['password'];
    $role = sanitize_input($data['role']);
    
    // Enhanced email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['success' => false, 'message' => t('auth.invalid_email')], 400);
    }

    if (strlen($normalized_phone) < 9) {
        json_response(['success' => false, 'message' => t('auth.phone_required')], 400);
    }
    
    // Enhanced password validation
    if (strlen($password) < 8) {
        json_response(['success' => false, 'message' => t('auth.password_length')], 400);
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        json_response(['success' => false, 'message' => t('auth.password_upper')], 400);
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        json_response(['success' => false, 'message' => t('auth.password_lower')], 400);
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        json_response(['success' => false, 'message' => t('auth.password_number')], 400);
    }
    
    // Name validation
    if (strlen($name) < 2) {
        json_response(['success' => false, 'message' => t('auth.name_length')], 400);
    }
    
    if (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        json_response(['success' => false, 'message' => t('auth.name_letters')], 400);
    }
    
    if (!in_array($role, ['employer', 'worker'])) {
        json_response(['success' => false, 'message' => t('auth.invalid_role')], 400);
    }
    
    // Check if email already exists (PostgreSQL)
    $check_sql = "SELECT id FROM users WHERE email = :email";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bindParam(':email', $email);
    $check_stmt->execute();
    $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_user) {
        json_response(['success' => false, 'message' => t('auth.email_exists')], 400);
    }

    $phone_check_sql = "SELECT id FROM users WHERE REGEXP_REPLACE(COALESCE(phone, ''), '[^0-9]', '', 'g') = :phone";
    $phone_check_stmt = $conn->prepare($phone_check_sql);
    $phone_check_stmt->bindParam(':phone', $normalized_phone);
    $phone_check_stmt->execute();
    $existing_phone_user = $phone_check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_phone_user) {
        json_response(['success' => false, 'message' => t('auth.phone_exists')], 400);
    }
    
    // Hash password
    $hashed_password = password_hash($password, HASH_ALGO);
    
    // Insert user (PostgreSQL)
    $sql = "INSERT INTO users (name, email, phone, password, role, created_at) VALUES (:name, :email, :phone, :password, :role, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
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
        
        json_response(['success' => true, 'message' => t('auth.registration_success'), 'user' => [
            'id' => $user_id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => $role
        ]]);
    } else {
        json_response(['success' => false, 'message' => t('auth.registration_failed')], 500);
    }
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => t('auth.server_error') . ': ' . $e->getMessage()], 500);
}
?>
