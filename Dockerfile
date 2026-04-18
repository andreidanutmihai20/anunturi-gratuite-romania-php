<<<<<<< HEAD
FROM php:8.2-cli

RUN apt-get update && apt-get install -y libssl-dev pkg-config \
    && rm -rf /var/lib/apt/lists/* \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

WORKDIR /app
COPY index.php .
COPY router.php .
COPY start.sh .
RUN chmod +x start.sh

EXPOSE 80

CMD ["/bin/sh", "start.sh"]
=======
FROM php:8.2-apache

# Instaleaza extensii necesare
RUN apt-get update && apt-get install -y \
    libssl-dev \
    pkg-config \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Instaleaza extensia MongoDB pentru PHP
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Instaleaza Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Activeaza mod_rewrite pentru Apache (necesar pentru router)
RUN a2enmod rewrite

# Configureaza Apache sa permita .htaccess
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/allow-htaccess.conf \
    && a2enconf allow-htaccess

WORKDIR /var/www/html

# Copiaza fisierele proiectului
COPY . .

# Instaleaza dependentele PHP
RUN composer install --no-dev --optimize-autoloader

EXPOSE 80

CMD ["apache2-foreground"]
>>>>>>> 057697b (s)
