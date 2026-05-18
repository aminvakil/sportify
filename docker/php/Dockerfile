FROM composer:2.2.25 AS composer

FROM php:7.1-cli

RUN set -eux; \
    printf '%s\n' \
        'deb [trusted=yes] http://archive.debian.org/debian stretch main' \
        'deb [trusted=yes] http://archive.debian.org/debian-security stretch/updates main' \
        > /etc/apt/sources.list; \
    echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
        zlib1g-dev \
        libicu-dev \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
    ; \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/; \
    docker-php-ext-install -j"$(nproc)" curl gd intl mbstring opcache pdo_mysql zip; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
