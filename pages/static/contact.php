<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - HomewareOnTap</title>
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
        
        /* Contact Info Items */
        .contact-info-item {
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            height: 100%;
        }
        
        .contact-info-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .contact-icon {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--secondary);
            border-radius: 50%;
            margin-bottom: 20px;
        }
        
        /* Contact Form */
        .contact-form .form-control {
            height: 55px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 0 15px;
            font-family: 'Quicksand', sans-serif;
        }
        
        .contact-form textarea.form-control {
            height: auto;
            padding: 15px;
        }
        
        .contact-form .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(166, 123, 91, 0.25);
        }
        
        .map-container {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .social-contact {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .social-contact a {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .social-contact a:hover {
            background: var(--dark);
            transform: translateY(-3px);
        }
        
        .business-hours {
            list-style: none;
            padding: 0;
        }
        
        .business-hours li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }
        
        .business-hours li:last-child {
            border-bottom: none;
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
        
        .btn-outline-primary {
            border: 1px solid var(--primary);
            color: var(--primary);
            background: white;
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .submit-btn {
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(166, 123, 91, 0.3);
        }
        
        /* FAQ Preview */
        .accordion-button {
            font-weight: 600;
            color: var(--dark);
        }
        
        .accordion-button:not(.collapsed) {
            background-color: var(--light);
            color: var(--primary);
        }
        
        .accordion-body {
            background-color: var(--light);
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
            .contact-info-item { padding: 20px; margin-bottom: 20px; }
            .contact-icon { width: 60px; height: 60px; }
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
                        <a href="tel:+27 69 878 8382"><i class="fas fa-phone me-2"></i> +27 69 878 8382</a>
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
                        <a class="nav-link" href="/homewareontap/index.php">Home</a>
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
                        <a class="nav-link active" href="/homewareontap/pages/static/contact.php">Contact</a>
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
            <h1 class="display-1 text-white animated slideInDown">Contact Us</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb text-uppercase mb-0">
                    <li class="breadcrumb-item"><a class="text-white" href="/homewareontap/pages/index.php">Home</a></li>
                    <li class="breadcrumb-item text-primary active" aria-current="page">Contact</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Contact Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <h4 class="section-title">Get In Touch</h4>
                <h1 class="display-5 mb-4">We'd Love To Hear From You</h1>
                <p class="mb-4">Have questions about our products or need assistance with an order? Reach out to us through any of the channels below.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="contact-info-item">
                        <div class="contact-icon text-primary">
                            <i class="fa fa-phone-alt fa-2x"></i>
                        </div>
                        <h3>Call Us</h3>
                        <p>Speak directly with our customer service team during business hours.</p>
                        <p class="mb-1"><strong>Phone:</strong></p>
                        <p class="mb-4">+27 69 878 8382</p>
                        <a href="tel:+27 69 878 8382" class="btn btn-outline-primary">Call Now</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="contact-info-item">
                        <div class="contact-icon text-primary">
                            <i class="fa fa-envelope fa-2x"></i>
                        </div>
                        <h3>Email Us</h3>
                        <p>Send us an email and we'll get back to you within 24 hours.</p>
                        <p class="mb-1"><strong>Email:</strong></p>
                        <p class="mb-4">homewareontap@gmail.com</p>
                        <a href="mailto:homewareontap@gmail.com" class="btn btn-outline-primary">Email Now</a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="contact-info-item">
                        <div class="contact-icon text-primary">
                            <i class="fab fa-whatsapp fa-2x"></i>
                        </div>
                        <h3>WhatsApp</h3>
                        <p>Chat with us on WhatsApp for quick responses to your queries.</p>
                        <p class="mb-1"><strong>WhatsApp:</strong></p>
                        <p class="mb-4">+27 69 878 8382</p>
                        <a href="https://wa.me/27698788382?text=Hi%20HomewareOnTap,%20I%20would%20like%20to%20inquire%20about%20your%20products" class="btn btn-outline-primary">Message Us</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Contact End -->

    <!-- Contact Form & Map Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                    <h4 class="section-title">Send Us a Message</h4>
                    <h1 class="display-5 mb-4">Get In Touch With Our Team</h1>
                    <p class="mb-4">Have a question about our products, need help with an order, or want to share feedback? Fill out the form below and we'll get back to you as soon as possible.</p>
                    
                    <form class="contact-form" id="contactForm" action="/homewareontap/system/controllers/ContactController.php" method="POST">
                        <input type="hidden" name="action" value="send_message">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" required>
                                    <label for="name">Your Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Your Email" required>
                                    <label for="email">Your Email</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="subject" name="subject" placeholder="Subject" required>
                                    <label for="subject">Subject</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Leave a message here" id="message" name="message" style="height: 150px" required></textarea>
                                    <label for="message">Message</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary py-3 px-5 submit-btn" type="submit">Send Message</button>
                            </div>
                        </div>
                    </form>
                    
                    <div id="formMessage" class="mt-3" style="display: none;"></div>
                </div>
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                    <div class="h-100">
                        <h4 class="section-title mb-4">Visit Us</h4>
                        <h1 class="display-5 mb-4">Our Location & Business Hours</h1>
                        
                        <div class="map-container mb-4">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d458125.57432646!2d27.808208634374997!3d-26.17150439999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1e950c1b6c2d8e31%3A0x5c2f2c8a4f8b4b4f!2sJohannesburg!5e0!3m2!1sen!2sza!4v1641818392915!5m2!1sen!2sza" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                        </div>
                        
                        <div class="mb-4">
                            <p><i class="fa fa-map-marker-alt text-primary me-2"></i> Based in Johannesburg, delivering across South Africa</p>
                        </div>
                        
                        <h5 class="mb-3">Business Hours</h5>
                        <ul class="business-hours">
                            <li><span>Monday - Friday:</span> <span>8:00 AM - 5:00 PM</span></li>
                            <li><span>Saturday:</span> <span>9:00 AM - 2:00 PM</span></li>
                            <li><span>Sunday:</span> <span>Closed</span></li>
                        </ul>
                        
                        <div class="social-contact">
                            <a href="https://wa.me/27698788382?text=Hi%20HomewareOnTap,%20I%20would%20like%20to%20inquire%20about%20your%20products" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                            <a href="https://tiktok.com/@homewareontap" title="TikTok"><i class="fab fa-tiktok"></i></a>
                            <a href="https://instagram.com/homewareontap" title="Instagram"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Contact Form & Map End -->

    <!-- FAQ Preview Start -->
    <div class="container-xxl py-5 bg-light">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <h4 class="section-title">Common Questions</h4>
                <h1 class="display-5 mb-4">Frequently Asked Questions</h1>
                <p class="mb-4">Before contacting us, you might find answers to your questions in our FAQ section.</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="accordion" id="faqPreview">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    How long does delivery take?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqPreview">
                                <div class="accordion-body">
                                    Once your order is confirmed and payment is received, we process and dispatch orders within 1-2 business days. Delivery times vary by location: Major cities: 3-5 business days, Regional areas: 5-7 business days.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    What is your return policy?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqPreview">
                                <div class="accordion-body">
                                    We want you to be completely satisfied with your purchase. If you're not happy with your items, you may return them within 14 days of receipt for a refund or exchange. Items must be unused, in their original packaging, and with all tags attached.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Do you deliver nationwide?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqPreview">
                                <div class="accordion-body">
                                    Yes, we deliver across South Africa. Delivery costs are calculated based on your location and the size/weight of your order. Standard delivery rates start from R50, and we offer free delivery on orders over R500 anywhere in South Africa.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <a href="/homewareontap/pages/static/faqs.php" class="btn btn-primary">View All FAQs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- FAQ Preview End -->

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
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h4 class="footer-title">HomewareOnTap</h4>
                    <p>Transforming homes with quality essentials that combine functionality with elegant design.</p>
                    <div class="social-icons">
                        <a href="https://wa.me/27682598679?text=Hi%20HomewareOnTap,%20I%20would%20like%20to%20inquire%20about%20your%20products" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        <a href="https://instagram.com/homewareontap" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://tiktok.com/@homewareontap" title="TikTok"><i class="fab fa-tiktok"></i></a>
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
                <p>&copy; <?php echo date('Y'); ?> HomewareOnTap. All Rights Reserved.</p>
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
    </script>
    <script>
        // Form submission handling
        document.addEventListener('DOMContentLoaded', function() {
            const contactForm = document.getElementById('contactForm');
            const formMessage = document.getElementById('formMessage');
            
            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Simple form validation
                    const name = document.getElementById('name').value;
                    const email = document.getElementById('email').value;
                    const subject = document.getElementById('subject').value;
                    const message = document.getElementById('message').value;
                    
                    if (!name || !email || !subject || !message) {
                        showMessage('Please fill in all fields.', 'error');
                        return;
                    }
                    
                    if (!isValidEmail(email)) {
                        showMessage('Please enter a valid email address.', 'error');
                        return;
                    }
                    
                    // Submit form via AJAX
                    const formData = new FormData(contactForm);
                    
                    showMessage('Sending your message...', 'info');
                    
                    fetch(contactForm.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage('Thank you for your message! We will get back to you within 24 hours.', 'success');
                            contactForm.reset();
                        } else {
                            showMessage('There was an error sending your message. Please try again later.', 'error');
                        }
                    })
                    .catch(error => {
                        showMessage('There was an error sending your message. Please try again later.', 'error');
                        console.error('Error:', error);
                    });
                });
            }
            
            function showMessage(text, type) {
                formMessage.textContent = text;
                formMessage.className = 'mt-3 alert alert-' + (type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info');
                formMessage.style.display = 'block';
                
                // Hide message after 5 seconds
                setTimeout(() => {
                    formMessage.style.display = 'none';
                }, 5000);
            }
            
            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            
            // Newsletter form
            document.getElementById('newsletterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[type="email"]').value;
                this.querySelector('input[type="email"]').value = '';
                alert(`Thank you for subscribing with ${email}!`);
            });
            
            // Cart count update
            function updateCartCount() {
                // This would typically be fetched from the server
                // For now, we'll use a placeholder
                const cartBadge = document.querySelector('.cart-badge');
                if (cartBadge) {
                    // In a real implementation, you would fetch this from your backend
                    // cartBadge.textContent = cartCount;
                }
            }
            
            // Initialize cart count
            updateCartCount();
        });
    </script>
</body>
</html>