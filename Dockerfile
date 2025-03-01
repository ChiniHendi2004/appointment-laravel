# Use the official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    git \
    libpq-dev \
    libpng-dev \
    && docker-php-ext-install pdo pdo_mysql gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy Laravel project files
COPY . .

# Install Laravel dependencies
RUN composer install --no-dev --prefer-dist

# Set Laravel permissions
RUN chmod -R 775 storage bootstrap/cache

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
