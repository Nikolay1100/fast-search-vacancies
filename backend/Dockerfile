FROM php:8.4-fpm-bookworm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
      git \
      curl \
      libpng-dev \
      libonig-dev \
      libxml2-dev \
      zip \
      unzip \
      libpq-dev \
      libpq5

# Очистка кеша
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка расширений PHP
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd sockets && \
      pecl install redis && \
      docker-php-ext-enable redis

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка рабочей директории
WORKDIR /var/www

COPY . .

# Установка зависимостей Laravel
RUN composer install --no-interaction --optimize-autoloader --no-dev

RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]
