<?php
session_start();
require_once '../../includes/config.php';

if (!isset($_SESSION['pending_verification_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['pending_verification_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - HomewareOnTap</title>
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #A67B5B;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
            --gradient-primary: linear-gradient(135deg, #A67B5B 0%, #8B6145 100%);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.15);
            --border-radius: 12px;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: var(--dark);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'League Spartan', sans-serif;
            font-weight: 600;
        }
        
        .verification-container {
            max-width: 600px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        
        .verification-header {
            background: var(--gradient-primary);
            color: white;
            padding: 3rem;
            text-align: center;
            position: relative;
        }
        
        .verification-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }
        
        .verification-body {
            padding: 3rem;
        }
        
        .email-highlight {
            background: var(--light);
            border-left: 4px solid var(--primary);
            padding: 15px;
            margin: 20px 0;
            font-weight: 600;
            border-radius: 8px;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(166, 123, 91, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(166, 123, 91, 0.4);
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 10px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease;
        }
        
        @media (max-width: 768px) {
            .verification-header {
                padding: 2rem;
            }
            
            .verification-body {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="verification-container fade-in">
                    <div class="verification-header">
                        <div class="feature-icon">
                            <i class="fas fa-envelope-circle-check"></i>
                        </div>
                        <h2 class="mb-3">Verify Your Email</h2>
                        <p class="mb-0 lead">Almost there! Just one more step to complete your registration.</p>
                    </div>
                    
                    <div class="verification-body">
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                                <?php 
                                    echo $_SESSION['message']; 
                                    unset($_SESSION['message']);
                                    unset($_SESSION['message_type']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <div class="text-center mb-4">
                            <i class="fas fa-paper-plane fa-3x text-primary mb-3"></i>
                            <h3>Check Your Email</h3>
                        </div>
                        
                        <p class="text-center">We've sent a verification link to your email address. Click the link in the email to activate your account and start shopping with HomewareOnTap.</p>
                        
                        <div class="email-highlight text-center">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo htmlspecialchars($email); ?>
                        </div>
                        
                        <div class="alert alert-info">
                            <h5 class="alert-heading">
                                <i class="fas fa-info-circle me-2"></i>Didn't receive the email?
                            </h5>
                            <ul class="mb-0">
                                <li>Check your spam or junk folder</li>
                                <li>Make sure you entered <strong><?php echo htmlspecialchars($email); ?></strong> correctly</li>
                                <li>Wait a few minutes - delivery can sometimes take 2-5 minutes</li>
                                <li>Add <strong><?php echo MAIL_FROM; ?></strong> to your contacts to prevent emails going to spam</li>
                            </ul>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="resend-verification.php" class="btn btn-primary w-100">
                                    <i class="fas fa-redo me-2"></i>Resend Verification Email
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="login.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                                </a>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                Having trouble? <a href="<?php echo SITE_URL; ?>/pages/static/contact.php" class="text-decoration-none">Contact our support team</a>
                            </small>
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
            // Add some interactive elements
            $('.btn').hover(function() {
                $(this).addClass('shadow');
            }, function() {
                $(this).removeClass('shadow');
            });
            
            // Auto-resend after 2 minutes if user is still on page
            setTimeout(function() {
                $('.alert-info').append('<div class="mt-2"><small>Still no email? You can resend the verification email now.</small></div>');
            }, 120000); // 2 minutes
        });
    </script>
</body>
</html>