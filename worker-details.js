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
    
    axios.get(`api/worker-details.php?id=${id}`)
        .then(response => {
            const worker = response.data.data;
            displayWorkerDetails(worker);
            displayServices(worker.type);
            displaySkills(worker.skills);
            displayLanguages(worker.languages);
            displayReviews(worker.reviews);
            displayAvailability();
        })
        .catch(error => {
            console.error('Error loading worker details:', error);
            showError('Failed to load worker details. Please try again.');
        });
}

function displayWorkerDetails(worker) {
    document.getElementById('worker-name').textContent = worker.name;
    document.getElementById('worker-description').textContent = worker.description;
    document.getElementById('worker-type').textContent = worker.type.charAt(0).toUpperCase() + worker.type.slice(1);
    document.getElementById('worker-location').textContent = worker.location;
    document.getElementById('worker-experience').textContent = `${worker.experience_years}+ years`;
    document.getElementById('hourly-rate').textContent = worker.formatted_rate;
    document.getElementById('worker-phone').textContent = worker.phone || 'Not provided';
    document.getElementById('worker-email').textContent = worker.email || 'Not provided';
    
    // Set profile image
    document.getElementById('profile-image').src = worker.profile_image;
    
    // Set rating stars
    const ratingStars = getRatingStars(worker.avg_rating);
    document.getElementById('rating-stars').innerHTML = ratingStars;
}
}

