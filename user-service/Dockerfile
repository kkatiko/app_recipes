FROM php:8.2-fpm

# 1. Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo_mysql

# 2. Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer \
    && chmod +x /usr/local/bin/composer

# 3. Рабочая директория
WORKDIR /var/www

# 4. Копируем только composer файлы сначала
COPY composer.json composer.lock ./

# 5. Установка зависимостей
RUN composer install --no-interaction --optimize-autoloader --no-scripts

# 6. Копируем остальные файлы проекта
COPY . .

# 7. Создаем недостающие директории
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p bootstrap/cache

# 8. Настройка прав
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# 9. Порт и команда
EXPOSE 9000
CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]

RUN docker-php-ext-install pdo pdo_mysql
COPY . /var/www/html
