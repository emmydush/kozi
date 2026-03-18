<?php
require_once '../config.php';

// Check if user is logged in and is a worker
if (!is_logged_in() || $_SESSION['user_role'] !== 'worker') {
    json_response(['success' => false, 'message' => 'Unauthorized access']);
}

$user_id = $_SESSION['user_id'];

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitize_input($_POST['name'] ?? '');
    $type = sanitize_input($_POST['type'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $experience_years = (int)($_POST['experience_years'] ?? 0);
    $hourly_rate = (float)($_POST['hourly_rate'] ?? 0);
    $location = sanitize_input($_POST['location'] ?? '');
    $availability = sanitize_input($_POST['availability'] ?? '');
    $education = sanitize_input($_POST['education'] ?? '');
    $languages = sanitize_input($_POST['languages'] ?? '');
    $certifications = sanitize_input($_POST['certifications'] ?? '');
    $national_id = sanitize_input($_POST['national_id'] ?? '');
    $skills = isset($_POST['skills']) ? json_encode($_POST['skills']) : '[]';
    
    // Validate required fields
    $required_fields = ['name', 'type', 'description', 'experience_years', 'hourly_rate', 'location', 'availability', 'education', 'languages', 'national_id'];
    $errors = validate_required($required_fields, $_POST);
    
    if (!empty($errors)) {
        json_response(['success' => false, 'message' => 'Please fill in all required fields', 'errors' => $errors]);
    }
    
    // Check if worker record exists
    $check_sql = "SELECT id FROM workers WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $check_result = $check_stmt->fetchAll();
    
    // Handle file uploads
    $profile_image_filename = null;
    $national_id_photo_filename = null;
    
    // Upload profile image if provided
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_file($_FILES['profile_image'], ['jpg', 'jpeg', 'png', 'gif']);
        if ($upload_result['success']) {
            $profile_image_filename = $upload_result['filename'];
        } else {
            json_response(['success' => false, 'message' => 'Profile image upload failed: ' . $upload_result['message']]);
        }
    }
    
    // Upload national ID photo if provided
    if (isset($_FILES['national_id_photo']) && $_FILES['national_id_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_file($_FILES['national_id_photo'], ['jpg', 'jpeg', 'png', 'gif']);
        if ($upload_result['success']) {
            $national_id_photo_filename = $upload_result['filename'];
        } else {
            json_response(['success' => false, 'message' => 'National ID photo upload failed: ' . $upload_result['message']]);
        }
    }
    
    // Update or insert worker record
    if (!empty($check_result)) {
        // Update existing record
        $worker_id = $check_result[0]['id'];
        
        $update_sql = "UPDATE workers SET name = ?, type = ?, description = ?, experience_years = ?, 
                      hourly_rate = ?, location = ?, availability = ?, skills = ?, education = ?, 
                      languages = ?, certifications = ?, national_id = ?";
        
        $params = [$name, $type, $description, $experience_years, $hourly_rate, $location, 
                   $availability, $skills, $education, $languages, $certifications, $national_id];
        
        // Add profile image if uploaded
        if ($profile_image_filename) {
            $update_sql .= ", profile_image = ?";
            $params[] = $profile_image_filename;
        }
        
        // Add national ID photo if uploaded
        if ($national_id_photo_filename) {
            $update_sql .= ", national_id_photo = ?";
            $params[] = $national_id_photo_filename;
        }
        
        $update_sql .= " WHERE user_id = ?";
        $params[] = $user_id;
        
        $update_stmt = $conn->prepare($update_sql);
        $types = str_repeat('s', count($params) - 1) . 'i';
        $update_stmt->bind_param($types, ...$params);
        
        if ($update_stmt->execute()) {
            // Recalculate profile completion
            calculate_profile_completion($user_id);
            
            json_response([
                'success' => true, 
                'message' => 'Profile updated successfully',
                'profile_completion' => calculate_profile_completion($user_id)
            ]);
        } else {
            json_response(['success' => false, 'message' => 'Failed to update profile: ' . $conn->error]);
        }
        
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO workers (user_id, name, type, description, experience_years, hourly_rate, 
                          location, availability, skills, education, languages, certifications, 
                          national_id, national_id_photo, profile_image) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [$user_id, $name, $type, $description, $experience_years, $hourly_rate, 
                   $location, $availability, $skills, $education, $languages, $certifications, 
                   $national_id, $national_id_photo_filename, $profile_image_filename];
        
        $insert_stmt = $conn->prepare($insert_sql);
        $types = 'isssissssssssss';
        $insert_stmt->bind_param($types, ...$params);
        
        if ($insert_stmt->execute()) {
            // Recalculate profile completion
            calculate_profile_completion($user_id);
            
            json_response([
                'success' => true, 
                'message' => 'Profile created successfully',
                'profile_completion' => calculate_profile_completion($user_id)
            ]);
        } else {
            json_response(['success' => false, 'message' => 'Failed to create profile: ' . $conn->error]);
        }
    }
}

json_response(['success' => false, 'message' => 'Invalid request method']);
?>
