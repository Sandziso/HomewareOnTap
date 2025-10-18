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
$pageTitle = "Terms & Conditions - HomewareOnTap";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - HomewareOnTap</title>
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
            background: linear-gradient(rgba(58, 50, 41, 0.7), rgba(58, 50, 41, 0.7)), url('https://images.unsplash.com/photo-1589829545856-d10d557cf95f?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
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
        
        /* Terms Specific Styles */
        .terms-content {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .terms-content h3 {
            color: var(--primary);
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light);
        }
        
        .terms-content h3:first-child {
            margin-top: 0;
        }
        
        .terms-content ul {
            padding-left: 20px;
            margin-bottom: 20px;
        }
        
        .terms-content li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        .quick-links {
            background: var(--light);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 20px;
        }
        
        .quick-links h3 {
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .policy-btn {
            display: block;
            margin: 10px 0;
            padding: 12px 20px;
            background: white;
            border: 2px solid var(--primary);
            border-radius: 30px;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            text-align: center;
        }
        
        .policy-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(166, 123, 91, 0.3);
            text-decoration: none;
        }
        
        .contact-prompt {
            background: var(--light);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-top: 30px;
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
        
        /* Table of Contents */
        .toc {
            background: var(--light);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .toc h4 {
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .toc ul {
            list-style: none;
            padding-left: 0;
        }
        
        .toc li {
            margin-bottom: 8px;
        }
        
        .toc a {
            color: var(--dark);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .toc a:hover {
            color: var(--primary);
            text-decoration: underline;
        }
        
        /* Last Updated */
        .last-updated {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .page-header h1 { font-size: 2.5rem; }
        }
        
        @media (max-width: 768px) {
            .page-header { padding: 80px 0; }
            .page-header h1 { font-size: 2rem; }
            .terms-content { padding: 25px; }
            .quick-links { position: static; margin-bottom: 30px; }
        }
        
        @media (max-width: 576px) {
            .page-header h1 { font-size: 1.8rem; }
            .terms-content { padding: 20px; }
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include $rootPath . '/includes/header.php'; ?>

    <!-- Page Header Start -->
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <h1 class="display-1 text-white animated slideInDown">Terms & Conditions</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb text-uppercase mb-0">
                    <li class="breadcrumb-item"><a class="text-white" href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                    <li class="breadcrumb-item text-primary active" aria-current="page">Terms & Conditions</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Terms & Conditions Content -->
    <section class="py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-8">
                    <div class="terms-content wow fadeIn" data-wow-delay="0.1s">
                        <div class="last-updated">Last updated: <?php echo date('F j, Y'); ?></div>
                        
                        <h1 class="display-5 mb-4">Terms & Conditions</h1>
                        <p class="lead mb-5">Please read these terms and conditions carefully before using our website and services.</p>
                        
                        <!-- Table of Contents -->
                        <div class="toc">
                            <h4><i class="fas fa-list me-2"></i>Table of Contents</h4>
                            <ul>
                                <li><a href="#introduction">1. Introduction</a></li>
                                <li><a href="#definitions">2. Definitions</a></li>
                                <li><a href="#account">3. Account Registration</a></li>
                                <li><a href="#products">4. Product Information</a></li>
                                <li><a href="#pricing">5. Pricing and Payment</a></li>
                                <li><a href="#orders">6. Order Acceptance</a></li>
                                <li><a href="#delivery">7. Delivery</a></li>
                                <li><a href="#returns">8. Returns and Refunds</a></li>
                                <li><a href="#intellectual">9. Intellectual Property</a></li>
                                <li><a href="#conduct">10. User Conduct</a></li>
                                <li><a href="#liability">11. Limitation of Liability</a></li>
                                <li><a href="#indemnification">12. Indemnification</a></li>
                                <li><a href="#changes">13. Changes to Terms</a></li>
                                <li><a href="#governing">14. Governing Law</a></li>
                                <li><a href="#contact">15. Contact Information</a></li>
                            </ul>
                        </div>
                        
                        <div id="introduction">
                            <h3>1. Introduction</h3>
                            <p>Welcome to HomewareOnTap. These Terms and Conditions govern your use of our website and services. By accessing or using our website, you agree to be bound by these Terms and Conditions and our Privacy Policy.</p>
                            <p>HomewareOnTap is a South African-based homeware retailer operating through our website and social media platforms.</p>
                        </div>
                        
                        <div id="definitions">
                            <h3>2. Definitions</h3>
                            <p>In these Terms and Conditions:</p>
                            <ul>
                                <li><strong>"We", "us", "our"</strong> refers to HomewareOnTap</li>
                                <li><strong>"You", "your"</strong> refers to the customer accessing our website and services</li>
                                <li><strong>"Products"</strong> refers to the homeware items offered for sale on our website</li>
                                <li><strong>"Website"</strong> refers to homewareontap.co.za and associated platforms</li>
                                <li><strong>"Services"</strong> refers to all services provided by HomewareOnTap</li>
                            </ul>
                        </div>
                        
                        <div id="account">
                            <h3>3. Account Registration</h3>
                            <p>To place orders on our website, you may need to create an account. You are responsible for:</p>
                            <ul>
                                <li>Maintaining the confidentiality of your account credentials</li>
                                <li>All activities that occur under your account</li>
                                <li>Providing accurate and complete information during registration</li>
                                <li>Updating your information to keep it current and accurate</li>
                            </ul>
                            <p>We reserve the right to suspend or terminate accounts that provide false information or are used for fraudulent activities.</p>
                        </div>
                        
                        <div id="products">
                            <h3>4. Product Information</h3>
                            <p>We strive to display accurate product information, including descriptions, prices, and images. However, we cannot guarantee that all information is entirely accurate, complete, or current.</p>
                            <p>All products are subject to availability. We reserve the right to:</p>
                            <ul>
                                <li>Limit quantities of products available for purchase</li>
                                <li>Discontinue products at any time</li>
                                <li>Correct any errors in product information</li>
                                <li>Refuse or cancel orders for products with incorrect pricing</li>
                            </ul>
                        </div>
                        
                        <div id="pricing">
                            <h3>5. Pricing and Payment</h3>
                            <p>All prices are in South African Rand (ZAR) and include Value-Added Tax (VAT) where applicable.</p>
                            <p>We reserve the right to change prices at any time without notice. However, we will honor the price at the time of order confirmation.</p>
                            <p>We accept various payment methods including:</p>
                            <ul>
                                <li>Credit/debit cards (Visa, MasterCard)</li>
                                <li>Electronic Funds Transfer (EFT)</li>
                                <li>Secure payment gateways (Yoco, PayFast)</li>
                                <li>Cash on delivery for select areas (availability may vary)</li>
                            </ul>
                            <p>All payments are processed securely through our payment partners.</p>
                        </div>
                        
                        <div id="orders">
                            <h3>6. Order Acceptance</h3>
                            <p>Your order constitutes an offer to purchase our products. We reserve the right to accept or decline your order for any reason, including:</p>
                            <ul>
                                <li>Product availability</li>
                                <li>Errors in product or pricing information</li>
                                <li>Suspected fraudulent activity</li>
                                <li>Inability to process payment</li>
                            </ul>
                            <p>Order acceptance occurs when we send an order confirmation email. This confirmation constitutes our acceptance of your order.</p>
                        </div>
                        
                        <div id="delivery">
                            <h3>7. Delivery</h3>
                            <p>We deliver throughout South Africa using trusted courier services. Delivery times may vary depending on your location.</p>
                            <p><strong>Delivery Areas:</strong> We deliver to all major metropolitan areas and regional centers across South Africa. Remote areas may have extended delivery times.</p>
                            <p><strong>Delivery Times:</strong> Standard delivery takes 3-5 business days in major metropolitan areas. Regional areas may take 5-7 business days.</p>
                            <p><strong>Delivery Costs:</strong> Shipping costs are calculated at checkout based on your location and order value. We offer free shipping on orders over R250.</p>
                            <p>Risk of loss and damage to products passes to you upon delivery. You are responsible for inspecting products upon delivery and reporting any issues within 24 hours.</p>
                        </div>
                        
                        <div id="returns">
                            <h3>8. Returns and Refunds</h3>
                            <p>Please refer to our separate <a href="<?php echo SITE_URL; ?>/pages/static/returns.php">Returns & Refunds Policy</a> for detailed information about returning products and obtaining refunds.</p>
                            <p>Key points include:</p>
                            <ul>
                                <li>30-day return policy for unused items in original packaging</li>
                                <li>Proof of purchase required for all returns</li>
                                <li>Return shipping costs may apply</li>
                                <li>Refunds processed within 7-10 business days</li>
                            </ul>
                        </div>
                        
                        <div id="intellectual">
                            <h3>9. Intellectual Property</h3>
                            <p>All content on this website, including text, graphics, logos, images, and software, is the property of HomewareOnTap or our content suppliers and is protected by South African and international copyright laws.</p>
                            <p>You may not reproduce, distribute, or create derivative works from any content without our express written permission.</p>
                            <p>The HomewareOnTap name, logo, and all related names, logos, product and service names, designs, and slogans are trademarks of HomewareOnTap or its affiliates or licensors.</p>
                        </div>
                        
                        <div id="conduct">
                            <h3>10. User Conduct</h3>
                            <p>You agree not to:</p>
                            <ul>
                                <li>Use our website for any unlawful purpose</li>
                                <li>Post or transmit any harmful, offensive, or inappropriate content</li>
                                <li>Attempt to gain unauthorized access to our systems</li>
                                <li>Interfere with the proper functioning of our website</li>
                                <li>Use any automated systems to extract data from our website</li>
                                <li>Impersonate any person or entity</li>
                                <li>Engage in any conduct that restricts or inhibits anyone's use of the website</li>
                            </ul>
                        </div>
                        
                        <div id="liability">
                            <h3>11. Limitation of Liability</h3>
                            <p>HomewareOnTap shall not be liable for any indirect, incidental, special, or consequential damages arising from your use of our website or products.</p>
                            <p>Our total liability for any claim related to your use of our website or products shall not exceed the purchase price of the products in question.</p>
                            <p>We are not liable for:</p>
                            <ul>
                                <li>Damages resulting from improper use of products</li>
                                <li>Delays or failures in delivery beyond our control</li>
                                <li>Any third-party services or websites linked from our site</li>
                            </ul>
                        </div>
                        
                        <div id="indemnification">
                            <h3>12. Indemnification</h3>
                            <p>You agree to indemnify and hold harmless HomewareOnTap, its owners, employees, and affiliates from any claims, damages, or expenses arising from your use of our website or violation of these Terms and Conditions.</p>
                            <p>This includes any third-party claims resulting from:</p>
                            <ul>
                                <li>Your use of the website</li>
                                <li>Your violation of these Terms</li>
                                <li>Your violation of any rights of another</li>
                                <li>Any content you submit to the website</li>
                            </ul>
                        </div>
                        
                        <div id="changes">
                            <h3>13. Changes to Terms</h3>
                            <p>We reserve the right to modify these Terms and Conditions at any time. Changes will be effective immediately upon posting to our website.</p>
                            <p>We will make reasonable efforts to notify users of significant changes, but it is your responsibility to review these Terms periodically.</p>
                            <p>Your continued use of our website after changes constitutes acceptance of the modified terms.</p>
                        </div>
                        
                        <div id="governing">
                            <h3>14. Governing Law</h3>
                            <p>These Terms and Conditions are governed by the laws of South Africa. Any disputes shall be subject to the exclusive jurisdiction of the courts of South Africa.</p>
                            <p>If any provision of these Terms is found to be invalid or unenforceable, the remaining provisions will remain in full force and effect.</p>
                        </div>
                        
                        <div id="contact">
                            <h3>15. Contact Information</h3>
                            <p>If you have any questions about these Terms and Conditions, please contact us:</p>
                            <div class="contact-info">
                                <p><i class="fas fa-envelope me-2"></i>Email: info@homewareontap.co.za</p>
                                <p><i class="fas fa-phone me-2"></i>Phone: +27 68 259 8679</p>
                                <p><i class="fab fa-whatsapp me-2"></i>WhatsApp: +27 68 259 8679</p>
                                <p><i class="fas fa-clock me-2"></i>Business Hours: Monday-Friday, 9:00-17:00</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-prompt wow fadeIn" data-wow-delay="0.3s">
                        <h3 class="mb-3">Questions About Our Terms?</h3>
                        <p class="mb-4">If you have any questions about our Terms and Conditions, please don't hesitate to contact our team.</p>
                        <a href="<?php echo SITE_URL; ?>/pages/static/contact.php" class="btn-primary">Get In Touch</a>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="quick-links wow fadeIn" data-wow-delay="0.5s">
                        <h3><i class="fas fa-link me-2"></i>Quick Links</h3>
                        <a href="<?php echo SITE_URL; ?>/pages/static/privacy.php" class="policy-btn">
                            <i class="fas fa-shield-alt me-2"></i>Privacy Policy
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/static/returns.php" class="policy-btn">
                            <i class="fas fa-undo me-2"></i>Returns & Refunds
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/static/faqs.php" class="policy-btn">
                            <i class="fas fa-question-circle me-2"></i>FAQs
                        </a>
                        <a href="<?php echo SITE_URL; ?>/pages/static/contact.php" class="policy-btn">
                            <i class="fas fa-envelope me-2"></i>Contact Us
                        </a>
                        
                        <div class="mt-5">
                            <h4 class="mb-3">Need Help?</h4>
                            <p>Our customer service team is here to assist you with any questions or concerns.</p>
                            <div class="mt-3">
                                <a href="https://wa.me/27682598679" class="btn btn-success w-100 mb-2">
                                    <i class="fab fa-whatsapp me-2"></i>WhatsApp Us
                                </a>
                                <a href="tel:+27682598679" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-phone me-2"></i>Call Us
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Include Footer -->
    <?php include $rootPath . '/includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- WOW JS for animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script>
    <script>
        new WOW().init();
        
        // Smooth scrolling for table of contents links
        document.addEventListener('DOMContentLoaded', function() {
            const tocLinks = document.querySelectorAll('.toc a');
            
            tocLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>