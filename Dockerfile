# syntax=docker/dockerfile:1.10
FROM composer:2.8.12@sha256:5248900ab8b5f7f880c2d62180e40960cd87f60149ec9a1abfd62ac72a02577c AS composer_tools

FROM php:8.5.9-fpm-alpine3.24@sha256:9dc81f4086ea5402227a6bcc489b04b4baba12394624d9621faa92ed812fb8ee AS base

RUN apk add --no-cache libpq \
    && apk add --no-cache --virtual .build-deps postgresql-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql \
    && apk del .build-deps

WORKDIR /app
COPY --from=composer_tools /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock* ./

FROM base AS development
RUN composer install --no-interaction --prefer-dist --no-progress
COPY --chown=www-data:www-data . .
COPY docker/php/app.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/app.conf /usr/local/etc/php-fpm.d/zz-app.conf
USER www-data
CMD ["php-fpm", "-F"]

FROM base AS production
ENV APP_ENV=production APP_DEBUG=false
RUN composer install --no-dev --classmap-authoritative --no-interaction --prefer-dist --no-progress
COPY --chown=www-data:www-data . .
COPY docker/php/app.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/app.conf /usr/local/etc/php-fpm.d/zz-app.conf
USER www-data
CMD ["php-fpm", "-F"]
