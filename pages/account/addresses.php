<?php
// File: pages/account/addresses.php

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
$editMode = false;
$currentAddress = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add or update address
    if (isset($_POST['save_address'])) {
        $firstName = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
        $lastName = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
        $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
        $street = filter_var($_POST['street'], FILTER_SANITIZE_STRING);
        $city = filter_var($_POST['city'], FILTER_SANITIZE_STRING);
        $province = filter_var($_POST['province'], FILTER_SANITIZE_STRING);
        $postalCode = filter_var($_POST['postal_code'], FILTER_SANITIZE_STRING);
        $country = filter_var($_POST['country'], FILTER_SANITIZE_STRING) ?: 'South Africa';
        $addressType = filter_var($_POST['type'], FILTER_SANITIZE_STRING) ?: 'shipping';
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        $addressId = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;

        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($phone) || empty($street) || empty($city) || empty($province) || empty($postalCode)) {
            $error = 'Please fill in all required fields.';
        } else {
            try {
                // If setting as default, remove default status from other addresses of same type
                if ($isDefault) {
                    $removeDefaultQuery = "UPDATE addresses SET is_default = 0 WHERE user_id = :user_id AND type = :type";
                    $removeDefaultStmt = $pdo->prepare($removeDefaultQuery);
                    $removeDefaultStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $removeDefaultStmt->bindParam(':type', $addressType);
                    $removeDefaultStmt->execute();
                }

                // Update existing address
                if ($addressId > 0) {
                    $query = "UPDATE addresses SET 
                             first_name = :first_name, 
                             last_name = :last_name, 
                             phone = :phone, 
                             street = :street, 
                             city = :city, 
                             province = :province, 
                             postal_code = :postal_code, 
                             country = :country,
                             type = :type,
                             is_default = :is_default, 
                             updated_at = NOW() 
                             WHERE id = :id AND user_id = :user_id";
                    $stmt = $pdo->prepare($query);
                    $stmt->bindParam(':id', $addressId, PDO::PARAM_INT);
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $success = 'Address updated successfully.';
                } 
                // Add new address
                else {
                    $query = "INSERT INTO addresses (user_id, first_name, last_name, phone, street, city, province, postal_code, country, type, is_default) 
                             VALUES (:user_id, :first_name, :last_name, :phone, :street, :city, :province, :postal_code, :country, :type, :is_default)";
                    $stmt = $pdo->prepare($query);
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $success = 'Address added successfully.';
                }

                // Bind common parameters
                $stmt->bindParam(':first_name', $firstName);
                $stmt->bindParam(':last_name', $lastName);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':street', $street);
                $stmt->bindParam(':city', $city);
                $stmt->bindParam(':province', $province);
                $stmt->bindParam(':postal_code', $postalCode);
                $stmt->bindParam(':country', $country);
                $stmt->bindParam(':type', $addressType);
                $stmt->bindParam(':is_default', $isDefault, PDO::PARAM_INT);

                $stmt->execute();

            } catch (PDOException $e) {
                $error = 'An error occurred while saving your address. Please try again.';
                error_log("Address save error: " . $e->getMessage());
            }
        }
    }
    // Set default address
    elseif (isset($_POST['set_default'])) {
        $addressId = (int)$_POST['address_id'];
        
        try {
            // Get address type first
            $getTypeQuery = "SELECT type FROM addresses WHERE id = :id AND user_id = :user_id";
            $getTypeStmt = $pdo->prepare($getTypeQuery);
            $getTypeStmt->bindParam(':id', $addressId, PDO::PARAM_INT);
            $getTypeStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $getTypeStmt->execute();
            $addressType = $getTypeStmt->fetch(PDO::FETCH_ASSOC)['type'];
            
            // Remove default status from all addresses of same type
            $removeDefaultQuery = "UPDATE addresses SET is_default = 0 WHERE user_id = :user_id AND type = :type";
            $removeDefaultStmt = $pdo->prepare($removeDefaultQuery);
            $removeDefaultStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $removeDefaultStmt->bindParam(':type', $addressType);
            $removeDefaultStmt->execute();
            
            // Set the selected address as default
            $setDefaultQuery = "UPDATE addresses SET is_default = 1, updated_at = NOW() WHERE id = :id AND user_id = :user_id";
            $setDefaultStmt = $pdo->prepare($setDefaultQuery);
            $setDefaultStmt->bindParam(':id', $addressId, PDO::PARAM_INT);
            $setDefaultStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $setDefaultStmt->execute();
            
            $success = 'Default address updated successfully.';
        } catch (PDOException $e) {
            $error = 'An error occurred while setting the default address. Please try again.';
            error_log("Set default address error: " . $e->getMessage());
        }
    }
    // Delete address
    elseif (isset($_POST['delete_address'])) {
        $addressId = (int)$_POST['address_id'];
        
        try {
            $query = "DELETE FROM addresses WHERE id = :id AND user_id = :user_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':id', $addressId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = 'Address deleted successfully.';
        } catch (PDOException $e) {
            $error = 'An error occurred while deleting the address. Please try again.';
            error_log("Delete address error: " . $e->getMessage());
        }
    }
}

// Handle edit request
if (isset($_GET['edit'])) {
    $addressId = (int)$_GET['edit'];
    
    try {
        $query = "SELECT * FROM addresses WHERE id = :id AND user_id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $addressId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $currentAddress = $stmt->fetch(PDO::FETCH_ASSOC);
        $editMode = true;
    } catch (PDOException $e) {
        $error = 'Unable to fetch address details. Please try again.';
        error_log("Fetch address error: " . $e->getMessage());
    }
}

// Fetch all addresses for the user
try {
    $query = "SELECT * FROM addresses WHERE user_id = :user_id ORDER BY type, is_default DESC, created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Unable to fetch your addresses. Please try again.';
    error_log("Fetch addresses error: " . $e->getMessage());
}

// Get recent orders for topbar notifications
try {
    $ordersQuery = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY order_date DESC LIMIT 5";
    $ordersStmt = $pdo->prepare($ordersQuery);
    $ordersStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $ordersStmt->execute();
    $recentOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentOrders = [];
    error_log("Recent orders error: " . $e->getMessage());
}

// Set page title
$pageTitle = "Manage Addresses - HomewareOnTap";
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
    /* Global Styles for User Dashboard (Consistent with dashboard.php) */
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

    /* Address Cards */
    .address-card {
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        position: relative;
        transition: all 0.3s;
        background: white;
    }
    
    .address-card:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 15px rgba(166, 123, 91, 0.1);
    }
    
    .address-default {
        border-color: var(--primary);
        background: var(--light);
    }
    
    .address-default-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: var(--primary);
        color: #fff;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .address-type-badge {
        background: #6c757d;
        color: #fff;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
        margin-bottom: 10px;
    }
    
    .address-type-shipping {
        background: var(--success);
    }
    
    .address-type-billing {
        background: var(--danger);
    }
    
    .address-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    
    .address-details p {
        margin-bottom: 8px;
        color: #333;
    }
    
    .address-name {
        font-weight: 600;
        font-size: 18px;
        margin-bottom: 10px;
        color: var(--dark);
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark);
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    
    .form-control:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(166, 123, 91, 0.1);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    @media (min-width: 768px) {
        .form-row {
            grid-template-columns: 1fr 1fr;
        }
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .checkbox-group input {
        margin-right: 10px;
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
                        <h1>My Addresses</h1>
                        <p>Manage your shipping and billing addresses</p>
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

                    <div class="row">
                        <!-- Address Form -->
                        <div class="col-lg-6 mb-4">
                            <div class="card-dashboard h-100">
                                <div class="card-header">
                                    <i class="fas fa-plus-circle me-2"></i> 
                                    <?php echo $editMode ? 'Edit Address' : 'Add New Address'; ?>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <?php if ($editMode): ?>
                                            <input type="hidden" name="address_id" value="<?php echo $currentAddress['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="first_name">First Name *</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                                       value="<?php echo $editMode ? htmlspecialchars($currentAddress['first_name']) : ''; ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="last_name">Last Name *</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                                       value="<?php echo $editMode ? htmlspecialchars($currentAddress['last_name']) : ''; ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="phone">Phone Number *</label>
                                            <input type="text" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo $editMode ? htmlspecialchars($currentAddress['phone']) : ''; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="type">Address Type *</label>
                                            <select class="form-control" id="type" name="type" required>
                                                <option value="shipping" <?php echo ($editMode && $currentAddress['type'] == 'shipping') ? 'selected' : ''; ?>>Shipping Address</option>
                                                <option value="billing" <?php echo ($editMode && $currentAddress['type'] == 'billing') ? 'selected' : ''; ?>>Billing Address</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="street">Street Address *</label>
                                            <input type="text" class="form-control" id="street" name="street" 
                                                   value="<?php echo $editMode ? htmlspecialchars($currentAddress['street']) : ''; ?>" required>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="city">City *</label>
                                                <input type="text" class="form-control" id="city" name="city" 
                                                       value="<?php echo $editMode ? htmlspecialchars($currentAddress['city']) : ''; ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="province">Province *</label>
                                                <input type="text" class="form-control" id="province" name="province" 
                                                       value="<?php echo $editMode ? htmlspecialchars($currentAddress['province']) : ''; ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="postal_code">Postal Code *</label>
                                                <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                                       value="<?php echo $editMode ? htmlspecialchars($currentAddress['postal_code']) : ''; ?>" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="country">Country *</label>
                                                <input type="text" class="form-control" id="country" name="country" 
                                                       value="<?php echo $editMode ? htmlspecialchars($currentAddress['country']) : 'South Africa'; ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="checkbox-group">
                                            <input type="checkbox" id="is_default" name="is_default" 
                                                   <?php echo ($editMode && $currentAddress['is_default']) ? 'checked' : ''; ?>>
                                            <label for="is_default">Set as default address for this type</label>
                                        </div>
                                        
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="save_address" class="btn btn-primary">
                                                <?php echo $editMode ? 'Update Address' : 'Add Address'; ?>
                                            </button>
                                            
                                            <?php if ($editMode): ?>
                                                <a href="addresses.php" class="btn btn-secondary">Cancel</a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Address List -->
                        <div class="col-lg-6 mb-4">
                            <div class="card-dashboard h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-map-marker-alt me-2"></i> Your Addresses
                                    </div>
                                    <span class="badge bg-primary"><?php echo count($addresses); ?> address(es)</span>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($addresses)): ?>
                                        <?php foreach ($addresses as $address): ?>
                                            <div class="address-card <?php echo $address['is_default'] ? 'address-default' : ''; ?>">
                                                <?php if ($address['is_default']): ?>
                                                    <span class="address-default-badge">Default</span>
                                                <?php endif; ?>
                                                
                                                <span class="address-type-badge address-type-<?php echo $address['type']; ?>">
                                                    <?php echo ucfirst($address['type']); ?> Address
                                                </span>
                                                
                                                <div class="address-details">
                                                    <div class="address-name"><?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?></div>
                                                    <p><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($address['phone']); ?></p>
                                                    <p><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($address['street']); ?></p>
                                                    <p><?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['province']); ?> <?php echo htmlspecialchars($address['postal_code']); ?></p>
                                                    <p><?php echo htmlspecialchars($address['country']); ?></p>
                                                </div>
                                                
                                                <div class="address-actions">
                                                    <a href="?edit=<?php echo $address['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit me-1"></i> Edit
                                                    </a>
                                                    
                                                    <?php if (!$address['is_default']): ?>
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                                            <button type="submit" name="set_default" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-star me-1"></i> Set Default
                                                            </button>
                                                        </form>
                                                        
                                                        <form method="POST" action="" style="display: inline;">
                                                            <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                                            <button type="submit" name="delete_address" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="return confirm('Are you sure you want to delete this address?');">
                                                                <i class="fas fa-trash me-1"></i> Delete
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                            <h5>No Addresses Found</h5>
                                            <p class="text-muted">You haven't added any addresses yet.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
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
        });
    </script>
</body>
</html>