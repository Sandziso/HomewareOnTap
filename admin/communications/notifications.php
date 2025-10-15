<?php
// admin/communications/notifications.php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in and has admin privileges
if (!isAdminLoggedIn()) {
    header('Location: ../../pages/auth/login.php?redirect=admin');
    exit();
}

// Get database connection
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Database connection failed");
}

// Create notifications table if it doesn't exist
$createTableSQL = "
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` enum('info','alert','promo') NOT NULL DEFAULT 'info',
  `target_audience` enum('all_customers','registered_users','subscribers') NOT NULL DEFAULT 'all_customers',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `status` enum('draft','scheduled','sent') DEFAULT 'draft',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    $pdo->exec($createTableSQL);
} catch (PDOException $e) {
    error_log("Error creating notifications table: " . $e->getMessage());
}

// Handle notification actions (add, edit, delete, mark as sent)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $notificationId = intval($_POST['notification_id'] ?? 0);

    if ($action === 'add') {
        $title = sanitize_input($_POST['title']);
        $content = sanitize_input($_POST['content']);
        $type = sanitize_input($_POST['type']);
        $targetAudience = sanitize_input($_POST['target_audience']);
        $scheduledAt = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
        
        // Determine status based on scheduled_at
        $status = 'draft';
        if ($scheduledAt && strtotime($scheduledAt) > time()) {
            $status = 'scheduled';
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO notifications (title, content, type, target_audience, scheduled_at, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $success = $stmt->execute([$title, $content, $type, $targetAudience, $scheduledAt, $status]);
            
            if ($success) {
                $_SESSION['success_message'] = 'Notification created successfully.';
            } else {
                $_SESSION['error_message'] = 'Failed to create notification.';
            }
        } catch (PDOException $e) {
            error_log("Error creating notification: " . $e->getMessage());
            $_SESSION['error_message'] = 'Failed to create notification.';
        }
    } elseif ($action === 'edit' && $notificationId) {
        $title = sanitize_input($_POST['title']);
        $content = sanitize_input($_POST['content']);
        $type = sanitize_input($_POST['type']);
        $targetAudience = sanitize_input($_POST['target_audience']);
        $scheduledAt = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
        
        // Determine status based on scheduled_at and current sent status
        try {
            $checkStmt = $pdo->prepare("SELECT sent_at FROM notifications WHERE id = ?");
            $checkStmt->execute([$notificationId]);
            $notification = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            $status = 'draft';
            if ($notification && $notification['sent_at']) {
                $status = 'sent';
            } elseif ($scheduledAt && strtotime($scheduledAt) > time()) {
                $status = 'scheduled';
            }
            
            $stmt = $pdo->prepare("UPDATE notifications SET title = ?, content = ?, type = ?, target_audience = ?, scheduled_at = ?, status = ?, updated_at = NOW() WHERE id = ?");
            $success = $stmt->execute([$title, $content, $type, $targetAudience, $scheduledAt, $status, $notificationId]);
            
            if ($success) {
                $_SESSION['success_message'] = 'Notification updated successfully.';
            } else {
                $_SESSION['error_message'] = 'Failed to update notification.';
            }
        } catch (PDOException $e) {
            error_log("Error updating notification: " . $e->getMessage());
            $_SESSION['error_message'] = 'Failed to update notification.';
        }
    } elseif ($action === 'delete' && $notificationId) {
        try {
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            $success = $stmt->execute([$notificationId]);
            
            if ($success) {
                $_SESSION['success_message'] = 'Notification deleted successfully.';
            } else {
                $_SESSION['error_message'] = 'Failed to delete notification.';
            }
        } catch (PDOException $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            $_SESSION['error_message'] = 'Failed to delete notification.';
        }
    } elseif ($action === 'send_now' && $notificationId) {
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET sent_at = NOW(), status = 'sent' WHERE id = ?");
            $success = $stmt->execute([$notificationId]);
            
            if ($success) {
                $_SESSION['success_message'] = 'Notification sent immediately.';
            } else {
                $_SESSION['error_message'] = 'Failed to send notification.';
            }
        } catch (PDOException $e) {
            error_log("Error sending notification: " . $e->getMessage());
            $_SESSION['error_message'] = 'Failed to send notification.';
        }
    }
    
    // Redirect to avoid form resubmission
    header('Location: notifications.php');
    exit();
}

// Pagination and filtering
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;
$statusFilter = $_GET['status'] ?? 'all';

// Build query for notifications
$query = "SELECT * FROM notifications WHERE 1=1";
$params = [];

if ($statusFilter !== 'all') {
    if ($statusFilter === 'sent') {
        $query .= " AND sent_at IS NOT NULL";
    } elseif ($statusFilter === 'scheduled') {
        $query .= " AND scheduled_at > NOW() AND sent_at IS NULL";
    } elseif ($statusFilter === 'draft') {
        $query .= " AND (scheduled_at IS NULL OR scheduled_at <= NOW()) AND sent_at IS NULL";
    }
}

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Fetch notifications
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
    $notifications = [];
}

// Get total notification count for pagination
$countQuery = "SELECT COUNT(*) as total FROM notifications WHERE 1=1";
$countParams = [];

if ($statusFilter !== 'all') {
    if ($statusFilter === 'sent') {
        $countQuery .= " AND sent_at IS NOT NULL";
    } elseif ($statusFilter === 'scheduled') {
        $countQuery .= " AND scheduled_at > NOW() AND sent_at IS NULL";
    } elseif ($statusFilter === 'draft') {
        $countQuery .= " AND (scheduled_at IS NULL OR scheduled_at <= NOW()) AND sent_at IS NULL";
    }
}

try {
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalNotifications = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error counting notifications: " . $e->getMessage());
    $totalNotifications = 0;
}

$totalPages = ceil($totalNotifications / $limit);

// Helper function to format date
function formatDate($date) {
    if (!$date) return 'N/A';
    return date('M j, Y g:i A', strtotime($date));
}

// Helper function to get status badge
function getStatusBadge($scheduledAt, $sentAt) {
    if ($sentAt) {
        return '<span class="badge bg-success">Sent</span>';
    } elseif ($scheduledAt && strtotime($scheduledAt) > time()) {
        return '<span class="badge bg-info">Scheduled</span>';
    } else {
        return '<span class="badge bg-secondary">Draft</span>';
    }
}

// Helper function to get type badge
function getTypeBadge($type) {
    $badgeClasses = [
        'info' => 'bg-primary',
        'alert' => 'bg-warning',
        'promo' => 'bg-success'
    ];
    $class = $badgeClasses[$type] ?? 'bg-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst($type) . '</span>';
}

// Helper function to get target audience badge
function getTargetBadge($target) {
    $badgeClasses = [
        'all_customers' => 'bg-primary',
        'registered_users' => 'bg-info',
        'subscribers' => 'bg-success'
    ];
    $class = $badgeClasses[$target] ?? 'bg-secondary';
    $label = str_replace('_', ' ', $target);
    return '<span class="badge ' . $class . '">' . ucwords($label) . '</span>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Notifications - HomewareOnTap Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #A67B5B;
            --primary-dark: #8B6145;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
            min-height: 100vh;
        }
        
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .card-dashboard {
            border-radius: 12px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card-dashboard:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(166, 123, 91, 0.3);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .action-buttons .btn {
            padding: 0.35rem 0.65rem;
            font-size: 0.875rem;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-2px);
        }
        
        .top-navbar {
            background: #fff;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            padding: 15px 25px;
            margin-bottom: 25px;
            border-radius: 12px;
        }
        
        .navbar-toggle {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: var(--dark);
            display: none;
            transition: color 0.2s;
        }
        
        .navbar-toggle:hover {
            color: var(--primary);
        }
        
        @media (max-width: 991.98px) {
            .navbar-toggle {
                display: block;
            }
        }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
        }
        
        @media (max-width: 991.98px) {
            .sidebar-overlay.active {
                display: block;
            }
        }
        
        .notification-card {
            border-left: 4px solid var(--primary);
            transition: all 0.3s;
        }
        
        .notification-card:hover {
            border-left-color: var(--primary-dark);
            background-color: rgba(166, 123, 91, 0.03);
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            border: none;
            padding: 12px 15px;
        }
        
        .table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .table tbody tr {
            transition: background-color 0.2s;
        }
        
        .table tbody tr:hover {
            background-color: rgba(166, 123, 91, 0.05);
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-card .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .stats-card .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .filter-badge {
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-badge:hover {
            transform: scale(1.05);
        }
        
        .modal-header {
            background-color: var(--primary);
            color: white;
            border-bottom: none;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .pagination .page-link {
            color: var(--primary);
            border: 1px solid #dee2e6;
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .notification-preview {
            background-color: var(--light);
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            border-left: 3px solid var(--primary);
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.875rem;
            }
            
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            
            .action-buttons .btn {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .stats-card .stats-number {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .top-navbar {
                padding: 12px 15px;
            }
            
            .table th, .table td {
                padding: 8px 10px;
            }
            
            .btn-group .btn {
                font-size: 0.8rem;
                padding: 0.375rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Include the sidebar -->
    <?php include '../../includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button class="navbar-toggle me-3" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h4 class="mb-0 fw-bold">System Notifications</h4>
                        <p class="mb-0 text-muted small">Manage and send notifications to your customers</p>
                    </div>
                </div>
                <div class="dropdown">
                    <a class="dropdown-toggle d-flex align-items-center text-decoration-none" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=A67B5B&color=fff" alt="Admin" class="rounded-circle me-2" width="36" height="36">
                        <span>Admin</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../../includes/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Stats Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="stats-number"><?= $totalNotifications ?></p>
                            <p class="stats-label">Total Notifications</p>
                        </div>
                        <i class="fas fa-bell fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="h4 mb-0"><?= count(array_filter($notifications, function($n) { return $n['sent_at']; })) ?></p>
                                <p class="text-muted small mb-0">Sent</p>
                            </div>
                            <i class="fas fa-paper-plane fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="h4 mb-0"><?= count(array_filter($notifications, function($n) { return $n['scheduled_at'] && strtotime($n['scheduled_at']) > time() && !$n['sent_at']; })) ?></p>
                                <p class="text-muted small mb-0">Scheduled</p>
                            </div>
                            <i class="fas fa-clock fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-dashboard h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="h4 mb-0"><?= count(array_filter($notifications, function($n) { return (!$n['scheduled_at'] || strtotime($n['scheduled_at']) <= time()) && !$n['sent_at']; })) ?></p>
                                <p class="text-muted small mb-0">Drafts</p>
                            </div>
                            <i class="fas fa-edit fa-2x text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Content -->
        <div class="content-section" id="notificationsSection">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h3 class="mb-1">Manage Notifications</h3>
                    <p class="text-muted mb-0">Create and manage notifications for your customers</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <div class="btn-group">
                        <a href="?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                        <a href="?status=sent" class="btn btn-sm <?= $statusFilter === 'sent' ? 'btn-primary' : 'btn-outline-primary'; ?>">Sent</a>
                        <a href="?status=scheduled" class="btn btn-sm <?= $statusFilter === 'scheduled' ? 'btn-primary' : 'btn-outline-primary'; ?>">Scheduled</a>
                        <a href="?status=draft" class="btn btn-sm <?= $statusFilter === 'draft' ? 'btn-primary' : 'btn-outline-primary'; ?>">Draft</a>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNotificationModal">
                        <i class="fas fa-plus me-2"></i>New Notification
                    </button>
                </div>
            </div>

            <?php 
            // Display success/error messages
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">';
                echo '<i class="fas fa-check-circle me-2"></i>';
                echo $_SESSION['success_message'];
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
                unset($_SESSION['success_message']);
            }
            
            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">';
                echo '<i class="fas fa-exclamation-circle me-2"></i>';
                echo $_SESSION['error_message'];
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
                unset($_SESSION['error_message']);
            }
            ?>

            <!-- Notifications Table -->
            <div class="card card-dashboard">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title & Content</th>
                                    <th>Type</th>
                                    <th>Target</th>
                                    <th>Status</th>
                                    <th>Scheduled</th>
                                    <th>Sent</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($notifications)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="empty-state">
                                                <i class="fas fa-bell-slash"></i>
                                                <h4 class="mt-3">No notifications found</h4>
                                                <p class="text-muted">Get started by creating your first notification</p>
                                                <?php if ($statusFilter !== 'all'): ?>
                                                    <a href="?status=all" class="btn btn-primary mt-2">View All Notifications</a>
                                                <?php else: ?>
                                                    <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addNotificationModal">
                                                        <i class="fas fa-plus me-2"></i>Create Notification
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <tr class="notification-card">
                                            <td class="fw-bold"><?= $notif['id']; ?></td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($notif['title']); ?></div>
                                                <div class="text-muted small mt-1"><?= truncateText(htmlspecialchars($notif['content']), 80); ?></div>
                                            </td>
                                            <td><?= getTypeBadge($notif['type']); ?></td>
                                            <td><?= getTargetBadge($notif['target_audience']); ?></td>
                                            <td><?= getStatusBadge($notif['scheduled_at'], $notif['sent_at']); ?></td>
                                            <td>
                                                <small class="text-muted"><?= formatDate($notif['scheduled_at']); ?></small>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= formatDate($notif['sent_at']); ?></small>
                                            </td>
                                            <td class="action-buttons">
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <button type="button" class="btn btn-sm btn-outline-primary view-notification" 
                                                            data-notification-id="<?= $notif['id']; ?>"
                                                            data-title="<?= htmlspecialchars($notif['title']); ?>"
                                                            data-content="<?= htmlspecialchars($notif['content']); ?>"
                                                            data-type="<?= $notif['type']; ?>"
                                                            data-target="<?= $notif['target_audience']; ?>"
                                                            data-scheduled="<?= $notif['scheduled_at']; ?>"
                                                            data-sent="<?= $notif['sent_at']; ?>"
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-warning edit-notification" 
                                                            data-notification-id="<?= $notif['id']; ?>"
                                                            data-title="<?= htmlspecialchars($notif['title']); ?>"
                                                            data-content="<?= htmlspecialchars($notif['content']); ?>"
                                                            data-type="<?= $notif['type']; ?>"
                                                            data-target="<?= $notif['target_audience']; ?>"
                                                            data-scheduled="<?= $notif['scheduled_at']; ?>"
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if (!$notif['sent_at']): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="notification_id" value="<?= $notif['id']; ?>">
                                                            <input type="hidden" name="action" value="send_now">
                                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Send Now">
                                                                <i class="fas fa-paper-plane"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="notification_id" value="<?= $notif['id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Are you sure you want to delete this notification?')" 
                                                                title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer bg-white">
                        <nav aria-label="Notification pagination" class="mt-2">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $page - 1; ?>&status=<?= $statusFilter; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?= $i; ?>&status=<?= $statusFilter; ?>">
                                            <?= $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $page + 1; ?>&status=<?= $statusFilter; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Notification Modal -->
    <div class="modal fade" id="addNotificationModal" tabindex="-1" aria-labelledby="addNotificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addNotificationModalLabel">Create New Notification</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required maxlength="255" placeholder="Enter notification title">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="info">Information</option>
                                    <option value="alert">Alert</option>
                                    <option value="promo">Promotion</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="5" required placeholder="Enter notification content"></textarea>
                            <div class="form-text">This message will be sent to your customers.</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="target_audience" class="form-label">Target Audience <span class="text-danger">*</span></label>
                                <select class="form-select" id="target_audience" name="target_audience" required>
                                    <option value="all_customers">All Customers</option>
                                    <option value="registered_users">Registered Users Only</option>
                                    <option value="subscribers">Newsletter Subscribers Only</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="scheduled_at" class="form-label">Schedule Delivery</label>
                                <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at">
                                <div class="form-text">Leave empty to save as draft for now</div>
                            </div>
                        </div>
                        <div class="notification-preview mt-3 d-none" id="notificationPreview">
                            <h6 class="fw-bold mb-2">Preview:</h6>
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-bell text-primary me-2 mt-1"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-1" id="previewTitle">Notification Title</h6>
                                    <p class="mb-0 text-muted small" id="previewContent">Notification content will appear here...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Notification</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Notification Modal -->
    <div class="modal fade" id="editNotificationModal" tabindex="-1" aria-labelledby="editNotificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="notification_id" id="edit_notification_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editNotificationModalLabel">Edit Notification</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_title" name="title" required maxlength="255">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_type" name="type" required>
                                    <option value="info">Information</option>
                                    <option value="alert">Alert</option>
                                    <option value="promo">Promotion</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_content" class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="edit_content" name="content" rows="5" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_target_audience" class="form-label">Target Audience <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_target_audience" name="target_audience" required>
                                    <option value="all_customers">All Customers</option>
                                    <option value="registered_users">Registered Users Only</option>
                                    <option value="subscribers">Newsletter Subscribers Only</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_scheduled_at" class="form-label">Schedule Delivery</label>
                                <input type="datetime-local" class="form-control" id="edit_scheduled_at" name="scheduled_at">
                                <div class="form-text">Leave empty to save as draft</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Notification</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Notification Modal -->
    <div class="modal fade" id="viewNotificationModal" tabindex="-1" aria-labelledby="viewNotificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewNotificationModalLabel">Notification Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h4 id="view_title" class="mb-2"></h4>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span id="view_type_badge"></span>
                                <span id="view_target_badge"></span>
                                <span id="view_status_badge"></span>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="text-muted small">
                                <div>Created: <span id="view_created"></span></div>
                                <div>Updated: <span id="view_updated"></span></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold mb-2">Content:</h6>
                        <div class="border p-3 bg-light rounded">
                            <p id="view_content" class="mb-0"></p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2">Delivery Information:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-1"><strong>Scheduled:</strong> <span id="view_scheduled" class="text-muted">N/A</span></li>
                                <li class="mb-1"><strong>Sent:</strong> <span id="view_sent" class="text-muted">N/A</span></li>
                                <li class="mb-1"><strong>Target:</strong> <span id="view_target" class="text-muted"></span></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-2">Audience Reach:</h6>
                            <div class="bg-light p-3 rounded">
                                <p class="small text-muted mb-0" id="view_audience_info">This notification will be sent to all customers.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Handle view notification button clicks
            $(document).on('click', '.view-notification', function() {
                const title = $(this).data('title');
                const content = $(this).data('content');
                const type = $(this).data('type');
                const target = $(this).data('target');
                const scheduled = $(this).data('scheduled');
                const sent = $(this).data('sent');
                
                $('#view_title').text(title);
                $('#view_content').text(content);
                $('#view_type_badge').html(getTypeBadge(type));
                $('#view_target_badge').html(getTargetBadge(target));
                $('#view_status_badge').html(getStatusBadge(scheduled, sent));
                $('#view_scheduled').text(scheduled ? new Date(scheduled).toLocaleString() : 'N/A');
                $('#view_sent').text(sent ? new Date(sent).toLocaleString() : 'N/A');
                $('#view_target').text(formatTarget(target));
                $('#view_audience_info').text(getAudienceInfo(target));
                
                $('#viewNotificationModal').modal('show');
            });
            
            // Handle edit notification button clicks
            $(document).on('click', '.edit-notification', function() {
                const notificationId = $(this).data('notification-id');
                const title = $(this).data('title');
                const content = $(this).data('content');
                const type = $(this).data('type');
                const target = $(this).data('target');
                const scheduled = $(this).data('scheduled');
                
                $('#edit_notification_id').val(notificationId);
                $('#edit_title').val(title);
                $('#edit_content').val(content);
                $('#edit_type').val(type);
                $('#edit_target_audience').val(target);
                
                if (scheduled) {
                    // Convert datetime to local datetime string format
                    const scheduledDate = new Date(scheduled);
                    const localDateTime = scheduledDate.toISOString().slice(0, 16);
                    $('#edit_scheduled_at').val(localDateTime);
                } else {
                    $('#edit_scheduled_at').val('');
                }
                
                $('#editNotificationModal').modal('show');
            });
            
            // Preview functionality for add notification form
            $('#title, #content').on('input', function() {
                updatePreview();
            });
            
            $('#type').on('change', function() {
                updatePreview();
            });
            
            function updatePreview() {
                const title = $('#title').val();
                const content = $('#content').val();
                const type = $('#type').val();
                
                if (title || content) {
                    $('#notificationPreview').removeClass('d-none');
                    $('#previewTitle').text(title || 'Notification Title');
                    $('#previewContent').text(content || 'Notification content will appear here...');
                    
                    // Update icon based on type
                    let iconClass = 'fas fa-bell text-primary';
                    if (type === 'alert') iconClass = 'fas fa-exclamation-triangle text-warning';
                    if (type === 'promo') iconClass = 'fas fa-tag text-success';
                    
                    $('#notificationPreview i').attr('class', iconClass + ' me-2 mt-1');
                } else {
                    $('#notificationPreview').addClass('d-none');
                }
            }
            
            // Sidebar toggle functionality
            $('#sidebarToggle').click(function() {
                $('#adminSidebar').toggleClass('active');
                $('#sidebarOverlay').toggleClass('active');
                $('body').toggleClass('overflow-hidden');
            });
            
            // Close sidebar when clicking overlay
            $('#sidebarOverlay').click(function() {
                $('#adminSidebar').removeClass('active');
                $(this).removeClass('active');
                $('body').removeClass('overflow-hidden');
            });
            
            // Auto-close sidebar on mobile when clicking a link
            $('.admin-menu .nav-link:not(.dropdown-toggle)').click(function() {
                if (window.innerWidth < 992) {
                    $('#adminSidebar').removeClass('active');
                    $('#sidebarOverlay').removeClass('active');
                    $('body').removeClass('overflow-hidden');
                }
            });

            // Set minimum datetime for scheduled_at fields to current time
            const now = new Date();
            const localDateTime = now.toISOString().slice(0, 16);
            $('#scheduled_at').attr('min', localDateTime);
            $('#edit_scheduled_at').attr('min', localDateTime);
            
            // Helper functions for view modal
            function getTypeBadge(type) {
                const badgeClasses = {
                    'info': 'bg-primary',
                    'alert': 'bg-warning',
                    'promo': 'bg-success'
                };
                const classNames = badgeClasses[type] || 'bg-secondary';
                return '<span class="badge ' + classNames + '">' + type.charAt(0).toUpperCase() + type.slice(1) + '</span>';
            }
            
            function getTargetBadge(target) {
                const badgeClasses = {
                    'all_customers': 'bg-primary',
                    'registered_users': 'bg-info',
                    'subscribers': 'bg-success'
                };
                const classNames = badgeClasses[target] || 'bg-secondary';
                const label = target.replace('_', ' ');
                return '<span class="badge ' + classNames + '">' + label.charAt(0).toUpperCase() + label.slice(1) + '</span>';
            }
            
            function getStatusBadge(scheduled, sent) {
                if (sent) {
                    return '<span class="badge bg-success">Sent</span>';
                } else if (scheduled && new Date(scheduled) > new Date()) {
                    return '<span class="badge bg-info">Scheduled</span>';
                } else {
                    return '<span class="badge bg-secondary">Draft</span>';
                }
            }
            
            function formatTarget(target) {
                const targets = {
                    'all_customers': 'All Customers',
                    'registered_users': 'Registered Users',
                    'subscribers': 'Newsletter Subscribers'
                };
                return targets[target] || target;
            }
            
            function getAudienceInfo(target) {
                const info = {
                    'all_customers': 'This notification will be sent to all customers, including guests and registered users.',
                    'registered_users': 'This notification will only be sent to registered users with active accounts.',
                    'subscribers': 'This notification will only be sent to customers who have subscribed to your newsletter.'
                };
                return info[target] || 'Audience information not available.';
            }
        });
    </script>
</body>
</html>