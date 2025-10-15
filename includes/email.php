<?php
// File: includes/email.php

// Define the path to PHPMailer files
$phpmailer_path = __DIR__ . '/../lib/phpmailer/';

// Check if files exist before requiring them
$phpmailer_file = $phpmailer_path . 'PHPMailer.php';
$smtp_file = $phpmailer_path . 'SMTP.php';
$exception_file = $phpmailer_path . 'Exception.php';

if (!file_exists($phpmailer_file)) {
    error_log("PHPMailer file not found: " . $phpmailer_file);
    throw new Exception("PHPMailer library not found. Please check the installation.");
}

if (!file_exists($smtp_file)) {
    error_log("SMTP file not found: " . $smtp_file);
    throw new Exception("PHPMailer SMTP library not found. Please check the installation.");
}

if (!file_exists($exception_file)) {
    error_log("Exception file not found: " . $exception_file);
    throw new Exception("PHPMailer Exception library not found. Please check the installation.");
}

require_once $phpmailer_file;
require_once $smtp_file;
require_once $exception_file;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $siteName;
    private $siteUrl;
    
    public function __construct() {
        $this->siteName = defined('SITE_NAME') ? SITE_NAME : 'HomewareOnTap';
        $this->siteUrl = defined('SITE_URL') ? SITE_URL : 'http://localhost/homewareontap';
        
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }
    
    private function configureMailer() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            
            // MailHog configuration
            $this->mailer->SMTPAuth = false; // MailHog doesn't require auth
            $this->mailer->Username = SMTP_USER;
            $this->mailer->Password = SMTP_PASS;
            
            // No encryption for MailHog
            $this->mailer->SMTPSecure = ''; 
            
            $this->mailer->Port = SMTP_PORT;
            
            // Set default from address - with error handling
            $fromEmail = SMTP_USER;
            if (!$this->mailer->validateAddress($fromEmail)) {
                // Fallback to a valid email if the configured one is invalid
                $fromEmail = 'noreply@homewareontap.com';
                error_log("Invalid SMTP_USER email, using fallback: " . $fromEmail);
            }
            
            $this->mailer->setFrom($fromEmail, $this->siteName);
            $this->mailer->addReplyTo($fromEmail, $this->siteName);
            
            // Content format
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
            // Debug output - disable for production, enable only for debugging
            $this->mailer->SMTPDebug = 0; // Set to 0 to disable debug output
            $this->mailer->Debugoutput = 'error_log';
            
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send a transactional email
     */
    public function sendEmail($to, $subject, $body, $altBody = '') {
        try {
            // Validate recipient email
            if (!$this->mailer->validateAddress($to)) {
                error_log("Invalid recipient email: " . $to);
                return false;
            }
            
            // Clear all recipients and headers before sending a new email
            $this->mailer->clearAllRecipients();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();
            $this->mailer->clearReplyTos();
            
            // Add recipient
            $this->mailer->addAddress($to);
            
            // Content
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->wrapInTemplate($body);
            $this->mailer->AltBody = !empty($altBody) ? $altBody : strip_tags($body);
            
            // Send email
            $result = $this->mailer->send();
            
            if (!$result) {
                error_log("Email sending failed. Error: " . $this->mailer->ErrorInfo);
            } else {
                error_log("Email sent successfully to: " . $to);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Email send error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Wrap email content in a consistent template
     */
    private function wrapInTemplate($content) {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Email Template</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0;
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background: white;
                    border-radius: 5px;
                    overflow: hidden;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #A67B5B 0%, #8B6145 100%); 
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h1 {
                    margin: 0;
                    font-size: 28px;
                }
                .content { 
                    background: #ffffff; 
                    padding: 30px; 
                }
                .button { 
                    display: inline-block; 
                    padding: 12px 30px; 
                    background: #A67B5B; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px; 
                    margin: 20px 0;
                    font-weight: bold;
                    text-align: center;
                }
                .button:hover {
                    background: #8B6145;
                }
                .footer { 
                    text-align: center; 
                    padding: 20px; 
                    font-size: 12px; 
                    color: #666;
                    background: #f9f9f9;
                    border-top: 1px solid #eee;
                }
                .verification-link {
                    word-break: break-all;
                    background: #f9f9f9;
                    padding: 10px;
                    border-radius: 3px;
                    border: 1px solid #ddd;
                    margin: 10px 0;
                }
                @media only screen and (max-width: 600px) {
                    .container {
                        width: 100% !important;
                    }
                    .content {
                        padding: 20px !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . htmlspecialchars($this->siteName) . '</h1>
                </div>
                <div class="content">
                    ' . $content . '
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($this->siteName) . '. All rights reserved.</p>
                    <p>123 Design Street, Creative District, Johannesburg, South Africa</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Send welcome/verification email to new users
     */
    public function sendWelcomeEmail($userEmail, $userName, $verificationToken) {
        $verificationLink = $this->siteUrl . "/pages/auth/verify.php?token=" . $verificationToken;
        
        $subject = "Welcome to " . $this->siteName . " - Verify Your Email";
        $body = "
            <h2>Welcome to " . htmlspecialchars($this->siteName) . ", " . htmlspecialchars($userName) . "!</h2>
            <p>Thank you for registering with us. To complete your registration, please verify your email address by clicking the button below:</p>
            
            <div style='text-align: center;'>
                <a href='" . htmlspecialchars($verificationLink) . "' class='button'>Verify Email Address</a>
            </div>
            
            <p>Or copy and paste this link in your browser:</p>
            <div class='verification-link'>
                <a href='" . htmlspecialchars($verificationLink) . "'>" . htmlspecialchars($verificationLink) . "</a>
            </div>
            
            <p>This verification link will expire in 24 hours.</p>
            
            <p>If you did not create an account with " . htmlspecialchars($this->siteName) . ", please ignore this email.</p>
        ";
        
        return $this->sendEmail($userEmail, $subject, $body);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($userEmail, $userName, $resetToken) {
        $resetLink = $this->siteUrl . "/pages/auth/reset-password.php?token=" . $resetToken;
        
        $subject = "Password Reset Request - " . $this->siteName;
        $body = "
            <h2>Hello " . htmlspecialchars($userName) . ",</h2>
            <p>You recently requested to reset your password for your " . htmlspecialchars($this->siteName) . " account. Click the button below to reset it:</p>
            
            <div style='text-align: center;'>
                <a href='" . htmlspecialchars($resetLink) . "' class='button'>Reset Password</a>
            </div>
            
            <p>Or copy and paste this link into your browser:</p>
            <div class='verification-link'>
                <a href='" . htmlspecialchars($resetLink) . "'>" . htmlspecialchars($resetLink) . "</a>
            </div>
            
            <p>This password reset link is valid for 1 hour. If you did not request a password reset, please ignore this email.</p>
        ";
        
        return $this->sendEmail($userEmail, $subject, $body);
    }
    
    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($userEmail, $userName, $orderDetails) {
        $orderLink = $this->siteUrl . "/pages/account/orders.php?order_id=" . $orderDetails['id'];
        
        $subject = "Order Confirmation #" . $orderDetails['order_number'] . " - " . $this->siteName;
        
        // Build products table
        $productsTable = "
            <table width='100%' border='0' cellspacing='0' cellpadding='10' style='border-collapse: collapse;'>
                <tr style='background-color: #f1f1f1;'>
                    <th align='left' style='border-bottom: 2px solid #ddd;'>Product</th>
                    <th align='right' style='border-bottom: 2px solid #ddd;'>Quantity</th>
                    <th align='right' style='border-bottom: 2px solid #ddd;'>Price</th>
                </tr>
        ";
        
        foreach ($orderDetails['items'] as $item) {
            $productsTable .= "
                <tr>
                    <td style='border-bottom: 1px solid #ddd;'>" . htmlspecialchars($item['name']) . "</td>
                    <td align='right' style='border-bottom: 1px solid #ddd;'>" . htmlspecialchars($item['quantity']) . "</td>
                    <td align='right' style='border-bottom: 1px solid #ddd;'>R " . number_format($item['price'], 2) . "</td>
                </tr>
            ";
        }
        
        $productsTable .= "
                <tr>
                    <td colspan='2' align='right'><strong>Subtotal:</strong></td>
                    <td align='right'>R " . number_format($orderDetails['subtotal'], 2) . "</td>
                </tr>
                <tr>
                    <td colspan='2' align='right'><strong>Shipping:</strong></td>
                    <td align='right'>R " . number_format($orderDetails['shipping'], 2) . "</td>
                </tr>
                <tr>
                    <td colspan='2' align='right'><strong>Total:</strong></td>
                    <td align='right'><strong>R " . number_format($orderDetails['total'], 2) . "</strong></td>
                </tr>
            </table>
        ";
        
        $body = "
            <h2>Thank you for your order, " . htmlspecialchars($userName) . "!</h2>
            <p>Your order #" . htmlspecialchars($orderDetails['order_number']) . " has been confirmed and is being processed.</p>
            
            <h3>Order Summary</h3>
            " . $productsTable . "
            
            <h3>Shipping Address</h3>
            <p>" . nl2br(htmlspecialchars($orderDetails['shipping_address'])) . "</p>
            
            <p>You can view your order details and track its status anytime in your <a href='" . htmlspecialchars($this->siteUrl . "/pages/account/orders.php") . "'>account dashboard</a>.</p>
            
            <p>If you have any questions about your order, please reply to this email or contact our support team.</p>
        ";
        
        return $this->sendEmail($userEmail, $subject, $body);
    }
    
    /**
     * Send order status update email
     */
    public function sendOrderStatusUpdate($userEmail, $userName, $orderDetails) {
        $orderLink = $this->siteUrl . "/pages/account/orders.php?order_id=" . $orderDetails['id'];
        
        $subject = "Order #" . $orderDetails['order_number'] . " Update - " . $this->siteName;
        $body = "
            <h2>Hello " . htmlspecialchars($userName) . ",</h2>
            <p>Your order #" . htmlspecialchars($orderDetails['order_number']) . " status has been updated to: <strong>" . ucfirst($orderDetails['status']) . "</strong></p>
            
            <p><strong>Latest Update:</strong> " . htmlspecialchars($orderDetails['status_message']) . "</p>
            
            " . (!empty($orderDetails['tracking_number']) ? "
            <p><strong>Tracking Number:</strong> " . htmlspecialchars($orderDetails['tracking_number']) . "</p>
            <p><strong>Tracking Link:</strong> <a href='" . htmlspecialchars($orderDetails['tracking_link']) . "'>" . htmlspecialchars($orderDetails['tracking_link']) . "</a></p>
            " : "") . "
            
            <p>You can view your order details and track its status anytime in your <a href='" . htmlspecialchars($orderLink) . "'>account dashboard</a>.</p>
            
            <p>If you have any questions about your order, please reply to this email or contact our support team.</p>
        ";
        
        return $this->sendEmail($userEmail, $subject, $body);
    }
    
    /**
     * Send contact form submission to admin
     */
    public function sendContactFormSubmission($formData) {
        $subject = "New Contact Form Submission - " . $this->siteName;
        $body = "
            <h2>New Contact Form Submission</h2>
            <p><strong>Name:</strong> " . htmlspecialchars($formData['name']) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($formData['email']) . "</p>
            <p><strong>Subject:</strong> " . htmlspecialchars($formData['subject']) . "</p>
            <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($formData['message'])) . "</p>
            <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>
        ";
        
        // This is sent to the admin email defined in SMTP_USER
        return $this->sendEmail(SMTP_USER, $subject, $body);
    }
    
    /**
     * Send newsletter subscription confirmation
     */
    public function sendNewsletterConfirmation($email) {
        $subject = "Welcome to our Newsletter - " . $this->siteName;
        $body = "
            <h2>Thank you for subscribing!</h2>
            <p>You've successfully subscribed to the " . htmlspecialchars($this->siteName) . " newsletter.</p>
            <p>You'll be the first to know about new products, exclusive offers, and home decor tips.</p>
            <p>If you change your mind, you can unsubscribe at any time using the link in our emails.</p>
        ";
        
        return $this->sendEmail($email, $subject, $body);
    }
    
    /**
     * Send low stock alert to admin
     */
    public function sendLowStockAlert($productDetails) {
        $productLink = $this->siteUrl . "/admin/products/edit.php?id=" . $productDetails['id'];
        
        $subject = "Low Stock Alert: " . $productDetails['name'] . " - " . $this->siteName;
        $body = "
            <h2>Low Stock Alert</h2>
            <p>The following product is running low on stock:</p>
            <p><strong>Product:</strong> <a href='" . htmlspecialchars($productLink) . "'>" . htmlspecialchars($productDetails['name']) . "</a></p>
            <p><strong>SKU:</strong> " . htmlspecialchars($productDetails['sku']) . "</p>
            <p><strong>Current Stock:</strong> " . htmlspecialchars($productDetails['stock_quantity']) . "</p>
            <p><strong>Alert Threshold:</strong> " . htmlspecialchars($productDetails['low_stock_threshold']) . "</p>
            <p>Please consider restocking this item to avoid inventory issues.</p>
        ";
        
        // This is sent to the admin email defined in SMTP_USER
        return $this->sendEmail(SMTP_USER, $subject, $body);
    }
}

// Global function for backward compatibility
function sendEmail($to, $subject, $message, $headers = '') {
    $emailService = new EmailService();
    // The 'headers' argument is ignored but the altBody is correctly derived from the message
    return $emailService->sendEmail($to, $subject, $message, strip_tags($message));
}
?>