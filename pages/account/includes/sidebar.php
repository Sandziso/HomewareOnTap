<?php
// pages/account/includes/sidebar.php
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost/homewareontap');
}

// Determine active page
$current_page = basename($_SERVER['PHP_SELF']);

function isUserMenuActive($page) {
    global $current_page;
    // Check for "active" class
    return $current_page === $page ? 'active' : '';
}

// Get user stats for sidebar display
if (isset($user) && isset($user['id'])) {
    try {
        require_once '../../includes/database.php';
        $db = new Database();
        $pdo = $db->getConnection();
        
        // Get wishlist count
        $wishlistStmt = $pdo->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id");
        $wishlistStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
        $wishlistStmt->execute();
        $wishlistCount = $wishlistStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Get unread notifications count
        $notificationStmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_notifications WHERE user_id = :user_id AND is_read = 0");
        $notificationStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
        $notificationStmt->execute();
        $notificationCount = $notificationStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Get pending orders count
        $pendingOrdersStmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id AND status IN ('pending', 'processing')");
        $pendingOrdersStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
        $pendingOrdersStmt->execute();
        $pendingOrdersCount = $pendingOrdersStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Get total orders count for stats
        $totalOrdersStmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id");
        $totalOrdersStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
        $totalOrdersStmt->execute();
        $totalOrdersCount = $totalOrdersStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Sidebar stats error: " . $e->getMessage());
        $wishlistCount = 0;
        $notificationCount = 0;
        $pendingOrdersCount = 0;
        $totalOrdersCount = 0;
    }
} else {
    $wishlistCount = 0;
    $notificationCount = 0;
    $pendingOrdersCount = 0;
    $totalOrdersCount = 0;
}
?>

<aside class="user-sidebar" id="userSidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <i class="fas fa-home"></i>
            </div>
            <div class="brand-text">
                <h3>Homeware<span>OnTap</span></h3>
                <p>Customer Portal</p>
            </div>
        </div>
        <button class="sidebar-close" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="user-quick-info">
        <div class="user-avatar-sidebar">
            <?php 
            function getSidebarInitials($name) {
                $names = explode(' ', $name);
                $initials = '';
                foreach ($names as $n) {
                    $initials .= strtoupper(substr($n, 0, 1));
                }
                return substr($initials, 0, 2);
            }
            echo getSidebarInitials($user['name'] ?? 'Guest'); 
            ?>
        </div>
        <div class="user-details-sidebar">
            <h5><?php echo htmlspecialchars($user['name'] ?? 'Guest User'); ?></h5>
            <span>Member since <?php echo date('M Y', strtotime($user['created_at'] ?? 'now')); ?></span>
            
            <!-- Notification Bell -->
            <div class="sidebar-notification">
                <i class="fas fa-bell notification-bell"></i>
                <?php if ($notificationCount > 0): ?>
                <span class="notification-badge"><?php echo $notificationCount; ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-links">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo isUserMenuActive('dashboard.php'); ?>">
                    <div class="nav-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <span class="nav-text">Dashboard</span>
                    <div class="nav-indicator"></div>
                </a>
            </li>

            <li class="nav-item">
                <a href="orders.php" class="nav-link <?php echo isUserMenuActive('orders.php'); ?>">
                    <div class="nav-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <span class="nav-text">My Orders</span>
                    <?php if ($pendingOrdersCount > 0): ?>
                    <span class="order-count"><?php echo $pendingOrdersCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="addresses.php" class="nav-link <?php echo isUserMenuActive('addresses.php'); ?>">
                    <div class="nav-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <span class="nav-text">My Addresses</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="wishlist.php" class="nav-link <?php echo isUserMenuActive('wishlist.php'); ?>">
                    <div class="nav-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <span class="nav-text">My Wishlist</span>
                    <?php if ($wishlistCount > 0): ?>
                    <span class="wishlist-count"><?php echo $wishlistCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <!-- Enhanced Menu Items -->
            <li class="nav-item">
                <a href="reviews.php" class="nav-link <?php echo isUserMenuActive('reviews.php'); ?>">
                    <div class="nav-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <span class="nav-text">My Reviews</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="payment-methods.php" class="nav-link <?php echo isUserMenuActive('payment-methods.php'); ?>">
                    <div class="nav-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <span class="nav-text">Payment Methods</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="settings.php" class="nav-link <?php echo isUserMenuActive('settings.php'); ?>">
                    <div class="nav-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <span class="nav-text">Account Settings</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="<?php echo SITE_URL; ?>/pages/account/shop.php" class="nav-link">
                    <div class="nav-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <span class="nav-text">Continue Shopping</span>
                    <div class="nav-badge">New</div>
                </a>
            </li>
        </ul>

        <!-- Enhanced Quick Stats Summary -->
        <div class="sidebar-stats">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $totalOrdersCount; ?></span>
                    <span class="stat-label">Total Orders</span>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-info">
                    <span class="stat-number"><?php echo $wishlistCount; ?></span>
                    <span class="stat-label">Wishlist Items</span>
                </div>
            </div>
        </div>

        <div class="sidebar-support">
            <div class="support-header">
                <i class="fas fa-headset"></i>
                <h6>Need Help?</h6>
            </div>
            <p>Our support team is here to help you</p>
            <a href="<?php echo SITE_URL; ?>/pages/static/contact.php" class="support-btn">
                <i class="fas fa-envelope"></i>
                Contact Support
            </a>
        </div>

        <div class="sidebar-footer">
            <a href="../auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
/* Enhanced User Sidebar Styles */
:root {
    --primary: #A67B5B;
    --primary-light: #C8A27A;
    --primary-dark: #8B6145;
    --secondary: #F2E8D5;
    --light: #F9F5F0;
    --dark: #3A3229;
    --success: #27ae60;
    --warning: #f39c12;
    --danger: #e74c3c;
    --info: #3498db;
    --gradient-primary: linear-gradient(135deg, var(--primary), var(--primary-dark));
    --shadow-light: 0 4px 15px rgba(0, 0, 0, 0.05);
    --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.1);
    --shadow-hover: 0 12px 30px rgba(0, 0, 0, 0.15);
}

.user-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 300px;
    height: 100vh;
    background: linear-gradient(180deg, var(--dark) 0%, #4e4034 100%);
    color: white;
    z-index: 1000;
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: var(--shadow-medium);
    display: flex;
    flex-direction: column;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.user-sidebar.active {
    transform: translateX(0);
}

/* Enhanced Sidebar Header */
.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: rgba(0, 0, 0, 0.2);
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.brand-logo {
    width: 45px;
    height: 45px;
    background: var(--gradient-primary);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 4px 10px rgba(166, 123, 91, 0.3);
    transition: all 0.3s ease;
}

.brand-logo:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 15px rgba(166, 123, 91, 0.4);
}

.brand-text h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.brand-text h3 span {
    color: var(--primary);
    font-weight: 800;
}

.brand-text p {
    margin: 0;
    font-size: 0.8rem;
    color: var(--light);
    opacity: 0.8;
    font-weight: 500;
}

.sidebar-close {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    font-size: 1rem;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    display: none;
    backdrop-filter: blur(10px);
}

.sidebar-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

@media (max-width: 991.98px) {
    .sidebar-close {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

/* Enhanced User Quick Info */
.user-quick-info {
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    background: rgba(0, 0, 0, 0.1);
}

.user-avatar-sidebar {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    background: var(--gradient-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    font-weight: 700;
    flex-shrink: 0;
    border: 3px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.user-avatar-sidebar:hover {
    transform: scale(1.05);
    border-color: var(--primary-light);
}

.user-details-sidebar {
    flex: 1;
}

.user-details-sidebar h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: white;
}

.user-details-sidebar span {
    font-size: 0.8rem;
    color: var(--light);
    opacity: 0.9;
    font-weight: 500;
}

/* Enhanced Notification Bell in Sidebar */
.sidebar-notification {
    position: absolute;
    top: 1.5rem;
    right: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.sidebar-notification:hover {
    transform: scale(1.1);
}

.notification-bell {
    font-size: 1.2rem;
    color: var(--warning);
    transition: all 0.3s ease;
    filter: drop-shadow(0 2px 4px rgba(243, 156, 18, 0.3));
}

.notification-bell:hover {
    color: #ffd700;
    transform: scale(1.1);
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: var(--danger);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    box-shadow: 0 2px 5px rgba(231, 76, 60, 0.5);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Enhanced Navigation Menu */
.sidebar-nav {
    flex-grow: 1;
    overflow-y: auto;
    padding-bottom: 1rem;
}

.nav-links {
    list-style: none;
    padding: 0 1rem;
    margin: 0;
}

.nav-item {
    margin-bottom: 0.5rem;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    font-weight: 600;
    backdrop-filter: blur(10px);
    border: 1px solid transparent;
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    transform: translateX(8px);
    border-color: rgba(255, 255, 255, 0.1);
}

.nav-link.active {
    background: var(--gradient-primary);
    color: white;
    box-shadow: 0 6px 15px rgba(166, 123, 91, 0.4);
    transform: translateX(5px);
    border: none;
}

.nav-icon {
    width: 25px;
    text-align: center;
    margin-right: 0.75rem;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.nav-link:hover .nav-icon {
    transform: scale(1.1);
}

.nav-text {
    flex-grow: 1;
    font-size: 0.95rem;
}

.nav-indicator {
    width: 6px;
    height: 6px;
    background: white;
    border-radius: 50%;
    margin-left: auto;
    opacity: 0;
    transition: opacity 0.3s;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}

.nav-link.active .nav-indicator {
    opacity: 1;
    animation: glow 2s infinite;
}

@keyframes glow {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.order-count, .wishlist-count {
    background: var(--danger);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
    font-weight: 700;
    min-width: 20px;
    text-align: center;
    box-shadow: 0 2px 5px rgba(231, 76, 60, 0.3);
}

.wishlist-count {
    background: var(--info);
    box-shadow: 0 2px 5px rgba(52, 152, 219, 0.3);
}

.nav-badge {
    background: var(--success);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
    font-weight: 700;
    box-shadow: 0 2px 5px rgba(39, 174, 96, 0.3);
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-2px); }
}

/* Enhanced Sidebar Stats */
.sidebar-stats {
    margin: 1.5rem;
    padding: 1.5rem;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 16px;
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.sidebar-stats:hover {
    background: rgba(0, 0, 0, 0.4);
    transform: translateY(-2px);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: scale(1.05);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: var(--primary-light);
    transition: all 0.3s ease;
}

.stat-item:hover .stat-icon {
    background: var(--gradient-primary);
    color: white;
    transform: rotate(5deg);
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-size: 1.2rem;
    font-weight: 800;
    color: white;
    line-height: 1;
}

.stat-label {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    margin-top: 2px;
    font-weight: 500;
}

/* Enhanced Support Section */
.sidebar-support {
    margin: 1.5rem;
    padding: 1.5rem;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 16px;
    text-align: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.sidebar-support:hover {
    background: rgba(0, 0, 0, 0.4);
    transform: translateY(-2px);
}

.support-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    color: var(--warning);
    margin-bottom: 0.75rem;
}

.support-header h6 {
    margin: 0;
    color: white;
    font-weight: 700;
    font-size: 1rem;
}

.sidebar-support p {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 1.25rem;
    line-height: 1.4;
}

.support-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--gradient-primary);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 10px rgba(166, 123, 91, 0.3);
}

.support-btn:hover {
    background: linear-gradient(135deg, var(--primary-dark), #7a5339);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(166, 123, 91, 0.4);
}

/* Enhanced Logout Section */
.sidebar-footer {
    padding: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.2);
}

.logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem 1.25rem;
    color: var(--danger);
    background: rgba(231, 76, 60, 0.1);
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.4s ease;
    font-weight: 600;
    border: 1px solid rgba(231, 76, 60, 0.2);
    backdrop-filter: blur(10px);
}

.logout-btn:hover {
    background: var(--danger);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(231, 76, 60, 0.3);
}

.logout-btn i {
    margin-right: 0.75rem;
    transition: transform 0.3s ease;
}

.logout-btn:hover i {
    transform: translateX(3px);
}

/* Enhanced Overlay */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s;
    backdrop-filter: blur(5px);
}

.sidebar-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* Enhanced Scrollbar Styling */
.sidebar-nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: var(--gradient-primary);
    border-radius: 3px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, var(--primary-dark), var(--primary));
}

@media (min-width: 992px) {
    .user-sidebar {
        transform: translateX(0);
    }
    .sidebar-overlay {
        display: none;
    }
}

/* Mobile Responsive Enhancements */
@media (max-width: 991.98px) {
    .user-sidebar {
        width: 280px;
    }
    
    .sidebar-stats {
        flex-direction: column;
        gap: 1rem;
        margin: 1rem;
        padding: 1.25rem;
    }
    
    .stat-item {
        justify-content: flex-start;
    }
    
    .sidebar-support, .sidebar-footer {
        margin: 1rem;
    }
}

/* Animation for notification bell */
@keyframes ringBell {
    0% { transform: rotate(0deg); }
    25% { transform: rotate(15deg); }
    50% { transform: rotate(-15deg); }
    75% { transform: rotate(10deg); }
    100% { transform: rotate(0deg); }
}

.notification-bell.ringing {
    animation: ringBell 0.5s ease-in-out;
}

/* Loading animation for sidebar */
.sidebar-loading {
    position: relative;
    overflow: hidden;
}

.sidebar-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('userSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarClose = document.getElementById('sidebarClose');
    const notificationBell = document.querySelector('.notification-bell');

    // Toggle sidebar
    document.addEventListener('toggleSidebar', function() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    });

    // Close sidebar
    if (sidebarClose) {
        sidebarClose.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // Close sidebar when clicking nav links on mobile
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Enhanced Notification bell functionality
    if (notificationBell) {
        notificationBell.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Add ringing animation
            this.classList.add('ringing');
            setTimeout(() => {
                this.classList.remove('ringing');
            }, 500);
            
            // Redirect to notifications page
            window.location.href = 'notifications.php';
        });
    }

    // Enhanced sidebar interactions
    const menuItems = document.querySelectorAll('.nav-link');
    menuItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            if (window.innerWidth >= 992) {
                this.style.transform = 'translateX(8px)';
            }
        });
        
        item.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active') && window.innerWidth >= 992) {
                this.style.transform = 'translateX(0)';
            } else if (this.classList.contains('active') && window.innerWidth >= 992) {
                this.style.transform = 'translateX(5px)';
            }
        });
    });

    // Auto-update notification count every 30 seconds
    function updateNotificationCount() {
        fetch('ajax/get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.querySelector('.notification-badge');
                    if (data.count > 0) {
                        if (badge) {
                            badge.textContent = data.count;
                        } else {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'notification-badge';
                            newBadge.textContent = data.count;
                            document.querySelector('.sidebar-notification').appendChild(newBadge);
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                }
            })
            .catch(error => console.error('Error updating notification count:', error));
    }

    // Update notification count every 30 seconds
    setInterval(updateNotificationCount, 30000);

    // Add loading state to links
    const sidebarLinks = document.querySelectorAll('.sidebar-nav a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') && !this.getAttribute('href').startsWith('#')) {
                const navIcon = this.querySelector('.nav-icon');
                if (navIcon) {
                    const originalIcon = navIcon.innerHTML;
                    navIcon.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    
                    // Reset icon after 2 seconds (in case page doesn't load)
                    setTimeout(() => {
                        navIcon.innerHTML = originalIcon;
                    }, 2000);
                }
            }
        });
    });

    // Add hover effects to stat items
    const statItems = document.querySelectorAll('.stat-item');
    statItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Initialize sidebar with loading animation
    setTimeout(() => {
        sidebar.classList.remove('sidebar-loading');
    }, 500);
});

// Enhanced mobile detection
function isMobile() {
    return window.innerWidth < 992;
}

// Resize handler
window.addEventListener('resize', function() {
    if (window.innerWidth >= 992) {
        document.body.style.overflow = '';
    }
});
</script>