document.addEventListener('DOMContentLoaded', function() {
    loadWorkers();
    setupFilters();
    setupSorting();
    setupLoadMore();
});

let currentPage = 1;
let filters = {};
let sortBy = 'relevance';

function loadWorkers() {
    const params = new URLSearchParams({
        page: currentPage,
        ...filters,
        sort: sortBy
    });
    
    axios.get(`api/all-workers.php?${params.toString()}`)
        .then(response => {
            const workers = response.data.data;
            displayWorkers(workers);
            
            if (workers.length === 0) {
                document.getElementById('workers-container').innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4>No workers found</h4>
                        <p class="text-muted">Try adjusting your filters or search criteria.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading workers:', error);
            showError('Failed to load workers. Please try again.');
        });
}

function displayWorkers(workers) {
    const container = document.getElementById('workers-container');
    
    workers.forEach(worker => {
        const card = createWorkerCard(worker);
        container.appendChild(card);
    });
}

function createWorkerCard(worker) {
    const card = document.createElement('div');
    card.className = 'col-md-6 col-lg-4 mb-4';
    
    const ratingStars = getRatingStars(worker.rating);
    const skills = worker.skills ? worker.skills.split(',').slice(0, 3) : [];
    
    card.innerHTML = `
        <div class="card h-100 shadow-sm border-0 worker-card">
            <img src="${worker.profile_image}" class="card-img-top" alt="${worker.name}" style="height: 250px; object-fit: cover;">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="card-title">${worker.name}</h5>
                    <span class="badge bg-success">${worker.type}</span>
                </div>
                <div class="mb-2">
                    ${ratingStars} <small class="text-muted">(${worker.review_count} reviews)</small>
                </div>
                <p class="card-text text-muted small mb-2">${worker.description.substring(0, 100)}...</p>
                <div class="mb-2">
                    <small class="text-muted"><i class="fas fa-map-marker-alt"></i> ${worker.location}</small>
                </div>
                <div class="mb-3">
                    ${skills.map(skill => `<span class="badge bg-light text-dark me-1">${skill}</span>`).join('')}
                </div>
                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">${worker.formatted_rate}/hr</span>
                        <span class="text-muted small">${worker.experience_years} yrs exp</span>
                    </div>
                    <a href="worker-details.php?id=${worker.id}" class="btn btn-primary mt-3 w-100">View Profile</a>
                </div>
            </div>
        </div>
    `;
    
    return card;
}

function getRatingStars(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5 ? 1 : 0;
    const emptyStars = 5 - fullStars - halfStar;
    
    let stars = '';
    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star text-warning"></i>';
    }
    for (let i = 0; i < halfStar; i++) {
        stars += '<i class="fas fa-star-half-alt text-warning"></i>';
    }
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star text-muted"></i>';
    }
    
    return stars;
}

function setupFilters() {
    const form = document.getElementById('filter-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            filters = {
                type: document.getElementById('service-type').value,
                location: document.getElementById('location').value,
                min_rating: document.getElementById('rating').value,
                min_experience: document.getElementById('experience').value
            };
            
            currentPage = 1;
            document.getElementById('workers-container').innerHTML = '';
            loadWorkers();
        });
    }
}

function setupSorting() {
    const sortSelect = document.getElementById('sort-by');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            sortBy = this.value;
            currentPage = 1;
            document.getElementById('workers-container').innerHTML = '';
            loadWorkers();
        });
    }
}

function setupLoadMore() {
    const loadMoreBtn = document.getElementById('load-more');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            currentPage++;
            loadWorkers();
        });
    }
}

function showError(message) {
    const container = document.getElementById('workers-container');
    container.innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        </div>
    `;
}

// Search functionality
function searchWorkers(query) {
    if (query.length < 2) return;
    
    const params = new URLSearchParams({
        search: query,
        page: 1
    });
    
    axios.get(`api/search-workers.php?${params.toString()}`)
        .then(response => {
            const workers = response.data.data;
            displayWorkers(workers);
        })
        .catch(error => {
            console.error('Error searching workers:', error);
        });
}

// Auto-hide alerts
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 5000);
});