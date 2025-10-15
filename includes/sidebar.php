<?php
// includes/sidebar.php - Improved Admin Sidebar

// Determine active page for highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Function to check if a menu item is active
function isActive($page, $dir = '') {
    global $current_page, $current_dir;
    
    // Check for directory match (for main section links)
    if ($dir && $current_dir === $dir) {
        return 'active';
    }
    
    // Check for file page match (for specific sub-links)
    if (!$dir && $current_page === $page) {
        return 'active';
    }
    
    // Special case for Dashboard
    if (($page === 'index.php' || $page === 'dashboard.php') && 
        ($current_page === 'index.php' || $current_page === 'dashboard.php') && 
        $current_dir === 'admin') {
        return 'active';
    }
    
    return '';
}

// Check if user is admin
if (!function_exists('is_admin') || !is_admin()) {
    header('Location: ' . SITE_URL . '/pages/auth/login.php');
    exit;
}

// Get stats for sidebar (optional)
$database = new Database();
$pdo = $database->getConnection();
$pendingOrders = 0;
$lowStockCount = 0;

if ($pdo) {
    try {
        // Get pending orders count
        $orderStmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
        $orderStmt->execute();
        $pendingOrders = $orderStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Get low stock count
        $stockStmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= stock_alert AND status = 1");
        $stockStmt->execute();
        $lowStockCount = $stockStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    } catch (PDOException $e) {
        error_log("Sidebar stats error: " . $e->getMessage());
    }
}
?>

<!-- Improved Admin Sidebar -->
<div class="admin-sidebar" id="adminSidebar">
    <!-- Brand Section -->
    <div class="admin-brand">
        <div class="brand-content">
            <h2 class="brand-title">
                <span class="brand-main">Homeware</span>
                <span class="brand-accent">OnTap</span>
            </h2>
            <p class="brand-subtitle">Admin Panel</p>
        </div>
        <button class="sidebar-close d-md-none" id="sidebarClose" aria-label="Close Sidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Quick Stats -->
    <div class="sidebar-stats">
        <div class="stat-item">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo $pendingOrders; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo $lowStockCount; ?></div>
                <div class="stat-label">Low Stock</div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <div class="admin-menu">
        <ul class="nav flex-column">
            
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('index.php') . ' ' . isActive('dashboard.php'); ?>" href="<?php echo SITE_URL; ?>/admin/index.php">
                    <i class="fas fa-tachometer-alt me-3"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            
            <!-- Products Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('', 'products'); ?> has-dropdown" data-bs-toggle="collapse" href="#productsCollapse" role="button" aria-expanded="<?php echo isActive('', 'products') ? 'true' : 'false'; ?>">
                    <i class="fas fa-box me-3"></i>
                    <span class="nav-text">Products</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </a>
                <div class="collapse <?php echo isActive('', 'products') ? 'show' : ''; ?>" id="productsCollapse">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('manage.php'); ?>" href="<?php echo SITE_URL; ?>/admin/products/manage.php">
                                <i class="fas fa-cube me-3"></i>
                                <span class="nav-text">Manage Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('categories.php'); ?>" href="<?php echo SITE_URL; ?>/admin/products/categories.php">
                                <i class="fas fa-tags me-3"></i>
                                <span class="nav-text">Categories</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('inventory.php'); ?>" href="<?php echo SITE_URL; ?>/admin/products/inventory.php">
                                <i class="fas fa-boxes me-3"></i>
                                <span class="nav-text">Inventory</span>
                                <?php if ($lowStockCount > 0): ?>
                                <span class="badge bg-warning float-end"><?php echo $lowStockCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('import-export.php'); ?>" href="<?php echo SITE_URL; ?>/admin/products/import-export.php">
                                <i class="fas fa-file-import me-3"></i>
                                <span class="nav-text">Import/Export</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            
            <!-- Orders Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('', 'orders'); ?> has-dropdown" data-bs-toggle="collapse" href="#ordersCollapse" role="button" aria-expanded="<?php echo isActive('', 'orders') ? 'true' : 'false'; ?>">
                    <i class="fas fa-shopping-cart me-3"></i>
                    <span class="nav-text">Orders</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                    <?php if ($pendingOrders > 0): ?>
                    <span class="badge bg-danger float-end"><?php echo $pendingOrders; ?></span>
                    <?php endif; ?>
                </a>
                <div class="collapse <?php echo isActive('', 'orders') ? 'show' : ''; ?>" id="ordersCollapse">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('list.php'); ?>" href="<?php echo SITE_URL; ?>/admin/orders/list.php">
                                <i class="fas fa-list me-3"></i>
                                <span class="nav-text">Order List</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('details.php'); ?>" href="<?php echo SITE_URL; ?>/admin/orders/details.php">
                                <i class="fas fa-file-invoice me-3"></i>
                                <span class="nav-text">Order Details</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('invoices.php'); ?>" href="<?php echo SITE_URL; ?>/admin/orders/invoices.php">
                                <i class="fas fa-receipt me-3"></i>
                                <span class="nav-text">Invoices</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            
            <!-- Customers Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('', 'customers'); ?> has-dropdown" data-bs-toggle="collapse" href="#customersCollapse" role="button" aria-expanded="<?php echo isActive('', 'customers') ? 'true' : 'false'; ?>">
                    <i class="fas fa-users me-3"></i>
                    <span class="nav-text">Customers</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </a>
                <div class="collapse <?php echo isActive('', 'customers') ? 'show' : ''; ?>" id="customersCollapse">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('list.php'); ?>" href="<?php echo SITE_URL; ?>/admin/customers/list.php">
                                <i class="fas fa-list me-3"></i>
                                <span class="nav-text">Customer List</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('view.php'); ?>" href="<?php echo SITE_URL; ?>/admin/customers/view.php">
                                <i class="fas fa-user me-3"></i>
                                <span class="nav-text">Customer Details</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            
            <!-- Reports Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('', 'reports'); ?> has-dropdown" data-bs-toggle="collapse" href="#reportsCollapse" role="button" aria-expanded="<?php echo isActive('', 'reports') ? 'true' : 'false'; ?>">
                    <i class="fas fa-chart-bar me-3"></i>
                    <span class="nav-text">Reports</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </a>
                <div class="collapse <?php echo isActive('', 'reports') ? 'show' : ''; ?>" id="reportsCollapse">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('sales.php'); ?>" href="<?php echo SITE_URL; ?>/admin/reports/sales.php">
                                <i class="fas fa-chart-line me-3"></i>
                                <span class="nav-text">Sales Reports</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('products.php'); ?>" href="<?php echo SITE_URL; ?>/admin/reports/products.php">
                                <i class="fas fa-chart-pie me-3"></i>
                                <span class="nav-text">Product Reports</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('export.php'); ?>" href="<?php echo SITE_URL; ?>/admin/reports/export.php">
                                <i class="fas fa-file-export me-3"></i>
                                <span class="nav-text">Export Data</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            
            <!-- Communications Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('', 'communications'); ?> has-dropdown" data-bs-toggle="collapse" href="#communicationsCollapse" role="button" aria-expanded="<?php echo isActive('', 'communications') ? 'true' : 'false'; ?>">
                    <i class="fas fa-comments me-3"></i>
                    <span class="nav-text">Communications</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </a>
                <div class="collapse <?php echo isActive('', 'communications') ? 'show' : ''; ?>" id="communicationsCollapse">
                    <ul class="nav flex-column sub-menu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('messages.php'); ?>" href="<?php echo SITE_URL; ?>/admin/communications/messages.php">
                                <i class="fas fa-envelope me-3"></i>
                                <span class="nav-text">Messages</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo isActive('notifications.php'); ?>" href="<?php echo SITE_URL; ?>/admin/communications/notifications.php">
                                <i class="fas fa-bell me-3"></i>
                                <span class="nav-text">Notifications</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Settings Section -->
            <li class="nav-item">
                <a class="nav-link <?php echo isActive('', 'settings'); ?>" href="<?php echo SITE_URL; ?>/admin/settings/site.php">
                    <i class="fas fa-cog me-3"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
            
            <!-- System Section -->
            <li class="nav-item mt-auto">
                <a class="nav-link logout-link" href="<?php echo SITE_URL; ?>/includes/logout.php">
                    <i class="fas fa-sign-out-alt me-3"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Sidebar Overlay for Mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
/* Sidebar Variables - Updated to match admin pages */
:root {
    --primary: #A67B5B;
    --secondary: #F2E8D5;
    --light: #F9F5F0;
    --dark: #3A3229;
    --sidebar-bg: #3A3229;
    --sidebar-color: #F2E8D5;
    --sidebar-hover: #A67B5B;
    --sidebar-active: #A67B5B;
    --sidebar-border: #A67B5B;
    --sidebar-width: 260px; /* Reduced from 280px */
    --sidebar-collapsed-width: 60px; /* Reduced from 70px */
}

/* Main Sidebar Styles */
.admin-sidebar {
    width: var(--sidebar-width);
    background: var(--sidebar-bg);
    color: var(--sidebar-color);
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    z-index: 1030;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 0 28px 0 rgba(82, 63, 105, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Brand Section */
.admin-brand {
    padding: 1.25rem 1.25rem 0.75rem; /* Reduced padding */
    border-bottom: 1px solid var(--sidebar-border);
    position: relative;
    background: rgba(0, 0, 0, 0.1);
}

.brand-content {
    text-align: center;
}

.brand-title {
    margin: 0;
    font-size: 1.35rem; /* Reduced from 1.5rem */
    font-weight: 700;
    line-height: 1.2;
}

.brand-main {
    color: var(--light);
}

.brand-accent {
    color: var(--primary);
}

.brand-subtitle {
    color: var(--sidebar-color);
    font-size: 0.75rem;
    margin: 0.25rem 0 0;
    opacity: 0.7;
}

.sidebar-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: transparent;
    border: none;
    color: var(--sidebar-color);
    font-size: 1.25rem;
    cursor: pointer;
    transition: color 0.3s;
}

.sidebar-close:hover {
    color: var(--light);
}

/* Quick Stats */
.sidebar-stats {
    padding: 0.75rem 1.25rem; /* Reduced padding */
    border-bottom: 1px solid var(--sidebar-border);
}

.stat-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
}

.stat-item:last-child {
    margin-bottom: 0;
}

.stat-icon {
    width: 2.25rem; /* Reduced from 2.5rem */
    height: 2.25rem; /* Reduced from 2.5rem */
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.625rem; /* Reduced from 0.75rem */
    font-size: 1rem;
}

.stat-icon.pending {
    background: rgba(255, 184, 34, 0.1);
    color: #ffb822;
}

.stat-icon.warning {
    background: rgba(253, 57, 122, 0.1);
    color: #fd397a;
}

.stat-info {
    flex: 1;
}

.stat-value {
    color: var(--light);
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1;
}

.stat-label {
    color: var(--sidebar-color);
    font-size: 0.75rem;
    margin-top: 0.125rem;
}

/* Navigation Menu */
.admin-menu {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
}

.admin-menu .nav {
    padding: 0 1rem; /* Reduced from 1.5rem */
}

.nav-item {
    margin-bottom: 0.25rem;
}

.nav-link {
    color: var(--sidebar-color);
    padding: 0.625rem 0.875rem; /* Reduced padding */
    border-radius: 0.5rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: all 0.3s;
    position: relative;
    border: none;
    background: transparent;
}

.nav-link:hover {
    color: var(--light);
    background: var(--sidebar-hover);
}

.nav-link.active {
    color: var(--light);
    background: var(--sidebar-active);
    box-shadow: 0 0 10px rgba(166, 123, 91, 0.3);
}

.nav-text {
    flex: 1;
    font-weight: 500;
}

.nav-link i:not(.dropdown-arrow) {
    width: 1.1rem; /* Slightly reduced */
    text-align: center;
    font-size: 1rem; /* Slightly reduced */
    margin-right: 0.625rem; /* Reduced from 0.75rem */
}

/* Dropdown Styles */
.has-dropdown {
    position: relative;
}

.dropdown-arrow {
    transition: transform 0.3s;
    font-size: 0.875rem;
    margin-left: auto;
}

.has-dropdown[aria-expanded="true"] .dropdown-arrow {
    transform: rotate(180deg);
}

.sub-menu {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 0.5rem;
    margin: 0.5rem 0;
    padding: 0.25rem 0;
}

.sub-menu .nav-link {
    padding: 0.5rem 0.875rem 0.5rem 2.5rem; /* Reduced padding */
    font-size: 0.9rem;
}

.sub-menu .nav-link.active {
    background: rgba(166, 123, 91, 0.2);
    color: var(--sidebar-active);
}

/* Badges */
.badge {
    font-size: 0.65rem; /* Reduced from 0.7rem */
    padding: 0.2rem 0.4rem; /* Reduced padding */
    font-weight: 600;
}

/* Logout Link */
.logout-link {
    color: #f64e60 !important;
    margin-top: 1rem;
}

.logout-link:hover {
    background: rgba(246, 78, 96, 0.1) !important;
    color: #f64e60 !important;
}

/* Scrollbar Styling */
.admin-menu::-webkit-scrollbar {
    width: 6px;
}

.admin-menu::-webkit-scrollbar-track {
    background: transparent;
}

.admin-menu::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.admin-menu::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Sidebar Overlay */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1029;
    display: none;
}

/* Responsive Styles */
@media (max-width: 991.98px) {
    .admin-sidebar {
        transform: translateX(-100%);
    }
    
    .admin-sidebar.mobile-open {
        transform: translateX(0);
    }
    
    .sidebar-overlay.show {
        display: block;
    }
}

@media (max-width: 767.98px) {
    .admin-sidebar {
        width: 100%;
        max-width: 280px;
    }
}

/* Collapsed State (Optional) */
.admin-sidebar.collapsed {
    width: var(--sidebar-collapsed-width);
}

.admin-sidebar.collapsed .brand-content,
.admin-sidebar.collapsed .nav-text,
.admin-sidebar.collapsed .sidebar-stats,
.admin-sidebar.collapsed .badge,
.admin-sidebar.collapsed .dropdown-arrow {
    display: none;
}

.admin-sidebar.collapsed .nav-link {
    justify-content: center;
    padding: 0.75rem;
}

.admin-sidebar.collapsed .nav-link i:not(.dropdown-arrow) {
    margin-right: 0;
}

.admin-sidebar.collapsed .sub-menu .nav-link {
    padding: 0.625rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    // Toggle sidebar on mobile
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.add('mobile-open');
            sidebarOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Close sidebar
    const closeSidebar = () => {
        sidebar.classList.remove('mobile-open');
        sidebarOverlay.classList.remove('show');
        document.body.style.overflow = 'auto';
    };
    
    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // Auto-expand active menu sections
    const activeLinks = document.querySelectorAll('.admin-menu .nav-link.active');
    activeLinks.forEach(link => {
        const parentCollapse = link.closest('.collapse');
        if (parentCollapse) {
            const bsCollapse = new bootstrap.Collapse(parentCollapse, {
                toggle: false
            });
            bsCollapse.show();
        }
    });
    
    // Close sidebar when clicking menu item on mobile
    const menuLinks = document.querySelectorAll('.admin-menu .nav-link:not(.has-dropdown)');
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                closeSidebar();
            }
        });
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && window.innerWidth < 992) {
            closeSidebar();
        }
    });
    
    // Initialize dropdown transitions
    const dropdowns = document.querySelectorAll('.has-dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('show.bs.collapse', function() {
            this.setAttribute('aria-expanded', 'true');
        });
        
        dropdown.addEventListener('hide.bs.collapse', function() {
            this.setAttribute('aria-expanded', 'false');
        });
    });
});
</script>