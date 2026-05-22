FROM node:7.10.1-stretch

RUN set -eux; \
    printf '%s\n' \
        'deb [trusted=yes] http://archive.debian.org/debian stretch main' \
        'deb [trusted=yes] http://archive.debian.org/debian-security stretch/updates main' \
        > /etc/apt/sources.list; \
    echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until; \
    apt-get update; \
    apt-get install -y --no-install-recommends git; \
    npm install -g bower@1.8.14 gulp-cli@2.3.0; \
    rm -rf /var/lib/apt/lists/* /tmp/npm-*

WORKDIR /app
