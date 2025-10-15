<?php
// Start session and include necessary files
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/homewareontap/includes/functions.php';

// Enable error reporting (for debugging; remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if user is already logged in
if (is_logged_in()) {
    header("Location: " . SITE_URL);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - HomewareOnTap</title>
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
        
        /* Register Hero */
        .register-hero {
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
        
        /* Register Container */
        .register-container {
            max-width: 600px;
            margin: -80px auto 3rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 2;
            overflow: hidden;
        }
        
        .register-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2.5rem;
            text-align: center;
            position: relative;
        }
        
        .register-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }
        
        .register-header h2 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            position: relative;
        }
        
        .register-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
        }
        
        .register-body {
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
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
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
        
        .progress {
            height: 6px;
            margin-top: 5px;
            border-radius: 3px;
        }
        
        .password-strength {
            font-size: 0.875rem;
            margin-top: 5px;
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
            .register-hero {
                min-height: 30vh;
                padding: 60px 0;
                background-attachment: scroll;
            }
            
            .register-container {
                margin: -60px 1rem 2rem;
            }
            
            .register-header {
                padding: 2rem 1.5rem;
            }
            
            .register-header h2 {
                font-size: 1.8rem;
            }
            
            .register-body {
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
            
            .register-header h2 {
                font-size: 1.5rem;
            }
            
            .register-header p {
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
        
        .feature-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            height: 100%;
            border: 1px solid #f0f0f0;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.8rem;
        }
        
        .benefits-section {
            background: var(--light);
            padding: 4rem 0;
            margin-top: 3rem;
        }

        /* Email verification notice */
        .verification-notice {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 4px solid var(--info);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        /* Password requirements */
        .password-requirements {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }

        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
        }

        .requirement i {
            font-size: 0.75rem;
            margin-right: 5px;
        }

        .requirement.met {
            color: var(--success);
        }

        .requirement.unmet {
            color: #6c757d;
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
            <h4>Creating Your Account</h4>
            <p class="mb-0">Please wait while we set up your account...</p>
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
                    <a href="<?php echo SITE_URL; ?>/pages/auth/login.php">Login</a>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/register.php" class="fw-bold">Register</a>
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

    <!-- Register Hero Section -->
    <section class="register-hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <h1 class="mb-4" style="animation: fadeInDown 1s ease;">Join HomewareOnTap</h1>
                    <p class="lead mb-0" style="animation: fadeInUp 1s ease;">Create your account and discover premium home essentials</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Form -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="register-container">
                    <div class="register-header">
                        <h2>Create Your Account</h2>
                        <p>Join thousands of satisfied customers</p>
                    </div>
                    
                    <div class="register-body">
                        <!-- Messages -->
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?>">
                                <?php 
                                    echo $_SESSION['message']; 
                                    unset($_SESSION['message']);
                                    unset($_SESSION['message_type']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <!-- Email Verification Notice -->
                        <div class="verification-notice">
                            <h5><i class="fas fa-envelope-open-text me-2"></i>Email Verification Required</h5>
                            <p class="mb-0">After registration, you'll receive an email with a verification link. Please check your inbox (and spam folder) to activate your account.</p>
                        </div>

                        <form action="<?php echo SITE_URL; ?>/pages/auth/register-process.php" method="POST" id="registrationForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-group floating-label">
                                        <input type="text" class="form-control" id="first_name" name="first_name" placeholder=" " 
                                               minlength="2" maxlength="50" pattern="[a-zA-ZÀ-ÿ' -]+" required>
                                        <label for="first_name">First Name *</label>
                                        <div class="invalid-feedback">Please provide a valid first name (2-50 characters, letters, spaces, apostrophes, and hyphens only)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group floating-label">
                                        <input type="text" class="form-control" id="last_name" name="last_name" placeholder=" " 
                                               minlength="2" maxlength="50" pattern="[a-zA-ZÀ-ÿ' -]+" required>
                                        <label for="last_name">Last Name *</label>
                                        <div class="invalid-feedback">Please provide a valid last name (2-50 characters, letters, spaces, apostrophes, and hyphens only)</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-group floating-label">
                                    <input type="email" class="form-control" id="email" name="email" placeholder=" " 
                                           pattern="[^@]+@[^@]+\.[^@]+" maxlength="100" required>
                                    <label for="email">Email Address *</label>
                                    <div class="invalid-feedback">Please provide a valid email address</div>
                                    <div class="form-text">We'll never share your email with anyone else.</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <div class="form-group floating-label flex-grow-1">
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder=" " 
                                               pattern="^(\+?[0-9]{9,15})$" maxlength="20">
                                        <label for="phone">Phone Number (Optional)</label>
                                        <div class="invalid-feedback">Please provide a valid phone number (9-15 digits, optional + at start)</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-group floating-label">
                                    <input type="password" class="form-control" id="password" name="password" placeholder=" " 
                                           minlength="8" maxlength="255" pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{8,}$" required>
                                    <label for="password">Password *</label>
                                    <span class="password-toggle" id="passwordToggle"><i class="bi bi-eye"></i></span>
                                    <div class="invalid-feedback">Password must be at least 8 characters with letters and numbers</div>
                                    
                                    <!-- Password Strength Meter -->
                                    <div class="progress mt-2 d-none" id="passwordStrengthBar">
                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="form-text text-muted password-strength" id="passwordStrengthText">
                                        Password strength: None
                                    </small>
                                    
                                    <!-- Password Requirements -->
                                    <div class="password-requirements mt-2">
                                        <div class="requirement unmet" id="reqLength">
                                            <i class="fas fa-circle"></i> At least 8 characters
                                        </div>
                                        <div class="requirement unmet" id="reqLetter">
                                            <i class="fas fa-circle"></i> At least one letter
                                        </div>
                                        <div class="requirement unmet" id="reqNumber">
                                            <i class="fas fa-circle"></i> At least one number
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-group floating-label">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder=" " required>
                                    <label for="confirm_password">Confirm Password *</label>
                                    <span class="password-toggle" id="confirmPasswordToggle"><i class="bi bi-eye"></i></span>
                                    <div class="invalid-feedback">Passwords do not match</div>
                                    <div class="valid-feedback">Passwords match!</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter" checked>
                                    <label class="form-check-label" for="newsletter">
                                        Yes, I want to receive exclusive offers and home decor tips via email
                                    </label>
                                </div>
                            </div>

                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                                <label class="form-check-label" for="agree_terms">
                                    I agree to the <a href="<?php echo SITE_URL; ?>/pages/static/terms.php" target="_blank" class="text-decoration-none">Terms and Conditions</a> and <a href="<?php echo SITE_URL; ?>/pages/static/privacy.php" target="_blank" class="text-decoration-none">Privacy Policy</a> *
                                </label>
                                <div class="invalid-feedback">You must agree to the terms and conditions</div>
                            </div>

                            <button type="submit" class="btn btn-primary pulse mb-4" id="registerButton">
                                <i class="fas fa-user-plus me-2"></i> Create Account
                            </button>
                        </form>

                        <div class="login-link">
                            <p>Already have an account? <a href="<?php echo SITE_URL; ?>/pages/auth/login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Benefits Section -->
    <section class="benefits-section">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-12">
                    <h2>Why Join HomewareOnTap?</h2>
                    <p class="lead">Discover the benefits of being a member</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h5>Free Shipping</h5>
                        <p>Enjoy free shipping on orders over R1000</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <h5>Exclusive Discounts</h5>
                        <p>Get member-only deals and early access to sales</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h5>Order Tracking</h5>
                        <p>Track your orders and manage your purchases easily</p>
                    </div>
                </div>
            </div>
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

            const form = document.getElementById('registrationForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const registerButton = document.getElementById('registerButton');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Password visibility toggle
            function toggleVisibility(toggle, field) {
                toggle.addEventListener('click', function() {
                    const type = field.type === 'password' ? 'text' : 'password';
                    field.type = type;
                    toggle.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
                });
            }

            toggleVisibility(document.getElementById('passwordToggle'), password);
            toggleVisibility(document.getElementById('confirmPasswordToggle'), confirmPassword);

            // Password strength indicator and requirement checker
            function checkPasswordStrength(password) {
                let strength = 0;
                const strengthBar = document.getElementById('passwordStrengthBar');
                const strengthText = document.getElementById('passwordStrengthText');
                
                // Check requirements
                const hasLength = password.length >= 8;
                const hasLetter = /[a-zA-Z]/.test(password);
                const hasNumber = /\d/.test(password);
                
                // Update requirement indicators
                document.getElementById('reqLength').className = hasLength ? 'requirement met' : 'requirement unmet';
                document.getElementById('reqLetter').className = hasLetter ? 'requirement met' : 'requirement unmet';
                document.getElementById('reqNumber').className = hasNumber ? 'requirement met' : 'requirement unmet';
                
                if (hasLength) strength++;
                if (hasLetter) strength++;
                if (hasNumber) strength++;
                
                const strengthPercent = (strength / 3) * 100;
                let strengthLabel = '';
                let barColor = '';
                
                switch(strength) {
                    case 0:
                    case 1:
                        strengthLabel = 'Weak';
                        barColor = 'bg-danger';
                        break;
                    case 2:
                        strengthLabel = 'Fair';
                        barColor = 'bg-warning';
                        break;
                    case 3:
                        strengthLabel = 'Strong';
                        barColor = 'bg-success';
                        break;
                }
                
                if (password.length > 0) {
                    strengthBar.classList.remove('d-none');
                    strengthBar.querySelector('.progress-bar').style.width = strengthPercent + '%';
                    strengthBar.querySelector('.progress-bar').className = 'progress-bar ' + barColor;
                    strengthText.textContent = 'Password strength: ' + strengthLabel;
                } else {
                    strengthBar.classList.add('d-none');
                    strengthText.textContent = 'Password strength: None';
                }
                
                return strength === 3;
            }
            
            password.addEventListener('input', function() {
                const isStrong = checkPasswordStrength(this.value);
                
                // Update password field validity
                if (isStrong) {
                    password.classList.remove('is-invalid');
                    password.classList.add('is-valid');
                } else {
                    password.classList.remove('is-valid');
                    if (this.value.length > 0) {
                        password.classList.add('is-invalid');
                    }
                }
                
                // Real-time confirmation validation
                if (confirmPassword.value.length > 0) {
                    if (confirmPassword.value !== this.value) {
                        confirmPassword.classList.add('is-invalid');
                        confirmPassword.classList.remove('is-valid');
                    } else {
                        confirmPassword.classList.remove('is-invalid');
                        confirmPassword.classList.add('is-valid');
                    }
                }
            });
            
            confirmPassword.addEventListener('input', function() {
                if (this.value !== password.value) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                }
            });

            // Phone validation
            const phoneInput = document.getElementById('phone');
            phoneInput.addEventListener('input', function() {
                const phonePattern = /^\+?[0-9]{9,15}$/;
                if (this.value && !phonePattern.test(this.value.replace(/\s/g, ''))) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                } else if (this.value) {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.remove('is-valid');
                }
            });
            
            // Form submission
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Check required fields
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (field.type === 'checkbox') {
                        if (!field.checked) {
                            field.classList.add('is-invalid');
                            valid = false;
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    } else {
                        if (field.value.trim() === '') {
                            field.classList.add('is-invalid');
                            valid = false;
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    }
                });
                
                // Check email format
                const email = document.getElementById('email');
                const emailPattern = /^[^@]+@[^@]+\.[^@]+$/;
                if (email.value && !emailPattern.test(email.value)) {
                    email.classList.add('is-invalid');
                    valid = false;
                }
                
                // Check name format
                const namePattern = /^[a-zA-ZÀ-ÿ' -]+$/;
                const firstName = document.getElementById('first_name');
                const lastName = document.getElementById('last_name');
                
                if (firstName.value && !namePattern.test(firstName.value)) {
                    firstName.classList.add('is-invalid');
                    valid = false;
                }
                
                if (lastName.value && !namePattern.test(lastName.value)) {
                    lastName.classList.add('is-invalid');
                    valid = false;
                }
                
                // Check phone format if provided
                const phonePattern = /^\+?[0-9]{9,15}$/;
                if (phoneInput.value && !phonePattern.test(phoneInput.value.replace(/\s/g, ''))) {
                    phoneInput.classList.add('is-invalid');
                    valid = false;
                }
                
                // Check password strength
                if (!checkPasswordStrength(password.value)) {
                    password.classList.add('is-invalid');
                    valid = false;
                }
                
                // Check password match
                if (confirmPassword.value !== password.value) {
                    confirmPassword.classList.add('is-invalid');
                    valid = false;
                }
                
                if (!valid) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Scroll to the first error
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    // Show loading
                    loadingOverlay.style.display = 'flex';
                    registerButton.disabled = true;
                    registerButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Creating Account...';
                }
            });
            
            // Clear validation on input
            const inputs = form.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    this.classList.remove('is-invalid');
                    if (this.type === 'checkbox') return;
                    
                    if (this.value.trim() !== '') {
                        // Don't automatically add valid class for password until it meets requirements
                        if (this.id === 'password') {
                            if (checkPasswordStrength(this.value)) {
                                this.classList.add('is-valid');
                            } else {
                                this.classList.remove('is-valid');
                            }
                        } else {
                            this.classList.add('is-valid');
                        }
                    } else {
                        this.classList.remove('is-valid');
                    }
                });
            });
        });
    </script>
</body>
</html>