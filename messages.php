<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user role
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Household Connect</title>
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

        .btn-sm {
            min-height: 38px;
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .row > * {
            padding-left: 10px;
            padding-right: 10px;
        }

        .row {
            margin-left: -10px;
            margin-right: -10px;
        }

        .card-body {
            padding: 15px;
        }

        .card-header {
            padding: 12px 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

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

        h6 {
            color: #000000;
            font-weight: 600;
        }

        .message-list {
            height: 500px;
            overflow-y: auto;
        }

        .message-item {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .message-item:hover {
            background-color: #f8f9fa;
        }

        .message-item.unread {
            background-color: #e3f2fd;
        }

        .chat-window {
            height: 500px;
            display: flex;
            flex-direction: column;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: white;
            border: 1px solid #dee2e6;
        }

        .message {
            margin-bottom: 15px;
            max-width: 70%;
        }

        .message.sent {
            margin-left: auto;
        }

        .message-bubble {
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
        }

        .message.sent .message-bubble {
            background: linear-gradient(135deg, #000000 0%, #333333 100%);
            color: white;
        }

        .message.received .message-bubble {
            background: #e9ecef;
            color: #333;
        }

        .message-time {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .message.sent .message-time {
            text-align: right;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }

        .form-control:focus {
            border-color: #000000;
            box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.25);
        }

        .input-group .btn {
            min-height: auto;
            padding: 12px 20px;
        }

        @media (max-width: 768px) {
            .row > * {
                padding-left: 8px;
                padding-right: 8px;
            }

            .row {
                margin-left: -8px;
                margin-right: -8px;
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
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-home"></i> Household Connect</h3>
        </div>
        
        <nav class="nav flex-column p-3">
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            
            <?php if ($user_role === 'employer'): ?>
            <a class="nav-link" href="post-job.php">
                <i class="fas fa-plus-circle"></i> Post Job
            </a>
            <a class="nav-link" href="workers.php">
                <i class="fas fa-users"></i> Find Workers
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> My Jobs
            </a>
            <a class="nav-link" href="bookings.php">
                <i class="fas fa-calendar-check"></i> Bookings
            </a>
            <?php else: ?>
            <a class="nav-link" href="jobs.php">
                <i class="fas fa-search"></i> Find Jobs
            </a>
            <a class="nav-link" href="my-applications.php">
                <i class="fas fa-file-alt"></i> My Applications
            </a>
            <a class="nav-link" href="my-jobs.php">
                <i class="fas fa-briefcase"></i> Active Jobs
            </a>
            <a class="nav-link" href="earnings.php">
                <i class="fas fa-money-bill-wave"></i> Earnings
            </a>
            <?php endif; ?>
            
            <a class="nav-link active" href="messages.php">
                <i class="fas fa-envelope"></i> Messages
            </a>
            <a class="nav-link" href="profile.php">
                <i class="fas fa-user-cog"></i> Profile Settings
            </a>
            <a class="nav-link" href="reviews.php">
                <i class="fas fa-star"></i> Reviews
            </a>
            
            <hr class="text-white-50">
            
            <a class="nav-link" href="help.php">
                <i class="fas fa-question-circle"></i> Help & Support
            </a>
            <a class="nav-link" href="api/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row">
            <div class="col-12">
                <h2>Messages</h2>
                <p class="text-muted">Communicate with employers and workers</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Conversations</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="message-list" id="message-list">
                            <p class="text-muted p-3">Loading conversations...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center" id="conversation-header">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <strong>Select a conversation</strong>
                                <div class="text-muted small">Choose from the list</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="chat-window">
                            <div class="chat-messages" id="chat-messages">
                                <div class="text-center text-muted p-4">
                                    <i class="fas fa-comments fa-3x mb-3"></i>
                                    <p>Select a conversation to start messaging</p>
                                </div>
                            </div>
                            
                            <div class="p-3 border-top">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Type a message..." id="message-input">
                                    <button class="btn btn-primary" onclick="sendMessage()">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
        
        function selectConversation(id) {
            // Remove unread class from all messages
            document.querySelectorAll('.message-item').forEach(item => {
                item.classList.remove('unread');
            });
            
            // Add unread class to selected message
            event.currentTarget.classList.add('unread');
            
            // Load conversation (for demo purposes)
            console.log('Loading conversation', id);
        }
        
        function sendMessage() {
            const input = document.getElementById('message-input');
            const message = input.value.trim();
            
            if (message) {
                const chatMessages = document.querySelector('.chat-messages');
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message sent';
                messageDiv.innerHTML = `
                    <div class="message-bubble">${message}</div>
                    <div class="message-time">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
                `;
                
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                input.value = '';
            }
        }
        
        // Send message on Enter key
        document.getElementById('message-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html>
