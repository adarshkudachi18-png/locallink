-- Local Link - Database Schema
-- Run this in phpMyAdmin or MySQL CLI
-- Database name is set by environment (DB_NAME or MYSQL_DATABASE)

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mobile_number VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default.png',
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expiry DATETIME DEFAULT NULL,
    otp VARCHAR(6) DEFAULT NULL,
    otp_expiry DATETIME DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admins table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','editor') DEFAULT 'editor',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) UNIQUE NOT NULL,
    description TEXT,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(280) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    category_id INT,
    thumbnail VARCHAR(255),
    file_path VARCHAR(255) NOT NULL,
    file_size VARCHAR(50),
    location VARCHAR(255) DEFAULT NULL,
    version VARCHAR(50) DEFAULT '1.0',
    status ENUM('active','inactive') DEFAULT 'active',
    featured TINYINT(1) DEFAULT 0,
    downloads INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Product screenshots
CREATE TABLE product_screenshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY user_product (user_id, product_id)
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    customer_mobile VARCHAR(20) DEFAULT NULL,
    delivery_status ENUM('pending','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_title VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    download_token VARCHAR(255) DEFAULT NULL,
    download_expiry DATETIME DEFAULT NULL,
    download_count INT DEFAULT 0,
    max_downloads INT DEFAULT 3,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Support tickets
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    priority ENUM('low','medium','high') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Ticket messages
CREATE TABLE ticket_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    sender_type ENUM('user','admin') NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
);

-- FAQ table
CREATE TABLE faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Testimonials table
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    designation VARCHAR(100),
    company VARCHAR(100),
    message TEXT NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default.png',
    rating TINYINT DEFAULT 5,
    sort_order INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Contact messages
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Local Link'),
('site_tagline', 'Local Link - Premium Digital Products'),
('site_logo', ''),
('footer_text', '© 2026 Local Link. All rights reserved.'),
('primary_color', '#6c5ce7'),
('accent_color', '#00cec9'),
('currency', 'INR'),
('currency_symbol', 'Rs.'),
('tax_enabled', '1'),
('tax_type', 'GST'),
('tax_percentage', '18'),
('payment_gateway', 'razorpay'),
('razorpay_key_id', ''),
('razorpay_key_secret', ''),
('paypal_client_id', ''),
('paypal_secret', ''),
('stripe_publishable_key', ''),
('stripe_secret_key', ''),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_from_email', ''),
('smtp_from_name', 'Local Link'),
('email_order_confirmation', '1'),
('email_payment_failed', '1'),
('email_password_reset', '1'),
('max_downloads_per_purchase', '3'),
('download_expiry_days', '7');

-- Insert default admin (password: admin123)
INSERT INTO admins (name, email, password, role) VALUES
('Super Admin', 'admin@123', '$2y$10$ak06pG8JCbwxGyws8BB8eesI9vQZixdR0EQ/ll06BCF2.JM0hHFbK', 'super_admin');

-- Insert sample categories
INSERT INTO categories (name, slug, description) VALUES
('Software', 'software', 'Desktop & web applications'),
('Templates', 'templates', 'Website & app templates'),
('E-Books', 'e-books', 'Digital books and guides'),
('Graphics', 'graphics', 'Design assets and graphics'),
('Courses', 'courses', 'Online learning courses');

-- Insert sample FAQs
INSERT INTO faqs (question, answer, sort_order) VALUES
('How do I download my purchased products?', 'After completing your purchase, go to the Orders/Downloads page in your account. You will find a secure download button next to each purchased product.', 1),
('What payment methods do you accept?', 'We accept major credit/debit cards, UPI, net banking, and popular digital wallets through our secure payment gateway.', 2),
('Can I get a refund?', 'Due to the digital nature of our products, refunds are handled on a case-by-case basis. Please contact support within 24 hours of purchase if you experience issues.', 3),
('How many times can I download a product?', 'By default, each purchase allows up to 3 downloads within 7 days. Check your order details for specific limits.', 4),
('Do you offer product updates?', 'Yes! When a product you purchased receives an update, you will be notified and can download the latest version at no extra cost.', 5);

-- Insert sample testimonials
INSERT INTO testimonials (name, designation, company, message, rating) VALUES
('Rahul Sharma', 'Web Developer', 'TechCorp', 'Amazing collection of digital products. The templates saved me weeks of development time!', 5),
('Priya Patel', 'Designer', 'CreativeStudio', 'High-quality graphics and design assets. The pricing is very competitive for what you get.', 5),
('Amit Kumar', 'Entrepreneur', 'StartupHub', 'The courses here are top-notch. Helped me launch my online business in record time.', 4);
