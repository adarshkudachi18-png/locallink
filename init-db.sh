#!/bin/bash

# Support both Railway and standard environment variables
DB_HOST="${MYSQLHOST:-${DB_HOST:-localhost}}"
DB_USER="${MYSQLUSER:-${DB_USER:-root}}"
DB_PASS="${MYSQLPASSWORD:-${DB_PASS:-}}"
DB_NAME="${MYSQL_DATABASE:-${DB_NAME:-railway}}"

echo "Using MySQL host: $DB_HOST"
echo "Using database: $DB_NAME"
echo "Using user: $DB_USER"

# Test connection with visible errors (disable SSL for Railway)
echo "Testing MySQL connection..."
mysql --ssl=0 -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" 2>&1
CONNECTION_RESULT=$?

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
ATTEMPTS=0
MAX_ATTEMPTS=30

while [ $ATTEMPTS -lt $MAX_ATTEMPTS ]; do
    if mysql --ssl=0 -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" > /dev/null 2>&1; then
        echo "MySQL is ready!"
        break
    fi
    ATTEMPTS=$((ATTEMPTS + 1))
    echo "MySQL is not ready yet. Attempt $ATTEMPTS/$MAX_ATTEMPTS. Waiting..."
    sleep 2
done

if [ $ATTEMPTS -eq $MAX_ATTEMPTS ]; then
    echo "ERROR: Could not connect to MySQL after $MAX_ATTEMPTS attempts"
    echo "Trying to show last error:"
    mysql --ssl=0 -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" 2>&1
    echo "Continuing anyway..."
fi

# Drop existing tables to ensure clean schema import
echo "Dropping existing tables..."
mysql --ssl=0 -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SET FOREIGN_KEY_CHECKS=0; DROP TABLE IF EXISTS orders, order_items, cart, products, categories, admins, users, testimonials, tickets, messages, faqs, settings; SET FOREIGN_KEY_CHECKS=1;" 2>&1

# Import database schema
echo "Importing database schema..."
mysql --ssl=0 -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/database.sql 2>&1
echo "Database schema import completed!"

# Start the web server (apache2-foreground)
echo "Starting web server..."
exec "$@"
