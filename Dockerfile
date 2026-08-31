
FROM php:8.3-fpm
 
# System dependencies
RUN apt-get update && apt-get install -y \
    git curl libpq-dev libzip-dev zip unzip nodejs npm \
    && docker-php-ext-install pdo pdo_pgsql zip
 
# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
 
WORKDIR /var/www
 
COPY . .
 
RUN composer install --no-dev --optimize-autoloader --no-interaction
 
RUN php artisan config:cache && php artisan route:cache
 
EXPOSE 8000
 
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000
 