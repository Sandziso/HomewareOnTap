<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomewareOnTap - Core JavaScript Demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #B78D65;
            --secondary: #6c757d;
            --light: #F8F8F8;
            --dark: #252525;
            --body-color: #777;
        }
        
        body {
            font-family: 'Open Sans', sans-serif;
            color: var(--body-color);
            background-color: #fff;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Teko', sans-serif;
            font-weight: 500;
            color: var(--dark);
        }
        
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-family: 'Teko', sans-serif;
            font-size: 28px;
            font-weight: 600;
        }
        
        .nav-link {
            font-weight: 500;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #927151;
            border-color: #927151;
        }
        
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1758&q=80');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            color: white;
        }
        
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .product-img {
            height: 200px;
            object-fit: cover;
        }
        
        .price {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .category-filter {
            margin-bottom: 30px;
        }
        
        .filter-btn {
            margin: 5px;
        }
        
        .mobile-menu {
            position: fixed;
            top: 0;
            left: -300px;
            width: 300px;
            height: 100%;
            background: white;
            z-index: 1000;
            padding: 20px;
            transition: left 0.3s;
            box-shadow: 5px 0 15px rgba(0,0,0,0.1);
        }
        
        .mobile-menu.open {
            left: 0;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
        
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            background: var(--dark);
            color: white;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transform: translateY(100px);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
            z-index: 1000;
        }
        
        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .search-box {
            position: absolute;
            top: 100%;
            right: 0;
            width: 300px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 15px;
            border-radius: 5px;
            display: none;
            z-index: 100;
        }
        
        .search-box.active {
            display: block;
        }
        
        .cart-preview {
            position: absolute;
            top: 100%;
            right: 0;
            width: 320px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
            border-radius: 5px;
            display: none;
            z-index: 100;
        }
        
        .cart-preview.active {
            display: block;
        }
        
        .cart-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s, transform 0.3s;
            z-index: 99;
        }
        
        .to-top.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Header & Navigation -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="#">Homeware<span class="text-primary">OnTap</span></a>
                
                <div class="d-flex align-items-center order-lg-3">
                    <a href="#" class="btn btn-link position-relative me-3" id="searchToggle">
                        <i class="fas fa-search"></i>
                    </a>
                    <a href="#" class="btn btn-link position-relative me-3" id="cartToggle">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">3</span>
                    </a>
                    <button class="navbar-toggler" type="button" id="mobileMenuToggle">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
                
                <div class="collapse navbar-collapse order-lg-2" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Shop</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Categories</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Contact</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Search Box -->
        <div class="search-box" id="searchBox">
            <form>
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search products...">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Cart Preview -->
        <div class="cart-preview" id="cartPreview">
            <h5>Your Cart (3)</h5>
            <div class="cart-items">
                <div class="cart-item">
                    <img src="https://images.unsplash.com/photo-1555041469-a586c61ea9bc?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" alt="Product" class="cart-item-img">
                    <div>
                        <h6>Modern Sofa</h6>
                        <p>1 x $599.99</p>
                    </div>
                </div>
                <div class="cart-item">
                    <img src="https://images.unsplash.com/photo-1503602642458-232111445657?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=387&q=80" alt="Product" class="cart-item-img">
                    <div>
                        <h6>Wooden Table</h6>
                        <p>1 x $299.99</p>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <h5>Total: $899.98</h5>
                <a href="#" class="btn btn-primary">Checkout</a>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5>Menu</h5>
            <button class="btn btn-link" id="closeMobileMenu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="list-unstyled">
            <li class="mb-2"><a href="#" class="text-dark">Home</a></li>
            <li class="mb-2"><a href="#" class="text-dark">Shop</a></li>
            <li class="mb-2"><a href="#" class="text-dark">Categories</a></li>
            <li class="mb-2"><a href="#" class="text-dark">About Us</a></li>
            <li class="mb-2"><a href="#" class="text-dark">Contact</a></li>
            <li class="mb-2"><a href="#" class="text-dark">My Account</a></li>
        </ul>
    </div>
    
    <div class="overlay" id="overlay"></div>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-4">Transform Your Home with Style</h1>
            <p class="lead mb-4">Discover the perfect pieces to create your dream living space</p>
            <a href="#" class="btn btn-primary btn-lg">Shop Now</a>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Featured Products</h2>
                <p class="lead">Browse our most popular homeware items</p>
            </div>
            
            <div class="category-filter text-center">
                <button class="btn btn-outline-primary filter-btn active" data-filter="all">All</button>
                <button class="btn btn-outline-primary filter-btn" data-filter="furniture">Furniture</button>
                <button class="btn btn-outline-primary filter-btn" data-filter="decor">Decor</button>
                <button class="btn btn-outline-primary filter-btn" data-filter="lighting">Lighting</button>
            </div>
            
            <div class="row" id="productGrid">
                <div class="col-md-4" data-category="furniture">
                    <div class="card product-card">
                        <img src="https://images.unsplash.com/photo-1555041469-a586c61ea9bc?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" class="card-img-top product-img" alt="Modern Sofa">
                        <div class="card-body">
                            <h5 class="card-title">Modern Sofa</h5>
                            <p class="card-text">Elegant and comfortable sofa for your living room</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price">$599.99</span>
                                <button class="btn btn-primary add-to-cart" data-product="Modern Sofa" data-price="599.99">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-category="decor">
                    <div class="card product-card">
                        <img src="https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=387&q=80" class="card-img-top product-img" alt="Decorative Vase">
                        <div class="card-body">
                            <h5 class="card-title">Decorative Vase</h5>
                            <p class="card-text">Beautiful ceramic vase to enhance your home decor</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price">$49.99</span>
                                <button class="btn btn-primary add-to-cart" data-product="Decorative Vase" data-price="49.99">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-category="lighting">
                    <div class="card product-card">
                        <img src="https://images.unsplash.com/photo-1507473885765-e6ed057f782c?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1170&q=80" class="card-img-top product-img" alt="Modern Lamp">
                        <div class="card-body">
                            <h5 class="card-title">Modern Lamp</h5>
                            <p class="card-text">Elegant lamp to illuminate your space with style</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price">$89.99</span>
                                <button class="btn btn-primary add-to-cart" data-product="Modern Lamp" data-price="89.99">Add to Cart</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center">
                    <h3>Subscribe to Our Newsletter</h3>
                    <p>Get updates on new products and special promotions</p>
                    <form id="newsletterForm" class="mt-4">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Your email address" required>
                            <button class="btn btn-primary" type="submit">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Notification -->
    <div class="notification" id="notification">
        Product added to cart successfully!
    </div>

    <!-- Back to Top Button -->
    <a href="#" class="to-top" id="toTop">
        <i class="fas fa-chevron-up"></i>
    </a>

    <script>
        // Main JavaScript functionality for HomewareOnTap
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile Menu Toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const mobileMenu = document.getElementById('mobileMenu');
            const closeMobileMenu = document.getElementById('closeMobileMenu');
            const overlay = document.getElementById('overlay');
            
            function openMobileMenu() {
                mobileMenu.classList.add('open');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
            
            function closeMobileMenuHandler() {
                mobileMenu.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
            
            mobileMenuToggle.addEventListener('click', openMobileMenu);
            closeMobileMenu.addEventListener('click', closeMobileMenuHandler);
            overlay.addEventListener('click', closeMobileMenuHandler);
            
            // Search Toggle
            const searchToggle = document.getElementById('searchToggle');
            const searchBox = document.getElementById('searchBox');
            
            searchToggle.addEventListener('click', function(e) {
                e.preventDefault();
                searchBox.classList.toggle('active');
                cartPreview.classList.remove('active');
            });
            
            // Cart Toggle
            const cartToggle = document.getElementById('cartToggle');
            const cartPreview = document.getElementById('cartPreview');
            
            cartToggle.addEventListener('click', function(e) {
                e.preventDefault();
                cartPreview.classList.toggle('active');
                searchBox.classList.remove('active');
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchToggle.contains(e.target) && !searchBox.contains(e.target)) {
                    searchBox.classList.remove('active');
                }
                
                if (!cartToggle.contains(e.target) && !cartPreview.contains(e.target)) {
                    cartPreview.classList.remove('active');
                }
            });
            
            // Product Filtering
            const filterButtons = document.querySelectorAll('.filter-btn');
            const productItems = document.querySelectorAll('#productGrid > [data-category]');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    
                    productItems.forEach(item => {
                        if (filter === 'all' || item.getAttribute('data-category') === filter) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
            
            // Add to Cart functionality
            const addToCartButtons = document.querySelectorAll('.add-to-cart');
            const cartCount = document.querySelector('.cart-count');
            const notification = document.getElementById('notification');
            let count = parseInt(cartCount.textContent);
            
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const product = this.getAttribute('data-product');
                    const price = this.getAttribute('data-price');
                    
                    // Update cart count
                    count++;
                    cartCount.textContent = count;
                    
                    // Show notification
                    notification.textContent = `${product} added to cart successfully!`;
                    notification.classList.add('show');
                    
                    // Hide notification after 3 seconds
                    setTimeout(() => {
                        notification.classList.remove('show');
                    }, 3000);
                    
                    // Here you would typically add the product to the cart storage
                    console.log(`Added ${product} to cart at $${price}`);
                });
            });
            
            // Newsletter Form Submission
            const newsletterForm = document.getElementById('newsletterForm');
            
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[type="email"]').value;
                
                // Here you would typically send this to a server
                console.log(`Subscribed with email: ${email}`);
                
                // Show success message
                notification.textContent = 'Thanks for subscribing to our newsletter!';
                notification.classList.add('show');
                
                // Hide notification after 3 seconds
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000);
                
                // Reset form
                this.reset();
            });
            
            // Back to Top Button
            const toTopButton = document.getElementById('toTop');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    toTopButton.classList.add('visible');
                } else {
                    toTopButton.classList.remove('visible');
                }
            });
            
            toTopButton.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Product Search Functionality (simplified)
            const searchInput = document.querySelector('#searchBox input');
            
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                
                productItems.forEach(item => {
                    const productName = item.querySelector('.card-title').textContent.toLowerCase();
                    
                    if (productName.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>