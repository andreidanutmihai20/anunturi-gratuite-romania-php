FROM php:8.2-cli

RUN apt-get update && apt-get install -y libssl-dev pkg-config \
    && rm -rf /var/lib/apt/lists/* \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

WORKDIR /app
COPY index.php .
COPY router.php .

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} router.php"]
