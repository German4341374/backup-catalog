# syntax=docker/dockerfile:1.10
FROM composer:2.10.2@sha256:4d71c3c2109c61d5415544264b59ad4087e4c5b7244481723664138fd36d5040 AS composer_tools

FROM php:8.5.10-fpm-alpine3.24@sha256:22a4c414bb8e91ac7aefe9b1d80e832caa67252aca58b6af7eeb3bc92188fc5b AS base

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
