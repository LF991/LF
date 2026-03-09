// Admin Dashboard Management
class AdminDashboard {
    constructor() {
        this.currentUser = null;
        this.init();
    }

    init() {
        // Only access elements that exist - don't auto load dashboard
        var userNameEl = document.getElementById('userName');
        if (userNameEl) {
            userNameEl.textContent = 'Admin';
        }
        
        var logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function() {
                if (typeof authManager !== 'undefined') {
                    authManager.handleLogout();
                }
                window.location.href = '../index.html';
            });
        }
        
        console.log('Admin Dashboard JS loaded');
    }

    checkAuth() {
        const userNameEl = document.getElementById('userName');
        if (userNameEl) {
            userNameEl.textContent = 'Admin';
        }
    }

    setupEventListeners() {
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => authManager.handleLogout());
        }
    }

    async loadDashboard() {
        const loading = document.getElementById('dashboardLoading');
        if (loading) {
            loading.style.display = 'block';
        }

        try {
            await Promise.all([
                this.loadStats(),
                this.loadRecentOrders(),
                this.loadSystemNotifications()
            ]);
        } catch (error) {
            this.showError('Erreur lors du chargement du tableau de bord: ' + error.message);
        } finally {
            if (loading) {
                loading.style.display = 'none';
            }
        }
    }

    async loadStats() {
        try {
            const response = await api.getAdminStats();
            this.renderStats(response.stats);
        } catch (error) {
            console.error('Error loading stats:', error);
            this.renderStats({
                total_orders: 0,
                total_users: 0,
                total_products: 0,
                total_revenue: 0,
                pending_orders: 0,
                active_users: 0
            });
        }
    }

    renderStats(stats) {
        const statsList = document.getElementById('statsList');
        if (!statsList) {
            console.error('statsList element not found');
            return;
        }
        
        const html = 
            '<div class="dashboard-card p-6 flex items-center gap-4"><div class="stat-icon orders"><i class="fas fa-shopping-cart"></i></div><div><h3 class="text-2xl font-bold text-slate-900">' + (stats.total_orders || 0) + '</h3><p class="text-slate-500 text-sm">Total Commandes</p></div>' +
            '<div class="dashboard-card p-6 flex items-center gap-4"><div class="stat-icon pending"><i class="fas fa-clock"></i></div><div><h3 class="text-2xl font-bold text-slate-900">' + (stats.pending_orders || 0) + '</h3><p class="text-slate-500 text-sm">En Attente</p></div>' +
            '<div class="dashboard-card p-6 flex items-center gap-4"><div class="stat-icon users"><i class="fas fa-users"></i></div><div><h3 class="text-2xl font-bold text-slate-900">' + (stats.total_users || 0) + '</h3><p class="text-slate-500 text-sm">Total Utilisateurs</p></div>' +
            '<div class="dashboard-card p-6 flex items-center gap-4"><div class="stat-icon active"><i class="fas fa-user-check"></i></div><div><h3 class="text-2xl font-bold text-slate-900">' + (stats.active_users || 0) + '</h3><p class="text-slate-500 text-sm">Actifs</p></div>' +
            '<div class="dashboard-card p-6 flex items-center gap-4"><div class="stat-icon products"><i class="fas fa-box"></i></div><div><h3 class="text-2xl font-bold text-slate-900">' + (stats.total_products || 0) + '</h3><p class="text-slate-500 text-sm">Produits</p></div>' +
            '<div class="dashboard-card p-6 flex items-center gap-4"><div class="stat-icon revenue"><i class="fas fa-euro-sign"></i></div><div><h3 class="text-2xl font-bold text-slate-900">' + (stats.total_revenue || 0) + ' €</h3><p class="text-slate-500 text-sm">Revenu Total</p></div>';
        statsList.innerHTML = html;
    }

    async loadRecentOrders() {
        try {
            const response = await api.getAdminOrders({ limit: 5 });
            this.renderRecentOrders(response.orders);
        } catch (error) {
            console.error('Error loading recent orders:', error);
            this.renderRecentOrders([]);
        }
    }

    renderRecentOrders(orders) {
        const recentOrders = document.getElementById('recentOrders');
        if (!recentOrders) {
            console.error('recentOrders element not found');
            return;
        }

        if (!orders || orders.length === 0) {
            recentOrders.innerHTML = '<p>Aucune commande récente</p>';
            return;
        }

        recentOrders.innerHTML = orders.map(order => 
            '<div class="recent-order-item">' +
                '<div class="order-info">' +
                    '<strong>Commande #' + order.ID_Commande + '</strong>' +
                    '<span class="order-date">' + this.formatDate(order.Date_Commande) + '</span>' +
                '</div>' +
                '<div class="order-status ' + this.getStatusClass(order.Statut) + '">' +
                    order.Statut +
                '</div>' +
                '<div class="order-total">' +
                    order.Prix_Total + '€' +
                '</div>' +
            '</div>'
        ).join('');
    }

    async loadSystemNotifications() {
        try {
            const notifications = [
                { type: 'info', message: 'Système opérationnel', time: 'Maintenant' },
                { type: 'warning', message: 'Stock faible pour certains produits', time: 'Il y a 2h' },
                { type: 'success', message: 'Sauvegarde automatique effectuée', time: 'Il y a 1j' }
            ];
            this.renderSystemNotifications(notifications);
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    renderSystemNotifications(notifications) {
        const notificationsDiv = document.getElementById('systemNotifications');
        if (!notificationsDiv) {
            console.error('systemNotifications element not found');
            return;
        }

        notificationsDiv.innerHTML = notifications.map(notification => 
            '<div class="notification-item ' + notification.type + '">' +
                '<div class="notification-icon">' +
                    '<i class="fas fa-' + this.getNotificationIcon(notification.type) + '"></i>' +
                '</div>' +
                '<div class="notification-content">' +
                    '<p>' + notification.message + '</p>' +
                    '<span class="notification-time">' + notification.time + '</span>' +
                '</div>' +
            '</div>'
        ).join('');
    }

    getNotificationIcon(type) {
        const icons = {
            'info': 'info-circle',
            'warning': 'exclamation-triangle',
            'success': 'check-circle',
            'error': 'times-circle'
        };
        return icons[type] || 'info-circle';
    }

    getStatusClass(status) {
        const classes = {
            'En attente': 'status-pending',
            'Confirmée': 'status-confirmed',
            'En préparation': 'status-preparing',
            'Prête': 'status-ready',
            'En livraison': 'status-delivering',
            'Livrée': 'status-delivered',
            'Annulée': 'status-cancelled'
        };
        return classes[status] || 'status-default';
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = 'notification ' + type;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

const adminDashboard = new AdminDashboard();
window.AdminDashboard = AdminDashboard;
window.adminDashboard = adminDashboard;
