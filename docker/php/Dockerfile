FROM php:8.4-fpm-alpine AS symfony_php

ARG SYMFONY_VERSION=7.2.*

RUN apk add --no-cache acl fcgi file gettext git mysql-client unzip libzip-dev libpng-dev \
    libjpeg-turbo-dev freetype-dev icu-dev postgresql-dev libxml2-dev php84-pecl-xdebug

RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j$(nproc) \
    intl zip pdo pdo_mysql pdo_pgsql gd opcache exif bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/sh -D app

WORKDIR /srv/app

COPY composer.* symfony.* ./

RUN if [ -f "composer.json" ]; then composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction; fi

COPY . .
RUN chown -R app:app /srv/app
USER app
EXPOSE 9000
CMD ["php-fpm"]

