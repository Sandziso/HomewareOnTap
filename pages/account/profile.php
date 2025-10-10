<?php
// account/profile.php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php?redirect=profile');
    exit();
}

$user_id = get_current_user_id();
$database = new Database();
$pdo = $database->getConnection();

// Get user data
$user = null;
$addresses = [];
$recent_orders = [];

try {
    // Get user information
    $user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error_message'] = "User not found.";
        header('Location: login.php');
        exit();
    }
    
    // Get user addresses
    $address_stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $address_stmt->execute([$user_id]);
    $addresses = $address_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent orders
    $orders_stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $orders_stmt->execute([$user_id]);
    $recent_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Profile data error: " . $e->getMessage());
    $_SESSION['error_message'] = "Error loading profile data.";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $phone = sanitize_input($_POST['phone'] ?? '');
    
    try {
        $update_stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->execute([$first_name, $last_name, $phone, $user_id]);
        
        $_SESSION['success_message'] = "Profile updated successfully!";
        
        // Update session data
        $_SESSION['user']['first_name'] = $first_name;
        $_SESSION['user']['last_name'] = $last_name;
        
        header('Location: profile.php');
        exit();
        
    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        $_SESSION['error_message'] = "Error updating profile. Please try again.";
    }
}

$pageTitle = "My Profile - HomewareOnTap";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $pageTitle; ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .profile-header {
            background: linear-gradient(135deg, #A67B5B, #c19a6b);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1rem;
        }
        
        .nav-pills .nav-link.active {
            background-color: #A67B5B;
            border-color: #A67B5B;
        }
        
        .nav-pills .nav-link {
            color: #A67B5B;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }
        
        .btn-primary {
            background-color: #A67B5B;
            border-color: #A67B5B;
        }
        
        .btn-primary:hover {
            background-color: #8B6145;
            border-color: #8B6145;
        }
    </style>
</head>

<body>
    <!-- Include Header -->
    <?php include_once '../../includes/header.php'; ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                    <p class="mb-0">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="orders.php" class="btn btn-light me-2">
                        <i class="fas fa-shopping-bag me-1"></i> My Orders
                    </a>
                    <a href="settings.php" class="btn btn-outline-light">
                        <i class="fas fa-cog me-1"></i> Settings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" href="profile.php">
                                    <i class="fas fa-user me-2"></i> Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="orders.php">
                                    <i class="fas fa-shopping-bag me-2"></i> Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="addresses.php">
                                    <i class="fas fa-map-marker-alt me-2"></i> Addresses
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="wishlist.php">
                                    <i class="fas fa-heart me-2"></i> Wishlist
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="settings.php">
                                    <i class="fas fa-cog me-2"></i> Settings
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="col-md-9">
                <!-- Display Messages -->
                <?php display_message(); ?>

                <div class="row">
                    <!-- Personal Information -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Personal Information</h5>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4 fw-bold">Name:</div>
                                    <div class="col-sm-8"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 fw-bold">Email:</div>
                                    <div class="col-sm-8"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 fw-bold">Phone:</div>
                                    <div class="col-sm-8"><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-4 fw-bold">Member Since:</div>
                                    <div class="col-sm-8"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Default Address -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Default Address</h5>
                                <a href="addresses.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit me-1"></i> Manage
                                </a>
                            </div>
                            <div class="card-body">
                                <?php 
                                $default_address = array_filter($addresses, function($addr) {
                                    return $addr['is_default'] == 1;
                                });
                                $default_address = reset($default_address);
                                
                                if ($default_address): 
                                ?>
                                    <p class="mb-2">
                                        <strong><?php echo htmlspecialchars($default_address['first_name'] . ' ' . $default_address['last_name']); ?></strong>
                                    </p>
                                    <p class="mb-2"><?php echo htmlspecialchars($default_address['street']); ?></p>
                                    <p class="mb-2">
                                        <?php echo htmlspecialchars($default_address['city'] . ', ' . $default_address['province'] . ' ' . $default_address['postal_code']); ?>
                                    </p>
                                    <p class="mb-0"><?php echo htmlspecialchars($default_address['country']); ?></p>
                                    <p class="mb-0">Phone: <?php echo htmlspecialchars($default_address['phone']); ?></p>
                                <?php else: ?>
                                    <p class="text-muted mb-3">No default address set</p>
                                    <a href="addresses.php?action=add" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus me-1"></i> Add Address
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Orders</h5>
                        <a href="orders.php" class="btn btn-sm btn-primary">View All Orders</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No orders found</p>
                                <a href="../shop.php" class="btn btn-primary">Start Shopping</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            <td><?php echo $order['item_count']; ?> item(s)</td>
                                            <td>R<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="order-status status-<?php echo strtolower($order['status']); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include_once '../../includes/footer.php'; ?>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Auto-show modal if there was an error
            <?php if (isset($_SESSION['error_message']) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                $('#editProfileModal').modal('show');
            <?php endif; ?>
        });
    </script>
</body>
</html>