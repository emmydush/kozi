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

$post_job_translations = [
    'en' => [
        'page_title' => 'Post a New Job',
        'page_subtitle' => 'Find the perfect worker for your household needs',
        'section_basic' => 'Basic Information',
        'section_details' => 'Job Details',
        'label_title' => 'Job Title *',
        'label_type' => 'Job Type *',
        'label_description' => 'Job Description *',
        'label_salary' => 'Monthly Salary (RWF) *',
        'label_location' => 'Location *',
        'label_work_hours' => 'Work Hours *',
        'label_requirements' => 'Requirements',
        'label_experience' => 'Experience Required',
        'placeholder_title' => 'e.g., House Cleaner, Childcare Provider',
        'placeholder_select_type' => 'Select job type',
        'placeholder_description' => "Describe the job responsibilities, tasks, and what you're looking for in a worker",
        'placeholder_salary' => 'e.g., 50000',
        'placeholder_location' => 'e.g., Kigali, Kicukiro',
        'placeholder_work_hours' => 'e.g., 8 hours/day, Monday-Friday',
        'placeholder_requirements' => 'List any specific requirements or qualifications needed',
        'placeholder_experience' => 'e.g., 2+ years of experience, references required',
        'type_cleaning' => 'House Cleaning',
        'type_cooking' => 'Cooking',
        'type_childcare' => 'Childcare',
        'type_eldercare' => 'Elderly Care',
        'type_gardening' => 'Gardening',
        'type_driving' => 'Driving',
        'type_other' => 'Other',
        'success' => 'Job posted successfully! Workers can now apply for your position.',
        'error_failed' => 'Failed to post job. Please try again.',
        'validation_title' => 'Job title is required',
        'validation_description' => 'Job description is required',
        'validation_type' => 'Job type is required',
        'validation_salary' => 'Valid salary is required',
        'validation_location' => 'Location is required',
        'validation_work_hours' => 'Work hours are required',
        'cancel' => 'Cancel',
    ],
    'fr' => [
        'page_title' => 'Publier une nouvelle offre',
        'page_subtitle' => 'Trouvez le travailleur ideal pour les besoins de votre foyer',
        'section_basic' => 'Informations de base',
        'section_details' => 'Details du poste',
        'label_title' => 'Titre du poste *',
        'label_type' => 'Type de poste *',
        'label_description' => 'Description du poste *',
        'label_salary' => 'Salaire mensuel (RWF) *',
        'label_location' => 'Lieu *',
        'label_work_hours' => 'Heures de travail *',
        'label_requirements' => 'Exigences',
        'label_experience' => 'Experience requise',
        'placeholder_title' => 'ex. Femme de menage, Garde d\'enfants',
        'placeholder_select_type' => 'Selectionnez le type de poste',
        'placeholder_description' => 'Decrivez les responsabilites, les taches et le profil recherche',
        'placeholder_salary' => 'ex. 50000',
        'placeholder_location' => 'ex. Kigali, Kicukiro',
        'placeholder_work_hours' => 'ex. 8 heures/jour, lundi-vendredi',
        'placeholder_requirements' => 'Listez les exigences ou qualifications necessaires',
        'placeholder_experience' => 'ex. 2+ ans d\'experience, references requises',
        'type_cleaning' => 'Menage',
        'type_cooking' => 'Cuisine',
        'type_childcare' => 'Garde d\'enfants',
        'type_eldercare' => 'Soins aux personnes agees',
        'type_gardening' => 'Jardinage',
        'type_driving' => 'Conduite',
        'type_other' => 'Autre',
        'success' => 'Offre publiee avec succes ! Les travailleurs peuvent maintenant postuler.',
        'error_failed' => 'Echec de la publication de l\'offre. Veuillez reessayer.',
        'validation_title' => 'Le titre du poste est obligatoire',
        'validation_description' => 'La description du poste est obligatoire',
        'validation_type' => 'Le type de poste est obligatoire',
        'validation_salary' => 'Un salaire valide est obligatoire',
        'validation_location' => 'Le lieu est obligatoire',
        'validation_work_hours' => 'Les heures de travail sont obligatoires',
        'cancel' => 'Annuler',
    ],
    'rw' => [
        'page_title' => 'Tangaza akazi gashya',
        'page_subtitle' => 'Shaka umukozi ukwiriye ibyo urugo rwawe rukeneye',
        'section_basic' => 'Amakuru y\'ibanze',
        'section_details' => 'Ibisobanuro by\'akazi',
        'label_title' => 'Umutwe w\'akazi *',
        'label_type' => 'Ubwoko bw\'akazi *',
        'label_description' => 'Ibisobanuro by\'akazi *',
        'label_salary' => 'Umushahara wa buri kwezi (RWF) *',
        'label_location' => 'Aho akazi gakorerwa *',
        'label_work_hours' => 'Amasaha y\'akazi *',
        'label_requirements' => 'Ibisabwa',
        'label_experience' => 'Uburambe busabwa',
        'placeholder_title' => 'nko: Umukozi wo gukora isuku, Umurera w\'abana',
        'placeholder_select_type' => 'Hitamo ubwoko bw\'akazi',
        'placeholder_description' => 'Sobanura inshingano z\'akazi, imirimo, n\'umukozi ushaka',
        'placeholder_salary' => 'nko: 50000',
        'placeholder_location' => 'nko: Kigali, Kicukiro',
        'placeholder_work_hours' => 'nko: amasaha 8 ku munsi, Kuwa mbere-Kuwa gatanu',
        'placeholder_requirements' => 'Andika ibisabwa cyangwa ubumenyi bukenewe',
        'placeholder_experience' => 'nko: imyaka 2+ y\'uburambe, references zisabwa',
        'type_cleaning' => 'Isuku yo mu rugo',
        'type_cooking' => 'Guteka',
        'type_childcare' => 'Kurera abana',
        'type_eldercare' => 'Kwita ku bageze mu zabukuru',
        'type_gardening' => 'Ubuhinzi bw\'ubusitani',
        'type_driving' => 'Gutwara imodoka',
        'type_other' => 'Ibindi',
        'success' => 'Akazi katangajwe neza! Abakozi bashobora kugasaba ubu.',
        'error_failed' => 'Kwamamaza akazi byanze. Ongera ugerageze.',
        'validation_title' => 'Umutwe w\'akazi urakenewe',
        'validation_description' => 'Ibisobanuro by\'akazi birakenewe',
        'validation_type' => 'Ubwoko bw\'akazi burakenewe',
        'validation_salary' => 'Umushahara wemewe urakenewe',
        'validation_location' => 'Aho akazi gakorerwa harakenewe',
        'validation_work_hours' => 'Amasaha y\'akazi arakenewe',
        'cancel' => 'Hagarika',
    ],
];

function pj($key)
{
    global $post_job_translations;
    $lang = current_language();
    return $post_job_translations[$lang][$key] ?? $post_job_translations['en'][$key] ?? $key;
}

// Only employers should access this page
if ($user_role !== 'employer') {
    redirect('dashboard.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $job_type = sanitize_input($_POST['job_type']);
    $salary = floatval($_POST['salary']);
    $location = sanitize_input($_POST['location']);
    $work_hours = sanitize_input($_POST['work_hours']);
    $requirements = sanitize_input($_POST['requirements']);
    $experience_required = sanitize_input($_POST['experience_required']);
    
    // Validate required fields
    $errors = [];
    if (empty($title)) $errors[] = pj('validation_title');
    if (empty($description)) $errors[] = pj('validation_description');
    if (empty($job_type)) $errors[] = pj('validation_type');
    if (empty($salary) || $salary <= 0) $errors[] = pj('validation_salary');
    if (empty($location)) $errors[] = pj('validation_location');
    if (empty($work_hours)) $errors[] = pj('validation_work_hours');
    
    if (empty($errors)) {
        // Insert job into database
        $sql = "INSERT INTO jobs (employer_id, title, description, type, salary, location, work_hours, requirements, status, created_at, updated_at) 
                VALUES (:employer_id, :title, :description, :type, :salary, :location, :work_hours, :requirements, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':employer_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':type', $job_type, PDO::PARAM_STR);
        $stmt->bindParam(':salary', $salary, PDO::PARAM_STR);
        $stmt->bindParam(':location', $location, PDO::PARAM_STR);
        $stmt->bindParam(':work_hours', $work_hours, PDO::PARAM_STR);
        $stmt->bindParam(':requirements', $requirements, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            $success_message = pj('success');
            // Clear form
            $title = $description = $job_type = $salary = $location = $work_hours = $requirements = $experience_required = '';
        } else {
            $error_message = pj('error_failed');
        }
    } else {
        $error_message = implode(', ', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('common.post_job'); ?> - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 60px);
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            position: fixed;
            top: 60px;
            left: 0;
            width: 250px;
            z-index: 1000;
            transition: all 0.3s;
            transform: translateX(-100%);
            border-radius: 0 20px 20px 0;
            box-shadow: 4px 0 12px rgba(0,0,0,0.15);
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 15px 20px;
            border-radius: 12px;
            margin: 5px 10px;
            transition: all 0.3s;
            min-height: 50px;
            display: flex;
            align-items: center;
            font-size: 0.95rem;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            margin-right: 12px;
            width: 20px;
            font-size: 1rem;
        }
        
        .main-content {
            margin-left: 0;
            padding: 15px;
            min-height: calc(100vh - 60px);
            margin-top: 60px;
            background: #f8f9fa;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0 20px 0 0;
        }
        
        .sidebar-header h3 {
            color: white;
            margin: 0;
            font-size: 1.2rem;
        }
        
        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 250px;
                padding: 20px;
            }
        }
        
        @media (min-width: 768px) and (max-width: 991px) {
            .sidebar {
                width: 260px;
            }
        }
        
        .btn {
            min-height: 44px;
            padding: 12px 20px;
            font-size: 0.95rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #000000, #333333);
            color: white;
        }
        
        .btn-outline-primary {
            border: 2px solid #000000;
            color: #000000;
            background: white;
        }
        
        .btn-outline-primary:hover {
            background: #000000;
            color: white;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: none;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .form-control, .form-select {
            min-height: 44px;
            padding: 10px 15px;
            font-size: 16px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }
        
        h2 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #000000;
            font-weight: 700;
        }
        
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .form-section h5 {
            color: #000000;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <nav class="nav flex-column p-3">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> <?php echo t('common.dashboard'); ?>
            </a>
            <a class="nav-link active" href="post-job.php">
                <i class="fas fa-plus-circle"></i> <?php echo t('common.post_job'); ?>
            </a>
            <a class="nav-link" href="workers.php">
                <i class="fas fa-users"></i> <?php echo t('common.find_workers'); ?>
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> <?php echo t('common.my_jobs'); ?>
            </a>
            <a class="nav-link" href="bookings.php">
                <i class="fas fa-calendar-check"></i> <?php echo t('common.bookings'); ?>
            </a>
            
            <a class="nav-link" href="messages.php">
                <i class="fas fa-envelope"></i> <?php echo t('common.messages'); ?>
            </a>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user-cog"></i> <?php echo t('nav.profile_settings'); ?>
            </a>
            <a class="nav-link" href="reviews.php">
                <i class="fas fa-star"></i> <?php echo t('common.reviews'); ?>
            </a>
            
            <hr class="text-white-50">
            
            <a class="nav-link" href="help.php">
                <i class="fas fa-question-circle"></i> <?php echo t('nav.help_support'); ?>
            </a>
            <a class="nav-link" href="api/logout.php">
                <i class="fas fa-sign-out-alt"></i> <?php echo t('nav.logout'); ?>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row">
            <div class="col-12">
                <h2><?php echo pj('page_title'); ?></h2>
                <p class="text-muted"><?php echo pj('page_subtitle'); ?></p>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- Basic Information -->
            <div class="form-section">
                <h5><i class="fas fa-info-circle me-2"></i><?php echo pj('section_basic'); ?></h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label"><?php echo pj('label_title'); ?></label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" 
                               placeholder="<?php echo pj('placeholder_title'); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="job_type" class="form-label"><?php echo pj('label_type'); ?></label>
                        <select class="form-select" id="job_type" name="job_type" required>
                            <option value=""><?php echo pj('placeholder_select_type'); ?></option>
                            <option value="cleaning" <?php echo (isset($job_type) && $job_type === 'cleaning') ? 'selected' : ''; ?>><?php echo pj('type_cleaning'); ?></option>
                            <option value="cooking" <?php echo (isset($job_type) && $job_type === 'cooking') ? 'selected' : ''; ?>><?php echo pj('type_cooking'); ?></option>
                            <option value="childcare" <?php echo (isset($job_type) && $job_type === 'childcare') ? 'selected' : ''; ?>><?php echo pj('type_childcare'); ?></option>
                            <option value="eldercare" <?php echo (isset($job_type) && $job_type === 'eldercare') ? 'selected' : ''; ?>><?php echo pj('type_eldercare'); ?></option>
                            <option value="gardening" <?php echo (isset($job_type) && $job_type === 'gardening') ? 'selected' : ''; ?>><?php echo pj('type_gardening'); ?></option>
                            <option value="driving" <?php echo (isset($job_type) && $job_type === 'driving') ? 'selected' : ''; ?>><?php echo pj('type_driving'); ?></option>
                            <option value="other" <?php echo (isset($job_type) && $job_type === 'other') ? 'selected' : ''; ?>><?php echo pj('type_other'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label"><?php echo pj('label_description'); ?></label>
                    <textarea class="form-control" id="description" name="description" rows="4" 
                              placeholder="<?php echo pj('placeholder_description'); ?>" required><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>
            </div>

            <!-- Job Details -->
            <div class="form-section">
                <h5><i class="fas fa-briefcase me-2"></i><?php echo pj('section_details'); ?></h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="salary" class="form-label"><?php echo pj('label_salary'); ?></label>
                        <input type="number" class="form-control" id="salary" name="salary" 
                               value="<?php echo isset($salary) ? htmlspecialchars($salary) : ''; ?>" 
                               placeholder="<?php echo pj('placeholder_salary'); ?>" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="location" class="form-label"><?php echo pj('label_location'); ?></label>
                        <input type="text" class="form-control" id="location" name="location" 
                               value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" 
                               placeholder="<?php echo pj('placeholder_location'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="work_hours" class="form-label"><?php echo pj('label_work_hours'); ?></label>
                        <input type="text" class="form-control" id="work_hours" name="work_hours" 
                               value="<?php echo isset($work_hours) ? htmlspecialchars($work_hours) : ''; ?>" 
                               placeholder="<?php echo pj('placeholder_work_hours'); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="requirements" class="form-label"><?php echo pj('label_requirements'); ?></label>
                        <textarea class="form-control" id="requirements" name="requirements" rows="3" 
                                  placeholder="<?php echo pj('placeholder_requirements'); ?>"><?php echo isset($requirements) ? htmlspecialchars($requirements) : ''; ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="experience_required" class="form-label"><?php echo pj('label_experience'); ?></label>
                        <textarea class="form-control" id="experience_required" name="experience_required" rows="3" 
                                  placeholder="<?php echo pj('placeholder_experience'); ?>"><?php echo isset($experience_required) ? htmlspecialchars($experience_required) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i><?php echo t('common.post_job'); ?>
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i><?php echo pj('cancel'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('mobile-menu-toggle');
            
            if (window.innerWidth < 992 && 
                !sidebar.contains(event.target) && 
                !toggle?.contains(event.target) && 
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('show');
            }
        });
    </script>
</body>
</html>
