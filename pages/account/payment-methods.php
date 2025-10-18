<?php
// File: pages/account/payment-methods.php

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

// Get user payment methods
$paymentMethods = getUserPaymentMethods($pdo, $userId);

// Handle form submissions
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Security validation failed. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add_payment_method':
                $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
                $expiryMonth = $_POST['expiry_month'] ?? '';
                $expiryYear = $_POST['expiry_year'] ?? '';
                $cvv = $_POST['cvv'] ?? '';
                $cardHolder = trim($_POST['card_holder'] ?? '');
                $isDefault = isset($_POST['is_default']) ? 1 : 0;
                
                // Validate required fields
                if (empty($cardNumber) || empty($expiryMonth) || empty($expiryYear) || empty($cvv) || empty($cardHolder)) {
                    $errors[] = "Please fill in all required fields.";
                }
                
                // Validate card holder name
                if (!empty($cardHolder) && (strlen($cardHolder) < 2 || strlen($cardHolder) > 100)) {
                    $errors[] = "Card holder name must be between 2 and 100 characters.";
                }
                
                // Validate card number
                if (!empty($cardNumber) && !validateCardNumber($cardNumber)) {
                    $errors[] = "Please enter a valid card number.";
                }
                
                // Validate expiry date
                if (!empty($expiryMonth) && !empty($expiryYear) && !validateExpiryDate($expiryMonth, $expiryYear)) {
                    $errors[] = "Please check the card expiry date.";
                }
                
                // Validate CVV
                if (!empty($cvv) && (!is_numeric($cvv) || strlen($cvv) < 3 || strlen($cvv) > 4)) {
                    $errors[] = "Please enter a valid CVV (3-4 digits).";
                }

                // Check if payment method already exists
                if (empty($errors)) {
                    $existingMethods = getUserPaymentMethods($pdo, $userId);
                    $maskedNewCard = maskCardNumber($cardNumber);
                    foreach ($existingMethods as $existingMethod) {
                        if ($existingMethod['masked_card_number'] === $maskedNewCard) {
                            $errors[] = "This card is already saved in your payment methods.";
                            break;
                        }
                    }
                }

                if (empty($errors)) {
                    // Mask card number for storage
                    $maskedCardNumber = maskCardNumber($cardNumber);
                    $cardType = detectCardType($cardNumber);
                    
                    if (addUserPaymentMethod($pdo, $userId, $cardType, $maskedCardNumber, $cardHolder, $expiryMonth, $expiryYear, $isDefault)) {
                        $success = "Payment method added successfully!";
                        // Refresh payment methods
                        $paymentMethods = getUserPaymentMethods($pdo, $userId);
                        
                        // Clear form
                        $_POST = [];
                    } else {
                        $errors[] = "Failed to add payment method. Please try again.";
                    }
                }
                break;
                
            case 'set_default':
                $paymentMethodId = filter_var($_POST['payment_method_id'] ?? 0, FILTER_VALIDATE_INT);
                if ($paymentMethodId && $paymentMethodId > 0) {
                    // Verify the payment method belongs to the user
                    $userPaymentMethods = array_column($paymentMethods, 'id');
                    if (in_array($paymentMethodId, $userPaymentMethods)) {
                        if (setDefaultPaymentMethod($pdo, $paymentMethodId, $userId)) {
                            $success = "Default payment method updated!";
                            $paymentMethods = getUserPaymentMethods($pdo, $userId);
                        } else {
                            $errors[] = "Failed to update default payment method.";
                        }
                    } else {
                        $errors[] = "Invalid payment method.";
                    }
                } else {
                    $errors[] = "Invalid payment method ID.";
                }
                break;
                
            case 'delete_payment_method':
                $paymentMethodId = filter_var($_POST['payment_method_id'] ?? 0, FILTER_VALIDATE_INT);
                if ($paymentMethodId && $paymentMethodId > 0) {
                    // Verify the payment method belongs to the user
                    $userPaymentMethods = array_column($paymentMethods, 'id');
                    if (in_array($paymentMethodId, $userPaymentMethods)) {
                        // Prevent deletion if it's the only payment method
                        if (count($paymentMethods) <= 1) {
                            $errors[] = "You cannot delete your only payment method. Please add another payment method first.";
                        } else {
                            if (deletePaymentMethod($pdo, $paymentMethodId, $userId)) {
                                $success = "Payment method deleted successfully!";
                                $paymentMethods = getUserPaymentMethods($pdo, $userId);
                            } else {
                                $errors[] = "Failed to delete payment method.";
                            }
                        }
                    } else {
                        $errors[] = "Invalid payment method.";
                    }
                } else {
                    $errors[] = "Invalid payment method ID.";
                }
                break;
                
            default:
                $errors[] = "Invalid action.";
                break;
        }
    }
}

// Generate CSRF token for form protection
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

// Get recent orders for topbar notifications
try {
    $recentOrdersQuery = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5";
    $recentOrdersStmt = $pdo->prepare($recentOrdersQuery);
    $recentOrdersStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $recentOrdersStmt->execute();
    $recentOrders = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentOrders = [];
    error_log("Recent orders error: " . $e->getMessage());
}

// Set page title
$pageTitle = "Payment Methods - HomewareOnTap";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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
        width: 100%;
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

    /* Payment Method Card Styles */
    .payment-method-card {
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        position: relative;
        transition: all 0.3s;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .payment-method-card:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 15px rgba(166, 123, 91, 0.1);
    }
    
    .payment-method-card.default {
        border-color: var(--primary);
        background: var(--light);
    }
    
    .payment-method-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .payment-method-type {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .payment-icon {
        font-size: 2.5rem;
        color: var(--dark);
    }
    
    .payment-method-type h5 {
        margin: 0;
        color: var(--dark);
    }
    
    .payment-method-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .default-badge {
        background: var(--primary);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .payment-method-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .detail-item {
        display: flex;
        flex-direction: column;
    }
    
    .detail-label {
        font-size: 0.75rem;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    
    .detail-value {
        font-size: 1rem;
        color: var(--dark);
        font-weight: 500;
    }
    
    /* Form Styles */
    .card-preview {
        background: linear-gradient(135deg, #3a3229 0%, #5c4a3a 100%);
        border-radius: 12px;
        padding: 1.5rem;
        color: white;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
        min-height: 180px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .card-chip {
        width: 50px;
        height: 40px;
        background: linear-gradient(135deg, #d4af37 0%, #f9f295 100%);
        border-radius: 8px;
        position: relative;
    }
    
    .card-chip:after {
        content: "";
        position: absolute;
        top: 5px;
        left: 5px;
        right: 5px;
        bottom: 5px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 4px;
    }
    
    .card-number {
        font-size: 1.5rem;
        letter-spacing: 2px;
        font-family: 'Courier New', monospace;
        font-weight: 600;
        margin: 1rem 0;
    }
    
    .card-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .card-holder, .card-expiry {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
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
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s, box-shadow 0.3s;
    }
    
    .form-control:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(166, 123, 91, 0.1);
    }
    
    .card-input-wrapper {
        position: relative;
    }
    
    .card-icon {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.5rem;
        color: var(--dark);
    }

    .cvv-input-wrapper { /* New style for CVV to include eye icon */
        position: relative;
    }

    .cvv-toggle-icon {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
        padding: 0.25rem;
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
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
    }
    
    .empty-state-icon {
        font-size: 4rem;
        color: var(--secondary);
        margin-bottom: 1.5rem;
    }
    
    .empty-state h4 {
        color: var(--dark);
        margin-bottom: 1rem;
    }
    
    .empty-state p {
        color: var(--dark);
        opacity: 0.7;
        margin-bottom: 2rem;
    }
    
    /* Toast Container */
    .toast-container {
        z-index: 1090;
    }
    
    /* Form Text */
    .form-text {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .payment-method-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .payment-method-actions {
            margin-top: 1rem;
            width: 100%;
            justify-content: flex-start;
        }
        
        .payment-method-details {
            grid-template-columns: 1fr;
        }
        
        .card-preview {
            min-height: 160px;
            padding: 1rem;
        }
        
        .card-number {
            font-size: 1.25rem;
        }
    }
    
    /* Enhanced Mobile Responsiveness */
    @media (max-width: 576px) {
        .payment-method-actions.mobile-actions {
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
        }
        
        .payment-method-actions.mobile-actions .btn {
            width: 100%;
            justify-content: center;
        }
        
        .card-preview {
            min-height: 140px;
            padding: 1rem;
        }
        
        .card-number {
            font-size: 1.1rem;
        }
        
        .card-details {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .content-area {
            padding: 1rem;
        }
    }

    /* South African Payment Badges */
    .badge {
        font-size: 0.7rem;
        padding: 0.4em 0.6em;
    }

    /* Enhanced Form Accessibility */
    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.2rem rgba(166, 123, 91, 0.25);
    }

    /* Loading States */
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Success States */
    .payment-method-card.added {
        border-color: var(--success);
        background-color: rgba(28, 200, 138, 0.05);
    }

    /* Error States */
    .is-invalid {
        border-color: var(--danger) !important;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: var(--danger);
    }
    </style>
</head>
<body>
    
    <div class="dashboard-wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php require_once 'includes/topbar.php'; ?>

            <main class="content-area">
                <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

                <div class="container-fluid">
                    <div class="page-header">
                        <h1>Payment Methods</h1>
                        <p>Manage your saved payment methods</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6 class="alert-heading mb-2">Please fix the following errors:</h6>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card-dashboard mb-4">
                                <div class="card-header">
                                    <i class="fas fa-credit-card me-2"></i> Saved Payment Methods
                                </div>
                                <div class="card-body">
                                    <?php if (empty($paymentMethods)): ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="far fa-credit-card"></i>
                                        </div>
                                        <h4>No Payment Methods</h4>
                                        <p class="mb-3">You haven't added any payment methods yet.</p>
                                        <p class="text-muted">Add your first payment method using the form on the right.</p>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($paymentMethods as $method): ?>
                                        <div class="payment-method-card <?php echo $method['is_default'] ? 'default' : ''; ?>">
                                            <div class="payment-method-header">
                                                <div class="payment-method-type">
                                                    <i class="fab fa-cc-<?php echo strtolower($method['card_type']); ?> payment-icon"></i>
                                                    <div>
                                                        <h5 class="mb-1"><?php echo htmlspecialchars($method['card_type']); ?> Card</h5>
                                                        <small class="text-muted">Added <?php echo date('M j, Y', strtotime($method['created_at'])); ?></small>
                                                    </div>
                                                </div>
                                                <div class="payment-method-actions">
                                                    <?php if ($method['is_default']): ?>
                                                    <span class="default-badge">Default</span>
                                                    <?php else: ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="set_default">
                                                        <input type="hidden" name="payment_method_id" value="<?php echo $method['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-star me-1"></i>Set Default
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this payment method?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                        <input type="hidden" name="action" value="delete_payment_method">
                                                        <input type="hidden" name="payment_method_id" value="<?php echo $method['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" <?php echo (count($paymentMethods) <= 1) ? 'disabled title="Cannot delete your only payment method"' : ''; ?>>
                                                            <i class="fas fa-trash me-1"></i>Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="payment-method-details">
                                                <div class="detail-item">
                                                    <span class="detail-label">Card Number</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($method['masked_card_number']); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Card Holder</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($method['card_holder']); ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Expires</span>
                                                    <span class="detail-value"><?php echo sprintf('%02d/%04d', $method['expiry_month'], $method['expiry_year']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card-dashboard">
                                <div class="card-header">
                                    <i class="fas fa-plus-circle me-2"></i> Add New Payment Method
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="addPaymentMethodForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                        <input type="hidden" name="action" value="add_payment_method">
                                        
                                        <div class="card-preview">
                                            <div class="card-chip"></div>
                                            <div class="card-number" id="cardPreview">**** **** **** ****</div>
                                            <div class="card-details">
                                                <div class="card-holder" id="holderPreview">CARD HOLDER</div>
                                                <div class="card-expiry" id="expiryPreview">MM/YYYY</div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="card_holder" class="form-label">Card Holder Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="card_holder" name="card_holder" 
                                                   placeholder="John Doe" required maxlength="100"
                                                   value="<?php echo htmlspecialchars($_POST['card_holder'] ?? ''); ?>"
                                                   oninput="updateCardPreview()">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="card_number" class="form-label">Card Number <span class="text-danger">*</span></label>
                                            <div class="card-input-wrapper">
                                                <i class="fas fa-credit-card card-icon" id="cardTypeIcon"></i>
                                                <input type="text" class="form-control" id="card_number" name="card_number" 
                                                       placeholder="1234 5678 9012 3456" required 
                                                       maxlength="19" 
                                                       value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>"
                                                       oninput="formatCardNumber(this); updateCardPreview()">
                                            </div>
                                            <div class="form-text">Enter your 16-digit card number</div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="expiry_month" class="form-label">Expiry Month <span class="text-danger">*</span></label>
                                                <select class="form-select" id="expiry_month" name="expiry_month" required onchange="updateCardPreview()">
                                                    <option value="">Month</option>
                                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                                    <option value="<?php echo sprintf('%02d', $i); ?>" 
                                                        <?php echo (($_POST['expiry_month'] ?? '') == sprintf('%02d', $i)) ? 'selected' : ''; ?>>
                                                        <?php echo sprintf('%02d', $i); ?> - <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                                    </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="expiry_year" class="form-label">Expiry Year <span class="text-danger">*</span></label>
                                                <select class="form-select" id="expiry_year" name="expiry_year" required onchange="updateCardPreview()">
                                                    <option value="">Year</option>
                                                    <?php for ($i = date('Y'); $i <= date('Y') + 15; $i++): ?>
                                                    <option value="<?php echo $i; ?>"
                                                        <?php echo (($_POST['expiry_year'] ?? '') == $i) ? 'selected' : ''; ?>>
                                                        <?php echo $i; ?>
                                                    </option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cvv" class="form-label">CVV <span class="text-danger">*</span></label>
                                            <div class="cvv-input-wrapper">
                                                <input type="password" class="form-control" id="cvv" name="cvv" 
                                                    placeholder="123" required maxlength="4" pattern="[0-9]{3,4}"
                                                    value="<?php echo htmlspecialchars($_POST['cvv'] ?? ''); ?>">
                                                <i class="fas fa-eye cvv-toggle-icon" id="cvvEyeIcon" onclick="toggleCVVVisibility()"></i>
                                            </div>
                                            <div class="form-text">3 or 4-digit security code</div>
                                        </div>
                                        
                                        <div class="form-check mb-4">
                                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default"
                                                <?php echo (empty($paymentMethods) || isset($_POST['is_default'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_default">
                                                Set as default payment method
                                            </label>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary w-100 py-2">
                                            <i class="fas fa-save me-2"></i> Save Payment Method
                                        </button>
                                        
                                        <div class="text-center mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-lock me-1"></i>
                                                Your payment information is secure and encrypted
                                            </small>
                                        </div>
                                    </form>
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

            // Initialize card preview
            updateCardPreview();
            
            // Clear form on success if needed
            <?php if ($success): ?>
                document.getElementById('addPaymentMethodForm').reset();
                updateCardPreview();
            <?php endif; ?>
            
            // Initialize responsive layout handling
            handleResponsiveLayout();
            window.addEventListener('resize', handleResponsiveLayout);
        });
        
        // Format card number with spaces
        function formatCardNumber(input) {
            let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = '';
            
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                }
                formattedValue += value[i];
            }
            
            input.value = formattedValue;
            detectCardType(value);
        }
        
        // Update card preview
        function updateCardPreview() {
            const cardNumber = document.getElementById('card_number').value || '**** **** **** ****';
            const cardHolder = document.getElementById('card_holder').value || 'CARD HOLDER';
            const expiryMonth = document.getElementById('expiry_month').value || 'MM';
            const expiryYear = document.getElementById('expiry_year').value || 'YYYY';
            
            document.getElementById('cardPreview').textContent = cardNumber;
            document.getElementById('holderPreview').textContent = cardHolder.toUpperCase();
            document.getElementById('expiryPreview').textContent = `${expiryMonth}/${expiryYear}`;
        }
        
        // Enhanced card type detection for South African cards
        function detectCardType(cardNumber) {
            let cardType = 'credit-card'; // default
            const cleanCardNumber = cardNumber.replace(/\s+/g, '');
            
            // South African specific card detection (using FA-CC icons)
            if (/^4/.test(cleanCardNumber)) {
                cardType = 'visa';
            } else if (/^5[1-5]/.test(cleanCardNumber)) {
                cardType = 'mastercard';
            } else if (/^3[47]/.test(cleanCardNumber)) {
                cardType = 'amex';
            } else if (/^(506|507|508|650)/.test(cleanCardNumber)) {
                cardType = 'diners-club'; // Using diners club icon for Verve-like
            } else if (/^(62|88)/.test(cleanCardNumber)) {
                cardType = 'unionpay'; // Assuming an icon exists for unionpay or fallback
            } else if (/^6/.test(cleanCardNumber)) {
                cardType = 'discover';
            }
            
            // Update card icon
            const cardIcon = document.getElementById('cardTypeIcon');
            // Check for explicit 'cc-' prefix or handle standard 'fa-cc-'
            if (cardType === 'unionpay') {
                // Font Awesome 6 uses 'cc-unionpay' if the brand is in the supported set, 
                // but the original had a custom logic for 'cc-unionpay' with 'fa-'. 
                // We'll stick to 'fa-cc-' for consistency where possible.
                cardIcon.className = `fab fa-cc-${cardType} card-icon`;
            } else if (cardType === 'credit-card') {
                cardIcon.className = `fas fa-${cardType} card-icon`; // fas for generic
            } else {
                cardIcon.className = `fab fa-cc-${cardType} card-icon`; // fab for branded
            }
            
            return cardType;
        }

        // Check if card is expired
        function isCardExpired(month, year) {
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth() + 1; // JavaScript months are 0-indexed
            
            const expiryYearInt = parseInt(year);
            const expiryMonthInt = parseInt(month);

            if (expiryYearInt < currentYear) {
                return true;
            }
            // Check if year is current year and month is past
            if (expiryYearInt == currentYear && expiryMonthInt < currentMonth) {
                return true;
            }
            return false;
        }

        // Show toast notification
        function showToast(message, type = 'danger') {
            const toast = document.createElement('div');
            // Use 'bg-success' for success and 'bg-danger' for danger
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-exclamation-circle me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.querySelector('.toast-container').appendChild(toast);
            new bootstrap.Toast(toast).show();
        }

        // Enhanced form validation
        document.getElementById('addPaymentMethodForm').addEventListener('submit', function(e) {
            const cardNumber = document.getElementById('card_number').value.replace(/\s+/g, '');
            const cvv = document.getElementById('cvv').value;
            const expiryMonth = document.getElementById('expiry_month').value;
            const expiryYear = document.getElementById('expiry_year').value;
            const cardHolder = document.getElementById('card_holder').value.trim();
            
            let isValid = true;
            let errorMessage = '';

            // Card holder validation
            if (cardHolder.length < 2 || cardHolder.length > 100) {
                isValid = false;
                errorMessage = 'Card holder name must be between 2 and 100 characters';
            }
            // South African card number validation (13-19 digits)
            else if (cardNumber.length < 13 || cardNumber.length > 19 || !/^\d+$/.test(cardNumber)) {
                isValid = false;
                errorMessage = 'Please enter a valid card number (13-19 digits)';
            }
            // CVV validation
            else if (cvv.length < 3 || cvv.length > 4 || !/^\d+$/.test(cvv)) {
                isValid = false;
                errorMessage = 'Please enter a valid CVV (3-4 digits)';
            }
            // Expiry date validation
            else if (!expiryMonth || !expiryYear) {
                isValid = false;
                errorMessage = 'Please select expiry month and year';
            }
            // Check if card is not expired
            else if (isCardExpired(expiryMonth, expiryYear)) {
                isValid = false;
                errorMessage = 'Card has expired. Please check the expiry date.';
            }

            if (!isValid) {
                e.preventDefault();
                showToast(errorMessage, 'danger');
                return false;
            }
            
            return true;
        });

        // Toggle CVV visibility
        function toggleCVVVisibility() {
            const cvvInput = document.getElementById('cvv');
            const cvvEyeIcon = document.getElementById('cvvEyeIcon');
            
            if (cvvInput.type === 'password') {
                cvvInput.type = 'text';
                cvvEyeIcon.className = 'fas fa-eye-slash cvv-toggle-icon';
            } else {
                cvvInput.type = 'password';
                cvvEyeIcon.className = 'fas fa-eye cvv-toggle-icon';
            }
        }

        // Enhanced responsive behavior
        function handleResponsiveLayout() {
            const screenWidth = window.innerWidth;
            const paymentMethods = document.querySelectorAll('.payment-method-card');
            
            if (screenWidth < 768) {
                // Mobile optimizations
                paymentMethods.forEach(method => {
                    const actions = method.querySelector('.payment-method-actions');
                    if (actions) {
                        actions.classList.add('mobile-actions');
                    }
                });
            } else {
                // Desktop
                paymentMethods.forEach(method => {
                    const actions = method.querySelector('.payment-method-actions');
                    if (actions) {
                        actions.classList.remove('mobile-actions');
                    }
                });
            }
        }

        // Auto-format card number on page load if value exists
        const cardNumberInput = document.getElementById('card_number');
        if (cardNumberInput.value) {
            formatCardNumber(cardNumberInput);
        }
        
    </script>
</body>
</html>