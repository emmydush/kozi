<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Household Workers - Household Connect</title>
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
                        <a class="nav-link active" href="workers.php">Find Workers</a>
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

    <header class="page-header bg-gradient text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3">Find Trusted Household Workers</h1>
                    <p class="lead">Discover skilled and reliable household workers in Kigali. Filter by service type, location, and ratings to find the perfect match for your needs.</p>
                </div>
                <div class="col-lg-4 text-center">
                    <div class="stats-card bg-white text-dark p-4 rounded">
                        <h3 class="text-primary">1,247+</h3>
                        <p class="mb-0">Verified Workers</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-3">
                    <div class="sticky-top" style="top: 100px;">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5><i class="fas fa-filter"></i> Filters</h5>
                            </div>
                            <div class="card-body">
                                <form id="filter-form">
                                    <div class="mb-3">
                                        <label for="service-type" class="form-label">Service Type</label>
                                        <select class="form-select" id="service-type">
                                            <option value="">All Services</option>
                                            <option value="cleaning">Cleaning</option>
                                            <option value="cooking">Cooking</option>
                                            <option value="childcare">Childcare</option>
                                            <option value="eldercare">Eldercare</option>
                                            <option value="gardening">Gardening</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="location" placeholder="Enter location">
                                    </div>
                                    <div class="mb-3">
                                        <label for="rating" class="form-label">Minimum Rating</label>
                                        <select class="form-select" id="rating">
                                            <option value="">Any Rating</option>
                                            <option value="5">5 stars</option>
                                            <option value="4">4 stars & above</option>
                                            <option value="3">3 stars & above</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="experience" class="form-label">Experience</label>
                                        <select class="form-select" id="experience">
                                            <option value="">Any Experience</option>
                                            <option value="1">1+ year</option>
                                            <option value="3">3+ years</option>
                                            <option value="5">5+ years</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="mb-0">Available Workers</h2>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="sort-by">
                                <option value="relevance">Relevance</option>
                                <option value="rating">Top Rated</option>
                                <option value="experience">Most Experienced</option>
                                <option value="newest">Newest</option>
                            </select>
                        </div>
                    </div>
                    
                    <div id="workers-container" class="row">
                        <!-- Workers will be loaded here -->
                    </div>
                    
                    <div class="d-flex justify-content-center mt-4">
                        <button id="load-more" class="btn btn-outline-primary">Load More Workers</button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="script.js"></script>
    <script src="workers.js"></script>
</body>
</html>