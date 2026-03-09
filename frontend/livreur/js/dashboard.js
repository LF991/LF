// Livreur Dashboard JavaScript

console.log('Livreur dashboard loaded');

// Setup logout button when DOM is loaded - use authManager for proper redirect
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            authManager.handleLogout();
        });
    }
});
