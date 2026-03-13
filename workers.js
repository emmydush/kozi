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
            if (response.data.success) {
                const workers = response.data.data;
                displayWorkers(workers);
                
                // Update results count
                updateResultsCount(workers.length, response.data.pagination.total_items);
                
                if (workers.length === 0) {
                    document.getElementById('workers-container').innerHTML = `
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h4>No workers found</h4>
                            <p class="text-muted">Try adjusting your filters or search criteria.</p>
                        </div>
                    `;
                }
            } else {
                showError(response.data.message || 'Failed to load workers');
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
    card.className = 'col-md-6 col-lg-3 mb-4';
    
    const skills = worker.skills ? worker.skills.split(',').slice(0, 3) : [];
    
    card.innerHTML = `
        <div class="card h-100 shadow-sm border-0 worker-card modern-worker-card">
            <div class="text-center p-3">
                <img src="${worker.profile_image}" class="rounded-circle mb-3" alt="${worker.name}" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #f8f9fa;">
                <h5 class="card-title mb-1 fw-bold">${worker.name}</h5>
                <p class="text-muted mb-2">${worker.type}</p>
                <p class="card-text text-muted small mb-3">${worker.description.substring(0, 80)}${worker.description.length > 80 ? '...' : ''}</p>
                <a href="worker-details.php?id=${worker.id}" class="btn btn-primary w-100">View Profile</a>
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