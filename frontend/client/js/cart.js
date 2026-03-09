// Cart Page Management
class CartManager {
    constructor() {
        this.cartItems = [];
        this.init();
    }

    init() {
        this.checkAuth();
        this.loadCart();
        this.setupEventListeners();
    }

    checkAuth() {
        if (authManager.isAuthenticated()) {
            this.updateCartCount();
        } else {
            const logoutBtnElement = document.getElementById('logoutBtn');
            if (logoutBtnElement) logoutBtnElement.style.display = 'none';

            const navAuth = document.querySelector('.nav-auth-products');
            if (navAuth) {
                navAuth.innerHTML = `
                    <button id="loginBtn" class="btn btn-outline">Connexion</button>
                    <button id="registerBtn" class="btn btn-primary">Inscription</button>
                `;
                const loginBtn = document.getElementById('loginBtn');
                const registerBtn = document.getElementById('registerBtn');
                if (loginBtn) loginBtn.addEventListener('click', () => authManager.showLoginForm());
                if (registerBtn) registerBtn.addEventListener('click', () => authManager.showRegisterForm());
            }
            this.updateCartCount();
        }
    }

    async updateCartCount() {
        try {
            let count = 0;
            if (authManager.isAuthenticated()) {
                const response = await api.getCart();
                count = response.items?.length || 0;
            } else {
                const localCart = JSON.parse(localStorage.getItem('localCart') || '[]');
                count = localCart.length;
            }

            const cartCount = document.getElementById('cart-count');
            if (cartCount) cartCount.textContent = count;
        } catch (error) {
            console.error('Error updating cart count:', error);
            const localCart = JSON.parse(localStorage.getItem('localCart') || '[]');
            const cartCount = document.getElementById('cart-count');
            if (cartCount) cartCount.textContent = localCart.length;
        }
    }

    setupEventListeners() {
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.addEventListener('click', () => authManager.handleLogout());

        const checkoutModal = document.getElementById('checkoutModal');
        if (checkoutModal) {
            const closeBtn = checkoutModal.querySelector('.close');
            if (closeBtn) closeBtn.addEventListener('click', () => this.hideCheckoutModal());

            window.addEventListener('click', (e) => {
                if (e.target === checkoutModal) {
                    this.hideCheckoutModal();
                }
            });
        }

        const checkoutForm = document.getElementById('checkoutForm');
        if (checkoutForm) checkoutForm.addEventListener('submit', (e) => this.handleCheckout(e));
    }

    async loadCart() {
        const loading = document.getElementById('cartLoading');
        const cartItems = document.getElementById('cartItems');
        const cartSummary = document.getElementById('cartSummary');
        const emptyCart = document.getElementById('emptyCart');

        if (loading) loading.style.display = 'block';

        try {
            if (authManager.isAuthenticated()) {
                const response = await api.getCart();
                
                if (response.items && response.items.length > 0) {
                    this.cartItems = response.items.map(item => ({
                        ...item,
                        Prix: parseFloat(item.Prix) || 0,
                        Quantite: parseInt(item.Quantite) || 1
                    }));
                    this.renderCartItems();
                    this.renderCartSummary();
                    if (emptyCart) emptyCart.style.display = 'none';
                } else {
                    this.loadLocalCart();
                }
            } else {
                this.loadLocalCart();
            }
        } catch (error) {
            console.error('Error loading cart:', error);
            this.loadLocalCart();
        } finally {
            if (loading) loading.style.display = 'none';
        }
    }

    loadLocalCart() {
        const cartItems = document.getElementById('cartItems');
        const cartSummary = document.getElementById('cartSummary');
        const emptyCart = document.getElementById('emptyCart');

        const localCart = JSON.parse(localStorage.getItem('localCart') || '[]');

        if (localCart.length > 0) {
            this.cartItems = localCart;
            this.renderCartItems();
            this.renderCartSummary();
            if (emptyCart) emptyCart.style.display = 'none';
        } else {
            if (cartItems) cartItems.innerHTML = '';
            if (cartSummary) cartSummary.innerHTML = '';
            if (emptyCart) emptyCart.style.display = 'block';
        }
    }

    renderCartItems() {
        const container = document.getElementById('cartItems');
        if (!container) return;

        container.innerHTML = '';

        this.cartItems.forEach(item => {
            const itemElement = document.createElement('div');
            itemElement.className = 'cart-item';
            itemElement.dataset.productId = item.ID_Produit;
            
            itemElement.innerHTML = `
                <img src="${item.Image_URL || 'https://via.placeholder.com/150x150.png?text=No+Image'}" alt="${item.Nom}" class="cart-item-image">
                <div class="cart-item-info">
                    <h3 class="cart-item-name">${item.Nom}</h3>
                    <p class="cart-item-price">${item.Prix.toFixed(2)}€</p>
                    <div class="cart-item-quantity">
                        <button class="quantity-btn decrease-btn" data-product-id="${item.ID_Produit}" data-quantity="${item.Quantite - 1}">-</button>
                        <input type="number" class="quantity-input" value="${item.Quantite}" min="1" data-product-id="${item.ID_Produit}">
                        <button class="quantity-btn increase-btn" data-product-id="${item.ID_Produit}" data-quantity="${item.Quantite + 1}">+</button>
                    </div>
                </div>
                <div class="cart-item-total">
                    <span>${(item.Prix * item.Quantite).toFixed(2)}€</span>
                </div>
                <button class="cart-item-remove" data-product-id="${item.ID_Produit}">
                    <i class="fas fa-trash"></i>
                </button>
            `;

            container.appendChild(itemElement);
        });

        this.attachCartItemEventListeners();
    }

    attachCartItemEventListeners() {
        const container = document.getElementById('cartItems');
        if (!container) return;

        container.querySelectorAll('.decrease-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = parseInt(e.target.dataset.productId);
                const newQuantity = parseInt(e.target.dataset.quantity);
                if (newQuantity >= 1) {
                    this.updateQuantity(productId, newQuantity);
                }
            });
        });

        container.querySelectorAll('.increase-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = parseInt(e.target.dataset.productId);
                const newQuantity = parseInt(e.target.dataset.quantity);
                this.updateQuantity(productId, newQuantity);
            });
        });

        container.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', (e) => {
                const productId = parseInt(e.target.dataset.productId);
                const newQuantity = parseInt(e.target.value);
                if (newQuantity >= 1) {
                    this.updateQuantity(productId, newQuantity);
                } else {
                    e.target.value = 1;
                }
            });
        });

        container.querySelectorAll('.cart-item-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = parseInt(e.currentTarget.dataset.productId);
                if (confirm('Voulez-vous supprimer cet article du panier?')) {
                    this.removeItem(productId);
                }
            });
        });
    }

    renderCartSummary() {
        const container = document.getElementById('cartSummary');
        if (!container) return;

        const subtotal = this.cartItems.reduce((sum, item) => sum + (item.Prix * item.Quantite), 0);
        
        // Get delivery fee from stored selection if available
        const deliveryFee = this.currentDeliveryFee || 0;

        container.innerHTML = `
            <h3>Résumé de la commande</h3>
            <div class="summary-row">
                <span>Sous-total:</span>
                <span>${subtotal.toFixed(2)}€</span>
            </div>
            ${deliveryFee > 0 ? `
            <div class="summary-row">
                <span>Frais de livraison:</span>
                <span>${deliveryFee.toFixed(2)}€</span>
            </div>
            ` : ''}
            <div class="summary-row summary-total">
                <span>Total:</span>
                <span>${(subtotal + deliveryFee).toFixed(2)}€</span>
            </div>
            <button class="btn-checkout" id="checkoutBtn">
                <i class="fas fa-credit-card"></i> Commander
            </button>
        `;

        const checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', () => this.showCheckoutModal());
        }
    }

    async updateQuantity(productId, newQuantity) {
        if (newQuantity < 1) return;

        try {
            if (authManager.isAuthenticated()) {
                await api.updateCart(productId, parseInt(newQuantity));
            } else {
                const localCart = JSON.parse(localStorage.getItem('localCart') || '[]');
                const itemIndex = localCart.findIndex(item => item.ID_Produit == productId);
                if (itemIndex !== -1) {
                    localCart[itemIndex].Quantite = parseInt(newQuantity);
                    localStorage.setItem('localCart', JSON.stringify(localCart));
                }
            }
            await this.loadCart();
        } catch (error) {
            this.showError('Erreur lors de la mise à jour: ' + error.message);
        }
    }

    async removeItem(productId) {
        try {
            if (authManager.isAuthenticated()) {
                await api.removeFromCart(productId);
            } else {
                const localCart = JSON.parse(localStorage.getItem('localCart') || '[]');
                const updatedCart = localCart.filter(item => item.ID_Produit != productId);
                localStorage.setItem('localCart', JSON.stringify(updatedCart));
            }
            await this.loadCart();
            this.showSuccess('Article supprimé du panier');
        } catch (error) {
            this.showError('Erreur lors de la suppression: ' + error.message);
        }
    }

    // Delivery fee based on municipality distance from Chlef
    getDeliveryFee(municipality) {
        const distances = {
            'Chlef': 0,
            'Abou El Hassan': 10,
            'Aïn Merane': 15,
            'Bénairia': 20,
            'Boukadir': 25,
            'Chettia': 30,
            'Djidioua': 35,
            'El Karimia': 40,
            'El Marsa': 45,
            'Ouled Fares': 50,
            'Oum Drou': 55,
            'Sendjas': 60,
            'Sobha': 65,
            'Ténès': 70,
            'Zemmora': 75
        };
        
        const distance = distances[municipality] || 10;
        const feePerKm = 1.0;
        return distance * feePerKm;
    }

    updateCheckoutSummary() {
        const summary = document.getElementById('checkoutSummary');
        const deliverySelect = document.getElementById('deliveryAddress');
        
        if (!summary || !deliverySelect) return;

        const subtotal = this.cartItems.reduce((sum, item) => sum + (item.Prix * item.Quantite), 0);
        const selectedMunicipality = deliverySelect.value;
        
        if (!selectedMunicipality) {
            summary.innerHTML = `
                <div class="summary-row">
                    <span>Sous-total:</span>
                    <span>${subtotal.toFixed(2)}€</span>
                </div>
                <div class="summary-row">
                    <span>Livraison:</span>
                    <span>Sélectionnez une mairie</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total à payer:</span>
                    <span>${subtotal.toFixed(2)}€</span>
                </div>
            `;
            return;
        }

        const deliveryFee = this.getDeliveryFee(selectedMunicipality);
        const total = subtotal + deliveryFee;
        
        // Store the delivery fee for confirmation modal
        this.currentDeliveryFee = deliveryFee;

        summary.innerHTML = `
            <div class="summary-row">
                <span>Sous-total:</span>
                <span>${subtotal.toFixed(2)}€</span>
            </div>
            <div class="summary-row">
                <span>Livraison (${selectedMunicipality}):</span>
                <span>${deliveryFee.toFixed(2)}€</span>
            </div>
            <div class="summary-row summary-total">
                <span>Total à payer:</span>
                <span>${total.toFixed(2)}€</span>
            </div>
        `;
    }

    showCheckoutModal() {
        const modal = document.getElementById('checkoutModal');
        const summary = document.getElementById('checkoutSummary');
        const deliverySelect = document.getElementById('deliveryAddress');

        if (modal && summary) {
            const subtotal = this.cartItems.reduce((sum, item) => sum + (item.Prix * item.Quantite), 0);

            // Initial summary - no delivery fee shown until municipality is selected
            summary.innerHTML = `
                <div class="summary-row">
                    <span>Sous-total:</span>
                    <span>${subtotal.toFixed(2)}€</span>
                </div>
                <div class="summary-row">
                    <span>Livraison:</span>
                    <span>Sélectionnez une mairie</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total à payer:</span>
                    <span>${subtotal.toFixed(2)}€</span>
                </div>
            `;

            // Add event listener to update delivery fee when municipality is selected
            if (deliverySelect) {
                deliverySelect.onchange = () => this.updateCheckoutSummary();
            }

            modal.style.display = 'block';
        }
    }

    hideCheckoutModal() {
        const modal = document.getElementById('checkoutModal');
        if (modal) modal.style.display = 'none';
    }

async handleCheckout(e) {
        e.preventDefault();

        const deliveryAddress = document.getElementById('deliveryAddress').value;
        const deliveryNotes = document.getElementById('deliveryNotes').value;

        if (!deliveryAddress || !deliveryAddress.trim()) {
            this.showError('Veuillez sélectionner une mairie.');
            return;
        }

        if (this.cartItems.length === 0) {
            this.showError('Votre panier est vide.');
            return;
        }

        const subtotal = this.cartItems.reduce((sum, item) => sum + (item.Prix * item.Quantite), 0);
        
        // Use the delivery fee that was selected in the checkout modal
        const deliveryFee = this.currentDeliveryFee || 0;
        const total = subtotal + deliveryFee;

        const confirmMessage = `Confirmer la commande ?\n\nTotal: ${total.toFixed(2)}€\n\nAdresse: ${deliveryAddress}`;

        if (!confirm(confirmMessage)) {
            return;
        }

        const itemsToDelete = [...this.cartItems];

        try {
            const orderData = {
                delivery_address: deliveryAddress,
                delivery_notes: deliveryNotes || '',
                subtotal: subtotal,
                delivery_fee: deliveryFee,
                total: total,
                items: this.cartItems.map(item => ({
                    product_id: parseInt(item.ID_Produit),
                    quantity: parseInt(item.Quantite),
                    price: parseFloat(item.Prix)
                }))
            };

            console.log('Creating order with data:', orderData);
            
            const result = await api.createOrder(orderData);
            
            console.log('Order result:', result);

            if (!result || !result.success) {
                throw new Error(result?.error || 'Erreur lors de la création de la commande');
            }

            // Show confirmation modal FIRST (while cart data is still available)
            this.showOrderConfirmationModal(result, orderData);

            // Then clear the cart
            if (authManager.isAuthenticated()) {
                for (const item of itemsToDelete) {
                    try {
                        await api.removeFromCart(parseInt(item.ID_Produit));
                    } catch (e) {
                        console.error('Error removing item from cart:', e);
                    }
                }
            } else {
                localStorage.removeItem('localCart');
            }
            
            this.cartItems = [];
            
            const cartItemsEl = document.getElementById('cartItems');
            const cartSummaryEl = document.getElementById('cartSummary');
            const emptyCartEl = document.getElementById('emptyCart');
            
            if (cartItemsEl) cartItemsEl.innerHTML = '';
            if (cartSummaryEl) cartSummaryEl.innerHTML = '';
            if (emptyCartEl) emptyCartEl.style.display = 'block';
            
            this.updateCartCount();
        } catch (error) {
            console.error('Checkout error:', error);
            this.showError('Erreur lors de la création de la commande: ' + error.message);
        }
    }

    showOrderConfirmationModal(result, orderData) {
        this.hideCheckoutModal();

        const subtotal = orderData.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        // Use stored delivery fee (from checkout modal) to ensure consistency
        const deliveryFee = this.currentDeliveryFee || result.delivery_fee || 0;
        // Calculate total as subtotal + delivery fee
        const total = subtotal + deliveryFee;

        const modal = document.createElement('div');
        modal.id = 'orderConfirmationModal';
        modal.className = 'modal';
        modal.style.display = 'block';

        modal.innerHTML = `
            <div class="modal-content order-confirmation">
                <div class="confirmation-header">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h2>Commande confirmée !</h2>
                </div>
                
                <div class="confirmation-body modal-body">
                    <div class="order-number">
                        <span class="label">Numéro de commande:</span>
                        <span class="value">#${result.order_id}</span>
                    </div>
                    
                    <div class="order-summary">
                        <h3>Résumé de la commande</h3>
                        <div class="summary-items">
                            ${orderData.items.map(item => {
                                const product = this.cartItems.find(p => p.ID_Produit === item.product_id);
                                return `
                                    <div class="summary-item">
                                        <span>${product ? product.Nom : 'Produit #' + item.product_id}</span>
                                        <span>${item.quantity} x ${item.price.toFixed(2)}€</span>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                    
                    <div class="order-totals">
                        <div class="total-row">
                            <span>Sous-total:</span>
                            <span>${subtotal.toFixed(2)}€</span>
                        </div>
                        <div class="total-row">
                            <span>Frais de livraison:</span>
                            <span>${deliveryFee.toFixed(2)}€</span>
                        </div>
                        <div class="total-row">
                            <span>Total:</span>
                            <span>${total.toFixed(2)}€</span>
                        </div>
                    </div>
                </div>
                
                <div class="confirmation-footer">
<button class="btn-orders" id="goToOrdersBtn">
                        <i class="fas fa-list"></i> Voir mes commandes
                    </button>
<button class="btn-shopping" id="continueShoppingBtn">
                        <i class="fas fa-shopping-bag"></i> Continuer les achats
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const goToOrdersBtn = document.getElementById('goToOrdersBtn');
        const continueShoppingBtn = document.getElementById('continueShoppingBtn');
        
        if (goToOrdersBtn) {
            goToOrdersBtn.addEventListener('click', () => this.goToOrders());
        }
        if (continueShoppingBtn) {
            continueShoppingBtn.addEventListener('click', () => this.closeConfirmationModal());
        }

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeConfirmationModal();
            }
        });
    }

    goToOrders() {
        window.location.href = 'orders.html';
    }

    closeConfirmationModal() {
        const modal = document.getElementById('orderConfirmationModal');
        if (modal) {
            modal.remove();
        }
        window.location.href = 'orders.html';
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
}

let cartManager;

function initCartManager() {
    cartManager = new CartManager();
    
    window.cartManager = cartManager;
    window.CartManager = CartManager;
    
    window.updateQuantity = function(productId, newQuantity) {
        cartManager.updateQuantity(productId, newQuantity);
    };
    
    window.removeItem = function(productId) {
        cartManager.removeItem(productId);
    };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCartManager);
} else {
    initCartManager();
}
