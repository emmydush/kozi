<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    // Test database connection
    if ($conn) {
        echo json_encode(['success' => true, 'message' => 'Database connection successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
