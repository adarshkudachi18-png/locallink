#!/bin/bash

# Support both Railway and standard environment variables
DB_HOST="${MYSQLHOST:-${DB_HOST:-localhost}}"
DB_USER="${MYSQLUSER:-${DB_USER:-root}}"
DB_PASS="${MYSQLPASSWORD:-${DB_PASS:-}}"
DB_NAME="${MYSQL_DATABASE:-${DB_NAME:-railway}}"

echo "Using MySQL host: $DB_HOST"
echo "Using database: $DB_NAME"
echo "Using user: $DB_USER"

# Create image directories (for Railway volume compatibility)
echo "Creating image directories..."
mkdir -p /var/www/html/assets/img/products /var/www/html/assets/img/uploads /var/www/html/assets/img/screenshots
chmod -R 777 /var/www/html/assets/img
ls -la /var/www/html/assets/img/

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

# Check if tables already exist - only import schema if database is empty
TABLE_COUNT=$(mysql --ssl=0 -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME'" -s -N 2>&1)

if [ "$TABLE_COUNT" -eq 0 ]; then
    echo "Database is empty. Importing schema..."
    mysql --ssl=0 -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/database.sql 2>&1
    IMPORT_RESULT=$?
    echo "Import result: $IMPORT_RESULT"
else
    echo "Database already has $TABLE_COUNT tables. Skipping schema import to preserve data."
    # Add missing columns if they don't exist
    echo "Checking for missing columns..."
    # Check and add customer_mobile column
    mysql --ssl=0 -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
    SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = '$DB_NAME' AND table_name = 'orders' AND column_name = 'customer_mobile');
    SET @sql = IF(@col_exists = 0, 'ALTER TABLE orders ADD COLUMN customer_mobile VARCHAR(20) DEFAULT NULL', 'SELECT \"customer_mobile already exists\"');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    " 2>&1
    # Check and add delivery_status column
    mysql --ssl=0 -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
    SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = '$DB_NAME' AND table_name = 'orders' AND column_name = 'delivery_status');
    SET @sql = IF(@col_exists = 0, 'ALTER TABLE orders ADD COLUMN delivery_status ENUM(\"pending\",\"shipped\",\"delivered\",\"cancelled\") DEFAULT \"pending\"', 'SELECT \"delivery_status already exists\"');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    " 2>&1
    # Make file_path nullable in products table
    mysql --ssl=0 -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
    ALTER TABLE products MODIFY COLUMN file_path VARCHAR(255) DEFAULT NULL;
    " 2>&1
fi

# Verify tables were created
echo "Verifying tables..."
mysql --ssl=0 -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES;" 2>&1
echo "Database schema import completed!"

# Start the web server (apache2-foreground)
echo "Starting web server..."
exec "$@"
