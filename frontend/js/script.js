// Auth Modal Management
document.addEventListener('DOMContentLoaded', function() {
    // Get elements - support both old (id) and new (class) selectors
    const loginBtn = document.getElementById('login-btn') || document.querySelector('.auth-link[data-type="login"]');
    const registerBtn = document.getElementById('register-btn') || document.querySelector('.auth-link[data-type="register"]');
    const authOverlay = document.getElementById('auth-overlay');
    const loginModal = document.getElementById('login-modal');
    const registerModal = document.getElementById('register-modal');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const showRegisterLink = document.getElementById('show-register');
    const showLoginLink = document.getElementById('show-login');

    // Show login modal
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            // If modal elements exist, show modal; otherwise navigate to login page
            if (authOverlay && loginModal) {
                e.preventDefault();
                authOverlay.style.display = 'flex';
                loginModal.style.display = 'block';
                registerModal.style.display = 'none';
            }
            // If no modal, let default href behavior navigate to login.html
        });
    }

    // Show register modal
    if (registerBtn) {
        registerBtn.addEventListener('click', function(e) {
            // If modal elements exist, show modal; otherwise navigate to register page
            if (authOverlay && registerModal) {
                e.preventDefault();
                authOverlay.style.display = 'flex';
                registerModal.style.display = 'block';
                loginModal.style.display = 'none';
            }
            // If no modal, let default href behavior navigate to register.html
        });
    }

    // Close modals
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            authOverlay.style.display = 'none';
            loginModal.style.display = 'none';
            registerModal.style.display = 'none';
        });
    });

    // Switch to register modal
    if (showRegisterLink) {
        showRegisterLink.addEventListener('click', function(e) {
            e.preventDefault();
            loginModal.style.display = 'none';
            registerModal.style.display = 'block';
        });
    }

    // Switch to login modal
    if (showLoginLink) {
        showLoginLink.addEventListener('click', function(e) {
            e.preventDefault();
            registerModal.style.display = 'none';
            loginModal.style.display = 'block';
        });
    }

    // Close modal when clicking overlay
    if (authOverlay) {
        authOverlay.addEventListener('click', function(e) {
            if (e.target === authOverlay) {
                authOverlay.style.display = 'none';
                loginModal.style.display = 'none';
                registerModal.style.display = 'none';
            }
        });
    }

    // Login form submission
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('backend/auth/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect based on user role
                    if (data.user && data.user.role) {
                        switch (data.user.role.toLowerCase()) {
                            case 'admin':
                                window.location.href = 'admin/dashboard.html';
                                break;
                            case 'livreur':
                                window.location.href = 'livreur/dashboard.html';
                                break;
                            default:
                                window.location.href = 'client/products.html';
                        }
                    } else {
                        location.reload();
                    }
                } else {
                    const errorElement = document.getElementById('login-error');
                    if (errorElement) {
                        errorElement.textContent = data.message;
                        errorElement.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorElement = document.getElementById('login-error');
                if (errorElement) {
                    errorElement.textContent = 'An error occurred. Please try again.';
                    errorElement.style.display = 'block';
                }
            });
        });
    }

    // Register form submission
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('backend/auth/register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    const errorElement = document.getElementById('register-error');
                    if (errorElement) {
                        errorElement.textContent = data.message;
                        errorElement.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorElement = document.getElementById('register-error');
                if (errorElement) {
                    errorElement.textContent = 'An error occurred. Please try again.';
                    errorElement.style.display = 'block';
                }
            });
        });
    }

    // Cart functionality
    const cartSidebar = document.getElementById('cart-sidebar');
    const closeCartBtn = document.getElementById('close-cart');
    const cartIcon = document.querySelector('.cart-icon');

    if (cartIcon && cartSidebar) {
        cartIcon.addEventListener('click', function(e) {
            e.preventDefault();
            cartSidebar.classList.add('open');
        });
    }

    if (closeCartBtn && cartSidebar) {
        closeCartBtn.addEventListener('click', function() {
            cartSidebar.classList.remove('open');
        });
    }

    // Load products
    loadProducts();

    // Load more products button
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', loadMoreProducts);
    }
});

// Product loading functions
let products = [];
let displayedProducts = 0;
const productsPerPage = 8;

function loadProducts() {
    fetch('backend/products/get.php')
        .then(response => response.json())
        .then(data => {
            products = data.products || [];
            displayProducts();
        })
        .catch(error => console.error('Error loading products:', error));
}

function displayProducts() {
    const productList = document.getElementById('product-list');
    if (!productList) return;

    const productsToShow = products.slice(displayedProducts, displayedProducts + productsPerPage);
    
    productsToShow.forEach(product => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card';
        productCard.innerHTML = `
            <img src="${product.image || 'https://via.placeholder.com/300x200'}" alt="${product.name}">
            <div class="product-info">
                <h3>${product.name}</h3>
                <p>${product.description || ''}</p>
                <div class="product-price">$${product.price}</div>
                <button class="add-to-cart" onclick="addToCart(${product.id})">Add to Cart</button>
            </div>
        `;
        productList.appendChild(productCard);
    });

    displayedProducts += productsToShow.length;

    // Hide load more button if all products are shown
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn && displayedProducts >= products.length) {
        loadMoreBtn.style.display = 'none';
    }
}

function loadMoreProducts() {
    displayProducts();
}

function addToCart(productId) {
    fetch('backend/cart/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount();
            // Open cart sidebar
            const cartSidebar = document.getElementById('cart-sidebar');
            if (cartSidebar) {
                cartSidebar.classList.add('open');
            }
        } else {
            alert(data.message || 'Error adding to cart');
        }
    })
    .catch(error => console.error('Error adding to cart:', error));
}

function updateCartCount() {
    fetch('backend/cart/get.php')
        .then(response => response.json())
        .then(data => {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                cartCount.textContent = data.total_items || 0;
            }
        })
        .catch(error => console.error('Error getting cart count:', error));
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', updateCartCount);
