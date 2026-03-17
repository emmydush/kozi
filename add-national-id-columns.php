<?php
require_once 'config.php';

try {
    // Add national_id column if it doesn't exist
    $add_national_id = "ALTER TABLE workers ADD COLUMN IF NOT EXISTS national_id VARCHAR(50)";
    $conn->exec($add_national_id);
    echo "Added national_id column (if it didn't exist)\n";
    
    // Add national_id_photo column if it doesn't exist
    $add_national_id_photo = "ALTER TABLE workers ADD COLUMN IF NOT EXISTS national_id_photo VARCHAR(255)";
    $conn->exec($add_national_id_photo);
    echo "Added national_id_photo column (if it didn't exist)\n";
    
    echo "Database migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
