<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    if (!is_logged_in()) {
        json_response(['success' => false, 'message' => 'Authentication required'], 401);
    }
    
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'];
    $user_email = $_SESSION['user_email'];
    $user_role = $_SESSION['user_role'];
    
    json_response(['success' => true, 'user' => [
        'id' => $user_id,
        'name' => $user_name,
        'email' => $user_email,
        'role' => $user_role
    ]]);
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>