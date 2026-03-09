// Orders Management
class OrdersManager {
    constructor() {
        this.currentUser = null;
        this.init();
    }

    init() {
        this.checkAuth();
        this.setupEventListeners();
        this.loadOrders();
    }

    checkAuth() {
        if (!authManager.isAuthenticated()) {
            window.location.href = '../index.html';
            return;
        }
        this.currentUser = authManager.getCurrentUser();
    }

    setupEventListeners() {
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => authManager.handleLogout());
        }

        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.loadOrders());
        }

        const modal = document.getElementById('orderModal');
        if (modal) {
            const closeBtn = modal.querySelector('.close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.hideOrderModal());
            }

            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hideOrderModal();
                }
            });
        }
    }

    async loadOrders() {
        const loading = document.getElementById('ordersLoading');
        const ordersList = document.getElementById('ordersList');
        const emptyOrders = document.getElementById('emptyOrders');

        if (loading) loading.style.display = 'block';
        if (ordersList) ordersList.innerHTML = '<tr><td colspan="6" class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-emerald-600"></i><p class="mt-2 text-slate-500">Chargement...</p></td></tr>';
        if (emptyOrders) emptyOrders.style.display = 'none';

        try {
            const status = document.getElementById('statusFilter').value;
            const response = await api.getOrders(status ? { status } : {});

            if (!response.success || !response.orders || response.orders.length === 0) {
                if (emptyOrders) emptyOrders.style.display = 'block';
                if (ordersList) ordersList.innerHTML = '';
            } else {
                this.renderOrders(response.orders);
            }
        } catch (error) {
            console.error('Error loading orders:', error);
            if (ordersList) {
                ordersList.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-500">Erreur: ' + error.message + '</td></tr>';
            }
        } finally {
            if (loading) loading.style.display = 'none';
        }
    }

    renderOrders(orders) {
        const ordersList = document.getElementById('ordersList');
        if (!ordersList) return;

        const html = orders.map(order => {
            const statusClass = this.getStatusClass(order.Statut);
            const itemCount = order.produits ? order.produits.length : (order.items ? order.items.length : 0);
            
            // Check if order is in delivery status for map button
            const isInDelivery = order.Statut === 'En livraison' || order.Statut_Livraison === 'En cours' || order.Statut_Livraison === 'Assignée';
            const mapButton = isInDelivery ? `<button class="action-btn bg-orange-500 text-white hover:bg-orange-600 ml-2" onclick="ordersManager.showOrderMapFromList(${order.ID_Commande})"><i class="fas fa-map-marker-alt"></i></button>` : '';
            
            return '<tr>' +
                '<td class="font-bold">#' + order.ID_Commande + '</td>' +
                '<td>' + this.formatDate(order.Date_Commande) + '</td>' +
                '<td>' + itemCount + ' article' + (itemCount > 1 ? 's' : '') + '</td>' +
                '<td class="font-bold text-emerald-600">' + parseFloat(order.Prix_Total).toFixed(2) + ' €</td>' +
                '<td><span class="status-badge ' + statusClass + '">' + order.Statut + '</span></td>' +
                '<td><button class="action-btn btn-view" onclick="ordersManager.showOrderDetails(' + order.ID_Commande + ')"><i class="fas fa-eye"></i> Voir</button>' + mapButton + '</td>' +
            '</tr>';
        }).join('');

        ordersList.innerHTML = html;
    }

    // Show map directly from orders list
    async showOrderMapFromList(orderId) {
        try {
            const response = await api.getOrder(orderId);
            if (response.success && response.order) {
                this.currentOrder = response.order;
                this.showMapModal(response.order);
            }
        } catch (error) {
            console.error('Error loading order for map:', error);
            alert('Erreur lors du chargement de la carte');
        }
    }

    async showOrderDetails(orderId) {
        console.log('Showing order details for:', orderId);
        
        try {
            const response = await api.getOrder(orderId);
            console.log('Order response:', response);
            
            if (response.success && response.order) {
                this.renderOrderDetails(response.order);
                document.getElementById('orderModal').style.display = 'block';
            } else {
                alert('Commande non trouvée');
            }
        } catch (error) {
            console.error('Error loading order details:', error);
            alert('Erreur lors du chargement: ' + error.message);
        }
    }

    renderOrderDetails(order) {
        const details = document.getElementById('orderDetails');
        if (!details) {
            console.error('orderDetails element not found!');
            return;
        }
        
        // Store current order for map access
        this.currentOrder = order;
        
        const statusClass = this.getStatusClass(order.Statut);
        
        let productsHtml = '';
        if (order.produits && order.produits.length > 0) {
            productsHtml = order.produits.map(p => 
                '<tr class="border-t border-slate-200">' +
                    '<td class="px-4 py-3"><p class="font-semibold">' + p.Nom + '</p><p class="text-sm text-slate-500">' + (p.Categorie || '') + '</p></td>' +
                    '<td class="px-4 py-3 text-center">' + p.Quantite + '</td>' +
                    '<td class="px-4 py-3 text-right font-semibold text-emerald-600">' + (p.Prix_Unitaire * p.Quantite).toFixed(2) + ' €</td>' +
                '</tr>'
            ).join('');
        } else {
            productsHtml = '<tr><td colspan="3" class="px-4 py-3 text-center text-slate-500">Aucun produit</td></tr>';
        }

        let deliveryHtml = '';
        if (order.Statut_Livraison) {
            // Format estimated delivery time
            let estimatedTimeHtml = '';
            if (order.estimated_delivery_minutes) {
                const minutes = order.estimated_delivery_minutes;
                if (minutes < 60) {
                    estimatedTimeHtml = '<div><p class="text-sm text-slate-500">Temps estimé</p><p class="font-semibold text-emerald-600"><i class="fas fa-clock mr-1"></i>~' + minutes + ' min</p></div>';
                } else {
                    const hours = Math.floor(minutes / 60);
                    const mins = minutes % 60;
                    estimatedTimeHtml = '<div><p class="text-sm text-slate-500">Temps estimé</p><p class="font-semibold text-emerald-600"><i class="fas fa-clock mr-1"></i>~' + hours + 'h ' + mins + 'min</p></div>';
                }
            }
            
            // Show real-time distance if available
            let distanceHtml = '';
            if (order.real_time_distance_km) {
                distanceHtml = '<div><p class="text-sm text-slate-500">Distance</p><p class="font-semibold">' + order.real_time_distance_km + ' km</p></div>';
            } else if (order.Distance_KM) {
                distanceHtml = '<div><p class="text-sm text-slate-500">Distance</p><p class="font-semibold">' + order.Distance_KM + ' km</p></div>';
            }
            
            // Show "View Map" button if tracking data is available
            let mapButtonHtml = '';
            if (order.livreur_position && order.client_position && (order.Statut === 'En livraison' || order.Statut_Livraison === 'En cours' || order.Statut_Livraison === 'Assignée')) {
                mapButtonHtml = '<button onclick="ordersManager.showMapModalFromCurrentOrder()" class="mt-4 w-full px-4 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition-all">' +
                    '<i class="fas fa-map-marker-alt mr-2"></i>Voir la carte' +
                '</button>';
            }
            
            deliveryHtml = '<div class="bg-slate-50 p-6 rounded-xl mt-4">' +
                '<h3 class="font-bold text-slate-900 mb-4"><i class="fas fa-truck mr-2"></i>Informations de livraison</h3>' +
                '<div class="grid grid-cols-2 gap-4">' +
                    '<div><p class="text-sm text-slate-500">Statut</p><p class="font-semibold">' + this.getDeliveryStatusText(order.Statut_Livraison) + '</p></div>' +
                    estimatedTimeHtml +
                    distanceHtml +
                    (order.Livreur_Nom ? '<div><p class="text-sm text-slate-500">Livreur</p><p class="font-semibold">' + order.Livreur_Nom + '</p></div>' : '') +
                    (order.Telephone || order.Livreur_Telephone ? '<div><p class="text-sm text-slate-500">Téléphone</p><p class="font-semibold">' + (order.Telephone || order.Livreur_Telephone) + '</p></div>' : '') +
                '</div>' +
                mapButtonHtml +
            '</div>';
        }

        details.innerHTML = 
            '<h2 class="text-2xl font-bold text-slate-900 mb-6">Détails de la commande #' + order.ID_Commande + '</h2>' +
            
            '<div class="bg-slate-50 p-6 rounded-xl mb-6">' +
                '<h3 class="font-bold text-slate-900 mb-4"><i class="fas fa-info-circle mr-2"></i>Informations générales</h3>' +
                '<div class="grid grid-cols-2 gap-4">' +
                    '<div><p class="text-sm text-slate-500">Date</p><p class="font-semibold">' + this.formatDate(order.Date_Commande) + '</p></div>' +
                    '<div><p class="text-sm text-slate-500">Statut</p><span class="status-badge ' + statusClass + '">' + order.Statut + '</span></div>' +
                    '<div class="col-span-2"><p class="text-sm text-slate-500">Adresse de livraison</p><p class="font-semibold">' + (order.Adresse_Livraison || 'Non spécifiée') + '</p></div>' +
                    (order.Notes ? '<div class="col-span-2"><p class="text-sm text-slate-500">Notes</p><p class="font-semibold">' + order.Notes + '</p></div>' : '') +
                '</div>' +
            '</div>' +

            '<div class="mb-6">' +
                '<h3 class="font-bold text-slate-900 mb-4"><i class="fas fa-shopping-bag mr-2"></i>Produits commandés</h3>' +
                '<div class="bg-slate-50 rounded-xl overflow-hidden">' +
                    '<table class="w-full">' +
                        '<thead class="bg-slate-100">' +
                            '<tr><th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Produit</th><th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">Qté</th><th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">Prix</th></tr>' +
                        '</thead>' +
                        '<tbody>' + productsHtml + '</tbody>' +
                        '<tfoot class="bg-slate-100">' +
                            '<tr><td colspan="2" class="px-4 py-3 text-right font-bold">Total:</td><td class="px-4 py-3 text-right font-bold text-emerald-600 text-lg">' + parseFloat(order.Prix_Total).toFixed(2) + ' €</td></tr>' +
                        '</tfoot>' +
                    '</table>' +
                '</div>' +
            '</div>' +

            deliveryHtml +

            '<div class="mt-6 text-center">' +
                '<button onclick="ordersManager.hideOrderModal()" class="px-6 py-3 bg-slate-200 text-slate-700 rounded-full font-bold hover:bg-slate-300 transition-all"><i class="fas fa-times mr-2"></i>Fermer</button>' +
            '</div>';
    }

    hideOrderModal() {
        const modal = document.getElementById('orderModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Show map modal with livreur and delivery location
    showMapModal(order) {
        const mapContent = document.getElementById('mapContent');
        const mapModal = document.getElementById('mapModal');
        
        if (!order.livreur_position || !order.client_position) {
            alert('Position du livreur non disponible');
            return;
        }

        const livreurIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const clientIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        mapContent.innerHTML = `
            <h2 class="text-2xl font-bold text-slate-900 mb-4">
                <i class="fas fa-map-marker-alt mr-2 text-emerald-600"></i>
                Suivi de livraison - Commande #${order.ID_Commande}
            </h2>
            <div class="bg-slate-50 p-4 rounded-xl mb-4">
                <div class="flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-blue-500"></span>
                        <span class="font-medium">Livreur</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded-full bg-green-500"></span>
                        <span class="font-medium">Votre adresse</span>
                    </div>
                </div>
            </div>
            <div id="map" style="height: 400px; width: 100%; border-radius: 1rem;"></div>
            <div class="mt-4 flex items-center justify-between">
                <div class="text-sm text-slate-500">
                    ${order.real_time_distance_km ? `<i class="fas fa-route mr-1"></i> ${order.real_time_distance_km} km` : ''}
                    ${order.real_time_estimated_minutes ? ` • <i class="fas fa-clock mr-1"></i> ~${order.real_time_estimated_minutes} min` : ''}
                </div>
                <button onclick="ordersManager.hideMapModal()" class="px-6 py-2 bg-slate-200 text-slate-700 rounded-full font-medium hover:bg-slate-300 transition-all">
                    <i class="fas fa-times mr-1"></i> Fermer
                </button>
            </div>
        `;

        mapModal.style.display = 'block';

    // Initialize map after modal is shown
        setTimeout(() => {
            const map = L.map('map');
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            const livreurPos = [order.livreur_position.latitude, order.livreur_position.longitude];
            const clientPos = [order.client_position.latitude, order.client_position.longitude];

            // Add markers
            L.marker(livreurPos, { icon: livreurIcon })
                .addTo(map)
                .bindPopup(`<b>📍 Livreur</b><br>${order.Livreur_Nom || 'En route vers vous'}`)
                .openPopup();

            L.marker(clientPos, { icon: clientIcon })
                .addTo(map)
                .bindPopup(`<b>🏠 Adresse de livraison</b><br>${order.Adresse_Livraison || 'Destination'}`);

            // Draw route line
            const routeLine = L.polyline([livreurPos, clientPos], {
                color: '#10b981',
                weight: 4,
                opacity: 0.7,
                dashArray: '10, 10'
            }).addTo(map);

            // Fit bounds to show both markers
            map.fitBounds(routeLine.getBounds(), { padding: [50, 50] });
        }, 100);

        // Add click outside to close
        setTimeout(() => {
            const mapModal = document.getElementById('mapModal');
            if (mapModal) {
                mapModal.addEventListener('click', function(e) {
                    if (e.target === mapModal) {
                        ordersManager.hideMapModal();
                    }
                });
            }
        }, 200);
    }

    // Show map from current order (used by button in order details)
    showMapModalFromCurrentOrder() {
        if (this.currentOrder) {
            document.getElementById('orderModal').style.display = 'none';
            this.showMapModal(this.currentOrder);
        }
    }

    hideMapModal() {
        const mapModal = document.getElementById('mapModal');
        if (mapModal) {
            mapModal.style.display = 'none';
        }
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

    getDeliveryStatusText(status) {
        const texts = {
            'En attente': 'En attente d\'assignation',
            'Assignée': 'Livreur assigné',
            'En cours': 'En cours de livraison',
            'Livrée': 'Livrée',
            'Retard': 'En retard'
        };
        return texts[status] || status;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// Create global instance
let ordersManager;

document.addEventListener('DOMContentLoaded', function() {
    ordersManager = new OrdersManager();
    window.ordersManager = ordersManager;
});

