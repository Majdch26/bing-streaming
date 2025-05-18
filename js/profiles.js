// Profile forms
function showAddForm() {
    document.getElementById('addProfileForm').classList.add('active');
}

function hideAddForm() {
    document.getElementById('addProfileForm').classList.remove('active');
}

function showEditForm(profileId, profileName, avatar) {
    document.getElementById('editProfileId').value = profileId;
    document.getElementById('edit_profile_name').value = profileName;
    document.getElementById('deleteProfileId').value = profileId;
    
    // Set the current avatar as selected
    const avatarOptions = document.querySelectorAll('#editProfileForm .avatar-option');
    avatarOptions.forEach(option => {
        const input = option.querySelector('input');
        if (input.value === avatar) {
            option.classList.add('selected');
            input.checked = true;
        } else {
            option.classList.remove('selected');
            input.checked = false;
        }
    });
    
    hideManageProfiles();
    document.getElementById('editProfileForm').classList.add('active');
}

function hideEditForm() {
    document.getElementById('editProfileForm').classList.remove('active');
}

function showDeleteConfirm(profileId) {
    document.getElementById('deleteProfileId').value = profileId;
    document.getElementById('deleteConfirm').classList.add('active');
}

function hideDeleteConfirm() {
    document.getElementById('deleteConfirm').classList.remove('active');
}

function confirmDelete() {
    hideEditForm();
    showDeleteConfirm(document.getElementById('editProfileId').value);
}

// Manage profiles
function showManageProfiles() {
    document.getElementById('manageProfiles').classList.add('active');
}

function hideManageProfiles() {
    document.getElementById('manageProfiles').classList.remove('active');
}

// Avatar selection
document.querySelectorAll('.avatar-option').forEach(option => {
    option.addEventListener('click', function() {
        // Remove selected class from all options in this container
        const container = this.closest('.avatar-selection');
        container.querySelectorAll('.avatar-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        
        // Add selected class to clicked option
        this.classList.add('selected');
        const input = this.querySelector('input');
        input.checked = true;
    });
});

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('profile-form-container')) {
        event.target.classList.remove('active');
    }
    
    if (event.target.classList.contains('manage-profiles-container')) {
        event.target.classList.remove('active');
    }
    
    if (event.target.classList.contains('delete-confirm-overlay')) {
        event.target.classList.remove('active');
    }
});