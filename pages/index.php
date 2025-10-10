<?php
// pages/index.php - Homepage with featured products and promotions

// Get the root directory path
$rootPath = dirname(__DIR__);

require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/functions.php';

// Get featured products and categories from database
$featured_products = getFeaturedProducts(8);
$categories = getCategories(null, 6);
// or simply:
// $categories = getCategories();
// then use array_slice if you only want 6 categories
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomewareOnTap - Premium Home Essentials</title>
    
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive.css">
    
    <style>
        /* Additional styles for enhanced homepage */
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('<?php echo SITE_URL; ?>/assets/img/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            color: white;
            text-align: center;
        }
        
        .category-card {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .category-img {
            height: 250px;
            overflow: hidden;
        }
        
        .category-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .category-card:hover .category-img img {
            transform: scale(1.1);
        }
        
        .category-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            padding: 20px;
            color: white;
        }
        
        .product-card {
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .product-img {
            height: 250px;
            overflow: hidden;
            position: relative;
        }
        
        .product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-img img {
            transform: scale(1.05);
        }
        
        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .product-wishlist {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 35px;
            height: 35px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .product-card:hover .product-wishlist {
            opacity: 1;
        }
        
        .product-wishlist:hover {
            background: #ff6b6b;
            color: white;
        }
        
        .product-content {
            padding: 15px;
        }
        
        .product-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .current-price {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .old-price {
            font-size: 14px;
            text-decoration: line-through;
            color: #95a5a6;
            margin-left: 8px;
        }
        
        .product-rating {
            color: #f39c12;
            margin: 8px 0;
        }
        
        .product-btn {
            background: #3A3229;
            color: white;
            border: none;
            padding: 10px 0;
            width: 100%;
            border-radius: 5px;
            transition: background 0.3s ease;
            font-weight: 600;
        }
        
        .product-btn:hover {
            background: #A67B5B;
        }
        
        .section-title {
            position: relative;
            margin-bottom: 40px;
            font-weight: 700;
            text-align: center;
        }
        
        .section-title:after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: #A67B5B;
            margin: 15px auto;
        }
        
        .newsletter-section {
            background: #f8f9fa;
            padding: 80px 0;
            text-align: center;
        }
        
        .newsletter-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .newsletter-form input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            font-size: 16px;
        }
        
        .newsletter-form button {
            background: #A67B5B;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        
        .newsletter-form button:hover {
            background: #8b5a3c;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-section {
                padding: 80px 0;
            }
            
            .category-img {
                height: 200px;
            }
            
            .newsletter-form {
                flex-direction: column;
            }
            
            .newsletter-form input {
                border-radius: 5px;
                margin-bottom: 10px;
            }
            
            .newsletter-form button {
                border-radius: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php require_once $rootPath . '/includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Elevate Your Home with Premium Essentials</h1>
            <p class="lead mb-4">Discover curated collections for every room in your house</p>
            <a href="shop.php" class="btn btn-primary btn-lg">Shop Now</a>
        </div>
    </section>
    
    <!-- Categories Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Shop by Category</h2>
            <div class="row">
                <?php foreach ($categories as $category): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="category-card">
                        <div class="category-img">
                            <img src="<?php echo SITE_URL; ?>/assets/img/categories/<?php echo $category['image'] ?? 'default-category.jpg'; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                        </div>
                        <div class="category-content">
                            <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                            <a href="shop.php?category=<?php echo $category['id']; ?>" class="btn btn-outline-light">Explore</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Featured Products -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">Featured Products</h2>
            
            <div class="row">
                <?php foreach ($featured_products as $product): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="product-card">
                        <div class="product-img">
                            <img src="<?php echo SITE_URL; ?>/assets/img/products/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <!-- Featured Products -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title">Featured Products</h2>
        
        <div class="row">
            <?php foreach ($featured_products as $product): ?>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="product-card">
                    <div class="product-img">
                        <img src="<?php echo SITE_URL; ?>/assets/img/products/<?php echo $product['image'] ?? 'default-product.jpg'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php if (isset($product['discount']) && $product['discount'] > 0): ?>
                        <span class="product-badge">Save <?php echo $product['discount']; ?>%</span>
                        <?php endif; ?>
                        <a href="#" class="product-wishlist" data-product-id="<?php echo $product['id']; ?>">
                            <i class="far fa-heart"></i>
                        </a>
                    </div>
                    <div class="product-content">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-price">
                            <span class="current-price">
                                R<?php 
                                $discount = $product['discount'] ?? 0;
                                $finalPrice = calculateDiscountPrice($product['price'], $discount);
                                echo number_format($finalPrice, 2); 
                                ?>
                            </span>
                            <?php if (isset($product['discount']) && $product['discount'] > 0): ?>
                            <span class="old-price">R<?php echo number_format($product['price'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-rating">
                            <?php echo generateStarRating($product['rating'] ?? 4.5); ?>
                            <span class="ms-1 text-muted small">(<?php echo $product['review_count'] ?? 0; ?>)</span>
                        </div>
                        <button class="product-btn add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="shop.php" class="btn btn-primary btn-lg">View All Products</a>
        </div>
    </div>
</section>
                                <span class="current-price">R<?php echo number_format(calculateDiscountPrice($product['price'], $product['discount']), 2); ?></span>
                                <?php if ($product['discount'] > 0): ?>
                                <span class="old-price">R<?php echo number_format($product['price'], 2); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-rating">
                                <?php echo generateStarRating($product['rating']); ?>
                                <span class="ms-1 text-muted small">(<?php echo $product['review_count'] ?? 0; ?>)</span>
                            </div>
                            <button class="product-btn add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="shop.php" class="btn btn-primary btn-lg">View All Products</a>
            </div>
        </div>
    </section>
    
    <!-- Promotion Banner -->
    <section class="py-5">
        <div class="container">
            <div class="promotion-banner rounded-3 overflow-hidden position-relative" style="background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('<?php echo SITE_URL; ?>/assets/img/promotion-banner.jpg'); background-size: cover; background-position: center; padding: 80px 40px; color: white; text-align: center;">
                <h2 class="fw-bold mb-3">Summer Sale</h2>
                <p class="fs-5 mb-4">Up to 30% off on selected items. Limited time offer!</p>
                <a href="shop.php?discount=1" class="btn btn-primary btn-lg">Shop Sale</a>
            </div>
        </div>
    </section>
    
    <!-- Newsletter -->
    <section class="newsletter-section">
        <div class="container text-center">
            <h2>Subscribe to Our Newsletter</h2>
            <p class="mb-4">Get updates on new products, special offers, and interior design tips.</p>
            
            <form class="newsletter-form" id="newsletterForm">
                <input type="email" name="email" placeholder="Your email address" required>
                <button type="submit">Subscribe</button>
            </form>
        </div>
    </section>
    
    <!-- Footer -->
    <?php require_once $rootPath . '/includes/footer.php'; ?>
    
    <!-- If modals don't exist, create basic ones -->
    <?php if (!file_exists($rootPath . '/includes/modals/login-modal.php')): ?>
    <!-- Basic Login Modal -->
    <div class="modal fade" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="authModalLabel">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="#" id="showRegister">Register</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <?php require_once $rootPath . '/includes/modals/login-modal.php'; ?>
    <?php endif; ?>
    
    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/cart.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Product card hover effect
            $('.product-card').hover(
                function() {
                    $(this).find('.product-btn').css('backgroundColor', '#A67B5B');
                },
                function() {
                    $(this).find('.product-btn').css('backgroundColor', '#3A3229');
                }
            );
            
            // Wishlist toggle
            $('.product-wishlist').on('click', function(e) {
                e.preventDefault();
                const productId = $(this).data('product-id');
                $(this).find('i').toggleClass('far fa-heart fas fa-heart');
                
                // AJAX call to add/remove from wishlist
                toggleWishlist(productId);
            });
            
            // Add to cart functionality
            $('.add-to-cart').on('click', function() {
                const productId = $(this).data('product-id');
                addToCart(productId, 1);
                
                // Visual feedback
                const $btn = $(this);
                const originalText = $btn.html();
                $btn.html('<i class="fas fa-check me-2"></i>Added');
                $btn.css('backgroundColor', '#28a745');
                
                setTimeout(function() {
                    $btn.html(originalText);
                    $btn.css('backgroundColor', '#3A3229');
                }, 1500);
            });
            
            // Newsletter form submission
            $('#newsletterForm').on('submit', function(e) {
                e.preventDefault();
                const email = $(this).find('input[name="email"]').val();
                
                // AJAX call to subscribe
                subscribeNewsletter(email);
                
                // Visual feedback
                $(this).find('button').html('Subscribed!');
                $(this).find('button').css('backgroundColor', '#28a745');
                
                setTimeout(function() {
                    $('#newsletterForm').find('button').html('Subscribe');
                    $('#newsletterForm').find('button').css('backgroundColor', '#A67B5B');
                    $('#newsletterForm')[0].reset();
                }, 2000);
            });
        });
    </script>
</body>
</html>