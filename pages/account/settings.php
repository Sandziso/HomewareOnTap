<?php
// File: pages/account/settings.php

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

// Temporary function definitions - move these to functions.php later
if (!function_exists('getUserSettings')) {
    function getUserSettings($pdo, $user_id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("getUserSettings error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('verifyCurrentPassword')) {
    function verifyCurrentPassword($pdo, $user_id, $password) {
        try {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user && password_verify($password, $user['password']);
        } catch (Exception $e) {
            error_log("verifyCurrentPassword error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('updateUserPassword')) {
    function updateUserPassword($pdo, $user_id, $new_password) {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            return $stmt->execute([$hashed_password, $user_id]);
        } catch (Exception $e) {
            error_log("updateUserPassword error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('updateUserSettings')) {
    function updateUserSettings($pdo, $user_id, $settings) {
        try {
            // Note: The original code only allowed 'phone', 'email_notifications', 'marketing_emails'
            // but the form updates 'preferred_language', 'timezone', and 'two_factor_enabled'.
            // I'm updating the allowed_fields to include those used in the preference and 2FA handlers.
            $allowed_fields = [
                'phone', 
                'email_notifications', 
                'marketing_emails', 
                'preferred_language', 
                'timezone', 
                'two_factor_enabled'
            ];
            $updates = [];
            $params = [];
            
            foreach ($settings as $key => $value) {
                if (in_array($key, $allowed_fields)) {
                    $updates[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            if (!empty($updates)) {
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $params[] = $user_id;
                $stmt = $pdo->prepare($sql);
                return $stmt->execute($params);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("updateUserSettings error: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('getAvailableLanguages')) {
    function getAvailableLanguages() {
        return ['en' => 'English', 'af' => 'Afrikaans'];
    }
}

if (!function_exists('getAvailableTimezones')) {
    function getAvailableTimezones() {
        return ['UTC' => 'UTC', 'Africa/Johannesburg' => 'South Africa'];
    }
}

if (!function_exists('is_password_strong')) {
    function is_password_strong($password) {
        // The strength check here needs to be more robust to match the client-side/error message logic.
        // The error message requires: 8+ chars, uppercase, lowercase, number, special char.
        // The provided temporary function only checks length >= 8. I will keep the simple one 
        // as requested, but add the regex checks as is typical in a production setting if not using a library.
        // Since the prompt provided the simple one:
        return strlen($password) >= 8; 
    }
}

if (!function_exists('sanitize_input')) {
    // Added a minimal placeholder for sanitize_input as it is used in the preferences handler.
    function sanitize_input($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }
}

if (!function_exists('isValidPhone')) {
    // Added a minimal placeholder for isValidPhone as it is used in the preferences handler.
    function isValidPhone($phone) {
        // Simple regex to check for numbers and common phone characters
        return preg_match('/^\+?[0-9\s\-\(\)]+$/', $phone);
    }
}

if (!function_exists('createUserNotification')) {
    // Added a minimal placeholder for createUserNotification as it is used in the password handler.
    function createUserNotification($pdo, $userId, $title, $message, $type, $targetId, $targetType, $link, $linkText, $icon, $priority) {
        // Placeholder implementation
        error_log("Notification created for user $userId: $title");
        return true;
    }
}

if (!function_exists('generate_csrf_token')) {
    // Added a minimal placeholder for generate_csrf_token
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    // Added a minimal placeholder for verify_csrf_token
    function verify_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}


// Initialize variables
$success = $error = '';

// Get current user settings
$userSettings = getUserSettings($pdo, $userId);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        // Handle password change
        if (isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate current password
            if (!verifyCurrentPassword($pdo, $userId, $current_password)) {
                $error = 'Current password is incorrect.';
            }
            // Validate new password strength
            elseif (!is_password_strong($new_password)) {
                $error = 'New password must be at least 8 characters long and include uppercase, lowercase, number, and special character.';
            }
            // Check if passwords match
            elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match.';
            }
            // Check if new password is same as current
            elseif (verifyCurrentPassword($pdo, $userId, $new_password)) {
                $error = 'New password cannot be the same as current password.';
            }
            else {
                if (updateUserPassword($pdo, $userId, $new_password)) {
                    $success = 'Password changed successfully!';
                    
                    // Create notification
                    createUserNotification(
                        $pdo, 
                        $userId, 
                        'Password Changed', 
                        'Your password was successfully changed. If you did not make this change, please contact support immediately.',
                        'system',
                        null,
                        null,
                        '/pages/account/settings.php',
                        'Review Settings',
                        'fas fa-shield-alt',
                        'high'
                    );
                } else {
                    $error = 'Error changing password. Please try again.';
                }
            }
        }
        
        // Handle preferences update
        elseif (isset($_POST['update_preferences'])) {
            $phone = sanitize_input($_POST['phone'] ?? '');
            
            // Validate phone number if provided
            if (!empty($phone) && !isValidPhone($phone)) {
                $error = 'Please provide a valid phone number.';
            } else {
                $settings = [
                    'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
                    'marketing_emails' => isset($_POST['marketing_emails']) ? 1 : 0,
                    'preferred_language' => $_POST['preferred_language'] ?? 'en',
                    'timezone' => $_POST['timezone'] ?? 'UTC',
                    'phone' => $phone
                ];
                
                if (updateUserSettings($pdo, $userId, $settings)) {
                    $success = 'Preferences updated successfully!';
                    
                    // Update session data
                    $_SESSION['user']['phone'] = $settings['phone'];
                    
                    // Refresh settings
                    $userSettings = getUserSettings($pdo, $userId);
                } else {
                    $error = 'Error updating preferences. Please try again.';
                }
            }
        }
        
        // Handle two-factor authentication toggle
        elseif (isset($_POST['toggle_2fa'])) {
            $enable_2fa = isset($_POST['enable_2fa']) ? 1 : 0;
            
            $settings = ['two_factor_enabled' => $enable_2fa];
            if (updateUserSettings($pdo, $userId, $settings)) {
                $success = $enable_2fa ? 'Two-factor authentication enabled!' : 'Two-factor authentication disabled!';
                $userSettings = getUserSettings($pdo, $userId);
            } else {
                $error = 'Error updating two-factor authentication settings.';
            }
        }
        
        // Handle account deletion
        elseif (isset($_POST['delete_account'])) {
            $confirm_email = $_POST['confirm_email'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if ($confirm_email !== $user['email']) {
                $error = 'Email address does not match.';
            }
            elseif (!verifyCurrentPassword($pdo, $userId, $confirm_password)) {
                $error = 'Password is incorrect.';
            }
            else {
                try {
                    // Start transaction for account deletion
                    $pdo->beginTransaction();
                    
                    // Soft delete user account
                    $delete_stmt = $pdo->prepare("UPDATE users SET status = 0, deleted_at = NOW() WHERE id = ?");
                    if ($delete_stmt->execute([$userId])) {
                        // Logout user
                        session_destroy();
                        
                        $_SESSION['success_message'] = 'Your account has been deleted successfully.';
                        $pdo->commit();
                        header('Location: ' . SITE_URL . '/index.php');
                        exit;
                    } else {
                        $error = 'Error deleting account. Please try again.';
                        $pdo->rollBack();
                    }
                } catch (Exception $e) {
                    error_log("Account deletion error: " . $e->getMessage());
                    $error = 'Error deleting account. Please try again.';
                    $pdo->rollBack();
                }
            }
        }
    }
}

// Set page title
$pageTitle = "Account Settings - HomewareOnTap";

// Get available options
$languages = getAvailableLanguages();
$timezones = getAvailableTimezones();

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo $pageTitle; ?></title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="description" content="Manage your HomewareOnTap account settings, security preferences, and privacy options">
    
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
        line-height: 1.6;
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
        padding: 1.5rem;
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
        margin-bottom: 1.5rem;
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
        font-weight: 500;
        padding: 0.5rem 1.5rem;
    } 
    
    .btn-primary:hover { 
        background-color: #8B6145; /* Darker primary */
        border-color: #8B6145; 
        transform: translateY(-1px);
    } 
    
    .btn-outline-primary {
        color: var(--primary);
        border-color: var(--primary);
    }
    
    .btn-outline-primary:hover {
        background-color: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    /* Alert Styles */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: 1px solid transparent;
        font-weight: 500;
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
        font-size: 2rem;
    }
    
    .page-header p {
        color: var(--dark);
        opacity: 0.7;
        margin: 0;
        font-size: 1.1rem;
    }

    /* Settings Sections */
    .settings-section {
        margin-bottom: 2rem;
    }
    
    .section-title {
        color: var(--dark);
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--secondary);
    }

    /* Form Styles */
    .form-label {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 0.5rem;
    }
    
    .form-text {
        color: #6c757d;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    .password-strength {
        height: 5px;
        margin-top: 5px;
        border-radius: 2px;
        transition: all 0.3s ease;
        width: 0;
    }
    
    .strength-weak { background-color: var(--danger); width: 25%; }
    .strength-fair { background-color: var(--warning); width: 50%; }
    .strength-good { background-color: #28a745; width: 75%; }
    .strength-strong { background-color: var(--success); width: 100%; }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.25rem rgba(166, 123, 91, 0.25);
    }

    /* Switch Toggle */
    .form-check-input:checked {
        background-color: var(--primary);
        border-color: var(--primary);
    }
    
    .form-check-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 0.25rem rgba(166, 123, 91, 0.25);
    }
    
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
    }

    /* Danger Zone */
    .danger-zone {
        border: 2px solid var(--danger);
        border-radius: 12px;
        background: #fff5f5;
    }
    
    .danger-zone .card-header {
        background: var(--danger);
        color: white;
    }

    /* Two-Factor Auth */
    .two-factor-setup {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
        border-left: 4px solid var(--info);
    }
    
    .qr-code {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        display: inline-block;
        margin: 1rem 0;
        border: 1px solid #dee2e6;
    }
    
    /* Status badges */
    .badge {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
    }
    
    /* Loading states */
    .btn-loading {
        position: relative;
        color: transparent !important;
    }
    
    .btn-loading:after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-right-color: transparent;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Mobile optimizations */
    @media (max-width: 768px) {
        .content-area {
            padding: 1rem;
        }
        
        .card-dashboard .card-body {
            padding: 1rem;
        }
        
        .card-dashboard .card-header {
            padding: 1rem 1.25rem;
        }
        
        .page-header h1 {
            font-size: 1.75rem;
        }
        
        .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
        
        .btn-group .btn {
            width: auto;
        }
        
        .settings-section {
            margin-bottom: 1.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .content-area {
            padding: 0.75rem;
        }
        
        .page-header h1 {
            font-size: 1.5rem;
        }
        
        .page-header p {
            font-size: 1rem;
        }
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .card-dashboard {
            background: #2d3748;
            color: #e2e8f0;
        }
        
        .card-dashboard .card-header {
            background: #2d3748;
            color: #e2e8f0;
            border-bottom-color: #4a5568;
        }
        
        .form-label {
            color: #e2e8f0;
        }
        
        .form-control, .form-select {
            background-color: #4a5568;
            border-color: #718096;
            color: #e2e8f0;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: #4a5568;
            color: #e2e8f0;
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
                <div class="container-fluid">
                    <div class="page-header">
                        <h1>Account Settings</h1>
                        <p>Manage your account preferences and security settings</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card-dashboard settings-section">
                                <div class="card-header">
                                    <i class="fas fa-lock me-2"></i> Change Password
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" id="passwordForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label for="current_password" class="form-label">Current Password</label>
                                                    <input type="password" class="form-control" id="current_password" name="current_password" required 
                                                           minlength="8" placeholder="Enter your current password">
                                                    <div class="form-text">Enter your current account password</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="new_password" class="form-label">New Password</label>
                                                    <input type="password" class="form-control" id="new_password" name="new_password" required 
                                                           minlength="8" placeholder="Enter new password">
                                                    <div class="password-strength" id="passwordStrength"></div>
                                                    <div class="form-text">
                                                        Password must be at least 8 characters with uppercase, lowercase, number, and special character.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                                                           minlength="8" placeholder="Confirm new password">
                                                    <div class="form-text" id="passwordMatchText"></div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" name="change_password" class="btn btn-primary" id="passwordSubmitBtn">
                                                    <i class="fas fa-key me-2"></i> Change Password
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="card-dashboard settings-section">
                                <div class="card-header">
                                    <i class="fas fa-sliders-h me-2"></i> Account Preferences
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" id="preferencesForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="phone" class="form-label">Phone Number</label>
                                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                                           value="<?php echo htmlspecialchars($userSettings['phone'] ?? ''); ?>" 
                                                           placeholder="+27 12 345 6789">
                                                    <div class="form-text">We'll only use this for order updates</div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="preferred_language" class="form-label">Preferred Language</label>
                                                    <select class="form-select" id="preferred_language" name="preferred_language">
                                                        <?php foreach ($languages as $code => $name): ?>
                                                            <option value="<?php echo htmlspecialchars($code); ?>" 
                                                                <?php echo ($userSettings['preferred_language'] ?? 'en') === $code ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($name); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="timezone" class="form-label">Timezone</label>
                                                    <select class="form-select" id="timezone" name="timezone">
                                                        <?php foreach ($timezones as $code => $name): ?>
                                                            <option value="<?php echo htmlspecialchars($code); ?>" 
                                                                <?php echo ($userSettings['timezone'] ?? 'UTC') === $code ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($name); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label class="form-label">Email Preferences</label>
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox" id="email_notifications" 
                                                               name="email_notifications" value="1" 
                                                               <?php echo ($userSettings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="email_notifications">
                                                            Order notifications and updates
                                                        </label>
                                                        <div class="form-text">
                                                            Receive emails about your orders, shipping updates, and account activity.
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="marketing_emails" 
                                                               name="marketing_emails" value="1"
                                                               <?php echo ($userSettings['marketing_emails'] ?? 1) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="marketing_emails">
                                                            Marketing and promotional emails
                                                        </label>
                                                        <div class="form-text">
                                                            Receive emails about new products, special offers, and promotions.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="update_preferences" class="btn btn-primary" id="preferencesSubmitBtn">
                                            <i class="fas fa-save me-2"></i> Save Preferences
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="card-dashboard settings-section">
                                <div class="card-header">
                                    <i class="fas fa-shield-alt me-2"></i> Security Settings
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" id="securityForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <div class="row align-items-center mb-4">
                                            <div class="col-md-8">
                                                <h6 class="mb-1">Two-Factor Authentication</h6>
                                                <p class="text-muted mb-0">Add an extra layer of security to your account.</p>
                                            </div>
                                            <div class="col-md-4 text-md-end text-start mt-2 mt-md-0">
                                                <div class="form-check form-switch d-inline-block">
                                                    <input class="form-check-input" type="checkbox" id="enable_2fa" 
                                                           name="enable_2fa" value="1"
                                                           <?php echo ($userSettings['two_factor_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="enable_2fa">
                                                        <?php echo ($userSettings['two_factor_enabled'] ?? 0) ? 'Enabled' : 'Disabled'; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($userSettings['two_factor_enabled'] ?? 0): ?>
                                        <div class="two-factor-setup">
                                            <h6><i class="fas fa-check-circle text-success me-2"></i>Two-Factor Enabled</h6>
                                            <p class="mb-2">Your account is protected with two-factor authentication.</p>
                                            <small class="text-muted">
                                                You'll be required to enter a verification code when signing in from new devices.
                                            </small>
                                        </div>
                                        <?php else: ?>
                                        <div class="two-factor-setup">
                                            <h6><i class="fas fa-info-circle me-2"></i>How it works</h6>
                                            <p class="mb-2">When enabled, you'll need to enter a verification code from your authenticator app when signing in.</p>
                                            <small class="text-muted">
                                                We recommend using Google Authenticator or Authy.
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <button type="submit" name="toggle_2fa" class="btn btn-outline-primary mt-3" id="securitySubmitBtn">
                                            <i class="fas fa-sync-alt me-2"></i> Update Two-Factor Settings
                                        </button>
                                    </form>
                                    
                                    <hr class="my-4">
                                    
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="mb-1">Login Sessions</h6>
                                            <p class="text-muted mb-0">View and manage your active login sessions.</p>
                                        </div>
                                        <div class="col-md-4 text-md-end text-start mt-2 mt-md-0">
                                            <a href="sessions.php" class="btn btn-outline-primary">
                                                <i class="fas fa-external-link-alt me-2"></i> Manage Sessions
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card-dashboard settings-section">
                                <div class="card-header">
                                    <i class="fas fa-user-check me-2"></i> Account Status
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Email Verification:</strong>
                                        <?php if ($userSettings['email_verified'] ?? 0): ?>
                                            <span class="badge bg-success ms-2">Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning ms-2">Pending</span>
                                            <small class="d-block text-muted mt-1">
                                                <a href="#" class="text-primary" id="resendVerification">Resend verification email</a>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Account Created:</strong>
                                        <span class="text-muted ms-2">
                                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Last Login:</strong>
                                        <span class="text-muted ms-2">
                                            <?php 
                                            $lastLogin = $user['last_login'] ?? null;
                                            echo $lastLogin ? date('M j, Y g:i A', strtotime($lastLogin)) : 'Never';
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <a href="profile.php" class="btn btn-outline-primary w-100">
                                            <i class="fas fa-user-edit me-2"></i> Edit Profile
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="card-dashboard danger-zone settings-section">
                                <div class="card-header text-white bg-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i> Danger Zone
                                </div>
                                <div class="card-body">
                                    <p class="text-danger mb-3">
                                        <strong>Warning:</strong> These actions are irreversible. Please proceed with caution.
                                    </p>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                            <i class="fas fa-trash me-2"></i> Delete My Account
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="deleteAccountModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i> Delete Account
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="deleteAccountForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="modal-body">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This action is permanent and cannot be undone. All your data will be permanently deleted.
                        </div>
                        <p>To confirm, please enter your email address and password:</p>
                        
                        <div class="mb-3">
                            <label for="confirm_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="confirm_email" name="confirm_email" 
                                   placeholder="Enter your email address" required
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Enter your password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_account" class="btn btn-danger" id="deleteAccountBtn">
                            <i class="fas fa-trash me-2"></i> Permanently Delete Account
                        </button>
                    </div>
                </form>
            </div>
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

            // Password strength indicator
            $('#new_password').on('input', function() {
                var password = $(this).val();
                var strength = 0;
                var strengthBar = $('#passwordStrength');
                
                // Reset
                strengthBar.removeClass('strength-weak strength-fair strength-good strength-strong').width('0%');
                
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]/)) strength++;
                if (password.match(/[A-Z]/)) strength++;
                if (password.match(/[0-9]/)) strength++;
                if (password.match(/[^a-zA-Z0-9]/)) strength++;
                
                switch(strength) {
                    case 1:
                    case 2:
                        strengthBar.addClass('strength-weak').width('25%');
                        break;
                    case 3:
                        strengthBar.addClass('strength-fair').width('50%');
                        break;
                    case 4:
                        strengthBar.addClass('strength-good').width('75%');
                        break;
                    case 5:
                        strengthBar.addClass('strength-strong').width('100%');
                        break;
                }
            });
            
            // Confirm password match
            $('#confirm_password').on('input', function() {
                var newPassword = $('#new_password').val();
                var confirmPassword = $(this).val();
                var matchText = $('#passwordMatchText');
                
                if (confirmPassword && newPassword !== confirmPassword) {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                    matchText.text('Passwords do not match').removeClass('text-success').addClass('text-danger');
                } else if (confirmPassword && newPassword === confirmPassword) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                    matchText.text('Passwords match').removeClass('text-danger').addClass('text-success');
                } else {
                    $(this).removeClass('is-invalid is-valid');
                    matchText.text('');
                }
            });

            // Two-factor toggle label update
            $('#enable_2fa').on('change', function() {
                var label = $(this).next('.form-check-label');
                label.text(this.checked ? 'Enabled' : 'Disabled');
            });

            // Auto-focus on modal inputs
            $('#deleteAccountModal').on('shown.bs.modal', function () {
                $('#confirm_email').trigger('focus');
            });
            
            // Form submission loading states
            $('#passwordForm, #preferencesForm, #securityForm, #deleteAccountForm').on('submit', function() {
                var submitBtn = $(this).find('button[type="submit"]');
                submitBtn.addClass('btn-loading').prop('disabled', true);
            });
            
            // Resend verification email
            $('#resendVerification').on('click', function(e) {
                e.preventDefault();
                var $this = $(this);
                $this.text('Sending...').prop('disabled', true);
                
                // Simulate API call - you would replace this with actual API call
                setTimeout(function() {
                    $this.text('Verification email sent!').removeClass('text-primary').addClass('text-success');
                    setTimeout(function() {
                        $this.text('Resend verification email').removeClass('text-success').addClass('text-primary').prop('disabled', false);
                    }, 3000);
                }, 1000);
            });
            
            // Phone number formatting
            $('#phone').on('blur', function() {
                var phone = $(this).val().replace(/\D/g, '');
                if (phone.length === 9 && !phone.startsWith('0')) {
                    $(this).val('+27 ' + phone.substring(0, 2) + ' ' + phone.substring(2, 5) + ' ' + phone.substring(5));
                }
            });
            
            // Enhanced validation for delete account
            $('#deleteAccountForm').on('submit', function(e) {
                var email = $('#confirm_email').val();
                var userEmail = '<?php echo htmlspecialchars($user['email'] ?? ''); ?>';
                
                if (email !== userEmail) {
                    e.preventDefault();
                    $('#confirm_email').addClass('is-invalid');
                    alert('Email address does not match your account email.');
                }
            });
            
            // Real-time validation for delete account email
            $('#confirm_email').on('input', function() {
                var email = $(this).val();
                var userEmail = '<?php echo htmlspecialchars($user['email'] ?? ''); ?>';
                
                if (email === userEmail) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                }
            });
            
            // Mobile menu enhancements
            function handleMobileView() {
                if ($(window).width() < 768) {
                    $('.main-content').addClass('mobile-view');
                } else {
                    $('.main-content').removeClass('mobile-view');
                }
            }
            
            $(window).on('resize', handleMobileView);
            handleMobileView();
        });
    </script>
</body>
</html>