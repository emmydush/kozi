<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    // Test login API with sample data
    $email = 'test@example.com';
    $password = 'Test1234';
    
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found - this is expected for test']);
    } else {
        echo json_encode(['success' => true, 'message' => 'User found', 'user' => ['id' => $user['id'], 'email' => $user['email']]]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
