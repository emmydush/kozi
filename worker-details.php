<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Profile - Household Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-home"></i> Household Connect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="workers.php">Find Workers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php">Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="worker-profile-header bg-gradient text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 text-center">
                    <img id="profile-image" src="https://picsum.photos/seed/1/300/300.jpg" class="rounded-circle mb-3" alt="Worker" style="width: 200px; height: 200px; object-fit: cover;">
                </div>
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-3" id="worker-name">Loading...</h1>
                    <div class="d-flex align-items-center mb-3">
                        <div id="rating-stars"></div>
                        <span class="text-white-50 ms-2" id="review-count">Loading...</span>
                    </div>
                    <p class="lead" id="worker-description">Loading...</p>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <span class="badge bg-success" id="worker-type">Loading...</span>
                        <span class="badge bg-primary" id="worker-location">Loading...</span>
                        <span class="badge bg-info" id="worker-experience">Loading...</span>
                    </div>
                    <div class="d-flex gap-3">
                        <button id="contact-btn" class="btn btn-warning btn-lg">
                            <i class="fas fa-envelope"></i> Contact
                        </button>
                        <button id="book-btn" class="btn btn-success btn-lg">
                            <i class="fas fa-calendar-check"></i> Book Now
                        </button>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card bg-white text-dark">
                        <div class="card-body">
                            <h5 class="card-title text-center">Hourly Rate</h5>
                            <h2 class="text-center text-success fw-bold" id="hourly-rate">Loading...</h2>
                            <p class="text-center text-muted small">RWF per hour</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Services Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="fas fa-tools"></i> Services Offered</h5>
                        </div>
                        <div class="card-body">
                            <div id="services-list">
                                <!-- Services will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Availability Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="fas fa-calendar"></i> Availability</h5>
                        </div>
                        <div class="card-body">
                            <div id="availability-calendar">
                                <!-- Calendar will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="fas fa-star"></i> Reviews</h5>
                        </div>
                        <div class="card-body">
                            <div id="reviews-section">
                                <!-- Reviews will be loaded here -->
                            </div>
                            <button id="load-more-reviews" class="btn btn-outline-primary w-100">
                                Load More Reviews
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Contact Info -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="fas fa-phone"></i> Contact Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-phone fa-2x text-primary me-3"></i>
                                <div>
                                    <h6>Phone</h6>
                                    <p id="worker-phone">Loading...</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-envelope fa-2x text-primary me-3"></i>
                                <div>
                                    <h6>Email</h6>
                                    <p id="worker-email">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Skills -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="fas fa-check-circle"></i> Skills</h5>
                        </div>
                        <div class="card-body">
                            <div id="skills-list">
                                <!-- Skills will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Languages -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="fas fa-language"></i> Languages</h5>
                        </div>
                        <div class="card-body">
                            <div id="languages-list">
                                <!-- Languages will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2024 Household Connect. Connecting Kigali families with trusted workers.</p>
        </div>
    </footer>

    <!-- Contact Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contact Worker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="contact-form">
                        <div class="mb-3">
                            <label for="message-subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="message-subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message-body" class="form-label">Message</label>
                            <textarea class="form-control" id="message-body" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="send-message-btn">Send Message</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book Worker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="booking-form">
                        <div class="mb-3">
                            <label for="booking-start" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="booking-start" required>
                        </div>
                        <div class="mb-3">
                            <label for="booking-end" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="booking-end" required>
                        </div>
                        <div class="mb-3">
                            <label for="booking-service" class="form-label">Service Type</label>
                            <select class="form-select" id="booking-service" required>
                                <option value="cleaning">Cleaning</option>
                                <option value="cooking">Cooking</option>
                                <option value="childcare">Childcare</option>
                                <option value="eldercare">Eldercare</option>
                                <option value="gardening">Gardening</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="booking-notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="booking-notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="create-booking-btn">Create Booking</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="script.js"></script>
    <script src="worker-details.js"></script>
</body>
</html>