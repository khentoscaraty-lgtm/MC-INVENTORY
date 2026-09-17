# Secure Inventory Management System

A modern, secure PHP-based inventory management system with REST API support and enhanced security features.

## 🚀 Features

### Security Features
- **SQL Injection Protection**: All database queries use prepared statements
- **Modern Password Hashing**: Argon2ID hashing with automatic MD5 migration
- **CSRF Protection**: Cross-site request forgery tokens on all forms
- **Secure Session Management**: Regenerated session IDs, secure cookies
- **Input Validation**: Comprehensive sanitization and validation
- **Error Logging**: Detailed logging system for security monitoring

### Modern UI/UX
- **Responsive Design**: Mobile-first Bootstrap 5 interface
- **Modern Dashboard**: Real-time statistics and analytics
- **Interactive Components**: Smooth animations and transitions
- **Accessibility**: WCAG compliant design patterns

### API Integration
- **RESTful API**: Full CRUD operations for products and orders
- **Token Authentication**: Secure API key-based authentication
- **JSON Responses**: Standardized API response format
- **CORS Support**: Cross-origin resource sharing enabled

## 📋 Requirements

- PHP 7.4+ (8.0+ recommended)
- MySQL 5.7+ / MariaDB 10.2+
- Apache/Nginx web server
- mod_rewrite enabled

## 🛠️ Installation

### 1. Database Setup

```sql
CREATE DATABASE sinventoryphp;
USE sinventoryphp;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Brands table
CREATE TABLE brand (
    brand_id INT AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(100) NOT NULL,
    brand_active INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    categories_id INT AUTO_INCREMENT PRIMARY KEY,
    categories_name VARCHAR(100) NOT NULL,
    categories_active INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE product (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(200) NOT NULL,
    product_code VARCHAR(50) UNIQUE,
    rate DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 0,
    brand_id INT,
    categories_id INT,
    status INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brand(brand_id),
    FOREIGN KEY (categories_id) REFERENCES categories(categories_id)
);

-- Orders table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    grand_total DECIMAL(10,2) NOT NULL,
    paid DECIMAL(10,2) NOT NULL,
    order_status INT DEFAULT 1,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Insert default admin user (password: Password@123)
INSERT INTO users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');
```

### 2. Configuration

1. Copy `php_action/config.php` and update database settings:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'sinventoryphp');
define('BASE_URL', 'http://yourdomain.com/path/');
```

2. Set up API token in `api/index.php`:
```php
define('API_TOKEN', 'your_secure_random_token_here');
```

3. Configure logging directory permissions:
```bash
chmod 755 logs/
chmod 644 logs/*.log
```

### 3. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

# Security headers
add_header X-Frame-Options DENY;
add_header X-Content-Type-Options nosniff;
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
```

## 🔐 Security Implementation

### Password Security
- New passwords use Argon2ID hashing
- Legacy MD5 passwords are automatically upgraded on login
- Password strength requirements enforced

### Session Security
```php
// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Enable with HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
```

### CSRF Protection
```php
// Generate token
$token = generate_csrf_token();

// Verify token
if (!verify_csrf_token($_POST['csrf_token'])) {
    die('CSRF token validation failed');
}
```

### SQL Injection Prevention
```php
// Use prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
```

## 📚 API Documentation

### Authentication
All API requests require authentication via Bearer token:

```http
Authorization: Bearer your_api_token_here
```

### Endpoints

#### Login
```http
POST /api/login
Content-Type: application/json

{
    "username": "admin",
    "password": "Password@123"
}
```

#### Get Products
```http
GET /api/products?page=1&limit=20&search=keyword
```

#### Create Product
```http
POST /api/products
Content-Type: application/json

{
    "product_name": "Product Name",
    "rate": 99.99,
    "quantity": 100,
    "brand_id": 1,
    "categories_id": 1
}
```

#### Get Dashboard Stats
```http
GET /api/dashboard
```

#### Get Orders
```http
GET /api/orders?page=1&limit=20
```

#### Create Order
```http
POST /api/orders
Content-Type: application/json

{
    "user_id": 1,
    "grand_total": 199.99,
    "paid": 199.99
}
```

## 🎯 Usage

### Web Interface
1. Access `http://yourdomain.com/login_secure.php`
2. Login with admin credentials
3. Navigate through the modern dashboard

### API Usage
```bash
# Get authentication token
curl -X POST http://yourdomain.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"Password@123"}'

# Use token to access protected endpoints
curl -X GET http://yourdomain.com/api/products \
  -H "Authorization: Bearer your_token_here"
```

## 📊 Monitoring & Logging

### Log Files
- `logs/app.log` - General application logs
- `logs/error.log` - Error logs
- Logs include timestamps, user actions, IP addresses

### Security Monitoring
- Failed login attempts are logged
- All user actions are tracked
- API requests are monitored
- Security violations are flagged

## 🔧 Maintenance

### Regular Tasks
1. Review log files for suspicious activity
2. Update API tokens periodically
3. Backup database regularly
4. Monitor disk space for logs
5. Update dependencies

### Security Updates
1. Keep PHP version updated
2. Update database server
3. Review security headers
4. Audit user permissions

## 🚨 Troubleshooting

### Common Issues

#### Login Issues
- Check database connection
- Verify password hash migration
- Review session configuration

#### API Issues
- Verify API token
- Check CORS settings
- Review request format

#### Performance Issues
- Optimize database queries
- Implement caching
- Monitor server resources

### Debug Mode
Enable debug mode by setting in `config.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For support and questions:
- Review the documentation
- Check the log files
- Create an issue in the repository

---

**Security Note**: Always keep your system updated and monitor logs for suspicious activity. Change default passwords and API tokens immediately after installation.
