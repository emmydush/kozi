<?php
require_once '../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized'], 401);
}

$user_id = $_SESSION['user_id'];

try {
    // Check if file was uploaded
    if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
        json_response(['success' => false, 'message' => 'No file uploaded or upload error'], 400);
    }
    
    $file = $_FILES['profile_image'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        json_response(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed'], 400);
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        json_response(['success' => false, 'message' => 'File too large. Maximum size is 5MB'], 400);
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
        json_response(['success' => false, 'message' => 'Failed to save file'], 500);
    }
    
    // Delete old profile picture if exists
    $conn->select_db("household_connect");
    $result = $conn->query("SELECT profile_image FROM users WHERE id = $user_id");
    if ($result && $row = $result->fetch_assoc()) {
        if ($row['profile_image'] && file_exists(__DIR__ . '/../' . $row['profile_image'])) {
            unlink(__DIR__ . '/../' . $row['profile_image']);
        }
    }
    
    // Update database
    $relativePath = 'uploads/profiles/' . $filename;
    $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $relativePath, $user_id);
    
    if ($stmt->execute()) {
        json_response([
            'success' => true, 
            'message' => 'Profile picture updated successfully',
            'profile_image' => $relativePath
        ]);
    } else {
        // Clean up uploaded file if database update fails
        unlink($filepath);
        json_response(['success' => false, 'message' => 'Failed to update database'], 500);
    }
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>
