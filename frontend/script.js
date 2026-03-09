// Smooth Scroll Animations
const observerOptions = { threshold: 0.1 };
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('active');
        }
    });
}, observerOptions);

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// Navbar Scroll Effect
window.addEventListener('scroll', () => {
    const nav = document.getElementById('navbar');
    const logo = document.getElementById('nav-logo');
    const links = document.getElementById('nav-links');
    const cartIcon = document.getElementById('cart-icon');
    const searchInput = document.getElementById('search-input');
    const searchIcon = document.getElementById('search-icon');

    if (window.scrollY > 50) {
        nav.classList.add('glass-nav', 'py-3', 'shadow-sm');
        nav.classList.remove('py-5');
        logo.classList.replace('text-white', 'text-emerald-600');
        links.classList.replace('text-white', 'text-slate-600');
        const cartIconBg = document.getElementById('cart-icon-bg');
        if (cartIconBg) {
            cartIconBg.classList.replace('bg-white/10', 'bg-emerald-50');
            cartIconBg.classList.replace('text-white', 'text-emerald-600');
        }
        searchInput.classList.replace('bg-white/10', 'bg-slate-100');
        searchInput.classList.replace('text-white', 'text-slate-900');
        searchIcon.classList.replace('text-white/60', 'text-slate-400');
    } else {
        nav.classList.remove('glass-nav', 'py-3', 'shadow-sm');
        nav.classList.add('py-5');
        logo.classList.replace('text-emerald-600', 'text-white');
        links.classList.replace('text-slate-600', 'text-white');
        const cartIconBg = document.getElementById('cart-icon-bg');
        if (cartIconBg) {
            cartIconBg.classList.replace('bg-emerald-50', 'bg-white/10');
            cartIconBg.classList.replace('text-emerald-600', 'text-white');
        }
        searchInput.classList.replace('bg-slate-100', 'bg-white/10');
        searchInput.classList.replace('text-slate-900', 'text-white');
        searchIcon.classList.replace('text-slate-400', 'text-white/60');
    }
});

// Cart Logic
let cart = [];
window.toggleCart = function(open) {
    const sidebar = document.getElementById('cart-sidebar');
    const overlay = document.getElementById('cart-overlay');
    if (open) {
        sidebar.classList.add('open');
        overlay.classList.add('opacity-100', 'visible');
        overlay.classList.remove('opacity-0', 'invisible');
        renderCart();
    } else {
        sidebar.classList.remove('open');
        overlay.classList.add('opacity-0', 'invisible');
        overlay.classList.remove('opacity-100', 'visible');
    }
}

window.addToCart = function(name, price, image) {
    const existing = cart.find(item => item.name === name);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({ name, price, image, quantity: 1 });
    }
    updateCartCount();
}

function updateCartCount() {
    const count = cart.reduce((acc, item) => acc + item.quantity, 0);
    document.getElementById('cart-count').innerText = count;
}

function renderCart() {
    const container = document.getElementById('cart-items-container');
    const totalDisplay = document.getElementById('cart-total-display');
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="h-full flex flex-col items-center justify-center text-slate-400 space-y-4">
                <i class="fas fa-shopping-bag text-6xl opacity-20"></i>
                <p class="text-lg font-medium">Your cart is empty</p>
            </div>
        `;
        totalDisplay.innerText = '$0.00';
        return;
    }

    let total = 0;
    container.innerHTML = cart.map((item, idx) => {
        const priceVal = parseFloat(item.price.replace('$', ''));
        total += priceVal * item.quantity;
        return `
            <div class="flex gap-4 pb-6 border-b border-slate-50">
                <img src="${item.image}" class="w-20 h-20 rounded-xl object-cover">
                <div class="flex-grow">
                    <div class="flex justify-between items-start mb-1">
                        <h4 class="font-bold text-slate-900">${item.name}</h4>
                        <button onclick="removeFromCart(${idx})" class="text-slate-300 hover:text-red-500"><i class="fas fa-trash-alt"></i></button>
                    </div>
                    <p class="text-emerald-600 font-bold mb-3">${item.price}</p>
                    <div class="flex items-center gap-3">
                        <button onclick="updateQty(${idx}, -1)" class="w-8 h-8 rounded-lg border border-slate-100 flex items-center justify-center hover:bg-slate-50"><i class="fas fa-minus text-[10px]"></i></button>
                        <span class="font-bold text-sm">${item.quantity}</span>
                        <button onclick="updateQty(${idx}, 1)" class="w-8 h-8 rounded-lg border border-slate-100 flex items-center justify-center hover:bg-slate-50"><i class="fas fa-plus text-[10px]"></i></button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    totalDisplay.innerText = `$${total.toFixed(2)}`;
}

window.updateQty = function(idx, delta) {
    cart[idx].quantity += delta;
    if (cart[idx].quantity < 1) cart[idx].quantity = 1;
    renderCart();
    updateCartCount();
}

window.removeFromCart = function(idx) {
    cart.splice(idx, 1);
    renderCart();
    updateCartCount();
}

// Modal Logic
window.openModal = function(id) {
    const overlay = document.getElementById('modal-overlay');
    const modals = document.querySelectorAll('.modal-content');
    modals.forEach(m => m.classList.add('hidden'));
    document.getElementById(id).classList.remove('hidden');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
}

window.closeModal = function() {
    const overlay = document.getElementById('modal-overlay');
    overlay.classList.remove('open');
    document.body.style.overflow = 'auto';
}

window.closeModalOnOverlay = function(e) {
    if (e.target.id === 'modal-overlay') closeModal();
}

window.switchModal = function(id) {
    const modals = document.querySelectorAll('.modal-content');
    modals.forEach(m => m.classList.add('hidden'));
    document.getElementById(id).classList.remove('hidden');
}

// Initial Product Load
const products = [
    { name: "Organic Red Apples", price: "$4.99", category: "Fruits", image: "https://images.unsplash.com/photo-1560806887-1e4cd0b6bcd6?auto=format&fit=crop&w=400&q=80" },
    { name: "Fresh Sourdough", price: "$3.50", category: "Bakery", image: "https://images.unsplash.com/photo-1585478259715-876acc5be8eb?auto=format&fit=crop&w=400&q=80" },
    { name: "Premium Ribeye", price: "$18.99", category: "Meat", image: "https://images.unsplash.com/photo-1603048297172-c92544798d5e?auto=format&fit=crop&w=400&q=80" },
    { name: "Frozen Blueberries", price: "$6.25", category: "Frozen", image: "https://images.unsplash.com/photo-1498557850523-fd3d118b962e?auto=format&fit=crop&w=400&q=80" },
];

const grid = document.getElementById('product-grid');
if (grid) {
    grid.innerHTML = products.map((p, idx) => `
        <div class="reveal group bg-white rounded-[2.5rem] overflow-hidden border border-slate-100 hover:shadow-2xl hover:shadow-emerald-600/10 transition-all duration-500" style="transition-delay: ${idx * 0.1}s">
            <div class="relative h-64 overflow-hidden">
                <img src="${p.image}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                <div class="absolute top-4 left-4 px-3 py-1 rounded-full bg-white/90 backdrop-blur-sm text-[10px] font-bold uppercase tracking-widest text-emerald-600">
                    ${p.category}
                </div>
            </div>
            <div class="p-8 space-y-4">
                <h3 class="text-xl font-bold text-slate-900">${p.name}</h3>
                <div class="flex items-center justify-between">
                    <span class="text-2xl font-bold text-emerald-600">${p.price}</span>
                    <button onclick="addToCart('${p.name}', '${p.price}', '${p.image}')" class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all duration-300">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// ============================================
// Authentication Functions
// ============================================

// Check if user is logged in on page load
document.addEventListener('DOMContentLoaded', function() {
    checkAuthStatus();
});

function checkAuthStatus() {
    const token = localStorage.getItem('authToken');
    const user = localStorage.getItem('userData');
    
    if (token && user) {
        updateNavbarForLoggedIn(user);
    }
}

function updateNavbarForLoggedIn(user) {
    const navLinks = document.getElementById('nav-links');
    if (!navLinks) return;
    
    try {
        const userData = typeof user === 'string' ? JSON.parse(user) : user;
        
        navLinks.innerHTML = `
            <a href="#products" class="hover:text-emerald-200 transition-colors">Products</a>
            <a href="#categories" class="hover:text-emerald-200 transition-colors">Categories</a>
            <span class="text-emerald-200">Welcome, ${userData.name}</span>
            <button onclick="logout()" class="px-6 py-2.5 rounded-full bg-white text-emerald-600 hover:bg-emerald-50 transition-all shadow-xl shadow-emerald-900/10">Logout</button>
        `;
    } catch (e) {
        console.error('Error parsing user data:', e);
    }
}

async function handleLogin(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const errorDiv = document.getElementById('login-error');
    
    // Hide any previous error
    if (errorDiv) {
        errorDiv.classList.add('hidden');
    }
    
    // Get the submit button
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.textContent;
    submitBtn.textContent = 'Logging in...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('../backend/auth/login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Store token and user data
            localStorage.setItem('authToken', data.token);
            localStorage.setItem('userData', JSON.stringify(data.user));
            
            // Close modal
            closeModal();
            
            // Update navbar
            updateNavbarForLoggedIn(data.user);
            
            // Redirect based on role
            redirectBasedOnRole(data.user.role);
            
            // Reset form
            form.reset();
        } else {
            // Show error message
            if (errorDiv) {
                errorDiv.textContent = data.message || 'Login failed. Please try again.';
                errorDiv.classList.remove('hidden');
            }
        }
    } catch (error) {
        console.error('Login error:', error);
        if (errorDiv) {
            errorDiv.textContent = 'An error occurred. Please try again.';
            errorDiv.classList.remove('hidden');
        }
    } finally {
        submitBtn.textContent = originalBtnText;
        submitBtn.disabled = false;
    }
}

async function handleRegister(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const errorDiv = document.getElementById('register-error');
    
    // Hide any previous error
    if (errorDiv) {
        errorDiv.classList.add('hidden');
    }
    
    // Get the submit button
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.textContent;
    submitBtn.textContent = 'Registering...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('../backend/auth/register.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Store token and user data
            localStorage.setItem('authToken', data.token);
            localStorage.setItem('userData', JSON.stringify(data.user));
            
            // Close modal
            closeModal();
            
            // Update navbar
            updateNavbarForLoggedIn(data.user);
            
            // Redirect based on role
            redirectBasedOnRole(data.user.role);
            
            // Reset form
            form.reset();
        } else {
            // Show error message
            if (errorDiv) {
                errorDiv.textContent = data.message || 'Registration failed. Please try again.';
                errorDiv.classList.remove('hidden');
            }
        }
    } catch (error) {
        console.error('Registration error:', error);
        if (errorDiv) {
            errorDiv.textContent = 'An error occurred. Please try again.';
            errorDiv.classList.remove('hidden');
        }
    } finally {
        submitBtn.textContent = originalBtnText;
        submitBtn.disabled = false;
    }
}

function redirectBasedOnRole(role) {
    // Map backend role to frontend path
    const roleRedirects = {
        'Client': 'client/dashboard.html',
        'Livreur': 'livreur/dashboard.html',
        'Admin': 'admin/dashboard.html'
    };
    
    const redirectPath = roleRedirects[role];
    
    if (redirectPath) {
        // Small delay to show success message before redirect
        setTimeout(() => {
            window.location.href = redirectPath;
        }, 500);
    }
}

function logout() {
    // Call backend logout endpoint
    fetch('../backend/auth/logout.php', {
        method: 'POST'
    }).then(() => {
        // Clear localStorage
        localStorage.removeItem('authToken');
        localStorage.removeItem('userData');
        
        // Reset navbar to default
        const navLinks = document.getElementById('nav-links');
        if (navLinks) {
            navLinks.innerHTML = `
                <a href="#products" class="hover:text-emerald-200 transition-colors">Products</a>
                <a href="#categories" class="hover:text-emerald-200 transition-colors">Categories</a>
                <button onclick="openModal('login-modal')" class="hover:text-emerald-200 transition-colors">Login</button>
                <button onclick="openModal('register-modal')" class="px-6 py-2.5 rounded-full bg-white text-emerald-600 hover:bg-emerald-50 transition-all shadow-xl shadow-emerald-900/10">Register</button>
            `;
        }
        
        // Show logout message
        alert('You have been logged out successfully.');
    }).catch((error) => {
        console.error('Logout error:', error);
        // Still clear local storage even if backend call fails
        localStorage.removeItem('authToken');
        localStorage.removeItem('userData');
        
        const navLinks = document.getElementById('nav-links');
        if (navLinks) {
            navLinks.innerHTML = `
                <a href="#products" class="hover:text-emerald-200 transition-colors">Products</a>
                <a href="#categories" class="hover:text-emerald-200 transition-colors">Categories</a>
                <button onclick="openModal('login-modal')" class="hover:text-emerald-200 transition-colors">Login</button>
                <button onclick="openModal('register-modal')" class="px-6 py-2.5 rounded-full bg-white text-emerald-600 hover:bg-emerald-50 transition-all shadow-xl shadow-emerald-900/10">Register</button>
            `;
        }
    });
}
