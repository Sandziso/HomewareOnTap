<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $_SESSION['message'] = "Please provide your email address.";
        $_SESSION['message_type'] = "danger";
        header("Location: resend-verification.php");
        exit();
    }
    
    $result = resend_verification_email($email);
    
    $_SESSION['message'] = $result['message'];
    $_SESSION['message_type'] = $result['success'] ? 'success' : 'danger';
    
    if ($result['success']) {
        $_SESSION['pending_verification_email'] = $email;
        header("Location: verification-pending.php");
    } else {
        header("Location: resend-verification.php");
    }
    exit();
}

// If GET request, show the resend form
$email = $_SESSION['pending_verification_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification - HomewareOnTap</title>
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
        
        .resend-container {
            max-width: 500px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        
        .resend-header {
            background: var(--gradient-primary);
            color: white;
            padding: 2.5rem;
            text-align: center;
            position: relative;
        }
        
        .resend-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-size: cover;
        }
        
        .resend-body {
            padding: 2.5rem;
        }
        
        .form-control {
            padding: 14px 16px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(166, 123, 91, 0.1);
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            padding: 14px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(166, 123, 91, 0.3);
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(166, 123, 91, 0.4);
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        .feature-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.8rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="resend-container fade-in">
                    <div class="resend-header">
                        <div class="feature-icon">
                            <i class="fas fa-redo"></i>
                        </div>
                        <h2 class="mb-2">Resend Verification</h2>
                        <p class="mb-0">Get a new verification email</p>
                    </div>
                    
                    <div class="resend-body">
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                                <?php 
                                    echo $_SESSION['message']; 
                                    unset($_SESSION['message']);
                                    unset($_SESSION['message_type']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <p class="text-center mb-4">Enter your email address below and we'll send you a new verification link.</p>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email); ?>" 
                                       placeholder="Enter your email address" required>
                                <div class="form-text">Make sure this is the same email you used during registration.</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary mb-3">
                                <i class="fas fa-paper-plane me-2"></i>Send Verification Email
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <a href="login.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Login
                            </a>
                        </div>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                Still having issues? <a href="<?php echo SITE_URL; ?>/pages/static/contact.php" class="text-decoration-none">Contact support</a>
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
            // Focus on email field
            $('#email').focus();
            
            // Form validation
            $('form').on('submit', function() {
                const email = $('#email').val();
                if (!email) {
                    $('#email').addClass('is-invalid');
                    return false;
                }
                
                // Simple email validation
                const emailPattern = /^[^@]+@[^@]+\.[^@]+$/;
                if (!emailPattern.test(email)) {
                    $('#email').addClass('is-invalid');
                    return false;
                }
                
                return true;
            });
            
            // Clear validation on input
            $('#email').on('input', function() {
                $(this).removeClass('is-invalid');
            });
        });
    </script>
</body>
</html>