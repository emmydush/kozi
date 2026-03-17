<?php
require_once 'config.php';

// Check if user is logged in
session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Debug</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>Dashboard Debug Information</h2>
        
        <div class='card mb-3'>
            <div class='card-header'>Session Information</div>
            <div class='card-body'>
                <table class='table'>
                    <tr><td><strong>User ID:</strong></td><td>" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "</td></tr>
                    <tr><td><strong>User Role:</strong></td><td>" . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'NOT SET') . "</td></tr>
                    <tr><td><strong>User Name:</strong></td><td>" . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'NOT SET') . "</td></tr>
                    <tr><td><strong>Logged In:</strong></td><td>" . (isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : 'NOT SET') . "</td></tr>
                </table>
            </div>
        </div>";

if (is_logged_in()) {
    echo "<div class='card mb-3'>
            <div class='card-header'>Database Query Results</div>
            <div class='card-body'>";
    
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    if ($user_role === 'employer') {
        // Test the exact query from the dashboard API
        $postedJobsSql = "SELECT COUNT(*) as total FROM jobs WHERE employer_id = :user_id";
        $stmt = $conn->prepare($postedJobsSql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $postedJobsResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $postedJobs = $postedJobsResult ? $postedJobsResult['total'] : 0;
        
        echo "<p><strong>Posted Jobs Count:</strong> $postedJobs</p>";
        
        // Get recent jobs
        $recentJobsSql = "SELECT j.*, COUNT(ja.id) as application_count 
                          FROM jobs j 
                          LEFT JOIN job_applications ja ON j.id = ja.job_id 
                          WHERE j.employer_id = :user_id 
                          GROUP BY j.id 
                          ORDER BY j.created_at DESC 
                          LIMIT 5";
        $stmt = $conn->prepare($recentJobsSql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $recentJobs = [];
        
        echo "<h5>Recent Jobs:</h5>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<div class='alert alert-info'>
                <strong>Title:</strong> " . htmlspecialchars($row['title']) . "<br>
                <strong>ID:</strong> " . $row['id'] . "<br>
                <strong>Status:</strong> " . $row['status'] . "<br>
                <strong>Applications:</strong> " . $row['application_count'] . "<br>
                <strong>Created:</strong> " . $row['created_at'] . "
            </div>";
        }
    }
    
    echo "</div></div>";
    
    // Test API call
    echo "<div class='card mb-3'>
            <div class='card-header'>API Test</div>
            <div class='card-body'>
                <button onclick='testAPI()' class='btn btn-primary'>Test Dashboard API</button>
                <div id='api-result' class='mt-3'></div>
            </div>
          </div>";
} else {
    echo "<div class='alert alert-warning'>You are not logged in. Please <a href='login.php'>login here</a>.</div>";
}

echo "</div>

<script>
function testAPI() {
    document.getElementById('api-result').innerHTML = '<div class=\"spinner-border\" role=\"status\"><span class=\"visually-hidden\">Loading...</span></div>';
    
    fetch('./api/dashboard-data-simple.php', {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('API Response:', data);
        document.getElementById('api-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
    })
    .catch(error => {
        console.error('API Error:', error);
        document.getElementById('api-result').innerHTML = '<div class=\"alert alert-danger\">Error: ' + error.message + '</div>';
    });
}
</script>

</body>
</html>";
?>
