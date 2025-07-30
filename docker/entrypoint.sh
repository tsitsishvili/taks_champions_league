#!/bin/bash
set -e

# Check if .env file exists and is not empty, otherwise copy .env.docker to .env
if [ ! -f "/var/www/.env" ] || [ ! -s "/var/www/.env" ]; then
    echo "No .env file found or it's empty. Copying .env.docker to .env..."
    cp /var/www/.env.docker /var/www/.env
    echo ".env.docker has been copied to .env"
fi

# Load environment variables from .env file
if [ -f "/var/www/.env" ]; then
    echo "Loading environment variables from .env file..."
    export $(grep -v '^#' /var/www/.env | xargs)
    echo "Environment variables loaded"
fi

# Function to wait for MySQL to be ready
wait_for_mysql() {
    echo "Waiting for MySQL to be ready..."
    max_attempts=30
    attempt=0

    while [ $attempt -lt $max_attempts ]; do
        if php -r "try { new PDO('mysql:host=${DB_HOST};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'Connected to MySQL successfully!'; } catch (PDOException \$e) { echo \$e->getMessage(); exit(1); }" 2>/dev/null; then
            echo "MySQL is ready!"
            return 0
        fi

        attempt=$((attempt+1))
        echo "Attempt $attempt/$max_attempts: MySQL not ready yet. Waiting 2 seconds..."
        sleep 2
    done

    echo "Could not connect to MySQL after $max_attempts attempts. Exiting."
    exit 1
}

# Install Composer dependencies if vendor directory is empty or missing
if [ ! -d "/var/www/vendor" ] || [ -z "$(ls -A /var/www/vendor)" ]; then
    echo "Vendor directory is empty or missing. Installing Composer dependencies..."
    cd /var/www
    composer install --no-interaction --prefer-dist --optimize-autoloader
else
    echo "Vendor directory exists and is not empty. Skipping Composer install."
fi

# Check if APP_KEY is empty and generate one if needed
if [ -z "$APP_KEY" ]; then
    echo "No application encryption key found. Generating a new APP_KEY..."
    APP_KEY=$(php artisan key:generate --show)

    # Update the .env file with the new key
    sed -i "s#APP_KEY=#APP_KEY=$APP_KEY#" /var/www/.env

    # Export the APP_KEY so it's available to the current process
    export APP_KEY

    echo "APP_KEY has been set and saved to .env file."
fi

# Wait for MySQL to be ready before running Laravel commands
if [ "$APP_ENV" != "testing" ]; then
    # Wait for MySQL to be ready
    wait_for_mysql

    # Run Laravel commands
    echo "Clearing Laravel cache..."
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    php artisan migrate --force

    echo "Optimizing Laravel..."
    php artisan optimize
fi

# Start PHP-FPM
echo "Starting PHP-FPM..."
exec "$@"
