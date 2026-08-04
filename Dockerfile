FROM amazonlinux:2023

# Instala dependências do sistema usando yum
RUN yum -y update && yum install -y \
    git curl zip unzip nano cronie \
    libzip-devel libpng-devel oniguruma-devel libxml2-devel \
    libjpeg-turbo-devel libwebp-devel \
    gcc gcc-c++ make openssl-devel bzip2-devel libffi-devel \
    && curl -fsSL https://rpm.nodesource.com/setup_20.x | bash - \
    && yum install -y nodejs \
    && yum install -y php php-fpm php-cli php-mbstring php-json php-mysqlnd php-zip php-gd php-xml php-opcache php-bcmath php-intl php-devel \
    && yum clean all && rm -rf /var/cache/yum

WORKDIR /var/www/html

# Instala o composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Cria usuário www-data se não existir
RUN useradd -r -s /sbin/nologin www-data || true

# Copia apenas dependências para aproveitar cache
COPY composer.json composer.lock package.json package-lock.json ./
RUN composer install --no-dev --optimize-autoloader --no-scripts
RUN npm install

# Copia o resto do código do projeto
COPY . .

# Build do frontend
RUN npm run build

# Ajusta permissões do Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Configura o agendador do Laravel para rodar dentro do container
RUN echo '* * * * * www-data cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1' > /etc/cron.d/laravel-schedule \
    && chmod 0644 /etc/cron.d/laravel-schedule

# Roda o PHP-FPM e o cron do sistema
CMD ["sh", "-c", "crond && php-fpm"]
