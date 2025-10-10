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
function getInitials($name) {
    $names = explode(' ', $name);
    $initials = '';
    foreach ($names as $n) {
        $initials .= strtoupper(substr($n, 0, 1));
    }
    return $initials;
}
?>

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg mb-4">
    <div class="container-fluid">
        <button class="btn btn-sm btn-light" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="d-flex align-items-center">
            <div class="dropdown me-3">
                <a href="#" class="dropdown-toggle text-dark text-decoration-none" role="button" 
                   id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <span class="badge bg-danger rounded-pill"><?php echo isset($recentOrders) ? count($recentOrders) : 0; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                    <?php if (isset($recentOrders) && !empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <li><a class="dropdown-item" href="orders.php?order_id=<?php echo $order['id']; ?>">Order #<?php echo $order['id']; ?> - <?php echo ucfirst($order['status']); ?></a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a class="dropdown-item" href="#">No recent orders</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="dropdown">
                <a href="#" class="dropdown-toggle text-dark text-decoration-none d-flex align-items-center" 
                   role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 35px; height: 35px;">
                        <?php echo getInitials($user['name']); ?>
                    </div>
                    <span class="ms-2"><?php echo htmlspecialchars($user['name']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="addresses.php"><i class="fas fa-map-marker-alt me-2"></i> Addresses</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>