<?php
// Include configuration and header
include '../includes/config.php';
include '../includes/header.php';
?>

<!-- Page Header Start -->
<div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
    <div class="container py-5">
        <h1 class="display-1 text-white animated slideInDown">Returns & Refunds</h1>
        <nav aria-label="breadcrumb animated slideInDown">
            <ol class="breadcrumb text-uppercase mb-0">
                <li class="breadcrumb-item"><a class="text-white" href="<?php echo BASE_URL; ?>">Home</a></li>
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
                <h1 class="display-5 mb-4">Returns & Refunds Policy</h1>
                <p class="mb-4">Last updated: <?php echo date('F j, Y'); ?></p>
                
                <div class="mb-5">
                    <h3 class="mb-3">Our Return Policy</h3>
                    <p>At HomewareOnTap, we want you to be completely satisfied with your purchase. If you're not happy with your items, you may return them within 14 days of receipt for a refund or exchange.</p>
                    <p>To be eligible for a return, your item must be unused and in the same condition that you received it. It must also be in the original packaging with all tags attached.</p>
                </div>
                
                <div class="mb-5">
                    <h3 class="mb-3">Non-Returnable Items</h3>
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
                    <h3 class="mb-3">How to Initiate a Return</h3>
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
                    <h3 class="mb-3">Return Shipping</h3>
                    <p>Customers are responsible for return shipping costs unless the return is due to our error (e.g., you received the wrong item or a defective product).</p>
                    <p>We recommend using a trackable shipping service and purchasing shipping insurance. We cannot guarantee that we will receive your returned item without proper tracking.</p>
                </div>
                
                <div class="mb-5">
                    <h3 class="mb-3">Refunds</h3>
                    <p>Once we receive and inspect your return, we will send you an email to notify you that we have received your returned item. We will also notify you of the approval or rejection of your refund.</p>
                    <p>If approved, your refund will be processed, and a credit will automatically be applied to your original method of payment within 3-5 business days.</p>
                    <p>Please note that depending on your bank or payment method, it may take additional time for the refund to appear in your account.</p>
                </div>
                
                <div class="mb-5">
                    <h3 class="mb-3">Exchanges</h3>
                    <p>We only replace items if they are defective or damaged. If you need to exchange an item for the same product, please contact us at info@homewareontap.co.za with your order number and details about the product you would like to exchange.</p>
                </div>
                
                <div class="mb-5">
                    <h3 class="mb-3">Damaged or Defective Items</h3>
                    <p>If you receive a damaged or defective product, please contact us immediately at info@homewareontap.co.za or +27 68 259 8679. We will arrange for a replacement or refund and may ask you to provide photos of the damaged item.</p>
                </div>
                
                <div class="mb-5">
                    <h3 class="mb-3">Late or Missing Refunds</h3>
                    <p>If you haven't received your refund within 10 business days after we've approved it, please first check your bank account again. Then contact your bank as it may take some time before your refund is officially posted.</p>
                    <p>If you've done all of this and you still have not received your refund, please contact us at info@homewareontap.co.za.</p>
                </div>
                
                <div class="mb-5">
                    <h3 class="mb-3">Questions</h3>
                    <p>If you have any questions about our Returns & Refunds Policy, please contact us:</p>
                    <address>
                        Email: info@homewareontap.co.za<br>
                        Phone: +27 68 259 8679<br>
                        WhatsApp: +27 68 259 8679
                    </address>
                </div>
            </div>
            
            <div class="col-lg-4 wow fadeIn" data-wow-delay="0.5s">
                <div class="bg-light rounded p-5">
                    <h3 class="mb-4">Quick Links</h3>
                    <div class="d-flex flex-column">
                        <a href="<?php echo BASE_URL; ?>pages/terms.php" class="btn btn-outline-primary mb-3">Terms & Conditions</a>
                        <a href="<?php echo BASE_URL; ?>pages/privacy.php" class="btn btn-outline-primary mb-3">Privacy Policy</a>
                        <a href="<?php echo BASE_URL; ?>pages/faqs.php" class="btn btn-outline-primary mb-3">FAQs</a>
                        <a href="<?php echo BASE_URL; ?>pages/contact.php" class="btn btn-outline-primary">Contact Us</a>
                    </div>
                    
                    <div class="mt-5">
                        <h4 class="mb-3">Need Help With a Return?</h4>
                        <p>Our customer service team is here to help you with any questions about returns or exchanges.</p>
                        <a href="<?php echo BASE_URL; ?>pages/contact.php" class="btn btn-primary">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Returns Policy End -->

<?php
// Include footer
include '../includes/footer.php';
?>