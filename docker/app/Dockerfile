FROM php:8.3-fpm-alpine

# Build deps are kept: intl/zip/pgsql need their runtime libs anyway, and the
# image is only ever built on the dev VPS and the client VPS, never shipped.
#
# pcntl is required by Horizon, which forks and signals its worker processes.
# Without it Horizon cannot be installed at all, let alone supervise anything.
RUN apk add --no-cache \
        postgresql-dev icu-dev libzip-dev linux-headers \
        nodejs npm git unzip bash \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip bcmath opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Match the host UID so bind-mounted files stay writable from both sides.
RUN deluser www-data 2>/dev/null || true; \
    addgroup -g 1000 www-data; \
    adduser -u 1000 -G www-data -s /bin/sh -D www-data

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/app/php.ini /usr/local/etc/php/conf.d/kaisan.ini

WORKDIR /var/www/html
