#!/usr/bin/env bash

# Install PHP dependencies
composer install --no-dev --prefer-dist --optimize-autoloader

# Run database migrations
php artisan migrate --force

# Clear and cache Laravel configuration
php artisan config:clear
php artisan config:cache

# Set correct permissions (optional but recommended)
chmod -R 775 storage bootstrap/cache
