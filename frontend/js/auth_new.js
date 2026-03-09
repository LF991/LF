// Authentication Management
class AuthManager {
    constructor() {
        this.currentUser = null;
        this.init();
    }

    init() {
        this.checkAuthStatus();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Only setup event listeners if the modal elements exist on this page
        const modal = document.getElementById('authModal');
        if (!modal) {
            // No auth modal on this page, skip setting up auth event listeners
            return;
        }

        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');
        const closeBtn = modal ? modal.querySelector('.close') : null;

        if (loginBtn) loginBtn.addEventListener('click', () => this.showLoginForm());
        if (registerBtn) registerBtn.addEventListener('click', () => this.showRegisterForm());
        if (closeBtn) closeBtn.addEventListener('click', () => this.hideModal());

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.hideModal();
            }
        });
    }

    showLoginForm() {
        const modal = document.getElementById('authModal');
        const content = document.getElementById('authContent');

        content.innerHTML = `
            <h2>Connexion</h2>
            <form id="loginForm">
                <div class="form-group">
                    <input type="email" id="loginEmail" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" id="loginPassword" placeholder="Mot de passe" required>
                </div>
                <button type="submit" class="btn btn-primary">Se connecter</button>
            </form>
            <p>Pas encore de compte ? <a href="#" id="switchToRegister">S'inscrire</a></p>
        `;

        modal.style.display = 'block';

        // Setup form submission
        document.getElementById('loginForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });

        document.getElementById('switchToRegister').addEventListener('click', () => {
            this.hideModal();
            this.showRegisterForm();
        });
    }

    showRegisterForm() {
        const modal = document.getElementById('authModal');
        const content = document.getElementById('authContent');

        content.innerHTML = `
            <h2>Inscription</h2>
            <form id="registerForm">
                <div class="form-group">
                    <input type="text" id="registerName" placeholder="Nom complet" required>
                </div>
                <div class="form-group">
                    <input type="email" id="registerEmail" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" id="registerPassword" placeholder="Mot de passe" required>
                </div>
                <div class="form-group">
                    <input type="text" id="registerAddress" placeholder="Adresse">
                </div>
                <div class="form-group">
                    <input type="tel" id="registerPhone" placeholder="Téléphone">
                </div>
                <button type="submit" class="btn btn-primary">S'inscrire</button>
            </form>
            <p>Déjà un compte ? <a href="#" id="switchToLogin">Se connecter</a></p>
        `;

        modal.style.display = 'block';

        // Setup form submission
        document.getElementById('registerForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegister();
        });

        document.getElementById('switchToLogin').addEventListener('click', () => {
            this.hideModal();
            this.showLoginForm();
        });
    }

    hideModal() {
        document.getElementById('authModal').style.display = 'none';
    }

    async handleLogin(credentials = null) {
        let email, password;

        if (credentials) {
            // Page-level login (login.html)
            email = credentials.email;
            password = credentials.password;
        } else {
            // Modal login
            email = document.getElementById('loginEmail').value;
            password = document.getElementById('loginPassword').value;
        }

        // Basic frontend validation
        if (!email || !password) {
            this.showError('Veuillez remplir tous les champs');
            return;
        }

        try {
            console.log('Attempting login with:', { email, password });
            const response = await api.login({email, password});
            console.log('Login response:', response);

            if (response.success && response.user) {
                // Store user data and token
                this.currentUser = response.user;
                console.log('Current user set to:', this.currentUser);

                // Save token if provided
                if (response.token) {
                    localStorage.setItem('authToken', response.token);
                    console.log('Token saved:', response.token);
                }
                
                // Also store user data in localStorage for persistence
                localStorage.setItem('userData', JSON.stringify(response.user));
                console.log('User data saved:', response.user);

                if (!credentials) {
                    // Only hide modal for modal login
                    this.hideModal();
                }

                this.showSuccess('Connexion réussie !');

                // Redirect based on user role
                setTimeout(() => {
                    this.redirectBasedOnRole();
                }, 1000);
            } else {
                throw new Error(response.message || 'Login failed');
            }
        } catch (error) {
            this.showError(error.message || 'Erreur de connexion');
        }
    }

    async handleRegister() {
        const userData = {
            name: document.getElementById('registerName').value,
            email: document.getElementById('registerEmail').value,
            password: document.getElementById('registerPassword').value,
            address: document.getElementById('registerAddress').value,
            phone: document.getElementById('registerPhone').value
        };

        try {
            const response = await api.register(userData);
            this.showSuccess('Inscription réussie ! Vous pouvez maintenant vous connecter.');
            this.hideModal();
        } catch (error) {
            this.showError(error.message);
        }
    }

    async handleLogout() {
        try {
            await api.logout();
            this.currentUser = null;
            localStorage.removeItem('authToken');
            localStorage.removeItem('userData');
            this.showSuccess('Déconnexion réussie');
            // Redirect to index.html after logout
            setTimeout(() => {
                window.location.href = '../index.html';
            }, 1000);
        } catch (error) {
            console.error('Logout error:', error);
        }
    }

    checkAuthStatus() {
        // First try to get user data from localStorage
        const userDataStr = localStorage.getItem('userData');
        if (userDataStr) {
            try {
                this.currentUser = JSON.parse(userDataStr);
                console.log('User restored from localStorage:', this.currentUser);
                this.updateUI();
                return;
            } catch (e) {
                console.error('Error parsing userData from localStorage:', e);
            }
        }
        
        // Fallback: try to decode from token
        const token = localStorage.getItem('authToken');
        if (token) {
            // Decode token to get user info without making API call
            const userData = this.decodeToken(token);
            if (userData) {
                this.currentUser = {
                    id: userData.user_id,
                    role: userData.role,
                    name: userData.name || 'User'
                };
                // Save user data for future use
                localStorage.setItem('userData', JSON.stringify(this.currentUser));
                console.log('User restored from token:', this.currentUser);
            }
        }
        this.updateUI();
    }

    decodeToken(token) {
        try {
            const parts = token.split('.');
            if (parts.length !== 3) return null;
            
            const payload = parts[1];
            // Replace URL-safe characters
            const decoded = atob(payload.replace(/-/g, '+').replace(/_/g, '/'));
            return JSON.parse(decoded);
        } catch (e) {
            console.error('Error decoding token:', e);
            return null;
        }
    }

    updateUI() {
        const navAuth = document.querySelector('.nav-auth');
        if (!navAuth) return; // Skip if nav-auth not found (e.g., on products.html)

        if (this.currentUser) {
            navAuth.innerHTML = `
                <button id="logoutBtn" class="btn btn-outline">Déconnexion</button>
            `;

            document.getElementById('logoutBtn').addEventListener('click', () => this.handleLogout());
        } else {
            navAuth.innerHTML = `
                <button id="loginBtn" class="btn btn-outline">Connexion</button>
                <button id="registerBtn" class="btn btn-primary">Inscription</button>
            `;

            document.getElementById('loginBtn').addEventListener('click', () => this.showLoginForm());
            document.getElementById('registerBtn').addEventListener('click', () => this.showRegisterForm());
        }
    }

    redirectBasedOnRole() {
        // Redirect based on user role after login
        if (!this.currentUser || !this.currentUser.role) {
            console.error('User data not available for redirection');
            window.location.href = 'client/products.html'; // Default fallback
            return;
        }

        switch (this.currentUser.role.toLowerCase()) {
            case 'admin':
                window.location.href = 'admin/dashboard.html';
                break;
            case 'livreur':
                window.location.href = 'livreur/dashboard.html';
                break;
            default:
                window.location.href = 'client/products.html';
        }
    }

    showDashboard() {
        // Redirect based on user role
        this.redirectBasedOnRole();
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    getCurrentUser() {
        return this.currentUser;
    }

    isAuthenticated() {
        // Check both currentUser and localStorage
        if (this.currentUser !== null) {
            return true;
        }
        // Also check if token exists in localStorage
        const token = localStorage.getItem('authToken');
        return token !== null && token !== '';
    }

    hasRole(role) {
        return this.currentUser && this.currentUser.role === role;
    }
}

// Create global auth manager instance
const authManager = new AuthManager();

// Export for use in other modules
window.AuthManager = AuthManager;
window.authManager = authManager;
