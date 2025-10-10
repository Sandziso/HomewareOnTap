<?php
// File: pages/account/notifications.php

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

// Initialize variables
$success = $error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Mark all as read
    if (isset($_POST['mark_all_read'])) {
        try {
            if (markAllNotificationsAsRead($pdo, $userId)) {
                $success = 'All notifications marked as read.';
            } else {
                $error = 'No unread notifications to mark.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred while marking notifications as read.';
            error_log("Mark all read error: " . $e->getMessage());
        }
    }
    // Mark single as read
    elseif (isset($_POST['mark_read'])) {
        $notificationId = (int)$_POST['notification_id'];
        
        try {
            if (markNotificationAsRead($pdo, $notificationId, $userId)) {
                $success = 'Notification marked as read.';
            } else {
                $error = 'Notification not found.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred while marking the notification as read.';
            error_log("Mark read error: " . $e->getMessage());
        }
    }
    // Delete notification
    elseif (isset($_POST['delete_notification'])) {
        $notificationId = (int)$_POST['notification_id'];
        
        try {
            if (deleteNotification($pdo, $notificationId, $userId)) {
                $success = 'Notification deleted successfully.';
            } else {
                $error = 'Notification not found.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred while deleting the notification.';
            error_log("Delete notification error: " . $e->getMessage());
        }
    }
    // Clear all notifications
    elseif (isset($_POST['clear_all'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM user_notifications WHERE user_id = ?");
            $stmt->execute([$userId]);
            $success = 'All notifications cleared successfully.';
        } catch (Exception $e) {
            $error = 'An error occurred while clearing notifications.';
            error_log("Clear all notifications error: " . $e->getMessage());
        }
    }
}

// Handle AJAX mark as read
if (isset($_GET['ajax_mark_read'])) {
    $notificationId = (int)$_GET['ajax_mark_read'];
    if (markNotificationAsRead($pdo, $notificationId, $userId)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to mark as read']);
    }
    exit;
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch notifications
try {
    $notifications = getUserNotifications($pdo, $userId, $limit, $offset);
    
    // Get total count for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_notifications WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $totalNotifications = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalNotifications / $limit);
    
    // Get unread count
    $unreadCount = getUnreadNotificationCount($pdo, $userId);
    
} catch (Exception $e) {
    $error = 'Unable to fetch notifications. Please try again.';
    error_log("Fetch notifications error: " . $e->getMessage());
    $notifications = [];
    $totalNotifications = 0;
    $totalPages = 1;
    $unreadCount = 0;
}

// Set page title
$pageTitle = "My Notifications - HomewareOnTap";
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
    /* Global Styles for User Dashboard (Consistent with wishlist.php) */
    :root {
        --primary: #A67B5B; /* Brown/Tan */
        --secondary: #F2E8D5;
        --light: #F9F5F0;
        --dark: #3A3229;
        --success: #1cc88a; 
        --info: #36b9cc; 
        --warning: #f6c23e;
        --danger: #e74a3b;
    }

    body {
        background-color: var(--light);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
    }
    
    .dashboard-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .main-content {
        flex-grow: 1;
        transition: margin-left 0.3s ease;
        min-height: 100vh;
        margin-left: 0; /* Default for mobile/small screens */
    }

    @media (min-width: 992px) {
        .main-content {
            margin-left: 280px; /* Sidebar width */
        }
    }

    .content-area {
        padding: 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Card styles */
    .card-dashboard {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        border: none;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .card-dashboard:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }
    
    .card-dashboard .card-header {
        background: white;
        border-bottom: 1px solid var(--secondary);
        padding: 1.25rem 1.5rem;
        font-weight: 600;
        color: var(--dark);
        font-size: 1.1rem;
    }
    
    .card-dashboard .card-body {
        padding: 1.5rem;
    }

    /* Button styles */
    .btn-primary { 
        background-color: var(--primary); 
        border-color: var(--primary); 
        color: white; 
        transition: all 0.2s;
    } 
    
    .btn-primary:hover { 
        background-color: #8B6145; /* Darker primary */
        border-color: #8B6145; 
    } 

    /* Notification Styles */
    .notification-item {
        border-left: 4px solid var(--primary);
        background: white;
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        border: 1px solid #eee;
        position: relative;
    }
    
    .notification-item.unread {
        background: #f8f9fa;
        border-left-color: var(--info);
    }
    
    .notification-item.high-priority {
        border-left-color: var(--danger);
        background: #fff5f5;
    }
    
    .notification-item.medium-priority {
        border-left-color: var(--warning);
    }
    
    .notification-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .notification-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    
    .notification-title {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 0.25rem;
        font-size: 1.05rem;
    }
    
    .notification-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        color: #6c757d;
        font-size: 0.875rem;
    }
    
    .notification-type {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .notification-type.system {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .notification-type.order {
        background: #e8f5e9;
        color: #2e7d32;
    }
    
    .notification-type.promotion {
        background: #fff3e0;
        color: #f57c00;
    }
    
    .notification-type.shipping {
        background: #f3e5f5;
        color: #7b1fa2;
    }
    
    .notification-content {
        color: var(--dark);
        line-height: 1.6;
        margin-bottom: 1rem;
    }
    
    .notification-actions {
        display: flex;
        gap: 0.75rem;
        align-items: center;
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
        font-weight: 600;
    }
    
    .notification-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    
    .notification-icon.system {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .notification-icon.order {
        background: #e8f5e9;
        color: #2e7d32;
    }
    
    .notification-icon.promotion {
        background: #fff3e0;
        color: #f57c00;
    }
    
    .notification-icon.shipping {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    /* Alert Styles */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: 1px solid transparent;
    }
    
    .alert-danger {
        background: #ffebee;
        color: #c62828;
        border-color: #ef9a9a;
    }
    
    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        border-color: #a5d6a7;
    }

    /* Page Header */
    .page-header {
        margin-bottom: 2rem;
    }
    
    .page-header h1 {
        color: var(--dark);
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .page-header p {
        color: var(--dark);
        opacity: 0.7;
        margin: 0;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: var(--secondary);
        margin-bottom: 1.5rem;
    }
    
    .empty-state h5 {
        color: var(--dark);
        margin-bottom: 1rem;
    }
    
    .empty-state p {
        color: var(--dark);
        opacity: 0.7;
        margin-bottom: 2rem;
    }

    /* Pagination */
    .pagination {
        justify-content: center;
        margin-top: 2rem;
    }
    
    .page-item.active .page-link {
        background-color: var(--primary);
        border-color: var(--primary);
    }
    
    .page-link {
        color: var(--primary);
        border: 1px solid #dee2e6;
        padding: 0.5rem 0.75rem;
    }
    
    .page-link:hover {
        color: #8B6145;
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .notification-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .notification-meta {
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .notification-actions {
            flex-wrap: wrap;
        }
    }

    /* Bulk actions */
    .bulk-actions {
        background: #f8f9fa;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .bulk-actions .btn-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    </style>
</head>
<body>
    
    <div class="dashboard-wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php require_once 'includes/topbar.php'; ?>

            <main class="content-area">
                <div class="container-fluid">
                    <div class="page-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1>My Notifications</h1>
                                <p>Stay updated with your account activity</p>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-primary fs-6">
                                    <?php echo $unreadCount; ?> unread
                                </span>
                                <?php if ($totalNotifications > 0): ?>
                                <form method="POST" action="" class="d-inline">
                                    <button type="submit" name="mark_all_read" class="btn btn-success btn-sm">
                                        <i class="fas fa-check-double me-1"></i> Mark All Read
                                    </button>
                                </form>
                                <form method="POST" action="" class="d-inline">
                                    <button type="submit" name="clear_all" class="btn btn-outline-danger btn-sm" 
                                            onclick="return confirm('Are you sure you want to clear all notifications? This action cannot be undone.');">
                                        <i class="fas fa-trash me-1"></i> Clear All
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($notifications)): ?>
                        <div class="card-dashboard">
                            <div class="card-header">
                                <i class="fas fa-bell me-2"></i> Notifications (<?php echo $totalNotifications; ?>)
                            </div>
                            <div class="card-body p-0">
                                <div class="bulk-actions">
                                    <small class="text-muted">
                                        Showing <?php echo count($notifications); ?> of <?php echo $totalNotifications; ?> notifications
                                    </small>
                                </div>
                                
                                <div class="p-3">
                                    <?php foreach ($notifications as $notification): ?>
                                        <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?> <?php echo $notification['priority']; ?>-priority">
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="notification-badge" title="Unread"></span>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex">
                                                <div class="notification-icon <?php echo $notification['type']; ?>">
                                                    <i class="<?php echo $notification['icon'] ?? 'fas fa-bell'; ?>"></i>
                                                </div>
                                                
                                                <div class="flex-grow-1">
                                                    <div class="notification-header">
                                                        <div>
                                                            <div class="notification-title">
                                                                <?php echo htmlspecialchars($notification['title']); ?>
                                                            </div>
                                                            <div class="notification-meta">
                                                                <span class="notification-type <?php echo $notification['type']; ?>">
                                                                    <?php echo ucfirst($notification['type']); ?>
                                                                </span>
                                                                <span class="notification-time">
                                                                    <i class="fas fa-clock me-1"></i>
                                                                    <?php echo time_elapsed_string($notification['created_at']); ?>
                                                                </span>
                                                                <?php if ($notification['priority'] == 'high'): ?>
                                                                    <span class="badge bg-danger">High Priority</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="notification-content">
                                                        <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                                    </div>
                                                    
                                                    <div class="notification-actions">
                                                        <?php if (!$notification['is_read']): ?>
                                                            <form method="POST" action="" class="d-inline">
                                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                                <button type="submit" name="mark_read" class="btn btn-success btn-sm">
                                                                    <i class="fas fa-check me-1"></i> Mark Read
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($notification['action_url'])): ?>
                                                            <a href="<?php echo SITE_URL . $notification['action_url']; ?>" 
                                                               class="btn btn-primary btn-sm">
                                                                <i class="fas fa-arrow-right me-1"></i>
                                                                <?php echo $notification['action_text'] ?? 'View Details'; ?>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <form method="POST" action="" class="d-inline">
                                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                            <button type="submit" name="delete_notification" class="btn btn-outline-danger btn-sm"
                                                                    onclick="return confirm('Are you sure you want to delete this notification?');">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <nav aria-label="Notifications pagination" class="p-3 border-top">
                                        <ul class="pagination justify-content-center mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $totalPages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card-dashboard">
                            <div class="card-body">
                                <div class="empty-state">
                                    <i class="fas fa-bell-slash"></i>
                                    <h5>No Notifications</h5>
                                    <p class="mb-4">You don't have any notifications at the moment.</p>
                                    <a href="<?php echo SITE_URL; ?>/pages/shop.php" class="btn btn-primary">
                                        <i class="fas fa-store me-2"></i> Start Shopping
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Sidebar toggle logic for mobile
            $('#sidebarToggle').on('click', function() {
                document.dispatchEvent(new Event('toggleSidebar'));
            });

            // Auto-mark as read when clicking on notification action links
            $('.notification-item .btn-primary').on('click', function(e) {
                const notificationItem = $(this).closest('.notification-item');
                if (notificationItem.hasClass('unread')) {
                    const notificationId = notificationItem.find('input[name="notification_id"]').val();
                    
                    // Mark as read via AJAX
                    $.get('?ajax_mark_read=' + notificationId, function(response) {
                        if (response.success) {
                            notificationItem.removeClass('unread');
                            notificationItem.find('.notification-badge').remove();
                            
                            // Update unread count in header
                            const unreadCount = $('.notification-item.unread').length;
                            $('.badge.bg-primary').text(unreadCount + ' unread');
                        }
                    });
                }
            });
        });

        // Time formatting helper function
        function time_elapsed_string(datetime) {
            const date = new Date(datetime);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + " years ago";
            
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + " months ago";
            
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + " days ago";
            
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + " hours ago";
            
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + " minutes ago";
            
            return "just now";
        }
    </script>
</body>
</html>