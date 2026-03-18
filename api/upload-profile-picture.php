<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Check if file was uploaded
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit();
    }
    
    $file = $_FILES['profile_image'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
        exit();
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB']);
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/../uploads/profiles';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
    $filepath = $uploadsDir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        exit();
    }
    
    // Delete old profile picture if exists
    // PostgreSQL doesn't need select_db - connection is already to the correct database
    $sql = "SELECT profile_image FROM users WHERE id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row && $row['profile_image'] && file_exists(__DIR__ . '/../' . $row['profile_image'])) {
        unlink(__DIR__ . '/../' . $row['profile_image']);
    }
    
    // Update database
    $relativePath = 'uploads/profiles/' . $filename;
    $sql = "UPDATE users SET profile_image = :profile_image WHERE id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':profile_image', $relativePath);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        // Also update workers table if user is a worker
        if ($_SESSION['user_role'] === 'worker') {
            // Check if worker record exists
            $worker_check_sql = "SELECT id FROM workers WHERE user_id = :user_id";
            $worker_check_stmt = $conn->prepare($worker_check_sql);
            $worker_check_stmt->bindParam(':user_id', $user_id);
            $worker_check_stmt->execute();
            $existing_worker = $worker_check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_worker) {
                // Update worker profile image
                $worker_sql = "UPDATE workers SET profile_image = :profile_image WHERE user_id = :user_id";
                $worker_stmt = $conn->prepare($worker_sql);
                $worker_stmt->bindParam(':profile_image', $relativePath);
                $worker_stmt->bindParam(':user_id', $user_id);
                $worker_stmt->execute();
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Profile picture updated successfully',
            'profile_image' => $relativePath
        ]);
    } else {
        // Clean up uploaded file if database update fails
        unlink($filepath);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update database']);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
