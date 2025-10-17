<?php
// Start session and handle includes with proper error handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define root path and site URL for proper includes
$rootPath = $_SERVER['DOCUMENT_ROOT'] . '/homewareontap';
$siteUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/homewareontap';

// Try to include config files with fallback
$configIncluded = false;
$headerIncluded = false;

// Try multiple possible paths for config
$configPaths = [
    $rootPath . '/includes/config.php',
    $rootPath . '/config.php',
    '../includes/config.php',
    '../../includes/config.php'
];

foreach ($configPaths as $configPath) {
    if (file_exists($configPath)) {
        include $configPath;
        $configIncluded = true;
        break;
    }
}

// If config not found, define fallback constants
if (!$configIncluded) {
    define('BASE_URL', $siteUrl);
    define('SITE_URL', $siteUrl);
    error_log("Config file not found, using fallback values");
}

// Try multiple possible paths for header
$headerPaths = [
    $rootPath . '/includes/header.php',
    $rootPath . '/header.php',
    '../includes/header.php',
    '../../includes/header.php'
];

foreach ($headerPaths as $headerPath) {
    if (file_exists($headerPath)) {
        $headerIncluded = true;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - HomewareOnTap</title>
    
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
        
        /* Section Styling */
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
        
        .policy-content h4 {
            color: var(--dark);
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        .policy-content ul {
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
        
        /* Quick Links */
        .quick-links {
            background: var(--light);
            border-radius: 12px;
            padding: 30px;
            position: sticky;
            top: 20px;
        }
        
        .quick-links h3 {
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .policy-link {
            display: block;
            padding: 12px 20px;
            margin-bottom: 10px;
            background: white;
            border: 2px solid var(--primary);
            border-radius: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .policy-link:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(166, 123, 91, 0.3);
            text-decoration: none;
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
        
        /* Animations */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .page-header h1 { font-size: 2.5rem; }
            .quick-links {
                position: static;
                margin-top: 40px;
            }
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
    <!-- Simple Header if include fails -->
    <?php if (!$headerIncluded): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $siteUrl; ?>">HomewareOnTap</a>
        </div>
    </nav>
    <?php else: ?>
        <?php include $headerPath; ?>
    <?php endif; ?>

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <h1 class="display-1 text-white animated slideInDown">Privacy Policy</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb text-uppercase mb-0">
                    <li class="breadcrumb-item"><a class="text-white" href="<?php echo $siteUrl; ?>">Home</a></li>
                    <li class="breadcrumb-item text-primary active" aria-current="page">Privacy Policy</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Privacy Policy Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8 wow fadeIn" data-wow-delay="0.1s">
                    <div class="policy-content">
                        <h1 class="display-5 mb-4">Privacy Policy</h1>
                        <p class="mb-4">Last updated: <?php echo date('F j, Y'); ?></p>
                        
                        <div class="mb-5">
                            <h3>1. Introduction</h3>
                            <p>Welcome to HomewareOnTap. We are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or make a purchase from us.</p>
                            <p>By using our website, you consent to the data practices described in this Privacy Policy. If you do not agree with the data practices described, you should not use our website.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>2. Information We Collect</h3>
                            <h4>Personal Information</h4>
                            <p>When you make a purchase or attempt to make a purchase through our site, we collect the following personal information:</p>
                            <ul>
                                <li>Name</li>
                                <li>Email address</li>
                                <li>Shipping and billing addresses</li>
                                <li>Payment information (processed securely through our payment providers)</li>
                                <li>Phone number</li>
                            </ul>
                            
                            <h4>Automatically Collected Information</h4>
                            <p>When you visit our site, we automatically collect certain information about your device, including:</p>
                            <ul>
                                <li>IP address</li>
                                <li>Browser type</li>
                                <li>Operating system</li>
                                <li>Referring URLs</li>
                                <li>Pages viewed and time spent on pages</li>
                                <li>Clickstream data</li>
                            </ul>
                        </div>
                        
                        <div class="mb-5">
                            <h3>3. How We Use Your Information</h3>
                            <p>We use the information we collect to:</p>
                            <ul>
                                <li>Process your orders and send order confirmations</li>
                                <li>Communicate with you about products, services, promotions, and events</li>
                                <li>Provide customer support and respond to your inquiries</li>
                                <li>Improve and optimize our website and your shopping experience</li>
                                <li>Detect and prevent fraud and abuse</li>
                                <li>Comply with legal obligations</li>
                            </ul>
                        </div>
                        
                        <div class="mb-5">
                            <h3>4. Sharing Your Information</h3>
                            <p>We share your personal information with third parties to help us use your information, as described above. For example:</p>
                            <ul>
                                <li>We use Yoco and PayFast to process payments</li>
                                <li>We use shipping providers to deliver your orders</li>
                                <li>We may use analytics services to understand how our customers use the site</li>
                            </ul>
                            <p>We require all third parties to respect the security of your personal information and to treat it in accordance with the law.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>5. Data Retention</h3>
                            <p>We will retain your personal information only for as long as necessary to fulfill the purposes we collected it for, including to satisfy any legal, accounting, or reporting requirements.</p>
                            <p>For tax purposes, we are required to keep basic information about our customers (including Contact, Identity, Financial and Transaction Data) for five years after they stop being customers.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>6. Your Rights</h3>
                            <p>If you are a South African resident, you have the right to:</p>
                            <ul>
                                <li>Access the personal information we hold about you</li>
                                <li>Request that we correct any inaccurate personal information</li>
                                <li>Request deletion of your personal information</li>
                                <li>Object to processing of your personal information</li>
                                <li>Request transfer of your personal information</li>
                                <li>Withdraw consent at any time where we rely on consent to process your personal information</li>
                            </ul>
                            <p>To exercise any of these rights, please contact us at info@homewareontap.co.za.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>7. Cookies</h3>
                            <p>Our website uses cookies to distinguish you from other users of our website. This helps us provide you with a good experience when you browse our website and also allows us to improve our site.</p>
                            <p>You can set your browser to refuse all or some browser cookies, or to alert you when websites set or access cookies. If you disable or refuse cookies, please note that some parts of this website may become inaccessible or not function properly.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>8. Data Security</h3>
                            <p>We have implemented appropriate security measures to prevent your personal information from being accidentally lost, used or accessed in an unauthorized way, altered or disclosed.</p>
                            <p>All information you provide to us is stored on secure servers. Any payment transactions will be encrypted using SSL technology.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>9. Changes to This Privacy Policy</h3>
                            <p>We may update this privacy policy from time to time to reflect changes to our practices or for other operational, legal or regulatory reasons. The updated version will be indicated by an updated "Last updated" date and the updated version will be effective as soon as it is accessible.</p>
                        </div>
                        
                        <div class="mb-5">
                            <h3>10. Contact Us</h3>
                            <p>For more information about our privacy practices, if you have questions, or if you would like to make a complaint, please contact us by email at info@homewareontap.co.za or by mail using the details provided below:</p>
                            <address>
                                HomewareOnTap<br>
                                Johannesburg, South Africa<br>
                                Email: info@homewareontap.co.za<br>
                                Phone: +27 68 259 8679
                            </address>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 wow fadeIn" data-wow-delay="0.5s">
                    <div class="quick-links">
                        <h3 class="mb-4">Quick Links</h3>
                        <div class="d-flex flex-column">
                            <a href="<?php echo $siteUrl; ?>/pages/terms.php" class="policy-link">Terms & Conditions</a>
                            <a href="<?php echo $siteUrl; ?>/pages/returns.php" class="policy-link">Returns Policy</a>
                            <a href="<?php echo $siteUrl; ?>/pages/faqs.php" class="policy-link">FAQs</a>
                            <a href="<?php echo $siteUrl; ?>/pages/contact.php" class="policy-link">Contact Us</a>
                        </div>
                        
                        <div class="mt-5">
                            <h4 class="mb-3">Have Questions?</h4>
                            <p>If you have any questions about our privacy practices or how we handle your data, don't hesitate to reach out.</p>
                            <a href="<?php echo $siteUrl; ?>/pages/contact.php" class="btn btn-primary w-100">Get In Touch</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Privacy Policy End -->

    <!-- Simple Footer if include fails -->
    <?php 
    $footerIncluded = false;
    $footerPaths = [
        $rootPath . '/includes/footer.php',
        $rootPath . '/footer.php',
        '../includes/footer.php',
        '../../includes/footer.php'
    ];
    
    foreach ($footerPaths as $footerPath) {
        if (file_exists($footerPath)) {
            $footerIncluded = true;
            break;
        }
    }
    ?>
    
    <?php if (!$footerIncluded): ?>
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> HomewareOnTap. All rights reserved.</p>
        </div>
    </footer>
    <?php else: ?>
        <?php include $footerPath; ?>
    <?php endif; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- WOW JS for animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script>
    <script>
        new WOW().init();
    </script>
</body>
</html>