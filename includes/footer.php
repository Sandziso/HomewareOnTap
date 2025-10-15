<?php
// Check if we're in admin section
$isAdminPage = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
?>

<?php if (!$isAdminPage): ?>
<!-- Frontend Footer -->
<style>
    :root {
        --primary: #A67B5B;
        --secondary: #F2E8D5;
        --light: #F9F5F0;
        --dark: #3A3229;
    }
    
    .footer {
        background-color: var(--dark);
        color: white;
        padding: 60px 0 30px;
    }
    
    .footer-title {
        position: relative;
        margin-bottom: 25px;
        font-size: 20px;
        font-family: 'League Spartan', sans-serif;
    }
    
    .footer-title:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 0;
        width: 40px;
        height: 2px;
        background-color: var(--primary);
    }
    
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-links li {
        margin-bottom: 12px;
    }
    
    .footer-links a {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        transition: color 0.3s;
    }
    
    .footer-links a:hover {
        color: var(--primary);
    }
    
    .contact-info {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .contact-info li {
        margin-bottom: 15px;
        display: flex;
        align-items: flex-start;
    }
    
    .contact-info i {
        margin-right: 15px;
        color: var(--primary);
        font-size: 20px;
        margin-top: 3px;
    }
    
    .social-icons {
        display: flex;
        margin-top: 20px;
    }
    
    .social-icons a {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        margin-right: 10px;
        transition: all 0.3s;
        text-decoration: none;
    }
    
    .social-icons a:hover {
        background-color: var(--primary);
        transform: translateY(-3px);
    }
    
    .copyright {
        text-align: center;
        padding-top: 30px;
        margin-top: 30px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.7);
    }
    
    .newsletter-section {
        background: linear-gradient(rgba(58, 50, 41, 0.9), rgba(58, 50, 41, 0.9));
        padding: 80px 0;
        color: white;
    }
    
    .newsletter-form {
        display: flex;
        max-width: 500px;
        margin: 0 auto;
    }
    
    .newsletter-form input {
        flex: 1;
        padding: 15px 20px;
        border: none;
        border-radius: 30px 0 0 30px;
        outline: none;
    }
    
    .newsletter-form button {
        padding: 0 25px;
        background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
        color: white;
        border: none;
        border-radius: 0 30px 30px 0;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .newsletter-form button:hover {
        background: linear-gradient(135deg, #8B6145 0%, var(--primary) 100%);
    }
    
    /* Auth Modal Styles */
    .auth-modal .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }
    
    .auth-header {
        background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
        color: white;
        padding: 30px;
        text-align: center;
        border-radius: 15px 15px 0 0;
    }
    
    .auth-tabs {
        display: flex;
        border-bottom: 1px solid #e9ecef;
    }
    
    .auth-tab {
        flex: 1;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 600;
        border-bottom: 3px solid transparent;
    }
    
    .auth-tab.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }
    
    .auth-form-container {
        display: none;
        padding: 30px;
    }
    
    .auth-form-container.active {
        display: block;
    }
    
    .auth-form .form-group {
        margin-bottom: 20px;
    }
    
    .auth-form label {
        font-weight: 500;
        margin-bottom: 8px;
        color: var(--dark);
    }
    
    .auth-form .form-control {
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        transition: all 0.3s;
    }
    
    .auth-form .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(166, 123, 91, 0.1);
    }
    
    .btn-auth {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-auth:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(166, 123, 91, 0.4);
    }
    
    .auth-footer {
        text-align: center;
        margin-top: 20px;
    }
    
    .auth-footer a {
        color: var(--primary);
        text-decoration: none;
    }
    
    /* Toast Notification */
    .toast-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        transform: translateX(150%);
        transition: transform 0.3s ease;
        z-index: 1060;
        border-left: 4px solid var(--primary);
    }
    
    .toast-notification.show {
        transform: translateX(0);
    }
    
    .toast-notification.success {
        border-left-color: var(--success);
    }
    
    .toast-notification.error {
        border-left-color: var(--danger);
    }
    
    .toast-notification i {
        margin-right: 10px;
        font-size: 20px;
    }
    
    .toast-notification.success i {
        color: var(--success);
    }
    
    .toast-notification.error i {
        color: var(--danger);
    }
    
    /* Back to Top */
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        opacity: 0;
        transition: all 0.3s;
        z-index: 1000;
    }
    
    .back-to-top.show {
        opacity: 1;
    }
    
    .back-to-top:hover {
        transform: translateY(-3px);
        color: white;
    }

    @media (max-width: 768px) {
        .newsletter-form {
            flex-direction: column;
        }
        
        .newsletter-form input {
            border-radius: 30px;
            margin-bottom: 10px;
        }
        
        .newsletter-form button {
            border-radius: 30px;
            padding: 12px;
        }
        
        .footer {
            padding: 40px 0 20px;
        }
        
        .back-to-top {
            bottom: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
        }
    }
</style>

<!-- Newsletter Section -->
<section class="newsletter-section">
    <div class="container text-center">
        <h2>Subscribe to Our Newsletter</h2>
        <p class="mb-4">Get updates on new products, special offers, and interior design tips.</p>
        
        <form class="newsletter-form" id="newsletterForm" action="<?php echo SITE_URL; ?>/includes/newsletter-subscribe.php" method="POST">
            <input type="email" name="email" placeholder="Your email address" required>
            <button type="submit">Subscribe</button>
        </form>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h4 class="footer-title">HomewareOnTap</h4>
                <p>Transforming homes with quality essentials that combine functionality with elegant design.</p>
                <div class="social-icons">
                    <a href="https://wa.me/27698788382" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="https://instagram.com/homewareontap" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://tiktok.com/@homewareontap" title="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/shop.php">Shop</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/about.php">About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/contact.php">Contact</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/static/faqs.php">FAQ</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/static/track-order.php">Track Order</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h4 class="footer-title">Categories</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?category=kitchenware">Kitchenware</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?category=home-decor">Home Decor</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?category=bed-bath">Bed & Bath</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?category=tableware">Tableware</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?category=storage">Storage Solutions</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h4 class="footer-title">Contact Us</h4>
                <ul class="contact-info">
                    <li>
                        <i class="fas fa-phone"></i>
                        <span>+27698788382</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>homewareontap@gmail.com</span>
                    </li>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>South Africa</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; 2025 HomewareOnTap. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<!-- Auth Modal -->
<div class="modal fade auth-modal" id="authModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="auth-header">
                <h5 class="modal-title" id="authModalLabel">Welcome to HomewareOnTap</h5>
                <p>Sign in or create an account to continue</p>
            </div>
            <div class="auth-tabs">
                <div class="auth-tab active" data-tab="login">Login</div>
                <div class="auth-tab" data-tab="register">Register</div>
            </div>
            
            <!-- Login Form -->
            <div class="auth-form-container active" id="login-form">
                <form class="auth-form" id="loginForm" action="<?php echo SITE_URL; ?>/pages/auth/login-process.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="form-group">
                        <label for="login-email">Email Address</label>
                        <input type="email" class="form-control" id="login-email" name="email" placeholder="Enter your email" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" class="form-control" id="login-password" name="password" placeholder="Enter your password" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="remember-me" name="remember">
                        <label class="form-check-label" for="remember-me">Remember me</label>
                    </div>
                    <button type="submit" class="btn-auth">Login</button>
                    <div class="auth-footer">
                        <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot your password?</a>
                    </div>
                </form>
            </div>
            
            <!-- Register Form -->
            <div class="auth-form-container" id="register-form">
                <form class="auth-form" id="registerForm" action="<?php echo SITE_URL; ?>/pages/auth/register-process.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="form-group">
                        <label for="register-name">Full Name</label>
                        <input type="text" class="form-control" id="register-name" name="name" placeholder="Enter your full name" required>
                    </div>
                    <div class="form-group">
                        <label for="register-email">Email Address</label>
                        <input type="email" class="form-control" id="register-email" name="email" placeholder="Enter your email" required>
                    </div>
                    <div class="form-group">
                        <label for="register-password">Password</label>
                        <input type="password" class="form-control" id="register-password" name="password" placeholder="Create a password" required>
                    </div>
                    <div class="form-group">
                        <label for="register-confirm">Confirm Password</label>
                        <input type="password" class="form-control" id="register-confirm" name="confirm_password" placeholder="Confirm your password" required>
                    </div>
                    <div class="form-group form-check">
                        <input type="checkbox" class="form-check-input" id="terms-agree" name="terms" required>
                        <label class="form-check-label" for="terms-agree">I agree to the <a href="<?php echo SITE_URL; ?>/pages/terms.php">Terms & Conditions</a></label>
                    </div>
                    <button type="submit" class="btn-auth">Create Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="forgotPasswordModalLabel">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="forgotPasswordForm" action="<?php echo SITE_URL; ?>/pages/auth/forgot-password-process.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="form-group">
                        <label for="forgot-email">Email Address</label>
                        <input type="email" class="form-control" id="forgot-email" name="email" placeholder="Enter your email" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-notification" id="toastNotification">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage"></span>
</div>

<!-- Back to Top -->
<a href="#" class="back-to-top"><i class="fas fa-arrow-up"></i></a>

<?php else: ?>
    <!-- Admin Footer Structure -->
        </div> <!-- .main-content -->
    </div> <!-- #adminDashboard -->
<?php endif; ?>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Global Scripts -->
<script>
    // Auth modal tab switching
    document.addEventListener('DOMContentLoaded', function() {
        const authTabs = document.querySelectorAll('.auth-tab');
        const authForms = document.querySelectorAll('.auth-form-container');
        
        authTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');
                
                // Update active tab
                authTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding form
                authForms.forEach(form => {
                    form.classList.remove('active');
                    if (form.id === `${targetTab}-form`) {
                        form.classList.add('active');
                    }
                });
            });
        });
        
        // Show toast function
        function showToast(message, isSuccess = true) {
            const toastNotification = document.getElementById('toastNotification');
            const toastMessage = document.getElementById('toastMessage');
            
            toastMessage.textContent = message;
            toastNotification.className = isSuccess ? 
                'toast-notification success show' : 
                'toast-notification error show';
            
            setTimeout(() => {
                toastNotification.classList.remove('show');
            }, 3000);
        }
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            const backToTop = document.querySelector('.back-to-top');
            
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            // Show/hide back to top button
            if (window.scrollY > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }
        });
        
        // Back to top functionality
        document.querySelector('.back-to-top').addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Search form submission
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const searchTerm = this.querySelector('input').value;
                if (searchTerm.trim() !== '') {
                    window.location.href = `<?php echo SITE_URL; ?>/pages/shop.php?search=${encodeURIComponent(searchTerm)}`;
                }
            });
        }
        
        // Handle form submissions with AJAX
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const forgotPasswordForm = document.getElementById('forgotPasswordForm');
        
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message, false);
                    }
                })
                .catch(error => {
                    showToast('An error occurred. Please try again.', false);
                });
            });
        }
        
        if (registerForm) {
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                        // Switch to login tab after successful registration
                        document.querySelector('[data-tab="login"]').click();
                        // Clear form
                        this.reset();
                    } else {
                        showToast(data.message, false);
                    }
                })
                .catch(error => {
                    showToast('An error occurred. Please try again.', false);
                });
            });
        }
        
        if (forgotPasswordForm) {
            forgotPasswordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal')).hide();
                        // Clear form
                        this.reset();
                    } else {
                        showToast(data.message, false);
                    }
                })
                .catch(error => {
                    showToast('An error occurred. Please try again.', false);
                });
            });
        }
        
        // Newsletter subscription
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                        this.reset();
                    } else {
                        showToast(data.message, false);
                    }
                })
                .catch(error => {
                    showToast('An error occurred. Please try again.', false);
                });
            });
        }
    });
</script>

<!-- Page-specific JavaScript -->
<?php if (isset($pageScripts)) { echo $pageScripts; } ?>
</body>
</html>