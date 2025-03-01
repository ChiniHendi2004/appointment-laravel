#!/bin/bash

# Install required system dependencies
apt-get update && apt-get install -y unzip curl php-cli php-mbstring git sqlite3 libsqlite3-dev

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install Laravel dependencies
composer install --no-dev --prefer-dist --optimize-autoloader

# Set up application key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Optimize configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create symbolic link for storage
php artisan storage:link

echo "✅ Laravel app is ready!"
