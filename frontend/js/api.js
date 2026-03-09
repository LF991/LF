// API Configuration
const API_BASE_URL = '/castlemarket/backend';

// API Class
class ApiManager {
    constructor() {
        this.baseUrl = API_BASE_URL;
    }

    // Generic request method
    async request(endpoint, options = {}) {
    const url = `${this.baseUrl}${endpoint}`;
    const config = {
        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        },
        ...options
    };

    const token = localStorage.getItem('authToken');
    if (token) {
        config.headers['Authorization'] = `Bearer ${token}`;
    }

    try {
        const response = await fetch(url, config);
        
        // Get response text first to check if it's empty
        const text = await response.text();
        
        // Check if response is empty
        if (!text || text.trim() === '') {
            console.error('Empty response from:', url);
            throw new Error('Réponse vide du serveur');
        }
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON from:', url, text);
            throw new Error('Réponse invalide du serveur');
        }

        if (!response.ok) {
            throw new Error(data.error || data.message || `HTTP ${response.status}`);
        }

        return data;

    } catch (error) {
        console.error('API Request failed:', url, error);
        // Check if it's a network error
        if (error.name === 'TypeError' && error.message === 'Failed to fetch') {
            throw new Error('Erreur de connexion. Vérifiez que le serveur est en cours d\'exécution.');
        }
        throw error;
    }
}

    // Authentication methods
    async login(credentials) {
        const formData = new FormData();
        formData.append('email', credentials.email);
        formData.append('password', credentials.password);

        return this.request('/auth/login.php', {
            method: 'POST',
            headers: {}, // Remove Content-Type to let browser set it for FormData
            body: formData
        });
    }

    async register(userData) {
        return this.request('/auth/register.php', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }

    async logout() {
        return this.request('/auth/logout.php', {
            method: 'POST'
        });
    }

    async updateProfile(userData) {
        return this.request('/auth/update_profile.php', {
            method: 'PUT',
            body: JSON.stringify(userData)
        });
    }

    // Products methods
    async getProducts(filters = {}) {
        const params = new URLSearchParams();

        // Set a high limit to get all products
        params.append('limit', '1000');

        Object.keys(filters).forEach(key => {
            if (filters[key] !== null && filters[key] !== undefined && filters[key] !== '') {
                params.append(key, filters[key]);
            }
        });

        const queryString = params.toString();
        const endpoint = `/products/get.php?${queryString}`;

        return this.request(endpoint);
    }

    async getProduct(productId) {
        return this.request(`/products/get_single.php?id=${productId}`);
    }

    // Cart methods
    async getCart() {
        return this.request('/cart/get.php');
    }

    async addToCart(productId, quantity = 1) {
        return this.request('/cart/add.php', {
            method: 'POST',
            body: JSON.stringify({ product_id: productId, quantity })
        });
    }

    async updateCart(productId, quantity) {
        return this.request('/cart/update.php', {
            method: 'PUT',
            body: JSON.stringify({ product_id: productId, quantity })
        });
    }

    async removeFromCart(productId) {
        return this.request('/cart/remove.php', {
            method: 'DELETE',
            body: JSON.stringify({ product_id: productId })
        });
    }

    // Orders methods
    async getOrders(filters = {}) {
        const params = new URLSearchParams();
        
        if (filters.status) {
            params.append('status', filters.status);
        }
        
        const queryString = params.toString();
        const endpoint = queryString ? `/orders/get.php?${queryString}` : '/orders/get.php';
        
        return this.request(endpoint);
    }

    async getOrder(orderId) {
        return this.request(`/orders/get_single.php?id=${orderId}`);
    }

    async createOrder(orderData) {
        return this.request('/orders/create.php', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
    }

    async cancelOrder(orderId) {
        return this.request('/orders/cancel.php', {
            method: 'PUT',
            body: JSON.stringify({ order_id: orderId })
        });
    }

    // Notifications methods
    async getNotifications() {
        return this.request('/notifications/get.php');
    }

    async markNotificationRead(notificationId) {
        return this.request('/notifications/mark_read.php', {
            method: 'PUT',
            body: JSON.stringify({ notification_id: notificationId })
        });
    }

    // Debug: Create test notifications
    async createTestNotifications() {
        return this.request('/fix_notifications.php?action=create_test', {
            method: 'POST'
        });
    }

    // Debug: Get notification status
    async getNotificationStatus() {
        return this.request('/fix_notifications.php?action=status', {
            method: 'GET'
        });
    }

    // Admin methods
    async getUsers() {
        return this.request('/admin/users.php');
    }

    async getStats() {
        return this.request('/admin/stats.php');
    }

    async getAdminStats() {
        return this.request('/admin/stats.php');
    }

    async updateProduct(productId, productData) {
        return this.request(`/products/update.php?id=${productId}`, {
            method: 'PUT',
            body: JSON.stringify(productData)
        });
    }

    async deleteProduct(productId) {
        return this.request(`/products/delete.php?id=${productId}`, {
            method: 'DELETE'
        });
    }

    async createProduct(productData) {
        return this.request('/products/create.php', {
            method: 'POST',
            body: JSON.stringify(productData)
        });
    }

    // Admin Products methods
    async getAdminProducts() {
        return this.request('/admin/products.php');
    }

    async getAdminProduct(productId) {
        return this.request(`/admin/products.php?id=${productId}`);
    }

    async createAdminProduct(productData) {
        return this.request('/admin/products.php', {
            method: 'POST',
            body: JSON.stringify(productData)
        });
    }

    async updateAdminProduct(productId, productData) {
        return this.request(`/admin/products.php?id=${productId}`, {
            method: 'PUT',
            body: JSON.stringify(productData)
        });
    }

    async deleteAdminProduct(productId) {
        return this.request(`/admin/products.php?id=${productId}`, {
            method: 'DELETE'
        });
    }

    // Admin Orders methods
    async getAdminOrders() {
        return this.request('/admin/orders.php');
    }

    async updateAdminOrderStatus(orderId, status) {
        return this.request(`/admin/orders.php?id=${orderId}`, {
            method: 'PUT',
            body: JSON.stringify({ status: status })
        });
    }

    // Admin Users methods
    async getAdminUsers() {
        return this.request('/admin/users.php');
    }

    async getAdminUser(userId) {
        return this.request(`/admin/users.php?id=${userId}`);
    }

    async createAdminUser(userData) {
        return this.request('/admin/users.php', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }

    async updateAdminUser(userId, userData) {
        return this.request(`/admin/users.php?id=${userId}`, {
            method: 'PUT',
            body: JSON.stringify(userData)
        });
    }

    async deleteAdminUser(userId) {
        return this.request(`/admin/users.php?id=${userId}`, {
            method: 'DELETE'
        });
    }

    // Admin Delivery methods
    async getAdminDelivery() {
        return this.request('/admin/delivery.php');
    }

    async getAdminDeliveryPerson(deliveryId) {
        return this.request(`/admin/delivery.php?id=${deliveryId}`);
    }

    async createAdminDelivery(deliveryData) {
        return this.request('/admin/delivery.php', {
            method: 'POST',
            body: JSON.stringify(deliveryData)
        });
    }

    async updateAdminDelivery(deliveryId, deliveryData) {
        return this.request(`/admin/delivery.php?id=${deliveryId}`, {
            method: 'PUT',
            body: JSON.stringify(deliveryData)
        });
    }

    async deleteAdminDelivery(deliveryId) {
        return this.request(`/admin/delivery.php?id=${deliveryId}`, {
            method: 'DELETE'
        });
    }

    // Delivery methods
    async getDeliveries() {
        return this.request('/delivery/get.php');
    }

    async updateDeliveryStatus(deliveryId, status) {
        return this.request('/delivery/update_status.php', {
            method: 'PUT',
            body: JSON.stringify({ delivery_id: deliveryId, status })
        });
    }

    // Livreur specific: Update delivery status by order ID
    async updateDeliveryStatusByOrderId(orderId, status) {
        return this.request('/delivery/update_status.php', {
            method: 'POST',
            body: JSON.stringify({ order_id: orderId, status: status })
        });
    }

    async assignDelivery(orderId, livreurId) {
        return this.request('/delivery/assign.php', {
            method: 'POST',
            body: JSON.stringify({ order_id: orderId, livreur_id: livreurId })
        });
    }

    // Chat methods
    async sendMessage(message, category = 'General') {
        return this.request('/chat/send.php', {
            method: 'POST',
            body: JSON.stringify({ message, category })
        });
    }

    async getChatHistory() {
        return this.request('/chat/history.php');
    }

    // Livreur Dashboard methods
    async getDeliveryPendingOrders() {
        return this.request('/delivery/get_pending.php');
    }

    async getDeliveryAssignedOrders() {
        return this.request('/delivery/get_assigned.php');
    }

    async getDeliveryCompletedOrders() {
        return this.request('/delivery/get_completed.php');
    }

    async getDeliveryOrderDetails(orderId) {
        return this.request(`/delivery/get_single.php?id=${orderId}`);
    }

    async acceptDeliveryOrder(orderId) {
        return this.request('/delivery/accept.php', {
            method: 'POST',
            body: JSON.stringify({ order_id: orderId })
        });
    }

    async completeDeliveryOrder(orderId) {
        return this.request('/delivery/complete.php', {
            method: 'POST',
            body: JSON.stringify({ order_id: orderId })
        });
    }

    // Livreur Location methods
    async updateLivreurLocation(latitude, longitude) {
        return this.request('/delivery/update_location.php', {
            method: 'POST',
            body: JSON.stringify({ latitude, longitude })
        });
    }

    async getLivreurAvailability() {
        return this.request('/delivery/get_availability.php');
    }

    async updateLivreurAvailability(availability) {
        return this.request('/delivery/update_availability.php', {
            method: 'POST',
            body: JSON.stringify({ availability })
        });
    }
}

// Token management
const tokenManager = {
    setToken(token) {
        localStorage.setItem('authToken', token);
    },
    
    getToken() {
        return localStorage.getItem('authToken');
    },
    
    removeToken() {
        localStorage.removeItem('authToken');
    }
};

// Create global API instance
const api = new ApiManager();

// Add removeToken to API
api.removeToken = function() {
    tokenManager.removeToken();
};

// Export for use in other modules
window.ApiManager = ApiManager;
window.api = api;
