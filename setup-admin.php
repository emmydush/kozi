<?php
require_once 'config.php';

echo "<h1>Admin Setup - Household Connect</h1>";

// Check if admin role already exists in users table
$check_admin_sql = "SELECT * FROM users WHERE role = 'admin' LIMIT 1";
$check_result = $conn->query($check_admin_sql);

if ($check_result && $check_result->num_rows > 0) {
    $admin = $check_result->fetch_assoc();
    echo "<h3>✅ Admin user already exists!</h3>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</p>";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($admin['name']) . "</p>";
    echo "<p><strong>Created:</strong> " . format_date($admin['created_at']) . "</p>";
    
    // Option to reset password
    if (isset($_POST['reset_password'])) {
        $new_password = password_hash('admin123', PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_password, $admin['id']);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✅ Admin password reset to 'admin123'</p>";
        } else {
            echo "<p style='color: red;'>❌ Error resetting password: " . $stmt->error . "</p>";
        }
    }
    
    echo "<form method='post'>";
    echo "<input type='submit' name='reset_password' value='Reset Admin Password to admin123'>";
    echo "</form>";
    
} else {
    echo "<h3>🔧 Creating Admin User</h3>";
    
    // Create admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_sql = "INSERT INTO users (name, email, password, role, is_verified, status, created_at) 
                   VALUES (?, ?, ?, 'admin', 1, 'active', NOW())";
    
    $stmt = $conn->prepare($admin_sql);
    $admin_name = 'System Administrator';
    $admin_email = 'admin@householdconnect.rw';
    
    $stmt->bind_param("sss", $admin_name, $admin_email, $admin_password);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
        echo "<p><strong>Email:</strong> " . $admin_email . "</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<p><strong>⚠️ Important:</strong> Change this password immediately after first login!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating admin user: " . $stmt->error . "</p>";
    }
}

$conn->close();
?>
