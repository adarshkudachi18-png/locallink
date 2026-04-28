# YBT Digital – Digital Product Selling Website

A fully responsive digital product selling website with adaptive layouts for mobile and desktop, built with PHP and Bootstrap.

## Features

### User Side
- **Authentication**: Signup, Login, Forgot Password, Profile Management
- **Landing Page**: Hero section, Featured products, Testimonials, FAQ, Footer
- **Product Listing**: Grid (desktop) / Scrollable cards (mobile), Filters, Search
- **Product Detail**: Screenshots, Description, Price, Related Products
- **Cart & Checkout**: Add to cart, Secure checkout with payment method selection
- **Orders & Downloads**: Purchased products list, Secure download with expiry, Invoice
- **Support**: Contact form, FAQ page

### Admin Side
- **Dashboard**: Stats, Revenue chart, Top products, Recent orders
- **Product Management**: Add/Edit/Delete products, File upload, Screenshots
- **Order Management**: View orders, Update status, View invoices
- **Reports & Analytics**: Daily/monthly sales, Top products, Category revenue
- **Support Management**: Tickets with messaging, Contact messages, FAQ management
- **Settings**: Payment gateway keys, Tax settings, Branding, Email/SMTP, Download limits
- **Categories & Testimonials**: Full CRUD management

### UI/UX
- **Dark/Light Mode**: Toggle with persistent cookie
- **Mobile**: Material Design-inspired, AppBar + Bottom Navigation, Full-width cards
- **Desktop**: Professional navbar, Grid layouts, Sidebar admin panel
- **Animations**: Smooth transitions, Scroll animations, Hover effects
- **Responsive**: Mobile-first, scales to tablet and desktop

## Installation

1. **Import Database**: Run `database.sql` in phpMyAdmin or MySQL CLI
2. **Configure Database**: Edit `includes/config.php` if your DB credentials differ
3. **Access Site**: Open `http://localhost/sk%20ecommerce/`

## Default Admin Credentials

- **Email**: admin@ybtdigital.com
- **Password**: admin123

## Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5.3, Bootstrap Icons, Inter Font
- **JS**: Vanilla JavaScript (no framework dependency)

## Directory Structure

```
├── admin/                  # Admin panel
│   ├── assets/css/         # Admin styles
│   ├── includes/           # Admin header/footer
│   ├── categories.php
│   ├── faqs.php
│   ├── index.php           # Dashboard
│   ├── login.php
│   ├── messages.php
│   ├── orders.php
│   ├── products.php
│   ├── reports.php
│   ├── settings.php
│   ├── testimonials.php
│   └── tickets.php
├── assets/
│   ├── css/style.css       # Main stylesheet (dark/light themes)
│   ├── js/main.js          # Main JavaScript
│   ├── img/                # Images
│   └── downloads/          # Digital product files (protected)
├── includes/
│   ├── config.php          # DB connection & helpers
│   ├── header.php          # User header + nav
│   ├── footer.php          # User footer
│   └── product_card.php    # Reusable card component
├── database.sql            # Full schema + sample data
├── index.php               # Landing page
├── signup.php / login.php  # Auth pages
├── products.php            # Product listing
├── product.php             # Product detail
├── cart.php                # Shopping cart
├── checkout.php            # Checkout
├── orders.php              # User orders
├── profile.php             # User profile
├── contact.php / faq.php   # Support pages
└── download.php / invoice.php
```
