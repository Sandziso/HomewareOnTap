<?php
// includes/top-navbar.php - Improved Admin Top Navigation Bar

// Get current user info and notification data
$currentUser = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 
              (isset($_SESSION['user']['first_name']) ? $_SESSION['user']['first_name'] . ' ' . $_SESSION['user']['last_name'] : 'Admin');

// Get database connection for real data
$database = new Database();
$pdo = $database->getConnection();

// Get real notification count
$notificationCount = 0;
$unreadMessages = 0;

if ($pdo) {
    try {
        // Get unread notification count
        $notificationStmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM user_notifications 
            WHERE is_read = 0 AND user_id = ?
        ");
        $notificationStmt->execute([$_SESSION['user_id'] ?? 0]);
        $notificationCount = $notificationStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Get unread messages count
        $messageStmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM messages 
            WHERE status = 'new'
        ");
        $messageStmt->execute();
        $unreadMessages = $messageStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        // Get recent notifications for dropdown
        $recentNotificationsStmt = $pdo->prepare("
            SELECT * FROM user_notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $recentNotificationsStmt->execute([$_SESSION['user_id'] ?? 0]);
        $recentNotifications = $recentNotificationsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent messages for dropdown
        $recentMessagesStmt = $pdo->prepare("
            SELECT m.*, u.first_name, u.last_name 
            FROM messages m 
            LEFT JOIN users u ON m.user_id = u.id 
            WHERE m.status = 'new' 
            ORDER BY m.created_at DESC 
            LIMIT 3
        ");
        $recentMessagesStmt->execute();
        $recentMessages = $recentMessagesStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Top navbar data error: " . $e->getMessage());
        $recentNotifications = [];
        $recentMessages = [];
    }
} else {
    $recentNotifications = [];
    $recentMessages = [];
}

// Simple function to get initials
function getInitials($name) {
    $names = explode(' ', $name);
    $initials = '';
    foreach ($names as $n) {
        $initials .= strtoupper(substr($n, 0, 1));
    }
    return substr($initials, 0, 2);
}

// Get current page title for display
$pageTitle = isset($pageTitle) ? $pageTitle : 'Admin Dashboard';
?>
<!-- Improved Admin Header -->
<div class="admin-header bg-white shadow-sm border-bottom">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between py-2">
            
            <!-- Left Section: Toggle & Title -->
            <div class="d-flex align-items-center">
                <!-- Mobile sidebar toggle -->
                <button class="btn btn-sm btn-outline-secondary d-md-none me-3" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Page Title & Breadcrumb -->
                <div>
                    <h1 class="h4 mb-0 text-dark fw-bold"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <nav aria-label="breadcrumb" class="d-none d-md-block">
                        <ol class="breadcrumb mb-0 small">
                            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/index.php" class="text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($pageTitle); ?></li>
                        </ol>
                    </nav>
                </div>
            </div>
            
            <!-- Right Section: Search & User Menu -->
            <div class="d-flex align-items-center">
                
                <!-- Search bar -->
                <div class="d-none d-md-flex me-3">
                    <div class="input-group input-group-sm" style="width: 300px;">
                        <input type="text" class="form-control border-end-0" placeholder="Search products, orders, customers..." id="globalSearch">
                        <button class="btn btn-outline-secondary border-start-0" type="button" id="searchButton">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Mobile search toggle -->
                <button class="btn btn-sm btn-outline-secondary d-md-none me-2" id="searchToggle" data-bs-toggle="tooltip" title="Search">
                    <i class="fas fa-search"></i>
                </button>
                
                <!-- Notifications Dropdown -->
                <div class="dropdown me-3">
                    <a href="#" class="dropdown-toggle text-dark text-decoration-none position-relative" 
                       role="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                       data-bs-toggle="tooltip" title="Notifications">
                        <i class="fas fa-bell fs-5"></i>
                        <?php if ($notificationCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo $notificationCount > 99 ? '99+' : $notificationCount; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="width: 350px;" aria-labelledby="notificationsDropdown">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <strong>Notifications</strong>
                            <?php if ($notificationCount > 0): ?>
                            <span class="badge bg-primary"><?php echo $notificationCount; ?> new</span>
                            <?php endif; ?>
                        </li>
                        <li><hr class="dropdown-divider my-2"></li>
                        
                        <?php if (empty($recentNotifications)): ?>
                            <li class="px-3 py-2 text-muted text-center">
                                <i class="fas fa-bell-slash fa-2x mb-2 d-block"></i>
                                No new notifications
                            </li>
                        <?php else: ?>
                            <?php foreach ($recentNotifications as $notification): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-start p-3" href="<?php echo $notification['action_url'] ?: '#'; ?>">
                                    <div class="flex-shrink-0 me-3 mt-1">
                                        <i class="fas fa-<?php echo $notification['icon'] ?? 'bell'; ?> text-<?php 
                                            switch($notification['priority']) {
                                                case 'high': echo 'danger'; break;
                                                case 'medium': echo 'warning'; break;
                                                default: echo 'primary';
                                            }
                                        ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($notification['message']); ?></div>
                                        <div class="small text-muted"><?php echo time_elapsed_string($notification['created_at']); ?></div>
                                    </div>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <li>
                            <a class="dropdown-item text-center text-primary fw-semibold" href="<?php echo SITE_URL; ?>/admin/communications/notifications.php">
                                <i class="fas fa-list me-1"></i> View all notifications
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Messages Dropdown -->
                <div class="dropdown me-3">
                    <a href="#" class="dropdown-toggle text-dark text-decoration-none position-relative" 
                       role="button" id="messagesDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                       data-bs-toggle="tooltip" title="Messages">
                        <i class="fas fa-envelope fs-5"></i>
                        <?php if ($unreadMessages > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">
                            <?php echo $unreadMessages > 9 ? '9+' : $unreadMessages; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="width: 350px;" aria-labelledby="messagesDropdown">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <strong>Messages</strong>
                            <?php if ($unreadMessages > 0): ?>
                            <span class="badge bg-warning"><?php echo $unreadMessages; ?> new</span>
                            <?php endif; ?>
                        </li>
                        <li><hr class="dropdown-divider my-2"></li>
                        
                        <?php if (empty($recentMessages)): ?>
                            <li class="px-3 py-2 text-muted text-center">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No new messages
                            </li>
                        <?php else: ?>
                            <?php foreach ($recentMessages as $message): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-start p-3" href="<?php echo SITE_URL; ?>/admin/communications/messages.php?id=<?php echo $message['id']; ?>">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <?php 
                                                $senderName = $message['first_name'] ?? 'Guest';
                                                echo getInitials($senderName . ' ' . ($message['last_name'] ?? ''));
                                            ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-truncate" style="max-width: 200px;">
                                            <?php echo htmlspecialchars($senderName . ' ' . ($message['last_name'] ?? '')); ?>
                                        </div>
                                        <div class="small text-muted text-truncate" style="max-width: 200px;">
                                            <?php echo htmlspecialchars($message['subject']); ?>
                                        </div>
                                        <div class="small text-muted"><?php echo time_elapsed_string($message['created_at']); ?></div>
                                    </div>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <li>
                            <a class="dropdown-item text-center text-primary fw-semibold" href="<?php echo SITE_URL; ?>/admin/communications/messages.php">
                                <i class="fas fa-envelope-open me-1"></i> View all messages
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Quick Actions Dropdown -->
                <div class="dropdown me-3">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="quickActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bolt me-1"></i> Quick Actions
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="quickActionsDropdown">
                        <li><h6 class="dropdown-header">Quick Actions</h6></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/products/manage.php?action=add"><i class="fas fa-plus me-2"></i> Add New Product</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/orders/list.php?status=pending"><i class="fas fa-clock me-2"></i> View Pending Orders</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/products/inventory.php"><i class="fas fa-boxes me-2"></i> Check Inventory</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/reports/sales.php"><i class="fas fa-chart-line me-2"></i> Sales Report</a></li>
                    </ul>
                </div>
                
                <!-- User Menu -->
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle text-dark text-decoration-none d-flex align-items-center" 
                       role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                            <?php echo getInitials($currentUser); ?>
                        </div>
                        <span class="d-none d-lg-inline fw-semibold"><?php echo htmlspecialchars(explode(' ', $currentUser)[0]); ?></span>
                        <i class="fas fa-chevron-down ms-1 small d-none d-lg-inline"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDropdown">
                        <li class="dropdown-header">
                            <div class="fw-semibold">Welcome, <?php echo htmlspecialchars(explode(' ', $currentUser)[0]); ?></div>
                            <div class="small text-muted">Administrator</div>
                        </li>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/account/profile.php">
                                <i class="fas fa-user me-2"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/account/settings.php">
                                <i class="fas fa-cog me-2"></i> Account Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/index.php" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i> View Website
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/settings/site.php">
                                <i class="fas fa-sliders-h me-2"></i> Site Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li>
                            <a class="dropdown-item text-danger fw-semibold" href="<?php echo SITE_URL; ?>/includes/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Search Bar -->
<div class="container-fluid d-md-none bg-light border-bottom py-2" id="mobileSearch" style="display: none;">
    <div class="input-group">
        <input type="text" class="form-control" placeholder="Search products, orders, customers...">
        <button class="btn btn-primary" type="button">
            <i class="fas fa-search"></i>
        </button>
        <button class="btn btn-outline-secondary" type="button" id="closeMobileSearch">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchToggle = document.getElementById('searchToggle');
    const mobileSearch = document.getElementById('mobileSearch');
    const closeMobileSearch = document.getElementById('closeMobileSearch');
    const globalSearch = document.getElementById('globalSearch');
    const searchButton = document.getElementById('searchButton');
    
    // Mobile search toggle
    if (searchToggle && mobileSearch) {
        searchToggle.addEventListener('click', function() {
            mobileSearch.style.display = 'block';
            mobileSearch.querySelector('input').focus();
        });
    }
    
    if (closeMobileSearch) {
        closeMobileSearch.addEventListener('click', function() {
            mobileSearch.style.display = 'none';
        });
    }
    
    // Global search functionality
    if (globalSearch && searchButton) {
        const performSearch = () => {
            const query = globalSearch.value.trim();
            if (query) {
                // Redirect to search results page or perform AJAX search
                window.location.href = `<?php echo SITE_URL; ?>/admin/search.php?q=${encodeURIComponent(query)}`;
            }
        };
        
        searchButton.addEventListener('click', performSearch);
        globalSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            const dropdowns = document.querySelectorAll('.dropdown-menu');
            dropdowns.forEach(dropdown => {
                if (dropdown.classList.contains('show')) {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(dropdown.previousElementSibling);
                    if (dropdownInstance) {
                        dropdownInstance.hide();
                    }
                }
            });
        }
    });
    
    // Mark notifications as read when dropdown is opened
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    if (notificationsDropdown) {
        notificationsDropdown.addEventListener('show.bs.dropdown', function() {
            // AJAX call to mark notifications as read
            fetch('<?php echo SITE_URL; ?>/admin/ajax/mark-notifications-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ mark_all: true })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update notification badge
                    const badge = notificationsDropdown.querySelector('.badge');
                    if (badge) {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }
});
</script>

<style>
:root {
    --primary: #A67B5B;
    --secondary: #F2E8D5;
    --light: #F9F5F0;
    --dark: #3A3229;
}

.admin-header {
    position: sticky;
    top: 0;
    z-index: 1020;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--secondary) !important;
}

.avatar {
    font-weight: 600;
    font-size: 0.875rem;
    background-color: var(--primary) !important;
}

.dropdown-menu {
    border: 1px solid rgba(0,0,0,0.1);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-radius: 8px;
}

.dropdown-item {
    padding: 0.5rem 1rem;
    transition: all 0.2s;
    color: var(--dark);
}

.dropdown-item:hover {
    background-color: var(--secondary);
    color: var(--dark);
}

.dropdown-header {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    background-color: var(--light);
}

.badge {
    font-size: 0.7rem;
}

.bg-primary {
    background-color: var(--primary) !important;
}

.btn-primary {
    background-color: var(--primary);
    border-color: var(--primary);
}

.btn-primary:hover {
    background-color: #8B6145;
    border-color: #8B6145;
}

.btn-outline-primary {
    color: var(--primary);
    border-color: var(--primary);
}

.btn-outline-primary:hover {
    background-color: var(--primary);
    border-color: var(--primary);
    color: white;
}

.text-primary {
    color: var(--primary) !important;
}

#mobileSearch {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .admin-header .container-fluid {
        padding: 0 10px;
    }
    
    .dropdown-menu {
        position: fixed !important;
        top: 60px !important;
        left: 10px !important;
        right: 10px !important;
        width: auto !important;
    }
}

.breadcrumb-item.active {
    color: var(--primary);
}

.breadcrumb-item a {
    color: var(--dark);
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: var(--primary);
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 0.2rem rgba(166, 123, 91, 0.25);
}

.btn-outline-secondary {
    color: var(--dark);
    border-color: var(--secondary);
}

.btn-outline-secondary:hover {
    background-color: var(--secondary);
    border-color: var(--secondary);
    color: var(--dark);
}
</style>