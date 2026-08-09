<?php

/**
 * Site configuration.
 *
 * Every setting can come from an environment variable, which makes it possible
 * to deploy through Docker and a .env file without writing a single secret into
 * a tracked file. The fallbacks below cover a plain PHP hosting install, where
 * this file is edited directly.
 *
 * php-fpm clears the environment by default, so the project image sets
 * `clear_env = no` for getenv() to see these variables inside a container.
 */

function config_value(string $name, string $fallback): string
{
    $value = getenv($name);

    // A variable that is defined but empty is a deliberate choice, such as a
    // disabled password, rather than an absent setting.
    return $value === false ? $fallback : $value;
}

function config_int(string $name, int $fallback): int
{
    $value = getenv($name);
    if ($value === false || $value === '' || !is_numeric($value)) {
        return $fallback;
    }

    return (int)$value;
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
define('SITE_NAME_EN', config_value('SITE_NAME_EN', 'CS2 WeaponPaints Loadout Manager')); // English name and fallback
define('SITE_NAME_ZH_CN', config_value('SITE_NAME_ZH_CN', 'CS2 WeaponPaints 配置管理器')); // Simplified Chinese name
define('SITE_ACCESS_PASSWORD', config_value('SITE_ACCESS_PASSWORD', '')); // Set a password to enable access protection
define('ADMIN_PASSWORD', config_value('ADMIN_PASSWORD', '')); // Leave empty to disable administrator mode

define('AUTH_RATE_LIMIT_ATTEMPTS', config_int('AUTH_RATE_LIMIT_ATTEMPTS', 5)); // Failed attempts allowed within the time window
define('AUTH_RATE_LIMIT_WINDOW_SECONDS', config_int('AUTH_RATE_LIMIT_WINDOW_SECONDS', 1800)); // Failure tracking window: 30 minutes
define('AUTH_RATE_LIMIT_LOCK_SECONDS', config_int('AUTH_RATE_LIMIT_LOCK_SECONDS', 60)); // Lock duration: 1 minute

define('ENABLE_SKIN_FUSION', config_flag('ENABLE_SKIN_FUSION', true)); // Allow cross-weapon paint combinations

// The StatTrak kill counter is game state the plugin increments on its own.
// Letting players type it in is an open invitation to fake their record, so it
// is read-only unless a server owner deliberately opens it up.
define('ALLOW_STATTRAK_COUNT', config_flag('ALLOW_STATTRAK_COUNT', false));

define('DB_HOST', config_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', config_value('DB_PORT', '3306'));
define('DB_NAME', config_value('DB_NAME', 'your_db_name'));
define('DB_USER', config_value('DB_USER', 'your_db_user'));
define('DB_PASS', config_value('DB_PASS', 'your_db_password'));

define('WEB_STYLE_DARK', config_flag('WEB_STYLE_DARK', true));
