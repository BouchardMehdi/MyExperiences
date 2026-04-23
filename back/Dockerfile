FROM composer:2 AS composer

FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx libpq-dev \
    && docker-php-ext-install pdo_pgsql

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app

COPY composer.json composer.lock symfony.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts

COPY . .

RUN rm -f /etc/nginx/http.d/default.conf \
    && mkdir -p /run/nginx /var/log/nginx /var/cache/nginx \
    && composer dump-autoload --no-dev --classmap-authoritative \
    && chown -R www-data:www-data /var/www/app/var

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/php/app.ini /usr/local/etc/php/conf.d/app.ini

EXPOSE 80

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
