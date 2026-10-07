FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    librabbitmq-dev \
    libssl-dev \
    libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql intl zip \
    && pecl channel-update pecl.php.net \
    && pecl install -f redis-6.2.0 \
    && pecl install -f amqp-2.1.2 \
    && pecl install -f xdebug-3.4.5 \
    && docker-php-ext-enable redis amqp xdebug \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
