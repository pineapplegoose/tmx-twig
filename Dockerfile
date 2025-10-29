FROM php:8.2-fpm

# Install system packages
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    nginx supervisor gettext-base \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# PHP-FPM config
RUN printf "listen = 127.0.0.1:9000\nlisten.owner = www-data\nlisten.group = www-data\n" \
    > /usr/local/etc/php-fpm.d/zz-docker.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js for Webpack Encore
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www/html

# Copy composer files first (for layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-cache

# Copy package files
COPY package.json package-lock.json ./
RUN npm ci --production=false

# Copy all app files
COPY . .

# Finish composer install
RUN composer dump-autoload --optimize --classmap-authoritative

# Build assets
RUN npm run build

# Copy nginx template
COPY nginx.conf.template /etc/nginx/sites-available/default.template
RUN mkdir -p /etc/nginx/sites-enabled

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Symfony cache and permissions
RUN php bin/console cache:clear --env=prod --no-debug || true \
    && php bin/console cache:warmup --env=prod --no-debug || true \
    && php bin/console assets:install public --env=prod || true

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 var/cache var/log \
    && find var -type d -exec chmod 775 {} \; \
    && find var -type f -exec chmod 664 {} \;

EXPOSE 10000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]