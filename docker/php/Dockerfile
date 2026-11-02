# syntax=docker/dockerfile:1
FROM composer:2 AS composer
FROM php:8.4-cli-bookworm

RUN --mount=type=secret,id=proxy_ca,target=/run/proxy-ca.pem \
    set -eu; \
    if [ -f /run/proxy-ca.pem ]; then export CURL_CA_BUNDLE=/run/proxy-ca.pem; fi; \
    apt-get -o Acquire::https::CaInfo="${CURL_CA_BUNDLE:-/etc/ssl/certs/ca-certificates.crt}" update; \
    apt-get -o Acquire::https::CaInfo="${CURL_CA_BUNDLE:-/etc/ssl/certs/ca-certificates.crt}" install -y --no-install-recommends git unzip libpq-dev libonig-dev libxml2-dev; \
    docker-php-ext-install -j2 pdo_pgsql mbstring dom xml xmlwriter; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
WORKDIR /app
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
