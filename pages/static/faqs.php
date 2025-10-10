
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - HomewareOnTap</title>
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #A67B5B;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            color: var(--dark);
            background-color: #f8f9fa;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'League Spartan', sans-serif;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        /* Header Styles - Matching index.php */
        .top-bar {
            background-color: var(--dark);
            color: white;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .top-bar a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
        }
        
        .top-bar a:hover {
            color: var(--primary);
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            transition: all 0.3s ease;
        }
        
        .navbar.scrolled {
            padding: 10px 0;
        }
        
        .navbar-brand {
            font-family: 'League Spartan', sans-serif;
            font-weight: 700;
            font-size: 28px;
        }
        
        .navbar-brand span:first-child {
            color: var(--primary);
        }
        
        .navbar-brand span:last-child {
            color: var(--dark);
        }
        
        .nav-link {
            font-weight: 500;
            color: var(--dark);
            margin: 0 10px;
            transition: color 0.3s;
            position: relative;
        }
        
        .nav-link:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary);
            transition: width 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            color: var(--primary);
        }
        
        .nav-link:hover:after, .nav-link.active:after {
            width: 100%;
        }
        
        .navbar-toggler {
            border: none;
            font-size: 24px;
        }
        
        .search-form {
            position: relative;
            margin-right: 15px;
        }
        
        .search-form input {
            padding: 10px 15px;
            padding-right: 45px;
            border-radius: 30px;
            border: 1px solid #e9ecef;
            width: 250px;
            transition: all 0.3s;
        }
        
        .search-form input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(166, 123, 91, 0.1);
        }
        
        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--dark);
        }
        
        .cart-icon, .user-icon {
            position: relative;
            font-size: 20px;
            color: var(--dark);
            margin-left: 15px;
            transition: color 0.3s;
        }
        
        .cart-icon:hover, .user-icon:hover {
            color: var(--primary);
        }
        
        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--primary);
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Hero Section for FAQs */
        .hero {
            background: linear-gradient(rgba(58, 50, 41, 0.7), rgba(58, 50, 41, 0.7)), url('https://images.unsplash.com/photo-1524758631624-e2822e304ee6?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            color: white;
            text-align: center;
            position: relative;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            animation: fadeInDown 1s ease;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeInUp 1s ease;
        }
        
        /* Section Title */
        .section-title {
            position: relative;
            margin-bottom: 40px;
            text-align: center;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary);
        }
        
        /* FAQ Specific Styles */
        .search-box {
            position: relative;
            max-width: 500px;
            margin: 0 auto 40px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 20px;
            border: 1px solid #e9ecef;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(166, 123, 91, 0.1);
            outline: none;
        }
        
        .search-box button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--dark);
            font-size: 18px;
        }
        
        .category-filter {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }
        
        .category-btn {
            margin: 5px;
            padding: 10px 20px;
            border: 1px solid #e9ecef;
            background: white;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .category-btn:hover, .category-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(166, 123, 91, 0.2);
        }
        
        .faq-item {
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            background: white;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .faq-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .faq-question {
            padding: 20px;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            width: 100%;
            text-align: left;
            transition: background 0.3s;
        }
        
        .faq-question:hover {
            background: var(--light);
        }
        
        .faq-question h5 {
            margin: 0;
            color: var(--dark);
        }
        
        .faq-question i {
            color: var(--primary);
            transition: transform 0.3s;
            font-size: 18px;
        }
        
        .faq-question[aria-expanded="true"] i {
            transform: rotate(180deg);
        }
        
        .faq-answer {
            background: var(--light);
            padding: 0 20px 20px;
            line-height: 1.7;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            background: var(--light);
            border-radius: 12px;
            margin: 20px 0;
            display: none;
        }
        
        .contact-prompt {
            background: var(--light);
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            margin-top: 60px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(166, 123, 91, 0.3);
            color: white;
            text-decoration: none;
        }
        
        /* Newsletter - Optional, but matching index */
        .newsletter-section {
            background: linear-gradient(rgba(58, 50, 41, 0.9), rgba(58, 50, 41, 0.9)), url('https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1758&q=80');
            background-size: cover;
            background-position: center;
            padding: 80px 0;
            color: white;
            margin-top: 60px;
        }
        
        .newsletter-form {
            display: flex;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .newsletter-form input {
            flex: 1;
            padding: 15px 20px;
            border: none;
            border-radius: 30px 0 0 30px;
        }
        
        .newsletter-form button {
            padding: 0 25px;
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            color: white;
            border: none;
            border-radius: 0 30px 30px 0;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* Footer - Matching index.php */
        .footer {
            background-color: var(--dark);
            color: white;
            padding: 60px 0 30px;
        }
        
        .footer-title {
            position: relative;
            margin-bottom: 25px;
            font-size: 20px;
        }
        
        .footer-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--primary);
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--primary);
        }
        
        .contact-info {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .contact-info li {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .contact-info i {
            margin-right: 15px;
            color: var(--primary);
            font-size: 20px;
            margin-top: 3px;
        }
        
        .social-icons {
            display: flex;
            margin-top: 20px;
        }
        
        .social-icons a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .social-icons a:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .search-form input { width: 200px; }
            .hero h1 { font-size: 2.5rem; }
        }
        
        @media (max-width: 768px) {
            .search-form { display: none; }
            .hero { padding: 80px 0; }
            .hero h1 { font-size: 2rem; }
            .hero p { font-size: 1rem; }
            .newsletter-form { flex-direction: column; }
            .newsletter-form input { border-radius: 30px; margin-bottom: 10px; }
            .newsletter-form button { border-radius: 30px; padding: 12px; }
            .category-filter { flex-direction: column; align-items: center; }
            .category-btn { margin: 5px 0; width: 200px; }
        }
        
        @media (max-width: 576px) {
            .top-bar { text-align: center; }
            .navbar-brand { font-size: 24px; }
            .hero h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar d-none d-md-block">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex">
                        <a href="tel:+27698788382"><i class="fas fa-phone me-2"></i> +27 69 878 8382</a>
                        <a href="mailto:homewareontap@gmail.com"><i class="fas fa-envelope me-2"></i> homewareontap@gmail.com</a>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/homewareontap/pages/static/track-order.php">Track Order</a>
                    <a href="/homewareontap/pages/static/faqs.php">FAQ</a>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a>
                    <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Register</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/homewareontap/pages/index.php">
                <span>Homeware</span><span>OnTap</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/homewareontap/pages/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/homewareontap/pages/shop.php">Shop</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Categories
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                            <li><a class="dropdown-item" href="/homewareontap/pages/shop.php?category=kitchenware">Kitchenware</a></li>
                            <li><a class="dropdown-item" href="/homewareontap/pages/shop.php?category=home-decor">Home Decor</a></li>
                            <li><a class="dropdown-item" href="/homewareontap/pages/shop.php?category=bed-bath">Bed & Bath</a></li>
                            <li><a class="dropdown-item" href="/homewareontap/pages/shop.php?category=tableware">Tableware</a></li>
                            <li><a class="dropdown-item" href="/homewareontap/pages/shop.php?category=storage">Storage Solutions</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/homewareontap/pages/static/about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/homewareontap/pages/static/contact.php">Contact</a>
                    </li>
                </ul>
                
                <form class="search-form d-none d-lg-block" id="searchForm">
                    <input type="text" placeholder="Search for products...">
                    <button class="search-btn"><i class="fas fa-search"></i></button>
                </form>
                
                <div class="d-flex align-items-center">
                    <a href="/homewareontap/pages/cart.php" class="cart-icon" id="cartIcon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge">0</span>
                    </a>
                    <a href="#" class="user-icon d-none d-md-block" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fas fa-user"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Frequently Asked Questions</h1>
            <p>Find quick answers to common queries about ordering, shipping, returns, and more. We're here to help!</p>
        </div>
    </section>
    
    <!-- FAQs Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Common Questions</h2>
                <p class="mb-4">Can't find what you're looking for? <a href="/homewareontap/pages/static/contact.php">Contact us directly</a> for personalized assistance.</p>
            </div>
            
            <div class="search-box">
                <input type="text" class="form-control" placeholder="Search FAQs..." id="faq-search">
                <button type="button" id="search-btn"><i class="fas fa-search"></i></button>
            </div>
            
            <div class="category-filter">
                <button class="category-btn active" data-category="all">All Questions</button>
                <button class="category-btn" data-category="ordering">Ordering</button>
                <button class="category-btn" data-category="shipping">Shipping & Delivery</button>
                <button class="category-btn" data-category="returns">Returns & Exchanges</button>
                <button class="category-btn" data-category="products">Products</button>
            </div>
            
            <div class="no-results" id="no-results">
                <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                <h4 class="text-muted">No questions found</h4>
                <p class="text-muted">Try different keywords or browse by category</p>
            </div>
            
            <div class="accordion" id="faqAccordion">
                <!-- Ordering Questions -->
                <div class="faq-item" data-category="ordering">
                    <button class="faq-question" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                        <h5>How do I place an order?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseOne" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>You can place an order directly through our website by adding items to your cart and proceeding to checkout. Alternatively, you can message us on WhatsApp at +27 69 878 8382 with your order details, and we'll assist you with the ordering process.</p>
                        </div>
                    </div>
                </div>
                
                <div class="faq-item" data-category="ordering">
                    <button class="faq-question collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                        <h5>What payment methods do you accept?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseTwo" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>We accept various payment methods including credit/debit cards, EFT (Electronic Funds Transfer), and payments through secure payment gateways like Yoco and PayFast. All transactions are secure and encrypted for your protection.</p>
                        </div>
                    </div>
                </div>
                
                <div class="faq-item" data-category="ordering">
                    <button class="faq-question collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                        <h5>Is my personal and payment information secure?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseThree" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>Yes, we take your privacy and security seriously. Our website uses SSL encryption to protect your personal information, and we do not store your payment details. All payments are processed through secure, certified payment gateways that comply with the highest security standards.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping & Delivery Questions -->
                <div class="faq-item" data-category="shipping">
                    <button class="faq-question collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                        <h5>Where do you deliver?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseFour" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>We currently deliver across South Africa. Delivery times and costs may vary depending on your location. Major metropolitan areas typically receive deliveries within 3-5 business days, while regional areas may take slightly longer.</p>
                        </div>
                    </div>
                </div>
                
                <div class="faq-item" data-category="shipping">
                    <button class="faq-question collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive">
                        <h5>How long does delivery take?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseFive" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>Once your order is confirmed and payment is received, we process and dispatch orders within 1-2 business days. Delivery times vary by location:</p>
                            <ul>
                                <li>Major cities: 3-5 business days</li>
                                <li>Regional areas: 5-7 business days</li>
                            </ul>
                            <p>You will receive a tracking number once your order is dispatched so you can monitor its progress.</p>
                        </div>
                    </div>
                </div>
                
                <div class="faq-item" data-category="shipping">
                    <button class="faq-question collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix">
                        <h5>How much does delivery cost?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseSix" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>Delivery costs are calculated based on your location and the size/weight of your order. Standard delivery rates start from R50. We offer free delivery on orders over R500 anywhere in South Africa. Exact delivery costs will be shown during checkout before you complete your purchase.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Returns & Exchanges Questions -->
                <div class="faq-item" data-category="returns">
                    <button class="faq-question collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven">
                        <h5>What is your return policy?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseSeven" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>We want you to be completely satisfied with your purchase. If you're not happy with your items, you may return them within 14 days of receipt for a refund or exchange. Items must be unused, in their original packaging, and with all tags attached. Unfortunately, we cannot accept returns on personalized items or items that have been used.</p>
                        </div>
                    </div>
                </div>
                
                <div class="faq-item" data-category="returns">
                    <button class="faq-question collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight">
                        <h5>How do I return an item?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseEight" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>To return an item, please follow these steps:</p>
                            <ol>
                                <li>Contact us at homewareontap@gmail.com or via WhatsApp at +27 69 878 8382 to initiate the return process.</li>
                                <li>Pack the item securely in its original packaging.</li>
                                <li>Include your order number and reason for return.</li>
                                <li>We will provide you with a return shipping label or instructions.</li>
                                <li>Once we receive and inspect the returned item, we will process your refund or exchange.</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="faq-item" data-category="returns">
                    <button class="faq-question collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine">
                        <h5>How long does it take to process a refund?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseNine" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>Once we receive your returned item, we will process your refund within 3-5 business days. The time it takes for the refund to appear in your account will depend on your bank or payment method, but typically takes 5-10 business days.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Product Questions -->
                <div class="faq-item" data-category="products">
                    <button class="faq-question collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTen">
                        <h5>Are your products authentic and high quality?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseTen" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>Yes! We carefully curate all our products and only source from reputable suppliers. We prioritize quality and ensure that every item we sell meets our standards for durability, functionality, and design. If you ever have an issue with a product, please contact us and we'll make it right.</p>
                        </div>
                    </div>
                </div>
                
                <div class="faq-item" data-category="products">
                    <button class="faq-question collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEleven">
                        <h5>Do you offer warranties on your products?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseEleven" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>Many of our products come with manufacturer warranties that vary by item. Specific warranty information is included with product documentation. Additionally, we stand behind the quality of all our products and will work to resolve any issues you might have with items purchased from us.</p>
                        </div>
                    </div>
                </div>
                
                <div class="faq-item" data-category="products">
                    <button class="faq-question collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwelve">
                        <h5>How do I care for and clean my homeware items?</h5>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div id="collapseTwelve" class="collapse" data-bs-parent="#faqAccordion">
                        <div class="faq-answer">
                            <p>Care instructions vary by product material. Generally, we recommend:</p>
                            <ul>
                                <li>Wood items: Wipe with a damp cloth and dry immediately. Avoid prolonged exposure to water.</li>
                                <li>Glass items: Hand wash with mild detergent or place in dishwasher if specified.</li>
                                <li>Textiles: Follow care labels - most can be machine washed on gentle cycles.</li>
                            </ul>
                            <p>Specific care instructions are provided with each product. If you need additional guidance, please contact us.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="contact-prompt">
                <h3 class="mb-3">Still have questions?</h3>
                <p class="mb-4">We're here to help! Get in touch with our friendly customer service team.</p>
                <a href="/homewareontap/pages/static/contact.php" class="btn-primary">Contact Us</a>
            </div>
        </div>
    </section>
    
    <!-- Newsletter -->
    <section class="newsletter-section">
        <div class="container text-center">
            <h2>Subscribe to Our Newsletter</h2>
            <p class="mb-4">Get updates on new products, special offers, and interior design tips.</p>
            
            <form class="newsletter-form" id="newsletterForm" action="/homewareontap/includes/subscribe.php" method="POST">
                <input type="email" name="email" placeholder="Your email address" required>
                <button type="submit">Subscribe</button>
            </form>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h4 class="footer-title">HomewareOnTap</h4>
                    <p>Transforming homes with quality essentials that combine functionality with elegant design.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="/homewareontap/pages/index.php">Home</a></li>
                        <li><a href="/homewareontap/pages/shop.php">Shop</a></li>
                        <li><a href="/homewareontap/pages/static/about.php">About Us</a></li>
                        <li><a href="/homewareontap/pages/static/contact.php">Contact</a></li>
                        <li><a href="/homewareontap/pages/static/faqs.php">FAQ</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h4 class="footer-title">Categories</h4>
                    <ul class="footer-links">
                        <li><a href="/homewareontap/pages/shop.php?category=kitchenware">Kitchenware</a></li>
                        <li><a href="/homewareontap/pages/shop.php?category=home-decor">Home Decor</a></li>
                        <li><a href="/homewareontap/pages/shop.php?category=bed-bath">Bed & Bath</a></li>
                        <li><a href="/homewareontap/pages/shop.php?category=tableware">Tableware</a></li>
                        <li><a href="/homewareontap/pages/shop.php?category=storage">Storage Solutions</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h4 class="footer-title">Contact Us</h4>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>+27 69 878 8382</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>homewareontap@gmail.com</span>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>123 Design Street, Creative District<br>Johannesburg, South Africa</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; 2025 HomewareOnTap. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
    
    <!-- Login Modal (simplified, matching index) -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%); color: white; border: none;">
                    <h5 class="modal-title">Login to Your Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm" action="/homewareontap/pages/auth/login-process.php" method="POST">
                        <div class="mb-3">
                            <label for="loginEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="loginEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="loginPassword" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Register Modal (simplified) -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%); color: white; border: none;">
                    <h5 class="modal-title">Create an Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm" action="/homewareontap/pages/auth/register-process.php" method="POST">
                        <div class="mb-3">
                            <label for="registerName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="registerName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="registerEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="registerPassword" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Navbar scroll effect
            window.addEventListener('scroll', function() {
                if (window.scrollY > 50) {
                    document.querySelector('.navbar').classList.add('scrolled');
                } else {
                    document.querySelector('.navbar').classList.remove('scrolled');
                }
            });
            
            // FAQ Search Functionality
            const faqSearch = document.getElementById('faq-search');
            const searchButton = document.getElementById('search-btn');
            const noResults = document.getElementById('no-results');
            
            function performSearch() {
                const searchTerm = faqSearch.value.toLowerCase().trim();
                const faqItems = document.querySelectorAll('.faq-item');
                let foundResults = false;
                
                faqItems.forEach(item => {
                    const question = item.querySelector('.faq-question h5').textContent.toLowerCase();
                    const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                    
                    if (searchTerm === '' || question.includes(searchTerm) || answer.includes(searchTerm)) {
                        item.style.display = 'block';
                        foundResults = true;
                        
                        // Open the matching item if search term matches
                        if (searchTerm !== '' && (question.includes(searchTerm) || answer.includes(searchTerm))) {
                            const collapseId = item.querySelector('.faq-question').getAttribute('data-bs-target');
                            const collapseElement = document.querySelector(collapseId);
                            if (collapseElement) {
                                const bsCollapse = new bootstrap.Collapse(collapseElement, { toggle: true });
                            }
                        }
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                noResults.style.display = (!foundResults && searchTerm.length > 0) ? 'block' : 'none';
            }
            
            faqSearch.addEventListener('keyup', performSearch);
            searchButton.addEventListener('click', performSearch);
            
            // Category Filtering
            const categoryButtons = document.querySelectorAll('.category-btn');
            categoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    const category = this.getAttribute('data-category');
                    const faqItems = document.querySelectorAll('.faq-item');
                    let foundCategoryResults = false;
                    
                    faqItems.forEach(item => {
                        if (category === 'all' || item.getAttribute('data-category') === category) {
                            item.style.display = 'block';
                            foundCategoryResults = true;
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    noResults.style.display = (!foundCategoryResults) ? 'block' : 'none';
                    
                    // Clear search
                    faqSearch.value = '';
                    performSearch();
                });
            });
            
            // Newsletter form
            document.getElementById('newsletterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[type="email"]').value;
                this.querySelector('input[type="email"]').value = '';
                alert(`Thank you for subscribing with ${email}!`); // Replace with toast if desired
            });
        });
    </script>
</body>
</html>
