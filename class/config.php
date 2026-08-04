<?php

/**
 * Configuration du site.
 *
 * Chaque réglage peut provenir d'une variable d'environnement, ce qui permet
 * de déployer via Docker et un fichier .env sans écrire le moindre secret dans
 * un fichier suivi par git. Les valeurs de repli ci-dessous couvrent une
 * installation classique sur un hébergement PHP, où l'on édite ce fichier.
 *
 * En conteneur, php-fpm efface l'environnement par défaut : l'image du projet
 * pose `clear_env = no` pour que getenv() voie ces variables.
 */

function config_value(string $name, string $fallback): string
{
    $value = getenv($name);

    // Une variable définie mais vide est une valeur voulue (mot de passe
    // désactivé, par exemple) et non une absence de réglage.
    return $value === false ? $fallback : $value;
}

function config_flag(string $name, bool $fallback): bool
{
    $value = getenv($name);
    if ($value === false || $value === '') {
        return $fallback;
    }

    $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $parsed === null ? $fallback : $parsed;
}

define('DEFAULT_LANGUAGE', config_value('DEFAULT_LANGUAGE', 'en')); // Available values: en, zh-CN
define('SITE_ACCESS_PASSWORD', config_value('SITE_ACCESS_PASSWORD', '')); // Set a password to enable access protection
define('ADMIN_PASSWORD', config_value('ADMIN_PASSWORD', '')); // Leave empty to disable administrator mode

define('DB_HOST', config_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', config_value('DB_PORT', '3306'));
define('DB_NAME', config_value('DB_NAME', 'your_db_name'));
define('DB_USER', config_value('DB_USER', 'your_db_user'));
define('DB_PASS', config_value('DB_PASS', 'your_db_password'));

define('WEB_STYLE_DARK', config_flag('WEB_STYLE_DARK', true));
