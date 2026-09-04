FROM php:8.2-fpm-alpine

# Extensions the site actually uses:
#   pdo_mysql  class/database.php
#   mbstring   custom name lengths in UTF-8, otherwise counted in bytes
#   curl       tools/update_cs2_data.php
#   opcache    the CS2 data files are large and the gain is noticeable
# gd, mysqli and the image libraries serve no purpose here.
#
# libcurl and oniguruma are installed outside the virtual package: the -dev
# headers go away after the build, but the runtime libraries have to stay or the
# compiled extensions will no longer load.
#
# `php --ri` is the probe: it exits 0 when the extension is present. OPcache is a
# Zend extension and is named "Zend OPcache" rather than "opcache" -- looking it
# up under the latter would fail the verification below.
RUN set -eux; \
    apk add --no-cache nginx tzdata libcurl oniguruma; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS curl-dev oniguruma-dev; \
    for ext in pdo_mysql mbstring curl; do \
        php --ri "$ext" >/dev/null 2>&1 || docker-php-ext-install -j"$(nproc)" "$ext"; \
    done; \
    php --ri "Zend OPcache" >/dev/null 2>&1 || docker-php-ext-install -j"$(nproc)" opcache; \
    apk del --no-network .build-deps; \
    rm -rf /tmp/* /var/cache/apk/*; \
    for ext in pdo_mysql mbstring curl "Zend OPcache"; do \
        php --ri "$ext" >/dev/null 2>&1 \
            || { echo "extension missing after build: $ext" >&2; exit 1; }; \
    done

# php-fpm clears the environment by default. Without clear_env, getenv() would
# see none of the variables passed by docker compose and class/config.php would
# silently fall back to its defaults.
# nginx already logs every request, with the status and size on top, so a second
# access log from php-fpm would only duplicate each line.
RUN printf '%s\n' \
        '[www]' \
        'clear_env = no' \
        'access.log = /dev/null' \
        > /usr/local/etc/php-fpm.d/zz-env.conf

# The HEALTHCHECK probe. Deliberately outside /var/www/html: bind-mounting the
# repository covers the web root, not this file. It exercises nginx, php-fpm and
# PHP execution without touching the application or the database.
RUN printf '%s\n' '<?php header("Content-Type: text/plain"); echo "pong";' \
        > /var/www/health.php

# memory_limit: decoding data/stickers_*.json, close to 4 MB, goes past the
# default 128 MB on some setups.
# validate_timestamps stays on so that bind-mounting the repository picks up
# edits without rebuilding the image.
RUN printf '%s\n' \
        'expose_php=0' \
        'memory_limit=256M' \
        'opcache.enable=1' \
        'opcache.memory_consumption=128' \
        'opcache.max_accelerated_files=10000' \
        'opcache.validate_timestamps=1' \
        'opcache.revalidate_freq=2' \
        > /usr/local/etc/php/conf.d/zz-app.ini

COPY nginx.conf /etc/nginx/http.d/default.conf

# Copied so the image runs on its own; bind-mounting the repository simply
# covers it when live editing is preferred.
COPY --chown=www-data:www-data . /var/www/html

# Without these links nginx writes to files inside the container, where neither
# the requests nor, more importantly, its errors reach `docker compose logs`.
RUN set -eux; \
    mkdir -p /run/nginx /var/log/nginx; \
    ln -sf /dev/stdout /var/log/nginx/access.log; \
    ln -sf /dev/stderr /var/log/nginx/error.log; \
    chown -R www-data:www-data /var/www/html /run/nginx

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -qO- http://127.0.0.1/healthz 2>/dev/null | grep -q pong || exit 1

# The timezone is written at startup rather than at build time, so that TZ stays
# driven by the .env file without rebuilding the image.
# php-fpm goes to the background and nginx becomes the main process; if php-fpm
# fails to start, the container stops instead of serving 502s.
CMD ["sh", "-c", "printf 'date.timezone=%s\\n' \"${TZ:-UTC}\" > /usr/local/etc/php/conf.d/zz-timezone.ini && php-fpm -D && exec nginx -g 'daemon off;'"]
