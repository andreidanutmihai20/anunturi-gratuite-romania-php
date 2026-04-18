FROM php:8.2-apache

# Instaleaza dependente sistem
RUN apt-get update && apt-get install -y \
    libssl-dev pkg-config zip unzip curl git \
    && rm -rf /var/lib/apt/lists/*

# Instaleaza extensia MongoDB
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Instaleaza Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Activeaza mod_rewrite, dezactiveaza MPM conflictuale
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork rewrite

# Permite .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
    /etc/apache2/apache2.conf

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction

# Script start: Railway injecteaza PORT dinamic
RUN printf '#!/bin/bash\nPORT="${PORT:-80}"\nsed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf\nsed -i "s/*:80>/*:${PORT}>/" /etc/apache2/sites-available/000-default.conf\nexec apache2-foreground\n' \
    > /start.sh && chmod +x /start.sh

EXPOSE 80

CMD ["/bin/bash", "/start.sh"]
