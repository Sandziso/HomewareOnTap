<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - HomewareOnTap</title>
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- WOW CSS for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
        
        /* Header Styles */
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
        
        /* Page Header */
        .page-header {
            background: linear-gradient(rgba(58, 50, 41, 0.7), rgba(58, 50, 41, 0.7)), url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            color: white;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        
        .breadcrumb-item a {
            color: white;
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--primary);
        }
        
        /* Section Title */
        .section-title {
            position: relative;
            margin-bottom: 40px;
            text-align: center;
            color: var(--primary);
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
        
        /* About Page Specific Styles */
        .about-img {
            position: relative;
        }
        
        .about-img img {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .about-img img:first-child {
            width: 85%;
            margin-bottom: -80px;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }
        
        .about-img img:last-child {
            width: 70%;
            border: 5px solid white;
            position: absolute;
            bottom: 0;
            right: 0;
            z-index: 1;
        }
        
        .feature-img {
            position: relative;
        }
        
        .feature-img img:first-child {
            width: 85%;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .feature-img img:last-child {
            width: 60%;
            border: 5px solid white;
            border-radius: 10px;
            position: absolute;
            bottom: -40px;
            right: 0;
            z-index: 1;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .mission-img img {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 100%;
        }
        
        .team-item {
            transition: all 0.3s;
        }
        
        .team-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .team-social a {
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .team-social a:hover {
            transform: translateY(-3px);
        }
        
        .counter-box {
            border: 5px solid var(--primary);
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .counter-box h1 {
            margin: 0;
            color: var(--primary);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(166, 123, 91, 0.3);
            color: white;
        }
        
        /* Newsletter */
        .newsletter-section {
            background: linear-gradient(rgba(58, 50, 41, 0.9), rgba(58, 50, 41, 0.9)), url('https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1758&q=80');
            background-size: cover;
            background-position: center;
            padding: 80px 0;
            color: white;
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
        
        /* Footer */
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
            .page-header h1 { font-size: 2.5rem; }
        }
        
        @media (max-width: 768px) {
            .search-form { display: none; }
            .page-header { padding: 80px 0; }
            .page-header h1 { font-size: 2rem; }
            .about-img img:first-child, 
            .about-img img:last-child,
            .feature-img img:first-child,
            .feature-img img:last-child {
                position: static;
                width: 100%;
                margin: 0 0 20px 0;
            }
            .newsletter-form { flex-direction: column; }
            .newsletter-form input { border-radius: 30px; margin-bottom: 10px; }
            .newsletter-form button { border-radius: 30px; padding: 12px; }
        }
        
        @media (max-width: 576px) {
            .top-bar { text-align: center; }
            .navbar-brand { font-size: 24px; }
            .page-header h1 { font-size: 1.8rem; }
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
            <a class="navbar-brand" href="/homewareontap/index.php">
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
                        <a class="nav-link active" href="/homewareontap/pages/static/about.php">About Us</a>
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

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <h1 class="display-1 text-white animated slideInDown">About Us</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb text-uppercase mb-0">
                    <li class="breadcrumb-item"><a class="text-white" href="/homewareontap/pages/index.php">Home</a></li>
                    <li class="breadcrumb-item text-primary active" aria-current="page">About</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- About Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                    <div class="about-img">
                        <img class="img-fluid" src="https://images.unsplash.com/photo-1616137422495-1e9e46e2aa77?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="HomewareOnTap Founder">
                        <img class="img-fluid" src="https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="HomewareOnTap Products">
                    </div>
                </div>
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                    <h4 class="section-title">About Us</h4>
                    <h1 class="display-5 mb-4">Welcome to HomewareOnTap - Your Home Decor Destination</h1>
                    <p>HomewareOnTap was founded by Katlego Matuka with a passion for helping South Africans create beautiful, functional living spaces. We believe that everyone deserves a home that reflects their personality and meets their needs.</p>
                    <p class="mb-4">What started as a small venture on social media platforms like TikTok and WhatsApp has now grown into a trusted homeware destination. Our carefully curated collection includes everything from kitchen essentials to decorative items that add character to your home. We're committed to providing quality products at affordable prices, with excellent customer service.</p>
                    <div class="d-flex align-items-center mb-5">
                        <div class="counter-box">
                            <h1 class="display-1 mb-n2" data-toggle="counter-up">500</h1>
                        </div>
                        <div class="ps-4">
                            <h3>Happy</h3>
                            <h3>South African</h3>
                            <h3 class="mb-0">Customers</h3>
                        </div>
                    </div>
                    <a class="btn btn-primary py-3 px-5" href="/homewareontap/pages/static/contact.php">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->

    <!-- Feature Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                    <h4 class="section-title">Why Choose HomewareOnTap!</h4>
                    <h1 class="display-5 mb-4">Why South Africans Love Shopping With Us</h1>
                    <p class="mb-4">At HomewareOnTap, we're committed to providing an exceptional shopping experience for all our customers. From our carefully curated products to our reliable delivery service, we prioritize your satisfaction.</p>
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary rounded-circle p-3 text-white me-4">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                                <div class="ms-4">
                                    <h3>Quality Products</h3>
                                    <p class="mb-0">We carefully select each item in our collection for quality, functionality, and design.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary rounded-circle p-3 text-white me-4">
                                        <i class="fas fa-tags fa-2x"></i>
                                    </div>
                                </div>
                                <div class="ms-4">
                                    <h3>Affordable Prices</h3>
                                    <p class="mb-0">We believe beautiful homeware should be accessible to everyone at reasonable prices.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary rounded-circle p-3 text-white me-4">
                                        <i class="fas fa-truck fa-2x"></i>
                                    </div>
                                </div>
                                <div class="ms-4">
                                    <h3>Reliable Delivery</h3>
                                    <p class="mb-0">We deliver across South Africa with trusted courier partners to ensure your items arrive safely.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary rounded-circle p-3 text-white me-4">
                                        <i class="fas fa-heart fa-2x"></i>
                                    </div>
                                </div>
                                <div class="ms-4">
                                    <h3>Personal Touch</h3>
                                    <p class="mb-0">As a small business, we value each customer and provide personalized service you won't find elsewhere.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="feature-img">
                        <img class="img-fluid" src="https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="HomewareOnTap">
                        <img class="img-fluid" src="https://images.unsplash.com/photo-1616137422495-1e9e46e2aa77?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="HomewareOnTap">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Feature End -->

    <!-- Mission & Vision Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                    <div class="mission-img">
                        <img class="img-fluid rounded" src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Our Mission">
                    </div>
                </div>
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                    <h4 class="section-title">Our Mission & Vision</h4>
                    <h1 class="display-5 mb-4">Transforming Homes Across South Africa</h1>
                    <div class="mb-4">
                        <h3 class="mb-3">Our Mission</h3>
                        <p>To provide beautiful, functional, and affordable homeware that helps South Africans create spaces they love coming home to. We're committed to making quality home decor accessible to everyone.</p>
                    </div>
                    <div class="mb-4">
                        <h3 class="mb-3">Our Vision</h3>
                        <p>To become South Africa's most trusted homeware destination, known for our curated collections, exceptional customer service, and commitment to helping customers express their personal style through home decor.</p>
                    </div>
                    <div class="mb-4">
                        <h3 class="mb-3">Our Values</h3>
                        <p><i class="fa fa-check text-primary me-2"></i>Quality in every product we offer</p>
                        <p><i class="fa fa-check text-primary me-2"></i>Authenticity in our brand voice</p>
                        <p><i class="fa fa-check text-primary me-2"></i>Accessibility through affordable pricing</p>
                        <p><i class="fa fa-check text-primary me-2"></i>Reliability in our service and delivery</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Mission & Vision End -->

    <!-- Founder Start -->
    <div class="container-xxl py-5 bg-light">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <h4 class="section-title">Our Founder</h4>
                <h1 class="display-5 mb-4">Meet Katlego Matuka</h1>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-8 col-md-12 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="team-item text-center bg-white rounded overflow-hidden">
                        <div class="rounded-circle overflow-hidden m-4 mx-auto" style="width: 150px; height: 150px;">
                            <img class="img-fluid w-100 h-100" src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Katlego Matuka" style="object-fit: cover;">
                        </div>
                        <div class="team-text px-4 py-3">
                            <h5 class="mb-1">Katlego Matuka</h5>
                            <p class="text-primary mb-0">Founder & Creative Director</p>
                            <p class="mt-3">"I started HomewareOnTap with a simple vision: to help South Africans create beautiful, functional living spaces without breaking the bank. What began as a passion project shared on social media has grown into something much bigger, thanks to our amazing customers who continue to inspire us every day."</p>
                            <div class="team-social py-2">
                                <a class="btn btn-square btn-outline-primary mx-1" href="https://wa.me/27682598679"><i class="fab fa-whatsapp"></i></a>
                                <a class="btn btn-square btn-outline-primary mx-1" href="https://tiktok.com/@homewareontap"><i class="fab fa-tiktok"></i></a>
                                <a class="btn btn-square btn-outline-primary mx-1" href="https://instagram.com/homewareontap"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Founder End -->

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
    
    <!-- Login Modal -->
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
    
    <!-- Register Modal -->
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
    <!-- WOW JS for animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script>
    <script>
        new WOW().init();
        
        // Counter animation
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('[data-toggle="counter-up"]');
            counters.forEach(counter => {
                const target = +counter.innerText;
                let count = 0;
                const duration = 2000; // in milliseconds
                const increment = target / (duration / 20);
                
                const updateCount = () => {
                    if (count < target) {
                        count += increment;
                        counter.innerText = Math.ceil(count);
                        setTimeout(updateCount, 20);
                    } else {
                        counter.innerText = target;
                    }
                };
                
                // Start counter when element is in viewport
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            updateCount();
                            observer.unobserve(entry.target);
                        }
                    });
                });
                
                observer.observe(counter);
            });
            
            // Newsletter form
            document.getElementById('newsletterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[type="email"]').value;
                this.querySelector('input[type="email"]').value = '';
                alert(`Thank you for subscribing with ${email}!`);
            });
        });
    </script>
</body>
</html>