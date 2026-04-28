FROM php:8.1-apache

# Install MySQL extension and mysql client
RUN docker-php-ext-install mysqli pdo_mysql && apt-get update && apt-get install -y default-mysql-client && rm -rf /var/lib/apt/lists/*

# Remove conflicting MPM modules to fix "More than one MPM loaded" error
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_event.conf /etc/apache2/mods-enabled/mpm_worker.conf

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy files
COPY . /var/www/html/

# Make init script executable
RUN chmod +x /var/www/html/init-db.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html/
RUN chmod -R 755 /var/www/html/

# Set Apache document root
RUN sed -i 's|/var/www/html|/var/www/html|g' /etc/apache2/sites-available/000-default.conf

# Run database initialization script before starting Apache
ENTRYPOINT ["/var/www/html/init-db.sh"]
CMD ["apache2-foreground"]

EXPOSE 80
