FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    supervisor \
    libpq-dev

# Install PHP extensions (including pcntl + Redis)
RUN pecl install redis \
    && docker-php-ext-enable redis

RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Supervisor config
COPY docker/supervisor/horizon.conf /etc/supervisor/conf.d/horizon.conf
COPY docker/supervisor/php-fpm.conf /etc/supervisor/conf.d/php-fpm.conf

WORKDIR /var/www
COPY . .

RUN composer install --no-interaction

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

CMD ["/usr/bin/supervisord", "-n"]
