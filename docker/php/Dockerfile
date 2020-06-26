FROM php:5.6-fpm

RUN docker-php-ext-install pdo_mysql

#RUN pecl install apcu

RUN apt-get update && \
apt-get install -y \
libzip-dev

RUN docker-php-ext-install zip
#RUN docker-php-ext-enable apcu

#RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
#    && php -r "if (hash_file('sha384', 'composer-setup.php') === 'e0012edf3e80b6978849f5eff0d4b4e4c79ff1609dd1e613307e16318854d24ae64f26d17af3ef0bf7cfb710ca74755a') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
#    && php composer-setup.php --filename=composer \
#    && php -r "unlink('composer-setup.php');" \
#    && mv composer /usr/local/bin/composer

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

#COPY ../../ /var/www

RUN PATH=$PATH:/var/www/vendor/bin:bin

CMD ["php-fpm"]

EXPOSE 9000