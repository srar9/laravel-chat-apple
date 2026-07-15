#!/bin/bash

# Clear configuration cache to load environment variables correctly
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations automatically in production
echo "Running migrations..."
php artisan migrate --force

# Start the PHP CLI server directly (inherits all OS environment variables securely)
echo "Starting web server on port ${PORT:-8080}..."
export PHP_CLI_SERVER_WORKERS=6

exec php -S 0.0.0.0:${PORT:-8080} -t public
