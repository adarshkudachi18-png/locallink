#!/bin/bash
# EC2 Setup Script for LocalLink Digital
# Run on Ubuntu EC2 instance

set -e

echo "=== LocalLink Digital - EC2 Setup Script ==="

# Update system
sudo apt-get update
sudo apt-get upgrade -y

# Install Apache, PHP, and MySQL extensions
sudo apt-get install -y apache2 php8.1 php8.1-mysql php8.1-gd php8.1-curl php8.1-mbstring php8.1-xml php8.1-zip php8.1-pdo-mysql libapache2-mod-php

# Enable required Apache modules
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers

# Install AWS CLI (for downloading SSL bundle if needed)
sudo apt-get install -y awscli unzip

# Create app directory
sudo mkdir -p /var/www/locallink
sudo chown -R ubuntu:ubuntu /var/www/locallink

echo "=== Installing Application ==="

# Note: Application files should be deployed here via SCP, Git, or CodeDeploy
# Example: scp -i locallink-key-current.pem -r * ubuntu@<EC2_IP>:/var/www/locallink/

echo "=== Configuring Apache ==="

# Create Apache virtual host
sudo tee /etc/apache2/sites-available/locallink.conf << 'EOF'
<VirtualHost *:80>
    ServerName _default_
    DocumentRoot /var/www/locallink
    
    <Directory /var/www/locallink>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/locallink-error.log
    CustomLog ${APACHE_LOG_DIR}/locallink-access.log combined
</VirtualHost>
EOF

# Enable site and disable default
sudo a2ensite locallink
sudo a2dissite 000-default

echo "=== Creating .htaccess ==="

sudo tee /var/www/locallink/.htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ $1 [L]

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Disable directory browsing
Options -Indexes

# PHP settings
php_value upload_max_filesize 64M
php_value post_max_size 64M
php_value max_execution_time 300
EOF

echo "=== Setting Permissions ==="

# Set proper permissions
sudo chown -R www-data:www-data /var/www/locallink
sudo chmod -R 755 /var/www/locallink
sudo chmod -R 775 /var/www/locallink/assets/img/uploads
sudo chmod -R 775 /var/www/locallink/assets/img/products
sudo chmod -R 775 /var/www/locallink/assets/img/screenshots
sudo chmod -R 775 /var/www/locallink/assets/downloads

echo "=== Downloading RDS SSL Bundle ==="

# Download RDS SSL bundle
cd /var/www/locallink
wget -O global-bundle.pem https://truststore.pki.rds.amazonaws.com/eu-north-1/eu-north-1-bundle.pem
sudo chown www-data:www-data global-bundle.pem
sudo chmod 644 global-bundle.pem

echo "=== Setting Environment Variables ==="

# Create environment file
sudo tee /etc/apache2/conf-available/locallink-env.conf << 'EOF'
SetEnv DB_HOST locallink-digital-db.c5c8c4wkwd0m.eu-north-1.rds.amazonaws.com
SetEnv DB_NAME ybt_digital
SetEnv DB_USER admin
SetEnv DB_SSL_MODE VERIFY_IDENTITY
SetEnv DB_SSL_CA /var/www/locallink/global-bundle.pem
SetEnv SITE_URL http://YOUR_EC2_PUBLIC_IP
EOF

sudo a2enconf locallink-env

echo "=== Restarting Apache ==="
sudo systemctl restart apache2
sudo systemctl enable apache2

echo "=== Setup Complete ==="
echo "Next steps:"
echo "1. Deploy application files to /var/www/locallink"
echo "2. Update SITE_URL in /etc/apache2/conf-available/locallink-env.conf"
echo "3. Set DB_PASS environment variable securely"
echo "4. Import database.sql to RDS"
echo "5. Configure EC2 Security Group to allow HTTP (80)"
