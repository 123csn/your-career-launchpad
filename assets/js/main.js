// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(function(message) {
        setTimeout(function() {
            const alert = bootstrap.Alert.getOrCreateInstance(message);
            alert.close();
        }, 5000);
    });

    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            updatePasswordStrengthIndicator(strength);
        });
    }

    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            previewFile(this);
        });
    });

    // Job search form
    const jobSearchForm = document.querySelector('.job-search-form');
    if (jobSearchForm) {
        jobSearchForm.addEventListener('submit', function(e) {
            const keyword = this.querySelector('input[name="keyword"]').value.trim();
            const location = this.querySelector('input[name="location"]').value.trim();
            
            if (!keyword && !location) {
                e.preventDefault();
                alert('Please enter a keyword or location to search');
            }
        });
    }
});

// Calculate password strength
function calculatePasswordStrength(password) {
    let strength = 0;
    
    // Length check
    if (password.length >= 8) strength += 1;
    if (password.length >= 12) strength += 1;
    
    // Character type checks
    if (/[A-Z]/.test(password)) strength += 1;
    if (/[a-z]/.test(password)) strength += 1;
    if (/[0-9]/.test(password)) strength += 1;
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;
    
    return strength;
}

// Update password strength indicator
function updatePasswordStrengthIndicator(strength) {
    const indicator = document.getElementById('password-strength');
    if (!indicator) return;
    
    const strengthText = ['Weak', 'Fair', 'Good', 'Strong', 'Very Strong', 'Excellent'];
    const strengthClass = ['danger', 'warning', 'info', 'primary', 'success', 'success'];
    
    indicator.textContent = strengthText[strength];
    indicator.className = `text-${strengthClass[strength]}`;
}

// Preview uploaded file
function previewFile(input) {
    const preview = document.getElementById(`${input.id}-preview`);
    if (!preview) return;
    
    const file = input.files[0];
    if (!file) return;
    
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
}

// Confirm delete actions
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Handle job application submission
function submitJobApplication(jobId) {
    const form = document.getElementById('job-application-form');
    if (!form) return;
    
    const formData = new FormData(form);
    formData.append('job_id', jobId);
    
    fetch('apply.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Application submitted successfully!');
            setTimeout(() => window.location.href = 'my-applications.php', 2000);
        } else {
            showAlert('danger', data.message || 'An error occurred. Please try again.');
        }
    })
    .catch(error => {
        showAlert('danger', 'An error occurred. Please try again.');
        console.error('Error:', error);
    });
}

// Show alert message
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
        alert.close();
    }, 5000);
} 