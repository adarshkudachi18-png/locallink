#!/bin/bash

# Support both Railway and standard environment variables
DB_HOST="${MYSQLHOST:-${DB_HOST:-localhost}}"
DB_USER="${MYSQLUSER:-${DB_USER:-root}}"
DB_PASS="${MYSQLPASSWORD:-${DB_PASS:-}}"
DB_NAME="${MYSQL_DATABASE:-${DB_NAME:-railway}}"

echo "Using MySQL host: $DB_HOST"
echo "Using database: $DB_NAME"

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" > /dev/null 2>&1; do
    echo "MySQL is not ready yet. Waiting..."
    sleep 2
done

echo "MySQL is ready!"

# Check if database schema already exists
if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES" | grep -q "users"; then
    echo "Database schema already exists. Skipping import."
else
    echo "Importing database schema..."
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/database.sql
    echo "Database schema imported successfully!"
fi
