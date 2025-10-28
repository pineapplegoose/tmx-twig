# Start from official PHP image
FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    nginx supervisor git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer globally (from official Composer image)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Copy Nginx and Supervisor configs
COPY nginx.conf /etc/nginx/sites-available/default
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Install PHP dependencies (prod only, skip dev packages and scripts)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port (you can change 10000 to 80 or any port your host uses)
EXPOSE 10000

# Start Supervisor (handles PHP-FPM + Nginx)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
