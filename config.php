<?php

/**
 * Site configuration.
 *
 * Every setting can come from an environment variable, which makes it possible
 * to deploy through Docker and a .env file without writing a single secret into
 * a tracked file. The fallbacks below cover a plain PHP hosting install, where
 * this file is edited directly.
 *
 * Upstream's Site Settings dialog rewrites config.php in place, which cannot
 * work against values read from the environment: the variable wins on the next
 * request and the save looks lost. The form is therefore not rendered, and its
 * handler is not registered.
 *
 * php-fpm clears the environment by default, so the project image sets
 * `clear_env = no` for getenv() to see those variables inside a container.
 */

function config_value(string $name, string $fallback): string
{
    $value = getenv($name);

    // A variable that is defined but empty is a deliberate choice, such as a
    // disabled password, rather than an absent setting.
    return $value === false ? $fallback : $value;
}


/**
 * Same as config_value(), for settings an empty string cannot describe.
 *
 * A blank site name is never a deliberate choice, unlike a blank password, and
 * the compose file forwards these variables whether the deployment sets them
 * or not.
 */
function config_text(string $name, string $fallback): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $fallback : $value;
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
define('DEFAULT_WEB_THEME', config_value('DEFAULT_WEB_THEME', 'dark')); // Available values: dark, light; visitors can override it in the browser
define('SITE_NAME_EN', config_text('SITE_NAME_EN', 'CS2 Loadout Manager')); // English name and fallback
define('SITE_NAME_ZH_CN', config_text('SITE_NAME_ZH_CN', 'CS2 饰品管理器')); // Simplified Chinese name
define('ENABLE_SKIN_FUSION', config_flag('ENABLE_SKIN_FUSION', true)); // Allow cross-weapon paint combinations

// The Connect to Server button in the header. Empty address hides it; with a
// password the button copies the console command instead of launching the game.
define('SERVER_ADDRESS', config_value('SERVER_ADDRESS', '')); // "hostname or IP:port"
define('SERVER_PASSWORD', config_value('SERVER_PASSWORD', '')); // Leave empty to connect directly

// Players may set their own StatTrak kill count. The counter is game state the
// plugin increments on its own, so an editable field lets anyone claim a record
// they never earned: turn this on to make it read-only.
define('LOCK_STATTRAK_COUNT', config_flag('LOCK_STATTRAK_COUNT', false));

define('AUTH_RATE_LIMIT_ATTEMPTS', config_int('AUTH_RATE_LIMIT_ATTEMPTS', 5)); // Failed attempts allowed within the time window
define('AUTH_RATE_LIMIT_WINDOW_SECONDS', config_int('AUTH_RATE_LIMIT_WINDOW_SECONDS', 1800)); // Failure tracking window in seconds
define('AUTH_RATE_LIMIT_LOCK_SECONDS', config_int('AUTH_RATE_LIMIT_LOCK_SECONDS', 300)); // Lock duration in seconds

define('SITE_ACCESS_PASSWORD', config_value('SITE_ACCESS_PASSWORD', '')); // Set a password to enable access protection
define('ADMIN_PASSWORD', config_value('ADMIN_PASSWORD', '')); // Set a password to enable administrator mode

define('DB_HOST', config_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', config_value('DB_PORT', '3306'));
define('DB_NAME', config_value('DB_NAME', 'your_db_name'));
define('DB_USER', config_value('DB_USER', 'your_db_user'));
define('DB_PASS', config_value('DB_PASS', 'your_db_password'));
