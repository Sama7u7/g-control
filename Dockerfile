FROM php:8.3-fpm

# 1. Instalamos librerías del sistema (Agregamos libpq-dev para Postgres)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip

# 2. Instalar Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 3. Instalar extensiones de PHP (Agregamos pdo_pgsql y pgsql)
RUN docker-php-ext-install pdo_mysql pdo_pgsql pgsql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# 4. Instalamos dependencias (Usamos --ignore-platform-reqs por si las dudas)
RUN composer install --ignore-platform-reqs

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
