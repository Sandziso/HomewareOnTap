<?php
// Start session at the VERY TOP of the file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the root path and site URL for proper includes
$rootPath = $_SERVER['DOCUMENT_ROOT'] . '/homewareontap';
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/functions.php';

// Set page title for header
$pageTitle = "Returns & Refunds - HomewareOnTap";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returns & Refunds - HomewareOnTap</title>
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- WOW CSS for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary: #A67B5B;
            --secondary: #F2E8D5;
            --light: #F9F5F0;
            --dark: #3A3229;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            color: var(--dark);
            background-color: #f8f9fa;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'League Spartan', sans-serif;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(rgba(58, 50, 41, 0.7), rgba(58, 50, 41, 0.7)), url('https://images.unsplash.com/photo-1524758631624-e2822e304ee6?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            color: white;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        
        .breadcrumb-item a {
            color: white;
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: var(--primary);
        }
        
        /* Section Title */
        .section-title {
            position: relative;
            margin-bottom: 40px;
            text-align: center;
            color: var(--primary);
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--primary);
        }
        
        /* Policy Content */
        .policy-content h3 {
            color: var(--primary);
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .policy-content ul, .policy-content ol {
            padding-left: 20px;
            margin-bottom: 20px;
        }
        
        .policy-content li {
            margin-bottom: 8px;
        }
        
        .policy-content address {
            font-style: normal;
            background: var(--light);
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        /* Sidebar */
        .sidebar-widget {
            background: var(--light);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .sidebar-widget h3 {
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .btn-outline-primary {
            border-color: var(--primary);
            color: var(--primary);
            margin-bottom: 10px;
            width: 100%;
            text-align: left;
            padding: 12px 20px;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(166, 123, 91, 0.3);
            color: white;
            text-decoration: none;
        }
        
        /* Contact Info */
        .contact-info {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .contact-info h4 {
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .page-header h1 { font-size: 2.5rem; }
        }
        
        @media (max-width: 768px) {
            .page-header { padding: 80px 0; }
            .page-header h1 { font-size: 2rem; }
        }
        
        @media (max-width: 576px) {
            .page-header h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include $rootPath . '/includes/header.php'; ?>

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <h1 class="display-1 text-white animated slideInDown">Returns & Refunds</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb text-uppercase mb-0">
                    <li class="breadcrumb-item"><a class="text-white" href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                    <li class="breadcrumb-item text-primary active" aria-current="page">Returns & Refunds</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Returns Policy Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8 wow fadeIn" data-wow-delay="0.1s">
                    <div class="policy-content">
                        <h1 class="display-5 mb-4">Returns & Refunds Policy</h1>
                        <p class="mb-4">Last updated: <?php echo date('F j, Y'); ?></p>
                        
                        <div class="mb-5">
                            <h3>Our Return Policy</h3>
                            <p>At HomewareOnTap, we want you to be completely satisfied with your purchase. If you're not happy with your items, you may return them within 14 days of receipt for a refund or exchange.</p>
                            <p>To be eligible for a return, your item must be unused and in the same condition that you received it. It must also be in the original packaging with all tags attached.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>Non-Returnable Items</h3>
                            <p>Certain types of items cannot be returned, such as:</p>
                            <ul>
                                <li>Personalized or custom-made products</li>
                                <li>Items that have been used or installed</li>
                                <li>Products without their original packaging or tags</li>
                                <li>Gift cards</li>
                                <li>Items marked as final sale</li>
                            </ul>
                        </div>
                        
                        <div class="mb-5">
                            <h3>How to Initiate a Return</h3>
                            <p>To initiate a return, please follow these steps:</p>
                            <ol>
                                <li>Contact us at info@homewareontap.co.za or via WhatsApp at +27 68 259 8679 within 14 days of receiving your order</li>
                                <li>Provide your order number and reason for return</li>
                                <li>We will provide you with a return authorization number and instructions</li>
                                <li>Pack the item securely in its original packaging with all tags attached</li>
                                <li>Include your return authorization number inside the package</li>
                                <li>Ship the package to the address we provide</li>
                            </ol>
                        </div>
                        
                        <div class="mb-5">
                            <h3>Return Shipping</h3>
                            <p>Customers are responsible for return shipping costs unless the return is due to our error (e.g., you received the wrong item or a defective product).</p>
                            <p>We recommend using a trackable shipping service and purchasing shipping insurance. We cannot guarantee that we will receive your returned item without proper tracking.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>Refunds</h3>
                            <p>Once we receive and inspect your return, we will send you an email to notify you that we have received your returned item. We will also notify you of the approval or rejection of your refund.</p>
                            <p>If approved, your refund will be processed, and a credit will automatically be applied to your original method of payment within 3-5 business days.</p>
                            <p>Please note that depending on your bank or payment method, it may take additional time for the refund to appear in your account.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>Exchanges</h3>
                            <p>We only replace items if they are defective or damaged. If you need to exchange an item for the same product, please contact us at info@homewareontap.co.za with your order number and details about the product you would like to exchange.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>Damaged or Defective Items</h3>
                            <p>If you receive a damaged or defective product, please contact us immediately at info@homewareontap.co.za or +27 68 259 8679. We will arrange for a replacement or refund and may ask you to provide photos of the damaged item.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>Late or Missing Refunds</h3>
                            <p>If you haven't received your refund within 10 business days after we've approved it, please first check your bank account again. Then contact your bank as it may take some time before your refund is officially posted.</p>
                            <p>If you've done all of this and you still have not received your refund, please contact us at info@homewareontap.co.za.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>Questions</h3>
                            <p>If you have any questions about our Returns & Refunds Policy, please contact us:</p>
                            <address>
                                <strong>Email:</strong> info@homewareontap.co.za<br>
                                <strong>Phone:</strong> +27 68 259 8679<br>
                                <strong>WhatsApp:</strong> +27 68 259 8679
                            </address>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 wow fadeIn" data-wow-delay="0.5s">
                    <div class="sidebar-widget">
                        <h3>Quick Links</h3>
                        <div class="d-flex flex-column">
                            <a href="<?php echo SITE_URL; ?>/pages/static/terms.php" class="btn btn-outline-primary mb-3">
                                <i class="fas fa-file-contract me-2"></i>Terms & Conditions
                            </a>
                            <a href="<?php echo SITE_URL; ?>/pages/static/privacy.php" class="btn btn-outline-primary mb-3">
                                <i class="fas fa-shield-alt me-2"></i>Privacy Policy
                            </a>
                            <a href="<?php echo SITE_URL; ?>/pages/static/faqs.php" class="btn btn-outline-primary mb-3">
                                <i class="fas fa-question-circle me-2"></i>FAQs
                            </a>
                            <a href="<?php echo SITE_URL; ?>/pages/static/contact.php" class="btn btn-outline-primary">
                                <i class="fas fa-envelope me-2"></i>Contact Us
                            </a>
                        </div>
                    </div>
                    
                    <div class="contact-info">
                        <h4>Need Help With a Return?</h4>
                        <p class="mb-3">Our customer service team is here to help you with any questions about returns or exchanges.</p>
                        <a href="<?php echo SITE_URL; ?>/pages/static/contact.php" class="btn btn-primary">
                            <i class="fas fa-headset me-2"></i>Contact Support
                        </a>
                    </div>
                    
                    <div class="contact-info">
                        <h4>Return Status</h4>
                        <p class="mb-3">Already initiated a return? Check the status of your return or refund.</p>
                        <a href="<?php echo SITE_URL; ?>/pages/user/order-history.php" class="btn btn-outline-primary">
                            <i class="fas fa-truck me-2"></i>Track My Return
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Returns Policy End -->

    <!-- Include Footer -->
    <?php include $rootPath . '/includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- WOW JS for animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script>
    <script>
        new WOW().init();
    </script>
</body>
</html>