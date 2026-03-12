document.addEventListener('DOMContentLoaded', function() {
    loadWorkers();
    setupJobForm();
});

function loadWorkers() {
    fetch('api/workers.php')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('workers-container');
            container.innerHTML = '';
            
            data.forEach(worker => {
                const card = createWorkerCard(worker);
                container.appendChild(card);
            });
        })
        .catch(error => {
            console.error('Error loading workers:', error);
            showAlert('Error loading workers. Please try again.', 'danger');
        });
}

function createWorkerCard(worker) {
    const card = document.createElement('div');
    card.className = 'col-md-4';
    card.innerHTML = `
        <div class="card worker-card">
            <img src="https://picsum.photos/seed/${worker.id}/400/300.jpg" class="card-img-top" alt="${worker.name}">
            <div class="card-body">
                <h5 class="card-title">${worker.name}</h5>
                <p class="card-text">${worker.description.substring(0, 100)}...</p>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary">${worker.type}</span>
                        <span class="badge bg-success">${worker.location}</span>
                    </div>
                    <a href="worker-details.php?id=${worker.id}" class="btn btn-sm btn-primary">View Profile</a>
                </div>
            </div>
        </div>
    `;
    return card;
}

function setupJobForm() {
    const form = document.getElementById('job-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const jobData = {
                title: document.getElementById('job-title').value,
                description: document.getElementById('job-description').value,
                type: document.getElementById('job-type').value,
                salary: document.getElementById('salary').value,
                location: document.getElementById('location').value,
                work_hours: document.getElementById('work-hours').value
            };
            
            postJob(jobData);
        });
    }
}

function postJob(jobData) {
    fetch('api/jobs.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(jobData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Job posted successfully!', 'success');
            document.getElementById('job-form').reset();
        } else {
            showAlert('Error posting job: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error posting job:', error);
        showAlert('Error posting job. Please try again.', 'danger');
    });
}

function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function formatPhoneNumber(phone) {
    const cleaned = ('' + phone).replace(/\D/g, '');
    const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
    if (match) {
        return '(' + match[1] + ') ' + match[2] + '-' + match[3];
    }
    return phone;
}

// Auto-hide alerts
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 5000);
});