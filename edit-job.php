<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('index.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Only employers should access this page
if ($user_role !== 'employer') {
    redirect('dashboard.php');
}

// Get job ID from URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Initialize variables
$job = null;
$error_message = '';
$success_message = '';

if ($job_id > 0) {
    // Fetch job details
    $sql = "SELECT * FROM jobs WHERE id = :job_id AND employer_id = :user_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        $error_message = "Job not found or you don't have permission to edit this job.";
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_job'])) {
    // Get form data
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $job_type = sanitize_input($_POST['type']);
    $salary = sanitize_input($_POST['salary']);
    $location = sanitize_input($_POST['location']);
    $work_hours = sanitize_input($_POST['work_hours']);
    $requirements = sanitize_input($_POST['requirements']);
    
    // Validate input
    $errors = [];
    if (empty($title)) $errors[] = 'Job title is required';
    if (empty($description)) $errors[] = 'Job description is required';
    if (empty($job_type)) $errors[] = 'Job type is required';
    if (empty($salary) || $salary <= 0) $errors[] = 'Valid salary is required';
    if (empty($location)) $errors[] = 'Location is required';
    if (empty($work_hours)) $errors[] = 'Work hours are required';
    if (empty($requirements)) $errors[] = 'Requirements are required';
    
    if (empty($errors)) {
        // Update job in database
        $sql = "UPDATE jobs SET title = :title, description = :description, type = :type, 
                               salary = :salary, location = :location, work_hours = :work_hours, 
                               requirements = :requirements, updated_at = CURRENT_TIMESTAMP 
                               WHERE id = :job_id AND employer_id = :user_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':type', $job_type, PDO::PARAM_STR);
        $stmt->bindParam(':salary', $salary, PDO::PARAM_STR);
        $stmt->bindParam(':location', $location, PDO::PARAM_STR);
        $stmt->bindParam(':work_hours', $work_hours, PDO::PARAM_STR);
        $stmt->bindParam(':requirements', $requirements, PDO::PARAM_STR);
        $stmt->bindParam(':job_id', $job_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $success_message = "Job updated successfully!";
            // Redirect to my-jobs page
            header('Location: my-jobs.php');
            exit();
        } else {
            $error_message = "Failed to update job. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 800px;
            margin-top: 80px;
        }
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="form-section">
                    <h2 class="mb-4">
                        <i class="fas fa-edit me-2"></i>
                        Edit Job
                    </h2>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($job): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="update_job" value="1">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Job Title *</label>
                                    <input type="text" class="form-control" name="title" 
                                           value="<?php echo htmlspecialchars($job['title']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Job Type *</label>
                                    <select class="form-select" name="type" required>
                                        <option value="cleaning" <?php echo $job['type'] === 'cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                                        <option value="cooking" <?php echo $job['type'] === 'cooking' ? 'selected' : ''; ?>>Cooking</option>
                                        <option value="childcare" <?php echo $job['type'] === 'childcare' ? 'selected' : ''; ?>>Childcare</option>
                                        <option value="eldercare" <?php echo $job['type'] === 'eldercare' ? 'selected' : ''; ?>>Eldercare</option>
                                        <option value="gardening" <?php echo $job['type'] === 'gardening' ? 'selected' : ''; ?>>Gardening</option>
                                        <option value="other" <?php echo $job['type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Salary (RWF) *</label>
                                    <input type="number" class="form-control" name="salary" 
                                           value="<?php echo htmlspecialchars($job['salary']); ?>" required step="0.01">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Location *</label>
                                    <input type="text" class="form-control" name="location" 
                                           value="<?php echo htmlspecialchars($job['location']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Work Hours *</label>
                                <input type="text" class="form-control" name="work_hours" 
                                       value="<?php echo htmlspecialchars($job['work_hours']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Job Requirements *</label>
                                <textarea class="form-control" name="requirements" rows="5" required><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Job Description *</label>
                                <textarea class="form-control" name="description" rows="5" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Update Job
                                </button>
                                <a href="my-jobs.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    Cancel
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
