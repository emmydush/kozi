<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        json_response(['success' => false, 'message' => 'Invalid JSON data'], 400);
    }
    
    $update_type = $data['type'] ?? 'personal';
    
    $user_id = $_SESSION['user_id'];
    
    if (!$user_id) {
        json_response(['success' => false, 'message' => 'User not logged in'], 401);
    }
    
    switch ($update_type) {
        case 'personal':
            $required_fields = ['name', 'email'];
            $errors = validate_required($required_fields, $data);
            
            if (!empty($errors)) {
                json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 400);
            }
            
            $name = sanitize_input($data['name']);
            $email = sanitize_input($data['email']);
            $phone = sanitize_input($data['phone'] ?? '');
            $location = sanitize_input($data['location'] ?? '');
            $bio = sanitize_input($data['bio'] ?? '');
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                json_response(['success' => false, 'message' => 'Invalid email format'], 400);
            }
            
            // Check if email is taken by another user
            if ($email !== $_SESSION['user_email']) {
                $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("si", $email, $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    json_response(['success' => false, 'message' => 'Email already exists'], 400);
                }
            }
            
            $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
            
            if ($stmt->execute()) {
                // Update session
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                json_response(['success' => true, 'message' => 'Personal information updated successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Update failed'], 500);
            }
            break;
            
        case 'security':
            $required_fields = ['current_password', 'new_password', 'confirm_password'];
            $errors = validate_required($required_fields, $data);
            
            if (!empty($errors)) {
                json_response(['success' => false, 'message' => 'Validation failed', 'errors' => $errors], 400);
            }
            
            $current_password = $data['current_password'];
            $new_password = $data['new_password'];
            $confirm_password = $data['confirm_password'];
            
            if ($new_password !== $confirm_password) {
                json_response(['success' => false, 'message' => 'Passwords do not match'], 400);
            }
            
            if (strlen($new_password) < 6) {
                json_response(['success' => false, 'message' => 'Password must be at least 6 characters'], 400);
            }
            
            // Get current password hash
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!password_verify($current_password, $user['password'])) {
                json_response(['success' => false, 'message' => 'Current password is incorrect'], 400);
            }
            
            // Update password
            $hashed_password = password_hash($new_password, HASH_ALGO);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                json_response(['success' => true, 'message' => 'Password updated successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Password update failed'], 500);
            }
            break;
            
        case 'professional':
            // Only for workers
            if ($_SESSION['user_role'] !== 'worker') {
                json_response(['success' => false, 'message' => 'Access denied'], 403);
            }
            
            $skills = $data['skills'] ?? [];
            $experience = sanitize_input($data['experience'] ?? '');
            $expected_salary = sanitize_input($data['expected_salary'] ?? '');
            $availability = sanitize_input($data['availability'] ?? '');
            
            // Check if worker record exists
            $check_sql = "SELECT id FROM workers WHERE user_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing worker record
                $sql = "UPDATE workers SET experience_years = ?, skills = ?, availability = ? WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $skills_json = json_encode($skills);
                $stmt->bind_param("issi", $experience, $skills_json, $availability, $user_id);
            } else {
                // Create new worker record
                $sql = "INSERT INTO workers (user_id, name, experience_years, skills, availability, status) VALUES (?, ?, ?, ?, ?, 'active')";
                $stmt = $conn->prepare($sql);
                $skills_json = json_encode($skills);
                $user_name = $_SESSION['user_name'];
                $stmt->bind_param("isiss", $user_id, $user_name, $experience, $skills_json, $availability);
            }
            
            if ($stmt->execute()) {
                json_response(['success' => true, 'message' => 'Professional information updated successfully']);
            } else {
                json_response(['success' => false, 'message' => 'Update failed'], 500);
            }
            break;
            
        default:
            json_response(['success' => false, 'message' => 'Invalid update type'], 400);
    }
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>
