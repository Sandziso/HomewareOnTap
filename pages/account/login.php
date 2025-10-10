<?php
// File: pages/auth/login.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/index.php');
    } else {
        header('Location: ' . SITE_URL . '/pages/account/dashboard.php');
    }
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$email = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        set_message('Invalid CSRF token. Please try again.', 'error');
        header('Location: login.php');
        exit();
    }

    // Sanitize inputs
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validate inputs
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (empty($password)) {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        // Attempt login
        if (!userLogin($email, $password)) {
            set_message('Invalid email or password.', 'error');
        } else {
            // Remember me
            if ($remember) {
                $user = getUserByEmail($email);
                $token = generate_remember_token();
                set_remember_token($user['id'], $token);
                setcookie('remember_token', $user['id'] . '|' . $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
            }

            $user = getUserByEmail($email);
            if ($user['role'] === 'admin') {
                header('Location: ' . SITE_URL . '/admin/index.php');
            } else {
                header('Location: ' . SITE_URL . '/pages/account/dashboard.php');
            }
            exit();
        }
    } else {
        set_message(implode('<br>', $errors), 'error');
    }

    header('Location: login.php');
    exit();
}

$pageTitle = "Login - HomewareOnTap";
require_once '../../includes/header.php';
?>

<div class="login-container">
    <div class="login-card">

        <!-- Flash messages -->
        <?php
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . '">' . htmlspecialchars($flash['message']) . '</div>';
            unset($_SESSION['flash_message']);
        }
        ?>

        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Sign in to your account</p>
        </div>

        <!-- Login Form -->
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="<?php echo SITE_URL; ?>/pages/auth/forgot-password.php" class="forgot-password">Forgot password?</a>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="<?php echo SITE_URL; ?>/pages/auth/register.php">Create one here</a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>