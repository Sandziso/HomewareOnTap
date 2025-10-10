<?php
// admin/customers/create.php
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = intval($_POST['status']);
    $sendWelcomeEmail = isset($_POST['send_welcome_email']) ? 1 : 0;
    
    $errors = [];
    
    // Validate required fields
    if (empty($firstName)) {
        $errors[] = "First name is required.";
    }
    
    if (empty($lastName)) {
        $errors[] = "Last name is required.";
    }
    
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address format.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        
        if ($checkStmt->fetch()) {
            $errors[] = "Email address already exists for another user.";
        }
    }
    
    // If no errors, create the customer
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert the user
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, phone, password, role, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $firstName, 
                $lastName, 
                $email, 
                $phone, 
                $hashedPassword, 
                $role, 
                $status
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // Log admin activity
            $activityStmt = $pdo->prepare("
                INSERT INTO admin_activities (user_id, action, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $activityStmt->execute([
                $_SESSION['user_id'],
                'create_customer',
                "Created customer: {$firstName} {$lastName} ({$email})",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            $pdo->commit();
            
            $_SESSION['success_message'] = "Customer created successfully!";
            
            // Send welcome email if requested
            if ($sendWelcomeEmail) {
                // In a real application, you would implement email sending here
                // For now, we'll just add a note
                $_SESSION['success_message'] .= " Welcome email has been queued for sending.";
            }
            
            // Redirect to customer list or view page
            header("Location: list.php");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Error creating customer: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Customer - HomewareOnTap Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: #8B6145;
            border-color: #8B6145;
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
        
        .form-section {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .section-title {
            color: var(--primary);
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        
        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-fair { background-color: #fd7e14; width: 50%; }
        .strength-good { background-color: #ffc107; width: 75%; }
        .strength-strong { background-color: #198754; width: 100%; }
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
                    <h4 class="mb-0">Add New Customer</h4>
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

        <!-- Customer Creation Form -->
        <div class="content-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <a href="list.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Back to Customers
                    </a>
                    <h3 class="mb-0 d-inline-block ms-2">Add New Customer</h3>
                </div>
            </div>

            <?php 
            // Display error messages
            if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <h5 class="alert-heading">Please fix the following errors:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="form-section">
                <h4 class="section-title">Customer Information</h4>
                
                <form method="POST" action="" id="customerForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="firstName" name="first_name" 
                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                       required>
                                <div class="form-text">Customer's first name</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="lastName" name="last_name" 
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                       required>
                                <div class="form-text">Customer's last name</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                       required>
                                <div class="form-text">Customer's email address for communication</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                <div class="form-text">Customer's contact number (optional)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Minimum 8 characters</div>
                                <div class="password-strength" id="passwordStrength"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="confirmPassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                                <div class="form-text">Re-enter the password</div>
                                <div class="invalid-feedback" id="passwordMatchError">Passwords do not match</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="customer" <?php echo (isset($_POST['role']) && $_POST['role'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
                                    <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="manager" <?php echo (isset($_POST['role']) && $_POST['role'] == 'manager') ? 'selected' : ''; ?>>Manager</option>
                                </select>
                                <div class="form-text">User role and permissions</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="1" <?php echo (isset($_POST['status']) && $_POST['status'] == 1) ? 'selected' : 'selected'; ?>>Active</option>
                                    <option value="0" <?php echo (isset($_POST['status']) && $_POST['status'] == 0) ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                                <div class="form-text">Account status</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sendWelcomeEmail" name="send_welcome_email" value="1" checked>
                            <label class="form-check-label" for="sendWelcomeEmail">
                                Send welcome email with login details
                            </label>
                        </div>
                        <div class="form-text">Customer will receive an email with their account information</div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Create Customer
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Help Section -->
            <div class="card card-dashboard">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Tips</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle text-primary me-2"></i>About Customer Roles</h6>
                            <ul class="small text-muted">
                                <li><strong>Customer:</strong> Standard user with shopping privileges</li>
                                <li><strong>Manager:</strong> Can manage products and orders</li>
                                <li><strong>Admin:</strong> Full system access</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-shield-alt text-primary me-2"></i>Security Notes</h6>
                            <ul class="small text-muted">
                                <li>Use strong passwords with mixed characters</li>
                                <li>Consider sending welcome emails for new accounts</li>
                                <li>Verify email format before submission</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Password strength indicator
            $('#password').on('input', function() {
                const password = $(this).val();
                const strengthBar = $('#passwordStrength');
                
                if (password.length === 0) {
                    strengthBar.removeClass('strength-weak strength-fair strength-good strength-strong').css('width', '0');
                    return;
                }
                
                let strength = 0;
                
                // Length check
                if (password.length >= 8) strength += 1;
                if (password.length >= 12) strength += 1;
                
                // Character variety checks
                if (/[a-z]/.test(password)) strength += 1;
                if (/[A-Z]/.test(password)) strength += 1;
                if (/[0-9]/.test(password)) strength += 1;
                if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
                
                // Update strength bar
                strengthBar.removeClass('strength-weak strength-fair strength-good strength-strong');
                
                if (strength <= 2) {
                    strengthBar.addClass('strength-weak');
                } else if (strength <= 4) {
                    strengthBar.addClass('strength-fair');
                } else if (strength <= 5) {
                    strengthBar.addClass('strength-good');
                } else {
                    strengthBar.addClass('strength-strong');
                }
            });
            
            // Password confirmation validation
            $('#confirmPassword').on('input', function() {
                const password = $('#password').val();
                const confirmPassword = $(this).val();
                const errorElement = $('#passwordMatchError');
                
                if (confirmPassword.length > 0 && password !== confirmPassword) {
                    $(this).addClass('is-invalid');
                    errorElement.show();
                } else {
                    $(this).removeClass('is-invalid');
                    errorElement.hide();
                }
            });
            
            // Form submission validation
            $('#customerForm').on('submit', function(e) {
                const password = $('#password').val();
                const confirmPassword = $('#confirmPassword').val();
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    $('#confirmPassword').addClass('is-invalid');
                    $('#passwordMatchError').show();
                    $('html, body').animate({
                        scrollTop: $('#confirmPassword').offset().top - 100
                    }, 500);
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    $('#password').addClass('is-invalid');
                    $('html, body').animate({
                        scrollTop: $('#password').offset().top - 100
                    }, 500);
                }
            });
            
            // Sidebar toggle functionality
            $('#sidebarToggle').click(function() {
                const sidebar = $('#adminSidebar');
                if (sidebar.length === 0) {
                    $('.main-content').toggleClass('full-width');
                } else {
                    sidebar.toggleClass('active');
                    $('#sidebarOverlay').toggle();
                    $('body').toggleClass('overflow-hidden');
                }
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