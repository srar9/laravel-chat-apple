#!/bin/bash

# Clear configuration cache to load environment variables correctly
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations automatically in production
echo "Running migrations..."
php artisan migrate --force

# Start the PHP application using PHP's built-in web server with multiple workers
echo "Starting web server on port ${PORT:-8080}..."
export PHP_CLI_SERVER_WORKERS=6
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
