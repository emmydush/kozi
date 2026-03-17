<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

$dashboard_translations = [
    'en' => [
        'title' => 'Dashboard - KOZI',
        'brand' => '',
        'dashboard' => 'Dashboard',
        'post_job' => 'Post Job',
        'job_applications' => 'Job Applications',
        'find_workers' => 'Find Workers',
        'my_jobs' => 'My Jobs',
        'bookings' => 'Bookings',
        'find_jobs' => 'Find Jobs',
        'my_applications' => 'My Applications',
        'active_jobs' => 'Active Jobs',
        'messages' => 'Messages',
        'help_support' => 'Help & Support',
        'logout' => 'Logout',
        'welcome_back' => 'Welcome back, %s!',
        'welcome_subtitle' => "We're glad to see you again! Here's what's happening with your %s dashboard today.",
        'create_worker_profile' => 'Create Your Worker Profile',
        'create_worker_profile_text' => 'To start finding jobs, you need to create your worker profile. This will help employers learn about your skills and experience.',
        'create_profile_now' => 'Create Profile Now',
        'posted_jobs' => 'Posted Jobs',
        'active_bookings' => 'Active Bookings',
        'available_workers' => 'Available Workers',
        'search_workers' => 'Search workers...',
        'all_types' => 'All Types',
        'cleaning' => 'Cleaning',
        'childcare' => 'Childcare',
        'gardening' => 'Gardening',
        'eldercare' => 'Elder Care',
        'cooking' => 'Cooking',
        'loading_available_workers' => 'Loading available workers...',
        'recent_job_postings' => 'Recent Job Postings',
        'loading_recent_jobs' => 'Loading recent jobs...',
        'jobs_applied' => 'Jobs Applied',
        'available_jobs' => 'Available Jobs',
        'loading_available_jobs' => 'Loading available jobs...',
        'quick_actions' => 'Quick Actions',
        'update_profile' => 'Update Profile',
        'browse_jobs' => 'Browse Jobs',
        'worker_profile' => 'Worker Profile',
        'close' => 'Close',
        'contact' => 'Contact',
        'book_now' => 'Book Now',
        'contact_worker' => 'Contact Worker',
        'subject' => 'Subject',
        'message' => 'Message',
        'cancel' => 'Cancel',
        'send_message' => 'Send Message',
        'book_worker' => 'Book Worker',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
        'service_type' => 'Service Type',
        'additional_notes' => 'Additional Notes',
        'create_booking' => 'Create Booking',
        'available' => 'Available',
        'applied' => 'Applied',
        'unknown' => 'Unknown',
        'apply_now' => 'Apply Now',
        'already_applied' => 'Already Applied',
        'not_available' => 'Not Available',
        'no_recent_jobs' => 'No recent job postings',
        'no_available_jobs' => 'No available jobs at the moment',
        'application_soon' => 'Application functionality will be implemented soon!',
        'loading_workers' => 'Loading workers...',
        'no_workers_match' => 'No workers found matching your criteria.',
        'general_worker' => 'General Worker',
        'no_description' => 'No description available',
        'view_profile' => 'View Profile',
        'no_workers_yet' => 'No Workers Available Yet',
        'no_workers_yet_text' => 'Be the first to register as a worker and start connecting with employers looking for household services!',
        'register_worker' => 'Register as Worker',
        'previous' => 'Previous',
        'next' => 'Next',
        'contact_soon' => 'Contact functionality will be implemented soon!',
        'loading_worker_profile' => 'Loading worker profile...',
        'failed_load_worker_profile' => 'Failed to load worker profile. Please try again.',
        'name_not_available' => 'Name not available',
        'reviews' => 'reviews',
        'location_not_specified' => 'Location not specified',
        'experience_not_specified' => 'Experience not specified',
        'years' => 'years',
        'other' => 'Other',
        'available_for_work' => 'Available for work',
        'reviews_title' => 'Reviews',
        'contact_information' => 'Contact Information',
        'skills_title' => 'Skills',
        'national_id_unavailable' => 'National ID photo not available',
        'no_certifications' => 'No certifications listed',
        'no_specific_skills' => 'No specific skills listed',
        'no_reviews_yet' => 'No reviews yet',
        'no_comment' => 'No comment provided',
    ],
    'fr' => [
        'title' => 'Tableau de bord - KOZI','brand' => '','dashboard' => 'Tableau de bord','post_job' => 'Publier une offre','job_applications' => 'Candidatures','find_workers' => 'Trouver des travailleurs','my_jobs' => 'Mes offres','bookings' => 'Reservations','find_jobs' => 'Trouver des offres','my_applications' => 'Mes candidatures','active_jobs' => 'Offres actives','messages' => 'Messages','help_support' => 'Aide et support','logout' => 'Deconnexion','welcome_back' => 'Bon retour, %s !','welcome_subtitle' => 'Heureux de vous revoir ! Voici ce qui se passe aujourd\'hui sur votre tableau de bord %s.','create_worker_profile' => 'Creez votre profil travailleur','create_worker_profile_text' => 'Pour commencer a trouver des offres, vous devez creer votre profil. Cela aidera les employeurs a connaitre vos competences et votre experience.','create_profile_now' => 'Creer le profil maintenant','posted_jobs' => 'Offres publiees','active_bookings' => 'Reservations actives','available_workers' => 'Travailleurs disponibles','search_workers' => 'Rechercher des travailleurs...','all_types' => 'Tous les types','cleaning' => 'Nettoyage','childcare' => 'Garde d\'enfants','gardening' => 'Jardinage','eldercare' => 'Aide aux personnes agees','cooking' => 'Cuisine','loading_available_workers' => 'Chargement des travailleurs disponibles...','recent_job_postings' => 'Offres recentes','loading_recent_jobs' => 'Chargement des offres recentes...','jobs_applied' => 'Offres postulees','available_jobs' => 'Offres disponibles','loading_available_jobs' => 'Chargement des offres disponibles...','quick_actions' => 'Actions rapides','update_profile' => 'Mettre a jour le profil','browse_jobs' => 'Parcourir les offres','worker_profile' => 'Profil du travailleur','close' => 'Fermer','contact' => 'Contacter','book_now' => 'Reserver','contact_worker' => 'Contacter le travailleur','subject' => 'Sujet','message' => 'Message','cancel' => 'Annuler','send_message' => 'Envoyer le message','book_worker' => 'Reserver le travailleur','start_date' => 'Date de debut','end_date' => 'Date de fin','service_type' => 'Type de service','additional_notes' => 'Notes supplementaires','create_booking' => 'Creer une reservation','available' => 'Disponible','applied' => 'Postule','unknown' => 'Inconnu','apply_now' => 'Postuler','already_applied' => 'Deja postule','not_available' => 'Indisponible','no_recent_jobs' => 'Aucune offre recente','no_available_jobs' => 'Aucune offre disponible pour le moment','application_soon' => 'La fonctionnalite de candidature arrive bientot !','loading_workers' => 'Chargement des travailleurs...','no_workers_match' => 'Aucun travailleur ne correspond a vos criteres.','general_worker' => 'Travailleur general','no_description' => 'Aucune description disponible','view_profile' => 'Voir le profil','no_workers_yet' => 'Aucun travailleur disponible pour le moment','no_workers_yet_text' => 'Soyez la premiere personne a vous inscrire comme travailleur et commencez a entrer en contact avec des employeurs !','register_worker' => 'S\'inscrire comme travailleur','previous' => 'Precedent','next' => 'Suivant','contact_soon' => 'La fonctionnalite de contact arrive bientot !','loading_worker_profile' => 'Chargement du profil du travailleur...','failed_load_worker_profile' => 'Impossible de charger le profil du travailleur. Veuillez reessayer.','name_not_available' => 'Nom non disponible','reviews' => 'avis','location_not_specified' => 'Lieu non precise','experience_not_specified' => 'Experience non precisee','years' => 'ans','other' => 'Autre','available_for_work' => 'Disponible pour travailler','reviews_title' => 'Avis','contact_information' => 'Informations de contact','skills_title' => 'Competences','national_id_unavailable' => 'Photo de carte d\'identite non disponible','no_certifications' => 'Aucune certification indiquee','no_specific_skills' => 'Aucune competence specifique indiquee','no_reviews_yet' => 'Aucun avis pour le moment','no_comment' => 'Aucun commentaire fourni',
    ],
    'rw' => [
        'title' => 'Imbonerahamwe - KOZI','brand' => '','dashboard' => 'Imbonerahamwe','post_job' => 'Tangaza akazi','job_applications' => 'Abasabye akazi','find_workers' => 'Shaka abakozi','my_jobs' => 'Akazi kanjye','bookings' => 'Bokingi','find_jobs' => 'Shaka akazi','my_applications' => 'Ubusabe bwanjye','active_jobs' => 'Akazi gakora','messages' => 'Ubutumwa','help_support' => 'Ubufasha na serivisi','logout' => 'Sohoka','welcome_back' => 'Murakaza neza, %s!','welcome_subtitle' => 'Twishimiye kongera kukubona! Dore ibiri kubera kuri konti yawe ya %s uyu munsi.','create_worker_profile' => 'Kora profili yawe y\'umukozi','create_worker_profile_text' => 'Kugira ngo utangire gushaka akazi, ugomba kubanza gukora profili yawe. Bizafasha abakoresha kumenya ubushobozi n\'uburambe bwawe.','create_profile_now' => 'Kora profili nonaha','posted_jobs' => 'Akazi katangajwe','active_bookings' => 'Bokingi zikora','available_workers' => 'Abakozi baboneka','search_workers' => 'Shaka abakozi...','all_types' => 'Ubwoko bwose','cleaning' => 'Isuku','childcare' => 'Kurera abana','gardening' => 'Ubusitani','eldercare' => 'Kwita ku bageze mu zabukuru','cooking' => 'Guteka','loading_available_workers' => 'Turimo gupakira abakozi baboneka...','recent_job_postings' => 'Akazi katangajwe vuba','loading_recent_jobs' => 'Turimo gupakira akazi ka vuba...','jobs_applied' => 'Akazi wasabiye','available_jobs' => 'Akazi kaboneka','loading_available_jobs' => 'Turimo gupakira akazi kaboneka...','quick_actions' => 'Ibikorwa byihuse','update_profile' => 'Hindura profili','browse_jobs' => 'Reba akazi','worker_profile' => 'Profili y\'umukozi','close' => 'Funga','contact' => 'Vugana','book_now' => 'Buka nonaha','contact_worker' => 'Vugana n\'umukozi','subject' => 'Umutwe','message' => 'Ubutumwa','cancel' => 'Hagarika','send_message' => 'Ohereza ubutumwa','book_worker' => 'Buka umukozi','start_date' => 'Itariki yo gutangira','end_date' => 'Itariki yo kurangiza','service_type' => 'Ubwoko bwa serivisi','additional_notes' => 'Andi makuru','create_booking' => 'Kora bokingi','available' => 'Biraboneka','applied' => 'Warasabye','unknown' => 'Ntibizwi','apply_now' => 'Saba nonaha','already_applied' => 'Warasabye','not_available' => 'Ntibiboneka','no_recent_jobs' => 'Nta kazi katangajwe vuba','no_available_jobs' => 'Nta kazi kaboneka ubu','application_soon' => 'Uburyo bwo gusaba akazi buraza vuba!','loading_workers' => 'Turimo gupakira abakozi...','no_workers_match' => 'Nta bakozi bahuye n\'ibyo washakaga.','general_worker' => 'Umukozi rusange','no_description' => 'Nta bisobanuro bihari','view_profile' => 'Reba profili','no_workers_yet' => 'Nta bakozi baraboneka kugeza ubu','no_workers_yet_text' => 'Ba uwa mbere wiyandikisha nk\'umukozi utangire guhuzwa n\'abakoresha bashaka serivisi zo mu rugo!','register_worker' => 'Iyandikishe nk\'umukozi','previous' => 'Ibanza','next' => 'Ikurikira','contact_soon' => 'Uburyo bwo kuvugana buraza vuba!','loading_worker_profile' => 'Turimo gupakira profili y\'umukozi...','failed_load_worker_profile' => 'Ntibyakunze gupakira profili y\'umukozi. Ongera ugerageze.','name_not_available' => 'Izina ntirihari','reviews' => 'ibitekerezo','location_not_specified' => 'Aho aherereye ntihasobanuwe','experience_not_specified' => 'Uburambe ntibwasobanuwe','years' => 'imyaka','other' => 'Ibindi','available_for_work' => 'Ariteguye gukora','reviews_title' => 'Ibitekerezo','contact_information' => 'Amakuru yo kuvugana','skills_title' => 'Ubumenyi','national_id_unavailable' => 'Ifoto y\'indangamuntu ntiboneka','no_certifications' => 'Nta byangombwa byanditswe','no_specific_skills' => 'Nta bumenyi bwihariye bwanditswe','no_reviews_yet' => 'Nta bitekerezo birabaho','no_comment' => 'Nta gitekerezo cyatanzwe',
    ],
];

$dlang = $dashboard_translations[current_language()] ?? $dashboard_translations['en'];
function dt($key) {
    global $dlang;
    return $dlang[$key] ?? $key;
}

// Check if worker has a profile (only for workers)
$has_worker_profile = false;
if ($user_role === 'worker') {
    $check_sql = "SELECT id FROM workers WHERE user_id = :user_id";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $has_worker_profile = $result !== false;
    
    // Force workers to complete profile before accessing dashboard
    if (!$has_worker_profile) {
        redirect('create-worker-profile.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(current_language()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(dt('title')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dialog.css">
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
        
        /* Update main content background for consistency */
        body {
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
        
        /* Mobile-first responsive design */
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
        
        /* Mobile optimizations */
        .card {
            margin-bottom: 1rem;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 15px;
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
        
        .btn-outline-secondary {
            border: 2px solid #e9ecef;
            color: var(--text-dark);
            background: white;
        }
        
        .btn-outline-secondary:hover {
            background: #f8f9fa;
            border-color: #e9ecef;
        }
        
        .btn-sm {
            min-height: 38px;
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        
        /* Better mobile spacing */
        .row > * {
            padding-left: 10px;
            padding-right: 10px;
        }
        
        .row {
            margin-left: -10px;
            margin-right: -10px;
        }
        
        /* Larger touch targets for mobile */
        .nav-tabs .nav-link {
            min-height: 44px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
        }
        
        /* Mobile-friendly cards */
        .card-body {
            padding: 15px;
        }
        
        .card-header {
            padding: 12px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        /* Mobile table responsiveness */
        .table-responsive {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Form optimizations for mobile */
        .form-control, .form-select {
            min-height: 44px;
            padding: 10px 15px;
            font-size: 16px; /* Prevents zoom on iOS */
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }
        
        /* Mobile-friendly badges */
        .badge {
            font-size: 0.75rem;
            padding: 6px 10px;
        }
        
        /* Better mobile typography */
        h2 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #000000;
            font-weight: 700;
        }
        
        h5 {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #000000;
            font-weight: 600;
        }
        
        /* Welcome section styling */
        .welcome-section {
            padding: 1rem 0 2rem 0;
            margin-bottom: 2rem;
        }
        
        .welcome-title {
            color: #000000;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .welcome-subtitle {
            color: #666666;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        .welcome-stats {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666666;
            font-size: 0.95rem;
        }
        
        .stat-item i {
            color: #000000 !important;
            font-size: 1rem;
        }
        
        .stat-text {
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .welcome-section {
                padding: 0.5rem 0 1.5rem 0;
                margin-bottom: 1.5rem;
            }
            
            .welcome-title {
                font-size: 1.5rem;
            }
            
            .welcome-subtitle {
                font-size: 1rem;
            }
            
            .welcome-stats {
                gap: 1rem;
            }
            
            .stat-item {
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) {
            .welcome-section {
                padding: 0 0 1rem 0;
                margin-bottom: 1rem;
            }
            
            .welcome-title {
                font-size: 1.3rem;
            }
            
            .welcome-stats {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
        
        /* Mobile-friendly statistics cards */
        .card.bg-primary {
            background: linear-gradient(135deg, #000000, #333333) !important;
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: none;
        }
        
        .card.bg-success {
            background: #000000 !important;
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: none;
        }
        
        .card.bg-info {
            background: #000000 !important;
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: none;
        }
        
        .card.bg-warning {
            background: #000000 !important;
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: none;
        }
        
        .card.bg-primary:hover, .card.bg-success:hover, .card.bg-info:hover, .card.bg-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        
        .card.bg-primary .card-title,
        .card.bg-success .card-title,
        .card.bg-info .card-title,
        .card.bg-warning .card-title {
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 600;
            opacity: 0.9;
        }
        
        .card.bg-primary h2,
        .card.bg-success h2,
        .card.bg-info h2,
        .card.bg-warning h2 {
            font-size: 2rem;
            margin-bottom: 0;
            font-weight: 700;
        }
        
        /* General card improvements */
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
        
        .card-header {
            border-radius: 15px 15px 0 0;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.25rem;
            background: #f8f9fa;
            font-weight: 600;
        }
        
        /* Better responsive grid */
        @media (max-width: 768px) {
            .row > * {
                padding-left: 8px;
                padding-right: 8px;
            }
            
            .row {
                margin-left: -8px;
                margin-right: -8px;
            }
            
            .col-lg-3.col-md-6.col-sm-12 {
                margin-bottom: 1rem;
            }
            
            .card.bg-primary h2,
            .card.bg-success h2,
            .card.bg-info h2,
            .card.bg-warning h2 {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .row > * {
                padding-left: 5px;
                padding-right: 5px;
            }
            
            .row {
                margin-left: -5px;
                margin-right: -5px;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .card-header {
                padding: 0.75rem 1rem;
            }
            
            .card.bg-primary h2,
            .card.bg-success h2,
            .card.bg-info h2,
            .card.bg-warning h2 {
                font-size: 1.3rem;
            }
            
            .card.bg-primary .card-title,
            .card.bg-success .card-title,
            .card.bg-info .card-title,
            .card.bg-warning .card-title {
                font-size: 0.8rem;
            }
        }
        
        /* Mobile-friendly dropdowns */
        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: none;
        }
        
        /* Better mobile spacing for lists */
        .list-group-item {
            padding: 15px;
            border: none;
            border-bottom: 1px solid #dee2e6;
        }
        
        .list-group-item:last-child {
            border-bottom: none;
        }
        
        /* Ensure card numbers are visible */
        .card.bg-primary h2,
        .card.bg-success h2,
        .card.bg-info h2,
        .card.bg-warning h2 {
            font-size: 2rem;
            margin-bottom: 0;
            font-weight: 700;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            line-height: 1.2;
        }
        
        /* Fix any potential overflow issues */
        .card-body {
            overflow: hidden;
        }
        
        /* Ensure proper spacing for numbers */
        .card-body h2 {
            min-height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Modern Worker Cards */
        .modern-worker-card {
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            background: white;
        }

        .modern-worker-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: #007bff;
        }

        .modern-worker-card .rounded-circle {
            transition: transform 0.3s ease;
        }

        .modern-worker-card:hover .rounded-circle {
            transform: scale(1.05);
        }

        .modern-worker-card .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .modern-worker-card .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-1px);
        }

        .modern-worker-card .card-title {
            color: #212529;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .modern-worker-card .text-muted {
            color: #6c757d !important;
            font-size: 0.9rem;
        }

        .modern-worker-card .card-text {
            color: #6c757d;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        /* Minimal worker cards styling */
        .worker-card {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            height: 100%;
            background: white;
        }
        
        .worker-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        
        .worker-card .card-img-top {
            height: 180px;
            object-fit: cover;
            position: relative;
            width: 100%;
        }
        
        .worker-card .card-img-placeholder {
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .worker-card .card-img-placeholder span {
            color: white;
            font-size: 3rem;
            font-weight: bold;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .worker-card .profile-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.1) 100%);
            pointer-events: none;
        }
        
        .worker-card .card-body {
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
        }
        
        .worker-card .worker-info {
            text-align: center;
        }
        
        .worker-card .worker-name {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #333;
        }
        
        .worker-card .worker-type {
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .worker-card .worker-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.8rem;
        }
        
        .worker-card .worker-rating {
            color: #ffc107;
        }
        
        .worker-card .worker-rate {
            font-weight: 600;
            color: #007bff;
        }
        
        .worker-card .worker-actions {
            margin-top: auto;
        }
        
        .worker-card .btn-view-profile {
            width: 100%;
            background: #007bff;
            border: none;
            color: white;
            padding: 0.4rem;
            border-radius: 4px;
            font-weight: 500;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .worker-card .btn-view-profile:hover {
            background: #0056b3;
        }
        
        .worker-card .availability-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(40, 167, 69, 0.9);
            color: white;
            padding: 0.2rem 0.4rem;
            border-radius: 10px;
            font-size: 0.6rem;
            font-weight: 600;
        }
        
        .worker-card .availability-badge.busy {
            background: rgba(255, 193, 7, 0.9);
        }
        
        /* Search and filter styling */
        .form-control-sm, .form-select-sm {
            border-radius: 8px;
        }
        
        .pagination .page-link {
            border-radius: 6px;
            margin: 0 2px;
            border: 1px solid #dee2e6;
            color: #000000;
        }
        
        .pagination .page-link:hover {
            background-color: #f8f9fa;
            border-color: #000000;
            color: #000000;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #000000;
            border-color: #000000;
        }
        
        .pagination .page-item.btn-outline-primary:hover {
            background-color: #000000;
            border-color: #000000;
        }

        /* Smartphone layout for dashboard cards */
        @media (max-width: 576px) {
            .row .col-6 {
                padding-left: 5px !important;
                padding-right: 5px !important;
            }
            
            .row .col-6 .card {
                margin-bottom: 10px;
            }
            
            .row .col-6 .card .card-body {
                padding: 15px;
            }
            
            .row .col-6 .card h5 {
                font-size: 0.9rem;
                margin-bottom: 10px;
            }
            
            .row .col-6 .card h2 {
                font-size: 1.5rem;
                margin-bottom: 0;
            }
        }

        /* Sticky search header styles */
        .sticky-search-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background-color: white;
            border-bottom: 1px solid rgba(0,0,0,.125);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .sticky-search-header:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-home"></i> <?php echo htmlspecialchars(dt('brand')); ?></h3>
        </div>
        
        <nav class="nav flex-column p-3">
            <a class="nav-link active" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> <?php echo htmlspecialchars(dt('dashboard')); ?>
            </a>
            
            <?php if ($user_role === 'employer'): ?>
            <a class="nav-link" href="post-job.php">
                <i class="fas fa-plus-circle"></i> <?php echo htmlspecialchars(dt('post_job')); ?>
            </a>
            <a class="nav-link" href="job-applications.php">
                <i class="fas fa-users"></i> <?php echo htmlspecialchars(dt('job_applications')); ?>
            </a>
            <a class="nav-link" href="workers.php">
                <i class="fas fa-search"></i> <?php echo htmlspecialchars(dt('find_workers')); ?>
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars(dt('my_jobs')); ?>
            </a>
            <a class="nav-link" href="bookings.php">
                <i class="fas fa-calendar-check"></i> <?php echo htmlspecialchars(dt('bookings')); ?>
            </a>
            <?php else: ?>
            <a class="nav-link" href="jobs.php">
                <i class="fas fa-search"></i> <?php echo htmlspecialchars(dt('find_jobs')); ?>
            </a>
            <a class="nav-link" href="my-applications.php">
                <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars(dt('my_applications')); ?>
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars(dt('active_jobs')); ?>
            </a>
            <a class="nav-link" href="earnings.php">
                <i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars(t('nav.earnings')); ?>
            </a>
            <?php endif; ?>
            
            <a class="nav-link" href="messages.php">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars(dt('messages')); ?>
            </a>
            
            <hr class="text-white-50">
            
            <a class="nav-link" href="help.php">
                <i class="fas fa-question-circle"></i> <?php echo htmlspecialchars(dt('help_support')); ?>
            </a>
            <a class="nav-link" href="#" onclick="confirmLogout(event)">
                <i class="fas fa-sign-out-alt"></i> <?php echo htmlspecialchars(dt('logout')); ?>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row">
            <div class="col-12">
                <div class="welcome-section">
                    <h2 class="welcome-title"><?php echo htmlspecialchars(sprintf(dt('welcome_back'), $user_name)); ?> 👋</h2>
                    <p class="welcome-subtitle text-muted"><?php echo htmlspecialchars(sprintf(dt('welcome_subtitle'), $user_role)); ?></p>
                    <div class="welcome-stats d-flex gap-4 mt-3">
                        <div class="stat-item">
                            <i class="fas fa-calendar-day text-primary"></i>
                            <span class="stat-text"><?php echo date('l, F j, Y'); ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-clock text-primary"></i>
                            <span class="stat-text"><?php echo date('g:i A'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($user_role === 'worker' && !$has_worker_profile): ?>
        <!-- Worker Profile Creation Prompt -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-plus fa-2x me-3"></i>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading mb-1"><?php echo htmlspecialchars(dt('create_worker_profile')); ?></h5>
                            <p class="mb-2"><?php echo htmlspecialchars(dt('create_worker_profile_text')); ?></p>
                            <a href="create-worker-profile.php" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i><?php echo htmlspecialchars(dt('create_profile_now')); ?>
                            </a>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($user_role === 'employer'): ?>
        <!-- Employer Dashboard -->
        <div class="row mt-4">
            <div class="col-lg-6 col-md-6 col-sm-6 col-6 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars(dt('posted_jobs')); ?></h5>
                        <h2 id="posted-jobs-count">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-6 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars(dt('active_bookings')); ?></h5>
                        <h2 id="active-bookings-count">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-8 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center sticky-search-header">
                        <h5><?php echo htmlspecialchars(dt('available_workers')); ?></h5>
                        <div class="d-flex gap-2">
                            <input type="text" id="worker-search" class="form-control form-control-sm" placeholder="<?php echo htmlspecialchars(dt('search_workers')); ?>" style="width: 200px;">
                            <select id="worker-type-filter" class="form-select form-select-sm" style="width: 150px;">
                                <option value=""><?php echo htmlspecialchars(dt('all_types')); ?></option>
                                <option value="cleaning"><?php echo htmlspecialchars(dt('cleaning')); ?></option>
                                <option value="childcare"><?php echo htmlspecialchars(dt('childcare')); ?></option>
                                <option value="gardening"><?php echo htmlspecialchars(dt('gardening')); ?></option>
                                <option value="eldercare"><?php echo htmlspecialchars(dt('eldercare')); ?></option>
                                <option value="cooking"><?php echo htmlspecialchars(dt('cooking')); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="workers-container" class="row">
                            <p class="text-muted col-12"><?php echo htmlspecialchars(dt('loading_available_workers')); ?></p>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            <nav id="workers-pagination"></nav>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5><?php echo htmlspecialchars(dt('recent_job_postings')); ?></h5>
                    </div>
                    <div class="card-body">
                        <div id="recent-jobs">
                            <p class="text-muted"><?php echo htmlspecialchars(dt('loading_recent_jobs')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Worker Dashboard -->
        <div class="row mt-4">
            <div class="col-lg-6 col-md-6 col-sm-6 col-6 mb-4">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars(dt('jobs_applied')); ?></h5>
                        <h2 id="jobs-applied-count">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-6 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars(dt('active_jobs')); ?></h5>
                        <h2 id="active-jobs-count">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-8 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5><?php echo htmlspecialchars(dt('available_jobs')); ?></h5>
                    </div>
                    <div class="card-body">
                        <div id="available-jobs">
                            <p class="text-muted"><?php echo htmlspecialchars(dt('loading_available_jobs')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5><?php echo htmlspecialchars(dt('quick_actions')); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="profile.php" class="btn btn-primary"><?php echo htmlspecialchars(dt('update_profile')); ?></a>
                            <a href="jobs.php" class="btn btn-outline-primary"><?php echo htmlspecialchars(dt('browse_jobs')); ?></a>
                            <a href="messages.php" class="btn btn-outline-secondary"><?php echo htmlspecialchars(dt('messages')); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Worker Profile Modal -->
    <div class="modal fade" id="workerProfileModal" tabindex="-1" aria-labelledby="workerProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h5 class="modal-title" id="workerProfileModalLabel">
                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars(dt('worker_profile')); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="worker-profile-content">
                        <!-- Worker profile content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i><?php echo htmlspecialchars(dt('close')); ?>
                    </button>
                    <button type="button" class="btn btn-warning" id="modal-contact-btn">
                        <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars(dt('contact')); ?>
                    </button>
                    <button type="button" class="btn btn-success" id="modal-book-btn">
                        <i class="fas fa-calendar-check me-2"></i><?php echo htmlspecialchars(dt('book_now')); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo htmlspecialchars(dt('contact_worker')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="contact-form">
                        <div class="mb-3">
                            <label for="message-subject" class="form-label"><?php echo htmlspecialchars(dt('subject')); ?></label>
                            <input type="text" class="form-control" id="message-subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message-body" class="form-label"><?php echo htmlspecialchars(dt('message')); ?></label>
                            <textarea class="form-control" id="message-body" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo htmlspecialchars(dt('cancel')); ?></button>
                    <button type="button" class="btn btn-primary" id="send-message-btn"><?php echo htmlspecialchars(dt('send_message')); ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo htmlspecialchars(dt('book_worker')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="booking-form">
                        <div class="mb-3">
                            <label for="booking-start" class="form-label"><?php echo htmlspecialchars(dt('start_date')); ?></label>
                            <input type="date" class="form-control" id="booking-start" required>
                        </div>
                        <div class="mb-3">
                            <label for="booking-end" class="form-label"><?php echo htmlspecialchars(dt('end_date')); ?></label>
                            <input type="date" class="form-control" id="booking-end" required>
                        </div>
                        <div class="mb-3">
                            <label for="booking-service" class="form-label"><?php echo htmlspecialchars(dt('service_type')); ?></label>
                            <select class="form-select" id="booking-service" required>
                                <option value="cleaning"><?php echo htmlspecialchars(dt('cleaning')); ?></option>
                                <option value="cooking"><?php echo htmlspecialchars(dt('cooking')); ?></option>
                                <option value="childcare"><?php echo htmlspecialchars(dt('childcare')); ?></option>
                                <option value="eldercare"><?php echo htmlspecialchars(dt('eldercare')); ?></option>
                                <option value="gardening"><?php echo htmlspecialchars(dt('gardening')); ?></option>
                                <option value="other"><?php echo htmlspecialchars(dt('other')); ?></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="booking-notes" class="form-label"><?php echo htmlspecialchars(dt('additional_notes')); ?></label>
                            <textarea class="form-control" id="booking-notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo htmlspecialchars(dt('cancel')); ?></button>
                    <button type="button" class="btn btn-success" id="create-booking-btn"><?php echo htmlspecialchars(dt('create_booking')); ?></button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    <script src="assets/js/dialog.js"></script>
    <script>
        const dashboardI18n = <?php echo json_encode($dlang, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        function tr(key) {
            return dashboardI18n[key] || key;
        }

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
        
        // Load dashboard data
        document.addEventListener('DOMContentLoaded', function() {
            const userRole = '<?php echo isset($user_role) ? $user_role : 'unknown'; ?>';
            console.log('Dashboard loaded for user role:', userRole);
            console.log('Session variables available:', {
                userRole: userRole,
                userName: '<?php echo isset($user_name) ? $user_name : 'unknown'; ?>',
                userId: '<?php echo isset($user_id) ? $user_id : 'unknown'; ?>'
            });
            
            if (userRole === 'unknown') {
                console.error('User role not detected - redirecting to login');
                window.location.href = 'login.php';
                return;
            }
            
            // Show loading state
            const loadingElements = ['posted-jobs-count', 'active-bookings-count', 'jobs-applied-count', 'active-jobs-count'];
            loadingElements.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.textContent = '<?php echo addslashes(t('common.loading')); ?>';
                    element.style.display = 'block';
                    element.style.visibility = 'visible';
                    console.log('Set loading for element:', id);
                }
            });
            
            // Set fallback values immediately to ensure something is visible
            setTimeout(() => {
                console.log('Setting fallback values after timeout...');
                if (userRole === 'employer') {
                    loadEmployerData(null); // Force empty state
                    loadWorkers(); // Load workers for employers
                } else if (userRole === 'worker') {
                    loadWorkerData(null); // Force empty state
                } else {
                    console.error('Unknown user role:', userRole);
                    showErrorMessage('Invalid user role detected. Please log in again.');
                }
            }, 300); // Faster timeout for better UX
            
            // Fetch real data from API
            console.log('Fetching data from API...');
            fetch('./api/dashboard-data-simple.php', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('API Response Status:', response.status);
                console.log('Response Headers:', response.headers);
                
                if (response.status === 401) {
                    console.error('Session expired - redirecting to login');
                    window.location.href = 'login.php';
                    return;
                } else if (!response.ok) {
                    console.error('HTTP Error:', response.status, response.statusText);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return response.json();
            })
            .then(result => {
                console.log('API Response received:', result);
                console.log('Result type:', typeof result);
                console.log('Success:', result.success);
                console.log('Data:', result.data);
                
                if (result.success) {
                    console.log('Successfully loaded data:', result.data);
                    console.log('User role check:', userRole, '===', 'worker');
                    
                    if (userRole === 'employer') {
                        console.log('Loading employer data...');
                        loadEmployerData(result.data);
                        loadWorkers(); // Load workers for employers
                    } else if (userRole === 'worker') {
                        console.log('Loading worker data...');
                        loadWorkerData(result.data);
                    } else {
                        console.error('Invalid user role in response:', userRole);
                        showErrorMessage('Invalid user role detected. Please log in again.');
                    }
                } else {
                    console.error('API returned error:', result.message);
                    console.error('Full error response:', result);
                    
                    // Use fallback data if provided
                    if (result.data) {
                        console.log('Using fallback data:', result.data);
                        if (userRole === 'employer') {
                            loadEmployerData(result.data);
                        } else if (userRole === 'worker') {
                            loadWorkerData(result.data);
                        }
                    } else {
                        console.log('No fallback data available, using empty state');
                        // Fallback to empty state data
                        if (userRole === 'employer') {
                            loadEmployerData();
                        } else if (userRole === 'worker') {
                            loadWorkerData();
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Network or fetch error:', error);
                console.error('Error details:', error.message, error.stack);
                showErrorMessage('Network error. Please check your connection and try again.');
                
                // Fallback to empty state data
                console.log('Using fallback empty state due to network error');
                if (userRole === 'employer') {
                    loadEmployerData();
                } else if (userRole === 'worker') {
                    loadWorkerData();
                }
            });
        });
        
        function showErrorMessage(message) {
            // Show error message in console and optionally on page
            console.error(message);
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning alert-dismissible fade show';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            const container = document.querySelector('.main-content .row .col-12');
            if (container) {
                container.insertBefore(alertDiv, container.firstChild);
            }
        }
        
        function loadEmployerData(data) {
            console.log('Loading employer data:', data);
            if (!data) {
                // No data available - show empty state
                data = {
                    posted_jobs: { total: 0, active: 0, filled: 0 },
                    active_bookings: { active: 0 },
                    recent_jobs: []
                };
                console.log('Using empty employer data:', data);
            }
            
            // Update statistics with fallback values - ensure numbers are visible
            const postedJobsEl = document.getElementById('posted-jobs-count');
            if (postedJobsEl) {
                const count = data.posted_jobs?.total || 0;
                postedJobsEl.textContent = count;
                postedJobsEl.style.display = 'block';
                postedJobsEl.style.visibility = 'visible';
                console.log('Set posted jobs count:', count);
            }
            
            const activeBookingsEl = document.getElementById('active-bookings-count');
            if (activeBookingsEl) {
                const count = data.active_bookings?.active || 0;
                activeBookingsEl.textContent = count;
                activeBookingsEl.style.display = 'block';
                activeBookingsEl.style.visibility = 'visible';
                console.log('Set active bookings count:', count);
            }
            
            // Update recent jobs
            const recentJobsContainer = document.getElementById('recent-jobs');
            if (recentJobsContainer) {
                if (data.recent_jobs && data.recent_jobs.length > 0) {
                    recentJobsContainer.innerHTML = data.recent_jobs.map(job => `
                        <div class="list-group-item">
                            <h6>${job.title}</h6>
                            <small class="text-muted">
                                ${job.applications || 0} applications - 
                                ${formatCurrency(job.salary)} - 
                                ${formatDate(job.created_at)}
                            </small>
                        </div>
                    `).join('');
                } else {
                    recentJobsContainer.innerHTML = `<p class="text-muted">${tr('no_recent_jobs')}</p>`;
                }
            }
        }
        
        function loadWorkerData(data) {
            console.log('Loading worker data:', data);
            if (!data) {
                // No data available - show empty state
                data = {
                    jobs_applied: { total: 0, pending: 0, under_review: 0, accepted: 0 },
                    active_jobs: { active: 0 },
                    available_jobs: []
                };
                console.log('Using empty worker data fallback:', data);
            }
            
            // Always update statistics - ensure numbers are visible
            const jobsAppliedEl = document.getElementById('jobs-applied-count');
            if (jobsAppliedEl) {
                const count = data.jobs_applied?.total || 0;
                jobsAppliedEl.textContent = count;
                jobsAppliedEl.style.display = 'block';
                jobsAppliedEl.style.visibility = 'visible';
                console.log('Set jobs applied count:', count);
            }
            
            const activeJobsEl = document.getElementById('active-jobs-count');
            if (activeJobsEl) {
                const count = data.active_jobs?.active || 0;
                activeJobsEl.textContent = count;
                activeJobsEl.style.display = 'block';
                activeJobsEl.style.visibility = 'visible';
                console.log('Set active jobs count:', count);
            }
            
            // Always update available jobs - ensure jobs list is visible
            const availableJobsContainer = document.getElementById('available-jobs');
            if (availableJobsContainer) {
                if (data.available_jobs && data.available_jobs.length > 0) {
                    console.log('Displaying', data.available_jobs.length, 'available jobs');
                    availableJobsContainer.innerHTML = data.available_jobs.map(job => {
                        let statusBadge = '';
                        let actionButton = '';
                        
                        switch(job.status) {
                            case 'active':
                                statusBadge = `<span class="badge bg-success">${tr('available')}</span>`;
                                actionButton = `<button class="btn btn-sm btn-primary mt-2" onclick="applyForJob(${job.id})">${tr('apply_now')}</button>`;
                                break;
                            case 'applied':
                                statusBadge = `<span class="badge bg-warning">${tr('applied')}</span>`;
                                actionButton = `<button class="btn btn-sm btn-secondary mt-2" disabled>${tr('already_applied')}</button>`;
                                break;
                            default:
                                statusBadge = `<span class="badge bg-secondary">${tr('unknown')}</span>`;
                                actionButton = `<button class="btn btn-sm btn-secondary mt-2" disabled>${tr('not_available')}</button>`;
                        }
                        
                        return `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6>${job.title} ${statusBadge}</h6>
                                        <small class="text-muted">
                                            ${job.employer_name} - ${job.location} - 
                                            ${formatCurrency(job.salary)}
                                        </small>
                                    </div>
                                </div>
                                ${actionButton}
                            </div>
                        `;
                    }).join('');
                } else {
                    console.log('No available jobs - showing empty message');
                    availableJobsContainer.innerHTML = `<p class="text-muted">${tr('no_available_jobs')}</p>`;
                }
            }
        }
        
        function formatCurrency(amount) {
            return 'RWF ' + Number(amount || 0).toLocaleString();
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-RW', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }
        
        function applyForJob(jobId) {
            // Implement job application logic
            alert(tr('application_soon'));
        }
        
        // Logout confirmation function (same as in navbar)
        function confirmLogout(event) {
            event.preventDefault();
            
            // Create confirmation modal
            const modalHtml = `
                <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title" id="logoutModalLabel">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo addslashes(t('nav.confirm_logout')); ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    <i class="fas fa-sign-out-alt fa-3x text-danger mb-3"></i>
                                </div>
                                <h6 class="text-center"><?php echo addslashes(t('nav.confirm_logout_question')); ?></h6>
                                <p class="text-muted text-center mb-0"><?php echo addslashes(t('nav.confirm_logout_text')); ?></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i><?php echo addslashes(t('nav.cancel')); ?>
                                </button>
                                <button type="button" class="btn btn-danger" onclick="performLogout()">
                                    <i class="fas fa-sign-out-alt me-2"></i><?php echo addslashes(t('nav.logout')); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if present
            const existingModal = document.getElementById('logoutModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('logoutModal'));
            modal.show();
        }
        
        // Perform logout function (same as in navbar)
        function performLogout() {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('logoutModal'));
            modal.hide();
            
            // Show loading indicator
            const loadingHtml = `
                <div class="modal fade" id="logoutLoadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center py-4">
                                <div class="spinner-border text-primary mb-3" role="status">
                                    <span class="visually-hidden"><?php echo addslashes(t('common.loading')); ?></span>
                                </div>
                                <h6><?php echo addslashes(t('nav.logout')); ?>...</h6>
                                <p class="text-muted mb-0"><?php echo addslashes(t('nav.confirm_logout_text')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add loading modal
            document.body.insertAdjacentHTML('beforeend', loadingHtml);
            const loadingModal = new bootstrap.Modal(document.getElementById('logoutLoadingModal'));
            loadingModal.show();
            
            // Perform logout via AJAX
            fetch('./api/logout.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (response.ok) {
                    // Show success message briefly
                    setTimeout(() => {
                        loadingModal.hide();
                        // Redirect to homepage
                        window.location.href = './index.php';
                    }, 1000);
                } else {
                    throw new Error('<?php echo addslashes(t('nav.logout')); ?> failed');
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                loadingModal.hide();
                // Fallback: redirect anyway
                window.location.href = './index.php';
            });
        }
        
        // Workers loading functions for employers
        let currentPage = 1;
        let currentFilters = {};
        
        function loadWorkers(page = 1, filters = {}) {
            currentPage = page;
            currentFilters = filters;
            
            console.log('Loading workers page:', page, 'with filters:', filters);
            
            const container = document.getElementById('workers-container');
            if (container) {
                container.innerHTML = `<p class="text-muted col-12">${tr('loading_workers')}</p>`;
            }
            
            // Build query parameters
            const params = new URLSearchParams({
                page: page,
                ...filters
            });
            
            fetch(`./api/all-workers.php?${params}`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(result => {
                console.log('Workers API Response:', result);
                if (result.success) {
                    displayWorkers(result.data);
                    displayPagination(result.pagination);
                } else {
                    console.error('Workers API Error:', result.message);
                    displayFallbackWorkers();
                }
            })
            .catch(error => {
                console.error('Error loading workers:', error);
                displayFallbackWorkers();
            });
        }
        
        function displayWorkers(workers) {
            const container = document.getElementById('workers-container');
            if (!container) return;
            
            if (workers.length === 0) {
                container.innerHTML = `<p class="text-muted col-12">${tr('no_workers_match')}</p>`;
                return;
            }
            
            container.innerHTML = workers.map(worker => {
                const skills = worker.skills ? worker.skills.split(',').slice(0, 3) : [];
                
                return `
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card h-100 shadow-sm border-0 worker-card modern-worker-card">
                            <div class="text-center p-3">
                                <img src="${worker.profile_image}" class="rounded-circle mb-3" alt="${worker.name}" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #f8f9fa;">
                                <h5 class="card-title mb-1 fw-bold">${worker.name}</h5>
                                <p class="text-muted mb-2">${worker.type || tr('general_worker')}</p>
                                <p class="card-text text-muted small mb-3">${worker.description ? worker.description.substring(0, 80) + (worker.description.length > 80 ? '...' : '') : tr('no_description')}</p>
                                <button class="btn btn-primary w-100" onclick="viewWorkerProfile(${worker.id})">${tr('view_profile')}</button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function displayFallbackWorkers() {
            const container = document.getElementById('workers-container');
            if (!container) return;
            
            // Show no workers available message with enhanced design
            container.innerHTML = `
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <div style="width: 100px; height: 100px; margin: 0 auto; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-users fa-3x text-muted"></i>
                                </div>
                            </div>
                            <h3 class="text-muted mb-3">${tr('no_workers_yet')}</h3>
                            <p class="text-muted mb-4" style="max-width: 400px; margin: 0 auto;">
                                ${tr('no_workers_yet_text')}
                            </p>
                            <div class="d-flex justify-content-center gap-3">
                                <a href="register.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>${tr('register_worker')}
                                </a>
                                <a href="post-job.php" class="btn btn-outline-primary">
                                    <i class="fas fa-bullhorn me-2"></i>${tr('post_job')}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function displayPagination(pagination) {
            const paginationContainer = document.getElementById('workers-pagination');
            if (!paginationContainer) return;
            
            if (pagination.total_pages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }
            
            let paginationHTML = '<ul class="pagination pagination-sm">';
            
            // Previous button
            if (pagination.current_page > 1) {
                paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadWorkers(${pagination.current_page - 1}); return false;">${tr('previous')}</a></li>`;
            }
            
            // Page numbers
            for (let i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    paginationHTML += `<li class="page-item active"><a class="page-link" href="#">${i}</a></li>`;
                } else {
                    paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadWorkers(${i}); return false;">${i}</a></li>`;
                }
            }
            
            // Next button
            if (pagination.current_page < pagination.total_pages) {
                paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="loadWorkers(${pagination.current_page + 1}); return false;">${tr('next')}</a></li>`;
            }
            
            paginationHTML += '</ul>';
            paginationContainer.innerHTML = paginationHTML;
        }
        
        function contactWorker(workerId) {
            // Implement contact worker functionality
            alert(tr('contact_soon'));
        }
        
        // Worker Profile Modal Functions
        let currentWorkerId = null;
        
        function viewWorkerProfile(workerId) {
            currentWorkerId = workerId;
            
            // Show loading state in modal
            const modalContent = document.getElementById('worker-profile-content');
            modalContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden"><?php echo addslashes(t('common.loading')); ?></span>
                    </div>
                    <h5>${tr('loading_worker_profile')}</h5>
                </div>
            `;
            
            // Show the modal
            const workerProfileModal = new bootstrap.Modal(document.getElementById('workerProfileModal'));
            workerProfileModal.show();
            
            // Fetch worker data
            fetch(`./api/worker-details.php?id=${workerId}`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    displayWorkerInModal(result.data);
                } else {
                    throw new Error(result.message || tr('failed_load_worker_profile'));
                }
            })
            .catch(error => {
                console.error('Error loading worker profile:', error);
                modalContent.innerHTML = `
                    <div class="alert alert-danger m-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${tr('failed_load_worker_profile')}
                    </div>
                `;
            });
        }
        
        function displayWorkerInModal(worker) {
            const modalContent = document.getElementById('worker-profile-content');
            
            // Generate initials for avatar if no profile image
            const initials = worker.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
            const profileImage = worker.profile_image || '';
            
            modalContent.innerHTML = `
                <div class="worker-profile-modal">
                    <!-- Modern Header Section -->
                    <div class="profile-header" style="background: linear-gradient(135deg, #000000 0%, #333333 100%); position: relative;">
                        <div class="container-fluid">
                            <div class="row align-items-center py-4">
                                <div class="col-md-4 text-center">
                                    <div class="profile-avatar-container position-relative">
                                        <img src="${profileImage}" class="profile-avatar" alt="${worker.name}" 
                                             style="width: 150px; height: 150px; object-fit: cover; border: 4px solid rgba(255,255,255,0.9); border-radius: 50%; box-shadow: 0 8px 24px rgba(0,0,0,0.3); display: block; margin: 0 auto;"
                                             onerror="this.style.display='none'; this.parentElement.querySelector('.profile-avatar-fallback').style.display='flex';">
                                        <div class="profile-avatar-fallback rounded-circle d-flex align-items-center justify-content-center position-absolute top-0 start-50 translate-middle-x" 
                                             style="width: 150px; height: 150px; background: rgba(255,255,255,0.2); display: none; border: 4px solid rgba(255,255,255,0.9); box-shadow: 0 8px 24px rgba(0,0,0,0.3);">
                                            <span style="color: white; font-size: 2.5rem; font-weight: bold;">${initials}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="profile-info text-white">
                                        <h2 class="mb-3 fw-bold">${worker.name || tr('name_not_available')}</h2>
                                        <div class="profile-rating mb-3">
                                            <div class="stars mb-2">
                                                ${getRatingStars(worker.avg_rating || 0)}
                                            </div>
                                            <small class="opacity-75">(${worker.review_count || 0} ${tr('reviews')})</small>
                                        </div>
                                        <p class="profile-description mb-3 opacity-90">${worker.description || tr('no_description')}</p>
                                        <div class="profile-badges">
                                            <span class="badge bg-white text-dark me-2 mb-2 px-3 py-2">${worker.type ? worker.type.charAt(0).toUpperCase() + worker.type.slice(1) : tr('general_worker')}</span>
                                            <span class="badge bg-white text-dark me-2 mb-2 px-3 py-2">
                                                <i class="fas fa-map-marker-alt me-1"></i>${worker.location || tr('location_not_specified')}
                                            </span>
                                            <span class="badge bg-white text-dark me-2 mb-2 px-3 py-2">
                                                <i class="fas fa-clock me-1"></i>${worker.experience_years ? worker.experience_years + '+ ' + tr('years') : tr('experience_not_specified')}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="rate-card bg-white rounded-3 shadow-lg p-4 text-center">
                                        <div class="rate-icon mb-2">
                                            <i class="fas fa-money-bill-wave fa-2x text-dark"></i>
                                        </div>
                                        <h6 class="text-muted mb-2">Hourly Rate</h6>
                                        <h3 class="text-dark fw-bold mb-1">${worker.formatted_rate || 'RWF 0'}</h3>
                                        <small class="text-muted">per hour</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modern Content Section -->
                    <div class="profile-content bg-white">
                        <div class="container-fluid py-4">
                            <div class="row g-4">
                                <div class="col-lg-8">
                                    <!-- Services Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4 mb-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-tools text-dark me-2"></i>Services Offered
                                            </h5>
                                        </div>
                                        <div class="services-grid">
                                            ${getWorkerServices(worker.type)}
                                        </div>
                                    </div>
                                    
                                    <!-- Availability Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4 mb-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-calendar-check text-dark me-2"></i>Availability
                                            </h5>
                                        </div>
                                        <div class="availability-info text-center">
                                            <div class="availability-status mb-3">
                                                <div class="status-indicator bg-dark rounded-circle d-inline-block me-2" style="width: 12px; height: 12px;"></div>
                                    <span class="fw-semibold text-dark">${tr('available_for_work')}</span>
                                            </div>
                                            <div class="availability-badges d-flex justify-content-center gap-2 flex-wrap">
                                                <span class="badge bg-dark text-white px-3 py-2">Mon-Fri</span>
                                                <span class="badge bg-secondary text-white px-3 py-2">Weekends</span>
                                                <span class="badge bg-dark text-white px-3 py-2">Flexible</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Reviews Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-star text-dark me-2"></i>${tr('reviews_title')}
                                            </h5>
                                        </div>
                                        ${getWorkerReviews(worker.reviews)}
                                    </div>
                                </div>
                                
                                <div class="col-lg-4">
                                    <!-- Contact Info Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4 mb-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-phone text-dark me-2"></i>${tr('contact_information')}
                                            </h5>
                                        </div>
                                        <div class="contact-details">
                                            <div class="contact-item mb-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-phone text-dark me-3" style="width: 20px;"></i>
                                                    <div>
                                                        <small class="text-muted d-block">Phone</small>
                                                        <span class="fw-semibold text-dark">${worker.phone || 'Not provided'}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="contact-item">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-envelope text-dark me-3" style="width: 20px;"></i>
                                                    <div>
                                                        <small class="text-muted d-block">Email</small>
                                                        <span class="fw-semibold text-dark">${worker.email || 'Not provided'}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Skills Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4 mb-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-check-circle text-dark me-2"></i>${tr('skills_title')}
                                            </h5>
                                        </div>
                                        ${getWorkerSkills(worker.skills)}
                                    </div>
                                    
                                    <!-- Education Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4 mb-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-graduation-cap text-dark me-2"></i>Education
                                            </h5>
                                        </div>
                                        <div class="education-content">
                                            <p class="text-dark mb-0">${worker.education || 'Education information not provided'}</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Languages Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4 mb-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-language text-dark me-2"></i>Languages
                                            </h5>
                                        </div>
                                        <div class="languages-content">
                                            <p class="text-dark mb-0">${worker.languages || 'Language information not provided'}</p>
                                        </div>
                                    </div>
                                    
                                    <!-- National ID Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4 mb-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-id-card text-dark me-2"></i>Identity Verification
                                            </h5>
                                        </div>
                                        <div class="id-content">
                                            <div class="id-info mb-3">
                                                <small class="text-muted d-block">National ID Number</small>
                                                <span class="fw-semibold text-success">${worker.national_id || 'Not provided'}</span>
                                            </div>
                                            ${worker.national_id_photo ? `
                                                <div class="id-photo">
                                                    <small class="text-muted d-block mb-2">National ID Document</small>
                                                    <img src="uploads/${worker.national_id_photo}" 
                                                         alt="National ID" 
                                                         class="img-thumbnail rounded" 
                                                         style="max-width: 200px; cursor: pointer;"
                                                         onclick="viewNationalIdModal('${worker.national_id_photo}', '${worker.name}')">
                                                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="viewNationalIdModal('${worker.national_id_photo}', '${worker.name}')">
                                                        <i class="fas fa-search me-1"></i>View Full Size
                                                    </button>
                                                </div>
                                    ` : `<p class="text-muted mb-0">${tr('national_id_unavailable')}</p>`}
                                        </div>
                                    </div>
                                    
                                    <!-- Certifications Card -->
                                    <div class="content-card bg-light rounded-3 shadow-sm p-4 mb-4">
                                        <div class="card-header-modern mb-3">
                                            <h5 class="mb-0 fw-bold text-dark">
                                                <i class="fas fa-certificate text-dark me-2"></i>Certifications
                                            </h5>
                                        </div>
                                        <div class="certifications-content">
                                    <p class="text-dark mb-0">${worker.certifications || tr('no_certifications')}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <style>
                    .profile-avatar-container {
                        position: relative;
                        display: inline-block;
                        width: 150px;
                        height: 150px;
                    }
                    
                    .profile-avatar {
                        transition: transform 0.3s ease;
                        display: block;
                    }
                    
                    .profile-avatar-fallback {
                        transition: transform 0.3s ease;
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                    }
                    
                    .profile-avatar:hover, .profile-avatar-fallback:hover {
                        transform: scale(1.05);
                    }
                    
                    .stars {
                        font-size: 1.2rem;
                    }
                    
                    .profile-badges .badge {
                        font-weight: 500;
                        border-radius: 20px;
                    }
                    
                    .content-card {
                        border: 1px solid #e9ecef;
                        transition: transform 0.2s ease, box-shadow 0.2s ease;
                    }
                    
                    .content-card:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
                    }
                    
                    .card-header-modern h5 {
                        color: #000000;
                    }
                    
                    .services-grid .row .col-md-6 {
                        margin-bottom: 0.5rem;
                    }
                    
                    .services-grid .d-flex {
                        padding: 0.5rem;
                        border-radius: 8px;
                        transition: background-color 0.2s ease;
                    }
                    
                    .services-grid .d-flex:hover {
                        background-color: #f8f9fa;
                    }
                    
                    .rate-card {
                        border: none;
                        transition: transform 0.2s ease;
                    }
                    
                    .rate-card:hover {
                        transform: translateY(-2px);
                    }
                    
                    .availability-badges .badge {
                        font-weight: 500;
                        border-radius: 20px;
                    }
                    
                    .contact-item {
                        padding: 0.75rem;
                        border-radius: 8px;
                        transition: background-color 0.2s ease;
                    }
                    
                    .contact-item:hover {
                        background-color: #f8f9fa;
                    }
                </style>
            `;
            
            // Setup modal action buttons
            setupModalActions(worker);
        }
        
        function getRatingStars(rating) {
            const fullStars = Math.floor(rating);
            const halfStar = rating % 1 >= 0.5 ? 1 : 0;
            const emptyStars = 5 - fullStars - halfStar;
            
            let stars = '';
            for (let i = 0; i < fullStars; i++) {
                stars += '<i class="fas fa-star text-warning"></i>';
            }
            if (halfStar) {
                stars += '<i class="fas fa-star-half-alt text-warning"></i>';
            }
            for (let i = 0; i < emptyStars; i++) {
                stars += '<i class="far fa-star text-warning"></i>';
            }
            
            return stars;
        }
        
        function getWorkerServices(type) {
            const services = {
                'cleaning': ['House Cleaning', 'Deep Cleaning', 'Window Cleaning', 'Laundry', 'Organizing'],
                'cooking': ['Meal Preparation', 'Special Dietary Cooking', 'Event Catering', 'Meal Planning'],
                'childcare': ['Child Supervision', 'Homework Help', 'Activity Planning', 'Light Housekeeping'],
                'eldercare': ['Companionship', 'Medication Reminders', 'Meal Assistance', 'Light Housekeeping'],
                'gardening': ['Lawn Maintenance', 'Plant Care', 'Landscape Design', 'Weed Control'],
                'other': ['General Household Support']
            };
            
            const workerServices = services[type] || services['other'];
            
            return `
                <div class="row g-3">
                    ${workerServices.map(service => `
                        <div class="col-md-6">
                            <div class="service-item d-flex align-items-center p-3 bg-white border rounded-2">
                                <div class="service-icon me-3">
                                    <i class="fas fa-check-circle text-dark"></i>
                                </div>
                                <span class="fw-medium text-dark">${service}</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        function getWorkerSkills(skills) {
            if (!skills || skills.trim() === '') {
                return `<p class="text-muted opacity-75">${tr('no_specific_skills')}</p>`;
            }
            
            const skillsArray = skills.split(',').map(skill => skill.trim()).filter(skill => skill);
            
            return `
                <div class="skills-container d-flex flex-wrap gap-2">
                    ${skillsArray.map(skill => `
                        <span class="skill-badge bg-dark text-white px-3 py-2 rounded-pill fw-medium">
                            <i class="fas fa-check-circle me-1"></i>${skill}
                        </span>
                    `).join('')}
                </div>
            `;
        }
        
        function getWorkerReviews(reviews) {
            if (!reviews || reviews.length === 0) {
                return `
                    <div class="text-center py-4">
                        <div class="no-reviews-icon mb-3">
                            <i class="fas fa-star fa-3x text-muted opacity-50"></i>
                        </div>
                                <h6 class="text-muted mb-2">${tr('no_reviews_yet')}</h6>
                        <p class="text-muted opacity-75 small mb-0">Be the first to review this worker!</p>
                    </div>
                `;
            }
            
            return reviews.slice(0, 3).map(review => `
                <div class="review-item bg-white border rounded-3 p-3 mb-3">
                    <div class="review-header d-flex justify-content-between align-items-start mb-2">
                        <div class="reviewer-info">
                            <h6 class="reviewer-name mb-1 fw-semibold text-dark">${review.reviewer_name || 'Anonymous'}</h6>
                            <div class="review-rating text-warning mb-1">
                                ${getRatingStars(review.rating)}
                            </div>
                        </div>
                        <div class="review-date">
                            <small class="text-muted">${new Date(review.created_at).toLocaleDateString('en-US', { 
                                year: 'numeric', 
                                month: 'short', 
                                day: 'numeric' 
                            })}</small>
                        </div>
                    </div>
                    <div class="review-comment">
                                <p class="mb-0 text-secondary">${review.comment || tr('no_comment')}</p>
                    </div>
                </div>
            `).join('');
        }
        
        function setupModalActions(worker) {
            const contactBtn = document.getElementById('modal-contact-btn');
            const bookBtn = document.getElementById('modal-book-btn');
            
            // Remove existing event listeners
            const newContactBtn = contactBtn.cloneNode(true);
            const newBookBtn = bookBtn.cloneNode(true);
            contactBtn.parentNode.replaceChild(newContactBtn, contactBtn);
            bookBtn.parentNode.replaceChild(newBookBtn, bookBtn);
            
            // Add new event listeners
            newContactBtn.addEventListener('click', () => {
                // Close profile modal and open contact modal
                bootstrap.Modal.getInstance(document.getElementById('workerProfileModal')).hide();
                setTimeout(() => {
                    const contactModal = new bootstrap.Modal(document.getElementById('contactModal'));
                    contactModal.show();
                }, 300);
            });
            
            newBookBtn.addEventListener('click', () => {
                // Close profile modal and open booking modal
                bootstrap.Modal.getInstance(document.getElementById('workerProfileModal')).hide();
                setTimeout(() => {
                    const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
                    bookingModal.show();
                }, 300);
            });
        }
        
        // Setup search and filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Worker search
            const searchInput = document.getElementById('worker-search');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function(e) {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        const filters = {...currentFilters};
                        if (e.target.value.trim()) {
                            filters.search = e.target.value.trim();
                        } else {
                            delete filters.search;
                        }
                        loadWorkers(1, filters);
                    }, 500);
                });
            }
            
            // Worker type filter
            const typeFilter = document.getElementById('worker-type-filter');
            if (typeFilter) {
                typeFilter.addEventListener('change', function(e) {
                    const filters = {...currentFilters};
                    if (e.target.value) {
                        filters.type = e.target.value;
                    } else {
                        delete filters.type;
                    }
                    loadWorkers(1, filters);
                });
            }
        });
        
        function viewNationalIdModal(photoFilename, workerName) {
            // Create modal if it doesn't exist
            if (!document.getElementById('nationalIdModal')) {
                const modalHTML = `
                    <div class="modal fade" id="nationalIdModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">National ID Document</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center">
                                    <h6 class="mb-3">Worker: <span id="nationalIdWorkerName"></span></h6>
                                    <img id="nationalIdImage" src="" alt="National ID" class="img-fluid" style="max-height: 500px; border-radius: 8px;">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
            }
            
            const modal = new bootstrap.Modal(document.getElementById('nationalIdModal'));
            const imageElement = document.getElementById('nationalIdImage');
            const nameElement = document.getElementById('nationalIdWorkerName');
            
            imageElement.src = 'uploads/' + photoFilename;
            nameElement.textContent = workerName;
            modal.show();
        }
    </script>
    
    <!-- National ID Modal -->
    <div class="modal fade" id="nationalIdModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">National ID Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <h6 class="mb-3">Worker: <span id="nationalIdWorkerName"></span></h6>
                    <img id="nationalIdImage" src="" alt="National ID" class="img-fluid" style="max-height: 500px; border-radius: 8px;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
