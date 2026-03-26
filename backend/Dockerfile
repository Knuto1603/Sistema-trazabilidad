FROM php:8.3-fpm

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev libssl-dev libicu-dev libjpeg-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP
RUN docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip opcache intl gd

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependencias primero (cache de capas)
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Código fuente
COPY backend/ .

# Directorios de cache/logs de cada app + uploads
RUN mkdir -p apps/security/var apps/core/var config/jwt apps/core/public/uploads/facturacion \
    && chown -R www-data:www-data apps/security/var apps/core/var config/jwt apps/core/public/uploads \
    && chmod -R 775 apps/security/var apps/core/var apps/core/public/uploads

# Configuración PHP-FPM
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Entrypoint: decodifica JWT keys y levanta PHP-FPM
COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
