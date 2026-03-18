<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => t('auth.method_not_allowed')], 405);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['phone', 'password'];
    $errors = validate_required($required_fields, $data);
    
    if (!empty($errors)) {
        json_response(['success' => false, 'message' => t('auth.validation_failed'), 'errors' => $errors], 400);
    }
    
    $phone = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
    $password = $data['password'];
    
    if (strlen($phone) < 9) {
        json_response(['success' => false, 'message' => t('auth.invalid_phone')], 400);
    }
    
    $sql = "SELECT id, name, email, phone, password, role FROM users WHERE REGEXP_REPLACE(COALESCE(phone, ''), '[^0-9]', '', 'g') = :phone LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        json_response(['success' => false, 'message' => t('auth.invalid_credentials')], 401);
    }
    
    if (!password_verify($password, $user['password'])) {
        json_response(['success' => false, 'message' => t('auth.invalid_credentials')], 401);
    }
    
    // Create session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    // Determine redirect based on user role
    $redirect = 'dashboard.php'; // Default for regular users
    if ($user['role'] === 'admin') {
        $redirect = 'admin-dashboard.php';
    }
    
    json_response([
        'success' => true, 
        'message' => t('auth.login_success'), 
        'redirect' => $redirect,
        'user' => [
            'id' => $user['id'],
            'phone' => $user['phone'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => t('auth.server_error') . ': ' . $e->getMessage()], 500);
}
?>
