FROM php:8.1-apache

# Install MySQL extension
RUN docker-php-ext-install mysqli pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

# Set Apache document root
RUN sed -i 's|/var/www/html|/var/www/html|g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80
