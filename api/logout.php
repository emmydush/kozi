<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    session_destroy();
    json_response(['success' => true, 'message' => 'Logged out successfully']);
    
} catch (Exception $e) {
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

$conn->close();
?>