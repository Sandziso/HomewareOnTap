<?php
// admin/coupons/manage.php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in and has admin privileges
if (!isAdminLoggedIn()) {
    header('Location: ../../pages/auth/login.php?redirect=admin');
    exit();
}

// Handle coupon actions (delete, toggle status)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $couponId = intval($_GET['id']);
    
    if ($_GET['action'] == 'delete') {
        // Delete coupon
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$couponId]);
        $_SESSION['success_message'] = "Coupon deleted successfully.";
    } 
    elseif ($_GET['action'] == 'toggle') {
        // Toggle coupon status
        $stmt = $pdo->prepare("SELECT is_active FROM coupons WHERE id = ?");
        $stmt->execute([$couponId]);
        $coupon = $stmt->fetch();
        
        $newStatus = ($coupon['is_active'] == 1) ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $couponId]);
        $_SESSION['success_message'] = "Coupon status updated successfully.";
    }
    
    // Redirect to avoid form resubmission
    header('Location: manage.php');
    exit();
}

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$discount_type = isset($_GET['discount_type']) ? $_GET['discount_type'] : '';

// Build query for coupons with optional filters
$query = "SELECT * FROM coupons WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (code LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status) && $status != 'all') {
    $query .= " AND is_active = ?";
    $params[] = ($status == 'active') ? 1 : 0;
}

if (!empty($discount_type) && $discount_type != 'all') {
    $query .= " AND discount_type = ?";
    $params[] = $discount_type;
}

$query .= " ORDER BY created_at DESC";

// Execute coupons query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$coupons = $stmt->fetchAll();

// Get stats for the header
$totalCoupons = $pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
$activeCoupons = $pdo->query("SELECT COUNT(*) FROM coupons WHERE is_active = 1")->fetchColumn();
$expiredCoupons = $pdo->query("SELECT COUNT(*) FROM coupons WHERE end_date < NOW() AND is_active = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Coupons - HomewareOnTap Admin</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
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
        
        .bg-primary-light {
            background-color: rgba(166, 123, 91, 0.15);
            color: var(--primary);
        }
        
        .bg-success-light {
            background-color: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }
        
        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }
        
        .bg-danger-light {
            background-color: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #8B6145;
            border-color: #8B6145;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-expired {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .discount-badge {
            background-color: var(--primary);
            color: white;
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
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .usage-progress {
            height: 6px;
            margin-top: 5px;
        }
    </style>
</head>

<body>
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
                    <h4 class="mb-0">Manage Coupons</h4>
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

        <!-- Coupons Content -->
        <div class="content-section" id="couponsSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Manage Coupons</h3>
                <a href="add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add New Coupon</a>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-primary-light">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <h5 class="card-title">Total Coupons</h5>
                            <h2 class="fw-bold"><?php echo $totalCoupons; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-success-light">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5 class="card-title">Active Coupons</h5>
                            <h2 class="fw-bold"><?php echo $activeCoupons; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body">
                            <div class="card-icon bg-warning-light">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h5 class="card-title">Expired Coupons</h5>
                            <h2 class="fw-bold"><?php echo $expiredCoupons; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="card card-dashboard mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Coupon code or description" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="all" <?php echo ($status == 'all') ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($status == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="discount_type" class="form-label">Discount Type</label>
                                <select class="form-select" id="discount_type" name="discount_type">
                                    <option value="all" <?php echo ($discount_type == 'all') ? 'selected' : ''; ?>>All Types</option>
                                    <option value="percentage" <?php echo ($discount_type == 'percentage') ? 'selected' : ''; ?>>Percentage</option>
                                    <option value="fixed" <?php echo ($discount_type == 'fixed') ? 'selected' : ''; ?>>Fixed Amount</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                                <a href="manage.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Coupons Table -->
            <div class="card card-dashboard">
                <div class="card-body">
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="couponsTable">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Discount</th>
                                    <th>Min Cart</th>
                                    <th>Usage</th>
                                    <th>Dates</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $coupon): 
                                    $isExpired = $coupon['end_date'] && strtotime($coupon['end_date']) < time();
                                    $usagePercentage = $coupon['usage_limit'] ? ($coupon['used_count'] / $coupon['usage_limit']) * 100 : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($coupon['code']); ?></div>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($coupon['description'] ?: 'No description'); ?>
                                    </td>
                                    <td>
                                        <span class="badge discount-badge">
                                            <?php if ($coupon['discount_type'] == 'percentage'): ?>
                                                <?php echo $coupon['discount_value']; ?>%
                                            <?php else: ?>
                                                R<?php echo number_format($coupon['discount_value'], 2); ?>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ($coupon['maximum_discount']): ?>
                                        <small class="text-muted d-block">Max: R<?php echo number_format($coupon['maximum_discount'], 2); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($coupon['min_cart_total'] > 0): ?>
                                            R<?php echo number_format($coupon['min_cart_total'], 2); ?>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-between">
                                            <span><?php echo $coupon['used_count']; ?></span>
                                            <?php if ($coupon['usage_limit']): ?>
                                            <span class="text-muted">/ <?php echo $coupon['usage_limit']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($coupon['usage_limit']): ?>
                                        <div class="progress usage-progress">
                                            <div class="progress-bar <?php echo $usagePercentage >= 80 ? 'bg-danger' : 'bg-success'; ?>" 
                                                 role="progressbar" style="width: <?php echo min($usagePercentage, 100); ?>%">
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <div><strong>Start:</strong> <?php echo $coupon['start_date'] ? date('M j, Y', strtotime($coupon['start_date'])) : 'Immediate'; ?></div>
                                            <div><strong>End:</strong> <?php echo $coupon['end_date'] ? date('M j, Y', strtotime($coupon['end_date'])) : 'No expiry'; ?></div>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($isExpired && $coupon['is_active']): ?>
                                            <span class="status-badge status-expired">Expired</span>
                                        <?php else: ?>
                                            <span class="status-badge status-<?php echo $coupon['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $coupon['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="edit.php?id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=toggle&id=<?php echo $coupon['id']; ?>" 
                                           class="btn btn-sm btn-outline-<?php echo ($coupon['is_active']) ? 'warning' : 'success'; ?>" 
                                           title="<?php echo ($coupon['is_active']) ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo ($coupon['is_active']) ? 'eye-slash' : 'eye'; ?>"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $coupon['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this coupon?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($coupons)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No coupons found.</p>
                        <a href="add.php" class="btn btn-primary">Create Your First Coupon</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#couponsTable').DataTable({
                pageLength: 10,
                responsive: true,
                order: [[0, 'asc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search coupons..."
                }
            });
            
            // Sidebar toggle functionality
            $('#sidebarToggle').click(function() {
                $('#adminSidebar').toggleClass('active');
                $('#sidebarOverlay').toggle();
                $('body').toggleClass('overflow-hidden');
            });
            
            // Close sidebar when clicking overlay
            $('#sidebarOverlay').click(function() {
                $('#adminSidebar').removeClass('active');
                $(this).hide();
                $('body').removeClass('overflow-hidden');
            });
            
            // Auto-close sidebar on mobile when clicking a link (except dropdown toggles)
            $('.admin-menu .nav-link:not(.has-dropdown)').click(function() {
                if (window.innerWidth < 992) {
                    $('#adminSidebar').removeClass('active');
                    $('#sidebarOverlay').hide();
                    $('body').removeClass('overflow-hidden');
                }
            });
        });
    </script>
</body>
</html>