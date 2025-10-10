<?php
// File: includes/email.php

require_once __DIR__ . '/../lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../lib/phpmailer/SMTP.php';
require_once __DIR__ . '/../lib/phpmailer/Exception.php';

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
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USER;
            $this->mailer->Password = SMTP_PASS;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            
            // Set default from address
            $this->mailer->setFrom(SMTP_USER, $this->siteName);
            $this->mailer->addReplyTo(SMTP_USER, $this->siteName);
            
            // Content format
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
        }
    }
    
    /**
     * Send a transactional email
     */
    public function sendEmail($to, $subject, $body, $altBody = '') {
        try {
            // Clear all recipients
            $this->mailer->clearAllRecipients();
            
            // Add recipient
            $this->mailer->addAddress($to);
            
            // Content
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $this->wrapInTemplate($body);
            $this->mailer->AltBody = !empty($altBody) ? $altBody : strip_tags($body);
            
            // Send email
            $this->mailer->send();
            return true;
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
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4a90e2; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 30px; }
                .footer { background-color: #f1f1f1; padding: 20px; text-align: center; font-size: 12px; }
                .button { display: inline-block; padding: 12px 24px; background-color: #4a90e2; 
                         color: white; text-decoration: none; border-radius: 4px; }
                .logo { max-width: 180px; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="' . $this->siteUrl . '/assets/img/logo.png" alt="' . $this->siteName . '" class="logo">
                    <h1>' . $this->siteName . '</h1>
                </div>
                <div class="content">
                    ' . $content . '
                </div>
                <div class="footer">
                    <p>&copy; ' . date('Y') . ' ' . $this->siteName . '. All rights reserved.</p>
                    <p><a href="' . $this->siteUrl . '/pages/privacy.php">Privacy Policy</a> | 
                    <a href="' . $this->siteUrl . '/pages/terms.php">Terms of Service</a></p>
                    <p>If you have any questions, contact us at <a href="mailto:' . SMTP_USER . '">' . SMTP_USER . '</a></p>
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
            <h2>Welcome to " . $this->siteName . ", " . $userName . "!</h2>
            <p>Thank you for registering with us. To get started, please verify your email address by clicking the button below:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='" . $verificationLink . "' class='button'>Verify Email Address</a>
            </p>
            <p>Or copy and paste this link into your browser:<br>
            <a href='" . $verificationLink . "'>" . $verificationLink . "</a></p>
            <p>If you did not create an account with us, please ignore this email.</p>
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
            <h2>Hello " . $userName . ",</h2>
            <p>You recently requested to reset your password for your " . $this->siteName . " account. Click the button below to reset it:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='" . $resetLink . "' class='button'>Reset Password</a>
            </p>
            <p>Or copy and paste this link into your browser:<br>
            <a href='" . $resetLink . "'>" . $resetLink . "</a></p>
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
                    <td style='border-bottom: 1px solid #ddd;'>" . $item['name'] . "</td>
                    <td align='right' style='border-bottom: 1px solid #ddd;'>" . $item['quantity'] . "</td>
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
            <h2>Thank you for your order, " . $userName . "!</h2>
            <p>Your order #" . $orderDetails['order_number'] . " has been confirmed and is being processed.</p>
            
            <h3>Order Summary</h3>
            " . $productsTable . "
            
            <h3>Shipping Address</h3>
            <p>" . nl2br($orderDetails['shipping_address']) . "</p>
            
            <p>You can view your order details and track its status anytime in your <a href='" . $this->siteUrl . "/pages/account/orders.php'>account dashboard</a>.</p>
            
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
            <h2>Hello " . $userName . ",</h2>
            <p>Your order #" . $orderDetails['order_number'] . " status has been updated to: <strong>" . ucfirst($orderDetails['status']) . "</strong></p>
            
            <p><strong>Latest Update:</strong> " . $orderDetails['status_message'] . "</p>
            
            " . (!empty($orderDetails['tracking_number']) ? "
            <p><strong>Tracking Number:</strong> " . $orderDetails['tracking_number'] . "</p>
            <p><strong>Tracking Link:</strong> <a href='" . $orderDetails['tracking_link'] . "'>" . $orderDetails['tracking_link'] . "</a></p>
            " : "") . "
            
            <p>You can view your order details and track its status anytime in your <a href='" . $orderLink . "'>account dashboard</a>.</p>
            
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
            <p><strong>Name:</strong> " . $formData['name'] . "</p>
            <p><strong>Email:</strong> " . $formData['email'] . "</p>
            <p><strong>Subject:</strong> " . $formData['subject'] . "</p>
            <p><strong>Message:</strong><br>" . nl2br($formData['message']) . "</p>
            <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s') . "</p>
        ";
        
        return $this->sendEmail(SMTP_USER, $subject, $body);
    }
    
    /**
     * Send newsletter subscription confirmation
     */
    public function sendNewsletterConfirmation($email) {
        $subject = "Welcome to our Newsletter - " . $this->siteName;
        $body = "
            <h2>Thank you for subscribing!</h2>
            <p>You've successfully subscribed to the " . $this->siteName . " newsletter.</p>
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
            <p><strong>Product:</strong> <a href='" . $productLink . "'>" . $productDetails['name'] . "</a></p>
            <p><strong>SKU:</strong> " . $productDetails['sku'] . "</p>
            <p><strong>Current Stock:</strong> " . $productDetails['stock_quantity'] . "</p>
            <p><strong>Alert Threshold:</strong> " . $productDetails['low_stock_threshold'] . "</p>
            <p>Please consider restocking this item to avoid inventory issues.</p>
        ";
        
        return $this->sendEmail(SMTP_USER, $subject, $body);
    }
}

// Global function for backward compatibility
function sendEmail($to, $subject, $message, $headers = '') {
    $emailService = new EmailService();
    return $emailService->sendEmail($to, $subject, $message, strip_tags($message));
}
?>