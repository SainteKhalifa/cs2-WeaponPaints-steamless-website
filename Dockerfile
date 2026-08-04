FROM php:8.2-fpm-alpine

# Extensions réellement utilisées par le site :
#   pdo_mysql  class/database.php
#   mbstring   longueur des noms personnalisés en UTF-8 (sinon comptés en octets)
#   curl       tools/update_cs2_data.php
#   opcache    les données CS2 sont volumineuses, le gain est net
# gd, mysqli et les bibliothèques d'images ne servent à rien ici.
#
# libcurl et oniguruma sont installés hors du paquet virtuel : les en-têtes
# -dev partent après la compilation, mais les bibliothèques d'exécution doivent
# rester, sinon les extensions compilées ne se chargent plus.
#
# `php --ri` sert de sonde : elle sort en 0 quand l'extension est présente.
# opcache est une extension Zend, elle se nomme « Zend OPcache », pas
# « opcache » : la chercher sous ce dernier nom ferait échouer la vérification.
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
            || { echo "extension absente apres construction : $ext" >&2; exit 1; }; \
    done

# php-fpm efface l'environnement par défaut : sans clear_env, getenv() ne
# verrait aucune variable passée par docker-compose et class/config.php
# retomberait silencieusement sur ses valeurs de repli.
# L'accès est déjà journalisé par nginx, avec le statut et la taille en plus :
# le second journal de php-fpm ne ferait que dupliquer chaque ligne.
RUN printf '%s\n' \
        '[www]' \
        'clear_env = no' \
        'access.log = /dev/null' \
        > /usr/local/etc/php-fpm.d/zz-env.conf

# Sonde du HEALTHCHECK. Volontairement hors de /var/www/html : monter le dépôt
# en volume recouvre la racine web, pas ce fichier. Elle exerce nginx, php-fpm
# et l'exécution de PHP sans toucher à l'application ni à la base.
RUN printf '%s\n' '<?php header("Content-Type: text/plain"); echo "pong";' \
        > /var/www/health.php

# memory_limit : le décodage de data/stickers_*.json (près de 4 Mo) dépasse la
# limite de 128 Mo par défaut sur certaines configurations.
# validate_timestamps reste actif pour que le montage du dépôt en volume
# reflète les modifications sans reconstruire l'image.
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

# Copié pour que l'image tourne seule ; monter le dépôt en volume le recouvre
# simplement, si l'on préfère éditer à chaud.
COPY --chown=www-data:www-data . /var/www/html

# Sans ces liens, nginx écrit dans des fichiers internes au conteneur : ni les
# requêtes ni surtout ses erreurs n'apparaissent dans `docker compose logs`.
RUN set -eux; \
    mkdir -p /run/nginx /var/log/nginx; \
    ln -sf /dev/stdout /var/log/nginx/access.log; \
    ln -sf /dev/stderr /var/log/nginx/error.log; \
    chown -R www-data:www-data /var/www/html /run/nginx

EXPOSE 80

# Interroge le ping de php-fpm : vérifie nginx et PHP sans toucher ni à
# l'application ni à la base de données.
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -qO- http://127.0.0.1/healthz 2>/dev/null | grep -q pong || exit 1

# La timezone est écrite au démarrage et non à la construction, pour que TZ
# reste pilotable depuis le .env sans reconstruire l'image.
# php-fpm passe en arrière-plan, nginx devient le processus principal ; si
# php-fpm ne démarre pas, le conteneur s'arrête au lieu de servir des 502.
CMD ["sh", "-c", "printf 'date.timezone=%s\\n' \"${TZ:-UTC}\" > /usr/local/etc/php/conf.d/zz-timezone.ini && php-fpm -D && exec nginx -g 'daemon off;'"]
