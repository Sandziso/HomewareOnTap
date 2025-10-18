<?php
// pages/account/includes/topbar.php
if (!isset($user)) {
    // Fallback for user data
    $user = [
        'id' => $_SESSION['user_id'] ?? 0,
        'name' => $_SESSION['user_name'] ?? 'Guest User',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'created_at' => $_SESSION['user_created_at'] ?? date('Y-m-d H:i:s')
    ];
}

// Simple function to get initials
function getUserInitials($name) {
    $names = explode(' ', $name);
    $initials = '';
    foreach ($names as $n) {
        $initials .= strtoupper(substr($n, 0, 1));
    }
    return $initials;
}

// Get notification count for user
$notificationCount = 0;
if (isset($user) && isset($user['id'])) {
    try {
        require_once '../../includes/database.php';
        $db = new Database();
        $pdo = $db->getConnection();
        
        // Get unread notifications count
        $notificationStmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_notifications WHERE user_id = :user_id AND is_read = 0");
        $notificationStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
        $notificationStmt->execute();
        $notificationCount = $notificationStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Topbar notification count error: " . $e->getMessage());
        $notificationCount = 0;
    }
}
?>

<nav class="user-top-nav">
    <div class="nav-container">
        <div class="nav-left">
            <button class="nav-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div class="page-info">
                <h1 class="page-title">My Account</h1>
                <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>!</p>
            </div>
        </div>

        <div class="nav-right">
            <div class="nav-actions">
                <a href="<?php echo SITE_URL; ?>/pages/account/shop.php" class="nav-action-btn" title="Continue Shopping">
                    <i class="fas fa-store"></i>
                    <span class="action-text">Shop</span>
                </a>
                <a href="wishlist.php" class="nav-action-btn" title="My Wishlist">
                    <i class="fas fa-heart"></i>
                    <span class="action-text">Wishlist</span>
                </a>
            </div>

            <div class="nav-notifications">
                <div class="dropdown">
                    <a href="notifications.php" class="notification-btn" title="View Notifications">
                        <i class="fas fa-bell"></i>
                        <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge"><?php echo $notificationCount; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <div class="nav-profile">
                <div class="dropdown">
                    <button class="profile-btn dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <?php echo getUserInitials($user['name']); ?>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                            <span class="user-role">Customer</span>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <div class="user-menu-header">
                            <div class="user-avatar large">
                                <?php echo getUserInitials($user['name']); ?>
                            </div>
                            <div class="user-details">
                                <h6><?php echo htmlspecialchars($user['name']); ?></h6>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>Dashboard
                        </a>
                        <a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user"></i>My Profile
                        </a>
                        <a class="dropdown-item" href="orders.php">
                            <i class="fas fa-shopping-bag"></i>My Orders
                        </a>
                        <a class="dropdown-item" href="addresses.php">
                            <i class="fas fa-map-marker-alt"></i>My Addresses
                        </a>
                        <a class="dropdown-item" href="wishlist.php">
                            <i class="fas fa-heart"></i>My Wishlist
                        </a>
                        <a class="dropdown-item" href="notifications.php">
                            <i class="fas fa-bell"></i>My Notifications
                            <?php if ($notificationCount > 0): ?>
                            <span class="badge bg-primary float-end"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/shop.php">
                            <i class="fas fa-store"></i>Continue Shopping
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
/* Enhanced User Top Navigation Styles */
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
}

.user-top-nav {
    background-color: white;
    box-shadow: var(--shadow-light);
    position: sticky;
    top: 0;
    z-index: 990;
    border-bottom: 1px solid var(--secondary);
}

.nav-container {
    padding: 0.75rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 100%;
    margin: 0 auto;
}

/* Left Section */
.nav-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nav-toggle {
    background: transparent;
    border: none;
    font-size: 1.5rem;
    color: var(--dark);
    cursor: pointer;
    padding: 0.5rem;
    margin-left: -0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-toggle:hover {
    background-color: var(--secondary);
    transform: scale(1.05);
}

@media (min-width: 992px) {
    .nav-toggle {
        display: none;
    }
}

.page-info {
    line-height: 1.2;
}

.page-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    letter-spacing: -0.5px;
}

.page-subtitle {
    margin: 0;
    font-size: 0.875rem;
    color: var(--dark);
    opacity: 0.7;
    font-weight: 500;
}

/* Right Section */
.nav-right {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

/* Quick Actions */
.nav-actions {
    display: flex;
    gap: 0.5rem;
}

.nav-action-btn {
    text-decoration: none;
    color: var(--dark);
    padding: 0.75rem 1rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    font-weight: 600;
    background: transparent;
    border: 1px solid transparent;
}

.nav-action-btn:hover {
    background-color: var(--secondary);
    color: var(--primary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
}

.nav-action-btn i {
    font-size: 1.1rem;
    transition: transform 0.3s ease;
}

.nav-action-btn:hover i {
    transform: scale(1.1);
}

/* Notifications */
.nav-notifications {
    position: relative;
}

.notification-btn {
    text-decoration: none;
    color: var(--dark);
    font-size: 1.25rem;
    position: relative;
    padding: 0.75rem;
    border-radius: 10px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    background: transparent;
}

.notification-btn:hover {
    background-color: var(--secondary);
    color: var(--primary);
    transform: scale(1.05);
}

.notification-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: var(--danger);
    color: white;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 4px 6px;
    border-radius: 50%;
    line-height: 1;
    border: 2px solid white;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 5px rgba(231, 76, 60, 0.3);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* User Profile */
.profile-btn {
    background: none;
    border: none;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0.75rem;
    border-radius: 12px;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid transparent;
}

.profile-btn:hover {
    background-color: var(--secondary);
    transform: translateY(-2px);
    box-shadow: var(--shadow-light);
}

.user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: var(--gradient-primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 700;
    flex-shrink: 0;
    border: 2px solid var(--secondary);
    transition: all 0.3s ease;
}

.profile-btn:hover .user-avatar {
    transform: scale(1.05);
    border-color: var(--primary-light);
}

.user-avatar.large {
    width: 60px;
    height: 60px;
    font-size: 1.2rem;
    border: 3px solid var(--secondary);
}

.user-info {
    line-height: 1.2;
    text-align: left;
    display: block;
}

.user-name {
    display: block;
    font-weight: 700;
    color: var(--dark);
    font-size: 0.95rem;
}

.user-role {
    display: block;
    font-size: 0.8rem;
    color: var(--dark);
    opacity: 0.7;
    font-weight: 500;
}

.profile-btn i {
    color: var(--dark);
    opacity: 0.7;
    transition: transform 0.3s ease;
}

.profile-btn:hover i {
    transform: rotate(180deg);
}

/* Enhanced Dropdown Menu */
.dropdown-menu {
    border-radius: 16px;
    box-shadow: var(--shadow-medium);
    border: 1px solid var(--secondary);
    min-width: 280px;
    overflow: hidden;
    margin-top: 0.5rem !important;
}

.user-menu-header {
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    background: var(--gradient-primary);
    color: white;
}

.user-details h6 {
    margin: 0;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
}

.user-details span {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.9);
    opacity: 0.9;
}

.dropdown-item {
    padding: 0.875rem 1.25rem;
    color: var(--dark);
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    position: relative;
}

.dropdown-item:hover {
    background-color: var(--secondary);
    color: var(--primary);
    padding-left: 1.5rem;
}

.dropdown-item i {
    margin-right: 0.75rem;
    width: 18px;
    text-align: center;
    color: var(--primary);
    transition: transform 0.3s ease;
}

.dropdown-item:hover i {
    transform: scale(1.1);
}

.dropdown-item.text-danger i {
    color: var(--danger);
}

.dropdown-item.text-danger:hover {
    background-color: rgba(231, 76, 60, 0.1);
    color: var(--danger);
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-color: var(--secondary);
}

.badge {
    font-size: 0.7rem;
    padding: 4px 6px;
    font-weight: 700;
}

/* Responsive Design */
@media (max-width: 768px) {
    .nav-container {
        padding: 0.75rem 1rem;
    }

    .page-title {
        font-size: 1.25rem;
    }

    .action-text {
        display: none;
    }

    .user-info {
        display: none;
    }

    .nav-actions {
        gap: 0.25rem;
    }

    .nav-action-btn {
        padding: 0.75rem;
    }

    .profile-btn {
        padding: 0.5rem;
    }

    .user-avatar {
        width: 38px;
        height: 38px;
        font-size: 0.8rem;
    }

    .dropdown-menu {
        min-width: 250px;
    }
}

@media (max-width: 576px) {
    .nav-right {
        gap: 1rem;
    }

    .nav-actions {
        display: none;
    }

    .page-info .page-subtitle {
        display: none;
    }
}

/* Enhanced focus states for accessibility */
.nav-toggle:focus,
.nav-action-btn:focus,
.notification-btn:focus,
.profile-btn:focus {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

/* Loading animation for notification badge */
.notification-badge.loading {
    animation: pulse 1s infinite;
}

/* Enhanced dropdown animation */
.dropdown-menu {
    animation: dropdownFadeIn 0.3s ease;
}

@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.dispatchEvent(new Event('toggleSidebar'));
        });
    }

    // Enhanced notification bell interaction
    const notificationBtn = document.querySelector('.notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function(e) {
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    }

    // Profile dropdown enhancements
    const profileBtn = document.getElementById('userDropdown');
    if (profileBtn) {
        profileBtn.addEventListener('click', function() {
            const icon = this.querySelector('i.fa-chevron-down');
            if (icon) {
                icon.style.transition = 'transform 0.3s ease';
            }
        });
    }

    // Auto-update notification count
    function updateNotificationCount() {
        fetch('ajax/get_notification_count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.querySelector('.notification-badge');
                    if (data.count > 0) {
                        if (badge) {
                            badge.textContent = data.count;
                            badge.style.display = 'flex';
                        } else {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'notification-badge';
                            newBadge.textContent = data.count;
                            document.querySelector('.notification-btn').appendChild(newBadge);
                        }
                        
                        // Update dropdown badge as well
                        const dropdownBadge = document.querySelector('.dropdown-item[href="notifications.php"] .badge');
                        if (dropdownBadge) {
                            dropdownBadge.textContent = data.count;
                        } else {
                            const newDropdownBadge = document.createElement('span');
                            newDropdownBadge.className = 'badge bg-primary float-end';
                            newDropdownBadge.textContent = data.count;
                            document.querySelector('.dropdown-item[href="notifications.php"]').appendChild(newDropdownBadge);
                        }
                    } else {
                        if (badge) badge.remove();
                        const dropdownBadge = document.querySelector('.dropdown-item[href="notifications.php"] .badge');
                        if (dropdownBadge) dropdownBadge.remove();
                    }
                }
            })
            .catch(error => console.error('Error updating notification count:', error));
    }

    // Update notification count every 30 seconds
    setInterval(updateNotificationCount, 30000);

    // Add hover effects to nav items
    const navItems = document.querySelectorAll('.nav-action-btn, .notification-btn, .profile-btn');
    navItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });

    // Enhanced mobile menu handling
    function handleResize() {
        if (window.innerWidth >= 992) {
            // Close any open dropdowns on desktop
            const dropdowns = document.querySelectorAll('.dropdown-menu');
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    }

    window.addEventListener('resize', handleResize);
});
</script>