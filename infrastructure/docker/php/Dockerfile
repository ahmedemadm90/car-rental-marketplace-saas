FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    icu-dev libzip-dev oniguruma-dev libpng-dev libjpeg-turbo-dev freetype-dev \
    mysql-client bash curl git unzip supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl mbstring opcache pdo_mysql zip \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS dependencies
COPY apps/platform/composer.json apps/platform/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

FROM base AS production
ARG APP_UID=1000
ARG APP_GID=1000
RUN addgroup -g "${APP_GID}" app && adduser -D -G app -u "${APP_UID}" app
COPY --from=dependencies /var/www/html/vendor ./vendor
COPY apps/platform ./
COPY infrastructure/php/production.ini /usr/local/etc/php/conf.d/99-production.ini
RUN mkdir -p storage/framework/{cache,sessions,views,testing} storage/logs bootstrap/cache \
    && chown -R app:app storage bootstrap/cache
USER app
EXPOSE 9000
CMD ["php-fpm", "-F"]
