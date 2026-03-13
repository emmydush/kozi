<?php
require_once 'config.php';

// Test booking functionality
echo "<h2>Testing Booking System</h2>";

// Check if user is logged in
if (!is_logged_in()) {
    echo "<p style='color: red;'>User not logged in</p>";
} else {
    echo "<p style='color: green;'>User logged in: " . $_SESSION['user_name'] . " (" . $_SESSION['user_role'] . ")</p>";
}

// Check workers table
echo "<h3>Workers Table Check</h3>";
$worker_sql = "SELECT COUNT(*) as count FROM workers WHERE status = 'active'";
$stmt = $conn->prepare($worker_sql);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];
echo "<p>Active workers: " . $count . "</p>";

// Check bookings table
echo "<h3>Bookings Table Check</h3>";
$booking_sql = "SELECT COUNT(*) as count FROM bookings";
$stmt = $conn->prepare($booking_sql);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];
echo "<p>Total bookings: " . $count . "</p>";

// Test booking creation
echo "<h3>Test Booking Creation</h3>";
if (isset($_POST['test_booking'])) {
    $worker_id = 1; // Test worker ID
    $employer_id = $_SESSION['user_id'];
    $start_date = '2024-01-01';
    $end_date = '2024-01-02';
    $service_type = 'cleaning';
    
    $booking_sql = "INSERT INTO bookings (worker_id, employer_id, start_date, end_date, service_type, status) 
                   VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($booking_sql);
    $stmt->bind_param("iisss", $worker_id, $employer_id, $start_date, $end_date, $service_type);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Test booking created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating test booking: " . $stmt->error . "</p>";
    }
}

echo "<form method='post'>";
echo "<input type='submit' name='test_booking' value='Create Test Booking'>";
echo "</form>";

$conn->close();
?>
