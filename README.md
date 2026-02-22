# HomewareOnTap

HomewareOnTap is a modern, feature-rich e‑commerce platform for premium home essentials, built with PHP/MySQL. It provides a seamless shopping experience for customers and a comprehensive administrative backend for managing products, orders, customers, and promotions.

---

## ✨ Features

### Customer‑Facing Features
- **Responsive Design** – Mobile‑first layout using Bootstrap 5.
- **Product Catalog** – Browse products by category, search, and filter by price.
- **Product Detail Pages** – Image gallery, detailed descriptions, reviews, and “add to cart”.
- **Shopping Cart** – Add/remove items, update quantities, apply discount coupons.
- **Checkout** – Guest checkout or registered user, address selection, PayFast payment integration.
- **User Accounts** – Registration, login, email verification, password reset, order history, wishlist, address book, saved payment methods.
- **Reviews & Ratings** – Customers can review products after purchase.
- **Newsletter Subscription** – Double opt‑in subscription with email confirmation.
- **Static Pages** – About, Contact, FAQs, Terms, Privacy, Returns & Refunds.
- **Social Media Integration** – Facebook, Instagram, TikTok links.

### Administrative Features
- **Dashboard** – Real‑time analytics (orders, revenue, customer count, low stock alerts).
- **Product Management** – Add, edit, delete products; manage categories; import/export via CSV; track inventory; low stock notifications.
- **Order Management** – View orders, update status, process refunds, generate invoices.
- **Customer Management** – View customer list, see order history, manage communication.
- **Marketing Tools** – Create discount coupons, manage banners, send newsletters.
- **Content Management** – Edit static pages and FAQs.
- **Reports** – Sales reports, product performance, customer analytics; export to Excel.
- **System Settings** – Configure site name, email, currency, tax rate, shipping costs, security rules, WhatsApp integration, exam mode, etc.
- **Security Logs** – Track login attempts, admin activities, password changes.

---

## 🛠️ Technology Stack

| Component          | Technology                                      |
|--------------------|-------------------------------------------------|
| **Backend**        | PHP 8.2+ (PDO MySQL)                            |
| **Database**       | MySQL 5.7+ / MariaDB 10.4+                      |
| **Frontend**       | HTML5, CSS3, JavaScript (ES6+)                   |
| **CSS Framework**  | Bootstrap 5.3                                    |
| **Icons**          | Font Awesome 6, Bootstrap Icons                  |
| **Charts**         | Chart.js                                         |
| **Animations**     | Animate.css                                      |
| **Payment Gateway**| PayFast (South Africa)                           |
| **Email**          | SendGrid (via PHPMailer)                         |
| **Social Login**   | MojoAuth (Facebook, Google)                       |
| **Security**       | Prepared statements, password_hash(), CSRF tokens, XSS prevention, session security |
| **Development**    | XAMPP / WAMP / LAMP stack                        |

---

## 📋 Requirements

- Web server with PHP 7.4 or higher (Apache / Nginx recommended)
- MySQL 5.7+ or MariaDB 10.4+
- mod_rewrite enabled (for clean URLs, optional)
- PHP extensions: PDO, mysqli, session, json, fileinfo, curl
- Composer (for installing dependencies)
- SSL certificate for production (required for PayFast)

---

## ⚙️ Installation

### 1. Clone the repository
```bash
git clone https://github.com/yourusername/homewareontap.git
cd homewareontap
```

### 2. Set up your local server
Place the project folder in your web root (e.g., `htdocs` for XAMPP).

### 3. Install dependencies
```bash
composer install
```

### 4. Create the database
- Open phpMyAdmin (or your MySQL client).
- Create a new database named `homewareontap_db`.
- Import the provided SQL dump (`homewareontap_db.sql`) into the database.

### 5. Configure the application
Copy the configuration template:
```bash
cp includes/config.example.php includes/config.php
```
Edit `includes/config.php` with your database credentials, site URL, and other settings:
```php
// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'homewareontap_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site URL (adjust to your local path)
define('SITE_URL', 'http://localhost/homewareontap');

// Email (SendGrid) settings
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-sendgrid-username');
define('SMTP_PASS', 'your-sendgrid-password');
define('MAIL_FROM', 'noreply@homewareontap.com');

// PayFast configuration (sandbox for testing)
define('PF_MERCHANT_ID', '10000100');
define('PF_MERCHANT_KEY', '46f0cd694581a');
define('PF_PASSPHRASE', 'your-passphrase');
define('PF_DEBUG', true); // false for production

// Security settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('PASSWORD_MIN_LENGTH', 8);

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // set to 1 when using HTTPS
```

### 6. Set file permissions
Ensure the following directories are writable by the web server:
- `assets/uploads/`
- `logs/` (create if not exists)

### 7. Run the application
Navigate to `http://localhost/homewareontap/` in your browser. The homepage should load with sample data.

---

## 🔐 Default Admin Account

After importing the SQL dump, you can log in with:

| Role    | Email                          | Password   |
|---------|--------------------------------|------------|
| Admin   | admin@homewareontap.co.za      | password   |
| Customer| john@example.com               | password   |

> **Important**: Change these passwords immediately after first login!

---

## 📁 Project Structure

```
homewareontap/
├── admin/                         # Admin dashboard
├── assets/                        # Static assets (CSS, JS, images, uploads)
├── includes/                      # Core includes (config, database, functions, auth)
├── lib/                            # Third-party libraries (PayFast, PHPMailer)
├── pages/                          # Frontend pages
│   ├── account/                    # User account pages
│   ├── auth/                        # Authentication pages
│   ├── checkout/                    # Checkout process
│   ├── payment/                     # PayFast return handlers
│   ├── static/                      # Static content pages
│   ├── index.php                    # Homepage
│   ├── shop.php                     # Product catalog
│   └── product-detail.php           # Product detail page
├── system/                          # MVC framework (controllers, models, views)
├── vendor/                           # Composer dependencies
├── .htaccess                         # Apache configuration
├── composer.json                      # Composer dependencies
└── README.md                          # This file
```

---

## 🧩 Key Modules

### Authentication (`includes/auth.php`)
- User registration with email verification.
- Secure login with password_hash().
- Password reset via email token.
- “Remember me” functionality with secure tokens.
- Role‑based access control (`isAdminLoggedIn()`, `isLoggedIn()`).

### Database Layer (`includes/database.php`)
- Singleton PDO connection with UTF‑8 encoding.
- Prepared statements for all queries (`fetchSingle`, `executeQuery`, etc.).
- Transaction support.

### Core Functions (`includes/functions.php`)
- **Security:** CSRF token generation/validation, input sanitization, XSS prevention.
- **Cart/Order:** `addToCart()`, `getCartItems()`, `calculateCartTotal()`, `applyCouponToCart()`, `createOrder()`.
- **Product:** `getProducts()`, `getProductById()`, `getCategories()`, `getFeaturedProducts()`.
- **User:** `getUserById()`, `getUserAddresses()`, `getUserPaymentMethods()`.
- **Formats:** `format_price()`, `generateStarRating()`, `time_elapsed_string()`.
- **Dashboard Statistics:** `getDashboardStatistics()`, `getSalesChartData()`, `getCategoryChartData()`.

### Admin Dashboard (`admin/index.php`)
- Real‑time statistics with Chart.js.
- Quick actions for product, order, and customer management.
- Recent orders and top products tables.
- System status overview.

### Payment Integration (`lib/payfast/`)
- PayFast payment form generation.
- Instant Transaction Notification (ITN) handler (`pages/payment/itn.php`).
- Return and cancel URL handlers.

### Email System (`includes/email.php`)
- PHPMailer with SendGrid SMTP.
- HTML email templates for welcome, order confirmation, password reset, etc.

---

## 🗄️ Database Schema

The database `homewareontap_db` contains the following main tables:

| Table                     | Description                                      |
|---------------------------|--------------------------------------------------|
| `users`                   | Customer and admin accounts                      |
| `products`                | Product catalog with stock tracking              |
| `categories`              | Product categories (hierarchical)                |
| `orders`                  | Order headers                                    |
| `order_items`             | Items within each order                          |
| `carts`                   | Shopping cart sessions (guest and user)          |
| `cart_items`              | Items in carts                                   |
| `addresses`               | Customer addresses (shipping/billing)            |
| `payments`                | Payment transactions                             |
| `reviews`                 | Product reviews and ratings                      |
| `wishlist`                | Saved items                                      |
| `coupons`                 | Discount codes                                   |
| `inventory_log`           | Stock change audit trail                         |
| `user_notifications`      | In‑app notifications                             |
| `newsletter_subscriptions`| Email marketing subscribers                      |
| `site_settings`           | Configuration settings                           |

For a complete schema, see `homewareontap_db.sql`.

---

## 🔒 Security Considerations

- **Prepared Statements** – All database queries use PDO prepared statements to prevent SQL injection.
- **Password Hashing** – Passwords are hashed with `password_hash()` (bcrypt).
- **CSRF Protection** – Every form includes a CSRF token validated on submission.
- **XSS Prevention** – User input is sanitized with `htmlspecialchars()` on output.
- **Session Security** – Session cookies are HTTP‑only, and sessions are regenerated after login.
- **Login Throttling** – Failed login attempts are limited; accounts are temporarily locked after multiple failures.
- **File Uploads** – Only allowed file types and sizes; files are stored outside the webroot (or with restrictive .htaccess).
- **HTTPS** – All production traffic should be encrypted; session cookies are set to secure only.
- **PayFast** – ITN validation ensures payment notifications are authentic.

---

## 🧪 Testing

- Run all user flows: registration, login, product search, add to cart, apply coupon, checkout (sandbox), order tracking.
- Verify responsive behaviour on mobile devices.
- Check accessibility (contrast, keyboard navigation, ARIA labels).
- Validate HTML/CSS using W3C validators.
- Test error handling: invalid login, expired coupon, out‑of‑stock items.
- Run PHPUnit tests (if implemented) for backend logic.

---

## 🚀 Deployment to Production

1. **Update `includes/config.php`** with production database credentials, site URL, and enable HTTPS.
2. **Set `PF_DEBUG = false`** in PayFast configuration.
3. **Enable HTTPS** and set `session.cookie_secure = 1` in `config.php`.
4. **Configure web server** (Apache/Nginx) to point to the project root.
5. **Set proper file permissions** (e.g., `755` for directories, `644` for files).
6. **Run database migrations** if any.
7. **Test the live site** thoroughly.
8. **Set up cron jobs** for scheduled tasks (e.g., daily database backup, low‑stock alerts).

---

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository.
2. Create a feature branch (`git checkout -b feature/AmazingFeature`).
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`).
4. Push to the branch (`git push origin feature/AmazingFeature`).
5. Open a Pull Request.

Please ensure your code adheres to the existing style (PSR‑12 inspired) and includes proper escaping and prepared statements.

---

## 📄 License

This project is open‑source software licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgements

- [Bootstrap](https://getbootstrap.com/) – front‑end framework
- [Font Awesome](https://fontawesome.com/) – icons
- [Chart.js](https://www.chartjs.org/) – charts and graphs
- [PayFast](https://www.payfast.io/) – South African payment gateway
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) – email sending
- [SendGrid](https://sendgrid.com/) – email delivery service
- [MojoAuth](https://mojoauth.com/) – social login integration
- [Unsplash](https://unsplash.com/) – placeholder images

---

## 📞 Support

For technical issues or questions, please open an issue on GitHub or contact the development team at [homewareontap@gmail.com](mailto:homewareontap@gmail.com).

---

*Made with ❤️ for South African homes.*
