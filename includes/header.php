<?php
// Define root path if not defined
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__FILE__, 2));
}

// Include necessary files
require_once ROOT_PATH . '/includes/config.php';
require_once ROOT_PATH . '/includes/database.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize login variables safely
$isLoggedIn = false;
$isAdmin = false;
$userName = '';

// Check login status
if (function_exists('is_logged_in') && function_exists('is_admin')) {
    $isLoggedIn = is_logged_in();
    $isAdmin = is_admin();
    if ($isLoggedIn && isset($_SESSION['user_name'])) {
        $userName = $_SESSION['user_name'];
    } elseif ($isLoggedIn && isset($_SESSION['user']['first_name'])) {
        $userName = $_SESSION['user']['first_name'];
    }
}

// Set page title if not defined
if (!isset($pageTitle)) {
    $pageTitle = "HomewareOnTap - Beautiful Home Decor";
} else {
    $pageTitle .= " - HomewareOnTap";
}

// Check if we're in admin section
$isAdminPage = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?php echo $pageTitle; ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <link href="<?php echo SITE_URL; ?>/assets/img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <?php if ($isAdminPage): ?>
    <!-- Admin specific styles -->
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    
    <?php if (isset($pageStyles)) { echo $pageStyles; } ?>

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
            transition: color 0.3s;
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
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
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
            color: var(--dark);
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
            transition: color 0.3s;
        }
        
        .search-btn:hover {
            color: var(--primary);
        }
        
        .cart-icon, .user-icon {
            position: relative;
            font-size: 20px;
            color: var(--dark);
            margin-left: 15px;
            transition: color 0.3s;
            text-decoration: none;
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
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(166, 123, 91, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(166, 123, 91, 0.4);
        }
        
        .btn-outline-primary {
            border-color: var(--primary);
            color: var(--primary);
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .search-form {
                display: none;
            }
            
            .navbar-brand {
                font-size: 24px;
            }
            
            .top-bar {
                text-align: center;
            }
            
            .top-bar .d-flex {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>
    <?php if (!$isAdminPage): ?>
    <!-- Frontend Header -->
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
                    <a href="<?php echo SITE_URL; ?>/pages/static/track-order.php">Track Order</a>
                    <a href="<?php echo SITE_URL; ?>/pages/static/faqs.php">FAQ</a>
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo SITE_URL; ?>/pages/account/dashboard.php">My Account</a>
                        <a href="<?php echo SITE_URL; ?>/includes/logout.php">Logout</a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/pages/auth/login.php">Login</a>
                        <a href="<?php echo SITE_URL; ?>/pages/auth/register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/index.php">
                <span>Homeware</span><span>OnTap</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'shop.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/pages/shop.php">Shop</a>
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
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == '/pages/static/about.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/pages/static/about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == '/pages/static/contact.php') ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/pages/static/contact.php">Contact</a>
                    </li>
                </ul>
                
                <form class="search-form d-none d-lg-block" id="searchForm">
                    <input type="text" placeholder="Search for products...">
                    <button class="search-btn"><i class="fas fa-search"></i></button>
                </form>
                
                <div class="d-flex align-items-center">
                    <a href="<?php echo SITE_URL; ?>/pages/cart.php" class="cart-icon" id="cartIcon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge">
                            <?php
                            // Display cart item count if available
                            if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                                echo count($_SESSION['cart']);
                            } else {
                                echo '0';
                            }
                            ?>
                        </span>
                    </a>
                    <?php if ($isLoggedIn): ?>
                        <div class="dropdown">
                            <a href="#" class="user-icon dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/account/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/account/orders.php"><i class="fas fa-shopping-bag me-2"></i>Orders</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/account/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <?php if ($isAdmin): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/index.php"><i class="fas fa-cog me-2"></i>Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/pages/auth/login.php" class="user-icon d-none d-md-block">
                            <i class="fas fa-user"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php else: ?>
    <!-- Admin Header Structure -->
    <div id="adminDashboard">
        <!-- Include Sidebar -->
        <?php include_once 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Include Top Navbar -->
            <?php include_once 'top-navbar.php'; ?>
    <?php endif; ?>