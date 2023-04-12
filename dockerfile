FROM php:7.3-fpm

RUN apt-get update
RUN apt-get install -y \
           git \
           libzip-dev \
           libc-client-dev \
           libkrb5-dev \
           libpng-dev \
           libjpeg-dev \
           libwebp-dev \
           libfreetype6-dev \
           libkrb5-dev \
           libicu-dev \
           zlib1g-dev \
           zip \
           ffmpeg

RUN docker-php-ext-configure gd \
   --with-webp-dir=/usr/include/ \
   --with-freetype-dir=/usr/include/ \
   --with-jpeg-dir=/usr/include/
RUN docker-php-ext-install gd

RUN docker-php-ext-configure imap \
   --with-kerberos \
   --with-imap-ssl
RUN docker-php-ext-install imap

RUN docker-php-ext-configure zip \
   --with-libzip
RUN docker-php-ext-install zip

RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl

RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install exif

RUN pecl install xdebug
RUN docker-php-ext-enable  xdebug

RUN echo "#zend_extension=/usr/local/lib/php/extensions/no-debug-non-zts-20180731/xdebug.so" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN curl --silent --show-error https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app