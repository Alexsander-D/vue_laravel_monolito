FROM php:8.3-fpm
<<<<<<< HEAD

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip nano cron \
    libzip-dev libpng-dev libonig-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libwebp-dev libxpm-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd pdo_mysql zip exif pcntl bcmath \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs
=======
>>>>>>> parent of 009e780 (Add EC2 script; migrate Dockerfile & compose)

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip nano cron \
    libzip-dev libpng-dev libonig-dev libxml2-dev \
    libjpeg-dev libfreetype6-dev libwebp-dev libxpm-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd pdo_mysql zip exif pcntl bcmath \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs
    
WORKDIR /var/www/html

# Copia todo o código do projeto
COPY . .

# Instala o composer e as dependencias do projeto
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
<<<<<<< HEAD

# Copia apenas dependências para aproveitar cache
COPY composer.json composer.lock package.json package-lock.json ./
=======
>>>>>>> parent of 009e780 (Add EC2 script; migrate Dockerfile & compose)
RUN composer install --no-dev --optimize-autoloader --no-scripts
RUN npm install

# Garante que vite tem permissão
RUN chmod +x node_modules/.bin/vite

# Build do frontend
RUN npm run build

# Ajusta permissões do Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Configura o agendador do Laravel para rodar dentro do container
RUN echo '* * * * * www-data cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1' > /etc/cron.d/laravel-schedule \
    && chmod 0644 /etc/cron.d/laravel-schedule

<<<<<<< HEAD
# Roda o PHP-FPM e o cron do sistema
=======
# Roda o Laravel e o agendador
>>>>>>> parent of 009e780 (Add EC2 script; migrate Dockerfile & compose)
CMD ["sh", "-c", "cron && php-fpm"]
