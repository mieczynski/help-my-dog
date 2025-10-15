# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-fpm-alpine

ENV COMPOSER_ALLOW_SUPERUSER=1

# Runtime libs (persist in final image) — add libzip here
RUN apk add --no-cache \
    bash git curl \
    icu icu-libs \
    libpng libjpeg-turbo freetype \
    postgresql-libs \
    libzip

# Build deps (removed later)
RUN apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS \
      icu-dev libzip-dev postgresql-dev \
      libpng-dev libjpeg-turbo-dev freetype-dev \
      build-base linux-headers \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" intl pdo_pgsql zip gd bcmath opcache \
 && pecl install xdebug-3.4.5 \
 && docker-php-ext-enable xdebug \
 && apk del .build-deps

COPY --from=composer:2.7 /usr/bin/composer /usr/local/bin/composer

# Xdebug 3 config (v2 keys removed)
RUN { \
      echo "xdebug.mode=debug"; \
      echo "xdebug.start_with_request=trigger"; \
      echo "xdebug.discover_client_host=1"; \
      echo "xdebug.client_host=host.docker.internal"; \
      echo "xdebug.client_port=9000"; \
      echo "xdebug.idekey=PHPSTORM"; \
    } >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN addgroup -g 1000 -S www-data || true \
 && adduser  -u 1000 -S -D -G www-data www-data || true \
 && chown -R www-data:www-data /var/www

WORKDIR /var/www/html/api

COPY docker/php/entrypoint.sh /entrypoint.sh
RUN sed -i "s/\r$//" /entrypoint.sh && chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm", "-F"]
