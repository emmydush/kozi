<?php
require_once 'config.php';

echo "<h1>Create New Admin Credentials - Household Connect</h1>";

// Generate secure random password
function generateSecurePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Create new admin credentials
$new_admin_phone = '250' . rand(700000000, 799999999); // Generate Rwanda phone number
$new_admin_email = 'admin' . time() . '@householdconnect.rw'; // Keep email for record
$new_admin_password = generateSecurePassword(16);
$hashed_password = password_hash($new_admin_password, PASSWORD_DEFAULT);
$admin_name = 'System Administrator';

echo "<h3>🔧 Creating New Admin User</h3>";

// Insert new admin user
$admin_sql = "INSERT INTO users (name, email, phone, password, role, is_verified, status, created_at) 
               VALUES (?, ?, ?, ?, 'admin', true, 'active', NOW())";

$stmt = $conn->prepare($admin_sql);
$result = $stmt->execute([$admin_name, $new_admin_email, $new_admin_phone, $hashed_password]);

if ($result) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>✅ New Admin User Created Successfully!</h3>";
    echo "<p><strong>Phone Number:</strong> <code>" . htmlspecialchars($new_admin_phone) . "</code></p>";
    echo "<p><strong>Email:</strong> <code>" . htmlspecialchars($new_admin_email) . "</code></p>";
    echo "<p><strong>Password:</strong> <code style='background: #f8f9fa; padding: 5px; border-radius: 3px;'>" . htmlspecialchars($new_admin_password) . "</code></p>";
    echo "<p><strong>Role:</strong> Administrator</p>";
    echo "<p><strong>Status:</strong> Active & Verified</p>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4 style='color: #856404;'>⚠️ Important Security Notes:</h4>";
    echo "<ul>";
    echo "<li>Save these credentials in a secure location</li>";
    echo "<li>Change the password immediately after first login</li>";
    echo "<li>Use a strong, unique password</li>";
    echo "<li>Enable two-factor authentication if available</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h4>🔗 Access Links:</h4>";
    echo "<p><strong>Admin Dashboard:</strong> <a href='admin-dashboard.php'>admin-dashboard.php</a></p>";
    echo "<p><strong>Login Page:</strong> <a href='login.php'>login.php</a></p>";
    echo "</div>";
    
    // Update credentials file
    $credentials_content = "# Household Connect - Admin Credentials\n\n";
    $credentials_content .= "## Latest Administrator Account\n\n";
    $credentials_content .= "**Phone Number (Login):** " . $new_admin_phone . "\n";
    $credentials_content .= "**Email:** " . $new_admin_email . "\n";
    $credentials_content .= "**Password:** " . $new_admin_password . "\n";
    $credentials_content .= "**Created:** " . date('Y-m-d H:i:s') . "\n\n";
    $credentials_content .= "## Login Instructions\n\n";
    $credentials_content .= "📱 **Use Phone Number and Password to login**\n\n";
    $credentials_content .= "1. Go to login.php\n";
    $credentials_content .= "2. Enter phone number: " . $new_admin_phone . "\n";
    $credentials_content .= "3. Enter password: " . $new_admin_password . "\n";
    $credentials_content .= "4. Click Login\n\n";
    $credentials_content .= "## Security Instructions\n\n";
    $credentials_content .= "⚠️ **IMPORTANT:** Change the default password immediately after first login!\n\n";
    $credentials_content .= "1. Login to admin dashboard\n";
    $credentials_content .= "2. Go to Settings > Change Password\n";
    $credentials_content .= "3. Set a strong, unique password\n";
    $credentials_content .= "4. Enable two-factor authentication if available\n\n";
    $credentials_content .= "## Access URLs\n\n";
    $credentials_content .= "- **Admin Dashboard:** `admin-dashboard.php`\n";
    $credentials_content .= "- **Login Page:** `login.php`\n\n";
    $credentials_content .= "---\n";
    $credentials_content .= "*Generated on " . date('Y-m-d H:i:s') . "*\n";
    
    if (file_put_contents('ADMIN_CREDENTIALS.md', $credentials_content)) {
        echo "<p style='color: green;'>✅ ADMIN_CREDENTIALS.md file updated with new credentials</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Could not update ADMIN_CREDENTIALS.md file</p>";
    }
    
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24;'>❌ Error Creating Admin User</h3>";
    echo "<p><strong>Error:</strong> " . $stmt->error . "</p>";
    echo "</div>";
}

$conn = null;
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: #f8f9fa;
}
h1 {
    color: #333;
    text-align: center;
    margin-bottom: 30px;
}
code {
    background: #f1f3f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}
a {
    color: #007bff;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
