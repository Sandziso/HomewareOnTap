<?php
// File: pages/account/dashboard.php

// Start session and include necessary files
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/session.php';

// Redirect if user is not logged in
if (!$sessionManager->isLoggedIn()) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit;
}

// Get user details from session
if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    $user = $_SESSION['user'];
    $userId = $user['id'] ?? 0;
} else {
    // Fallback for older session format
    $user = [
        'id' => $_SESSION['user_id'] ?? 0,
        'name' => $_SESSION['user_name'] ?? 'Guest User',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'created_at' => $_SESSION['user_created_at'] ?? date('Y-m-d H:i:s')
    ];
    $userId = $user['id'];
    $_SESSION['user'] = $user;
}

// If user ID is still 0, redirect to login
if ($userId === 0) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit;
}

// Initialize database connection
$db = new Database();
$pdo = $db->getConnection();

// Get recent orders and stats 
$recentOrders = [];
$orderStats = [
    'total_orders' => 0,
    'processing_orders' => 0,
    'shipped_orders' => 0,
    'delivered_orders' => 0
];

try {
    // Fetch recent orders (for topbar notifications and dashboard list)
    $ordersQuery = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY order_date DESC LIMIT 5";
    $ordersStmt = $pdo->prepare($ordersQuery);
    $ordersStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $ordersStmt->execute();
    $recentOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get order counts for stats
    $orderCountQuery = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
                        FROM orders WHERE user_id = :user_id";
    $orderCountStmt = $pdo->prepare($orderCountQuery);
    $orderCountStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $orderCountStmt->execute();
    $orderStats = $orderCountStmt->fetch(PDO::FETCH_ASSOC) ?: $orderStats;
    
} catch (Exception $e) {
    error_log("Dashboard orders query error: " . $e->getMessage());
}

// Get enhanced user data
try {
    // Get wishlist count
    $wishlistQuery = "SELECT COUNT(*) as wishlist_count FROM wishlist WHERE user_id = :user_id";
    $wishlistStmt = $pdo->prepare($wishlistQuery);
    $wishlistStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $wishlistStmt->execute();
    $wishlistCount = $wishlistStmt->fetch(PDO::FETCH_ASSOC)['wishlist_count'] ?? 0;

    // Get total spent
    $spentQuery = "SELECT COALESCE(SUM(total_amount), 0) as total_spent FROM orders WHERE user_id = :user_id AND status IN ('delivered', 'completed')";
    $spentStmt = $pdo->prepare($spentQuery);
    $spentStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $spentStmt->execute();
    $totalSpent = $spentStmt->fetch(PDO::FETCH_ASSOC)['total_spent'] ?? 0;

    // Get loyalty points if available
    $loyaltyQuery = "SELECT points_balance FROM loyalty_points WHERE user_id = :user_id";
    $loyaltyStmt = $pdo->prepare($loyaltyQuery);
    $loyaltyStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $loyaltyStmt->execute();
    $loyaltyPoints = $loyaltyStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get saved addresses count
    $addressQuery = "SELECT COUNT(*) as address_count FROM addresses WHERE user_id = :user_id";
    $addressStmt = $pdo->prepare($addressQuery);
    $addressStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $addressStmt->execute();
    $addressCount = $addressStmt->fetch(PDO::FETCH_ASSOC)['address_count'] ?? 0;
    
} catch (Exception $e) {
    error_log("Dashboard enhanced stats error: " . $e->getMessage());
    $wishlistCount = 0;
    $totalSpent = 0;
    $loyaltyPoints = null;
    $addressCount = 0;
}

// Set page title
$pageTitle = "Dashboard - HomewareOnTap";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $pageTitle; ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
    /* Global Styles for User Dashboard (Independent Version) */
    :root {
        --primary: #A67B5B; /* Brown/Tan */
        --primary-light: #C8A27A;
        --primary-dark: #8B6145;
        --secondary: #F2E8D5;
        --light: #F9F5F0;
        --dark: #3A3229;
        --success: #27ae60;
        --info: #3498db;
        --warning: #f39c12;
        --danger: #e74c3c;
        --gradient-primary: linear-gradient(135deg, var(--primary), var(--primary-dark));
        --gradient-success: linear-gradient(135deg, var(--success), #219653);
        --gradient-info: linear-gradient(135deg, var(--info), #2980b9);
        --gradient-warning: linear-gradient(135deg, var(--warning), #e67e22);
        --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.05);
        --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.1);
        --shadow-hover: 0 12px 30px rgba(0, 0, 0, 0.15);
    }

    body {
        background-color: var(--light);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        color: var(--dark);
    }
    
    .dashboard-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex-grow: 1;
        transition: margin-left 0.3s ease;
        min-height: 100vh;
        margin-left: 0;
    }

    @media (min-width: 992px) {
        .main-content {
            margin-left: 280px;
        }
    }

    .content-area {
        padding: 1.5rem;
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
    }

    /* Enhanced Card Styles */
    .card-dashboard {
        background: white;
        border-radius: 16px;
        box-shadow: var(--shadow-light);
        border: none;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        overflow: hidden;
        height: 100%;
    }

    .card-dashboard:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-hover);
    }
    
    .card-dashboard .card-header {
        background: white;
        border-bottom: 1px solid var(--secondary);
        padding: 1.25rem 1.5rem;
        font-weight: 600;
        color: var(--dark);
        font-size: 1.1rem;
        display: flex;
        align-items: center;
    }
    
    .card-dashboard .card-body {
        padding: 1.5rem;
    }

    /* Button styles */
    .btn-primary { 
        background: var(--gradient-primary);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(166, 123, 91, 0.3);
    } 
    
    .btn-primary:hover { 
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(166, 123, 91, 0.4);
        background: linear-gradient(135deg, var(--primary-dark), #7a5339);
    }

    /* Status badges */
    .status-badge {
        padding: 0.375rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: capitalize;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .status-pending { background: rgba(243, 156, 18, 0.15); color: var(--warning); border: 1px solid rgba(243, 156, 18, 0.3); } 
    .status-processing { background: rgba(52, 152, 219, 0.15); color: var(--info); border: 1px solid rgba(52, 152, 219, 0.3); }
    .status-shipped { background: rgba(39, 174, 96, 0.15); color: var(--success); border: 1px solid rgba(39, 174, 96, 0.3); }
    .status-delivered { background: rgba(166, 123, 91, 0.15); color: var(--primary); border: 1px solid rgba(166, 123, 91, 0.3); } 
    .status-cancelled { background: rgba(231, 76, 60, 0.15); color: var(--danger); border: 1px solid rgba(231, 76, 60, 0.3); }

    /* INDEPENDENT Stat Cards - Completely New Design */
    .dashboard-stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        transition: all 0.4s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-light);
        border: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 1rem;
        min-height: 120px;
    }
    
    .dashboard-stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient-primary);
    }
    
    .dashboard-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-hover);
    }

    .dashboard-stat-icon {
        width: 70px;
        height: 70px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .dashboard-stat-card:hover .dashboard-stat-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .dashboard-stat-icon.primary { 
        background: rgba(166, 123, 91, 0.1);
        color: var(--primary); 
    } 
    .dashboard-stat-icon.success { 
        background: rgba(39, 174, 96, 0.1); 
        color: var(--success); 
    } 
    .dashboard-stat-icon.info { 
        background: rgba(52, 152, 219, 0.1); 
        color: var(--info); 
    } 
    .dashboard-stat-icon.warning { 
        background: rgba(243, 156, 18, 0.1); 
        color: var(--warning); 
    }
    .dashboard-stat-icon.danger { 
        background: rgba(231, 76, 60, 0.1); 
        color: var(--danger); 
    }

    .dashboard-stat-content {
        flex: 1;
    }

    .dashboard-stat-number {
        font-size: 2rem;
        font-weight: 800;
        color: var(--dark);
        line-height: 1;
        margin-bottom: 0.25rem;
        display: block;
    }

    .dashboard-stat-label {
        color: var(--dark);
        opacity: 0.8;
        font-size: 0.9rem;
        font-weight: 600;
        display: block;
        margin: 0;
    }

    /* Enhanced Welcome banner */
    .welcome-banner {
        background: var(--gradient-primary);
        color: white;
        border-radius: 20px;
        padding: 2.5rem 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-medium);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .welcome-banner::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .welcome-banner::after {
        content: '';
        position: absolute;
        bottom: -30%;
        right: 5%;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }

    .welcome-text {
        position: relative;
        z-index: 1;
    }

    .welcome-banner h2 {
        margin: 0 0 0.5rem 0;
        font-size: 2rem;
        font-weight: 800;
    }

    .welcome-banner p {
        margin: 0;
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .welcome-icon {
        position: relative;
        z-index: 1;
    }

    .welcome-icon i {
        font-size: 4rem;
        opacity: 0.2;
        transition: all 0.5s ease;
    }

    .welcome-banner:hover .welcome-icon i {
        transform: scale(1.1) rotate(5deg);
        opacity: 0.3;
    }

    /* Enhanced Recent Orders */
    .table-responsive {
        border-radius: 16px;
        border: 1px solid var(--secondary);
        overflow: hidden;
        box-shadow: var(--shadow-light);
    }
    
    .table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table thead th {
        background-color: var(--secondary);
        color: var(--dark);
        border-bottom: 2px solid var(--primary);
        padding: 1rem 1.25rem;
        font-weight: 600;
    }
    
    .table tbody td {
        padding: 1rem 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--secondary);
    }
    
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .table tbody tr:hover {
        background-color: rgba(242, 232, 213, 0.3);
    }

    /* Enhanced Account Info */
    .account-info .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid var(--secondary);
        transition: all 0.3s ease;
    }

    .account-info .info-item:hover {
        background: rgba(242, 232, 213, 0.2);
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        border-radius: 8px;
    }

    .account-info .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .info-label i {
        color: var(--primary);
        width: 20px;
    }

    .info-value {
        color: var(--dark);
        opacity: 0.8;
        text-align: right;
        font-weight: 500;
    }
    
    /* Enhanced Quick Actions Styles */
    .quick-action-link {
        display: block;
        text-decoration: none;
        color: var(--dark);
        transition: all 0.4s ease;
        padding: 1.25rem 0.75rem;
        border-radius: 12px;
        position: relative;
        overflow: hidden;
    }
    
    .quick-action-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: var(--gradient-primary);
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 0;
    }
    
    .quick-action-link:hover {
        transform: translateY(-5px);
        color: white;
    }
    
    .quick-action-link:hover::before {
        opacity: 1;
    }
    
    .quick-action-link:hover .quick-action-icon {
        background: white;
        color: var(--primary);
        transform: scale(1.1);
    }
    
    .quick-action-link:hover span {
        color: white;
    }
    
    .quick-action-icon {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        background: var(--secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.75rem;
        font-size: 1.5rem;
        color: var(--primary);
        transition: all 0.4s ease;
        position: relative;
        z-index: 1;
    }
    
    .quick-action-link span {
        font-size: 0.9rem;
        font-weight: 600;
        display: block;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
    }
    
    /* Empty state styling */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: var(--secondary);
        margin-bottom: 1.5rem;
        opacity: 0.7;
    }
    
    .empty-state h5 {
        color: var(--dark);
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    
    .empty-state p {
        color: var(--dark);
        opacity: 0.7;
        margin-bottom: 1.5rem;
    }
    
    /* Section headers */
    .section-header {
        display: flex;
        justify-content: between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark);
        margin: 0;
    }
    
    .section-action {
        color: var(--primary);
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }
    
    .section-action:hover {
        color: var(--primary-dark);
        gap: 0.75rem;
    }
    
    /* Responsive improvements */
    @media (max-width: 768px) {
        .content-area {
            padding: 1rem;
        }
        
        .welcome-banner {
            padding: 1.5rem 1.25rem;
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }
        
        .welcome-banner h2 {
            font-size: 1.75rem;
        }
        
        .welcome-icon i {
            font-size: 3rem;
        }
        
        .dashboard-stat-card {
            padding: 1.25rem;
            min-height: 100px;
        }
        
        .dashboard-stat-icon {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }
        
        .dashboard-stat-number {
            font-size: 1.75rem;
        }
        
        .quick-action-link {
            padding: 1rem 0.5rem;
        }
        
        .quick-action-icon {
            width: 50px;
            height: 50px;
            font-size: 1.25rem;
        }
        
        .table thead th,
        .table tbody td {
            padding: 0.75rem 0.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .welcome-banner h2 {
            font-size: 1.5rem;
        }
        
        .dashboard-stat-card {
            flex-direction: column;
            text-align: center;
            gap: 0.75rem;
            padding: 1.5rem 1rem;
        }
        
        .dashboard-stat-content {
            width: 100%;
        }
        
        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
    }

    /* Loading animation for cards */
    .card-loading {
        position: relative;
        overflow: hidden;
    }
    
    .card-loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: loading 1.5s infinite;
    }
    
    @keyframes loading {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    </style>
</head>
<body>
    
    <div class="dashboard-wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php require_once 'includes/topbar.php'; ?>

            <main class="content-area">
                <div class="container-fluid p-0">
                    <!-- Welcome Banner -->
                    <div class="welcome-banner">
                        <div class="welcome-text">
                            <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>! 👋</h2>
                            <p>Here's what's happening with your orders and account today.</p>
                        </div>
                        <div class="welcome-icon">
                            <i class="fas fa-home"></i>
                        </div>
                    </div>

                    <!-- Stats Overview -->
                    <div class="section-header">
                        <h2 class="section-title">Dashboard Overview</h2>
                        <a href="orders.php" class="section-action">
                            View All Orders <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <!-- First Row of Stats -->
                    <div class="row mb-4 g-3">
                        <div class="col-xl-3 col-md-6">
                            <div class="dashboard-stat-card" onclick="window.location.href='orders.php'">
                                <div class="dashboard-stat-icon primary">
                                    <i class="fas fa-box-open"></i>
                                </div>
                                <div class="dashboard-stat-content">
                                    <span class="dashboard-stat-number"><?php echo $orderStats['total_orders']; ?></span>
                                    <span class="dashboard-stat-label">Total Orders</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="dashboard-stat-card" onclick="window.location.href='orders.php?status=processing'">
                                <div class="dashboard-stat-icon info">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div class="dashboard-stat-content">
                                    <span class="dashboard-stat-number"><?php echo $orderStats['processing_orders']; ?></span>
                                    <span class="dashboard-stat-label">Processing Orders</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="dashboard-stat-card" onclick="window.location.href='orders.php?status=shipped'">
                                <div class="dashboard-stat-icon warning">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="dashboard-stat-content">
                                    <span class="dashboard-stat-number"><?php echo $orderStats['shipped_orders']; ?></span>
                                    <span class="dashboard-stat-label">Shipped Orders</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="dashboard-stat-card" onclick="window.location.href='orders.php?status=delivered'">
                                <div class="dashboard-stat-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="dashboard-stat-content">
                                    <span class="dashboard-stat-number"><?php echo $orderStats['delivered_orders']; ?></span>
                                    <span class="dashboard-stat-label">Delivered Orders</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Second Row of Stats -->
                    <div class="row mb-4 g-3">
                        <div class="col-xl-3 col-md-6">
                            <div class="dashboard-stat-card">
                                <div class="dashboard-stat-icon success">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <div class="dashboard-stat-content">
                                    <span class="dashboard-stat-number">R<?php echo number_format($totalSpent, 0); ?></span>
                                    <span class="dashboard-stat-label">Total Amount Spent</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="dashboard-stat-card">
                                <div class="dashboard-stat-icon info">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="dashboard-stat-content">
                                    <span class="dashboard-stat-number">
                                        <?php echo $loyaltyPoints ? number_format($loyaltyPoints['points_balance']) : '0'; ?>
                                    </span>
                                    <span class="dashboard-stat-label">Loyalty Points</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6">
                            <div class="dashboard-stat-card" onclick="window.location.href='wishlist.php'">
                                <div class="dashboard-stat-icon warning">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <div class="dashboard-stat-content">
                                    <span class="dashboard-stat-number"><?php echo $wishlistCount; ?></span>
                                    <span class="dashboard-stat-label">Wishlist Items</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="dashboard-stat-card" onclick="window.location.href='addresses.php'">
                                <div class="dashboard-stat-icon primary">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="dashboard-stat-content">
                                    <span class="dashboard-stat-number"><?php echo $addressCount; ?></span>
                                    <span class="dashboard-stat-label">Saved Addresses</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Area -->
                    <div class="row g-4">
                        <!-- Recent Orders -->
                        <div class="col-lg-8">
                            <div class="card-dashboard h-100">
                                <div class="card-header">
                                    <i class="fas fa-history me-2"></i> Recent Orders
                                    <span class="badge bg-primary ms-2"><?php echo count($recentOrders); ?></span>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (!empty($recentOrders)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Total</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentOrders as $order): ?>
                                                <tr data-order="<?php echo $order['id']; ?>" data-order-status="<?php echo strtolower($order['status']); ?>">
                                                    <td class="fw-bold">#<?php echo htmlspecialchars($order['id']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                            <i class="fas fa-circle fa-xs"></i>
                                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order['status']))); ?>
                                                        </span>
                                                    </td>
                                                    <td class="fw-bold">R<?php echo number_format($order['total_amount'], 2); ?></td>
                                                    <td>
                                                        <a href="orders.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer text-center bg-white border-0 pt-3 pb-3">
                                        <a href="orders.php" class="section-action">
                                            View All Orders <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-box-open"></i>
                                        <h5>No Recent Orders</h5>
                                        <p>You haven't placed any orders yet.</p>
                                        <a href="<?php echo SITE_URL; ?>/shop.php" class="btn btn-primary mt-2">Start Shopping</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions & Account Summary -->
                        <div class="col-lg-4">
                            <!-- Quick Actions -->
                            <div class="card-dashboard mb-4">
                                <div class="card-header">
                                    <i class="fas fa-bolt me-2"></i> Quick Actions
                                </div>
                                <div class="card-body text-center p-3">
                                    <div class="row row-cols-3 g-2">
                                        <div class="col">
                                            <a href="<?php echo SITE_URL; ?>/shop.php" class="quick-action-link">
                                                <div class="quick-action-icon"><i class="fas fa-store"></i></div>
                                                <span>Shop</span>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a href="wishlist.php" class="quick-action-link">
                                                <div class="quick-action-icon"><i class="fas fa-heart"></i></div>
                                                <span>Wishlist</span>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a href="orders.php" class="quick-action-link">
                                                <div class="quick-action-icon"><i class="fas fa-box"></i></div>
                                                <span>Orders</span>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a href="addresses.php" class="quick-action-link">
                                                <div class="quick-action-icon"><i class="fas fa-map-marker-alt"></i></div>
                                                <span>Addresses</span>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a href="profile.php" class="quick-action-link">
                                                <div class="quick-action-icon"><i class="fas fa-cog"></i></div>
                                                <span>Settings</span>
                                            </a>
                                        </div>
                                        <div class="col">
                                            <a href="<?php echo SITE_URL; ?>/pages/auth/logout.php" class="quick-action-link">
                                                <div class="quick-action-icon"><i class="fas fa-sign-out-alt"></i></div>
                                                <span>Logout</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Account Summary -->
                            <div class="card-dashboard">
                                <div class="card-header">
                                    <i class="fas fa-user-circle me-2"></i> Account Summary
                                </div>
                                <div class="card-body">
                                    <div class="account-info">
                                        <div class="info-item">
                                            <span class="info-label"><i class="fas fa-user"></i> Name:</span>
                                            <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label"><i class="fas fa-envelope"></i> Email:</span>
                                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label"><i class="fas fa-calendar"></i> Member Since:</span>
                                            <span class="info-value"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label"><i class="fas fa-map-marker-alt"></i> Addresses:</span>
                                            <span class="info-value"><?php echo $addressCount; ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label"><i class="fas fa-star"></i> Loyalty Points:</span>
                                            <span class="info-value">
                                                <?php echo $loyaltyPoints ? number_format($loyaltyPoints['points_balance']) : '0'; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-grid mt-4">
                                        <a href="profile.php" class="btn btn-primary">
                                            <i class="fas fa-edit me-2"></i> Edit Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Enhanced Sidebar toggle logic for mobile
        $(document).ready(function() {
            $('#sidebarToggle').on('click', function() {
                document.dispatchEvent(new Event('toggleSidebar'));
            });
            
            // Add loading animation to stat cards on page load
            $('.dashboard-stat-card').addClass('card-loading');
            setTimeout(function() {
                $('.dashboard-stat-card').removeClass('card-loading');
            }, 1000);
        });

        // Enhanced stat card interactions
        $('.dashboard-stat-card').hover(
            function() {
                $(this).find('.dashboard-stat-icon').css({
                    'transform': 'scale(1.15) rotate(5deg)',
                    'border-radius': '25%'
                });
            },
            function() {
                $(this).find('.dashboard-stat-icon').css({
                    'transform': 'scale(1) rotate(0)',
                    'border-radius': '16px'
                });
            }
        );
        
        // Enhanced quick action animations
        $('.quick-action-link').hover(
            function() {
                $(this).find('.quick-action-icon').css('transform', 'scale(1.1) rotate(5deg)');
            },
            function() {
                $(this).find('.quick-action-icon').css('transform', 'scale(1) rotate(0)');
            }
        );
        
        // Add click effects to interactive elements
        $('.dashboard-stat-card, .btn, .quick-action-link').on('click', function() {
            const $this = $(this);
            $this.css('transform', 'scale(0.95)');
            setTimeout(() => {
                $this.css('transform', '');
            }, 150);
        });
        
        // Welcome message based on time of day
        function updateWelcomeMessage() {
            const hour = new Date().getHours();
            let greeting = "Hello";
            
            if (hour < 12) greeting = "Good morning";
            else if (hour < 18) greeting = "Good afternoon";
            else greeting = "Good evening";
            
            $('.welcome-banner h2').html(`${greeting}, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>! 👋`);
        }
        
        // Update welcome message on page load
        updateWelcomeMessage();
    </script>
</body>
</html>