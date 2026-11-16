FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip nano cron \
    libzip-dev libpng-dev libonig-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libwebp-dev libxpm-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd pdo_mysql zip exif pcntl bcmath \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs


WORKDIR /var/www/html


COPY . .


RUN curl -sS https://getcomposer.org/installer | php \
    -- --install-dir=/usr/local/bin --filename=composer


RUN composer install --no-dev --optimize-autoloader --no-scripts


RUN npm install


RUN npm run build


RUN chmod -R 775 storage bootstrap/cache


RUN chown -R www-data:www-data \
    storage bootstrap/cache


RUN echo '* * * * * www-data cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1' \
    > /etc/cron.d/laravel-schedule \
    && chmod 0644 /etc/cron.d/laravel-schedule


CMD ["sh", "-c", "cron && php-fpm"]