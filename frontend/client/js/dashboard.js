// Client Dashboard JavaScript
class ClientDashboard {
    constructor() {
        this.currentUser = null;
        this.init();
    }

    async init() {
        await this.loadUserData();
        await this.loadStats();
        await this.loadRecentOrders();
        await this.loadNotifications();
        this.setupEventListeners();
    }

    async loadUserData() {
        try {
            const userData = localStorage.getItem('userData');
            if (userData) {
                this.currentUser = JSON.parse(userData);
                this.updateProfileUI();
            } else {
                const token = localStorage.getItem('authToken');
                if (token) {
                    const decoded = this.decodeToken(token);
                    if (decoded) {
                        this.currentUser = {
                            id: decoded.user_id,
                            name: decoded.name,
                            email: decoded.email,
                            role: decoded.role
                        };
                    }
                }
            }
        } catch (error) {
            console.error('Error loading user data:', error);
        }
    }

    decodeToken(token) {
        try {
            const parts = token.split('.');
            if (parts.length !== 3) return null;
            const payload = parts[1];
            const decoded = atob(payload.replace(/-/g, '+').replace(/_/g, '/'));
            return JSON.parse(decoded);
        } catch (e) {
            console.error('Error decoding token:', e);
            return null;
        }
    }

    updateProfileUI() {
        if (!this.currentUser) return;

        const userNameEl = document.getElementById('userName');
        if (userNameEl) {
            userNameEl.textContent = this.currentUser.name || 'Client';
        }

        const profileNameEl = document.getElementById('profileName');
        if (profileNameEl) {
            profileNameEl.textContent = this.currentUser.name || 'Client';
        }

        const profileEmailEl = document.getElementById('profileEmail');
        if (profileEmailEl) {
            profileEmailEl.textContent = this.currentUser.email || 'client@email.com';
        }

        const infoNameEl = document.getElementById('infoName');
        if (infoNameEl) {
            infoNameEl.textContent = this.currentUser.name || '-';
        }

        const infoEmailEl = document.getElementById('infoEmail');
        if (infoEmailEl) {
            infoEmailEl.textContent = this.currentUser.email || '-';
        }

        const infoPhoneEl = document.getElementById('infoPhone');
        if (infoPhoneEl) {
            infoPhoneEl.textContent = this.currentUser.phone || '-';
        }

        const infoAddressEl = document.getElementById('infoAddress');
        if (infoAddressEl) {
            infoAddressEl.textContent = this.currentUser.address || '-';
        }
    }

    async loadStats() {
        try {
            const response = await api.getOrders();
            
            if (response.success && response.orders) {
                const orders = response.orders;
                const totalOrders = orders.length;
                
                const pendingOrders = orders.filter(o => 
                    ['En attente', 'Confirmée', 'En préparation', 'Prête', 'En livraison'].includes(o.Statut)
                ).length;
                
                const deliveredOrders = orders.filter(o => 
                    o.Statut === 'Livrée'
                ).length;
                
                const totalSpent = orders.reduce((sum, order) => {
                    return sum + (parseFloat(order.Prix_Total) || 0);
                }, 0);

                document.getElementById('totalOrders').textContent = totalOrders;
                document.getElementById('pendingOrders').textContent = pendingOrders;
                document.getElementById('deliveredOrders').textContent = deliveredOrders;
                document.getElementById('totalSpent').textContent = totalSpent.toFixed(2) + ' €';
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    async loadRecentOrders() {
        const container = document.getElementById('recentOrdersList');
        if (!container) return;

        try {
            const response = await api.getOrders();
            
            if (response.success && response.orders && response.orders.length > 0) {
                const recentOrders = response.orders
                    .sort((a, b) => new Date(b.Date_Commande) - new Date(a.Date_Commande))
                    .slice(0, 5);

                container.innerHTML = recentOrders.map(order => this.renderOrderItem(order)).join('');
                
                container.querySelectorAll('.recent-order-item').forEach(item => {
                    item.addEventListener('click', () => {
                        const orderId = item.dataset.orderId;
                        window.location.href = `orders.html?order=${orderId}`;
                    });
                });
            } else {
                container.innerHTML = `
                    <div class="empty-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <p>Aucune commande récente</p>
                        <a href="products.html" class="btn btn-primary" style="margin-top: 1rem;">Commander maintenant</a>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading recent orders:', error);
            container.innerHTML = `
                <div class="empty-orders">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erreur lors du chargement des commandes</p>
                </div>
            `;
        }
    }

    renderOrderItem(order) {
        const statusClass = this.getStatusClass(order.Statut);
        const date = new Date(order.Date_Commande).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });

        return `
            <div class="recent-order-item" data-order-id="${order.ID_Commande}">
                <div class="order-status-indicator ${statusClass}"></div>
                <div class="recent-order-info">
                    <div class="recent-order-id">Commande #${order.ID_Commande}</div>
                    <div class="recent-order-date">${date}</div>
                </div>
                <div class="recent-order-status ${statusClass}">${order.Statut}</div>
                <div class="recent-order-total">${parseFloat(order.Prix_Total).toFixed(2)} €</div>
            </div>
        `;
    }

    getStatusClass(status) {
        const statusMap = {
            'En attente': 'pending',
            'Confirmée': 'confirmed',
            'En préparation': 'preparing',
            'Prête': 'ready',
            'En livraison': 'delivering',
            'Livrée': 'delivered',
            'Annulée': 'cancelled'
        };
        return statusMap[status] || 'pending';
    }

    async loadNotifications() {
        const container = document.getElementById('notificationsList');
        const badge = document.getElementById('unreadBadge');
        if (!container) return;

        try {
            const response = await api.getNotifications();
            
            if (response.success && response.notifications && response.notifications.length > 0) {
                if (response.unread_count > 0 && badge) {
                    badge.textContent = response.unread_count;
                    badge.style.display = 'inline-block';
                }

                container.innerHTML = response.notifications
                    .map(notification => this.renderNotification(notification))
                    .join('');

                container.querySelectorAll('.notification-item').forEach(item => {
                    item.addEventListener('click', () => {
                        const notifId = item.dataset.notificationId;
                        this.markNotificationAsRead(notifId, item);
                    });
                });
            } else {
                // Show empty state with option to create test notifications
                container.innerHTML = `
                    <div class="empty-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <p>Aucune notification</p>
                        <button onclick="window.clientDashboard.createTestNotifications()" class="btn btn-sm btn-outline mt-2 text-xs">
                            <i class="fas fa-plus mr-1"></i>Créer des notifications de test
                        </button>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            container.innerHTML = `
                <div class="empty-notifications">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erreur lors du chargement</p>
                    <button onclick="window.clientDashboard.createTestNotifications()" class="btn btn-sm btn-outline mt-2 text-xs">
                        <i class="fas fa-plus mr-1"></i>Créer des notifications de test
                    </button>
                </div>
            `;
        }
    }

    async createTestNotifications() {
        try {
            const response = await api.createTestNotifications();
            if (response.success) {
                this.showNotification(`${response.count} notifications de test créées`, 'success');
                // Reload notifications
                await this.loadNotifications();
            } else {
                this.showNotification(response.message || 'Erreur lors de la création', 'error');
            }
        } catch (error) {
            console.error('Error creating test notifications:', error);
            this.showNotification('Erreur: ' + error.message, 'error');
        }
    }

    renderNotification(notification) {
        const isUnread = notification.status === 'Non lu';
        const iconClass = this.getNotificationIconClass(notification.type);
        const timeAgo = this.getTimeAgo(new Date(notification.created_at));

        return `
            <div class="notification-item ${isUnread ? 'unread' : ''}" 
                 data-notification-id="${notification.id}"
                 data-link="${notification.link || ''}">
                <div class="notification-icon ${iconClass}">
                    <i class="${this.getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
            </div>
        `;
    }

    getNotificationIconClass(type) {
        const iconMap = {
            'commande': 'order',
            'livraison': 'delivery',
            'alerte': 'alert',
            'success': 'success',
            'info': 'order'
        };
        return iconMap[type] || 'order';
    }

    getNotificationIcon(type) {
        const iconMap = {
            'commande': 'fas fa-shopping-bag',
            'livraison': 'fas fa-truck',
            'alerte': 'fas fa-exclamation-triangle',
            'success': 'fas fa-check-circle',
            'info': 'fas fa-info-circle'
        };
        return iconMap[type] || 'fas fa-bell';
    }

    getTimeAgo(date) {
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'À l\'instant';
        if (minutes < 60) return `Il y a ${minutes} min`;
        if (hours < 24) return `Il y a ${hours} h`;
        if (days < 7) return `Il y a ${days} j`;
        
        return date.toLocaleDateString('fr-FR');
    }

    async markNotificationAsRead(notificationId, element) {
        try {
            await api.markNotificationRead(notificationId);
            element.classList.remove('unread');
            
            const badge = document.getElementById('unreadBadge');
            if (badge) {
                const currentCount = parseInt(badge.textContent) || 0;
                if (currentCount > 0) {
                    badge.textContent = currentCount - 1;
                    if (currentCount - 1 === 0) {
                        badge.style.display = 'none';
                    }
                }
            }

            const link = element.dataset.link;
            if (link) {
                window.location.href = link;
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllNotificationsAsRead() {
        try {
            const container = document.getElementById('notificationsList');
            const unreadItems = container.querySelectorAll('.notification-item.unread');
            
            for (const item of unreadItems) {
                const notifId = item.dataset.notificationId;
                await api.markNotificationRead(notifId);
                item.classList.remove('unread');
            }

            const badge = document.getElementById('unreadBadge');
            if (badge) {
                badge.style.display = 'none';
            }

            this.showNotification('Toutes les notifications ont été marquées comme lues', 'success');
        } catch (error) {
            console.error('Error marking all as read:', error);
            this.showNotification('Erreur lors de la mise à jour', 'error');
        }
    }

    setupEventListeners() {
        const markAllReadBtn = document.getElementById('markAllRead');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => {
                this.markAllNotificationsAsRead();
            });
        }

        const editProfileBtn = document.getElementById('editProfileBtn');
        if (editProfileBtn) {
            editProfileBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.showEditProfileModal();
            });
        }

        const closeModalBtn = document.getElementById('closeProfileModal');
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => {
                this.hideEditProfileModal();
            });
        }

        const cancelEditBtn = document.getElementById('cancelEditBtn');
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', () => {
                this.hideEditProfileModal();
            });
        }

        const modal = document.getElementById('editProfileModal');
        if (modal) {
            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hideEditProfileModal();
                }
            });
        }

        const editProfileForm = document.getElementById('editProfileForm');
        if (editProfileForm) {
            editProfileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleProfileUpdate();
            });
        }
    }

    showEditProfileModal() {
        const modal = document.getElementById('editProfileModal');
        if (!modal || !this.currentUser) return;

        document.getElementById('editName').value = this.currentUser.name || '';
        document.getElementById('editEmail').value = this.currentUser.email || '';
        document.getElementById('editPhone').value = this.currentUser.phone || '';
        document.getElementById('editAddress').value = this.currentUser.address || '';

        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        document.getElementById('confirmPassword').value = '';

        modal.style.display = 'block';
    }

    hideEditProfileModal() {
        const modal = document.getElementById('editProfileModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    async handleProfileUpdate() {
        const name = document.getElementById('editName').value;
        const email = document.getElementById('editEmail').value;
        const phone = document.getElementById('editPhone').value;
        const address = document.getElementById('editAddress').value;
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword || currentPassword) {
            if (!currentPassword) {
                this.showNotification('Veuillez entrer le mot de passe actuel', 'error');
                return;
            }
            if (!newPassword) {
                this.showNotification('Veuillez entrer le nouveau mot de passe', 'error');
                return;
            }
            if (newPassword !== confirmPassword) {
                this.showNotification('Les mots de passe ne correspondent pas', 'error');
                return;
            }
            if (newPassword.length < 6) {
                this.showNotification('Le mot de passe doit contenir au moins 6 caractères', 'error');
                return;
            }
        }

        try {
            const response = await api.updateProfile({
                name,
                email,
                phone,
                address,
                current_password: currentPassword,
                new_password: newPassword
            });

            if (response.success) {
                this.currentUser = {
                    ...this.currentUser,
                    name,
                    email,
                    phone,
                    address
                };
                localStorage.setItem('userData', JSON.stringify(this.currentUser));

                this.updateProfileUI();

                this.hideEditProfileModal();
                this.showNotification('Profil mis à jour avec succès', 'success');
            } else {
                throw new Error(response.message || 'Erreur lors de la mise à jour');
            }
        } catch (error) {
            console.error('Error updating profile:', error);
            this.showNotification(error.message || 'Erreur lors de la mise à jour du profil', 'error');
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.clientDashboard = new ClientDashboard();
});
