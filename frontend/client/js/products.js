// Products Page Management
class ProductsManager {
    constructor() {
        this.allProducts = [];
        this.filteredProducts = [];
        this.currentPage = 1;
        this.productsPerPage = 500; // Show all products (up to 500)
        this.currentView = 'grid';
        this.filters = {
            search: '',
            categories: [],
            minPrice: 0,
            maxPrice: 50,
            minWeight: 0,
            maxWeight: 10,
            inStock: true,
            lowStock: false,
            sortBy: 'name_asc'
        };

        this.init();
    }

    init() {
        this.checkAuth();
        this.setupEventListeners();
        this.loadProducts();
        this.loadCategories();
        this.updateCartCount();
    }

    checkAuth() {
        // Allow viewing products without authentication
        if (authManager.isAuthenticated()) {
            this.currentUser = authManager.getCurrentUser();
            const userNameElement = document.getElementById('userName');
            if (userNameElement) {
                userNameElement.textContent = `Bonjour, ${this.currentUser.name}`;
            }
        } else {
            // Hide user-specific elements for non-authenticated users
            const userNameElement = document.getElementById('userName');
            const logoutBtnElement = document.getElementById('logoutBtn');
            if (userNameElement) userNameElement.style.display = 'none';
            if (logoutBtnElement) logoutBtnElement.style.display = 'none';

            // Show login/register buttons
            const navAuth = document.querySelector('.nav-auth');
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
        }
    }

    setupEventListeners() {
        // Logout
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) logoutBtn.addEventListener('click', () => authManager.handleLogout());

        // Search
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.addEventListener('input', (e) => {
            this.filters.search = e.target.value.toLowerCase();
            this.applyFilters();
        });

        // Price range
        const minPrice = document.getElementById('minPrice');
        if (minPrice) minPrice.addEventListener('input', (e) => {
            this.filters.minPrice = parseFloat(e.target.value) || 0;
            this.updatePriceSlider();
            this.applyFilters();
        });

        const maxPrice = document.getElementById('maxPrice');
        if (maxPrice) maxPrice.addEventListener('input', (e) => {
            this.filters.maxPrice = parseFloat(e.target.value) || 50;
            this.updatePriceSlider();
            this.applyFilters();
        });

        const priceSlider = document.getElementById('priceSlider');
        if (priceSlider) priceSlider.addEventListener('input', (e) => {
            this.filters.maxPrice = parseFloat(e.target.value);
            this.updatePriceInputs();
            this.applyFilters();
        });

        // Weight range
        const minWeight = document.getElementById('minWeight');
        if (minWeight) minWeight.addEventListener('input', (e) => {
            this.filters.minWeight = parseFloat(e.target.value) || 0;
            this.updateWeightSlider();
            this.applyFilters();
        });

        const maxWeight = document.getElementById('maxWeight');
        if (maxWeight) maxWeight.addEventListener('input', (e) => {
            this.filters.maxWeight = parseFloat(e.target.value) || 10;
            this.updateWeightSlider();
            this.applyFilters();
        });

        const weightSlider = document.getElementById('weightSlider');
        if (weightSlider) weightSlider.addEventListener('input', (e) => {
            this.filters.maxWeight = parseFloat(e.target.value);
            this.updateWeightInputs();
            this.applyFilters();
        });

        // Stock filters
        const inStock = document.getElementById('inStock');
        if (inStock) inStock.addEventListener('change', (e) => {
            this.filters.inStock = e.target.checked;
            this.applyFilters();
        });

        const lowStock = document.getElementById('lowStock');
        if (lowStock) lowStock.addEventListener('change', (e) => {
            this.filters.lowStock = e.target.checked;
            this.applyFilters();
        });

        // Sort
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) sortSelect.addEventListener('change', (e) => {
            this.filters.sortBy = e.target.value;
            this.applyFilters();
        });

        // Clear filters
        const clearFilters = document.getElementById('clearFilters');
        if (clearFilters) clearFilters.addEventListener('click', () => this.clearFilters());
        const resetFilters = document.getElementById('resetFilters');
        if (resetFilters) resetFilters.addEventListener('click', () => this.clearFilters());

        // View toggle
        const gridView = document.getElementById('gridView');
        if (gridView) gridView.addEventListener('click', () => this.setView('grid'));
        const listView = document.getElementById('listView');
        if (listView) listView.addEventListener('click', () => this.setView('list'));

        // Modal close
        const modal = document.getElementById('quickViewModal');
        if (modal) {
            const closeBtn = modal.querySelector('.close');
            if (closeBtn) closeBtn.addEventListener('click', () => this.hideQuickView());

            window.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hideQuickView();
                }
            });
        }

        // Cart toast
        const viewCartBtn = document.getElementById('viewCartBtn');
        if (viewCartBtn) viewCartBtn.addEventListener('click', () => {
            window.location.href = 'cart.html';
        });
    }

    async loadProducts() {
        const loading = document.getElementById('productsLoading');
        if (loading) loading.style.display = 'block';

        try {
            const response = await api.getProducts();
            this.allProducts = (response.products || []).map(product => ({
                ...product,
                Prix: parseFloat(product.Prix) || 0,
                Stock: parseInt(product.Stock) || 0,
                Poids: parseFloat(product.Poids) || 0
            }));

            // Calculate dynamic min/max values from actual data
            this.calculateDynamicRanges();

            this.applyFilters();
            this.loadCategories(); // Load categories after products are loaded
        } catch (error) {
            this.showError('Erreur lors du chargement des produits: ' + error.message);
        } finally {
            if (loading) loading.style.display = 'none';
        }
    }

    calculateDynamicRanges() {
        if (this.allProducts.length === 0) return;

        // Calculate price range
        const prices = this.allProducts.map(p => p.Prix).filter(p => p > 0);
        if (prices.length > 0) {
            this.filters.minPrice = Math.floor(Math.min(...prices));
            this.filters.maxPrice = Math.ceil(Math.max(...prices));
        }

        // Calculate weight range
        const weights = this.allProducts.map(p => p.Poids).filter(w => w > 0);
        if (weights.length > 0) {
            this.filters.minWeight = Math.floor(Math.min(...weights) * 10) / 10; // Round to 1 decimal
            this.filters.maxWeight = Math.ceil(Math.max(...weights) * 10) / 10; // Round to 1 decimal
        }

        // Update HTML elements with new ranges
        this.updateRangeElements();
    }

    updateRangeElements() {
        // Update price slider max value and display
        const priceSlider = document.getElementById('priceSlider');
        if (priceSlider) {
            priceSlider.max = this.filters.maxPrice;
            priceSlider.value = this.filters.maxPrice;
        }

        // Update weight slider max value and display
        const weightSlider = document.getElementById('weightSlider');
        if (weightSlider) {
            weightSlider.max = this.filters.maxWeight;
            weightSlider.value = this.filters.maxWeight;
        }

        // Update displays
        this.updatePriceDisplay();
        this.updateWeightDisplay();
    }

    async loadCategories() {
        try {
            // Extract unique categories from products
            const categories = [...new Set(this.allProducts.map(p => p.Categorie))].filter(Boolean);
            this.renderCategories(categories);
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    renderCategories(categories) {
        const categoryList = document.getElementById('categoryList');
        if (!categoryList) return;

        categories.forEach(category => {
            const categoryItem = document.createElement('label');
            categoryItem.className = 'checkbox-label';
            categoryItem.innerHTML = `
                <input type="checkbox" value="${category}" class="category-checkbox">
                <span class="checkmark"></span>
                ${category}
            `;

            categoryItem.querySelector('.category-checkbox').addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.filters.categories.push(category);
                } else {
                    this.filters.categories = this.filters.categories.filter(c => c !== category);
                }
                this.applyFilters();
            });

            categoryList.appendChild(categoryItem);
        });
    }

    applyFilters() {
        let filtered = [...this.allProducts];

        // Search filter
        if (this.filters.search) {
            filtered = filtered.filter(product =>
                product.Nom.toLowerCase().includes(this.filters.search) ||
                product.Description?.toLowerCase().includes(this.filters.search) ||
                product.Categorie?.toLowerCase().includes(this.filters.search)
            );
        }

        // Category filter
        if (this.filters.categories.length > 0) {
            filtered = filtered.filter(product =>
                this.filters.categories.includes(product.Categorie)
            );
        }

        // Price filter
        filtered = filtered.filter(product =>
            product.Prix >= this.filters.minPrice && product.Prix <= this.filters.maxPrice
        );

        // Weight filter
        filtered = filtered.filter(product =>
            product.Poids >= this.filters.minWeight && product.Poids <= this.filters.maxWeight
        );

        // Stock filter
        filtered = filtered.filter(product => {
            const isInStock = product.Stock > 5;
            const isLowStock = product.Stock > 0 && product.Stock <= 5;
            const isOutOfStock = product.Stock === 0;

            // If no stock filters are selected, show all products
            if (!this.filters.inStock && !this.filters.lowStock) return true;

            // If inStock is selected, include products with stock > 5
            if (this.filters.inStock && isInStock) return true;

            // If lowStock is selected, include products with stock 1-5
            if (this.filters.lowStock && isLowStock) return true;

            return false;
        });

        // Sort
        filtered.sort((a, b) => {
            switch (this.filters.sortBy) {
                case 'name_asc':
                    return a.Nom.localeCompare(b.Nom);
                case 'name_desc':
                    return b.Nom.localeCompare(a.Nom);
                case 'price_asc':
                    return a.Prix - b.Prix;
                case 'price_desc':
                    return b.Prix - a.Prix;
                case 'newest':
                    return new Date(b.Date_Ajout) - new Date(a.Date_Ajout);
                case 'stock_desc':
                    return b.Stock - a.Stock;
                default:
                    return 0;
            }
        });

        this.filteredProducts = filtered;
        this.currentPage = 1;
        this.renderProducts();
        this.updateResultsCount();
    }

    renderProducts() {
        const container = document.getElementById('productsContainer');
        const noResults = document.getElementById('noResults');
        const pagination = document.getElementById('pagination');

        if (this.filteredProducts.length === 0) {
            if (container) container.innerHTML = '';
            if (noResults) noResults.style.display = 'block';
            if (pagination) pagination.innerHTML = '';
            return;
        }

        if (noResults) noResults.style.display = 'none';

        // Calculate pagination
        const startIndex = (this.currentPage - 1) * this.productsPerPage;
        const endIndex = startIndex + this.productsPerPage;
        const paginatedProducts = this.filteredProducts.slice(startIndex, endIndex);

        if (container) {
            container.innerHTML = '';

            // Render all products in a simple grid (no category grouping)
            paginatedProducts.forEach(product => {
                const productCard = this.createProductCard(product);
                container.appendChild(productCard);
            });
        }

        // Show pagination
        this.renderPagination();
    }

    createProductCard(product) {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.dataset.productId = product.ID_Produit;

        const stockStatus = this.getStockStatus(product.Stock);
        const stockClass = this.getStockClass(product.Stock);

        card.innerHTML = `
            <div class="product-image">
                ${product.Image_URL ?
                    `<img src="${product.Image_URL}" alt="${product.Nom}" loading="lazy">` :
                    `<div class="placeholder-icon"><i class="fas fa-box"></i></div>`
                }
                <div class="product-badges">
                    ${stockStatus !== 'En stock' ? `<span class="badge ${stockClass}">${stockStatus}</span>` : ''}
                </div>
                <div class="product-actions">
                    <button class="action-btn quick-view-btn" title="Aperçu rapide" onclick="productsManager.showQuickView(${product.ID_Produit})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn add-to-cart-btn" title="Ajouter au panier" onclick="productsManager.addToCart(${product.ID_Produit})">
                        <i class="fas fa-cart-plus"></i>
                    </button>
                </div>
            </div>
            <div class="product-content">
                <div class="product-category">${product.Categorie || 'Général'}</div>
                <h3 class="product-title">${product.Nom}</h3>
                <p class="product-description">${product.Description || 'Aucune description disponible.'}</p>
                <div class="product-price">
                    <span class="price">${product.Prix.toFixed(2)}€</span>
                    ${product.Poids ? `<span class="weight">${product.Poids}kg</span>` : ''}
                </div>
                <div class="product-stock">
                    ${stockStatus}
                </div>
                <button class="btn-add-to-cart" onclick="productsManager.addToCart(${product.ID_Produit})" ${product.Stock === 0 ? 'disabled' : ''}>
                    <i class="fas fa-cart-plus"></i>
                    ${product.Stock === 0 ? 'Rupture de stock' : 'Ajouter au panier'}
                </button>
            </div>
        `;

        return card;
    }

    getStockStatus(stock) {
        if (stock === 0) return 'Rupture de stock';
        if (stock <= 5) return 'Stock faible';
        return 'En stock';
    }

    getStockClass(stock) {
        if (stock === 0) return 'out';
        if (stock <= 5) return 'low';
        return 'available';
    }

    renderPagination() {
        const pagination = document.getElementById('pagination');
        const totalPages = Math.ceil(this.filteredProducts.length / this.productsPerPage);

        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let paginationHTML = '<div class="pagination-controls">';

        // Previous button
        if (this.currentPage > 1) {
            paginationHTML += `<button class="page-btn" onclick="productsManager.goToPage(${this.currentPage - 1})">
                <i class="fas fa-chevron-left"></i> Précédent
            </button>`;
        }

        // Page numbers
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(totalPages, this.currentPage + 2);

        if (startPage > 1) {
            paginationHTML += `<button class="page-btn" onclick="productsManager.goToPage(1)">1</button>`;
            if (startPage > 2) {
                paginationHTML += '<span class="pagination-dots">...</span>';
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `<button class="page-btn ${i === this.currentPage ? 'active' : ''}" onclick="productsManager.goToPage(${i})">${i}</button>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += '<span class="pagination-dots">...</span>';
            }
            paginationHTML += `<button class="page-btn" onclick="productsManager.goToPage(${totalPages})">${totalPages}</button>`;
        }

        // Next button
        if (this.currentPage < totalPages) {
            paginationHTML += `<button class="page-btn" onclick="productsManager.goToPage(${this.currentPage + 1})">
                Suivant <i class="fas fa-chevron-right"></i>
            </button>`;
        }

        paginationHTML += '</div>';
        pagination.innerHTML = paginationHTML;
    }

    goToPage(page) {
        this.currentPage = page;
        this.renderProducts();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    setView(view) {
        this.currentView = view;
        const gridView = document.getElementById('gridView');
        const listView = document.getElementById('listView');
        if (gridView) gridView.classList.toggle('active', view === 'grid');
        if (listView) listView.classList.toggle('active', view === 'list');
        this.renderProducts();
    }

    updateResultsCount() {
        const count = this.filteredProducts.length;
        const resultsCount = document.getElementById('resultsCount');
        if (resultsCount) {
            resultsCount.textContent = count === 1 ? '1 produit trouvé' : `${count} produits trouvés`;
        }
    }

    updatePriceSlider() {
        const slider = document.getElementById('priceSlider');
        if (slider) slider.value = this.filters.maxPrice;
        this.updatePriceDisplay();
    }

    updatePriceInputs() {
        const maxPrice = document.getElementById('maxPrice');
        if (maxPrice) maxPrice.value = this.filters.maxPrice;
        this.updatePriceDisplay();
    }

    updatePriceDisplay() {
        const priceRangeDisplay = document.getElementById('priceRangeDisplay');
        if (priceRangeDisplay) {
            priceRangeDisplay.textContent = `${this.filters.minPrice}€ - ${this.filters.maxPrice}€`;
        }
    }

    updateWeightSlider() {
        const slider = document.getElementById('weightSlider');
        if (slider) slider.value = this.filters.maxWeight;
        this.updateWeightDisplay();
    }

    updateWeightInputs() {
        const maxWeight = document.getElementById('maxWeight');
        if (maxWeight) maxWeight.value = this.filters.maxWeight;
        this.updateWeightDisplay();
    }

    updateWeightDisplay() {
        const weightRangeDisplay = document.getElementById('weightRangeDisplay');
        if (weightRangeDisplay) {
            weightRangeDisplay.textContent = `${this.filters.minWeight}kg - ${this.filters.maxWeight}kg`;
        }
    }

    clearFilters() {
        // Recalculate dynamic ranges from current products
        this.calculateDynamicRanges();

        this.filters = {
            search: '',
            categories: [],
            minPrice: this.filters.minPrice,
            maxPrice: this.filters.maxPrice,
            minWeight: this.filters.minWeight,
            maxWeight: this.filters.maxWeight,
            inStock: true,
            lowStock: false,
            sortBy: 'name_asc'
        };

        // Reset form elements
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.value = '';
        const minPrice = document.getElementById('minPrice');
        if (minPrice) minPrice.value = '';
        const maxPrice = document.getElementById('maxPrice');
        if (maxPrice) maxPrice.value = '';
        const priceSlider = document.getElementById('priceSlider');
        if (priceSlider) priceSlider.value = this.filters.maxPrice;
        const minWeight = document.getElementById('minWeight');
        if (minWeight) minWeight.value = '';
        const maxWeight = document.getElementById('maxWeight');
        if (maxWeight) maxWeight.value = '';
        const weightSlider = document.getElementById('weightSlider');
        if (weightSlider) weightSlider.value = this.filters.maxWeight;
        const inStock = document.getElementById('inStock');
        if (inStock) inStock.checked = true;
        const lowStock = document.getElementById('lowStock');
        if (lowStock) lowStock.checked = false;
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) sortSelect.value = 'name_asc';

        // Reset category checkboxes
        document.querySelectorAll('.category-checkbox').forEach(cb => {
            cb.checked = false;
        });

        this.updatePriceDisplay();
        this.updateWeightDisplay();
        this.applyFilters();
    }

    async showQuickView(productId) {
        try {
            const response = await api.getProduct(productId);
            this.renderQuickView(response.product);
            const quickViewModal = document.getElementById('quickViewModal');
            if (quickViewModal) quickViewModal.style.display = 'block';
        } catch (error) {
            this.showError('Erreur lors du chargement du produit: ' + error.message);
        }
    }

    renderQuickView(product) {
        const content = document.getElementById('quickViewContent');
        if (!content) return;

        const stockStatus = this.getStockStatus(product.Stock);
        const stockClass = this.getStockClass(product.Stock);

        content.innerHTML = `
            <div class="quick-view-product">
                <div class="quick-view-image">
                    ${product.Image_URL ?
                        `<img src="${product.Image_URL}" alt="${product.Nom}">` :
                        `<div class="placeholder-icon large"><i class="fas fa-box"></i></div>`
                    }
                </div>
                <div class="quick-view-details">
                    <div class="product-category">${product.Categorie || 'Général'}</div>
                    <h2 class="product-title">${product.Nom}</h2>
                    <div class="product-price">
                        <span class="price">${product.Prix.toFixed(2)}€</span>
                        ${product.Poids ? `<span class="weight">${product.Poids}kg</span>` : ''}
                    </div>
                    <div class="product-stock ${stockClass}">
                        <i class="fas fa-${stockClass === 'available' ? 'check-circle' : stockClass === 'low' ? 'exclamation-triangle' : 'times-circle'}"></i>
                        ${stockStatus}
                    </div>
                    <div class="product-description">
                        <h3>Description</h3>
                        <p>${product.Description || 'Aucune description disponible.'}</p>
                    </div>
                    <div class="product-meta">
                        <div class="meta-item">
                            <strong>ID Produit:</strong> ${product.ID_Produit}
                        </div>
                        <div class="meta-item">
                            <strong>Ajouté le:</strong> ${this.formatDate(product.Date_Ajout)}
                        </div>
                        ${product.Statut === 'Disponible' ?
                            '<div class="meta-item available"><i class="fas fa-check"></i> Disponible à la vente</div>' :
                            '<div class="meta-item unavailable"><i class="fas fa-times"></i> Non disponible</div>'
                        }
                    </div>
                    <div class="quick-view-actions">
                        <button class="btn btn-primary btn-large" onclick="productsManager.addToCart(${product.ID_Produit})" ${product.Stock === 0 ? 'disabled' : ''}>
                            <i class="fas fa-cart-plus"></i>
                            ${product.Stock === 0 ? 'Rupture de stock' : 'Ajouter au panier'}
                        </button>
                        <button class="btn btn-outline" onclick="productsManager.hideQuickView()">
                            <i class="fas fa-times"></i> Fermer
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    hideQuickView() {
        const quickViewModal = document.getElementById('quickViewModal');
        if (quickViewModal) quickViewModal.style.display = 'none';
    }

    async addToCart(productId) {
        try {
            if (authManager.isAuthenticated()) {
                // Add to API cart for authenticated users
                await api.addToCart(productId, 1);
            } else {
                // Add to local cart for non-authenticated users
                this.addToLocalCart(productId);
            }
            this.showCartToast();
            this.updateCartCount();
        } catch (error) {
            this.showError('Erreur lors de l\'ajout au panier: ' + error.message);
        }
    }

    addToLocalCart(productId) {
        const localCart = JSON.parse(localStorage.getItem('localCart') || '[]');
        const existingItem = localCart.find(item => item.ID_Produit === productId);

        if (existingItem) {
            existingItem.Quantite += 1;
        } else {
            // Find the product details from allProducts
            const product = this.allProducts.find(p => p.ID_Produit === productId);
            if (product) {
                localCart.push({
                    ID_Produit: product.ID_Produit,
                    Nom: product.Nom,
                    Prix: product.Prix,
                    Image_URL: product.Image_URL,
                    Quantite: 1
                });
            }
        }

        localStorage.setItem('localCart', JSON.stringify(localCart));
    }

    showCartToast() {
        const toast = document.getElementById('cartToast');
        if (toast) {
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    }

    async updateCartCount() {
        try {
            let count = 0;
            if (authManager.isAuthenticated()) {
                // Get cart count from API for authenticated users
                const response = await api.getCart();
                count = response.items?.length || 0;
            } else {
                // Get cart count from localStorage for non-authenticated users
                const localCart = JSON.parse(localStorage.getItem('localCart') || '[]');
                count = localCart.length;
            }

            const cartCount = document.getElementById('cart-count');
            if (cartCount) cartCount.textContent = count;
        } catch (error) {
            console.error('Error updating cart count:', error);
            // Fallback to local cart count
            const localCart = JSON.parse(localStorage.getItem('localCart') || '[]');
            const cartCount = document.getElementById('cart-count');
            if (cartCount) cartCount.textContent = localCart.length;
        }
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
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

// Create global products manager instance
const productsManager = new ProductsManager();

// Export for use in other modules
window.ProductsManager = ProductsManager;
window.productsManager = productsManager;
