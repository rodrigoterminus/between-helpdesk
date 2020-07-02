FROM php:7.3-fpm-stretch

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

RUN apt-get update && \
    apt-get install -y \
    libzip-dev \
    zlib1g-dev \
    libicu-dev

RUN pecl install \
    apcu

RUN docker-php-ext-configure intl
RUN docker-php-ext-install \
    pdo_mysql \
    zip \
    intl \
    opcache

RUN docker-php-ext-enable \
    intl \
    apcu

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

RUN PATH=$PATH:/var/www/vendor/bin:bin

CMD ["php-fpm"]

EXPOSE 9000