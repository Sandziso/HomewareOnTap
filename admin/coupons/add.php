<?php
// admin/coupons/add.php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in and has admin privileges
if (!isAdminLoggedIn()) {
    header('Location: ../../pages/auth/login.php?redirect=admin');
    exit();
}

// Initialize variables
$errors = [];
$formData = [
    'code' => '',
    'description' => '',
    'discount_type' => 'percentage',
    'discount_value' => '',
    'min_cart_total' => '',
    'maximum_discount' => '',
    'usage_limit' => '',
    'start_date' => '',
    'end_date' => '',
    'is_active' => 1
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input data
    $formData['code'] = sanitize_input($_POST['code'] ?? '');
    $formData['description'] = sanitize_input($_POST['description'] ?? '');
    $formData['discount_type'] = sanitize_input($_POST['discount_type'] ?? 'percentage');
    $formData['discount_value'] = sanitize_input($_POST['discount_value'] ?? '');
    $formData['min_cart_total'] = sanitize_input($_POST['min_cart_total'] ?? '');
    $formData['maximum_discount'] = sanitize_input($_POST['maximum_discount'] ?? '');
    $formData['usage_limit'] = sanitize_input($_POST['usage_limit'] ?? '');
    $formData['start_date'] = sanitize_input($_POST['start_date'] ?? '');
    $formData['end_date'] = sanitize_input($_POST['end_date'] ?? '');
    $formData['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    // Validate required fields
    if (empty($formData['code'])) {
        $errors['code'] = 'Coupon code is required.';
    }

    if (empty($formData['discount_value'])) {
        $errors['discount_value'] = 'Discount value is required.';
    } elseif (!is_numeric($formData['discount_value']) || $formData['discount_value'] <= 0) {
        $errors['discount_value'] = 'Discount value must be a positive number.';
    }

    // Validate discount type specific rules
    if ($formData['discount_type'] === 'percentage' && $formData['discount_value'] > 100) {
        $errors['discount_value'] = 'Percentage discount cannot exceed 100%.';
    }

    // Validate dates
    if (!empty($formData['start_date']) && !empty($formData['end_date'])) {
        $startDate = strtotime($formData['start_date']);
        $endDate = strtotime($formData['end_date']);
        
        if ($endDate < $startDate) {
            $errors['end_date'] = 'End date cannot be before start date.';
        }
    }

    // Check if coupon code already exists
    if (empty($errors['code'])) {
        $stmt = $pdo->prepare("SELECT id FROM coupons WHERE code = ?");
        $stmt->execute([$formData['code']]);
        if ($stmt->fetch()) {
            $errors['code'] = 'Coupon code already exists.';
        }
    }

    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO coupons 
                (code, description, discount_type, discount_value, min_cart_total, maximum_discount, usage_limit, start_date, end_date, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $success = $stmt->execute([
                $formData['code'],
                $formData['description'],
                $formData['discount_type'],
                $formData['discount_value'],
                $formData['min_cart_total'] ?: 0,
                $formData['maximum_discount'] ?: null,
                $formData['usage_limit'] ?: null,
                $formData['start_date'] ?: null,
                $formData['end_date'] ?: null,
                $formData['is_active']
            ]);

            if ($success) {
                $_SESSION['success_message'] = "Coupon created successfully!";
                header('Location: manage.php');
                exit();
            } else {
                $errors['general'] = "Failed to create coupon. Please try again.";
            }

        } catch (PDOException $e) {
            error_log("Coupon creation error: " . $e->getMessage());
            $errors['general'] = "Database error occurred. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add New Coupon - HomewareOnTap Admin</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Flatpickr CSS for datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
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
            margin-bottom: 2rem;
        }
        
        .section-title {
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            color: var(--dark);
        }
        
        .discount-preview {
            background: var(--light);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .is-invalid {
            border-color: #dc3545;
        }
        
        .invalid-feedback {
            display: block;
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
                    <h4 class="mb-0">Add New Coupon</h4>
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

        <!-- Coupon Form -->
        <div class="content-section" id="couponFormSection">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card card-dashboard">
                        <div class="card-body">
                            <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $errors['general']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php endif; ?>

                            <form method="POST" action="" id="couponForm">
                                <!-- Basic Information Section -->
                                <div class="form-section">
                                    <h5 class="section-title">Basic Information</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="code" class="form-label">Coupon Code <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?php echo isset($errors['code']) ? 'is-invalid' : ''; ?>" 
                                                   id="code" name="code" value="<?php echo htmlspecialchars($formData['code']); ?>" 
                                                   placeholder="e.g., WELCOME10" required>
                                            <?php if (isset($errors['code'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['code']; ?></div>
                                            <?php endif; ?>
                                            <div class="form-text">Unique code customers will enter at checkout.</div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <input type="text" class="form-control" id="description" name="description" 
                                                   value="<?php echo htmlspecialchars($formData['description']); ?>" 
                                                   placeholder="e.g., 10% off for new customers">
                                            <div class="form-text">Optional description for internal reference.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Discount Details Section -->
                                <div class="form-section">
                                    <h5 class="section-title">Discount Details</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="discount_type" class="form-label">Discount Type <span class="text-danger">*</span></label>
                                            <select class="form-select" id="discount_type" name="discount_type" required>
                                                <option value="percentage" <?php echo $formData['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                                                <option value="fixed" <?php echo $formData['discount_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount (R)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="discount_value" class="form-label">Discount Value <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control <?php echo isset($errors['discount_value']) ? 'is-invalid' : ''; ?>" 
                                                   id="discount_value" name="discount_value" 
                                                   value="<?php echo htmlspecialchars($formData['discount_value']); ?>" 
                                                   step="0.01" min="0.01" required>
                                            <?php if (isset($errors['discount_value'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['discount_value']; ?></div>
                                            <?php endif; ?>
                                            <div class="form-text" id="discountHelp">
                                                For percentage: 10 = 10% off. For fixed: 50 = R50 off.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="min_cart_total" class="form-label">Minimum Cart Total</label>
                                            <input type="number" class="form-control" id="min_cart_total" name="min_cart_total" 
                                                   value="<?php echo htmlspecialchars($formData['min_cart_total']); ?>" 
                                                   step="0.01" min="0" placeholder="0.00">
                                            <div class="form-text">Minimum cart value required to use this coupon. Leave empty for no minimum.</div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="maximum_discount" class="form-label">Maximum Discount</label>
                                            <input type="number" class="form-control" id="maximum_discount" name="maximum_discount" 
                                                   value="<?php echo htmlspecialchars($formData['maximum_discount']); ?>" 
                                                   step="0.01" min="0" placeholder="0.00">
                                            <div class="form-text">Maximum discount amount (for percentage discounts). Leave empty for no limit.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Usage Limits Section -->
                                <div class="form-section">
                                    <h5 class="section-title">Usage Limits</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="usage_limit" class="form-label">Usage Limit</label>
                                            <input type="number" class="form-control" id="usage_limit" name="usage_limit" 
                                                   value="<?php echo htmlspecialchars($formData['usage_limit']); ?>" 
                                                   min="1" placeholder="e.g., 100">
                                            <div class="form-text">Maximum number of times this coupon can be used. Leave empty for unlimited usage.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Date Range Section -->
                                <div class="form-section">
                                    <h5 class="section-title">Date Range</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="text" class="form-control flatpickr" id="start_date" name="start_date" 
                                                   value="<?php echo htmlspecialchars($formData['start_date']); ?>" 
                                                   placeholder="Select start date">
                                            <div class="form-text">Date when coupon becomes active. Leave empty for immediate activation.</div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="text" class="form-control flatpickr <?php echo isset($errors['end_date']) ? 'is-invalid' : ''; ?>" 
                                                   id="end_date" name="end_date" 
                                                   value="<?php echo htmlspecialchars($formData['end_date']); ?>" 
                                                   placeholder="Select end date">
                                            <?php if (isset($errors['end_date'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['end_date']; ?></div>
                                            <?php endif; ?>
                                            <div class="form-text">Date when coupon expires. Leave empty for no expiration.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status Section -->
                                <div class="form-section">
                                    <h5 class="section-title">Status</h5>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                   value="1" <?php echo $formData['is_active'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_active">
                                                Active Coupon
                                            </label>
                                        </div>
                                        <div class="form-text">Inactive coupons cannot be used by customers.</div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="form-section">
                                    <div class="d-flex justify-content-between">
                                        <a href="manage.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Coupons
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Create Coupon
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Preview Section -->
                <div class="col-lg-4">
                    <div class="card card-dashboard">
                        <div class="card-body">
                            <h5 class="section-title">Coupon Preview</h5>
                            
                            <div class="discount-preview">
                                <div class="text-center mb-3">
                                    <h4 id="previewCode" class="text-primary"><?php echo htmlspecialchars($formData['code']) ?: 'COUPONCODE'; ?></h4>
                                    <div id="previewDiscount" class="h5">
                                        <?php if ($formData['discount_type'] === 'percentage'): ?>
                                            <?php echo $formData['discount_value'] ? $formData['discount_value'] . '% OFF' : '0% OFF'; ?>
                                        <?php else: ?>
                                            R<?php echo $formData['discount_value'] ? number_format($formData['discount_value'], 2) : '0.00'; ?> OFF
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="small">
                                    <div id="previewMinCart" class="mb-1">
                                        <?php if ($formData['min_cart_total']): ?>
                                            <i class="fas fa-shopping-cart me-2"></i>Min. spend: R<?php echo number_format($formData['min_cart_total'], 2); ?>
                                        <?php else: ?>
                                            <i class="fas fa-shopping-cart me-2"></i>No minimum spend
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div id="previewMaxDiscount" class="mb-1">
                                        <?php if ($formData['maximum_discount'] && $formData['discount_type'] === 'percentage'): ?>
                                            <i class="fas fa-tag me-2"></i>Max discount: R<?php echo number_format($formData['maximum_discount'], 2); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div id="previewUsage" class="mb-1">
                                        <?php if ($formData['usage_limit']): ?>
                                            <i class="fas fa-users me-2"></i>Limited to <?php echo $formData['usage_limit']; ?> uses
                                        <?php else: ?>
                                            <i class="fas fa-users me-2"></i>Unlimited uses
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div id="previewDates">
                                        <?php if ($formData['start_date'] || $formData['end_date']): ?>
                                            <i class="fas fa-calendar me-2"></i>
                                            <?php 
                                                echo $formData['start_date'] ? date('M j, Y', strtotime($formData['start_date'])) : 'Now';
                                                echo ' - ';
                                                echo $formData['end_date'] ? date('M j, Y', strtotime($formData['end_date'])) : 'No expiry';
                                            ?>
                                        <?php else: ?>
                                            <i class="fas fa-calendar me-2"></i>No expiry date
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <h6>Coupon Tips:</h6>
                                <ul class="small text-muted">
                                    <li>Use clear, memorable coupon codes</li>
                                    <li>Set reasonable usage limits to prevent abuse</li>
                                    <li>Consider minimum cart values to increase order size</li>
                                    <li>Test coupons before making them public</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Flatpickr JS for datepicker -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        $(document).ready(function() {
            // Initialize datepicker
            flatpickr('.flatpickr', {
                enableTime: false,
                dateFormat: 'Y-m-d',
                minDate: 'today'
            });

            // Update preview in real-time
            function updatePreview() {
                // Code
                $('#previewCode').text($('#code').val() || 'COUPONCODE');
                
                // Discount
                const discountType = $('#discount_type').val();
                const discountValue = $('#discount_value').val() || '0';
                if (discountType === 'percentage') {
                    $('#previewDiscount').text(discountValue + '% OFF');
                } else {
                    $('#previewDiscount').text('R' + parseFloat(discountValue).toFixed(2) + ' OFF');
                }
                
                // Min cart
                const minCart = $('#min_cart_total').val();
                if (minCart) {
                    $('#previewMinCart').html('<i class="fas fa-shopping-cart me-2"></i>Min. spend: R' + parseFloat(minCart).toFixed(2));
                } else {
                    $('#previewMinCart').html('<i class="fas fa-shopping-cart me-2"></i>No minimum spend');
                }
                
                // Max discount
                const maxDiscount = $('#maximum_discount').val();
                if (maxDiscount && discountType === 'percentage') {
                    $('#previewMaxDiscount').html('<i class="fas fa-tag me-2"></i>Max discount: R' + parseFloat(maxDiscount).toFixed(2));
                } else {
                    $('#previewMaxDiscount').html('');
                }
                
                // Usage limit
                const usageLimit = $('#usage_limit').val();
                if (usageLimit) {
                    $('#previewUsage').html('<i class="fas fa-users me-2"></i>Limited to ' + usageLimit + ' uses');
                } else {
                    $('#previewUsage').html('<i class="fas fa-users me-2"></i>Unlimited uses');
                }
            }

            // Update preview on input changes
            $('#code, #discount_value, #min_cart_total, #maximum_discount, #usage_limit').on('input', updatePreview);
            $('#discount_type').on('change', updatePreview);

            // Initial preview update
            updatePreview();

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

            // Form validation
            $('#couponForm').on('submit', function(e) {
                let valid = true;
                const code = $('#code').val().trim();
                const discountValue = parseFloat($('#discount_value').val());

                // Validate coupon code
                if (!code) {
                    $('#code').addClass('is-invalid');
                    valid = false;
                }

                // Validate discount value
                if (!discountValue || discountValue <= 0) {
                    $('#discount_value').addClass('is-invalid');
                    valid = false;
                }

                // Validate percentage discount limit
                if ($('#discount_type').val() === 'percentage' && discountValue > 100) {
                    $('#discount_value').addClass('is-invalid');
                    valid = false;
                }

                if (!valid) {
                    e.preventDefault();
                    // Scroll to first error
                    $('.is-invalid').first().focus();
                }
            });

            // Remove validation styles when user starts typing
            $('input, select').on('input change', function() {
                $(this).removeClass('is-invalid');
            });
        });
    </script>
</body>
</html>