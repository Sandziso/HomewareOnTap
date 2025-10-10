<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../includes/config.php';
require_once '../../includes/auth.php'; // Include auth functions
require_once '../../includes/functions.php';

// Clear any previous login email from session
if (isset($_SESSION['login_email'])) {
    unset($_SESSION['login_email']);
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HomewareOnTap</title>
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
            --gradient-primary: linear-gradient(135deg, #A67B5B 0%, #8B6145 100%);
            --gradient-dark: linear-gradient(135deg, #3A3229 0%, #2A231C 100%);
            --shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 5px 20px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.15);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            color: var(--dark);
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'League Spartan', sans-serif;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        /* Header Styles */
        .top-bar {
            background: var(--gradient-dark);
            color: white;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .top-bar a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
            transition: var(--transition);
        }
        
        .top-bar a:hover {
            color: var(--secondary);
        }
        
        .navbar {
            background-color: white;
            box-shadow: var(--shadow-sm);
            padding: 15px 0;
            transition: var(--transition);
        }
        
        .navbar.scrolled {
            padding: 10px 0;
            box-shadow: var(--shadow-md);
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
            transition: var(--transition);
            position: relative;
        }
        
        .nav-link:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gradient-primary);
            transition: var(--transition);
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
            padding: 8px 12px;
            border-radius: 8px;
            transition: var(--transition);
        }
        
        .navbar-toggler:hover {
            background-color: var(--light);
        }
        
        /* Login Hero */
        .login-hero {
            background: linear-gradient(rgba(58, 50, 41, 0.85), rgba(58, 50, 41, 0.9)), url('https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 40vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            position: relative;
            padding: 80px 0;
        }
        
        /* Login Container */
        .login-container {
            max-width: 500px;
            margin: -80px auto 3rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 2;
            overflow: hidden;
        }
        
        .login-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2.5rem;
            text-align: center;
            position: relative;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }
        
        .login-header h2 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            position: relative;
        }
        
        .login-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
        }
        
        .login-body {
            padding: 2.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            padding: 14px 16px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: var(--transition);
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(166, 123, 91, 0.1);
        }
        
        .form-control.is-invalid {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }
        
        .form-control.is-valid {
            border-color: var(--success);
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }
        
        .input-group-text {
            background-color: var(--light);
            border: 2px solid #e9ecef;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 14px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(166, 123, 91, 0.3);
            width: 100%;
            font-size: 1.1rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(166, 123, 91, 0.4);
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: var(--transition);
            width: 100%;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .register-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: none;
            padding: 1rem 1.5rem;
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 5;
            background: white;
            padding: 0 5px;
        }
        
        .form-group {
            position: relative;
        }
        
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
            backdrop-filter: blur(5px);
        }
        
        .loading-content {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
        }
        
        /* Footer */
        .footer {
            background: var(--gradient-dark);
            color: white;
            padding: 60px 0 30px;
            margin-top: auto;
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
            background: var(--gradient-primary);
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
            transition: var(--transition);
        }
        
        .footer-links a:hover {
            color: var(--primary);
            padding-left: 5px;
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
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 10px;
            transition: var(--transition);
        }
        
        .social-icons a:hover {
            background: var(--primary);
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .login-hero {
                min-height: 30vh;
                padding: 60px 0;
                background-attachment: scroll;
            }
            
            .login-container {
                margin: -60px 1rem 2rem;
            }
            
            .login-header {
                padding: 2rem 1.5rem;
            }
            
            .login-header h2 {
                font-size: 1.8rem;
            }
            
            .login-body {
                padding: 2rem 1.5rem;
            }
            
            .footer {
                padding: 40px 0 20px;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 24px;
            }
            
            .top-bar {
                text-align: center;
            }
            
            .top-bar a {
                margin-right: 8px;
                font-size: 13px;
            }
            
            .login-header h2 {
                font-size: 1.5rem;
            }
            
            .login-header p {
                font-size: 1rem;
            }
        }
        
        /* Custom enhancements */
        .floating-label {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .floating-label .form-control {
            padding-top: 24px;
            padding-bottom: 10px;
        }
        
        .floating-label label {
            position: absolute;
            top: 12px;
            left: 16px;
            color: #6c757d;
            transition: var(--transition);
            pointer-events: none;
            background: white;
            padding: 0 5px;
        }
        
        .floating-label .form-control:focus + label,
        .floating-label .form-control:not(:placeholder-shown) + label {
            top: -8px;
            left: 12px;
            font-size: 0.8rem;
            color: var(--primary);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h4>Logging You In</h4>
            <p class="mb-0">Please wait while we authenticate your account...</p>
        </div>
    </div>

    <!-- Top Bar -->
    <div class="top-bar d-none d-md-block">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex">
                        <a href="tel:+27698788382"><i class="fas fa-phone me-2"></i> +27698788382</a>
                        <a href="mailto:homewareontap@gmail.com"><i class="fas fa-envelope me-2"></i> homewareontap@gmail.com</a>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/homewareontap/pages/static/track-order.php">Track Order</a>
                    <a href="/homewareontap/pages/static/faqs.php">FAQ</a>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="fw-bold">Login</a>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/register.php">Register</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <span>Homeware</span><span>OnTap</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/shop.php">Shop</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Categories
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/shop.php?category=kitchenware">Kitchenware</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/shop.php?category=home-decor">Home Decor</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/shop.php?category=bed-bath">Bed & Bath</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/shop.php?category=tableware">Tableware</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/shop.php?category=storage">Storage Solutions</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/static/about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/static/contact.php">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Hero Section -->
    <section class="login-hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <h1 class="mb-4" style="animation: fadeInDown 1s ease;">Welcome Back</h1>
                    <p class="lead mb-0" style="animation: fadeInUp 1s ease;">Sign in to continue your shopping journey</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Form -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="login-container">
                    <div class="login-header">
                        <h2>Login to Your Account</h2>
                        <p>Welcome back! Please enter your details</p>
                    </div>
                    
                    <div class="login-body">
                        <!-- Messages will be displayed here -->
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['message']; 
                                unset($_SESSION['message']);
                                unset($_SESSION['message_type']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $_SESSION['error']; 
                                unset($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo SITE_URL; ?>/pages/auth/login-process.php" method="POST" id="loginForm" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-4">
                                <div class="form-group floating-label">
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo isset($_SESSION['form_data']['email']) ? htmlspecialchars($_SESSION['form_data']['email']) : (isset($_COOKIE['remembered_email']) ? htmlspecialchars($_COOKIE['remembered_email']) : ''); ?>" 
                                           placeholder=" " required>
                                    <label for="email">Email Address *</label>
                                    <div class="invalid-feedback">Please provide a valid email address</div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-group floating-label">
                                    <input type="password" class="form-control" id="password" name="password" placeholder=" " required>
                                    <span class="password-toggle" id="passwordToggle"><i class="bi bi-eye"></i></span>
                                    <label for="password">Password *</label>
                                    <div class="invalid-feedback">Please enter your password</div>
                                </div>
                            </div>
                            
                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" <?php echo (isset($_COOKIE['remembered_email']) || isset($_SESSION['form_data']['remember_me'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="remember_me">
                                        Remember me
                                    </label>
                                </div>
                                <a href="<?php echo SITE_URL; ?>/pages/auth/forgot-password.php" class="text-decoration-none small text-muted">Forgot password?</a>
                            </div>
                            
                            <button type="submit" class="btn btn-primary mb-4" id="loginButton">Login</button>
                            
                            <div class="register-link">
                                <p class="text-center mb-0">Don't have an account? <a href="<?php echo SITE_URL; ?>/pages/auth/register.php">Create one</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                        <li><a href="<?php echo SITE_URL; ?>">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/shop.php">Shop</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/static/about.php">About Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/static/contact.php">Contact</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/static/faqs.php">FAQ</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h4 class="footer-title">Categories</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?category=kitchenware">Kitchenware</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?category=home-decor">Home Decor</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?category=bed-bath">Bed & Bath</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?category=tableware">Tableware</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?category=storage">Storage Solutions</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h4 class="footer-title">Contact Us</h4>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>+27698788382</span>
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

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Navbar scroll effect
            $(window).scroll(function() {
                if ($(window).scrollTop() > 50) {
                    $('.navbar').addClass('scrolled');
                } else {
                    $('.navbar').removeClass('scrolled');
                }
            });

            // Form validation
            const form = document.getElementById('loginForm');
            const password = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            const loginButton = document.getElementById('loginButton');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Toggle password visibility
            passwordToggle.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                passwordToggle.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
            });
            
            // Validate form on submit
            form.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Check email
                const email = document.getElementById('email');
                const emailPattern = /^[^@]+@[^@]+\.[^@]+$/;
                if (!emailPattern.test(email.value)) {
                    email.classList.add('is-invalid');
                    isValid = false;
                } else {
                    email.classList.remove('is-invalid');
                }
                
                // Check password
                if (password.value.length === 0) {
                    password.classList.add('is-invalid');
                    isValid = false;
                } else {
                    password.classList.remove('is-invalid');
                }
                
                if (isValid) {
                    // Show loading overlay
                    loadingOverlay.style.display = 'flex';
                    loginButton.disabled = true;
                    loginButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Logging in...';
                } else {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Scroll to the first error
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
            
            // Clear validation on input
            const inputs = form.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                    if (this.type === 'checkbox') return;
                    
                    if (this.value.trim() !== '') {
                        this.classList.add('is-valid');
                    } else {
                        this.classList.remove('is-valid');
                    }
                });
            });
        });
    </script>
</body>
</html>