<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit();
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit();
    }
    
    $update_type = $data['type'] ?? 'personal';
    
    $user_id = $_SESSION['user_id'];
    
    if (!$user_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit();
    }
    
    switch ($update_type) {
        case 'personal':
            $required_fields = ['name', 'email'];
            $errors = validate_required($required_fields, $data);
            
            if (!empty($errors)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
                exit();
            }
            
            $name = sanitize_input($data['name']);
            $email = sanitize_input($data['email']);
            $phone = sanitize_input($data['phone'] ?? '');
            $location = sanitize_input($data['location'] ?? '');
            $bio = sanitize_input($data['bio'] ?? '');
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit();
            }
            
            // Check if email is taken by another user
            if ($email !== $_SESSION['user_email']) {
                $check_sql = "SELECT id FROM users WHERE email = :email AND id != :user_id";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bindParam(':email', $email);
                $check_stmt->bindParam(':user_id', $user_id);
                $check_stmt->execute();
                $existing_user = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing_user) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Email already exists']);
                    exit();
                }
            }
            
            // Update users table
            $sql = "UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                // Update session
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // Update national ID if worker and provided
                if ($_SESSION['user_role'] === 'worker' && isset($data['national_id'])) {
                    $national_id = sanitize_input($data['national_id']);
                    
                    // Check if national_id column exists
                    $check_columns = "SELECT column_name FROM information_schema.columns 
                                     WHERE table_name = 'workers' AND column_name = 'national_id'";
                    $column_check = $conn->query($check_columns);
                    $has_national_id = $column_check->fetch(PDO::FETCH_ASSOC);
                    
                    if ($has_national_id) {
                        // Check if worker record exists
                        $worker_check_sql = "SELECT id FROM workers WHERE user_id = :user_id";
                        $worker_check_stmt = $conn->prepare($worker_check_sql);
                        $worker_check_stmt->bindParam(':user_id', $user_id);
                        $worker_check_stmt->execute();
                        $existing_worker = $worker_check_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($existing_worker) {
                            // Update existing worker record
                            $worker_sql = "UPDATE workers SET national_id = :national_id WHERE user_id = :user_id";
                            $worker_stmt = $conn->prepare($worker_sql);
                            $worker_stmt->bindParam(':national_id', $national_id);
                            $worker_stmt->bindParam(':user_id', $user_id);
                            $worker_stmt->execute();
                        } else {
                            // Create worker record if it doesn't exist
                            $worker_sql = "INSERT INTO workers (user_id, name, national_id, status) VALUES (:user_id, :name, :national_id, 'active')";
                            $worker_stmt = $conn->prepare($worker_sql);
                            $worker_stmt->bindParam(':user_id', $user_id);
                            $worker_stmt->bindParam(':name', $name);
                            $worker_stmt->bindParam(':national_id', $national_id);
                            $worker_stmt->execute();
                        }
                    }
                }
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Personal information updated successfully']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Update failed']);
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
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
                exit();
            }
            
            if (strlen($new_password) < 6) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                exit();
            }
            
            // Get current password hash
            $sql = "SELECT password FROM users WHERE id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($current_password, $user['password'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit();
            }
            
            // Update password
            $hashed_password = password_hash($new_password, HASH_ALGO);
            $sql = "UPDATE users SET password = :password WHERE id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Password update failed']);
            }
            break;
            
        case 'professional':
            // Only for workers
            if ($_SESSION['user_role'] !== 'worker') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit();
            }
            
            $skills = $data['skills'] ?? [];
            $experience = sanitize_input($data['experience'] ?? '');
            $expected_salary = sanitize_input($data['expected_salary'] ?? '');
            $availability = sanitize_input($data['availability'] ?? '');
            
            // Check if worker record exists
            $check_sql = "SELECT id FROM workers WHERE user_id = :user_id";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindParam(':user_id', $user_id);
            $check_stmt->execute();
            $existing_worker = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_worker) {
                // Update existing worker record
                $sql = "UPDATE workers SET experience_years = :experience, skills = :skills, availability = :availability WHERE user_id = :user_id";
                $stmt = $conn->prepare($sql);
                $skills_json = json_encode($skills);
                $stmt->bindParam(':experience', $experience);
                $stmt->bindParam(':skills', $skills_json);
                $stmt->bindParam(':availability', $availability);
                $stmt->bindParam(':user_id', $user_id);
            } else {
                // Create new worker record
                $sql = "INSERT INTO workers (user_id, name, experience_years, skills, availability, status) VALUES (:user_id, :name, :experience, :skills, :availability, 'active')";
                $stmt = $conn->prepare($sql);
                $skills_json = json_encode($skills);
                $user_name = $_SESSION['user_name'];
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':name', $user_name);
                $stmt->bindParam(':experience', $experience);
                $stmt->bindParam(':skills', $skills_json);
                $stmt->bindParam(':availability', $availability);
            }
            
            if ($stmt->execute()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Professional information updated successfully']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid update type']);
            exit();
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
