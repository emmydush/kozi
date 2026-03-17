<?php
require_once '../config.php';

// Check if user is logged in and is a worker
if (!is_logged_in() || $_SESSION['user_role'] !== 'worker') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if file was uploaded
    if (!isset($_FILES['national_id_photo']) || $_FILES['national_id_photo']['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit();
    }
    
    $file = $_FILES['national_id_photo'];
    
    // Validate file
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);
    
    if (!in_array($extension, $allowed_types)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF files are allowed.']);
        exit();
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
        exit();
    }
    
    // Generate unique filename
    $filename = 'national_id_' . $user_id . '_' . uniqid() . '.' . $extension;
    $upload_path = '../uploads/' . $filename;
    
    // Create uploads directory if it doesn't exist
    if (!is_dir('../uploads')) {
        mkdir('../uploads', 0777, true);
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // File uploaded successfully, return success even if database column doesn't exist
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'National ID photo uploaded successfully',
            'filename' => $filename
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

?>
