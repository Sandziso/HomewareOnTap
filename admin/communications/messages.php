<?php
// admin/communications/messages.php
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

// Handle message actions (reply, delete, mark as read)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $messageId = intval($_POST['message_id'] ?? 0);

    if ($action === 'reply' && $messageId) {
        $replyContent = sanitize_input($_POST['reply_content']);
        $adminId = get_current_user_id();
        
        try {
            $pdo->beginTransaction();
            
            // Insert reply
            $stmt = $pdo->prepare("INSERT INTO message_replies (message_id, admin_id, reply_content, created_at) VALUES (?, ?, ?, NOW())");
            $success = $stmt->execute([$messageId, $adminId, $replyContent]);
            
            if ($success) {
                // Update message status
                $stmt = $pdo->prepare("UPDATE messages SET status = 'replied', is_read = 1, replied_at = NOW(), read_at = COALESCE(read_at, NOW()) WHERE id = ?");
                $stmt->execute([$messageId]);
                
                $pdo->commit();
                $_SESSION['success_message'] = 'Reply sent successfully.';
            } else {
                $pdo->rollBack();
                $_SESSION['error_message'] = 'Failed to send reply.';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error sending reply: " . $e->getMessage());
            $_SESSION['error_message'] = 'Failed to send reply.';
        }
    } elseif ($action === 'delete' && $messageId) {
        try {
            $pdo->beginTransaction();
            // Delete replies first
            $stmt = $pdo->prepare("DELETE FROM message_replies WHERE message_id = ?");
            $stmt->execute([$messageId]);
            
            // Delete the message
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
            $success = $stmt->execute([$messageId]);
            
            if ($success) {
                $pdo->commit();
                $_SESSION['success_message'] = 'Message deleted successfully.';
            } else {
                $pdo->rollBack();
                $_SESSION['error_message'] = 'Failed to delete message.';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error deleting message: " . $e->getMessage());
            $_SESSION['error_message'] = 'Failed to delete message.';
        }
    } elseif ($action === 'mark_read' && $messageId) {
        try {
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW(), status = 'read' WHERE id = ? AND is_read = 0");
            $success = $stmt->execute([$messageId]);
            
            if ($success) {
                if ($stmt->rowCount() > 0) {
                    $_SESSION['success_message'] = 'Message marked as read.';
                } else {
                    $_SESSION['info_message'] = 'Message was already marked as read.';
                }
            } else {
                $_SESSION['error_message'] = 'Failed to update message status.';
            }
        } catch (PDOException $e) {
            error_log("Error marking message as read: " . $e->getMessage());
            $_SESSION['error_message'] = 'Failed to update message status.';
        }
    }
    
    // Redirect to avoid form resubmission
    $redirect_url = 'messages.php';
    $get_params = [];
    if (isset($_GET['page'])) $get_params['page'] = $_GET['page'];
    if (isset($_GET['status'])) $get_params['status'] = $_GET['status'];
    if (isset($_GET['search'])) $get_params['search'] = $_GET['search'];

    if (!empty($get_params)) {
        $redirect_url .= '?' . http_build_query($get_params);
    }
    
    header("Location: $redirect_url");
    exit();
}

// Pagination and filtering
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 15; // Items per page
$offset = ($page - 1) * $limit;
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build query for messages
$query = "SELECT m.*, u.first_name, u.last_name, u.id as user_id
          FROM messages m 
          LEFT JOIN users u ON m.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($statusFilter !== 'all') {
    if ($statusFilter === 'unread') {
        $query .= " AND m.is_read = 0";
    } elseif ($statusFilter === 'replied') {
        $query .= " AND m.status = 'replied'";
    } elseif ($statusFilter === 'read') {
        $query .= " AND m.is_read = 1 AND m.status != 'replied'";
    }
}

if (!empty($searchQuery)) {
    $query .= " AND (m.name LIKE ? OR m.email LIKE ? OR m.subject LIKE ? OR m.message LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $searchParam = "%$searchQuery%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

$query .= " ORDER BY m.is_read ASC, m.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Fetch messages
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching messages: " . $e->getMessage());
    $messages = [];
    $_SESSION['error_message'] = 'Error retrieving messages from the database.';
}

// Get total message count for pagination
$countQuery = "SELECT COUNT(*) as total FROM messages m LEFT JOIN users u ON m.user_id = u.id WHERE 1=1";
$countParams = [];

if ($statusFilter !== 'all') {
    if ($statusFilter === 'unread') {
        $countQuery .= " AND m.is_read = 0";
    } elseif ($statusFilter === 'replied') {
        $countQuery .= " AND m.status = 'replied'";
    } elseif ($statusFilter === 'read') {
        $countQuery .= " AND m.is_read = 1 AND m.status != 'replied'";
    }
}

if (!empty($searchQuery)) {
    $countQuery .= " AND (m.name LIKE ? OR m.email LIKE ? OR m.subject LIKE ? OR m.message LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $searchParam = "%$searchQuery%";
    $countParams = array_merge($countParams, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

try {
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalMessages = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error counting messages: " . $e->getMessage());
    $totalMessages = 0;
}

$totalPages = ceil($totalMessages / $limit);

// Helper functions
function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}

function getStatusBadge($is_read, $status) {
    if (!$is_read) {
        return '<span class="badge bg-danger">Unread</span>';
    } elseif ($status === 'replied') {
        return '<span class="badge bg-success">Replied</span>';
    } else {
        return '<span class="badge bg-secondary">Read</span>';
    }
}

function getCustomerName($message) {
    if (!empty($message['first_name']) && !empty($message['last_name'])) {
        return htmlspecialchars($message['first_name'] . ' ' . $message['last_name']);
    } elseif (!empty($message['name'])) {
        return htmlspecialchars($message['name']);
    } else {
        return 'Unknown Customer';
    }
}

// Get message replies
function getMessageReplies($pdo, $message_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT mr.*, u.first_name, u.last_name 
            FROM message_replies mr 
            LEFT JOIN users u ON mr.admin_id = u.id 
            WHERE mr.message_id = ? 
            ORDER BY mr.created_at ASC
        ");
        $stmt->execute([$message_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching message replies: " . $e->getMessage());
        return [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Messages - HomewareOnTap Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            margin: 0 2px;
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
        
        .message-preview {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .unread-row {
            background-color: #fff9e6;
            font-weight: 600;
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
        
        .status-filter {
            border-radius: 20px;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--dark);
            background-color: var(--light);
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .pagination .page-link {
            color: var(--primary);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .message-reply {
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary);
            padding: 10px 15px;
            margin-bottom: 10px;
            border-radius: 0 4px 4px 0;
        }
        
        .message-reply.admin-reply {
            background-color: #e8f4fd;
            border-left-color: #0d6efd;
        }
        
        .message-content {
            white-space: pre-wrap;
            line-height: 1.5;
        }
        
        .stats-card {
            background: linear-gradient(135deg, var(--primary), #8B6145);
            color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .stats-card i {
            font-size: 2rem;
            opacity: 0.8;
        }
        
        .stats-card .count {
            font-size: 2rem;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .stats-card .label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <nav class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button class="navbar-toggle me-3" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="mb-0">Customer Messages</h4>
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

        <div class="content-section" id="messagesSection">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="count"><?= $totalMessages; ?></div>
                                <div class="label">Total Messages</div>
                            </div>
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php
                                $unreadCount = 0;
                                foreach ($messages as $msg) {
                                    if (!$msg['is_read']) $unreadCount++;
                                }
                                ?>
                                <div class="count"><?= $unreadCount; ?></div>
                                <div class="label">Unread Messages</div>
                            </div>
                            <i class="fas fa-envelope-open"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php
                                $repliedCount = 0;
                                foreach ($messages as $msg) {
                                    if ($msg['status'] === 'replied') $repliedCount++;
                                }
                                ?>
                                <div class="count"><?= $repliedCount; ?></div>
                                <div class="label">Replied Messages</div>
                            </div>
                            <i class="fas fa-reply"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="count"><?= count($messages); ?></div>
                                <div class="label">Current Page</div>
                            </div>
                            <i class="fas fa-list"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Message Management</h3>
                <div class="btn-group">
                    <a href="?status=all<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?> status-filter">All</a>
                    <a href="?status=unread<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn btn-sm <?= $statusFilter === 'unread' ? 'btn-primary' : 'btn-outline-primary'; ?> status-filter">Unread</a>
                    <a href="?status=read<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn btn-sm <?= $statusFilter === 'read' ? 'btn-primary' : 'btn-outline-primary'; ?> status-filter">Read</a>
                    <a href="?status=replied<?= !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" class="btn btn-sm <?= $statusFilter === 'replied' ? 'btn-primary' : 'btn-outline-primary'; ?> status-filter">Replied</a>
                </div>
            </div>

            <?php display_message(); ?>

            <div class="card card-dashboard mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-center">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by customer name, email or subject" value="<?= htmlspecialchars($searchQuery); ?>">
                                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary me-2" type="submit">Search</button>
                            <a href="messages.php" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-dashboard">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="messagesTable">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="15%">Customer</th>
                                    <th width="15%">Email</th>
                                    <th width="15%">Subject</th>
                                    <th width="20%">Message</th>
                                    <th width="10%">Date</th>
                                    <th width="10%">Status</th>
                                    <th width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 empty-state">
                                            <i class="fas fa-envelope-open-text"></i>
                                            <p class="mb-0">No messages found matching your criteria.</p>
                                            <?php if (!empty($searchQuery) || $statusFilter !== 'all'): ?>
                                                <a href="messages.php" class="btn btn-primary mt-3">View All Messages</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $msg): ?>
                                        <tr class="<?= !$msg['is_read'] ? 'unread-row' : ''; ?>">
                                            <td>#MSG-<?= str_pad($msg['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                            <td>
                                                <div class="fw-bold"><?= getCustomerName($msg); ?></div>
                                                <?php if (isset($msg['user_id'])): ?>
                                                <small class="text-muted">User ID: <?= $msg['user_id']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($msg['email']); ?></td>
                                            <td><?= htmlspecialchars($msg['subject']); ?></td>
                                            <td class="message-preview" title="<?= htmlspecialchars($msg['message']); ?>">
                                                <?= truncateText(htmlspecialchars($msg['message']), 50); ?>
                                            </td>
                                            <td><?= formatDate($msg['created_at']); ?></td>
                                            <td>
                                                <?= getStatusBadge($msg['is_read'], $msg['status']); ?>
                                            </td>
                                            <td class="action-buttons">
                                                <div class="d-flex flex-wrap">
                                                    <button type="button" class="btn btn-sm btn-outline-primary view-message" 
                                                            data-message-id="<?= $msg['id']; ?>"
                                                            data-customer-name="<?= getCustomerName($msg); ?>"
                                                            data-customer-email="<?= htmlspecialchars($msg['email']); ?>"
                                                            data-subject="<?= htmlspecialchars($msg['subject']); ?>"
                                                            data-message="<?= htmlspecialchars($msg['message']); ?>"
                                                            data-created-at="<?= formatDate($msg['created_at']); ?>"
                                                            title="View Message">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <button type="button" class="btn btn-sm btn-outline-success reply-message" 
                                                            data-message-id="<?= $msg['id']; ?>"
                                                            data-customer-name="<?= getCustomerName($msg); ?>"
                                                            data-customer-email="<?= htmlspecialchars($msg['email']); ?>"
                                                            data-subject="<?= htmlspecialchars($msg['subject']); ?>"
                                                            title="Reply">
                                                        <i class="fas fa-reply"></i>
                                                    </button>
                                                    
                                                    <?php if (!$msg['is_read']): ?>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="message_id" value="<?= $msg['id']; ?>">
                                                            <input type="hidden" name="action" value="mark_read">
                                                            <input type="hidden" name="current_page" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Mark as Read" onclick="return confirm('Mark this message as read?')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="message_id" value="<?= $msg['id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="current_page" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                onclick="return confirm('Are you sure you want to delete this message? This action cannot be undone.')" 
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

                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Message pagination">
                            <ul class="pagination justify-content-center mb-0">
                                
                                <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?= max(1, $page - 1); ?>&status=<?= $statusFilter; ?>&search=<?= urlencode($searchQuery); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>

                                <?php 
                                // Display a maximum of 5 page numbers around the current page
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                // Adjust start and end to always show 5 pages if totalPages allows
                                if ($endPage - $startPage < 4) {
                                    if ($startPage > 1) $startPage = max(1, $endPage - 4);
                                    if ($endPage < $totalPages) $endPage = min($totalPages, $startPage + 4);
                                }

                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                    <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?= $i; ?>&status=<?= $statusFilter; ?>&search=<?= urlencode($searchQuery); ?>">
                                            <?= $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?= min($totalPages, $page + 1); ?>&status=<?= $statusFilter; ?>&search=<?= urlencode($searchQuery); ?>" aria-label="Next">
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

    <!-- View Message Modal -->
    <div class="modal fade" id="viewMessageModal" tabindex="-1" aria-labelledby="viewMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewMessageModalLabel">Message Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Customer:</strong> <span id="viewCustomerName"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Email:</strong> <span id="viewCustomerEmail"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Date:</strong> <span id="viewCreatedAt"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Subject:</strong> <span id="viewSubject"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Message:</strong>
                        <div class="border p-3 mt-2 bg-light rounded message-content" id="viewMessageContent"></div>
                    </div>
                    
                    <div id="viewMessageReplies" class="mt-4">
                        <h6>Replies</h6>
                        <div id="repliesContainer"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary reply-message-from-view" data-bs-dismiss="modal">Reply</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Message Modal -->
    <div class="modal fade" id="replyMessageModal" tabindex="-1" aria-labelledby="replyMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="replyMessageModalLabel">Reply to Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="message_id" id="replyMessageId">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="current_page" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']); ?>"> 

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Customer:</label>
                            <p class="form-control-plaintext" id="replyCustomerName"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email:</label>
                            <p class="form-control-plaintext" id="replyCustomerEmail"></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subject:</label>
                            <p class="form-control-plaintext" id="replySubject"></p>
                        </div>
                        <div class="mb-3">
                            <label for="replyContent" class="form-label fw-bold">Your Reply</label>
                            <textarea class="form-control" id="replyContent" name="reply_content" rows="6" placeholder="Type your reply here..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Reply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Function to populate reply modal
            function setupReplyModal(messageId, customerName, customerEmail, subject) {
                $('#replyMessageId').val(messageId);
                $('#replyCustomerName').text(customerName);
                $('#replyCustomerEmail').text(customerEmail);
                $('#replySubject').text('Re: ' + subject);
                $('#replyContent').val('');
                $('#replyMessageModal').modal('show');
            }

            // Handle view message button clicks
            $(document).on('click', '.view-message', function() {
                const customerName = $(this).data('customer-name');
                const customerEmail = $(this).data('customer-email');
                const subject = $(this).data('subject');
                const message = $(this).data('message');
                const createdAt = $(this).data('created-at');
                const messageId = $(this).data('message-id');
                
                $('#viewCustomerName').text(customerName);
                $('#viewCustomerEmail').text(customerEmail);
                $('#viewSubject').text(subject);
                $('#viewMessageContent').text(message);
                $('#viewCreatedAt').text(createdAt);
                
                // Set data attributes on the quick reply button in the view modal
                $('.reply-message-from-view').data({
                    'message-id': messageId,
                    'customer-name': customerName,
                    'customer-email': customerEmail,
                    'subject': subject
                });
                
                // Load replies via AJAX
                $.ajax({
                    url: 'get_message_replies.php',
                    type: 'GET',
                    data: { message_id: messageId },
                    success: function(response) {
                        $('#repliesContainer').html(response);
                    },
                    error: function() {
                        $('#repliesContainer').html('<p class="text-muted">Error loading replies.</p>');
                    }
                });
                
                $('#viewMessageModal').modal('show');
            });
            
            // Handle reply message button clicks (from table)
            $(document).on('click', '.reply-message', function() {
                setupReplyModal(
                    $(this).data('message-id'),
                    $(this).data('customer-name'),
                    $(this).data('customer-email'),
                    $(this).data('subject')
                );
            });
            
            // Handle quick reply message button clicks (from view modal)
            $(document).on('click', '.reply-message-from-view', function() {
                setupReplyModal(
                    $(this).data('message-id'),
                    $(this).data('customer-name'),
                    $(this).data('customer-email'),
                    $(this).data('subject')
                );
            });
            
            // Sidebar toggle functionality
            const sidebar = $('#adminSidebar');
            const overlay = $('#sidebarOverlay');
            const body = $('body');

            $('#sidebarToggle').click(function() {
                sidebar.toggleClass('active');
                overlay.toggleClass('active');
                body.toggleClass('overflow-hidden');
            });
            
            // Close sidebar when clicking overlay
            overlay.click(function() {
                sidebar.removeClass('active');
                overlay.removeClass('active');
                body.removeClass('overflow-hidden');
            });
            
            // Auto-close sidebar on mobile when clicking a link
            $('.admin-menu .nav-link:not(.dropdown-toggle)').click(function() {
                if (window.innerWidth < 992) {
                    sidebar.removeClass('active');
                    overlay.removeClass('active');
                    body.removeClass('overflow-hidden');
                }
            });
        });
    </script>
</body>
</html>