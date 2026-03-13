<?php
require_once 'config.php';

echo "<h2>Database Connection Test</h2>";

// Test database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Test if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ Users table exists</p>";
    
    // Test if there are any users
    $user_count = $conn->query("SELECT COUNT(*) as count FROM users");
    $count = $user_count->fetch_assoc()['count'];
    echo "<p>📊 Total users in database: " . $count . "</p>";
    
    // Show sample users (without passwords)
    $users = $conn->query("SELECT id, name, email, role, created_at FROM users LIMIT 5");
    if ($users && $users->num_rows > 0) {
        echo "<h3>Sample Users:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr>";
        while ($user = $users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . $user['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No users found in database</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Users table does not exist</p>";
}

// Test session functionality
echo "<h2>Session Test</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✅ Session is active</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
} else {
    echo "<p style='color: red;'>❌ Session is not active</p>";
}

$conn->close();
?>
