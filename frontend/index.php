
<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['user_role'] ?? '';
$user_name = $_SESSION['user_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supermarket Delivery - Fresh Groceries Delivered</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <i class="fas fa-shopping-cart"></i>
                    <span>CastleMarket</span>
                </div>
                
                <div class="nav-search">
                    <form action="search.php" method="GET" role="search">
                        <input type="text" placeholder="Search products..." id="search-input" name="q" aria-label="Search products">
                        <button type="submit" id="search-btn" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <div class="nav-links">
                    <a href="client/products.html">Products</a>
                    <a href="categories.php">Categories</a>
                    <?php if ($is_logged_in): ?>
                        <span>Welcome, <?php echo htmlspecialchars($user_name); ?>!</span>
                        <a href="backend/auth/logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.html" class="auth-link" data-type="login">Login</a>
                        <a href="register.html" class="auth-link" data-type="register">Register</a>
                    <?php endif; ?>
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="cart-count">0</span>
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Rest of your HTML remains the same -->
    <section class="hero">
        <div class="hero-content">
            <h1>Fresh Groceries Delivered to Your Door</h1>
            <p>Order online and enjoy fast, reliable delivery. Quality products at great prices.</p>
            <div class="hero-buttons">
                <a href="#products" class="btn-primary">Shop Now</a>
                <a href="#categories" class="btn-secondary">Browse Categories</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="../fond.jpg" alt="Fresh groceries" style="width: 100%; height: auto; border-radius: 10px;">
        </div>
    </section>

    <section id="categories" class="categories">
        <h2>Shop by Category</h2>
        <div class="category-grid">
            <div class="category-card">
                <i class="fas fa-apple-alt"></i>
                <h3>Fruits & Vegetables</h3>
                <p>Fresh produce daily</p>
            </div>
            <div class="category-card">
                <i class="fas fa-bread-slice"></i>
                <h3>Bakery</h3>
                <p>Fresh baked goods</p>
            </div>
            <div class="category-card">
                <i class="fas fa-drumstick-bite"></i>
                <h3>Meat & Poultry</h3>
                <p>Premium quality meats</p>
            </div>
            <div class="category-card">
                <i class="fas fa-cheese"></i>
                <h3>Dairy</h3>
                <p>Milk, cheese, and more</p>
            </div>
            <div class="category-card">
                <i class="fas fa-utensils"></i>
                <h3>Pantry Staples</h3>
                <p>Essentials for your kitchen</p>
            </div>
            <div class="category-card">
                <i class="fas fa-ice-cream"></i>
                <h3>Frozen Foods</h3>
                <p>Frozen treats and meals</p>
            </div>
        </div>
    </section>

    <section id="products" class="featured-products">
        <h2>Featured Products</h2>
        <div class="product-grid" id="product-list">
            <!-- Products will be loaded here via JS -->
        </div>
        <div class="load-more">
            <button id="load-more-btn" class="btn-primary">Load More Products</button>
        </div>
    </section>

    <section class="features">
        <h2>Why Choose Us?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-truck"></i>
                <h3>Fast Delivery</h3>
                <p>Delivered within 1-2 hours</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-leaf"></i>
                <h3>Fresh Products</h3>
                <p>100% fresh and quality guaranteed</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-shield-alt"></i>
                <h3>Safe & Secure</h3>
                <p>Contactless delivery options</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-headset"></i>
                <h3>24/7 Support</h3>
                <p>Chat with our AI assistant anytime</p>
            </div>
        </div>
    </section>

    <section id="contact" class="contact">
        <h2>Contact Us</h2>
        <div class="contact-info">
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <p>support@Castlemarket.com</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-phone"></i>
                <p>+213 673893990</p>
            </div>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <p>chettia , chlef, algerie</p>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>CastleMarket</h3>
                <p>Your trusted online grocery store</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#products">Products</a></li>
                    <li><a href="#categories">Categories</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 CastleMarket Delivery. All rights reserved.</p>
        </div>
    </footer>

    <!-- Cart Sidebar -->
    <div id="cart-sidebar" class="cart-sidebar">
        <div class="cart-header">
            <h3>Your Cart</h3>
            <button id="close-cart"><i class="fas fa-times"></i></button>
        </div>
        <div id="cart-items">
            <!-- Cart items will be loaded here -->
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <strong>Total: $<span id="cart-total">0.00</span></strong>
            </div>
            <button id="checkout-btn" class="btn-primary">Checkout</button>
        </div>
    </div>

    <!-- Auth Modals -->
    <div id="auth-overlay" class="auth-overlay">
        <!-- Login Modal -->
        <div id="login-modal" class="auth-modal">
            <div class="auth-card">
                <span class="close-modal" data-modal="login-modal">&times;</span>
                <h2>Login</h2>
                <div id="login-error" class="error" style="display: none;"></div>
                <form id="login-form">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <button type="submit">Login</button>
                </form>
                <p>Don't have an account? <a href="#" id="show-register">Register</a></p>
            </div>
        </div>

        <!-- Register Modal -->
        <div id="register-modal" class="auth-modal">
            <div class="auth-card">
                <span class="close-modal" data-modal="register-modal">&times;</span>
                <h2>Register</h2>
                <div id="register-error" class="error" style="display: none;"></div>
                <form id="register-form">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <select name="role" required>
                        <option value="client">Client</option>
                        <option value="delivery_person">Delivery Person</option>
                    </select>
                    <input type="text" name="name" placeholder="Full Name" required>
                    <textarea name="address" placeholder="Address"></textarea>
                    <input type="tel" name="phone" placeholder="Phone">
                    <button type="submit">Register</button>
                </form>
                <p>Already have an account? <a href="#" id="show-login">Login</a></p>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP session data to JavaScript
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        const userRole = '<?php echo $user_role; ?>';
    </script>
    <script src="js/script.js"></script>
</body>
</html>