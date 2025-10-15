<?php
// admin/settings/site.php - Site Settings Management
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Check if user is logged in and has admin role
if (!isAdminLoggedIn()) {
    header('Location: ../../pages/account/login.php');
    exit();
}

// Get database connection
$pdo = getDBConnection();
if (!$pdo) {
    die("Database connection failed");
}

$pageTitle = "Site Settings - HomewareOnTap Admin";

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Process general settings
        if (isset($_POST['update_general'])) {
            $settingsToUpdate = [
                'site_name' => $_POST['site_name'] ?? '',
                'site_email' => $_POST['site_email'] ?? '',
                'currency' => $_POST['currency'] ?? 'ZAR',
                'currency_symbol' => $_POST['currency_symbol'] ?? 'R',
                'store_address' => $_POST['store_address'] ?? '',
                'store_phone' => $_POST['store_phone'] ?? '',
                'return_policy_days' => $_POST['return_policy_days'] ?? '30'
            ];
            
            foreach ($settingsToUpdate as $key => $value) {
                updateSiteSetting($pdo, $key, $value);
            }
            
            $message = "General settings updated successfully!";
            $messageType = "success";
        }
        
        // Process shipping settings
        if (isset($_POST['update_shipping'])) {
            $settingsToUpdate = [
                'free_shipping_threshold' => $_POST['free_shipping_threshold'] ?? '1000.00',
                'standard_shipping_cost' => $_POST['standard_shipping_cost'] ?? '99.00',
                'tax_rate' => $_POST['tax_rate'] ?? '15.00'
            ];
            
            foreach ($settingsToUpdate as $key => $value) {
                updateSiteSetting($pdo, $key, $value);
            }
            
            $message = "Shipping settings updated successfully!";
            $messageType = "success";
        }
        
        // Process security settings
        if (isset($_POST['update_security'])) {
            $settingsToUpdate = [
                'password_min_length' => $_POST['password_min_length'] ?? '8',
                'password_require_letters' => isset($_POST['password_require_letters']) ? '1' : '0',
                'password_require_numbers' => isset($_POST['password_require_numbers']) ? '1' : '0',
                'max_login_attempts' => $_POST['max_login_attempts'] ?? '5',
                'login_lockout_minutes' => $_POST['login_lockout_minutes'] ?? '15',
                'email_verification_required' => isset($_POST['email_verification_required']) ? '1' : '0',
                'verification_token_expiry_hours' => $_POST['verification_token_expiry_hours'] ?? '24'
            ];
            
            foreach ($settingsToUpdate as $key => $value) {
                updateSiteSetting($pdo, $key, $value);
            }
            
            $message = "Security settings updated successfully!";
            $messageType = "success";
        }
        
        // Process validation settings
        if (isset($_POST['update_validation'])) {
            $settingsToUpdate = [
                'validation_first_name_min' => $_POST['validation_first_name_min'] ?? '2',
                'validation_first_name_max' => $_POST['validation_first_name_max'] ?? '50',
                'validation_last_name_min' => $_POST['validation_last_name_min'] ?? '2',
                'validation_last_name_max' => $_POST['validation_last_name_max'] ?? '50',
                'validation_phone_min' => $_POST['validation_phone_min'] ?? '9',
                'validation_phone_max' => $_POST['validation_phone_max'] ?? '15',
                'validation_password_min' => $_POST['validation_password_min'] ?? '8',
                'validation_password_max' => $_POST['validation_password_max'] ?? '255'
            ];
            
            foreach ($settingsToUpdate as $key => $value) {
                updateSiteSetting($pdo, $key, $value);
            }
            
            $message = "Validation settings updated successfully!";
            $messageType = "success";
        }
        
        // Process exam mode settings
        if (isset($_POST['update_exam_mode'])) {
            $examModeEnabled = isset($_POST['exam_mode_enabled']) ? '1' : '0';
            $examModeStartDate = !empty($_POST['exam_mode_start_date']) ? $_POST['exam_mode_start_date'] : null;
            $examModeEndDate = !empty($_POST['exam_mode_end_date']) ? $_POST['exam_mode_end_date'] : null;
            
            $settingsToUpdate = [
                'exam_mode_enabled' => $examModeEnabled,
                'exam_mode_start_date' => $examModeStartDate,
                'exam_mode_end_date' => $examModeEndDate,
                'exam_mode_message' => $_POST['exam_mode_message'] ?? 'Orders are temporarily paused during exam period. We will resume normal operations soon.'
            ];
            
            foreach ($settingsToUpdate as $key => $value) {
                updateSiteSetting($pdo, $key, $value);
            }
            
            $message = "Exam mode settings updated successfully!";
            $messageType = "success";
        }
        
        // Process WhatsApp settings
        if (isset($_POST['update_whatsapp'])) {
            $whatsappEnabled = isset($_POST['whatsapp_enabled']) ? '1' : '0';
            $whatsappOrderUpdates = isset($_POST['whatsapp_order_updates']) ? '1' : '0';
            
            $settingsToUpdate = [
                'whatsapp_enabled' => $whatsappEnabled,
                'whatsapp_business_number' => $_POST['whatsapp_business_number'] ?? '',
                'whatsapp_default_message' => $_POST['whatsapp_default_message'] ?? 'Hello! I have a question about your products.',
                'whatsapp_order_updates' => $whatsappOrderUpdates
            ];
            
            foreach ($settingsToUpdate as $key => $value) {
                updateSiteSetting($pdo, $key, $value);
            }
            
            $message = "WhatsApp settings updated successfully!";
            $messageType = "success";
        }
        
        // Process social media settings
        if (isset($_POST['update_social'])) {
            $settingsToUpdate = [
                'social_facebook' => $_POST['social_facebook'] ?? '',
                'social_instagram' => $_POST['social_instagram'] ?? '',
                'social_tiktok' => $_POST['social_tiktok'] ?? '',
                'social_twitter' => $_POST['social_twitter'] ?? ''
            ];
            
            foreach ($settingsToUpdate as $key => $value) {
                updateSiteSetting($pdo, $key, $value);
            }
            
            $message = "Social media settings updated successfully!";
            $messageType = "success";
        }
        
        // Commit transaction
        $pdo->commit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $message = "Error updating settings: " . $e->getMessage();
        $messageType = "error";
        error_log("Site settings update error: " . $e->getMessage());
    }
}

// Get all current settings
$currentSettings = getSiteSettings($pdo);

// Helper function to update individual setting
function updateSiteSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare("
        INSERT INTO site_settings (setting_key, setting_value, updated_at) 
        VALUES (?, ?, NOW()) 
        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
    ");
    return $stmt->execute([$key, $value, $value]);
}

// Get dashboard stats for sidebar
$stats = getDashboardStatistics($pdo);
$pendingOrders = $stats['pendingOrders'];
$lowStockCount = $stats['lowStockCount'];
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
    
    <!-- Admin CSS -->
    <link href="../../assets/css/admin.css" rel="stylesheet">
    
    <style>
        .settings-section {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 20px;
        }
        
        .settings-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
        }
        
        .settings-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 8px 12px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #A67B5B;
            box-shadow: 0 0 0 0.2rem rgba(166, 123, 91, 0.25);
        }
        
        .help-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .setting-card {
            border-left: 4px solid #A67B5B;
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .exam-mode-status {
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        
        .exam-mode-active {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .exam-mode-inactive {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: white;
            font-size: 18px;
        }
        
        .facebook-bg {
            background-color: #3b5998;
        }
        
        .instagram-bg {
            background: linear-gradient(45deg, #405de6, #5851db, #833ab4, #c13584, #e1306c, #fd1d1d);
        }
        
        .tiktok-bg {
            background-color: #000000;
        }
        
        .twitter-bg {
            background-color: #1da1f2;
        }
        
        .nav-tabs .nav-link {
            color: #495057;
            border: none;
            padding: 12px 20px;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: #A67B5B;
            border-bottom: 3px solid #A67B5B;
            background: transparent;
        }
        
        .nav-tabs .nav-link:hover {
            color: #A67B5B;
            border-bottom: 3px solid #A67B5B;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #A67B5B;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }
        
        .setting-description {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 20px;
            background-color: #fff5f5;
        }
        
        @media (max-width: 768px) {
            .settings-body {
                padding: 15px;
            }
            
            .nav-tabs .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <!-- Admin Dashboard -->
    <div class="admin-container">
        <!-- Include Sidebar -->
        <?php 
        $_SESSION['sidebar_stats'] = [
            'pendingOrders' => $pendingOrders,
            'lowStockCount' => $lowStockCount
        ];
        include_once '../../includes/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Include Top Navbar -->
            <?php include_once '../../includes/top-navbar.php'; ?>

            <!-- Settings Content -->
            <div class="content-section" id="settingsSection">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">Site Settings</h3>
                    <div>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-cog me-1"></i> 
                            System Configuration
                        </span>
                    </div>
                </div>

                <!-- Display Messages -->
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Settings Navigation Tabs -->
                <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                            <i class="fas fa-cog me-2"></i>General
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button" role="tab" aria-controls="shipping" aria-selected="false">
                            <i class="fas fa-shipping-fast me-2"></i>Shipping & Tax
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                            <i class="fas fa-shield-alt me-2"></i>Security
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="validation-tab" data-bs-toggle="tab" data-bs-target="#validation" type="button" role="tab" aria-controls="validation" aria-selected="false">
                            <i class="fas fa-check-circle me-2"></i>Validation
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="exam-mode-tab" data-bs-toggle="tab" data-bs-target="#exam-mode" type="button" role="tab" aria-controls="exam-mode" aria-selected="false">
                            <i class="fas fa-graduation-cap me-2"></i>Exam Mode
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="whatsapp-tab" data-bs-toggle="tab" data-bs-target="#whatsapp" type="button" role="tab" aria-controls="whatsapp" aria-selected="false">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab" aria-controls="social" aria-selected="false">
                            <i class="fas fa-share-alt me-2"></i>Social Media
                        </button>
                    </li>
                </ul>

                <!-- Settings Content Tabs -->
                <div class="tab-content" id="settingsTabsContent">
                    <!-- General Settings Tab -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <form method="POST">
                            <div class="settings-section">
                                <div class="settings-header">
                                    <h5 class="mb-0">General Site Settings</h5>
                                </div>
                                <div class="settings-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="site_name" class="form-label">Site Name</label>
                                                <input type="text" class="form-control" id="site_name" name="site_name" 
                                                       value="<?php echo htmlspecialchars($currentSettings['site_name'] ?? 'HomewareOnTap'); ?>" required>
                                                <div class="help-text">The name of your website displayed to customers</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="site_email" class="form-label">Site Email</label>
                                                <input type="email" class="form-control" id="site_email" name="site_email" 
                                                       value="<?php echo htmlspecialchars($currentSettings['site_email'] ?? 'info@homewareontap.co.za'); ?>" required>
                                                <div class="help-text">Default email address for system notifications</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="currency" class="form-label">Currency</label>
                                                <select class="form-select" id="currency" name="currency">
                                                    <option value="ZAR" <?php echo ($currentSettings['currency'] ?? 'ZAR') === 'ZAR' ? 'selected' : ''; ?>>South African Rand (ZAR)</option>
                                                    <option value="USD" <?php echo ($currentSettings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                                    <option value="EUR" <?php echo ($currentSettings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
                                                    <option value="GBP" <?php echo ($currentSettings['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>British Pound (GBP)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="currency_symbol" class="form-label">Currency Symbol</label>
                                                <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" 
                                                       value="<?php echo htmlspecialchars($currentSettings['currency_symbol'] ?? 'R'); ?>" maxlength="3" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="return_policy_days" class="form-label">Return Policy Days</label>
                                                <input type="number" class="form-control" id="return_policy_days" name="return_policy_days" 
                                                       value="<?php echo htmlspecialchars($currentSettings['return_policy_days'] ?? '30'); ?>" min="1" max="365" required>
                                                <div class="help-text">Number of days customers have to return products</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="store_address" class="form-label">Store Address</label>
                                        <textarea class="form-control" id="store_address" name="store_address" rows="3"><?php echo htmlspecialchars($currentSettings['store_address'] ?? ''); ?></textarea>
                                        <div class="help-text">Physical store address for contact information</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="store_phone" class="form-label">Store Phone</label>
                                        <input type="text" class="form-control" id="store_phone" name="store_phone" 
                                               value="<?php echo htmlspecialchars($currentSettings['store_phone'] ?? ''); ?>">
                                        <div class="help-text">Contact phone number for customers</div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="update_general" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save General Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Shipping & Tax Settings Tab -->
                    <div class="tab-pane fade" id="shipping" role="tabpanel" aria-labelledby="shipping-tab">
                        <form method="POST">
                            <div class="settings-section">
                                <div class="settings-header">
                                    <h5 class="mb-0">Shipping & Tax Settings</h5>
                                </div>
                                <div class="settings-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="free_shipping_threshold" class="form-label">Free Shipping Threshold</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">R</span>
                                                    <input type="number" class="form-control" id="free_shipping_threshold" name="free_shipping_threshold" 
                                                           value="<?php echo htmlspecialchars($currentSettings['free_shipping_threshold'] ?? '1000.00'); ?>" step="0.01" min="0" required>
                                                </div>
                                                <div class="help-text">Minimum cart total for free shipping (0 to disable)</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="standard_shipping_cost" class="form-label">Standard Shipping Cost</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">R</span>
                                                    <input type="number" class="form-control" id="standard_shipping_cost" name="standard_shipping_cost" 
                                                           value="<?php echo htmlspecialchars($currentSettings['standard_shipping_cost'] ?? '99.00'); ?>" step="0.01" min="0" required>
                                                </div>
                                                <div class="help-text">Standard shipping cost when free shipping doesn't apply</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="tax_rate" class="form-label">Tax Rate (VAT)</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                                           value="<?php echo htmlspecialchars($currentSettings['tax_rate'] ?? '15.00'); ?>" step="0.01" min="0" max="50" required>
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <div class="help-text">VAT percentage applied to orders</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="update_shipping" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Shipping Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Security Settings Tab -->
                    <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                        <form method="POST">
                            <div class="settings-section">
                                <div class="settings-header">
                                    <h5 class="mb-0">Security Settings</h5>
                                </div>
                                <div class="settings-body">
                                    <div class="setting-card">
                                        <h6>Password Requirements</h6>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="password_min_length" class="form-label">Minimum Length</label>
                                                    <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                                           value="<?php echo htmlspecialchars($currentSettings['password_min_length'] ?? '8'); ?>" min="6" max="255" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <div class="form-check form-switch mt-4">
                                                        <input class="form-check-input" type="checkbox" id="password_require_letters" name="password_require_letters" 
                                                               <?php echo ($currentSettings['password_require_letters'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="password_require_letters">Require Letters</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <div class="form-check form-switch mt-4">
                                                        <input class="form-check-input" type="checkbox" id="password_require_numbers" name="password_require_numbers" 
                                                               <?php echo ($currentSettings['password_require_numbers'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="password_require_numbers">Require Numbers</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="setting-card">
                                        <h6>Login Security</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                                    <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                                           value="<?php echo htmlspecialchars($currentSettings['max_login_attempts'] ?? '5'); ?>" min="1" max="20" required>
                                                    <div class="help-text">Maximum failed login attempts before lockout</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="login_lockout_minutes" class="form-label">Lockout Duration (minutes)</label>
                                                    <input type="number" class="form-control" id="login_lockout_minutes" name="login_lockout_minutes" 
                                                           value="<?php echo htmlspecialchars($currentSettings['login_lockout_minutes'] ?? '15'); ?>" min="1" max="1440" required>
                                                    <div class="help-text">How long to lock account after max attempts</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="setting-card">
                                        <h6>Email Verification</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="email_verification_required" name="email_verification_required" 
                                                               <?php echo ($currentSettings['email_verification_required'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="email_verification_required">Require Email Verification</label>
                                                    </div>
                                                    <div class="help-text">Users must verify their email before accessing account</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="verification_token_expiry_hours" class="form-label">Verification Token Expiry (hours)</label>
                                                    <input type="number" class="form-control" id="verification_token_expiry_hours" name="verification_token_expiry_hours" 
                                                           value="<?php echo htmlspecialchars($currentSettings['verification_token_expiry_hours'] ?? '24'); ?>" min="1" max="168" required>
                                                    <div class="help-text">How long verification links remain valid</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="update_security" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Security Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Validation Settings Tab -->
                    <div class="tab-pane fade" id="validation" role="tabpanel" aria-labelledby="validation-tab">
                        <form method="POST">
                            <div class="settings-section">
                                <div class="settings-header">
                                    <h5 class="mb-0">Form Validation Settings</h5>
                                </div>
                                <div class="settings-body">
                                    <div class="setting-card">
                                        <h6>Name Validation</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="validation_first_name_min" class="form-label">First Name Minimum Length</label>
                                                    <input type="number" class="form-control" id="validation_first_name_min" name="validation_first_name_min" 
                                                           value="<?php echo htmlspecialchars($currentSettings['validation_first_name_min'] ?? '2'); ?>" min="1" max="50" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="validation_first_name_max" class="form-label">First Name Maximum Length</label>
                                                    <input type="number" class="form-control" id="validation_first_name_max" name="validation_first_name_max" 
                                                           value="<?php echo htmlspecialchars($currentSettings['validation_first_name_max'] ?? '50'); ?>" min="1" max="100" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="validation_last_name_min" class="form-label">Last Name Minimum Length</label>
                                                    <input type="number" class="form-control" id="validation_last_name_min" name="validation_last_name_min" 
                                                           value="<?php echo htmlspecialchars($currentSettings['validation_last_name_min'] ?? '2'); ?>" min="1" max="50" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="validation_last_name_max" class="form-label">Last Name Maximum Length</label>
                                                    <input type="number" class="form-control" id="validation_last_name_max" name="validation_last_name_max" 
                                                           value="<?php echo htmlspecialchars($currentSettings['validation_last_name_max'] ?? '50'); ?>" min="1" max="100" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="setting-card">
                                        <h6>Phone Validation</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="validation_phone_min" class="form-label">Phone Minimum Length</label>
                                                    <input type="number" class="form-control" id="validation_phone_min" name="validation_phone_min" 
                                                           value="<?php echo htmlspecialchars($currentSettings['validation_phone_min'] ?? '9'); ?>" min="5" max="20" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="validation_phone_max" class="form-label">Phone Maximum Length</label>
                                                    <input type="number" class="form-control" id="validation_phone_max" name="validation_phone_max" 
                                                           value="<?php echo htmlspecialchars($currentSettings['validation_phone_max'] ?? '15'); ?>" min="5" max="20" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="setting-card">
                                        <h6>Password Validation</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="validation_password_min" class="form-label">Password Minimum Length</label>
                                                    <input type="number" class="form-control" id="validation_password_min" name="validation_password_min" 
                                                           value="<?php echo htmlspecialchars($currentSettings['validation_password_min'] ?? '8'); ?>" min="6" max="255" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="validation_password_max" class="form-label">Password Maximum Length</label>
                                                    <input type="number" class="form-control" id="validation_password_max" name="validation_password_max" 
                                                           value="<?php echo htmlspecialchars($currentSettings['validation_password_max'] ?? '255'); ?>" min="8" max="255" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="update_validation" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Validation Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Exam Mode Settings Tab -->
                    <div class="tab-pane fade" id="exam-mode" role="tabpanel" aria-labelledby="exam-mode-tab">
                        <form method="POST">
                            <div class="settings-section">
                                <div class="settings-header">
                                    <h5 class="mb-0">Exam Mode Settings</h5>
                                </div>
                                <div class="settings-body">
                                    <?php
                                    $examModeEnabled = ($currentSettings['exam_mode_enabled'] ?? '0') === '1';
                                    $examModeStartDate = $currentSettings['exam_mode_start_date'] ?? '';
                                    $examModeEndDate = $currentSettings['exam_mode_end_date'] ?? '';
                                    
                                    // Check if exam mode is currently active
                                    $isExamModeActive = false;
                                    if ($examModeEnabled && $examModeStartDate && $examModeEndDate) {
                                        $now = time();
                                        $start = strtotime($examModeStartDate);
                                        $end = strtotime($examModeEndDate);
                                        $isExamModeActive = ($now >= $start && $now <= $end);
                                    }
                                    ?>
                                    
                                    <div class="exam-mode-status <?php echo $isExamModeActive ? 'exam-mode-active' : 'exam-mode-inactive'; ?>">
                                        <div class="d-flex align-items-center">
                                            <i class="fas <?php echo $isExamModeActive ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?> me-2"></i>
                                            <div>
                                                <strong>Exam Mode Status:</strong> 
                                                <?php echo $isExamModeActive ? 'ACTIVE - Orders are paused' : 'INACTIVE - Orders are accepted'; ?>
                                            </div>
                                        </div>
                                        <?php if ($isExamModeActive): ?>
                                        <div class="mt-2">
                                            <small>
                                                Active from <?php echo date('d M Y', strtotime($examModeStartDate)); ?> 
                                                to <?php echo date('d M Y', strtotime($examModeEndDate)); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="exam_mode_enabled" name="exam_mode_enabled" 
                                                   <?php echo $examModeEnabled ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="exam_mode_enabled">Enable Exam Mode</label>
                                        </div>
                                        <div class="help-text">When enabled, customers cannot place orders during specified dates</div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="exam_mode_start_date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="exam_mode_start_date" name="exam_mode_start_date" 
                                                       value="<?php echo htmlspecialchars($examModeStartDate); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="exam_mode_end_date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="exam_mode_end_date" name="exam_mode_end_date" 
                                                       value="<?php echo htmlspecialchars($examModeEndDate); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="exam_mode_message" class="form-label">Display Message</label>
                                        <textarea class="form-control" id="exam_mode_message" name="exam_mode_message" rows="3"><?php echo htmlspecialchars($currentSettings['exam_mode_message'] ?? 'Orders are temporarily paused during exam period. We will resume normal operations soon.'); ?></textarea>
                                        <div class="help-text">Message shown to customers when exam mode is active</div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="update_exam_mode" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Exam Mode Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- WhatsApp Settings Tab -->
                    <div class="tab-pane fade" id="whatsapp" role="tabpanel" aria-labelledby="whatsapp-tab">
                        <form method="POST">
                            <div class="settings-section">
                                <div class="settings-header">
                                    <h5 class="mb-0">WhatsApp Integration</h5>
                                </div>
                                <div class="settings-body">
                                    <div class="form-group">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="whatsapp_enabled" name="whatsapp_enabled" 
                                                   <?php echo ($currentSettings['whatsapp_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="whatsapp_enabled">Enable WhatsApp Integration</label>
                                        </div>
                                        <div class="help-text">Show WhatsApp contact button on the website</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="whatsapp_business_number" class="form-label">WhatsApp Business Number</label>
                                        <input type="text" class="form-control" id="whatsapp_business_number" name="whatsapp_business_number" 
                                               value="<?php echo htmlspecialchars($currentSettings['whatsapp_business_number'] ?? ''); ?>" 
                                               placeholder="e.g., 27821234567">
                                        <div class="help-text">Full number with country code (no + or spaces)</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="whatsapp_default_message" class="form-label">Default Message</label>
                                        <textarea class="form-control" id="whatsapp_default_message" name="whatsapp_default_message" rows="3"><?php echo htmlspecialchars($currentSettings['whatsapp_default_message'] ?? 'Hello! I have a question about your products.'); ?></textarea>
                                        <div class="help-text">Pre-filled message when customers click WhatsApp button</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="whatsapp_order_updates" name="whatsapp_order_updates" 
                                                   <?php echo ($currentSettings['whatsapp_order_updates'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="whatsapp_order_updates">Send Order Updates via WhatsApp</label>
                                        </div>
                                        <div class="help-text">Send order confirmations and updates via WhatsApp (requires customer consent)</div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="update_whatsapp" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save WhatsApp Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Social Media Settings Tab -->
                    <div class="tab-pane fade" id="social" role="tabpanel" aria-labelledby="social-tab">
                        <form method="POST">
                            <div class="settings-section">
                                <div class="settings-header">
                                    <h5 class="mb-0">Social Media Links</h5>
                                </div>
                                <div class="settings-body">
                                    <div class="form-group">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="social-icon facebook-bg">
                                                <i class="fab fa-facebook-f"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label for="social_facebook" class="form-label">Facebook Page URL</label>
                                                <input type="url" class="form-control" id="social_facebook" name="social_facebook" 
                                                       value="<?php echo htmlspecialchars($currentSettings['social_facebook'] ?? ''); ?>" 
                                                       placeholder="https://facebook.com/yourpage">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="social-icon instagram-bg">
                                                <i class="fab fa-instagram"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label for="social_instagram" class="form-label">Instagram Profile URL</label>
                                                <input type="url" class="form-control" id="social_instagram" name="social_instagram" 
                                                       value="<?php echo htmlspecialchars($currentSettings['social_instagram'] ?? ''); ?>" 
                                                       placeholder="https://instagram.com/yourprofile">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="social-icon tiktok-bg">
                                                <i class="fab fa-tiktok"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label for="social_tiktok" class="form-label">TikTok Profile URL</label>
                                                <input type="url" class="form-control" id="social_tiktok" name="social_tiktok" 
                                                       value="<?php echo htmlspecialchars($currentSettings['social_tiktok'] ?? ''); ?>" 
                                                       placeholder="https://tiktok.com/@yourprofile">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="social-icon twitter-bg">
                                                <i class="fab fa-twitter"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label for="social_twitter" class="form-label">Twitter Profile URL</label>
                                                <input type="url" class="form-control" id="social_twitter" name="social_twitter" 
                                                       value="<?php echo htmlspecialchars($currentSettings['social_twitter'] ?? ''); ?>" 
                                                       placeholder="https://twitter.com/yourprofile">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" name="update_social" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Social Media Settings
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="settings-section danger-zone mt-4">
                    <div class="settings-header">
                        <h5 class="mb-0 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</h5>
                    </div>
                    <div class="settings-body">
                        <p class="text-muted">These actions are irreversible. Please proceed with caution.</p>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-grid">
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearCacheModal">
                                        <i class="fas fa-broom me-1"></i> Clear Cache
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-grid">
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#resetSettingsModal">
                                        <i class="fas fa-undo me-1"></i> Reset to Defaults
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-grid">
                                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#exportSettingsModal">
                                        <i class="fas fa-download me-1"></i> Export Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Clear Cache Modal -->
    <div class="modal fade" id="clearCacheModal" tabindex="-1" aria-labelledby="clearCacheModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clearCacheModalLabel">Clear System Cache</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This will clear all cached data including product listings, category trees, and session data.</p>
                    <p class="text-danger"><strong>Warning:</strong> This may temporarily slow down the website as caches rebuild.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmClearCache">Clear Cache</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Settings Modal -->
    <div class="modal fade" id="resetSettingsModal" tabindex="-1" aria-labelledby="resetSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetSettingsModalLabel">Reset Settings to Defaults</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This will reset all site settings to their default values.</p>
                    <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. All custom settings will be lost.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmResetSettings">Reset Settings</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Settings Modal -->
    <div class="modal fade" id="exportSettingsModal" tabindex="-1" aria-labelledby="exportSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportSettingsModalLabel">Export Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Export all site settings as a JSON file for backup or migration purposes.</p>
                    <div class="form-group">
                        <label for="exportFormat" class="form-label">Export Format</label>
                        <select class="form-select" id="exportFormat">
                            <option value="json">JSON</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmExportSettings">Export Settings</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Tab functionality
            $('#settingsTabs button').on('click', function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
            
            // Exam mode date validation
            $('#exam_mode_start_date, #exam_mode_end_date').on('change', function() {
                const startDate = $('#exam_mode_start_date').val();
                const endDate = $('#exam_mode_end_date').val();
                
                if (startDate && endDate && startDate > endDate) {
                    alert('End date cannot be before start date!');
                    $('#exam_mode_end_date').val('');
                }
            });
            
            // Clear cache functionality
            $('#confirmClearCache').on('click', function() {
                // In a real implementation, this would call an API endpoint
                alert('Cache cleared successfully!');
                $('#clearCacheModal').modal('hide');
            });
            
            // Reset settings functionality
            $('#confirmResetSettings').on('click', function() {
                if (confirm('Are you absolutely sure? This cannot be undone!')) {
                    // In a real implementation, this would call an API endpoint
                    alert('Settings reset to defaults!');
                    $('#resetSettingsModal').modal('hide');
                    location.reload();
                }
            });
            
            // Export settings functionality
            $('#confirmExportSettings').on('click', function() {
                const format = $('#exportFormat').val();
                alert(`Settings exported as ${format.toUpperCase()} file!`);
                $('#exportSettingsModal').modal('hide');
                
                // In a real implementation, this would trigger a file download
                // window.location.href = `export-settings.php?format=${format}`;
            });
            
            // Auto-save functionality for critical settings
            let autoSaveTimeout;
            $('input, select, textarea').on('change', function() {
                clearTimeout(autoSaveTimeout);
                
                // Only auto-save critical settings
                const criticalFields = ['site_name', 'site_email', 'currency', 'exam_mode_enabled'];
                const fieldName = $(this).attr('name');
                
                if (criticalFields.includes(fieldName)) {
                    autoSaveTimeout = setTimeout(function() {
                        // In a real implementation, this would send an AJAX request
                        console.log('Auto-saving critical setting:', fieldName);
                    }, 2000);
                }
            });
            
            // Form validation
            $('form').on('submit', function(e) {
                let isValid = true;
                const $form = $(this);
                
                // Validate required fields
                $form.find('input[required], select[required], textarea[required]').each(function() {
                    if (!$(this).val().trim()) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });
                
                // Validate email format
                const emailFields = $form.find('input[type="email"]');
                emailFields.each(function() {
                    const email = $(this).val();
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    
                    if (email && !emailRegex.test(email)) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    }
                });
                
                // Validate URLs
                const urlFields = $form.find('input[type="url"]');
                urlFields.each(function() {
                    const url = $(this).val();
                    
                    if (url) {
                        try {
                            new URL(url);
                            $(this).removeClass('is-invalid');
                        } catch (_) {
                            isValid = false;
                            $(this).addClass('is-invalid');
                        }
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fix the validation errors before submitting.');
                }
            });
            
            // Add help tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl+S to save current form
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    const activeTab = $('.tab-pane.active');
                    const activeForm = activeTab.find('form');
                    if (activeForm.length) {
                        activeForm.submit();
                    }
                }
            });
        });
    </script>
</body>
</html>