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
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_payment_method':
            $cardNumber = $_POST['card_number'] ?? '';
            $expiryMonth = $_POST['expiry_month'] ?? '';
            $expiryYear = $_POST['expiry_year'] ?? '';
            $cvv = $_POST['cvv'] ?? '';
            $cardHolder = $_POST['card_holder'] ?? '';
            $isDefault = isset($_POST['is_default']) ? 1 : 0;
            
            // Validate card data
            if (empty($cardNumber) || empty($expiryMonth) || empty($expiryYear) || empty($cvv) || empty($cardHolder)) {
                $errors[] = "Please fill in all required fields.";
            }
            
            // Validate card number (basic Luhn check)
            if (!validateCardNumber($cardNumber)) {
                $errors[] = "Please enter a valid card number.";
            }
            
            // Validate expiry date
            if (!validateExpiryDate($expiryMonth, $expiryYear)) {
                $errors[] = "Please check the card expiry date.";
            }
            
            if (empty($errors)) {
                // Mask card number for storage
                $maskedCardNumber = maskCardNumber($cardNumber);
                $cardType = detectCardType($cardNumber);
                
                if (addUserPaymentMethod($pdo, $userId, $cardType, $maskedCardNumber, $cardHolder, $expiryMonth, $expiryYear, $isDefault)) {
                    $success = "Payment method added successfully!";
                    // Refresh payment methods
                    $paymentMethods = getUserPaymentMethods($pdo, $userId);
                } else {
                    $errors[] = "Failed to add payment method. Please try again.";
                }
            }
            break;
            
        case 'set_default':
            $paymentMethodId = $_POST['payment_method_id'] ?? 0;
            if ($paymentMethodId) {
                if (setDefaultPaymentMethod($pdo, $paymentMethodId, $userId)) {
                    $success = "Default payment method updated!";
                    $paymentMethods = getUserPaymentMethods($pdo, $userId);
                } else {
                    $errors[] = "Failed to update default payment method.";
                }
            }
            break;
            
        case 'delete_payment_method':
            $paymentMethodId = $_POST['payment_method_id'] ?? 0;
            if ($paymentMethodId) {
                if (deletePaymentMethod($pdo, $paymentMethodId, $userId)) {
                    $success = "Payment method deleted successfully!";
                    $paymentMethods = getUserPaymentMethods($pdo, $userId);
                } else {
                    $errors[] = "Failed to delete payment method.";
                }
            }
            break;
    }
}

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
        max-width: 1400px;
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

    /* Payment Method Cards */
    .payment-method-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        background: white;
        transition: all 0.3s ease;
    }
    
    .payment-method-card:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 10px rgba(166, 123, 91, 0.1);
    }
    
    .payment-method-card.default {
        border-color: var(--primary);
        background: rgba(166, 123, 91, 0.05);
    }
    
    .payment-method-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .payment-method-type {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .payment-icon {
        font-size: 1.5rem;
        color: var(--primary);
    }
    
    .default-badge {
        background: var(--primary);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .payment-method-actions {
        display: flex;
        gap: 0.5rem;
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
        font-size: 0.875rem;
        color: #666;
        margin-bottom: 0.25rem;
    }
    
    .detail-value {
        font-weight: 500;
        color: var(--dark);
    }

    /* Form Styles */
    .form-section {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        border: 1px solid #ddd;
    }
    
    .section-title {
        color: var(--dark);
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--secondary);
    }
    
    .card-input-wrapper {
        position: relative;
    }
    
    .card-input-wrapper .form-control {
        padding-left: 3rem;
    }
    
    .card-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }
    
    .card-preview {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    
    .card-preview::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }
    
    .card-chip {
        width: 40px;
        height: 30px;
        background: linear-gradient(135deg, #ffd700, #ffed4e);
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    
    .card-number {
        font-size: 1.25rem;
        letter-spacing: 2px;
        margin-bottom: 1rem;
        font-family: monospace;
    }
    
    .card-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .card-holder {
        font-size: 0.9rem;
    }
    
    .card-expiry {
        font-size: 0.9rem;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #666;
    }
    
    .empty-state-icon {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 1rem;
    }

    /* Toast positioning */
    .toast-container {
        z-index: 1090;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .payment-method-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .payment-method-actions {
            width: 100%;
            justify-content: flex-end;
        }
        
        .payment-method-details {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>
    
    <div class="dashboard-wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php require_once 'includes/topbar.php'; ?>

            <main class="content-area">
                <!-- Toast Container -->
                <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;"></div>

                <div class="container-fluid">
                    <div class="page-header">
                        <h1>Payment Methods</h1>
                        <p>Manage your saved payment methods</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Saved Payment Methods -->
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
                                        <p>You haven't added any payment methods yet.</p>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($paymentMethods as $method): ?>
                                        <div class="payment-method-card <?php echo $method['is_default'] ? 'default' : ''; ?>">
                                            <div class="payment-method-header">
                                                <div class="payment-method-type">
                                                    <i class="fab fa-cc-<?php echo strtolower($method['card_type']); ?> payment-icon"></i>
                                                    <div>
                                                        <h5 class="mb-0"><?php echo htmlspecialchars($method['card_type']); ?> Card</h5>
                                                        <small class="text-muted">Added <?php echo date('M j, Y', strtotime($method['created_at'])); ?></small>
                                                    </div>
                                                </div>
                                                <div class="payment-method-actions">
                                                    <?php if ($method['is_default']): ?>
                                                    <span class="default-badge">Default</span>
                                                    <?php else: ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="set_default">
                                                        <input type="hidden" name="payment_method_id" value="<?php echo $method['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-primary btn-sm">Set Default</button>
                                                    </form>
                                                    <?php endif; ?>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this payment method?');">
                                                        <input type="hidden" name="action" value="delete_payment_method">
                                                        <input type="hidden" name="payment_method_id" value="<?php echo $method['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
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
                            <!-- Add New Payment Method -->
                            <div class="card-dashboard">
                                <div class="card-header">
                                    <i class="fas fa-plus-circle me-2"></i> Add New Payment Method
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="addPaymentMethodForm">
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
                                            <label for="card_holder" class="form-label">Card Holder Name</label>
                                            <input type="text" class="form-control" id="card_holder" name="card_holder" 
                                                   placeholder="John Doe" required oninput="updateCardPreview()">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="card_number" class="form-label">Card Number</label>
                                            <div class="card-input-wrapper">
                                                <i class="fas fa-credit-card card-icon"></i>
                                                <input type="text" class="form-control" id="card_number" name="card_number" 
                                                       placeholder="1234 5678 9012 3456" required 
                                                       maxlength="19" oninput="formatCardNumber(this); updateCardPreview()">
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="expiry_month" class="form-label">Expiry Month</label>
                                                <select class="form-select" id="expiry_month" name="expiry_month" required onchange="updateCardPreview()">
                                                    <option value="">Month</option>
                                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                                    <option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="expiry_year" class="form-label">Expiry Year</label>
                                                <select class="form-select" id="expiry_year" name="expiry_year" required onchange="updateCardPreview()">
                                                    <option value="">Year</option>
                                                    <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cvv" class="form-label">CVV</label>
                                            <input type="text" class="form-control" id="cvv" name="cvv" 
                                                   placeholder="123" required maxlength="4">
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                                            <label class="form-check-label" for="is_default">
                                                Set as default payment method
                                            </label>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary w-100">
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
        
        // Form validation
        document.getElementById('addPaymentMethodForm').addEventListener('submit', function(e) {
            const cardNumber = document.getElementById('card_number').value.replace(/\s+/g, '');
            const cvv = document.getElementById('cvv').value;
            
            // Basic card number validation (Luhn algorithm would be better)
            if (cardNumber.length < 13 || cardNumber.length > 19) {
                e.preventDefault();
                alert('Please enter a valid card number (13-19 digits)');
                return false;
            }
            
            // CVV validation
            if (cvv.length < 3 || cvv.length > 4 || !/^\d+$/.test(cvv)) {
                e.preventDefault();
                alert('Please enter a valid CVV (3-4 digits)');
                return false;
            }
            
            return true;
        });
        
        // Auto-detect card type and update icon
        document.getElementById('card_number').addEventListener('input', function(e) {
            const cardNumber = e.target.value.replace(/\s+/g, '');
            let cardType = 'credit-card'; // default
            
            if (/^4/.test(cardNumber)) {
                cardType = 'visa';
            } else if (/^5[1-5]/.test(cardNumber)) {
                cardType = 'mastercard';
            } else if (/^3[47]/.test(cardNumber)) {
                cardType = 'amex';
            } else if (/^6(?:011|5)/.test(cardNumber)) {
                cardType = 'discover';
            }
            
            // Update card icon
            document.querySelector('.card-icon').className = `fab fa-cc-${cardType} card-icon`;
        });
    </script>
</body>
</html>