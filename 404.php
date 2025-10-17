[file name]: 404.php
[file content begin]
<?php
// 404.php - Custom 404 Error Page
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set HTTP response code to 404
http_response_code(404);

$page_title = "Page Not Found - HomewareOnTap";
$page_description = "Sorry, the page you're looking for doesn't exist. Browse our homeware collection instead.";

// Log the 404 error for analytics
if (function_exists('log404Error')) {
    $requested_url = $_SERVER['REQUEST_URI'] ?? 'Unknown';
    $referrer = $_SERVER['HTTP_REFERER'] ?? 'Direct';
    log404Error($requested_url, $referrer);
}

include 'includes/header.php';
?>

<!-- Page Header Start -->
<div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
    <div class="container py-5">
        <h1 class="display-1 text-white animated slideInDown">404 Error</h1>
        <nav aria-label="breadcrumb animated slideInDown">
            <ol class="breadcrumb text-uppercase mb-0">
                <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a class="text-white" href="pages/shop.php">Pages</a></li>
                <li class="breadcrumb-item text-primary active" aria-current="page">404 Error</li>
            </ol>
        </nav>
    </div>
</div>
<!-- Page Header End -->

<!-- 404 Start -->
<div class="container-xxl py-5 wow fadeInUp" data-wow-delay="0.1s">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="error-icon mb-4">
                    <i class="bi bi-exclamation-triangle display-1 text-primary"></i>
                    <h1 class="display-1 text-primary fw-bold">404</h1>
                </div>
                
                <h1 class="mb-4">Page Not Found</h1>
                
                <p class="lead mb-4">
                    We're sorry, but the page you're looking for doesn't exist or has been moved. 
                    This could be due to an outdated link, a typing error, or the page being removed.
                </p>

                <!-- Suggested Actions -->
                <div class="suggestions mb-5">
                    <h5 class="mb-3">Here are some helpful links instead:</h5>
                    <div class="row g-3 justify-content-center">
                        <div class="col-md-3 col-6">
                            <a href="index.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-home me-2"></i>Homepage
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="pages/shop.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-shopping-bag me-2"></i>Shop
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="pages/static/contact.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-envelope me-2"></i>Contact
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="pages/static/faqs.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-question-circle me-2"></i>FAQs
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Search Form -->
                <div class="search-suggest mb-5">
                    <h5 class="mb-3">Or try searching for what you need:</h5>
                    <form action="pages/shop.php" method="GET" class="row g-2 justify-content-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control form-control-lg" 
                                       placeholder="Search products..." aria-label="Search products">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Popular Products Section -->
                <div class="popular-products mt-5">
                    <h4 class="mb-4">Popular Products You Might Like</h4>
                    <div class="row g-4 justify-content-center">
                        <?php
                        // Get popular products to suggest
                        $popular_products = getBestsellerProducts(3);
                        
                        if (!empty($popular_products)):
                            foreach($popular_products as $product):
                        ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card product-card h-100 border-0 shadow-sm">
                                <div class="position-relative">
                                    <img src="assets/img/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         style="height: 200px; object-fit: cover;">
                                    <?php if($product['is_bestseller']): ?>
                                    <span class="badge bg-warning position-absolute top-0 end-0 m-2">Bestseller</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body text-center">
                                    <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                    <p class="card-text text-primary fw-bold mb-2"><?php echo format_price($product['price']); ?></p>
                                    <a href="pages/product-detail.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-primary btn-sm">View Product</a>
                                </div>
                            </div>
                        </div>
                        <?php 
                            endforeach;
                        else:
                            // Fallback if no products found
                        ?>
                        <div class="col-12 text-center">
                            <p class="text-muted">Check out our <a href="pages/shop.php">shop page</a> to browse all products.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Action Button -->
                <div class="mt-5">
                    <a href="index.php" class="btn btn-primary btn-lg px-5 py-3">
                        <i class="fas fa-arrow-left me-2"></i>Back to Homepage
                    </a>
                </div>

                <!-- Support Information -->
                <div class="support-info mt-4 p-4 bg-light rounded">
                    <h6 class="mb-3">Need Help?</h6>
                    <p class="mb-2 small">
                        If you believe this is an error or need assistance, please 
                        <a href="pages/static/contact.php" class="text-primary">contact our support team</a>.
                    </p>
                    <div class="contact-options">
                        <a href="https://wa.me/27682598679" class="btn btn-success btn-sm me-2" target="_blank">
                            <i class="fab fa-whatsapp me-1"></i>WhatsApp Support
                        </a>
                        <a href="mailto:info@homewareontap.co.za" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-envelope me-1"></i>Email Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- 404 End -->

<style>
.error-icon {
    position: relative;
    margin-bottom: 2rem;
}

.error-icon .display-1 {
    font-size: 8rem;
    font-weight: 700;
}

.error-icon .bi-exclamation-triangle {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 4rem;
    opacity: 0.3;
}

.page-header {
    background: linear-gradient(135deg, var(--bs-primary) 0%, #2c3e50 100%);
}

.suggestions .btn {
    transition: all 0.3s ease;
}

.suggestions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.support-info {
    border-left: 4px solid var(--bs-primary);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .error-icon .display-1 {
        font-size: 5rem;
    }
    
    .error-icon .bi-exclamation-triangle {
        font-size: 2.5rem;
    }
    
    .suggestions .col-md-3 {
        margin-bottom: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add animation to error elements
    const errorElements = document.querySelectorAll('.error-icon, h1, .lead, .suggestions, .search-suggest');
    
    errorElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            element.style.transition = 'all 0.6s ease';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 200);
    });

    // Auto-focus on search input
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        setTimeout(() => {
            searchInput.focus();
        }, 1000);
    }

    // Track 404 errors for analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', '404_error', {
            'event_category': 'error',
            'event_label': window.location.href,
            'value': 1
        });
    }
});
</script>

<?php 
// Add the log404Error function if it doesn't exist in functions.php
if (!function_exists('log404Error')) {
    /**
     * Log 404 errors for analytics and debugging
     */
    function log404Error($requested_url, $referrer) {
        $log_file = __DIR__ . '/logs/404_errors.log';
        $log_dir = dirname($log_file);
        
        // Create logs directory if it doesn't exist
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $log_entry = "[$timestamp] 404 Error:\n";
        $log_entry .= "URL: $requested_url\n";
        $log_entry .= "Referrer: $referrer\n";
        $log_entry .= "IP: $ip_address\n";
        $log_entry .= "User Agent: $user_agent\n";
        $log_entry .= "----------------------------------------\n";
        
        // Log to file
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Also log to PHP error log for easy monitoring
        error_log("404 Error: $requested_url (From: $referrer)");
    }
}

include 'includes/footer.php'; 
?>
[file content end]