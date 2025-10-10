<?php
// pages/index.php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get featured products
$featured_products = getFeaturedProducts(8);
$categories = getCategories();

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

<!-- Categories Section -->
<section class="categories-section py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="section-title h1 mb-3">Shop by Category</h2>
                <p class="section-subtitle text-muted">Find exactly what you need for every room</p>
            </div>
        </div>
        <div class="row g-4">
            <?php foreach(array_slice($categories, 0, 6) as $category): ?>
            <div class="col-md-4 col-lg-2">
                <a href="pages/shop.php?category=<?php echo $category['id']; ?>" class="category-card text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 text-center hover-lift">
                        <div class="card-body p-4">
                            <div class="category-icon mb-3">
                                <i class="fas fa-home fa-2x text-dark"></i>
                            </div>
                            <h6 class="card-title text-dark mb-0"><?php echo htmlspecialchars($category['name']); ?></h6>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
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
        <div class="row g-4">
            <?php if (!empty($featured_products)): ?>
                <?php foreach($featured_products as $product): ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card product-card h-100 border-0 shadow-sm">
                        <div class="position-relative">
                            <img src="assets/img/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 style="height: 250px; object-fit: cover;">
                            <?php if($product['is_new']): ?>
                            <span class="badge bg-primary position-absolute top-0 start-0 m-2">New</span>
                            <?php endif; ?>
                            <?php if($product['is_bestseller']): ?>
                            <span class="badge bg-warning position-absolute top-0 end-0 m-2">Bestseller</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted small flex-grow-1">
                                <?php echo truncateText($product['description'], 80); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="h5 text-primary mb-0"><?php echo format_price($product['price']); ?></span>
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-primary btn-sm">View Details</a>
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
        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center">
                    <div class="feature-icon bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-shipping-fast fa-2x"></i>
                    </div>
                    <h5>Fast Delivery</h5>
                    <p class="text-muted">Quick and reliable shipping across South Africa with tracking updates</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="feature-icon bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <h5>Secure Payment</h5>
                    <p class="text-muted">Multiple secure payment options including PayFast and cash on delivery</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="feature-icon bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 80px; height: 80px;">
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
    background: var(--bs-primary);
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
    background: var(--bs-primary);
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
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

   
    }
);
</script>

<?php include 'includes/footer.php'; ?>