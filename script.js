document.addEventListener('DOMContentLoaded', function() {
    setupJobForm();
});

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