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
