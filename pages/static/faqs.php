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
$pageTitle = "FAQs - HomewareOnTap";

// Get database connection
$pdo = getDBConnection();

// Fetch FAQs from database
$faqs = [];
$categories = [];

if ($pdo) {
    try {
        // Get all active FAQs ordered by sort order and creation date
        $stmt = $pdo->prepare("
            SELECT * FROM faqs 
            WHERE status = 1 
            ORDER BY sort_order ASC, created_at DESC
        ");
        $stmt->execute();
        $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Extract unique categories
        $categorySet = [];
        foreach ($faqs as $faq) {
            $categorySet[$faq['category']] = true;
        }
        $categories = array_keys($categorySet);
        
    } catch (PDOException $e) {
        error_log("FAQ database error: " . $e->getMessage());
        // If database fails, use default FAQs
        $faqs = getDefaultFAQs();
        $categories = ['Payment', 'Shipping', 'Returns', 'Discounts'];
    }
} else {
    // If no database connection, use default FAQs
    $faqs = getDefaultFAQs();
    $categories = ['Payment', 'Shipping', 'Returns', 'Discounts'];
}

// Function to provide default FAQs if database is not available
function getDefaultFAQs() {
    return [
        [
            'id' => 1,
            'question' => 'What payment methods do you accept?',
            'answer' => 'We accept credit/debit cards, EFT payments, and cash on delivery for select areas. All online payments are processed securely through our payment partners.',
            'category' => 'Payment',
            'sort_order' => 1
        ],
        [
            'id' => 2,
            'question' => 'How long does delivery take?',
            'answer' => 'Standard delivery takes 3-5 business days in major metropolitan areas. Regional areas may take 5-7 business days. Express delivery options are available at checkout.',
            'category' => 'Shipping',
            'sort_order' => 2
        ],
        [
            'id' => 3,
            'question' => 'What is your return policy?',
            'answer' => 'We offer a 30-day return policy for unused items in original packaging. Please contact our support team to initiate a return.',
            'category' => 'Returns',
            'sort_order' => 3
        ],
        [
            'id' => 4,
            'question' => 'Do you offer student discounts?',
            'answer' => 'Yes! We offer a 10% student discount with valid student ID verification. Contact us for more information.',
            'category' => 'Discounts',
            'sort_order' => 4
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs - HomewareOnTap</title>
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
        
        /* FAQ Specific Styles */
        .search-box {
            position: relative;
            max-width: 500px;
            margin: 0 auto 40px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 20px;
            border: 1px solid #e9ecef;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(166, 123, 91, 0.1);
            outline: none;
        }
        
        .search-box button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--dark);
            font-size: 18px;
        }
        
        .category-filter {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }
        
        .category-btn {
            margin: 5px;
            padding: 10px 20px;
            border: 1px solid #e9ecef;
            background: white;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .category-btn:hover, .category-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(166, 123, 91, 0.2);
        }
        
        .faq-item {
            margin-bottom: 20px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            background: white;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .faq-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .faq-question {
            padding: 20px;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            width: 100%;
            text-align: left;
            transition: background 0.3s;
        }
        
        .faq-question:hover {
            background: var(--light);
        }
        
        .faq-question h5 {
            margin: 0;
            color: var(--dark);
        }
        
        .faq-question i {
            color: var(--primary);
            transition: transform 0.3s;
            font-size: 18px;
        }
        
        .faq-question[aria-expanded="true"] i {
            transform: rotate(180deg);
        }
        
        .faq-answer {
            background: var(--light);
            padding: 0 20px 20px;
            line-height: 1.7;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            background: var(--light);
            border-radius: 12px;
            margin: 20px 0;
            display: none;
        }
        
        .contact-prompt {
            background: var(--light);
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            margin-top: 60px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
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
        
        /* Policy Links Section */
        .policy-links {
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            margin-top: 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .policy-links h3 {
            color: var(--primary);
            margin-bottom: 30px;
        }
        
        .policy-btn {
            display: inline-block;
            margin: 10px;
            padding: 12px 25px;
            background: white;
            border: 2px solid var(--primary);
            border-radius: 30px;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .policy-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(166, 123, 91, 0.3);
            text-decoration: none;
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
            .newsletter-form { flex-direction: column; }
            .newsletter-form input { border-radius: 30px; margin-bottom: 10px; }
            .newsletter-form button { border-radius: 30px; padding: 12px; }
            .category-filter { flex-direction: column; align-items: center; }
            .category-btn { margin: 5px 0; width: 200px; }
            .policy-btn { display: block; margin: 10px auto; width: 80%; }
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
            <h1 class="display-1 text-white animated slideInDown">FAQs</h1>
            <nav aria-label="breadcrumb animated slideInDown">
                <ol class="breadcrumb text-uppercase mb-0">
                    <li class="breadcrumb-item"><a class="text-white" href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                    <li class="breadcrumb-item text-primary active" aria-current="page">FAQs</li>
                </ol>
            </nav>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- FAQs Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Common Questions</h2>
                <p class="mb-4">Can't find what you're looking for? <a href="<?php echo SITE_URL; ?>/pages/static/contact.php">Contact us directly</a> for personalized assistance.</p>
            </div>
            
            <div class="search-box">
                <input type="text" class="form-control" placeholder="Search FAQs..." id="faq-search">
                <button type="button" id="search-btn"><i class="fas fa-search"></i></button>
            </div>
            
            <div class="category-filter">
                <button class="category-btn active" data-category="all">All Questions</button>
                <?php foreach ($categories as $category): ?>
                    <button class="category-btn" data-category="<?php echo htmlspecialchars(strtolower($category)); ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            
            <div class="no-results" id="no-results">
                <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                <h4 class="text-muted">No questions found</h4>
                <p class="text-muted">Try different keywords or browse by category</p>
            </div>
            
            <div class="accordion" id="faqAccordion">
                <?php if (empty($faqs)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h4>No FAQs Available</h4>
                        <p>We're currently updating our frequently asked questions. Please check back soon or contact us directly for assistance.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($faqs as $index => $faq): ?>
                        <div class="faq-item" data-category="<?php echo htmlspecialchars(strtolower($faq['category'])); ?>">
                            <button class="faq-question <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $faq['id'] ?? $index; ?>">
                                <h5><?php echo htmlspecialchars($faq['question']); ?></h5>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div id="collapse<?php echo $faq['id'] ?? $index; ?>" class="collapse <?php echo $index === 0 ? 'show' : ''; ?>" data-bs-parent="#faqAccordion">
                                <div class="faq-answer">
                                    <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Policy Links Section -->
            <div class="policy-links">
                <h3>More Information</h3>
                <a href="<?php echo SITE_URL; ?>/pages/static/privacy.php" class="policy-btn">
                    <i class="fas fa-shield-alt me-2"></i>Privacy Policy
                </a>
                <a href="<?php echo SITE_URL; ?>/pages/static/returns.php" class="policy-btn">
                    <i class="fas fa-undo me-2"></i>Returns & Refunds
                </a>
                <a href="<?php echo SITE_URL; ?>/pages/static/terms.php" class="policy-btn">
                    <i class="fas fa-file-contract me-2"></i>Terms & Conditions
                </a>
            </div>
            
            <div class="contact-prompt">
                <h3 class="mb-3">Still have questions?</h3>
                <p class="mb-4">We're here to help! Get in touch with our friendly customer service team.</p>
                <a href="<?php echo SITE_URL; ?>/pages/static/contact.php" class="btn-primary">Contact Us</a>
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
        
        document.addEventListener('DOMContentLoaded', function() {
            // FAQ Search Functionality
            const faqSearch = document.getElementById('faq-search');
            const searchButton = document.getElementById('search-btn');
            const noResults = document.getElementById('no-results');
            
            function performSearch() {
                const searchTerm = faqSearch.value.toLowerCase().trim();
                const faqItems = document.querySelectorAll('.faq-item');
                let foundResults = false;
                
                faqItems.forEach(item => {
                    const question = item.querySelector('.faq-question h5').textContent.toLowerCase();
                    const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                    
                    if (searchTerm === '' || question.includes(searchTerm) || answer.includes(searchTerm)) {
                        item.style.display = 'block';
                        foundResults = true;
                        
                        // Open the matching item if search term matches
                        if (searchTerm !== '' && (question.includes(searchTerm) || answer.includes(searchTerm))) {
                            const collapseId = item.querySelector('.faq-question').getAttribute('data-bs-target');
                            const collapseElement = document.querySelector(collapseId);
                            if (collapseElement) {
                                const bsCollapse = new bootstrap.Collapse(collapseElement, { toggle: true });
                            }
                        }
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                noResults.style.display = (!foundResults && searchTerm.length > 0) ? 'block' : 'none';
            }
            
            faqSearch.addEventListener('keyup', performSearch);
            searchButton.addEventListener('click', performSearch);
            
            // Category Filtering
            const categoryButtons = document.querySelectorAll('.category-btn');
            categoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    const category = this.getAttribute('data-category');
                    const faqItems = document.querySelectorAll('.faq-item');
                    let foundCategoryResults = false;
                    
                    faqItems.forEach(item => {
                        if (category === 'all' || item.getAttribute('data-category') === category) {
                            item.style.display = 'block';
                            foundCategoryResults = true;
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    noResults.style.display = (!foundCategoryResults) ? 'block' : 'none';
                    
                    // Clear search
                    faqSearch.value = '';
                    performSearch();
                });
            });
            
            // Newsletter form
            document.getElementById('newsletterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[type="email"]').value;
                
                // Simple validation
                if (!email || !email.includes('@')) {
                    alert('Please enter a valid email address.');
                    return;
                }
                
                // Here you would typically send the email to your server
                // For now, we'll just show a success message
                this.querySelector('input[type="email"]').value = '';
                alert(`Thank you for subscribing with ${email}! We'll keep you updated with our latest offers.`);
            });
        });
    </script>
</body>
</html>