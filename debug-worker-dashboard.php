<?php
require_once 'config.php';

session_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Worker Dashboard Debug</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h2>Worker Dashboard Debug</h2>
        
        <div class='card mb-3'>
            <div class='card-header'>Current Session</div>
            <div class='card-body'>
                <table class='table'>
                    <tr><td><strong>User ID:</strong></td><td>" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "</td></tr>
                    <tr><td><strong>User Role:</strong></td><td>" . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'NOT SET') . "</td></tr>
                    <tr><td><strong>User Name:</strong></td><td>" . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'NOT SET') . "</td></tr>
                    <tr><td><strong>Logged In:</strong></td><td>" . (isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : 'NOT SET') . "</td></tr>
                </table>
            </div>
        </div>";

if (is_logged_in() && $_SESSION['user_role'] === 'worker') {
    echo "<div class='card mb-3'>
            <div class='card-header'>Dashboard API Test</div>
            <div class='card-body'>
                <button onclick='testDashboardAPI()' class='btn btn-primary mb-3'>Test Dashboard API</button>
                <div id='api-result'></div>
            </div>
          </div>";
    
    echo "<div class='card mb-3'>
            <div class='card-header'>Manual Available Jobs Test</div>
            <div class='card-body'>
                <button onclick='loadAvailableJobs()' class='btn btn-success mb-3'>Load Available Jobs Manually</button>
                <div id='jobs-result'></div>
            </div>
          </div>";
} else {
    echo "<div class='alert alert-warning'>You are not logged in as a worker. Current role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'none') . "</div>";
}

echo "</div>

<script>
function testDashboardAPI() {
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
        console.log('API Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Full API Response:', data);
        document.getElementById('api-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        
        if (data.success && data.data && data.data.available_jobs) {
            displayJobs(data.data.available_jobs);
        }
    })
    .catch(error => {
        console.error('API Error:', error);
        document.getElementById('api-result').innerHTML = '<div class=\"alert alert-danger\">Error: ' + error.message + '</div>';
    });
}

function loadAvailableJobs() {
    document.getElementById('jobs-result').innerHTML = '<div class=\"spinner-border\" role=\"status\"><span class=\"visually-hidden\">Loading...</span></div>';
    
    // Simulate the loadWorkerData function
    const testData = {
        available_jobs: [
            {id: 1, title: 'Test Job 1', employer_name: 'Test Employer', status: 'active', salary: 50000, location: 'Kigali'},
            {id: 2, title: 'Test Job 2', employer_name: 'Test Employer 2', status: 'active', salary: 75000, location: 'Musanze'}
        ]
    };
    
    setTimeout(() => {
        displayJobs(testData.available_jobs);
    }, 1000);
}

function displayJobs(jobs) {
    console.log('Displaying jobs:', jobs);
    
    if (jobs && jobs.length > 0) {
        let html = jobs.map(job => {
            let statusBadge = '';
            let actionButton = '';
            
            switch(job.status) {
                case 'active':
                    statusBadge = '<span class=\"badge bg-success\">Available</span>';
                    actionButton = '<button class=\"btn btn-sm btn-primary mt-2\" onclick=\"alert(\\'Apply for job: ' + job.id + '\\')\">Apply Now</button>';
                    break;
                case 'applied':
                    statusBadge = '<span class=\"badge bg-warning\">Applied</span>';
                    actionButton = '<button class=\"btn btn-sm btn-secondary mt-2\" disabled>Already Applied</button>';
                    break;
                default:
                    statusBadge = '<span class=\"badge bg-secondary\">Unknown</span>';
                    actionButton = '<button class=\"btn btn-sm btn-secondary mt-2\" disabled>Not Available</button>';
            }
            
            return '<div class=\"list-group-item\">' +
                '<div class=\"d-flex justify-content-between align-items-start\">' +
                    '<div class=\"flex-grow-1\">' +
                        '<h6>' + job.title + ' ' + statusBadge + '</h6>' +
                        '<small class=\"text-muted\">' + job.employer_name + ' - ' + job.location + ' - RWF ' + Number(job.salary || 0).toLocaleString() + '</small>' +
                    '</div>' +
                '</div>' +
                actionButton +
            '</div>';
        }).join('');
        
        document.getElementById('jobs-result').innerHTML = html;
    } else {
        document.getElementById('jobs-result').innerHTML = '<p class=\"text-muted\">No available jobs at the moment</p>';
    }
}
</script>

</body>
</html>";
?>
