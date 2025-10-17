<?php
// pages/index.php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/database.php';
require_once 'includes/session.php';

// Initialize database connection
$database = new Database();
$pdo = $database->getConnection();

// Get featured products using the same function as product-detail.php
$featured_products = getFeaturedProducts(8);
$categories = getCategories();

// Get product ratings and review counts for featured products
if (!empty($featured_products)) {
    foreach ($featured_products as &$product) {
        $product['rating'] = getProductRating($pdo, $product['id']);
        $product['review_count'] = getReviewCount($pdo, $product['id']);
    }
    unset($product); // break the reference
}

$page_title = "HomewareOnTap - Premium Homeware & Decor";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section position-relative overflow-hidden">
    <div class="container">
        <div class="row align-items-center min-vh-80 py-5">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold text-dark mb-4">Transform Your Space Into a Home Quarter</h1>
                <p class="lead text-muted mb-4">Discover curated homeware that blends style, comfort, and functionality for your perfect living space.</p>
                <div class="hero-buttons">
                    <a href="pages/shop.php" class="btn btn-primary btn-lg me-3">Shop Collection</a>
                    <a href="#featured" class="btn btn-outline-dark btn-lg">Explore Products</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-container">
                    <img src="assets/img/banners/hero-homeware.jpg" alt="Modern Home Decor" class="img-fluid rounded-3 shadow-lg">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section id="featured" class="featured-products py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="section-title h1 mb-3">Featured Products</h2>
                <p class="section-subtitle text-muted">Handpicked items for your home transformation</p>
            </div>
        </div>
        <div class="row g-4 justify-content-center">
            <?php if (!empty($featured_products)): ?>
                <?php foreach($featured_products as $product): 
                    $days_old = (time() - strtotime($product['created_at'])) / (60 * 60 * 24);
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="product-card">
                        <div class="product-image position-relative">
                            <a href="pages/product-detail.php?id=<?php echo $product['id']; ?>">
                                <img src="assets/img/products/primary/<?php echo !empty($product['image']) ? htmlspecialchars($product['image']) : 'default-product.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     onerror="this.onerror=null; this.src='assets/img/products/primary/default-product.jpg'">
                            </a>
                            <?php if($days_old < 30): ?>
                            <span class="product-badge new">New</span>
                            <?php endif; ?>
                            <?php if($product['is_bestseller']): ?>
                            <span class="product-badge sale">Bestseller</span>
                            <?php endif; ?>
                            <?php if($product['stock_quantity'] == 0): ?>
                            <span class="product-badge out-of-stock">Out of Stock</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info-card">
                            <h3 class="product-title-card">
                                <a href="pages/product-detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            <div class="product-price-card">
                                R<?php echo number_format($product['price'], 2); ?>
                            </div>
                            <div class="product-rating mb-2">
                                <?php echo generateStarRating($product['rating']); ?>
                                <span class="ms-1">(<?php echo $product['review_count']; ?>)</span>
                            </div>
                            <p class="text-muted small mb-3">
                                <?php echo truncateText($product['description'] ?? 'Premium homeware product', 80); ?>
                            </p>
                            <div class="product-actions">
                                <button class="btn-add-cart" 
                                        data-product-id="<?php echo $product['id']; ?>" 
                                        data-stock="<?php echo $product['stock_quantity']; ?>"
                                        <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-shopping-cart me-2"></i> 
                                    <?php echo $product['stock_quantity'] == 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                </button>
                                <button class="btn-wishlist" data-product-id="<?php echo $product['id']; ?>">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No featured products available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="pages/shop.php" class="btn btn-primary btn-lg">View All Products</a>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="why-choose-us py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="section-title h1 mb-3">Why Choose HomewareOnTap</h2>
            </div>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px; background-color: #A67B5B;">
                        <i class="fas fa-shipping-fast fa-2x"></i>
                    </div>
                    <h5>Fast Delivery</h5>
                    <p class="text-muted">Quick and reliable shipping across South Africa with tracking updates</p>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px; background-color: #8B6145;">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <h5>Secure Payment</h5>
                    <p class="text-muted">Multiple secure payment options including PayFast and cash on delivery</p>
                </div>
            </div>
            <div class="col-md-4 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px; background-color: #A67B5B;">
                        <i class="fas fa-headset fa-2x"></i>
                    </div>
                    <h5>Dedicated Support</h5>
                    <p class="text-muted">24/7 customer support via WhatsApp and email for all your queries</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Floating Chat Button -->
<div class="floating-chat-container">
    <button class="floating-chat-btn btn btn-primary rounded-circle shadow-lg" id="chatToggle">
        <i class="fas fa-comment"></i>
    </button>
    <div class="chat-window" id="chatWindow">
        <div class="chat-header bg-primary text-white p-3">
            <h6 class="mb-0">Chat with Us</h6>
            <button class="btn-close btn-close-white" id="closeChat"></button>
        </div>
        <div class="chat-body p-3">
            <div class="chat-message bot-message">
                <p class="mb-1">Hi there! 👋 How can we help you today?</p>
                <small class="text-muted">Just now</small>
            </div>
        </div>
        <div class="chat-input p-3 border-top">
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Type your message..." id="chatInput">
                <button class="btn btn-primary" id="sendMessage">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="mt-2 text-center">
                <small class="text-muted">Or contact us directly:</small><br>
                <a href="https://wa.me/27682598679" class="btn btn-success btn-sm mt-1" target="_blank">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>

<style>
:root {
    --light-brown: #A67B5B;
    --dark-brown: #8B6145;
    --primary: #A67B5B;
    --primary-dark: #8B6145;
    --secondary: #F2E8D5;
    --light: #F9F5F0;
    --dark: #3A3229;
    --success: #28a745;
    --danger: #dc3545;
}

.min-vh-80 { min-height: 80vh; }
.hover-lift { transition: transform 0.2s ease-in-out; }
.hover-lift:hover { transform: translateY(-5px); }
.section-title { position: relative; }
.section-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: var(--light-brown);
}

.btn-primary {
    background-color: var(--light-brown);
    border-color: var(--light-brown);
}

.btn-primary:hover {
    background-color: var(--dark-brown);
    border-color: var(--dark-brown);
}

.text-primary {
    color: var(--light-brown) !important;
}

.btn-outline-primary {
    color: var(--light-brown);
    border-color: var(--light-brown);
}

.btn-outline-primary:hover {
    background-color: var(--light-brown);
    border-color: var(--light-brown);
    color: white;
}

.badge.bg-primary {
    background-color: var(--light-brown) !important;
}

/* Product Card Styles - Updated to match product-detail.php */
.product-card {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    height: 100%;
    background: white;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    margin: 0 auto;
    max-width: 320px;
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
}

.product-image {
    height: 240px;
    overflow: hidden;
    position: relative;
    background: var(--light);
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.1);
}

.product-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background-color: var(--primary);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    z-index: 2;
}

.product-badge.sale {
    background-color: var(--danger);
}

.product-badge.new {
    background-color: var(--success);
}

.product-badge.out-of-stock {
    background-color: #6c757d;
}

.product-info-card {
    padding: 24px;
}

.product-title-card {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 12px;
    height: 52px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    color: var(--dark);
}

.product-price-card {
    font-weight: 700;
    color: var(--primary);
    font-size: 1.25rem;
    margin-bottom: 12px;
}

.product-rating {
    display: flex;
    align-items: center;
}

.product-actions {
    display: flex;
    gap: 10px;
    margin-top: 16px;
}

.btn-add-cart {
    background-color: var(--primary);
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    flex: 1;
    transition: all 0.3s ease;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.btn-add-cart:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(166, 123, 91, 0.3);
}

.btn-add-cart:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.btn-add-cart.added {
    background-color: var(--success);
}

.btn-wishlist {
    width: 42px;
    height: 42px;
    display: flex;
    justify-content: center;
    align-items: center;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: white;
    color: #6c757d;
    transition: all 0.3s ease;
}

.btn-wishlist:hover, .btn-wishlist.active {
    color: #e74c3c;
    border-color: #e74c3c;
    background: #fff6f6;
    transform: scale(1.05);
}

/* Center alignment for featured products */
.featured-products .row.justify-content-center {
    display: flex;
    justify-content: center;
}

/* Toast positioning */
.toast-container {
    z-index: 1090;
}

/* Loading overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    display: none;
}

.spinner {
    width: 60px;
    height: 60px;
    border: 5px solid rgba(166, 123, 91, 0.2);
    border-radius: 50%;
    border-top-color: var(--primary);
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Floating Chat Styles */
.floating-chat-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1050;
}

.floating-chat-btn {
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
    background-color: var(--light-brown);
    border-color: var(--light-brown);
}

.floating-chat-btn:hover {
    background-color: var(--dark-brown);
    border-color: var(--dark-brown);
}

.chat-window {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 350px;
    height: 400px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    display: none;
    flex-direction: column;
}

.chat-window.show {
    display: flex;
}

.chat-body {
    flex: 1;
    overflow-y: auto;
}

.chat-message {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 10px;
    max-width: 80%;
}

.bot-message {
    background: #f8f9fa;
    margin-right: auto;
}

.user-message {
    background: var(--light-brown);
    color: white;
    margin-left: auto;
}

@media (max-width: 768px) {
    .chat-window {
        width: 300px;
        height: 350px;
    }
    
    .floating-chat-container {
        bottom: 10px;
        right: 10px;
    }
    
    .product-image {
        height: 200px;
    }
    
    .product-card {
        max-width: 100%;
    }
}

@media (max-width: 576px) {
    .product-card {
        max-width: 300px;
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Constants
    const CART_CONTROLLER_URL = '<?php echo SITE_URL; ?>/system/controllers/CartController.php';
    const WISHLIST_CONTROLLER_URL = '<?php echo SITE_URL; ?>/system/controllers/WishlistController.php';
    const IS_LOGGED_IN = <?php echo $sessionManager->isLoggedIn() ? 'true' : 'false'; ?>;

    $(document).ready(function() {
        // Initialize cart count
        updateCartCount();
        
        // Featured products - Add to cart and wishlist
        $('.featured-products .btn-add-cart').on('click', function() {
            const productId = $(this).data('product-id');
            const quantity = 1;
            addToCart(productId, quantity, this);
        });

        $('.featured-products .btn-wishlist').on('click', function() {
            const productId = $(this).data('product-id');
            toggleWishlist(productId, this);
        });

        // Floating chat functionality
        const chatToggle = document.getElementById('chatToggle');
        const chatWindow = document.getElementById('chatWindow');
        const closeChat = document.getElementById('closeChat');
        const chatInput = document.getElementById('chatInput');
        const sendMessage = document.getElementById('sendMessage');
        const chatBody = document.querySelector('.chat-body');

        // Toggle chat window
        chatToggle.addEventListener('click', function() {
            chatWindow.classList.toggle('show');
        });

        // Close chat window
        closeChat.addEventListener('click', function() {
            chatWindow.classList.remove('show');
        });

        // Send message
        function sendChatMessage() {
            const message = chatInput.value.trim();
            if (message) {
                // Add user message
                const userMessage = document.createElement('div');
                userMessage.className = 'chat-message user-message';
                userMessage.innerHTML = `
                    <p class="mb-1">${message}</p>
                    <small class="text-white-50">Just now</small>
                `;
                chatBody.appendChild(userMessage);
                
                // Clear input
                chatInput.value = '';
                
                // Scroll to bottom
                chatBody.scrollTop = chatBody.scrollHeight;
                
                // Simulate bot response after delay
                setTimeout(() => {
                    const botMessage = document.createElement('div');
                    botMessage.className = 'chat-message bot-message';
                    botMessage.innerHTML = `
                        <p class="mb-1">Thanks for your message! Our team will respond shortly. For immediate assistance, click the WhatsApp button below.</p>
                        <small class="text-muted">Just now</small>
                    `;
                    chatBody.appendChild(botMessage);
                    chatBody.scrollTop = chatBody.scrollHeight;
                }, 1000);
            }
        }

        sendMessage.addEventListener('click', sendChatMessage);
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendChatMessage();
            }
        });
    });
    
    // Show login required message
    function showLoginRequired() {
        showToast('Please login to use wishlist features', 'warning');
        setTimeout(() => {
            window.location.href = '<?php echo SITE_URL; ?>/pages/auth/login.php?redirect=index';
        }, 2000);
    }
    
    // Toggle wishlist function
    async function toggleWishlist(productId, element) {
        if (!IS_LOGGED_IN) {
            showLoginRequired();
            return;
        }

        const heartIcon = $(element).find('i');
        const originalClass = heartIcon.attr('class');
        
        // Show loading state
        heartIcon.removeClass('far fas').addClass('fas fa-spinner fa-spin');
        $(element).prop('disabled', true);
        
        try {
            const formData = new URLSearchParams({
                action: 'toggle_wishlist',
                product_id: productId
            });
            
            const response = await fetch(WISHLIST_CONTROLLER_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            });
            
            const responseText = await response.text();
            console.log('Wishlist response:', responseText);
            
            // Check for HTML response (PHP errors)
            if (responseText.trim().startsWith('<!') || responseText.trim().startsWith('<')) {
                throw new Error('Server returned HTML instead of JSON. Check for PHP errors.');
            }
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Wishlist JSON parse error:', parseError);
                throw new Error('Invalid JSON response from server');
            }
            
            if (result.success) {
                if (result.action === 'added') {
                    heartIcon.removeClass('fa-spinner fa-spin').addClass('fas fa-heart');
                    $(element).addClass('active');
                    showToast('Added to wishlist!', 'success');
                } else {
                    heartIcon.removeClass('fa-spinner fa-spin').addClass('far fa-heart');
                    $(element).removeClass('active');
                    showToast('Removed from wishlist', 'info');
                }
            } else {
                throw new Error(result.message || 'Failed to update wishlist');
            }
            
        } catch (error) {
            // Revert to original state on error
            heartIcon.attr('class', originalClass);
            console.error('Wishlist error:', error);
            showToast(error.message || 'Network error. Please try again.', 'error');
        } finally {
            $(element).prop('disabled', false);
        }
    }
    
    // Enhanced addToCart function
    async function addToCart(productId, quantity, element) {
        const originalText = element.innerHTML;
        
        // Show loading state
        element.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Adding...';
        element.disabled = true;
        $(element).removeClass('btn-primary').removeClass('btn-outline-primary');
        
        try {
            const formData = new URLSearchParams({
                action: 'add_to_cart',
                product_id: productId,
                quantity: quantity
            });
            
            const response = await fetch(CART_CONTROLLER_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            });
            
            const responseText = await response.text();
            console.log('Raw response:', responseText);
            
            // Check if response is HTML (starts with <) - indicates PHP error
            if (responseText.trim().startsWith('<!') || responseText.trim().startsWith('<')) {
                throw new Error('Server returned HTML instead of JSON. Check for PHP errors.');
            }
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response that failed to parse:', responseText.substring(0, 200));
                throw new Error('Invalid JSON response from server');
            }
            
            if (result.success) {
                // Success state
                element.innerHTML = '<i class="fas fa-check me-2"></i> Added!';
                $(element).addClass('btn-success');
                
                updateCartCount();
                showToast('Product added to cart!', 'success');
                
                // Revert after 2 seconds
                setTimeout(() => {
                    element.innerHTML = originalText;
                    $(element).removeClass('btn-success');
                    
                    // Restore original button class
                    $(element).addClass('btn-primary');
                    element.disabled = false;
                }, 2000);
                
            } else {
                throw new Error(result.message || 'Failed to add product to cart');
            }
            
        } catch (error) {
            // Restore original state and show error
            element.innerHTML = originalText;
            element.disabled = false;
            $(element).addClass('btn-primary');
            
            console.error('Add to cart error:', error);
            showToast(error.message || 'Network error. Please try again.', 'error');
        }
    }
    
    // ENHANCED updateCartCount function
    function updateCartCount() {
        console.log('🛒 Updating cart count...');
        
        $.ajax({
            url: CART_CONTROLLER_URL,
            type: 'POST',
            data: {
                action: 'get_cart_count'
            },
            success: function(response) {
                console.log('Cart count response:', response);
                
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        // UPDATE ALL CART COUNT ELEMENTS ON THE PAGE
                        $('.cart-count').text(result.cart_count); 
                        console.log('✅ Cart count updated to:', result.cart_count);
                    } else {
                        console.error('❌ Cart count error:', result.message);
                    }
                } catch (e) {
                    console.error('❌ JSON parse error:', e);
                    console.log('Raw response:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX error updating cart count:', error);
            }
        });
    }
    
    // Show toast notification using Bootstrap Toasts
    function showToast(message, type = 'success') {
        const toastId = 'toast-' + Date.now();
        const bgClass = type === 'success' ? 'text-bg-success' : 
                       type === 'error' ? 'text-bg-danger' : 
                       type === 'warning' ? 'text-bg-warning' : 'text-bg-info';
        
        const iconClass = type === 'success' ? 'fa-check-circle' : 
                         type === 'error' ? 'fa-exclamation-circle' : 
                         type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
        
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas ${iconClass} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        
        $('.toast-container').append(toastHtml);
        
        // Initialize and show the toast
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 4000
        });
        toast.show();
        
        // Remove toast from DOM after it's hidden
        toastElement.addEventListener('hidden.bs.toast', function () {
            $(this).remove();
        });
    }
</script>

<?php include 'includes/footer.php'; ?>