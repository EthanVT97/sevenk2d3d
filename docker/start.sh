#!/bin/bash

# Maximum number of retries
MAX_RETRIES=30
RETRY_COUNT=0

echo "Starting database connection check..."
echo "Database Host: $DB_HOST"
echo "Database Name: $DB_NAME"
echo "Database Port: ${DB_PORT:-5432}"

# Wait for database to be ready
until php public/api/test/database.php > /dev/null 2>&1; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        echo "Error: Maximum retry count ($MAX_RETRIES) reached. Database is not available."
        echo "Please check your database configuration and ensure the database service is running."
        exit 1
    fi
    echo "Waiting for database... (Attempt $RETRY_COUNT of $MAX_RETRIES)"
    sleep 5
done

echo "Database connection successful!"

# Run database migrations
echo "Running database migrations..."
if php database/migrate.php; then
    echo "Migrations completed successfully!"
else
    echo "Warning: Database migrations failed!"
    # Continue anyway as the tables might already exist
fi

# Start Apache
echo "Starting Apache..."
apache2-foreground 