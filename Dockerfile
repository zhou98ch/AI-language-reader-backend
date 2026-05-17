FROM composer:2 AS vendor

WORKDIR /app

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

FROM php:8.4-cli

WORKDIR /app

RUN docker-php-ext-install pdo_mysql

COPY --from=vendor /app /app

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t public"]
