<?php
// admin/customers/list.php
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

// Handle user actions (delete, toggle status)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    
    if ($_GET['action'] == 'delete') {
        // Soft delete user (set deleted_at timestamp)
        $stmt = $pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
        $_SESSION['success_message'] = "Customer deleted successfully.";
    } 
    elseif ($_GET['action'] == 'toggle') {
        // Toggle user status
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $newStatus = ($user['status'] == 1) ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        $_SESSION['success_message'] = "Customer status updated successfully.";
    }
    
    // Redirect to avoid parameter resubmission
    header('Location: list.php');
    exit();
}

// Handle customer search and filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query for users with optional filters
$query = "SELECT u.*, 
                 COUNT(o.id) as order_count,
                 COALESCE(SUM(o.total_amount), 0) as total_spent,
                 MAX(o.created_at) as last_order_date
          FROM users u 
          LEFT JOIN orders o ON u.id = o.user_id 
          WHERE u.deleted_at IS NULL AND u.role = 'customer'";

$params = [];
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status) && $status != 'all') {
    $conditions[] = "u.status = ?";
    $params[] = ($status == 'active') ? 1 : 0;
}

if (!empty($role) && $role != 'all') {
    $conditions[] = "u.role = ?";
    $params[] = $role;
}

if (!empty($dateFrom)) {
    $conditions[] = "DATE(u.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = "DATE(u.created_at) <= ?";
    $params[] = $dateTo;
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " GROUP BY u.id ORDER BY u.created_at DESC";

// Execute users query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Get stats for the header
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND role = 'customer'")->fetchColumn();
$activeCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 1 AND deleted_at IS NULL AND role = 'customer'")->fetchColumn();
$customersWithOrders = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE status != 'cancelled'")->fetchColumn();

// Calculate average order value
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed'")->fetchColumn();
$avgOrderValue = $customersWithOrders > 0 ? $totalRevenue / $customersWithOrders : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - HomewareOnTap Admin</title>
    
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
        
        .bg-info-light {
            background-color: rgba(23, 162, 184, 0.15);
            color: #17a2b8;
        }
        
        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.15);
            color: #ffc107;
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
            background-color: #fff3cd;
            color: #856404;
        }
        
        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
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
        
        .stats-card {
            text-align: center;
            padding: 15px 10px;
        }
        
        .stats-card .number {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stats-card .label {
            font-size: 0.875rem;
            color: #6c757d;
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
                    <h4 class="mb-0">Manage Customers</h4>
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

        <!-- Customers Content -->
        <div class="content-section" id="customersSection">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Customer Management</h3>
                <div>
                    <button type="button" class="btn btn-outline-primary me-2">
                        <i class="fas fa-file-export me-2"></i>Export
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                        <i class="fas fa-plus me-2"></i>Add Customer
                    </button>
                </div>
            </div>

            <!-- Customer Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body stats-card">
                            <div class="card-icon bg-primary-light">
                                <i class="fas fa-users"></i>
                            </div>
                            <h5 class="card-title">Total Customers</h5>
                            <div class="number"><?php echo $totalCustomers; ?></div>
                            <p class="card-text"><span class="text-success">+8%</span> from last month</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body stats-card">
                            <div class="card-icon bg-success-light">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h5 class="card-title">Active Customers</h5>
                            <div class="number"><?php echo $activeCustomers; ?></div>
                            <p class="card-text">Regular purchasers</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body stats-card">
                            <div class="card-icon bg-info-light">
                                <i class="fas fa-cart-plus"></i>
                            </div>
                            <h5 class="card-title">Avg. Order Value</h5>
                            <div class="number">R<?php echo number_format($avgOrderValue, 2); ?></div>
                            <p class="card-text">Per customer</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-dashboard h-100">
                        <div class="card-body stats-card">
                            <div class="card-icon bg-warning-light">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <h5 class="card-title">Customers with Orders</h5>
                            <div class="number"><?php echo $customersWithOrders; ?></div>
                            <p class="card-text">Made at least one purchase</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card card-dashboard mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label for="searchCustomer" class="form-label">Search</label>
                                <input type="text" class="form-control" id="searchCustomer" name="search" 
                                       placeholder="Name or email" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="statusFilter" class="form-label">Status</label>
                                <select class="form-select" id="statusFilter" name="status">
                                    <option value="all" <?php echo ($status == 'all' || empty($status)) ? 'selected' : ''; ?>>All Status</option>
                                    <option value="active" <?php echo ($status == 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($status == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="roleFilter" class="form-label">Role</label>
                                <select class="form-select" id="roleFilter" name="role">
                                    <option value="all" <?php echo ($role == 'all' || empty($role)) ? 'selected' : ''; ?>>All Roles</option>
                                    <option value="customer" <?php echo ($role == 'customer') ? 'selected' : ''; ?>>Customer</option>
                                    <option value="admin" <?php echo ($role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="manager" <?php echo ($role == 'manager') ? 'selected' : ''; ?>>Manager</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="dateFrom" class="form-label">Date From</label>
                                <input type="date" class="form-control" id="dateFrom" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label for="dateTo" class="form-label">Date To</label>
                                <input type="date" class="form-control" id="dateTo" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                            <div class="col-md-12 d-flex justify-content-end">
                                <a href="list.php" class="btn btn-outline-secondary me-2">Reset</a>
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="card card-dashboard">
                <div class="card-body">
                    <?php display_message(); ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="customersTable">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $customer): 
                                $statusText = $customer['status'] == 1 ? 'active' : 'inactive';
                                $statusClass = $customer['status'] == 1 ? 'status-active' : 'status-inactive';
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($customer['first_name'] . ' ' . $customer['last_name']); ?>&background=random" 
                                                 alt="Customer" class="customer-avatar me-3">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></div>
                                                <small class="text-muted">Joined: <?php echo date('d M Y', strtotime($customer['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $customer['role'] == 'admin' ? 'danger' : ($customer['role'] == 'manager' ? 'warning' : 'primary'); ?>">
                                            <?php echo ucfirst($customer['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $customer['order_count']; ?></td>
                                    <td>R <?php echo number_format($customer['total_spent'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($statusText); ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-info edit-customer" 
                                                data-id="<?php echo $customer['id']; ?>"
                                                data-first-name="<?php echo htmlspecialchars($customer['first_name']); ?>"
                                                data-last-name="<?php echo htmlspecialchars($customer['last_name']); ?>"
                                                data-email="<?php echo htmlspecialchars($customer['email']); ?>"
                                                data-phone="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>"
                                                data-status="<?php echo $customer['status']; ?>"
                                                data-role="<?php echo $customer['role']; ?>"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?action=toggle&id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-outline-<?php echo ($customer['status'] == 1) ? 'warning' : 'success'; ?>" 
                                           title="<?php echo ($customer['status'] == 1) ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas fa-<?php echo ($customer['status'] == 1) ? 'eye-slash' : 'eye'; ?>"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $customer['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this customer?')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($customers)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No customers found.</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                            Add Your First Customer
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="process_customer.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="firstName" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lastName" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="customer">Customer</option>
                                <option value="admin">Admin</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="sendWelcomeEmail" name="send_welcome_email" value="1">
                            <label class="form-check-label" for="sendWelcomeEmail">
                                Send welcome email with login details
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_customer">Save Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="process_customer.php">
                    <input type="hidden" id="editCustomerId" name="customer_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editFirstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editLastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editLastName" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editEmail" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="editEmail" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editPhone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="editPhone" name="phone">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="role">
                                <option value="customer">Customer</option>
                                <option value="admin">Admin</option>
                                <option value="manager">Manager</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editPassword" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="editPassword" name="password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="edit_customer">Update Customer</button>
                    </div>
                </form>
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
            $('#customersTable').DataTable({
                pageLength: 10,
                responsive: true,
                order: [[0, 'asc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search customers..."
                }
            });
            
            // Handle edit customer button clicks
            $('.edit-customer').on('click', function() {
                const id = $(this).data('id');
                const firstName = $(this).data('first-name');
                const lastName = $(this).data('last-name');
                const email = $(this).data('email');
                const phone = $(this).data('phone');
                const status = $(this).data('status');
                const role = $(this).data('role');
                
                $('#editCustomerId').val(id);
                $('#editFirstName').val(firstName);
                $('#editLastName').val(lastName);
                $('#editEmail').val(email);
                $('#editPhone').val(phone);
                $('#editStatus').val(status);
                $('#editRole').val(role);
                
                $('#editCustomerModal').modal('show');
            });
            
            // Reset form when add modal is closed
            $('#addCustomerModal').on('hidden.bs.modal', function() {
                $(this).find('form')[0].reset();
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