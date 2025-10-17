<?php
// Start session at the VERY TOP of the file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define the root path and site URL for proper includes
$rootPath = $_SERVER['DOCUMENT_ROOT'] . '/homewareontap';
require_once $rootPath . '/includes/config.php';

// Set page title for header
$pageTitle = "About Us - HomewareOnTap";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - HomewareOnTap</title>
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
            background: linear-gradient(rgba(58, 50, 41, 0.7), rgba(58, 50, 41, 0.7)), url('https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
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
        
        /* About Page Specific Styles */
        .about-img {
            position: relative;
        }
        
        .about-img img {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .about-img img:first-child {
            width: 85%;
            margin-bottom: -80px;
            margin-left: auto;
            margin-right: auto;
            display: block;
        }
        
        .about-img img:last-child {
            width: 70%;
            border: 5px solid white;
            position: absolute;
            bottom: 0;
            right: 0;
            z-index: 1;
        }
        
        .feature-img {
            position: relative;
        }
        
        .feature-img img:first-child {
            width: 85%;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .feature-img img:last-child {
            width: 60%;
            border: 5px solid white;
            border-radius: 10px;
            position: absolute;
            bottom: -40px;
            right: 0;
            z-index: 1;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        
        .mission-img img {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            width: 100%;
        }
        
        .team-item {
            transition: all 0.3s;
        }
        
        .team-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .team-social a {
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
        }
        
        .team-social a:hover {
            transform: translateY(-3px);
            background-color: var(--dark);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(166, 123, 91, 0.3);
            color: white;
        }
        
        /* Feature Icons */
        .feature-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            color: white;
            font-size: 1.5rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        /* Check List */
        .check-list {
            list-style: none;
            padding-left: 0;
        }
        
        .check-list li {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .check-list li:before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 0;
            color: var(--primary);
        }
        
        /* Newsletter */
        .newsletter-section {
            background: linear-gradient(rgba(58, 50, 41, 0.9), rgba(58, 50, 41, 0.9)), url('https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=1758&q=80');
            background-size: cover;
            background-position: center;
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
        }
        
        .newsletter-form button {
            padding: 0 25px;
            background: linear-gradient(135deg, var(--primary) 0%, #8B6145 100%);
            color: white;
            border: none;
            border-radius: 0 30px 30px 0;
            font-weight: 600;
            cursor: pointer;
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
        }
        
        @media (max-width: 768px) {
            .page-header { padding: 80px 0; }
            .page-header h1 { font-size: 2rem; }
            .about-img img:first-child, 
            .about-img img:last-child,
            .feature-img img:first-child,
            .feature-img img:last-child {
                position: static;
                width: 100%;
                margin: 0 0 20px 0;
            }
            .newsletter-form { flex-direction: column; }
            .newsletter-form input { border-radius: 30px; margin-bottom: 10px; }
            .newsletter-form button { border-radius: 30px; padding: 12px; }
            .feature-icon {
                margin-right: 0;
                margin-bottom: 1rem;
            }
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
            <h1 class="display-1 text-white animated slideInDown">About Us</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb text-uppercase mb-0">
                    <li class="breadcrumb-item"><a class="text-white" href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                    <li class="breadcrumb-item text-primary active" aria-current="page">About</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- About Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                    <div class="about-img">
                        <img class="img-fluid" src="https://images.unsplash.com/photo-1616137422495-1e9e46e2aa77?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="HomewareOnTap Founder">
                        <img class="img-fluid" src="https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="HomewareOnTap Products">
                    </div>
                </div>
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                    <h4 class="section-title">About Us</h4>
                    <h1 class="display-5 mb-4">Welcome to HomewareOnTap - Your Home Decor Destination</h1>
                    <p>HomewareOnTap was founded by Katlego Matuka with a passion for helping South Africans create beautiful, functional living spaces. We believe that everyone deserves a home that reflects their personality and meets their needs.</p>
                    <p class="mb-4">What started as a small venture on social media platforms like TikTok and WhatsApp has now grown into a trusted homeware destination. Our carefully curated collection includes everything from kitchen essentials to decorative items that add character to your home. We're committed to providing quality products at affordable prices, with excellent customer service.</p>
                    <a class="btn btn-primary py-3 px-5" href="<?php echo SITE_URL; ?>/pages/static/contact.php">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->

    <!-- Feature Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                    <h4 class="section-title">Why Choose HomewareOnTap!</h4>
                    <h1 class="display-5 mb-4">Why South Africans Love Shopping With Us</h1>
                    <p class="mb-4">At HomewareOnTap, we're committed to providing an exceptional shopping experience for all our customers. From our carefully curated products to our reliable delivery service, we prioritize your satisfaction.</p>
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="ms-4">
                                    <h3>Quality Products</h3>
                                    <p class="mb-0">We carefully select each item in our collection for quality, functionality, and design.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon">
                                    <i class="fas fa-tags"></i>
                                </div>
                                <div class="ms-4">
                                    <h3>Affordable Prices</h3>
                                    <p class="mb-0">We believe beautiful homeware should be accessible to everyone at reasonable prices.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="ms-4">
                                    <h3>Reliable Delivery</h3>
                                    <p class="mb-0">We deliver across South Africa with trusted courier partners to ensure your items arrive safely.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="feature-icon">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <div class="ms-4">
                                    <h3>Personal Touch</h3>
                                    <p class="mb-0">As a small business, we value each customer and provide personalized service you won't find elsewhere.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="feature-img">
                        <img class="img-fluid" src="https://images.unsplash.com/photo-1556228453-efd6c1ff04f6?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="HomewareOnTap">
                        <img class="img-fluid" src="https://images.unsplash.com/photo-1616137422495-1e9e46e2aa77?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="HomewareOnTap">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Feature End -->

    <!-- Mission & Vision Start -->
    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                    <div class="mission-img">
                        <img class="img-fluid rounded" src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Our Mission">
                    </div>
                </div>
                <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                    <h4 class="section-title">Our Mission & Vision</h4>
                    <h1 class="display-5 mb-4">Transforming Homes Across South Africa</h1>
                    <div class="mb-4">
                        <h3 class="mb-3">Our Mission</h3>
                        <p>To provide beautiful, functional, and affordable homeware that helps South Africans create spaces they love coming home to. We're committed to making quality home decor accessible to everyone.</p>
                    </div>
                    <div class="mb-4">
                        <h3 class="mb-3">Our Vision</h3>
                        <p>To become South Africa's most trusted homeware destination, known for our curated collections, exceptional customer service, and commitment to helping customers express their personal style through home decor.</p>
                    </div>
                    <div class="mb-4">
                        <h3 class="mb-3">Our Values</h3>
                        <ul class="check-list">
                            <li>Quality in every product we offer</li>
                            <li>Authenticity in our brand voice</li>
                            <li>Accessibility through affordable pricing</li>
                            <li>Reliability in our service and delivery</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Mission & Vision End -->

    <!-- Founder Start -->
    <div class="container-xxl py-5 bg-light">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <h4 class="section-title">Our Founder</h4>
                <h1 class="display-5 mb-4">Meet Katlego Matuka</h1>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-8 col-md-12 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="team-item text-center bg-white rounded overflow-hidden">
                        <div class="rounded-circle overflow-hidden m-4 mx-auto" style="width: 150px; height: 150px;">
                            <img class="img-fluid w-100 h-100" src="<?php echo SITE_URL; ?>/assets/img/Admin.jpg" alt="Katlego Matuka" style="object-fit: cover;">
                        </div>
                        <div class="team-text px-4 py-3">
                            <h5 class="mb-1">Katlego Matuka</h5>
                            <p class="text-primary mb-0">Founder & Creative Director</p>
                            <p class="mt-3">"I started HomewareOnTap with a simple vision: to help South Africans create beautiful, functional living spaces without breaking the bank. What began as a passion project shared on social media has grown into something much bigger, thanks to our amazing customers who continue to inspire us every day."</p>
                            <div class="team-social py-2">
                                <a class="btn btn-square mx-1" href="https://wa.me/27682598679"><i class="fab fa-whatsapp"></i></a>
                                <a class="btn btn-square mx-1" href="https://tiktok.com/@homewareontap"><i class="fab fa-tiktok"></i></a>
                                <a class="btn btn-square mx-1" href="https://instagram.com/homewareontap"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Founder End -->

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