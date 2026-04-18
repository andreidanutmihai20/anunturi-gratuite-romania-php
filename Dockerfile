FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libssl-dev pkg-config zip unzip curl git \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install mongodb && docker-php-ext-enable mongodb

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 80

CMD php -S 0.0.0.0:${PORT:-80} router.php
