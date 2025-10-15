<?php
// includes/logout.php
// Handle user logout and session destruction

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once 'config.php';

// Store user info for potential feedback message
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Determine redirect URL
$redirect_url = SITE_URL . '/pages/auth/login.php?logout=success';

// If there was a specific redirect requested (like from admin), use it
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $redirect_url = $_GET['redirect'] . '?logout=success';
}

// If user was admin, redirect to admin login
if (isset($_GET['admin']) && $_GET['admin'] == '1') {
    $redirect_url = SITE_URL . '/admin/index.php?logout=success';
}

// Set logout status for display on logout page
$logout_success = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - HomewareOnTap</title>
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
        
        /* Logout Hero */
        .logout-hero {
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
        
        /* Logout Container */
        .logout-container {
            max-width: 500px;
            margin: -80px auto 3rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 2;
            overflow: hidden;
        }
        
        .logout-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2.5rem;
            text-align: center;
            position: relative;
        }
        
        .logout-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }
        
        .logout-header h2 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            position: relative;
        }
        
        .logout-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
        }
        
        .logout-body {
            padding: 2.5rem;
            text-align: center;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 14px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(166, 123, 91, 0.3);
            font-size: 1.1rem;
            margin: 0 10px 10px;
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
            margin: 0 10px 10px;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: none;
            padding: 1rem 1.5rem;
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 1.5rem;
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
            .logout-hero {
                min-height: 30vh;
                padding: 60px 0;
                background-attachment: scroll;
            }
            
            .logout-container {
                margin: -60px 1rem 2rem;
            }
            
            .logout-header {
                padding: 2rem 1.5rem;
            }
            
            .logout-header h2 {
                font-size: 1.8rem;
            }
            
            .logout-body {
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
            
            .logout-header h2 {
                font-size: 1.5rem;
            }
            
            .logout-header p {
                font-size: 1rem;
            }
            
            .btn-primary, .btn-outline-primary {
                display: block;
                width: 100%;
                margin: 10px 0;
            }
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
                        <a href="tel:+27698788382"><i class="fas fa-phone me-2"></i> +27698788382</a>
                        <a href="mailto:homewareontap@gmail.com"><i class="fas fa-envelope me-2"></i> homewareontap@gmail.com</a>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/homewareontap/pages/static/track-order.php">Track Order</a>
                    <a href="/homewareontap/pages/static/faqs.php">FAQ</a>
                    <a href="<?php echo SITE_URL; ?>/pages/auth/login.php">Login</a>
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

    <!-- Logout Hero Section -->
    <section class="logout-hero">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <h1 class="mb-4" style="animation: fadeInDown 1s ease;">Goodbye</h1>
                    <p class="lead mb-0" style="animation: fadeInUp 1s ease;">You have been successfully logged out</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Logout Message -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="logout-container">
                    <div class="logout-header">
                        <h2>Logged Out Successfully</h2>
                        <p>Thank you for visiting HomewareOnTap</p>
                    </div>
                    
                    <div class="logout-body">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        
                        <h4 class="mb-3">You're Now Logged Out</h4>
                        <p class="mb-4">
                            You have been successfully logged out of your account. 
                            We hope to see you again soon!
                        </p>
                        
                        <div class="d-flex flex-wrap justify-content-center">
                            <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i> Login Again
                            </a>
                            <a href="<?php echo SITE_URL; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-home me-2"></i> Back to Home
                            </a>
                        </div>
                        
                        <div class="mt-4">
                            <p class="text-muted small">
                                For security reasons, we recommend closing your browser if you're on a shared device.
                            </p>
                        </div>
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

            // Auto-redirect to login page after 10 seconds
            setTimeout(function() {
                window.location.href = '<?php echo $redirect_url; ?>';
            }, 10000);
        });
    </script>
</body>
</html>