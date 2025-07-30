#!/bin/bash
set -e

# Function to wait for MySQL to be ready
wait_for_mysql() {
    echo "Waiting for MySQL to be ready..."
    max_attempts=30
    attempt=0

    while [ $attempt -lt $max_attempts ]; do
        if php -r "try { new PDO('mysql:host=${DB_HOST};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'Connected to MySQL successfully!'; } catch (PDOException \$e) { exit(1); }" 2>/dev/null; then
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

# Wait for MySQL to be ready before running Laravel commands
if [ "$APP_ENV" != "testing" ]; then
    wait_for_mysql

    # Run Laravel commands
    echo "Running Laravel migrations..."
    php artisan migrate --force

    echo "Clearing Laravel cache..."
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

    echo "Optimizing Laravel..."
    php artisan optimize
fi

# Start PHP-FPM
echo "Starting PHP-FPM..."
exec "$@"
