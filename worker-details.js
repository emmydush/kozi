document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const workerId = urlParams.get('id');
    
    if (!workerId) {
        showError('Worker ID is required');
        return;
    }
    
    loadWorkerDetails(workerId);
    setupContactForm();
    setupBookingForm();
    setupLoadMoreReviews();
});

let currentPage = 1;
let workerId;

function loadWorkerDetails(id) {
    workerId = id;
    
    // Show loading state
    showLoadingState();
    
    axios.get(`api/worker-details.php?id=${id}`)
        .then(response => {
            if (response.data.success) {
                const worker = response.data.data;
                displayWorkerDetails(worker);
                displayServices(worker.type);
                displaySkills(worker.skills);
                displayLanguages(worker.languages);
                displayReviews(worker.reviews);
                displayAvailability();
            } else {
                showError(response.data.message || 'Failed to load worker details');
            }
        })
        .catch(error => {
            console.error('Error loading worker details:', error);
            showError('Failed to load worker details. Please try again.');
        });
}

function displayWorkerDetails(worker) {
    document.getElementById('worker-name').textContent = worker.name || 'Name not available';
    document.getElementById('worker-description').textContent = worker.description || 'No description available';
    document.getElementById('worker-type').textContent = worker.type ? worker.type.charAt(0).toUpperCase() + worker.type.slice(1) : 'Not specified';
    document.getElementById('worker-location').textContent = worker.location || 'Location not specified';
    document.getElementById('worker-experience').textContent = worker.experience_years ? `${worker.experience_years}+ years` : 'Experience not specified';
    document.getElementById('hourly-rate').textContent = worker.formatted_rate || 'RWF 0';
    document.getElementById('worker-phone').textContent = worker.phone || 'Not provided';
    document.getElementById('worker-email').textContent = worker.email || 'Not provided';
    document.getElementById('review-count').textContent = worker.avg_rating > 0 ? `(${worker.review_count || 0} reviews)` : '(No reviews)';
    
    // Set profile image
    const profileImg = document.getElementById('profile-image');
    if (worker.profile_image) {
        profileImg.src = worker.profile_image;
        profileImg.onerror = function() {
            this.src = `https://picsum.photos/seed/${worker.id}/300/300.jpg`;
        };
    } else {
        profileImg.src = `https://picsum.photos/seed/${worker.id}/300/300.jpg`;
    }
    
    // Set rating stars
    const ratingStars = getRatingStars(worker.avg_rating);
    document.getElementById('rating-stars').innerHTML = ratingStars;
}

function displayServices(type) {
    const servicesList = document.getElementById('services-list');
    
    const services = {
        'cleaning': ['House Cleaning', 'Deep Cleaning', 'Window Cleaning', 'Laundry', 'Organizing'],
        'cooking': ['Meal Preparation', 'Special Dietary Cooking', 'Event Catering', 'Meal Planning'],
        'childcare': ['Child Supervision', 'Homework Help', 'Activity Planning', 'Light Housekeeping'],
        'eldercare': ['Companionship', 'Medication Reminders', 'Meal Assistance', 'Light Housekeeping'],
        'gardening': ['Lawn Maintenance', 'Plant Care', 'Landscape Design', 'Weed Control'],
        'other': ['General Household Support']
    };
    
    const workerServices = services[type] || services['other'];
    
    servicesList.innerHTML = `
        <div class="row">
            ${workerServices.map(service => `
                <div class="col-md-6 mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        <span>${service}</span>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

function displaySkills(skills) {
    const skillsList = document.getElementById('skills-list');
    
    if (!skills || skills.trim() === '') {
        skillsList.innerHTML = '<p class="text-muted">No specific skills listed</p>';
        return;
    }
    
    const skillsArray = skills.split(',').map(skill => skill.trim()).filter(skill => skill);
    
    skillsList.innerHTML = `
        <div class="d-flex flex-wrap gap-2">
            ${skillsArray.map(skill => `
                <span class="badge bg-primary">${skill}</span>
            `).join('')}
        </div>
    `;
}

function displayLanguages(languages) {
    const languagesList = document.getElementById('languages-list');
    
    if (!languages || languages.trim() === '') {
        languagesList.innerHTML = '<p class="text-muted">Languages not specified</p>';
        return;
    }
    
    const languagesArray = languages.split(',').map(lang => lang.trim()).filter(lang => lang);
    
    languagesList.innerHTML = `
        <div class="d-flex flex-wrap gap-2">
            ${languagesArray.map(language => `
                <span class="badge bg-info">${language}</span>
            `).join('')}
        </div>
    `;
}

function displayReviews(reviews) {
    const reviewsSection = document.getElementById('reviews-section');
    
    if (!reviews || reviews.length === 0) {
        reviewsSection.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                <p class="text-muted">No reviews yet. Be the first to review this worker!</p>
            </div>
        `;
        return;
    }
    
    reviewsSection.innerHTML = reviews.map(review => `
        <div class="review-item border-bottom pb-3 mb-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h6 class="mb-1">${review.reviewer_name || 'Anonymous'}</h6>
                    <div class="text-warning small">
                        ${getRatingStars(review.rating)}
                    </div>
                </div>
                <small class="text-muted">${formatDate(review.created_at)}</small>
            </div>
            <p class="mb-0">${review.comment || 'No comment provided'}</p>
        </div>
    `).join('');
}

function displayAvailability() {
    const availabilityCalendar = document.getElementById('availability-calendar');
    
    // Simple availability display - can be enhanced with a real calendar
    availabilityCalendar.innerHTML = `
        <div class="text-center py-4">
            <i class="fas fa-calendar-check fa-3x text-primary mb-3"></i>
            <h6>Available for Work</h6>
            <p class="text-muted">Contact the worker to discuss specific dates and times</p>
            <div class="d-flex justify-content-center gap-2 mt-3">
                <span class="badge bg-success">Mon-Fri</span>
                <span class="badge bg-warning">Weekends</span>
                <span class="badge bg-info">Flexible</span>
            </div>
        </div>
    `;
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

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function showLoadingState() {
    // You could add loading spinners here if needed
    console.log('Loading worker details...');
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger alert-dismissible fade show';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.worker-profile-header');
    if (container) {
        container.parentNode.insertBefore(errorDiv, container);
    }
}

function setupContactForm() {
    const contactBtn = document.getElementById('contact-btn');
    const contactModal = new bootstrap.Modal(document.getElementById('contactModal'));
    const sendBtn = document.getElementById('send-message-btn');
    
    contactBtn.addEventListener('click', () => {
        contactModal.show();
    });
    
    sendBtn.addEventListener('click', () => {
        const subject = document.getElementById('message-subject').value;
        const message = document.getElementById('message-body').value;
        
        if (!subject || !message) {
            alert('Please fill in all fields');
            return;
        }
        
        // Here you would normally send the message to your API
        alert('Message sent successfully!');
        contactModal.hide();
        document.getElementById('contact-form').reset();
    });
}

function setupBookingForm() {
    const bookBtn = document.getElementById('book-btn');
    const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
    const createBtn = document.getElementById('create-booking-btn');
    
    bookBtn.addEventListener('click', () => {
        bookingModal.show();
    });
    
    createBtn.addEventListener('click', () => {
        const startDate = document.getElementById('booking-start').value;
        const endDate = document.getElementById('booking-end').value;
        const service = document.getElementById('booking-service').value;
        
        if (!startDate || !endDate) {
            alert('Please select start and end dates');
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
            alert('End date must be after start date');
            return;
        }
        
        // Here you would normally create the booking via your API
        alert('Booking created successfully!');
        bookingModal.hide();
        document.getElementById('booking-form').reset();
    });
}

function setupLoadMoreReviews() {
    const loadMoreBtn = document.getElementById('load-more-reviews');
    
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => {
            // Here you would load more reviews from your API
            alert('More reviews would be loaded here');
        });
    }
}

