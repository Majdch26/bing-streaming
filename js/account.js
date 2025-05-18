// Toggle password visibility
function togglePassword(id) {
    const input = document.getElementById(id);
    const icon = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Plan change modal
const planModal = document.getElementById('planModal');
const changePlanBtn = document.getElementById('changePlanBtn');

changePlanBtn.addEventListener('click', function() {
    planModal.classList.add('active');
    document.body.style.overflow = 'hidden';
});

function closeModal() {
    planModal.classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
planModal.addEventListener('click', function(e) {
    if (e.target === planModal) {
        closeModal();
    }
});

// Plan selection
document.querySelectorAll('.plan-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.plan-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        this.classList.add('selected');
        this.querySelector('input').checked = true;
    });
});

// Add animation when page loads
document.addEventListener('DOMContentLoaded', function() {
    const elements = document.querySelectorAll('.detail-card, .form-group');
    elements.forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Show confirmation modal if URL has confirm=1
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('confirm')) {
        document.querySelector('.confirmation-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
});

// Close confirmation modal
function closeConfirmation() {
    document.querySelector('.confirmation-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
    window.history.replaceState({}, document.title, window.location.pathname);
}