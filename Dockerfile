FROM php:8.2-fpm

# Install system packages and nginx/supervisor + envsubst (gettext)
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    nginx supervisor gettext-base \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP extensions (build)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Make PHP-FPM listen on TCP 127.0.0.1:9000 (so nginx can use 127.0.0.1:9000)
RUN printf "listen = 127.0.0.1:9000\nlisten.owner = www-data\nlisten.group = www-data\n" \
    > /usr/local/etc/php-fpm.d/zz-docker.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working dir
WORKDIR /var/www/html

# Copy app files
COPY . .

# Put nginx template where we can envsubst it at container start.
# (we keep the template; supervisor will create the real conf)
COPY nginx.conf.template /etc/nginx/sites-available/default.template

# Link placeholder for enabled site (supervisor will write the real one)
RUN mkdir -p /etc/nginx/sites-enabled

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Install PHP dependencies for production only and avoid running auto scripts that expect dev packages
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-cache

# Clear and warmup Symfony cache for prod
RUN php bin/console cache:clear --env=prod --no-warmup || true \
    && php bin/console cache:warmup --env=prod || true \
    && php bin/console assets:install public --env=prod || true

# Permissions (ensure www-data owns app)
RUN chown -R www-data:www-data /var/www/html \
    && find var -type d -exec chmod 775 {} \; \
    && find var -type f -exec chmod 664 {} \;

# Expose a port (documentary only; Render will set PORT env variable)
EXPOSE 10000

RUN php bin/console cache:clear --env=prod --no-debug && php bin/console cache:warmup --env=prod --no-debug

RUN chmod -R 775 var/cache var/log && chown -R www-data:www-data var/cache var/log


# Start supervisor (supervisor will run a command to envsubst nginx template before launching nginx)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
