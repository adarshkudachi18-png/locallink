FROM php:8.1-cli

# Install MySQL extension and mysql client
RUN docker-php-ext-install mysqli pdo_mysql && apt-get update && apt-get install -y default-mysql-client && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy files
COPY . /var/www/html/

# Make init script executable
RUN chmod +x /var/www/html/init-db.sh

# Create downloads directory (images will be created by init script for volume compatibility)
RUN mkdir -p /var/www/html/assets/downloads

# Set permissions
RUN chmod -R 755 /var/www/html/
RUN chmod -R 777 /var/www/html/assets/downloads

# Expose port 80
EXPOSE 80

# Run database initialization then start PHP built-in server
ENTRYPOINT ["/var/www/html/init-db.sh"]
CMD ["php", "-S", "0.0.0.0:80", "-t", "/var/www/html"]
