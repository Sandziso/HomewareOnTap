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
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .card-dashboard {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #8B6145;
            border-color: #8B6145;
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .top-navbar {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .navbar-toggle {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: var(--dark);
            display: none;
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
                    <h4 class="mb-0">System Notifications</h4>
                </div>
                <div class="dropdown">
                    <a class="dropdown-toggle d-flex align-items-center text-decoration-none" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://ui-avatars.com/api/?name=Admin&background=A67B5B&color=fff" alt="Admin" class="rounded-circle me-2" width="32" height="32">
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

        <!-- Notifications Content -->
        <div class="content-section" id="notificationsSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Manage Notifications</h3>
                <div class="btn-group">
                    <a href="?status=all" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                    <a href="?status=sent" class="btn btn-sm <?= $statusFilter === 'sent' ? 'btn-primary' : 'btn-outline-primary'; ?>">Sent</a>
                    <a href="?status=scheduled" class="btn btn-sm <?= $statusFilter === 'scheduled' ? 'btn-primary' : 'btn-outline-primary'; ?>">Scheduled</a>
                    <a href="?status=draft" class="btn btn-sm <?= $statusFilter === 'draft' ? 'btn-primary' : 'btn-outline-primary'; ?>">Draft</a>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNotificationModal">
                    <i class="fas fa-plus me-2"></i>Add Notification
                </button>
            </div>

            <?php 
            // Display success/error messages
            if (isset($_SESSION['success_message'])) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                echo $_SESSION['success_message'];
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
                unset($_SESSION['success_message']);
            }
            
            if (isset($_SESSION['error_message'])) {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                echo $_SESSION['error_message'];
                echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                echo '</div>';
                unset($_SESSION['error_message']);
            }
            ?>

            <!-- Notifications Table -->
            <div class="card card-dashboard">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Target Audience</th>
                                    <th>Status</th>
                                    <th>Scheduled At</th>
                                    <th>Sent At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($notifications)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-bell fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No notifications found.</p>
                                            <?php if ($statusFilter !== 'all'): ?>
                                                <a href="?status=all" class="btn btn-sm btn-primary">View All Notifications</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <tr>
                                            <td><?= $notif['id']; ?></td>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($notif['title']); ?></div>
                                                <small class="text-muted"><?= truncateText(htmlspecialchars($notif['content']), 50); ?></small>
                                            </td>
                                            <td><?= getTypeBadge($notif['type']); ?></td>
                                            <td><?= ucfirst(str_replace('_', ' ', $notif['target_audience'])); ?></td>
                                            <td><?= getStatusBadge($notif['scheduled_at'], $notif['sent_at']); ?></td>
                                            <td><?= formatDate($notif['scheduled_at']); ?></td>
                                            <td><?= formatDate($notif['sent_at']); ?></td>
                                            <td class="action-buttons">
                                                <button type="button" class="btn btn-sm btn-outline-primary view-notification" 
                                                        data-notification-id="<?= $notif['id']; ?>"
                                                        data-title="<?= htmlspecialchars($notif['title']); ?>"
                                                        data-content="<?= htmlspecialchars($notif['content']); ?>"
                                                        title="View">
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
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="notification_id" value="<?= $notif['id']; ?>">
                                                        <input type="hidden" name="action" value="send_now">
                                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Send Now">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="notification_id" value="<?= $notif['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this notification?')" 
                                                            title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Notification pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Notification Modal -->
    <div class="modal fade" id="addNotificationModal" tabindex="-1" aria-labelledby="addNotificationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addNotificationModalLabel">Add New Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="info">Information</option>
                                <option value="alert">Alert</option>
                                <option value="promo">Promotion</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="target_audience" class="form-label">Target Audience</label>
                            <select class="form-select" id="target_audience" name="target_audience" required>
                                <option value="all_customers">All Customers</option>
                                <option value="registered_users">Registered Users</option>
                                <option value="subscribers">Subscribers</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="scheduled_at" class="form-label">Scheduled At (optional)</label>
                            <input type="datetime-local" class="form-control" id="scheduled_at" name="scheduled_at">
                            <div class="form-text">Leave empty to send immediately or save as draft</div>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="notification_id" id="edit_notification_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editNotificationModalLabel">Edit Notification</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label for="edit_content" class="form-label">Content</label>
                            <textarea class="form-control" id="edit_content" name="content" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_type" class="form-label">Type</label>
                            <select class="form-select" id="edit_type" name="type" required>
                                <option value="info">Information</option>
                                <option value="alert">Alert</option>
                                <option value="promo">Promotion</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_target_audience" class="form-label">Target Audience</label>
                            <select class="form-select" id="edit_target_audience" name="target_audience" required>
                                <option value="all_customers">All Customers</option>
                                <option value="registered_users">Registered Users</option>
                                <option value="subscribers">Subscribers</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_scheduled_at" class="form-label">Scheduled At (optional)</label>
                            <input type="datetime-local" class="form-control" id="edit_scheduled_at" name="scheduled_at">
                            <div class="form-text">Leave empty to send immediately or save as draft</div>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewNotificationModalLabel">Notification Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h4 id="view_title" class="mb-3"></h4>
                    <div class="border p-3 bg-light rounded">
                        <p id="view_content" class="mb-0"></p>
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
                
                $('#view_title').text(title);
                $('#view_content').text(content);
                
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
        });
    </script>
</body>
</html>